<?php

namespace App\Livewire\Admin;

use App\Models\DynamicForm;
use App\Services\DynamicForm\FormSchemaService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class DynamicFormList extends Component
{
    use AuthorizesRequests, Toast, WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = 'all';

    public bool $showCreateModal = false;

    public bool $showDeleteModal = false;

    public string $newTitle = '';

    public ?int $deletingFormId = null;

    public function mount(): void
    {
        $this->authorizeAnyFormAccess();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->authorize('manage-forms');
        $this->newTitle = '';
        $this->showCreateModal = true;
    }

    public function createForm(FormSchemaService $schema): mixed
    {
        $this->authorize('manage-forms');
        $this->validate([
            'newTitle' => 'required|string|max:255',
        ]);

        $form = $schema->createBlank($this->newTitle, auth()->id());
        $this->showCreateModal = false;

        return redirect()->route('forms.edit', $form);
    }

    public function duplicate(int $formId, FormSchemaService $schema): mixed
    {
        $this->authorize('manage-forms');
        $form = DynamicForm::findOrFail($formId);
        $copy = $schema->duplicate($form, auth()->id());
        $this->success('Form duplicated.');

        return redirect()->route('forms.edit', $copy);
    }

    public function publish(int $formId, FormSchemaService $schema): void
    {
        $this->authorize('manage-forms');
        $schema->publish(DynamicForm::findOrFail($formId));
        $this->success('Form published.');
    }

    public function archive(int $formId, FormSchemaService $schema): void
    {
        $this->authorize('manage-forms');
        $schema->archive(DynamicForm::findOrFail($formId));
        $this->success('Form archived.');
    }

    public function confirmDelete(int $formId): void
    {
        $this->authorize('manage-forms');
        $this->deletingFormId = $formId;
        $this->showDeleteModal = true;
    }

    public function deleteForm(): void
    {
        $this->authorize('manage-forms');
        $form = DynamicForm::findOrFail($this->deletingFormId);

        if ($form->submissions()->exists()) {
            $this->error('Archive this form instead — it already has submissions.');
            $this->showDeleteModal = false;

            return;
        }

        $form->delete();
        $this->showDeleteModal = false;
        $this->deletingFormId = null;
        $this->success('Form deleted.');
    }

    public function render()
    {
        $query = DynamicForm::query()
            ->withCount('submissions')
            ->with(['creator', 'statuses'])
            ->latest();

        if ($this->search !== '') {
            $query->where(function ($builder) {
                $builder->where('title', 'like', '%'.$this->search.'%')
                    ->orWhere('slug', 'like', '%'.$this->search.'%');
            });
        }

        if ($this->status === 'published') {
            $query->whereNotNull('published_at')->where('is_active', true);
        } elseif ($this->status === 'draft') {
            $query->whereNull('published_at')->where('is_active', true);
        } elseif ($this->status === 'archived') {
            $query->where('is_active', false);
        }

        if (! auth()->user()->can('manage-forms')) {
            $query->where('is_active', true)->whereNotNull('published_at');
        }

        return view('livewire.admin.dynamic-form-list', [
            'forms' => $query->paginate(12),
            'canManage' => auth()->user()->can('manage-forms'),
            'canFill' => auth()->user()->can('fill-forms'),
            'canProcess' => auth()->user()->can('process-forms') || auth()->user()->can('view-forms'),
        ])->layout('layouts.app');
    }

    private function authorizeAnyFormAccess(): void
    {
        $user = auth()->user();
        if ($user->can('manage-forms') || $user->can('fill-forms') || $user->can('process-forms') || $user->can('view-forms')) {
            return;
        }

        abort(403);
    }
}
