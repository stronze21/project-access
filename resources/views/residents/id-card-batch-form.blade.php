<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Generate Batch ACCESS ID Cards</h2>
            <div class="flex gap-2">
                <x-mary-button link="{{ route('residents.id-cards.batches.index') }}"
                    class="btn-secondary btn-outline" icon="o-clock">Print History</x-mary-button>
                <x-mary-button link="{{ route('residents.index') }}"
                    class="tagged-color btn-secondary btn-outline btn-secline" icon="o-arrow-left">Back to Residents</x-mary-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl sm:px-6 lg:px-8">
            <x-mary-card>
                <h3 class="text-xl font-semibold">Print IDs by Barangay</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Landscape CR80 format. Each print batch contains up to {{ \App\Http\Controllers\ResidentIdCardController::MAX_BATCH_SIZE }} residents in your selected order.
                </p>

                <form action="{{ route('residents.id-cards.batch') }}" method="POST" class="mt-6 space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div>
                            <label for="filter-barangay" class="label font-semibold">Barangay</label>
                            <select name="barangay" id="filter-barangay" required class="select select-bordered w-full">
                                <option value="">Select a barangay</option>
                                <option value="all" @selected(old('barangay', $selectedBarangay) === 'all')>All Barangays</option>
                                @foreach ($barangayList as $barangay)
                                    @if (filled($barangay))
                                        <option value="{{ $barangay }}" @selected(old('barangay', $selectedBarangay) === $barangay)>{{ $barangay }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="filter-status" class="label font-semibold">Resident Status</label>
                            <select name="status" id="filter-status" class="select select-bordered w-full">
                                @foreach (['active' => 'Active Residents', 'all' => 'All Statuses', 'inactive' => 'Inactive Residents'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('status', $selectedStatus) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <fieldset class="rounded-lg border border-slate-200 p-4" x-data="{ enabled: @js((bool) old('filter_sectors', $filterSectors)) }">
                        <legend class="px-1 text-sm font-semibold">Sector</legend>
                        <input type="hidden" name="filter_sectors" value="0">
                        <label class="flex cursor-pointer items-center gap-3">
                            <input type="checkbox" name="filter_sectors" value="1" x-model="enabled"
                                class="checkbox checkbox-primary" @checked(old('filter_sectors', $filterSectors))>
                            <span class="text-sm font-semibold">Filter by sector</span>
                        </label>
                        <p class="mt-2 text-xs text-gray-500">Off includes all sectors, including residents without a sector. On includes residents in any selected sector.</p>
                        <div x-show="enabled" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @forelse ($sectorList as $sector)
                                <label class="flex cursor-pointer items-center gap-2 text-sm">
                                    <input type="checkbox" name="sectors[]" value="{{ $sector }}" :disabled="!enabled"
                                        class="checkbox checkbox-sm" @checked(in_array($sector, (array) old('sectors', $selectedSectors), true))>
                                    <span>{{ $sector }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-500">No sectors are recorded yet.</p>
                            @endforelse
                        </div>
                    </fieldset>

                    <input type="hidden" name="alphabetical" value="0">
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                        <input type="checkbox" name="alphabetical" value="1" class="checkbox checkbox-primary mt-0.5"
                            @checked(old('alphabetical', $alphabetical))>
                        <span>
                            <strong class="block text-sm text-gray-900">Arrange alphabetically (A–Z by surname)</strong>
                            <span class="mt-1 block text-xs text-gray-500">Sort by surname, then first name. Uncheck to use resident record ID order. Applies to preview and generated IDs within each batch.</span>
                        </span>
                    </label>

                    <input type="hidden" name="exclude_printed" value="0">
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                        <input type="checkbox" name="exclude_printed" value="1"
                            class="checkbox checkbox-primary mt-0.5"
                            @checked(old('exclude_printed', $excludePrinted))>
                        <span>
                            <strong class="block text-sm text-gray-900">Hide residents already assigned or printed</strong>
                            <span class="mt-1 block text-xs text-gray-500">Recommended to prevent duplicate ID printing. Uncheck only when an intentional reprint is required.</span>
                        </span>
                    </label>

                    @if ($errors->any())
                        <div class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800" role="alert">
                            <strong class="block">The print batch could not be generated</strong>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                        With duplicate protection enabled, residents already assigned to a tracked batch remain there. New residents are added to the latest unprinted batch with the same barangay, status, sector selection, and sorting setting when space is available, or placed in a new batch automatically. Batches with print initiation are never extended. To resume pending IDs, open Print History and choose Print Remaining. Print initiation does not confirm physical printing.
                    </div>

                    @isset ($previewResidents)
                        <section class="rounded-lg border border-slate-200 p-4" aria-label="Resident preview">
                            <h4 class="font-semibold">Resident preview</h4>
                            <p class="mt-1 text-sm">{{ $matchingCount }} matching · {{ $eligibleCount }} eligible · {{ $excludedCount }} already assigned or printed and excluded</p>
                            <p class="mt-1 text-xs text-gray-500">Showing up to 100 eligible residents. Order: {{ $alphabetical ? 'Surname A–Z' : 'Resident record ID' }}. Preview does not assign IDs. Refresh the preview after changing filters. Generate rechecks eligibility and fills up to 100 slots in a matching batch.</p>
                            @if ($pendingBatches->isNotEmpty())
                                <div class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-3">
                                    <h5 class="text-sm font-semibold">Already assigned, awaiting print initiation</h5>
                                    <p class="text-xs">These residents remain in their original batches, including All Barangays batches. Each link selects only pending IDs matching your preview filters.</p>
                                    <ul class="mt-2 space-y-2">
                                        @foreach ($pendingBatches as $batchId => $items)
                                            <li><a class="link text-sm" href="{{ route('residents.id-cards.batches.print', ['printBatch' => $batchId, 'selected_residents' => $items->pluck('resident_id')->unique()->values()->all()]) }}">Open {{ $items->count() }} Matching Pending ID(s) — Batch #{{ $batchId }}</a></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div class="mt-3 overflow-x-auto">
                                <table class="table w-full text-sm">
                                    <thead><tr><th>Resident</th><th>PIN</th><th>Barangay</th><th>Sector</th><th>Status</th></tr></thead>
                                    <tbody>
                                        @forelse ($previewResidents as $resident)
                                            <tr><td>{{ $resident->full_name }}</td><td>{{ $resident->resident_id }}</td><td>{{ $resident->household?->barangay }}</td><td>{{ $resident->special_sector ?: 'None' }}</td><td>{{ $resident->is_active ? 'Active' : 'Inactive' }}</td></tr>
                                        @empty
                                            <tr><td colspan="5">No eligible residents match these filters. Check Print History for pending IDs.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @endisset

                    <div class="flex flex-wrap justify-end gap-3 pt-4 border-t">
                        <x-mary-button type="submit" name="action" value="preview" class="btn-secondary btn-outline" icon="o-eye">
                            Preview Residents
                        </x-mary-button>
                        <x-mary-button type="submit" name="action" value="generate" class="tagged-color btn-primary" icon="o-printer">
                            Generate Barangay Batch
                        </x-mary-button>
                    </div>
                </form>
            </x-mary-card>
        </div>
    </div>
</x-app-layout>
