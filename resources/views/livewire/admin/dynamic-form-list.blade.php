<div>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Forms</h1>
            <p class="mt-1 text-sm text-gray-600">Build staff intake forms with a status workflow and tags. Answers are stored as structured data — no new database tables per form.</p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <a href="{{ route('forms.inbox') }}" class="btn btn-outline btn-sm inline-flex h-9 min-h-9 items-center">Inbox</a>
            @if ($canManage)
                <button type="button" wire:click="openCreate" class="btn btn-primary btn-sm inline-flex h-9 min-h-9 items-center">
                    New form
                </button>
            @endif
        </div>
    </div>

    <x-mary-card class="mb-6">
        <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-3">
            <x-mary-input label="Search" icon="o-magnifying-glass" wire:model.live.debounce.300ms="search" placeholder="Search forms..." />
            <x-mary-select
                label="Status"
                wire:model.live="status"
                :options="[
                    ['id' => 'all', 'name' => 'All'],
                    ['id' => 'published', 'name' => 'Published'],
                    ['id' => 'draft', 'name' => 'Draft'],
                    ['id' => 'archived', 'name' => 'Archived'],
                ]"
            />
        </div>
    </x-mary-card>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        @forelse ($forms as $form)
            <x-mary-card>
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">{{ $form->title }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $form->description ?: 'No description' }}</p>
                    </div>
                    @if (! $form->is_active)
                        <x-mary-badge value="Archived" class="badge-ghost" />
                    @elseif ($form->published_at)
                        <x-mary-badge value="Published" class="badge-success" />
                    @else
                        <x-mary-badge value="Draft" class="badge-warning" />
                    @endif
                </div>
                <div class="mt-3 flex flex-wrap gap-3 text-xs text-slate-500">
                    <span>{{ $form->submissions_count }} submission{{ $form->submissions_count === 1 ? '' : 's' }}</span>
                    <span>{{ $form->statuses->count() }} statuses</span>
                    @if ($form->link_to_resident)
                        <span>Links to resident</span>
                    @endif
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    @if ($canFill && $form->is_active)
                        <a href="{{ route('forms.fill', $form) }}" class="btn btn-primary btn-sm inline-flex h-8 min-h-8 items-center">Fill</a>
                    @endif
                    @if ($canProcess)
                        <a href="{{ route('forms.inbox', ['formId' => $form->id]) }}" class="btn btn-outline btn-sm inline-flex h-8 min-h-8 items-center">Inbox</a>
                    @endif
                    @if ($canManage)
                        <a href="{{ route('forms.edit', $form) }}" class="btn btn-outline btn-sm inline-flex h-8 min-h-8 items-center">Edit</a>
                        <button type="button" wire:click="duplicate({{ $form->id }})" class="btn btn-outline btn-sm inline-flex h-8 min-h-8 items-center">Duplicate</button>
                        @if (! $form->published_at)
                            <button type="button" wire:click="publish({{ $form->id }})" class="btn btn-outline btn-sm inline-flex h-8 min-h-8 items-center">Publish</button>
                        @endif
                        @if ($form->is_active)
                            <button type="button" wire:click="archive({{ $form->id }})" class="btn btn-outline btn-sm inline-flex h-8 min-h-8 items-center">Archive</button>
                        @endif
                        <button type="button" wire:click="confirmDelete({{ $form->id }})" class="btn btn-outline btn-error btn-sm inline-flex h-8 min-h-8 items-center">Delete</button>
                    @endif
                </div>
            </x-mary-card>
        @empty
            <x-mary-card class="lg:col-span-2">
                <p class="text-sm text-slate-500">No forms yet. Create one to start collecting staff submissions.</p>
            </x-mary-card>
        @endforelse
    </div>

    <div class="mt-4">{{ $forms->links() }}</div>

    <x-mary-modal wire:model="showCreateModal" title="New form">
        <x-mary-input label="Title" wire:model="newTitle" placeholder="e.g. Barangay intake interview" />
        <x-slot:actions>
            <x-mary-button label="Cancel" wire:click="$set('showCreateModal', false)" />
            <x-mary-button label="Create and edit" class="btn-primary" wire:click="createForm" />
        </x-slot:actions>
    </x-mary-modal>

    <x-mary-modal wire:model="showDeleteModal" title="Delete form?">
        <p class="text-sm text-slate-600">This only works if the form has no submissions. Forms with answers should be archived instead.</p>
        <x-slot:actions>
            <x-mary-button label="Cancel" wire:click="$set('showDeleteModal', false)" />
            <x-mary-button label="Delete" class="btn-error" wire:click="deleteForm" />
        </x-slot:actions>
    </x-mary-modal>
</div>
