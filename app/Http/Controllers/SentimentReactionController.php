<?php

namespace App\Http\Controllers;

use App\Models\SentimentComment;
use App\Models\SentimentPost;
use App\Models\SentimentReaction;
use App\Services\ComplaintAuditLogger;
use App\Services\SentimentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SentimentReactionController extends Controller
{
    public function __construct(
        private SentimentService $sentimentService,
        private ComplaintAuditLogger $auditLogger
    ) {
    }

    public function reactPost(Request $request, SentimentPost $post): RedirectResponse
    {
        abort_unless($this->sentimentService->canViewPost($request->user(), $post), 404);
        abort_if((int) $post->user_id === (int) $request->user()->id, 422, 'You cannot react to your own post.');

        $validated = $request->validate([
            'reaction' => ['required', Rule::in($this->sentimentService->reactionKeys())],
        ]);

        $reactionType = strtolower((string) $validated['reaction']);
        $outcome = $this->upsertReaction($request->user()->id, SentimentPost::class, $post->id, $reactionType);

        if ($outcome['notify'] && $post->author) {
            $this->sentimentService->notifyReaction($post->author, $request->user(), $reactionType, 'post', $post->id);
        }

        $this->auditLogger->log($outcome['event'], null, $post, $request->user(), $request, [
            'post_id' => $post->id,
            'reaction' => $reactionType,
            'from' => $outcome['from'],
            'to' => $outcome['to'],
        ]);

        return back();
    }

    public function reactComment(Request $request, SentimentComment $comment): RedirectResponse
    {
        abort_unless($this->sentimentService->canViewComment($request->user(), $comment), 404);
        abort_if((int) $comment->user_id === (int) $request->user()->id, 422, 'You cannot react to your own comment.');

        $validated = $request->validate([
            'reaction' => ['required', Rule::in($this->sentimentService->reactionKeys())],
        ]);

        $reactionType = strtolower((string) $validated['reaction']);
        $outcome = $this->upsertReaction($request->user()->id, SentimentComment::class, $comment->id, $reactionType);

        if ($outcome['notify'] && $comment->author) {
            $this->sentimentService->notifyReaction($comment->author, $request->user(), $reactionType, 'comment', $comment->id);
        }

        $this->auditLogger->log($outcome['event'], null, $comment, $request->user(), $request, [
            'post_id' => $comment->post_id,
            'comment_id' => $comment->id,
            'reaction' => $reactionType,
            'from' => $outcome['from'],
            'to' => $outcome['to'],
        ]);

        return back();
    }

    public function postReactors(Request $request, SentimentPost $post): JsonResponse
    {
        abort_unless($this->sentimentService->canViewPost($request->user(), $post), 404);

        return response()->json([
            'groups' => $this->sentimentService->groupedReactorNames($post),
            'labels' => config('sentiments.reaction_types', []),
        ]);
    }

    public function commentReactors(Request $request, SentimentComment $comment): JsonResponse
    {
        abort_unless($this->sentimentService->canViewComment($request->user(), $comment), 404);

        return response()->json([
            'groups' => $this->sentimentService->groupedReactorNames($comment),
            'labels' => config('sentiments.reaction_types', []),
        ]);
    }

    /**
     * @return array{event:string, notify:bool, from:?string, to:?string}
     */
    private function upsertReaction(int $userId, string $type, int $id, string $reaction): array
    {
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

            return [
                'event' => 'sentiment_reaction_added',
                'notify' => true,
                'from' => null,
                'to' => $reaction,
            ];
        }

        if ($existing->reaction === $reaction) {
            $from = $existing->reaction;
            $existing->delete();

            return [
                'event' => 'sentiment_reaction_removed',
                'notify' => false,
                'from' => $from,
                'to' => null,
            ];
        }

        $from = $existing->reaction;
        $existing->reaction = $reaction;
        $existing->save();

        return [
            'event' => 'sentiment_reaction_changed',
            'notify' => true,
            'from' => $from,
            'to' => $reaction,
        ];
    }
}

