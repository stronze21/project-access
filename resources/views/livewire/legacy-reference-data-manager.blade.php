<div class="space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
        <div>
            <h1 class="text-2xl font-bold text-base-content">{{ $config['title'] }}</h1>
            <p class="mt-1 text-sm text-base-content/60">Manage the values used by future legacy CSV promotions.</p>
        </div>
        <a href="{{ route('residents.legacy-import.index') }}" class="btn btn-outline btn-sm">Back to Legacy Import</a>
    </div>

    @include('legacy-data._navigation')

    @if ($notice)
        <div role="alert" class="alert alert-success" wire:transition>
            <span>{{ $notice }}</span>
            <button type="button" wire:click="$set('notice', null)" class="btn btn-ghost btn-xs">Dismiss</button>
        </div>
    @endif

    <section class="card border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body">
            <div class="flex items-center justify-between gap-3">
                <h2 class="card-title text-lg">
                    {{ $editingId ? 'Edit '.strtolower($config['singular']) : 'Add '.strtolower($config['singular']) }}
                </h2>
                @if ($editingId)
                    <button type="button" wire:click="cancelEdit" class="btn btn-ghost btn-sm">Cancel editing</button>
                @endif
            </div>

            <form wire:submit="save" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                @include('livewire._legacy-reference-fields')
                <div class="flex items-end">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save Changes' : 'Add Record' }}</span>
                        <span wire:loading wire:target="save" class="loading loading-spinner loading-sm"></span>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="card border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body p-0">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Legacy code</th>
                            <th>Name</th>
                            @if ($type === 'civil-statuses')<th>Project equivalent</th>@endif
                            @if ($type === 'barangays')<th>Project barangay</th><th>Status</th>@else<th>Status</th>@endif
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $record)
                            <tr wire:key="legacy-reference-{{ $type }}-{{ $record->id }}">
                                <td class="font-mono">{{ $record->legacy_code }}</td>
                                <td class="font-medium">{{ $record->name ?? $record->legacy_name }}</td>
                                @if ($type === 'civil-statuses')
                                    <td><span class="badge badge-outline">{{ str($record->canonical_value)->title() }}</span></td>
                                @endif
                                @if ($type === 'barangays')
                                    <td>{{ collect($barangays)->firstWhere('value', $record->brgy_code)['label'] ?? 'Unmapped' }}</td>
                                    <td><span class="badge {{ $record->status === 'mapped' ? 'badge-success' : ($record->status === 'ignored' ? 'badge-ghost' : 'badge-warning') }}">{{ ucfirst($record->status) }}</span></td>
                                @else
                                    <td><span class="badge {{ $record->is_active ? 'badge-success' : 'badge-ghost' }}">{{ $record->is_active ? 'Active' : 'Inactive' }}</span></td>
                                @endif
                                <td class="text-right">
                                    <button type="button" wire:click="edit({{ $record->id }})" class="btn btn-outline btn-sm">Edit</button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-10 text-center text-base-content/60">No records yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @if ($records->hasPages())
        <div class="flex items-center justify-between">
            <span class="text-sm text-base-content/60">Page {{ $records->currentPage() }} of {{ $records->lastPage() }}</span>
            <div class="join">
                <button type="button" wire:click="previousPage" class="btn join-item btn-sm" @disabled($records->onFirstPage())>Previous</button>
                <button type="button" wire:click="nextPage" class="btn join-item btn-sm" @disabled(! $records->hasMorePages())>Next</button>
            </div>
        </div>
    @endif
</div>
