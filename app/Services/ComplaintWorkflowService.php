<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\ComplaintStatusHistory;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ComplaintWorkflowService
{
    /**
     * @return array{due_ack_at: CarbonInterface, due_first_action_at: CarbonInterface, due_resolution_at: CarbonInterface}
     */
    public function computeDueDates(CarbonInterface $base): array
    {
        return [
            'due_ack_at' => $base->copy()->addDays((int) config('complaints.sla.acknowledge_days', 1)),
            'due_first_action_at' => $base->copy()->addDays((int) config('complaints.sla.first_action_days', 3)),
            'due_resolution_at' => $base->copy()->addDays((int) config('complaints.sla.resolution_days', 7)),
        ];
    }

    public function initializeSla(Complaint $complaint, ?CarbonInterface $base = null): void
    {
        $base ??= Carbon::now();
        $dueDates = $this->computeDueDates($base);
        $complaint->due_ack_at = $dueDates['due_ack_at'];
        $complaint->due_first_action_at = $dueDates['due_first_action_at'];
        $complaint->due_resolution_at = $dueDates['due_resolution_at'];
    }

    public function transition(
        Complaint $complaint,
        string $toStatus,
        ?User $actor = null,
        ?string $note = null,
        bool $isOverride = false
    ): Complaint {
        $validStatuses = config('complaints.workflow.statuses', []);
        if (!in_array($toStatus, $validStatuses, true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid status transition target.',
            ]);
        }

        $fromStatus = $complaint->status;

        if ($fromStatus === $toStatus) {
            return $complaint;
        }

        if ($fromStatus === Complaint::STATUS_CLOSED) {
            throw ValidationException::withMessages([
                'status' => 'Closed complaints cannot be reopened. Please file a new complaint.',
            ]);
        }

        $allowedTransitions = [
            Complaint::STATUS_RECEIVED => [Complaint::STATUS_ASSIGNED],
            Complaint::STATUS_ASSIGNED => [Complaint::STATUS_IN_PROGRESS, Complaint::STATUS_RESOLVED],
            Complaint::STATUS_IN_PROGRESS => [Complaint::STATUS_RESOLVED],
            Complaint::STATUS_RESOLVED => [Complaint::STATUS_CLOSED],
            Complaint::STATUS_CLOSED => [],
        ];

        $isAllowed = in_array($toStatus, $allowedTransitions[$fromStatus] ?? [], true);
        $isAllowedByOverride = $isOverride
            && $fromStatus === Complaint::STATUS_RESOLVED
            && $toStatus === Complaint::STATUS_IN_PROGRESS;

        if (!$isAllowed && !$isAllowedByOverride) {
            throw ValidationException::withMessages([
                'status' => "Status transition from {$fromStatus} to {$toStatus} is not allowed.",
            ]);
        }

        $now = Carbon::now();

        if ($toStatus === Complaint::STATUS_ASSIGNED && $complaint->acknowledged_at === null) {
            $complaint->acknowledged_at = $now;
        }

        if ($toStatus === Complaint::STATUS_IN_PROGRESS && $complaint->first_action_at === null) {
            $complaint->first_action_at = $now;
        }

        if ($toStatus === Complaint::STATUS_RESOLVED) {
            $complaint->resolved_at = $now;
        }

        if ($toStatus === Complaint::STATUS_CLOSED) {
            $complaint->closed_at = $now;
        }

        if ($fromStatus === Complaint::STATUS_RESOLVED && $toStatus === Complaint::STATUS_IN_PROGRESS) {
            $complaint->resolved_at = null;
        }

        $complaint->status = $toStatus;
        $complaint->save();

        ComplaintStatusHistory::create([
            'complaint_id' => $complaint->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'changed_by_user_id' => $actor?->id,
            'is_override' => $isOverride,
            'note' => $note,
        ]);

        return $complaint;
    }
}
