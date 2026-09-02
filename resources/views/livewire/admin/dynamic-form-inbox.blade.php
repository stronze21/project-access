<div>
    <div class="mb-6">
        <a href="{{ route('forms.index') }}" class="text-sm text-slate-500 hover:text-slate-800">← Forms</a>
        <div class="mt-1 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Form inbox</h1>
                <p class="mt-1 text-sm text-gray-600">Filter submissions, work them on a status board, and export answers to CSV.</p>
            </div>
            <div class="join shrink-0">
                <button
                    type="button"
                    wire:click="$set('view', 'table')"
                    class="btn join-item btn-sm inline-flex h-9 min-h-9 items-center {{ $view === 'table' ? 'btn-primary' : 'btn-outline' }}"
                >Table</button>
                <button
                    type="button"
                    wire:click="$set('view', 'board')"
                    class="btn join-item btn-sm inline-flex h-9 min-h-9 items-center {{ $view === 'board' ? 'btn-primary' : 'btn-outline' }}"
                >Board</button>
                @if ($selectedForm && (auth()->user()->can('view-forms') || auth()->user()->can('process-forms')))
                    <a
                        href="{{ route('forms.export', $selectedForm) }}"
                        class="btn btn-outline join-item btn-sm inline-flex h-9 min-h-9 items-center"
                    >Export CSV</a>
                @endif
            </div>
        </div>
    </div>

    <x-mary-card class="mb-6">
        <div class="grid grid-cols-1 items-end gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-mary-select
                label="Form"
                wire:model.live="formId"
                placeholder="All forms"
                placeholder-value=""
                :options="$forms->map(fn ($formOption) => ['id' => (string) $formOption->id, 'name' => $formOption->title])->values()->all()"
            />
            <x-mary-input label="Search" wire:model.live.debounce.300ms="search" placeholder="Reference, staff, resident" />
            @if ($selectedForm)
                <x-mary-select
                    label="Status"
                    wire:model.live="statusId"
                    placeholder="All statuses"
                    placeholder-value=""
                    :options="$selectedForm->statuses->map(fn ($status) => ['id' => (string) $status->id, 'name' => $status->label])->values()->all()"
                />
                <x-mary-select
                    label="Tag"
                    wire:model.live="tagId"
                    placeholder="All tags"
                    placeholder-value=""
                    :options="$selectedForm->tags->map(fn ($tag) => ['id' => (string) $tag->id, 'name' => $tag->label])->values()->all()"
                />
            @endif
        </div>
        @if ($filterableFields->isNotEmpty())
            <div class="mt-4 grid grid-cols-1 items-end gap-4 md:grid-cols-3">
                <x-mary-select
                    label="Filterable field"
                    wire:model.live="filterFieldKey"
                    placeholder="None"
                    placeholder-value=""
                    :options="$filterableFields->map(fn ($field) => ['id' => $field->key, 'name' => $field->label])->values()->all()"
                />
                <x-mary-input label="Value" wire:model.live.debounce.400ms="filterFieldValue" />
                <button type="button" wire:click="clearFilters" class="btn btn-outline inline-flex h-12 min-h-12 w-full items-center justify-center">Clear filters</button>
            </div>
        @endif
    </x-mary-card>

    @if ($view === 'board')
        @if (! $selectedForm)
            <x-mary-card>
                <p class="text-sm text-slate-500">Choose a form to see its status board.</p>
            </x-mary-card>
        @else
            <div class="flex gap-4 overflow-x-auto pb-4">
                @foreach ($selectedForm->statuses as $status)
                    <div class="w-72 shrink-0 rounded-xl border {{ $status->boardClass() }} p-3">
                        <div class="mb-3 flex items-center justify-between">
                            <h2 class="font-semibold text-slate-800">{{ $status->label }}</h2>
                            <span class="text-xs text-slate-500">{{ ($board[$status->id] ?? collect())->count() }}</span>
                        </div>
                        <div class="space-y-2">
                            @forelse ($board[$status->id] ?? [] as $submission)
                                <a href="{{ route('forms.submissions.show', $submission) }}" class="block rounded-lg bg-white p-3 text-sm shadow-sm hover:shadow">
                                    <div class="font-medium text-slate-800">{{ $submission->reference_number }}</div>
                                    <div class="mt-1 text-xs text-slate-500">{{ $submission->creator?->name }} · {{ $submission->created_at?->timezone('Asia/Manila')->format('M j, g:ia') }}</div>
                                    @if ($submission->tags->isNotEmpty())
                                        <div class="mt-2 flex flex-wrap gap-1">
                                            @foreach ($submission->tags as $tag)
                                                <span class="badge badge-xs {{ $tag->badgeClass() }}">{{ $tag->label }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </a>
                            @empty
                                <p class="text-xs text-slate-400">No submissions</p>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @else
        <x-mary-card>
            <div class="overflow-x-auto">
                <table class="w-full table-auto">
                    <thead class="bg-base-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-700">Reference</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-700">Form</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-700">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-700">Tags</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-700">Created by</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-700">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($submissions as $submission)
                            <tr class="border-t hover:bg-slate-50">
                                <td class="px-4 py-3">
                                    <a class="font-medium text-sky-700 hover:underline" href="{{ route('forms.submissions.show', $submission) }}">{{ $submission->reference_number }}</a>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $submission->form?->title }}</td>
                                <td class="px-4 py-3">
                                    @if ($submission->status)
                                        <span class="badge {{ $submission->status->badgeClass() }}">{{ $submission->status->label }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($submission->tags as $tag)
                                            <span class="badge badge-sm {{ $tag->badgeClass() }}">{{ $tag->label }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm">{{ $submission->creator?->name }}</td>
                                <td class="px-4 py-3 text-sm">{{ $submission->created_at?->timezone('Asia/Manila')->format('M j, Y g:ia') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">No submissions match these filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if (method_exists($submissions, 'links'))
                <div class="mt-4">{{ $submissions->links() }}</div>
            @endif
        </x-mary-card>
    @endif
</div>
