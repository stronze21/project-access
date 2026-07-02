<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SentimentComment;
use App\Models\SentimentPost;
use App\Models\SentimentReaction;
use App\Services\SentimentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobileSentimentController extends Controller
{
    public function __construct(private SentimentService $sentimentService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $mode = $request->string('mode')->toString() === 'trending' ? 'trending' : 'feed';
        $perPage = max(1, min(50, $request->integer('per_page', (int) config('sentiments.page_size', 10))));

        $query = SentimentPost::query()
            ->withTrashed()
            ->visibleTo($user)
            ->with([
                'author:id,name,profile_photo_path',
            ])
            ->withCount([
                'reactions',
                'comments as comments_count' => function ($commentCountQuery) use ($user): void {
                    $commentCountQuery
                        ->withTrashed()
                        ->visibleTo($user);
                },
            ]);

        $query->orderByDesc('is_pinned');
        if ($mode === 'trending') {
            $ageHoursExpression = $this->ageHoursExpression('sentiment_posts.created_at');
            $query->orderByRaw(
                "((COALESCE(reactions_count, 0) * 4 + COALESCE(comments_count, 0) * 6) / NULLIF($ageHoursExpression, 0)) DESC"
            );
            $query->orderByDesc('created_at');
        } else {
            $createdAtEpochExpression = $this->unixTimestampExpression('sentiment_posts.created_at');
            $query->orderByRaw(
                "($createdAtEpochExpression + (COALESCE(reactions_count, 0) * 120) + (COALESCE(comments_count, 0) * 180)) DESC"
            );
        }

        $posts = $query->paginate($perPage)->withQueryString();
        $postIds = $posts->getCollection()->pluck('id')->all();

        $myReactions = empty($postIds)
            ? collect()
            : SentimentReaction::query()
                ->where('user_id', $user->id)
                ->where('reactionable_type', SentimentPost::class)
                ->whereIn('reactionable_id', $postIds)
                ->pluck('reaction', 'reactionable_id');

        return response()->json([
            'data' => $posts
                ->getCollection()
                ->map(fn (SentimentPost $post): array => $this->serializePost($post, $myReactions))
                ->values(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ],
            'reaction_types' => $this->sentimentService->reactionKeys(),
        ]);
    }

    public function media(Request $request, SentimentPost $post): StreamedResponse
    {
        $user = $request->user();
        abort_unless($this->sentimentService->canViewPost($user, $post), 404);
        abort_unless(
            $post->media_kind === SentimentPost::MEDIA_IMAGE || $post->media_kind === SentimentPost::MEDIA_VIDEO,
            404
        );
        abort_unless($post->media_disk && $post->media_path, 404);
        abort_unless(Storage::disk($post->media_disk)->exists($post->media_path), 404);

        $stream = Storage::disk($post->media_disk)->readStream($post->media_path);
        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $post->media_mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$post->media_original_name.'"',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function storePost(Request $request): JsonResponse
    {
        if ($this->sentimentService->isPostingBanned($request->user())) {
            return response()->json([
                'message' => 'Your posting access for People Sentiments is currently disabled.',
            ], 403);
        }

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'external_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $body = trim((string) ($validated['body'] ?? ''));
        $externalUrl = trim((string) ($validated['external_url'] ?? ''));

        if ($body === '' && $externalUrl === '') {
            return response()->json([
                'message' => 'Post text or external link is required.',
            ], 422);
        }

        $post = SentimentPost::create([
            'user_id' => $request->user()->id,
            'body' => $body !== '' ? $body : null,
            'media_kind' => $externalUrl !== '' ? SentimentPost::MEDIA_EXTERNAL : SentimentPost::MEDIA_NONE,
            'external_url' => $externalUrl !== '' ? $externalUrl : null,
        ]);

        $post->load('author:id,name,profile_photo_path')->loadCount(['reactions', 'comments']);

        return response()->json([
            'message' => 'Post published.',
            'data' => $this->serializePost($post, collect()),
        ], 201);
    }

    public function reactPost(Request $request, SentimentPost $post): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->sentimentService->canViewPost($user, $post), 404);

        if ((int) $post->user_id === (int) $user->id) {
            return response()->json([
                'message' => 'You cannot react to your own post.',
            ], 422);
        }

        $validated = $request->validate([
            'reaction' => ['required', Rule::in($this->sentimentService->reactionKeys())],
        ]);

        $reactionType = strtolower((string) $validated['reaction']);
        $reactionValue = $this->upsertReaction($user->id, SentimentPost::class, $post->id, $reactionType);

        $post->loadCount(['reactions', 'comments']);

        return response()->json([
            'message' => 'Reaction updated.',
            'data' => [
                'post_id' => $post->id,
                'reactions_count' => (int) $post->reactions_count,
                'comments_count' => (int) $post->comments_count,
                'my_reaction' => $reactionValue,
            ],
        ]);
    }

    public function comments(Request $request, SentimentPost $post): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->sentimentService->canViewPost($user, $post), 404);

        $perPage = max(1, min(50, $request->integer('per_page', 20)));
        $comments = SentimentComment::query()
            ->withTrashed()
            ->visibleTo($user)
            ->where('post_id', $post->id)
            ->with('author:id,name,profile_photo_path')
            ->withCount('reactions')
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();

        $commentIds = $comments->getCollection()->pluck('id')->all();
        $myReactions = empty($commentIds)
            ? collect()
            : SentimentReaction::query()
                ->where('user_id', $user->id)
                ->where('reactionable_type', SentimentComment::class)
                ->whereIn('reactionable_id', $commentIds)
                ->pluck('reaction', 'reactionable_id');

        return response()->json([
            'data' => $comments
                ->getCollection()
                ->map(fn (SentimentComment $comment): array => $this->serializeComment($comment, $myReactions))
                ->values(),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    public function storeComment(Request $request, SentimentPost $post): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->sentimentService->canViewPost($user, $post), 404);

        if ($this->sentimentService->isPostingBanned($user)) {
            return response()->json([
                'message' => 'Your posting access for People Sentiments is currently disabled.',
            ], 403);
        }

        if ($post->is_comments_locked) {
            return response()->json([
                'message' => 'Comments are locked for this post.',
            ], 422);
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', Rule::exists('sentiment_comments', 'id')],
        ]);

        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;
        if ($parentId !== null) {
            $parentComment = SentimentComment::query()->find($parentId);
            if ($parentComment === null || (int) $parentComment->post_id !== (int) $post->id) {
                return response()->json([
                    'message' => 'Invalid parent comment selected.',
                ], 422);
            }
        }

        $comment = SentimentComment::create([
            'post_id' => $post->id,
            'parent_id' => $parentId,
            'user_id' => $user->id,
            'body' => trim((string) $validated['body']),
        ]);

        $comment->load('author:id,name,profile_photo_path')->loadCount('reactions');

        return response()->json([
            'message' => 'Comment posted.',
            'data' => $this->serializeComment($comment, collect()),
        ], 201);
    }

    public function reactComment(Request $request, SentimentComment $comment): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->sentimentService->canViewComment($user, $comment), 404);

        if ((int) $comment->user_id === (int) $user->id) {
            return response()->json([
                'message' => 'You cannot react to your own comment.',
            ], 422);
        }

        $validated = $request->validate([
            'reaction' => ['required', Rule::in($this->sentimentService->reactionKeys())],
        ]);

        $reactionType = strtolower((string) $validated['reaction']);
        $reactionValue = $this->upsertReaction($user->id, SentimentComment::class, $comment->id, $reactionType);
        $comment->loadCount('reactions');

        return response()->json([
            'message' => 'Reaction updated.',
            'data' => [
                'comment_id' => $comment->id,
                'reactions_count' => (int) $comment->reactions_count,
                'my_reaction' => $reactionValue,
            ],
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $myReactions
     * @return array<string, mixed>
     */
    private function serializePost(SentimentPost $post, $myReactions): array
    {
        return [
            'id' => $post->id,
            'body' => $post->body,
            'media_kind' => $post->media_kind,
            'external_url' => $post->external_url,
            'media_url' => $this->mediaUrl($post),
            'is_pinned' => (bool) $post->is_pinned,
            'is_comments_locked' => (bool) $post->is_comments_locked,
            'reactions_count' => (int) ($post->reactions_count ?? 0),
            'comments_count' => (int) ($post->comments_count ?? 0),
            'my_reaction' => $myReactions[$post->id] ?? null,
            'created_at' => $post->created_at?->toISOString(),
            'edited_at' => $post->edited_at?->toISOString(),
            'author' => $post->author
                ? [
                    'id' => $post->author->id,
                    'name' => $post->author->name,
                    'profile_photo_url' => $post->author->profilePhotoUrl(),
                ]
                : null,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $myReactions
     * @return array<string, mixed>
     */
    private function serializeComment(SentimentComment $comment, $myReactions): array
    {
        return [
            'id' => $comment->id,
            'post_id' => $comment->post_id,
            'parent_id' => $comment->parent_id,
            'body' => $comment->body,
            'reactions_count' => (int) ($comment->reactions_count ?? 0),
            'my_reaction' => $myReactions[$comment->id] ?? null,
            'created_at' => $comment->created_at?->toISOString(),
            'edited_at' => $comment->edited_at?->toISOString(),
            'author' => $comment->author
                ? [
                    'id' => $comment->author->id,
                    'name' => $comment->author->name,
                    'profile_photo_url' => $comment->author->profilePhotoUrl(),
                ]
                : null,
        ];
    }

    private function upsertReaction(int $userId, string $type, int $id, string $reaction): ?string
    {
        return DB::transaction(function () use ($userId, $type, $id, $reaction): ?string {
            $existing = SentimentReaction::query()
                ->where('user_id', $userId)
                ->where('reactionable_type', $type)
                ->where('reactionable_id', $id)
                ->first();

            if ($existing === null) {
                SentimentReaction::create([
                    'user_id' => $userId,
                    'reactionable_type' => $type,
                    'reactionable_id' => $id,
                    'reaction' => $reaction,
                ]);

                return $reaction;
            }

            if ($existing->reaction === $reaction) {
                $existing->delete();
                return null;
            }

            $existing->reaction = $reaction;
            $existing->save();

            return $reaction;
        });
    }

    private function unixTimestampExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => "CAST(strftime('%s', $column) AS INTEGER)",
            'pgsql' => "EXTRACT(EPOCH FROM $column)",
            default => "UNIX_TIMESTAMP($column)",
        };
    }

    private function ageHoursExpression(string $column): string
    {
        $driver = DB::connection()->getDriverName();

        return match ($driver) {
            'sqlite' => "MAX(((strftime('%s', 'now') - strftime('%s', $column)) / 3600.0), 1)",
            'pgsql' => "GREATEST(EXTRACT(EPOCH FROM (NOW() - $column)) / 3600.0, 1)",
            default => "GREATEST(TIMESTAMPDIFF(HOUR, $column, NOW()), 1)",
        };
    }

    private function mediaUrl(SentimentPost $post): ?string
    {
        if ($post->media_kind !== SentimentPost::MEDIA_IMAGE && $post->media_kind !== SentimentPost::MEDIA_VIDEO) {
            return null;
        }

        if (!$post->media_disk || !$post->media_path) {
            return null;
        }

        return route('api.mobile.sentiments.posts.media', ['post' => $post->id]);
    }
}
