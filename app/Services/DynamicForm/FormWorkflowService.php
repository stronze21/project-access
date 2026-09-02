<?php

namespace App\Services\DynamicForm;

use App\Models\DynamicFormStatus;
use App\Models\DynamicFormSubmission;
use App\Models\DynamicFormSubmissionEvent;
use App\Models\DynamicFormTag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FormWorkflowService
{
    public function transition(
        DynamicFormSubmission $submission,
        int $toStatusId,
        ?User $actor = null,
        ?string $note = null,
    ): DynamicFormSubmission {
        $submission->loadMissing(['form.transitions', 'form.statuses', 'status']);

        $toStatus = $submission->form->statuses->firstWhere('id', $toStatusId);
        if (! $toStatus) {
            throw ValidationException::withMessages([
                'status' => 'That status does not belong to this form.',
            ]);
        }

        $fromStatus = $submission->status;
        if ($fromStatus && (int) $fromStatus->id === (int) $toStatus->id) {
            return $submission;
        }

        if ($fromStatus?->is_terminal) {
            throw ValidationException::withMessages([
                'status' => "{$fromStatus->label} is a terminal status and cannot be changed.",
            ]);
        }

        if (! $this->isAllowed($submission, $toStatus)) {
            $fromLabel = $fromStatus?->label ?? 'the current status';
            throw ValidationException::withMessages([
                'status' => "Status transition from {$fromLabel} to {$toStatus->label} is not allowed.",
            ]);
        }

        return DB::transaction(function () use ($submission, $fromStatus, $toStatus, $actor, $note) {
            $submission->status_id = $toStatus->id;
            $submission->updated_by = $actor?->id;
            $submission->save();

            DynamicFormSubmissionEvent::create([
                'dynamic_form_submission_id' => $submission->id,
                'from_status_id' => $fromStatus?->id,
                'to_status_id' => $toStatus->id,
                'actor_id' => $actor?->id,
                'note' => $note,
            ]);

            return $submission->fresh(['status', 'events.fromStatus', 'events.toStatus', 'events.actor']);
        });
    }

    public function submitFromInitial(DynamicFormSubmission $submission, ?User $actor = null): DynamicFormSubmission
    {
        $submission->loadMissing(['status', 'form.statuses', 'form.transitions']);

        if (! $submission->isInInitialStatus()) {
            return $submission;
        }

        $targets = $this->allowedTargets($submission);
        if ($targets->isEmpty()) {
            return $submission;
        }

        $submitted = $targets->firstWhere('key', 'submitted') ?? $targets->first(fn (DynamicFormStatus $status) => ! $status->is_terminal);

        if (! $submitted) {
            return $submission;
        }

        return $this->transition($submission, $submitted->id, $actor, 'Submitted from the form.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, DynamicFormStatus>
     */
    public function allowedTargets(DynamicFormSubmission $submission)
    {
        $submission->loadMissing(['form.transitions', 'form.statuses', 'status']);

        $fromId = $submission->status_id;
        $transitions = $submission->form->transitions;

        if ($transitions->isEmpty()) {
            return $submission->form->statuses
                ->where('id', '!=', $fromId)
                ->values();
        }

        $allowedIds = $transitions
            ->where('from_status_id', $fromId)
            ->pluck('to_status_id')
            ->all();

        return $submission->form->statuses
            ->whereIn('id', $allowedIds)
            ->values();
    }

    /**
     * @param  array<int, int>  $tagIds
     */
    public function syncTags(DynamicFormSubmission $submission, array $tagIds): DynamicFormSubmission
    {
        $validIds = DynamicFormTag::query()
            ->where('dynamic_form_id', $submission->dynamic_form_id)
            ->whereIn('id', $tagIds)
            ->pluck('id')
            ->all();

        $submission->tags()->sync($validIds);

        return $submission->fresh(['tags']);
    }

    private function isAllowed(DynamicFormSubmission $submission, DynamicFormStatus $toStatus): bool
    {
        $transitions = $submission->form->transitions;

        if ($transitions->isEmpty()) {
            return (int) $submission->status_id !== (int) $toStatus->id;
        }

        return $transitions->contains(function ($transition) use ($submission, $toStatus) {
            return (int) $transition->from_status_id === (int) $submission->status_id
                && (int) $transition->to_status_id === (int) $toStatus->id;
        });
    }
}
