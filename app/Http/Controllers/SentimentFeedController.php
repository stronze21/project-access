<?php

namespace App\Http\Controllers;

use App\Models\SentimentComment;
use App\Models\SentimentFollow;
use App\Models\SentimentPost;
use App\Models\SentimentReaction;
use App\Models\SentimentReport;
use App\Models\User;
use App\Services\ComplaintAuditLogger;
use App\Services\SentimentService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SentimentFeedController extends Controller
{
    public function __construct(
        private SentimentService $sentimentService,
        private ComplaintAuditLogger $auditLogger
    ) {
    }

    public function index(Request $request): View
    {
        return view('sentiments.index', $this->feedViewData($request, 'feed'));
    }

    public function trending(Request $request): View
    {
        return view('sentiments.index', $this->feedViewData($request, 'trending'));
    }

    public function fragment(Request $request): View
    {
        $mode = $request->string('mode')->toString() === 'trending' ? 'trending' : 'feed';

        return view('sentiments.partials.feed-list', $this->feedViewData($request, $mode, false));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensurePostingAllowed($request->user());

        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:5000'],
            'media' => [
                'nullable',
                'file',
                'max:20480',
                'mimetypes:image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime',
            ],
            'external_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $body = trim((string) ($validated['body'] ?? ''));
        $mediaFile = $request->file('media');
        $externalUrl = trim((string) ($validated['external_url'] ?? ''));

        if ($body === '' && $mediaFile === null && $externalUrl === '') {
            return back()->withErrors([
                'body' => 'Post text, media, or external link is required.',
            ])->withInput();
        }

        if ($mediaFile !== null && $externalUrl !== '') {
            return back()->withErrors([
                'media' => 'Choose either one upload OR one external link.',
            ])->withInput();
        }

        $post = DB::transaction(function () use ($request, $body, $mediaFile, $externalUrl) {
            $post = SentimentPost::create([
                'user_id' => $request->user()->id,
                'body' => $body !== '' ? $body : null,
                'media_kind' => SentimentPost::MEDIA_NONE,
            ]);

            if ($mediaFile !== null) {
                $mime = (string) $mediaFile->getMimeType();
                $mediaKind = str_starts_with($mime, 'image/')
                    ? SentimentPost::MEDIA_IMAGE
                    : SentimentPost::MEDIA_VIDEO;

                $path = $mediaFile->store('sentiments/posts/'.$post->id, 'local');

                $post->update([
                    'media_kind' => $mediaKind,
                    'media_disk' => 'local',
                    'media_path' => $path,
                    'media_mime_type' => $mime,
                    'media_original_name' => $mediaFile->getClientOriginalName(),
                ]);
            } elseif ($externalUrl !== '') {
                $post->update([
                    'media_kind' => SentimentPost::MEDIA_EXTERNAL,
                    'external_url' => $externalUrl,
                ]);
            }

            return $post;
        });

        $this->sentimentService->notifyMentions(
            (string) ($post->body ?? ''),
            $request->user(),
            'post',
            $post->id,
            route('sentiments.index', ['post' => $post->id]).'#post-'.$post->id
        );

        $this->auditLogger->log('sentiment_post_created', null, $post, $request->user(), $request, [
            'post_id' => $post->id,
            'has_media' => $post->media_kind !== SentimentPost::MEDIA_NONE,
            'media_kind' => $post->media_kind,
        ]);

        return back()->with('status', 'Post published.');
    }

    public function update(Request $request, SentimentPost $post): RedirectResponse
    {
        abort_unless((int) $post->user_id === (int) $request->user()->id, 403);
        abort_if($post->trashed() || $post->is_permanently_deleted, 422, 'Deleted posts cannot be edited.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $post->body = trim((string) $validated['body']);
        $post->edited_at = now();
        $post->save();

        $this->sentimentService->notifyMentions(
            (string) ($post->body ?? ''),
            $request->user(),
            'post',
            $post->id,
            route('sentiments.index', ['post' => $post->id]).'#post-'.$post->id
        );

        $this->auditLogger->log('sentiment_post_edited', null, $post, $request->user(), $request, [
            'post_id' => $post->id,
        ]);

        return back()->with('status', 'Post updated.');
    }

    public function destroy(Request $request, SentimentPost $post): RedirectResponse
    {
        abort_unless((int) $post->user_id === (int) $request->user()->id, 403);
        abort_if($post->trashed(), 422, 'Post already deleted.');

        $post->delete();

        $this->auditLogger->log('sentiment_post_deleted', null, $post, $request->user(), $request, [
            'post_id' => $post->id,
            'soft_deleted' => true,
        ]);

        return back()->with('status', 'Post deleted.');
    }

    public function moderate(Request $request, SentimentPost $post): RedirectResponse
    {
        abort_unless($this->sentimentService->isModerator($request->user()), 403);
        abort_if($post->trashed(), 422, 'Deleted posts cannot be moderated.');

        $validated = $request->validate([
            'action' => ['required', Rule::in([
                'restore',
                'permanent_delete',
                'ban_user',
                'lock_comments',
                'unlock_comments',
                'pin',
                'unpin',
            ])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $action = (string) $validated['action'];
        $note = trim((string) ($validated['note'] ?? ''));
        $moderator = $request->user();

        if ($action === 'restore') {
            $post->hidden_at = null;
            $post->hidden_reason = null;
            $post->is_permanently_deleted = false;
            $post->save();

            $this->sentimentService->resolveReportsAndNotify($post, SentimentReport::STATUS_RESOLVED_RESTORE, $moderator);
        }

        if ($action === 'permanent_delete') {
            $post->hidden_at = now();
            $post->hidden_reason = 'moderator_permanent_delete';
            $post->is_permanently_deleted = true;
            $post->save();

            $this->sentimentService->resolveReportsAndNotify($post, SentimentReport::STATUS_RESOLVED_DELETE, $moderator);
        }

        if ($action === 'ban_user') {
            $author = $post->author;
            if ($author) {
                $author->sentiment_posting_banned_at = now();
                $author->sentiment_posting_ban_reason = $note !== '' ? $note : 'Banned by moderator from People Sentiments posting.';
                $author->save();
            }
        }

        if ($action === 'lock_comments') {
            $post->is_comments_locked = true;
            $post->save();
        }

        if ($action === 'unlock_comments') {
            $post->is_comments_locked = false;
            $post->save();
        }

        if ($action === 'pin') {
            $post->is_pinned = true;
            $post->save();
        }

        if ($action === 'unpin') {
            $post->is_pinned = false;
            $post->save();
        }

        $this->auditLogger->log('sentiment_post_moderated', null, $post, $moderator, $request, [
            'post_id' => $post->id,
            'action' => $action,
            'note' => $note,
        ]);

        return back()->with('status', 'Moderator action applied.');
    }

    public function media(Request $request, SentimentPost $post): StreamedResponse
    {
        abort_unless($post->media_kind === SentimentPost::MEDIA_IMAGE || $post->media_kind === SentimentPost::MEDIA_VIDEO, 404);
        abort_unless($post->media_disk && $post->media_path, 404);
        abort_unless($this->sentimentService->canViewPost($request->user(), $post), 404);
        abort_unless(Storage::disk($post->media_disk)->exists($post->media_path), 404);

        $stream = Storage::disk($post->media_disk)->readStream($post->media_path);
        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $post->media_mime_type ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="'.$post->media_original_name.'"',
            'Cache-Control' => 'private, max-age=120',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function feedViewData(Request $request, string $mode, bool $includeSidebarData = true): array
    {
        $user = $request->user();
        $commentSort = $request->string('comment_sort')->toString() === 'oldest' ? 'oldest' : 'newest';
        $commentSortDirection = $commentSort === 'oldest' ? 'asc' : 'desc';

        $query = SentimentPost::query()
            ->withTrashed()
            ->visibleTo($user)
            ->with([
                'author:id,name,profile_photo_path',
                'comments' => function ($commentQuery) use ($user, $commentSortDirection): void {
                    $commentQuery
                        ->withTrashed()
                        ->visibleTo($user)
                        ->with('author:id,name,profile_photo_path')
                        ->withCount('reactions')
                        ->orderBy('created_at', $commentSortDirection);
                },
            ])
            ->withCount([
                'reactions',
                'reports',
                'comments as comments_count' => function ($commentCountQuery) use ($user): void {
                    $commentCountQuery
                        ->withTrashed()
                        ->visibleTo($user);
                },
            ]);

        $this->applyFilters($query, $request);
        $this->applySort($query, $mode);

        $posts = $query
            ->paginate((int) config('sentiments.page_size', 10))
            ->withQueryString();

        $postIds = $posts->getCollection()->pluck('id')->all();
        $commentIds = $posts->getCollection()->flatMap(fn ($post) => $post->comments->pluck('id'))->all();
        $authorIds = $posts->getCollection()->pluck('user_id')->unique()->values()->all();

        $postMyReactions = SentimentReaction::query()
            ->where('user_id', $user->id)
            ->where('reactionable_type', SentimentPost::class)
            ->whereIn('reactionable_id', $postIds)
            ->pluck('reaction', 'reactionable_id');

        $commentMyReactions = !empty($commentIds)
            ? SentimentReaction::query()
                ->where('user_id', $user->id)
                ->where('reactionable_type', SentimentComment::class)
                ->whereIn('reactionable_id', $commentIds)
                ->pluck('reaction', 'reactionable_id')
            : collect();

        $followingAuthorIds = empty($authorIds)
            ? collect()
            : SentimentFollow::query()
                ->where('follower_user_id', $user->id)
                ->whereIn('followed_user_id', $authorIds)
                ->pluck('followed_user_id');

        $authors = $includeSidebarData
            ? User::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        $unreadNotificationCount = $includeSidebarData ? $user->unreadNotifications()->count() : 0;
        $notifications = $includeSidebarData ? $user->notifications()->latest()->limit(10)->get() : collect();

        return [
            'posts' => $posts,
            'activeMode' => $mode,
            'commentSort' => $commentSort,
            'reactionTypes' => config('sentiments.reaction_types', []),
            'reactionTypeKeys' => $this->sentimentService->reactionKeys(),
            'postMyReactions' => $postMyReactions,
            'commentMyReactions' => $commentMyReactions,
            'followingAuthorIds' => $followingAuthorIds,
            'authors' => $authors,
            'canModerate' => $this->sentimentService->isModerator($user),
            'isPostingBanned' => $this->sentimentService->isPostingBanned($user),
            'unreadNotificationCount' => $unreadNotificationCount,
            'notifications' => $notifications,
            'pollIntervalMs' => (int) config('sentiments.poll_interval_ms', 8000),
        ];
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        $search = trim($request->string('q')->toString());
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('body', 'like', '%'.$search.'%')
                    ->orWhereHas('comments', function ($commentBuilder) use ($search): void {
                        $commentBuilder->where('body', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($request->filled('author_id')) {
            $query->where('user_id', $request->integer('author_id'));
        }

        if ($request->filled('reaction_type')) {
            $reactionType = strtolower($request->string('reaction_type')->toString());
            $query->whereHas('reactions', function ($reactionBuilder) use ($reactionType): void {
                $reactionBuilder->where('reaction', $reactionType);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }
    }

    private function applySort(Builder $query, string $mode): void
    {
        $query->orderByDesc('is_pinned');

        if ($mode === 'trending') {
            $query->orderByRaw(
                '((COALESCE(reactions_count, 0) * 4 + COALESCE(comments_count, 0) * 6) / POW(GREATEST(TIMESTAMPDIFF(HOUR, sentiment_posts.created_at, NOW()), 1), 0.7)) DESC'
            );
            $query->orderByDesc('created_at');
            return;
        }

        $query->orderByRaw(
            '(UNIX_TIMESTAMP(sentiment_posts.created_at) + (COALESCE(reactions_count, 0) * 120) + (COALESCE(comments_count, 0) * 180)) DESC'
        );
    }

    private function ensurePostingAllowed(User $user): void
    {
        if ($this->sentimentService->isPostingBanned($user)) {
            abort(403, 'Your posting access for People Sentiments is currently disabled.');
        }
    }
}
