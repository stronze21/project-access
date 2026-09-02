<?php

namespace App\Livewire\Admin;

use App\Models\DynamicFormSubmission;
use App\Services\DynamicForm\FormWorkflowService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Mary\Traits\Toast;

class DynamicFormSubmissionShow extends Component
{
    use AuthorizesRequests, Toast;

    public DynamicFormSubmission $submission;

    public string $note = '';

    public ?int $nextStatusId = null;

    public array $selectedTagIds = [];

    public function mount(DynamicFormSubmission $submission): void
    {
        abort_unless($submission->visibleTo(auth()->user()), 403);
        $this->submission = $submission->load([
            'form.fields',
            'form.statuses',
            'form.tags',
            'form.transitions',
            'status',
            'tags',
            'resident',
            'creator',
            'events.fromStatus',
            'events.toStatus',
            'events.actor',
        ]);
        $this->selectedTagIds = $this->submission->tags->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function transition(FormWorkflowService $workflow): void
    {
        $this->authorize('process-forms');
        $this->validate([
            'nextStatusId' => 'required|integer',
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            $this->submission = $workflow->transition(
                $this->submission,
                (int) $this->nextStatusId,
                auth()->user(),
                $this->note !== '' ? $this->note : null,
            );
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $this->note = '';
        $this->nextStatusId = null;
        $this->success('Status updated.');
        $this->submission->load([
            'form.statuses',
            'form.transitions',
            'status',
            'events.fromStatus',
            'events.toStatus',
            'events.actor',
        ]);
    }

    public function saveTags(FormWorkflowService $workflow): void
    {
        $this->authorize('process-forms');
        $this->submission = $workflow->syncTags(
            $this->submission,
            array_map('intval', $this->selectedTagIds)
        );
        $this->success('Tags updated.');
    }

    public function render()
    {
        $targets = app(FormWorkflowService::class)->allowedTargets($this->submission);

        return view('livewire.admin.dynamic-form-submission-show', [
            'targets' => $targets,
            'canProcess' => auth()->user()->can('process-forms'),
            'canEditDraft' => auth()->user()->can('fill-forms')
                && $this->submission->isInInitialStatus()
                && ((int) $this->submission->created_by === (int) auth()->id() || auth()->user()->can('process-forms')),
        ])->layout('layouts.app');
    }
}
