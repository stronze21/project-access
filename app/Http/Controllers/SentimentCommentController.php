<?php

namespace App\Http\Controllers;

use App\Models\SentimentComment;
use App\Models\SentimentPost;
use App\Models\SentimentReport;
use App\Services\ComplaintAuditLogger;
use App\Services\SentimentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SentimentCommentController extends Controller
{
    public function __construct(
        private SentimentService $sentimentService,
        private ComplaintAuditLogger $auditLogger
    ) {
    }

    public function store(Request $request, SentimentPost $post): RedirectResponse
    {
        $this->ensurePostingAllowed($request);
        abort_unless($this->sentimentService->canViewPost($request->user(), $post), 404);
        abort_if($post->is_comments_locked, 422, 'Comments are locked for this post.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', Rule::exists('sentiment_comments', 'id')],
        ]);

        $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;
        if ($parentId !== null) {
            $parentComment = SentimentComment::query()->find($parentId);
            if ($parentComment === null || (int) $parentComment->post_id !== (int) $post->id) {
                return back()->withErrors(['body' => 'Invalid parent comment selected.']);
            }
        }

        $comment = SentimentComment::create([
            'post_id' => $post->id,
            'parent_id' => $parentId,
            'user_id' => $request->user()->id,
            'body' => trim((string) $validated['body']),
        ]);

        if ($post->author) {
            $this->sentimentService->notifyCommentOnPost($post->author, $request->user(), $post);
        }

        $this->sentimentService->notifyMentions(
            $comment->body,
            $request->user(),
            'comment',
            $comment->id,
            route('sentiments.index', ['post' => $post->id]).'#comment-'.$comment->id
        );

        $this->auditLogger->log('sentiment_comment_created', null, $comment, $request->user(), $request, [
            'post_id' => $post->id,
            'comment_id' => $comment->id,
            'parent_id' => $parentId,
        ]);

        return back()->with('status', 'Comment posted.');
    }

    public function update(Request $request, SentimentComment $comment): RedirectResponse
    {
        abort_unless((int) $comment->user_id === (int) $request->user()->id, 403);
        abort_if($comment->trashed() || $comment->is_permanently_deleted, 422, 'Deleted comments cannot be edited.');

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $comment->body = trim((string) $validated['body']);
        $comment->edited_at = now();
        $comment->save();

        $this->sentimentService->notifyMentions(
            $comment->body,
            $request->user(),
            'comment',
            $comment->id,
            route('sentiments.index', ['post' => $comment->post_id]).'#comment-'.$comment->id
        );

        $this->auditLogger->log('sentiment_comment_edited', null, $comment, $request->user(), $request, [
            'post_id' => $comment->post_id,
            'comment_id' => $comment->id,
        ]);

        return back()->with('status', 'Comment updated.');
    }

    public function destroy(Request $request, SentimentComment $comment): RedirectResponse
    {
        abort_unless((int) $comment->user_id === (int) $request->user()->id, 403);
        abort_if($comment->trashed(), 422, 'Comment already deleted.');

        $comment->delete();

        $this->auditLogger->log('sentiment_comment_deleted', null, $comment, $request->user(), $request, [
            'post_id' => $comment->post_id,
            'comment_id' => $comment->id,
            'soft_deleted' => true,
        ]);

        return back()->with('status', 'Comment deleted.');
    }

    public function moderate(Request $request, SentimentComment $comment): RedirectResponse
    {
        abort_unless($this->sentimentService->isModerator($request->user()), 403);
        abort_if($comment->trashed(), 422, 'Deleted comments cannot be moderated.');

        $validated = $request->validate([
            'action' => ['required', Rule::in(['restore', 'permanent_delete', 'ban_user'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $action = (string) $validated['action'];
        $note = trim((string) ($validated['note'] ?? ''));

        if ($action === 'restore') {
            $comment->hidden_at = null;
            $comment->hidden_reason = null;
            $comment->is_permanently_deleted = false;
            $comment->save();

            $this->sentimentService->resolveReportsAndNotify($comment, SentimentReport::STATUS_RESOLVED_RESTORE, $request->user());
        }

        if ($action === 'permanent_delete') {
            $comment->hidden_at = now();
            $comment->hidden_reason = 'moderator_permanent_delete';
            $comment->is_permanently_deleted = true;
            $comment->save();

            $this->sentimentService->resolveReportsAndNotify($comment, SentimentReport::STATUS_RESOLVED_DELETE, $request->user());
        }

        if ($action === 'ban_user') {
            $author = $comment->author;
            if ($author) {
                $author->sentiment_posting_banned_at = now();
                $author->sentiment_posting_ban_reason = $note !== '' ? $note : 'Banned by moderator from People Sentiments posting.';
                $author->save();
            }
        }

        $this->auditLogger->log('sentiment_comment_moderated', null, $comment, $request->user(), $request, [
            'post_id' => $comment->post_id,
            'comment_id' => $comment->id,
            'action' => $action,
            'note' => $note,
        ]);

        return back()->with('status', 'Moderator action applied.');
    }

    private function ensurePostingAllowed(Request $request): void
    {
        if ($this->sentimentService->isPostingBanned($request->user())) {
            abort(403, 'Your posting access for People Sentiments is currently disabled.');
        }
    }
}

