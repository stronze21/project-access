<?php

namespace App\Livewire\Admin;

use App\Models\DynamicForm;
use App\Models\DynamicFormSubmission;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class DynamicFormInbox extends Component
{
    use AuthorizesRequests, Toast, WithPagination;

    #[Url]
    public string $formId = '';

    #[Url]
    public string $search = '';

    #[Url]
    public string $statusId = '';

    #[Url]
    public string $tagId = '';

    #[Url]
    public string $view = 'table';

    #[Url]
    public string $filterFieldKey = '';

    #[Url]
    public string $filterFieldValue = '';

    public function mount(?int $form = null): void
    {
        $this->authorizeInbox();
        if ($form && $this->formId === '') {
            $this->formId = (string) $form;
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFormId(): void
    {
        $this->statusId = '';
        $this->tagId = '';
        $this->filterFieldKey = '';
        $this->filterFieldValue = '';
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusId = '';
        $this->tagId = '';
        $this->filterFieldKey = '';
        $this->filterFieldValue = '';
        $this->resetPage();
    }

    public function render()
    {
        $forms = DynamicForm::query()
            ->when(! auth()->user()->can('manage-forms'), fn ($query) => $query->where('is_active', true)->whereNotNull('published_at'))
            ->orderBy('title')
            ->get(['id', 'title']);

        $selectedForm = $this->formId !== '' ? DynamicForm::with(['statuses', 'tags', 'fields'])->find($this->formId) : null;

        $query = DynamicFormSubmission::query()
            ->with(['form', 'status', 'tags', 'creator', 'resident'])
            ->latest();

        if (! auth()->user()->can('view-forms') && ! auth()->user()->can('process-forms')) {
            $query->where('created_by', auth()->id());
        }

        if ($this->formId !== '') {
            $query->where('dynamic_form_id', $this->formId);
        }

        if ($this->statusId !== '') {
            $query->where('status_id', $this->statusId);
        }

        if ($this->tagId !== '') {
            $query->whereHas('tags', fn ($builder) => $builder->where('dynamic_form_tags.id', $this->tagId));
        }

        if ($this->search !== '') {
            $query->where(function ($builder) {
                $builder->where('reference_number', 'like', '%'.$this->search.'%')
                    ->orWhereHas('creator', fn ($creator) => $creator->where('name', 'like', '%'.$this->search.'%'))
                    ->orWhereHas('resident', function ($resident) {
                        $resident->where('first_name', 'like', '%'.$this->search.'%')
                            ->orWhere('last_name', 'like', '%'.$this->search.'%')
                            ->orWhere('resident_id', 'like', '%'.$this->search.'%');
                    });
            });
        }

        if ($this->filterFieldKey !== '' && $this->filterFieldValue !== '') {
            $query->whereHas('values', function ($values) {
                $values->where('field_key', $this->filterFieldKey)
                    ->where(function ($inner) {
                        $inner->where('value_string', 'like', '%'.$this->filterFieldValue.'%')
                            ->orWhere('value_number', $this->filterFieldValue)
                            ->orWhere('value_date', $this->filterFieldValue);
                    });
            });
        }

        $board = [];
        if ($this->view === 'board' && $selectedForm) {
            foreach ($selectedForm->statuses as $status) {
                $board[$status->id] = (clone $query)
                    ->where('status_id', $status->id)
                    ->limit(40)
                    ->get();
            }
        }

        return view('livewire.admin.dynamic-form-inbox', [
            'forms' => $forms,
            'selectedForm' => $selectedForm,
            'submissions' => $this->view === 'table' ? $query->paginate(20) : collect(),
            'board' => $board,
            'filterableFields' => $selectedForm
                ? $selectedForm->fields->where('is_filterable', true)->where('is_active', true)->values()
                : collect(),
        ])->layout('layouts.app');
    }

    private function authorizeInbox(): void
    {
        $user = auth()->user();
        if ($user->can('view-forms') || $user->can('fill-forms') || $user->can('process-forms')) {
            return;
        }

        abort(403);
    }
}
