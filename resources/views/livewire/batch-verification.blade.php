<div>
    <div class="flex flex-col justify-between gap-4 mb-6 md:flex-row md:items-end">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Batch Verification</h1>
            <p class="mt-1 text-sm text-gray-600">Verify multiple aid distributions at once</p>
        </div>
        <div class="flex space-x-2">
            <x-mary-button link="{{ route('distributions.index') }}" icon="o-arrow-left"
                class="tagged-color btn-secondary btn-outline btn-secline" label="Back to Distributions" />
        </div>
    </div>

    <!-- Filters Section -->
    <div class="mb-6">
        <x-mary-card title="Search & Filters">
            <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-3">
                <div>
                    <x-mary-select label="Distribution Status" wire:model.live="status" :options="[['key' => 'pending', 'id' => 'Pending'], ['key' => 'verified', 'id' => 'Verified']]"
                        option-value="key" option-label="id" hint="Select the status to filter by" />
                </div>

                <div>
                    <x-mary-select label="Ayuda Program" wire:model.live="programId" :options="collect($programList)->map(fn($p) => ['key' => $p->id, 'id' => $p->name])->toArray()"
                        option-value="key" option-label="id" placeholder="All Programs" />
                </div>

                <div>
                    <x-mary-select label="Barangay" wire:model.live="barangay" placeholder="All Barangays"
                        :options="collect($barangayList)->map(fn($b) => ['key' => $b, 'id' => $b])->toArray()" option-value="key" option-label="id" />
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-3">
                <div>
                    <x-mary-select label="Distribution Batch" wire:model.live="batchId" :options="collect($batchList)
                        ->map(fn($b) => ['key' => $b->id, 'id' => $b->batch_number])
                        ->toArray()"
                        option-value="key" option-label="id" placeholder="All Batches" />
                </div>

                <div>
                    <x-mary-datetime label="From Date" wire:model.live="dateFrom" />
                </div>

                <div>
                    <x-mary-datetime label="To Date" wire:model.live="dateTo" />
                </div>
            </div>

            <div class="flex justify-between mt-4">
                <x-mary-input wire:model.live.debounce.300ms="search"
                    placeholder="Search by reference # or recipient name" class="w-full max-w-xs" />
                <x-mary-button wire:click="resetFilters" class="tagged-color btn-secondary btn-outline btn-secline"
                    size="sm">Reset
                    Filters</x-mary-button>
            </div>
        </x-mary-card>
    </div>

    <!-- Verification Actions -->
    <div class="mb-4">
        <div class="p-4 rounded-lg bg-blue-50">
            <div class="flex flex-col justify-between md:flex-row md:items-center">
                <div class="mb-3 md:mb-0">
                    <h3 class="text-lg font-medium text-blue-800">Verification Options</h3>
                    <p class="text-blue-700">Select distributions and choose an action</p>
                </div>
                <div class="flex flex-col space-y-2 md:flex-row md:space-y-0 md:space-x-2">
                    <x-mary-select wire:model="targetStatus" :options="[
                        ['key' => 'verified', 'id' => 'Mark as Verified'],
                        ['key' => 'distributed', 'id' => 'Mark as Distributed'],
                        ['key' => 'rejected', 'id' => 'Mark as Rejected'],
                    ]" option-value="key" option-label="id" />

                    <x-mary-button wire:click="processVerification" class="tagged-color btn-success"
                        wire:loading.attr="disabled" wire:loading.class="opacity-75">
                        <span wire:loading.remove wire:target="processVerification">Process Selected</span>
                        <span wire:loading wire:target="processVerification">Processing...</span>
                    </x-mary-button>
                </div>
            </div>

            <div class="mt-4">
                <x-mary-textarea wire:model="verificationNote"
                    placeholder="Optional: Add a verification note to all selected distributions" rows="2"
                    label="Verification Note" />
            </div>
        </div>
    </div>

    <!-- Distributions Table -->
    <x-mary-card>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="w-10 px-4 py-3">
                            <x-mary-checkbox wire:model.live="selectAll" label="" />
                        </th>
                        <th scope="col" class="px-4 py-3">Reference #</th>
                        <th scope="col" class="px-4 py-3">Beneficiary</th>
                        <th scope="col" class="px-4 py-3">Program</th>
                        <th scope="col" class="px-4 py-3">Barangay</th>
                        <th scope="col" class="px-4 py-3">Date</th>
                        <th scope="col" class="px-4 py-3">Amount</th>
                        <th scope="col" class="px-4 py-3">Batch</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($distributions as $distribution)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <x-mary-checkbox wire:model.live="selectedDistributions.{{ $distribution->id }}"
                                    label="" />
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <a href="{{ route('distributions.show', $distribution->id) }}"
                                    class="text-blue-600 hover:underline">
                                    {{ $distribution->reference_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('residents.show', $distribution->resident_id) }}"
                                    class="text-blue-600 hover:underline">
                                    {{ $distribution->resident->full_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                {{ $distribution->ayudaProgram->name }}
                            </td>
                            <td class="px-4 py-3">
                                {{ optional($distribution->resident)->household->barangay ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $distribution->distribution_date->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3">
                                ₱{{ number_format($distribution->amount, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($distribution->batch)
                                    <span class="text-xs">{{ $distribution->batch->batch_number }}</span>
                                @else
                                    <span class="text-xs text-gray-500">Individual</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $distribution->status === 'distributed'
                                        ? 'bg-green-100 text-green-800'
                                        : ($distribution->status === 'verified'
                                            ? 'bg-blue-100 text-blue-800'
                                            : ($distribution->status === 'pending'
                                                ? 'bg-yellow-100 text-yellow-800'
                                                : 'bg-red-100 text-red-800')) }}">
                                    {{ ucfirst($distribution->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr class="bg-white border-b">
                            <td colspan="9" class="px-4 py-6 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="mb-1">No distributions found with the selected filters</p>
                                    <p class="text-sm">Try adjusting your filters or select a different status</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between px-4 py-3 border-t">
            <div class="text-sm text-gray-600">
                <span>{{ count(array_filter($selectedDistributions)) }} selected</span>
            </div>
            {{ $distributions->links() }}
        </div>
    </x-mary-card>

    <!-- Verification Confirmation Modal -->
    @if ($verificationComplete)
        <x-mary-modal title="Verification Complete" wire:model="verificationComplete">
            <div class="p-4 mb-4 text-center rounded-lg bg-green-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 mx-auto mb-3 text-green-500" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <h3 class="text-lg font-medium text-green-800">Success!</h3>
                <p class="text-green-700">
                    {{ count($verificationResults) }} distributions have been processed successfully.
                </p>
            </div>

            <div class="mb-4">
                <h4 class="mb-2 font-medium">Processed Distributions</h4>
                <div class="overflow-y-auto max-h-60">
                    <table class="w-full text-sm">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th class="px-2 py-2">Reference #</th>
                                <th class="px-2 py-2">Beneficiary</th>
                                <th class="px-2 py-2">Program</th>
                                <th class="px-2 py-2">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($verificationResults as $result)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-2 py-2 font-medium">{{ $result['reference_number'] }}</td>
                                    <td class="px-2 py-2">{{ $result['resident_name'] }}</td>
                                    <td class="px-2 py-2">{{ $result['program_name'] }}</td>
                                    <td class="px-2 py-2">₱{{ number_format($result['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <x-slot:actions>
                <x-mary-button wire:click="resetVerification" class="tagged-color btn-primary">
                    Continue
                </x-mary-button>
            </x-slot:actions>
        </x-mary-modal>
    @endif
</div>
