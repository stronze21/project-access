<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintComment;
use App\Models\ComplaintCommentReaction;
use App\Notifications\ComplaintStatusNotification;
use App\Services\ComplaintAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ComplaintCommentController extends Controller
{
    public function __construct(private ComplaintAuditLogger $auditLogger)
    {
    }

    public function store(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('comment', $complaint);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
        ]);

        $comment = $complaint->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
            'is_staff_response' => $request->user()->isDepartmentHead() || $request->user()->isMayor(),
        ]);

        $this->auditLogger->log('comment_added', $complaint, $comment, $request->user(), $request);

        $submitter = $complaint->submitter;
        if ($submitter && $submitter->id !== $request->user()->id && $submitter->email_verified_at !== null) {
            $submitter->notify(new ComplaintStatusNotification(
                $complaint,
                'A new public comment was added to your complaint.'
            ));
        }

        return back()->with('status', 'Comment posted.');
    }

    public function hide(Request $request, Complaint $complaint, ComplaintComment $comment): RedirectResponse
    {
        $this->authorize('moderate', $complaint);
        abort_unless((int) $comment->complaint_id === (int) $complaint->id, 404);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $comment->is_hidden = true;
        $comment->hidden_by_user_id = $request->user()->id;
        $comment->hidden_reason = $validated['reason'];
        $comment->hidden_at = Carbon::now();
        $comment->save();

        $this->auditLogger->log('comment_hidden', $complaint, $comment, $request->user(), $request, [
            'reason' => $validated['reason'],
        ]);

        return back()->with('status', 'Comment hidden.');
    }

    public function react(Request $request, Complaint $complaint, ComplaintComment $comment): RedirectResponse
    {
        $this->authorize('comment', $complaint);
        abort_unless((int) $comment->complaint_id === (int) $complaint->id, 404);
        abort_if($comment->is_hidden, 404);

        $validated = $request->validate([
            'reaction' => ['required', Rule::in([
                ComplaintCommentReaction::REACTION_LIKE,
                ComplaintCommentReaction::REACTION_DISLIKE,
            ])],
            'redirect_to' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user();
        $current = $comment->reactions()
            ->where('user_id', $user->id)
            ->first();

        if ($current && $current->reaction === $validated['reaction']) {
            $current->delete();

            $this->auditLogger->log('comment_reaction_removed', $complaint, $comment, $user, $request, [
                'reaction' => $validated['reaction'],
            ]);

            return $this->reactionRedirect($request, 'Reaction removed.');
        }

        if ($current) {
            $oldReaction = $current->reaction;
            $current->reaction = $validated['reaction'];
            $current->save();

            $this->auditLogger->log('comment_reaction_updated', $complaint, $comment, $user, $request, [
                'from' => $oldReaction,
                'to' => $validated['reaction'],
            ]);

            return $this->reactionRedirect($request, 'Reaction updated.');
        }

        $comment->reactions()->create([
            'user_id' => $user->id,
            'reaction' => $validated['reaction'],
        ]);

        $this->auditLogger->log('comment_reaction_added', $complaint, $comment, $user, $request, [
            'reaction' => $validated['reaction'],
        ]);

        return $this->reactionRedirect($request, 'Reaction added.');
    }

    private function reactionRedirect(Request $request, string $status): RedirectResponse
    {
        $redirectTo = trim((string) $request->input('redirect_to', ''));
        $isSafeLocalPath = $redirectTo !== ''
            && str_starts_with($redirectTo, '/')
            && !str_starts_with($redirectTo, '//')
            && !str_contains($redirectTo, "\r")
            && !str_contains($redirectTo, "\n");

        if ($isSafeLocalPath) {
            return redirect($redirectTo)->with('status', $status);
        }

        return back()->with('status', $status);
    }
}
