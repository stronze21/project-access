<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintComment;
use App\Models\ComplaintCommentReaction;
use App\Models\ComplaintSupport;
use App\Services\ComplaintAuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobileComplaintEngagementController extends Controller
{
    public function __construct(private ComplaintAuditLogger $auditLogger)
    {
    }

    public function support(Request $request, Complaint $complaint): JsonResponse
    {
        $this->authorize('support', $complaint);

        $support = ComplaintSupport::query()->firstOrCreate([
            'complaint_id' => $complaint->id,
            'user_id' => $request->user()->id,
        ]);

        if ($support->wasRecentlyCreated) {
            $complaint->increment('support_count');
            $this->auditLogger->log('complaint_supported', $complaint, $support, $request->user(), $request);
        }

        return response()->json([
            'message' => $support->wasRecentlyCreated ? 'Support added.' : 'Already supported.',
            'support_count' => (int) $complaint->fresh()->support_count,
        ]);
    }

    public function storeComment(Request $request, Complaint $complaint): JsonResponse
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

        return response()->json([
            'message' => 'Comment posted.',
            'data' => $this->serializeComment($comment->load('user:id,name,profile_photo_path')),
        ], 201);
    }

    public function reactComment(
        Request $request,
        Complaint $complaint,
        ComplaintComment $comment
    ): JsonResponse {
        $this->authorize('comment', $complaint);
        abort_unless((int) $comment->complaint_id === (int) $complaint->id, 404);
        abort_if($comment->is_hidden, 404);

        $validated = $request->validate([
            'reaction' => ['required', Rule::in([
                ComplaintCommentReaction::REACTION_LIKE,
                ComplaintCommentReaction::REACTION_DISLIKE,
            ])],
        ]);

        $user = $request->user();
        $current = $comment->reactions()
            ->where('user_id', $user->id)
            ->first();

        $message = 'Reaction added.';
        $userReaction = $validated['reaction'];

        if ($current && $current->reaction === $validated['reaction']) {
            $current->delete();
            $message = 'Reaction removed.';
            $userReaction = null;

            $this->auditLogger->log('comment_reaction_removed', $complaint, $comment, $user, $request, [
                'reaction' => $validated['reaction'],
            ]);
        } elseif ($current) {
            $oldReaction = $current->reaction;
            $current->reaction = $validated['reaction'];
            $current->save();
            $message = 'Reaction updated.';

            $this->auditLogger->log('comment_reaction_updated', $complaint, $comment, $user, $request, [
                'from' => $oldReaction,
                'to' => $validated['reaction'],
            ]);
        } else {
            $comment->reactions()->create([
                'user_id' => $user->id,
                'reaction' => $validated['reaction'],
            ]);

            $this->auditLogger->log('comment_reaction_added', $complaint, $comment, $user, $request, [
                'reaction' => $validated['reaction'],
            ]);
        }

        $likes = $comment->reactions()
            ->where('reaction', ComplaintCommentReaction::REACTION_LIKE)
            ->count();
        $dislikes = $comment->reactions()
            ->where('reaction', ComplaintCommentReaction::REACTION_DISLIKE)
            ->count();

        return response()->json([
            'message' => $message,
            'data' => [
                'comment_id' => $comment->id,
                'likes_count' => $likes,
                'dislikes_count' => $dislikes,
                'user_reaction' => $userReaction,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeComment(ComplaintComment $comment): array
    {
        return [
            'id' => $comment->id,
            'complaint_id' => $comment->complaint_id,
            'body' => $comment->body,
            'is_staff_response' => (bool) $comment->is_staff_response,
            'created_at' => $comment->created_at?->toISOString(),
            'user' => $comment->user
                ? [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'role' => $comment->user->role,
                    'profile_photo_url' => $comment->user->profilePhotoUrl(),
                ]
                : null,
        ];
    }
}
