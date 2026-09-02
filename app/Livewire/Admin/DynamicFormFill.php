<?php

namespace App\Livewire\Admin;

use App\Models\DynamicForm;
use App\Models\DynamicFormSubmission;
use App\Models\Resident;
use App\Services\DynamicForm\FormSubmissionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

class DynamicFormFill extends Component
{
    use AuthorizesRequests, Toast, WithFileUploads;

    public DynamicForm $form;

    #[Url]
    public ?int $submission = null;

    public array $answers = [];

    public array $uploads = [];

    public ?int $residentId = null;

    public string $residentSearch = '';

    public array $residentResults = [];

    public string $selectedResidentLabel = '';

    public function mount(DynamicForm $form): void
    {
        $this->authorize('fill-forms');
        if (! $form->is_active) {
            abort(403, 'This form is archived.');
        }

        if (! $form->published_at && ! auth()->user()->can('manage-forms')) {
            abort(403, 'This form is not published yet.');
        }

        $this->form = $form->load(['fields', 'statuses']);

        if ($this->submission) {
            $existing = DynamicFormSubmission::findOrFail($this->submission);
            abort_unless($existing->dynamic_form_id === $form->id, 404);
            abort_unless($existing->visibleTo(auth()->user()), 403);
            abort_unless($existing->isInInitialStatus(), 403, 'Only draft submissions can be edited.');
            $existing->loadMissing('resident');
            $this->answers = $existing->answers ?? [];
            $this->residentId = $existing->resident_id;
            if ($existing->resident) {
                $this->selectedResidentLabel = $existing->resident->full_name.' ('.$existing->resident->resident_id.')';
            }
        }

        foreach ($this->form->activeFields as $field) {
            if (! array_key_exists($field->key, $this->answers)) {
                $this->answers[$field->key] = $field->type === 'checkboxes' ? [] : null;
            }
        }
    }

    public function updatedResidentSearch(): void
    {
        $term = trim($this->residentSearch);
        if (strlen($term) < 2) {
            $this->residentResults = [];

            return;
        }

        $this->residentResults = Resident::query()
            ->where('is_active', true)
            ->where(function ($query) use ($term) {
                $query->where('first_name', 'like', '%'.$term.'%')
                    ->orWhere('last_name', 'like', '%'.$term.'%')
                    ->orWhere('resident_id', 'like', '%'.$term.'%');
            })
            ->orderBy('last_name')
            ->limit(8)
            ->get(['id', 'resident_id', 'first_name', 'last_name', 'middle_name'])
            ->map(fn (Resident $resident) => [
                'id' => $resident->id,
                'label' => $resident->full_name.' ('.$resident->resident_id.')',
            ])
            ->all();
    }

    public function selectResident(int $id, string $label): void
    {
        $this->residentId = $id;
        $this->selectedResidentLabel = $label;
        $this->residentSearch = '';
        $this->residentResults = [];
    }

    public function clearResident(): void
    {
        $this->residentId = null;
        $this->selectedResidentLabel = '';
    }

    public function save(FormSubmissionService $submissions, bool $submit = false): mixed
    {
        $this->authorize('fill-forms');

        try {
            if ($this->submission) {
                $submission = $submissions->update(
                    DynamicFormSubmission::findOrFail($this->submission),
                    $this->answers,
                    $this->uploads,
                    auth()->user(),
                    $this->residentId,
                    $submit,
                );
            } else {
                $submission = $submissions->create(
                    $this->form,
                    $this->answers,
                    $this->uploads,
                    auth()->user(),
                    $this->residentId,
                    $submit,
                );
            }
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->getMessageBag());

            return null;
        }

        $this->success($submit ? 'Submission saved and moved forward.' : 'Draft saved.');

        return redirect()->route('forms.submissions.show', $submission);
    }

    public function submit(FormSubmissionService $submissions): mixed
    {
        return $this->save($submissions, true);
    }

    public function render()
    {
        return view('livewire.admin.dynamic-form-fill', [
            'fields' => $this->form->activeFields()->get(),
            'existingAnswers' => $this->submission
                ? (DynamicFormSubmission::find($this->submission)?->answers ?? [])
                : [],
        ])->layout('layouts.app');
    }
}
