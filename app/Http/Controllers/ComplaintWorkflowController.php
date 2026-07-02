<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintAssignment;
use App\Models\User;
use App\Notifications\ComplaintStatusNotification;
use App\Services\ComplaintAuditLogger;
use App\Services\ComplaintWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ComplaintWorkflowController extends Controller
{
    public function __construct(
        private ComplaintWorkflowService $workflowService,
        private ComplaintAuditLogger $auditLogger
    ) {
    }

    public function assignDepartment(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('assignDepartment', Complaint::class);
        abort_if($complaint->isClosed(), 422, 'Closed complaints cannot be reassigned.');

        $validated = $request->validate([
            'department_id' => ['required', Rule::exists('departments', 'id')],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $complaint->assigned_department_id = $validated['department_id'];
        $complaint->assigned_officer_id = null;
        $complaint->assigned_by_user_id = $request->user()->id;

        if ($complaint->status === Complaint::STATUS_RECEIVED) {
            $this->workflowService->transition($complaint, Complaint::STATUS_ASSIGNED, $request->user(), $validated['reason'] ?? null);
        } else {
            $dueDates = $this->workflowService->computeDueDates(Carbon::now());
            $complaint->due_first_action_at = $dueDates['due_first_action_at'];
            $complaint->due_resolution_at = $dueDates['due_resolution_at'];
            $complaint->save();
        }

        $assignment = ComplaintAssignment::create([
            'complaint_id' => $complaint->id,
            'department_id' => $validated['department_id'],
            'officer_id' => null,
            'assigned_by_user_id' => $request->user()->id,
            'reason' => $validated['reason'] ?? null,
            'is_override' => $request->user()->isMayor(),
        ]);

        $this->auditLogger->log('complaint_department_assigned', $complaint, $assignment, $request->user(), $request);

        return back()->with('status', 'Department assigned.');
    }

    public function assignOfficer(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('assignOfficer', $complaint);
        abort_if($complaint->isClosed(), 422, 'Closed complaints cannot be reassigned.');

        $validated = $request->validate([
            'officer_id' => ['required', Rule::exists('users', 'id')],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $officer = User::query()->findOrFail($validated['officer_id']);
        abort_unless($officer->isActionOfficer(), 422, 'Selected user is not an Action Officer.');

        if ($complaint->assigned_department_id !== null) {
            abort_unless((int) $officer->department_id === (int) $complaint->assigned_department_id, 422, 'Officer must belong to assigned department.');
        }

        $complaint->assigned_officer_id = $officer->id;
        $complaint->assigned_by_user_id = $request->user()->id;

        if ($complaint->status === Complaint::STATUS_RECEIVED) {
            $this->workflowService->transition($complaint, Complaint::STATUS_ASSIGNED, $request->user(), $validated['reason'] ?? null);
        } else {
            $complaint->save();
        }

        $assignment = ComplaintAssignment::create([
            'complaint_id' => $complaint->id,
            'department_id' => $complaint->assigned_department_id,
            'officer_id' => $officer->id,
            'assigned_by_user_id' => $request->user()->id,
            'reason' => $validated['reason'] ?? null,
            'is_override' => $request->user()->isMayor(),
        ]);

        $this->auditLogger->log('complaint_officer_assigned', $complaint, $assignment, $request->user(), $request);

        return back()->with('status', 'Action officer assigned.');
    }

    public function setPriority(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('setPriority', Complaint::class);
        abort_if($complaint->isClosed(), 422, 'Closed complaints cannot be reprioritized.');

        $validated = $request->validate([
            'priority' => ['required', Rule::in(config('complaints.workflow.priorities', []))],
        ]);

        $complaint->priority = $validated['priority'];
        $complaint->save();

        $this->auditLogger->log('complaint_priority_set', $complaint, $complaint, $request->user(), $request, [
            'priority' => $validated['priority'],
        ]);

        return back()->with('status', 'Priority updated.');
    }

    public function updateStatus(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('updateStatus', $complaint);

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                Complaint::STATUS_ASSIGNED,
                Complaint::STATUS_IN_PROGRESS,
                Complaint::STATUS_RESOLVED,
                Complaint::STATUS_CLOSED,
            ])],
            'note' => ['nullable', 'string', 'max:1000'],
            'resolution_summary' => ['nullable', 'string'],
        ]);

        $toStatus = $validated['status'];
        $fromStatus = $complaint->status;

        if ($complaint->isClosed()) {
            return back()->withErrors(['status' => 'Closed complaints cannot be reopened.']);
        }

        $isOverride = false;

        if ($complaint->status === Complaint::STATUS_RESOLVED && $toStatus === Complaint::STATUS_IN_PROGRESS) {
            abort_unless($request->user()->isMayor(), 403, 'Only the Mayor can reopen resolved complaints.');
            $isOverride = true;
        }

        if ($toStatus === Complaint::STATUS_CLOSED) {
            if (!$complaint->is_anonymous_submission) {
                return back()->withErrors(['status' => 'Only citizens can close non-anonymous complaints by confirmation.']);
            }

            if ($complaint->status !== Complaint::STATUS_RESOLVED) {
                return back()->withErrors(['status' => 'Anonymous complaints can only be closed after resolution.']);
            }
        }

        if ($toStatus === Complaint::STATUS_RESOLVED && empty($validated['resolution_summary'])) {
            return back()->withErrors(['resolution_summary' => 'Resolution summary is required when marking as resolved.']);
        }

        $this->workflowService->transition(
            $complaint,
            $toStatus,
            $request->user(),
            $validated['note'] ?? null,
            $isOverride
        );

        if ($toStatus === Complaint::STATUS_RESOLVED) {
            $complaint->resolution_summary = $validated['resolution_summary'];
            $complaint->save();
        }

        $this->auditLogger->log('complaint_status_updated', $complaint, $complaint, $request->user(), $request, [
            'from' => $fromStatus,
            'to' => $toStatus,
            'override' => $isOverride,
        ]);

        $this->notifySubmitter($complaint, $toStatus);

        return back()->with('status', 'Status updated.');
    }

    public function addInternalNote(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('addInternalNote', $complaint);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $note = $complaint->internalNotes()->create([
            'user_id' => $request->user()->id,
            'note' => $validated['note'],
        ]);

        $this->auditLogger->log('internal_note_added', $complaint, $note, $request->user(), $request);

        return back()->with('status', 'Internal note added.');
    }

    public function moderate(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('moderate', $complaint);

        $validated = $request->validate([
            'moderation_status' => ['required', Rule::in(config('complaints.workflow.moderation_statuses', []))],
            'moderation_reason' => ['nullable', 'string', 'max:1000', 'required_unless:moderation_status,normal'],
        ]);

        $complaint->moderation_status = $validated['moderation_status'];
        $complaint->moderation_reason = $validated['moderation_reason'] ?? null;
        $complaint->save();

        $this->auditLogger->log('complaint_moderated', $complaint, $complaint, $request->user(), $request, [
            'moderation_status' => $validated['moderation_status'],
            'moderation_reason' => $validated['moderation_reason'] ?? null,
        ]);

        return back()->with('status', 'Moderation updated.');
    }

    public function override(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('override', $complaint);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['reopen', 'escalate'])],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['action'] === 'reopen') {
            if ($complaint->status !== Complaint::STATUS_RESOLVED) {
                return back()->withErrors(['action' => 'Only resolved complaints can be reopened.']);
            }

            $this->workflowService->transition(
                $complaint,
                Complaint::STATUS_IN_PROGRESS,
                $request->user(),
                $validated['note'] ?? 'Mayor override: reopen',
                true
            );
        }

        if ($validated['action'] === 'escalate') {
            $complaint->is_escalated = true;
            $complaint->save();
        }

        $this->auditLogger->log('complaint_mayor_override', $complaint, $complaint, $request->user(), $request, [
            'action' => $validated['action'],
            'note' => $validated['note'] ?? null,
        ]);

        return back()->with('status', 'Mayor override applied.');
    }

    public function syncOfficials(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('assignDepartment', Complaint::class);

        $validated = $request->validate([
            'official_ids' => ['nullable', 'array'],
            'official_ids.*' => ['integer', Rule::exists('public_officials', 'id')],
        ]);

        $complaint->officials()->sync($validated['official_ids'] ?? []);

        $this->auditLogger->log('complaint_official_tags_updated', $complaint, $complaint, $request->user(), $request, [
            'official_ids' => $validated['official_ids'] ?? [],
        ]);

        return back()->with('status', 'Official tags updated.');
    }

    private function notifySubmitter(Complaint $complaint, string $toStatus): void
    {
        if ($complaint->is_anonymous_submission) {
            return;
        }

        $submitter = $complaint->submitter;
        if (!$submitter || $submitter->email_verified_at === null) {
            return;
        }

        $submitter->notify(new ComplaintStatusNotification(
            $complaint,
            'Your complaint was updated to: '.str_replace('_', ' ', $toStatus).'.'
        ));
    }
}
