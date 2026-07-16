<div class="px-4 py-8 space-y-6 sm:px-6 lg:px-8">
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
        <div>
            <h1 class="text-2xl font-bold text-base-content">BHW Master</h1>
            <p class="mt-1 text-sm text-base-content/60">Manage one primary and one secondary resident BHW per zone.</p>
        </div>
        <a href="{{ route('residents.legacy-import.index') }}" class="btn btn-outline btn-sm">Back to Import Manager</a>
    </div>

    @include('legacy-data._navigation', ['type' => 'bhw'])

    @if ($notice)
        <div role="alert" class="alert alert-success" wire:transition>
            <span>{{ $notice }}</span>
            <button type="button" wire:click="$set('notice', null)" class="btn btn-ghost btn-xs">Dismiss</button>
        </div>
    @endif

    <section class="border shadow-sm card border-base-300 bg-base-100">
        <div class="card-body">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg card-title">{{ $editingId ? 'Edit BHW zone' : 'Add BHW zone' }}</h2>
                @if ($editingId)<button type="button" wire:click="cancelEdit" class="btn btn-ghost btn-sm">Cancel editing</button>@endif
            </div>

            <form wire:submit="save" class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Legacy barangay code</legend>
                    <input type="text" wire:model="form.legacy_barangay_code" class="w-full input input-bordered">
                    @error('form.legacy_barangay_code')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Zone ID</legend>
                    <input type="text" wire:model="form.legacy_zone_id" class="w-full input input-bordered">
                    @error('form.legacy_zone_id')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Zone name</legend>
                    <input type="text" wire:model="form.name" class="w-full input input-bordered">
                    @error('form.name')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Project barangay</legend>
                    <select wire:model="form.brgy_code" class="w-full select select-bordered">
                        <option value="">Unmapped</option>
                        @foreach ($barangays as $barangay)<option value="{{ $barangay->brgyCode }}">{{ $barangay->brgyDesc }}</option>@endforeach
                    </select>
                    @error('form.brgy_code')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Primary BHW PIN</legend>
                    <input type="text" wire:model="form.primary_pin" placeholder="Resident ID / PIN" class="w-full input input-bordered">
                    @error('form.primary_pin')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </fieldset>
                <fieldset class="fieldset">
                    <legend class="fieldset-legend">Secondary BHW PIN</legend>
                    <input type="text" wire:model="form.secondary_pin" placeholder="Resident ID / PIN" class="w-full input input-bordered">
                    @error('form.secondary_pin')<p class="text-sm text-error">{{ $message }}</p>@enderror
                </fieldset>
                <div class="flex items-end">
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{ $editingId ? 'Save Changes' : 'Add Zone' }}</span>
                        <span wire:loading wire:target="save" class="loading loading-spinner loading-sm"></span>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="border shadow-sm card border-base-300 bg-base-100">
        <div class="p-0 card-body">
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead><tr><th>Barangay / Zone</th><th>Zone name</th><th>Primary BHW</th><th>Secondary BHW</th><th class="text-right">Actions</th></tr></thead>
                    <tbody>
                        @forelse ($zones as $zone)
                            @php($assignments = $zone->healthWorkerAssignments->keyBy('assignment_slot'))
                            <tr wire:key="bhw-zone-{{ $zone->id }}">
                                <td><span class="font-mono">{{ $zone->legacy_barangay_code }} / {{ $zone->legacy_zone_id }}</span></td>
                                <td class="font-medium">{{ $zone->name }}</td>
                                <td>{{ $assignments->get('primary')?->legacy_pin ?? 'Unassigned' }}</td>
                                <td>{{ $assignments->get('secondary')?->legacy_pin ?? 'Unassigned' }}</td>
                                <td>
                                    <div class="flex justify-end gap-2">
                                        <button type="button" wire:click="edit({{ $zone->id }})" class="btn btn-outline btn-sm">Edit</button>
                                        <button type="button" wire:click="requestDelete({{ $zone->id }})" class="btn btn-error btn-outline btn-sm">Remove</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-10 text-center text-base-content/60">No BHW zones yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @if ($zones->hasPages())
        <div class="flex items-center justify-between">
            <span class="text-sm text-base-content/60">Page {{ $zones->currentPage() }} of {{ $zones->lastPage() }}</span>
            <div class="join">
                <button type="button" wire:click="previousPage" class="btn join-item btn-sm" @disabled($zones->onFirstPage())>Previous</button>
                <button type="button" wire:click="nextPage" class="btn join-item btn-sm" @disabled(! $zones->hasMorePages())>Next</button>
            </div>
        </div>
    @endif

    @if ($deleteId)
        <div class="modal modal-open" role="dialog">
            <div class="modal-box">
                <h3 class="text-lg font-bold">Remove BHW zone?</h3>
                <p class="py-4">The zone and its BHW assignments will be removed. Resident BHW flags will be recalculated.</p>
                <div class="modal-action">
                    <button type="button" wire:click="cancelDelete" class="btn">Cancel</button>
                    <button type="button" wire:click="deleteZone" class="btn btn-error" wire:loading.attr="disabled">Remove Zone</button>
                </div>
            </div>
            <button type="button" wire:click="cancelDelete" class="modal-backdrop">Close</button>
        </div>
    @endif
</div>
