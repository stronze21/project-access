<div>
    <div class="flex flex-col justify-between gap-4 mb-6 md:flex-row md:items-end">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Barangay Batch Distribution</h1>
            <p class="mt-1 text-sm text-gray-600">Distribute aid to multiple residents in a barangay at once</p>
        </div>
        <div class="flex space-x-2">
            <x-mary-button link="{{ route('distributions.index') }}" icon="o-arrow-left"
                class="tagged-color btn-secondary btn-outline btn-secline" label="Back to Distributions" />
        </div>
    </div>

    @if (!$processingComplete)
        <!-- Batch Distribution Form -->
        <div class="mb-6">
            <x-mary-card>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- Program and Batch Selection -->
                    <div>
                        <h3 class="mb-4 font-medium">Distribution Settings</h3>

                        <div class="mb-4">
                            <x-mary-select label="Ayuda Program" wire:model.live="selectedProgramId" required
                                placeholder="Select an Ayuda program" :options="collect($availablePrograms)
                                    ->map(fn($p) => ['key' => $p->id, 'id' => $p->name])
                                    ->toArray()" option-value="key"
                                option-label="id" />
                        </div>

                        <div class="mb-4">
                            <x-mary-select label="Distribution Batch (Optional)" wire:model.live="selectedBatchId"
                                placeholder="Select a batch" :options="collect($availableBatches)
                                    ->map(fn($b) => ['key' => $b->id, 'id' => $b->batch_number . ' - ' . $b->location])
                                    ->toArray()" option-value="key" option-label="id" />
                        </div>

                        <div class="mb-4">
                            <x-mary-datetime label="Distribution Date" wire:model="distributionDate" required />
                        </div>

                        <div class="mb-4">
                            <x-mary-select label="Distribution Status" wire:model="status" required :options="[
                                ['key' => 'pending', 'id' => 'Pending'],
                                ['key' => 'verified', 'id' => 'Verified'],
                                ['key' => 'distributed', 'id' => 'Distributed'],
                            ]"
                                option-value="key" option-label="id" />
                            <p class="mt-1 text-xs text-gray-500">Select "Distributed" to mark as complete immediately
                            </p>
                        </div>
                    </div>

                    <!-- Aid Details -->
                    <div>
                        <h3 class="mb-4 font-medium">Aid Details</h3>

                        @if ($programType === 'cash' || $programType === 'mixed')
                            <div class="mb-4">
                                <x-mary-input label="Amount (₱)" wire:model="amount" type="number" step="0.01"
                                    required
                                    hint="{{ $programAmount > 0 ? 'Default: ₱' . number_format($programAmount, 2) : 'Enter amount to distribute' }}" />
                            </div>
                        @endif

                        @if ($programType === 'goods' || $programType === 'mixed')
                            <div class="mb-4">
                                <x-mary-textarea label="Goods Details" wire:model="goodsDetails"
                                    placeholder="Describe the goods distributed" rows="2" />
                            </div>
                        @endif

                        @if ($programType === 'services' || $programType === 'mixed')
                            <div class="mb-4">
                                <x-mary-textarea label="Services Details" wire:model="servicesDetails"
                                    placeholder="Describe the services provided" rows="2" />
                            </div>
                        @endif

                        <div class="mb-4">
                            <x-mary-textarea label="Notes" wire:model="notes"
                                placeholder="Additional notes about this distribution" rows="2" />
                        </div>
                    </div>
                </div>
            </x-mary-card>
        </div>

        <!-- Barangay Selection and Resident List -->
        <div class="mb-6">
            <x-mary-card>
                <div class="mb-6">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <x-mary-select label="Select Barangay" wire:model.live="barangay"
                                placeholder="Choose a barangay" :options="collect($barangayList)->map(fn($b) => ['key' => $b, 'id' => $b])->toArray()" option-value="key"
                                option-label="id" />
                        </div>
                        <div>
                            <x-mary-input label="Search Residents" wire:model.live.debounce.300ms="search"
                                placeholder="Search by name or ID" />
                        </div>
                    </div>

                    @if (!empty($barangay))
                        <div class="flex flex-wrap items-center gap-3 pt-4 mt-4 border-t">
                            <span class="font-medium text-gray-700">Filter by:</span>
                            <x-mary-toggle label="Senior Citizens" wire:model.live="filterSeniors" hint="Ages 60+" />
                            <x-mary-toggle label="Solo Parents" wire:model.live="filterSoloParent"
                                hint="For solo parents" />
                            <x-mary-toggle label="PWD" wire:model.live="filterPwd"
                                hint="Persons with disabilities" />
                        </div>
                    @endif
                </div>

                @if (!empty($barangay))
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="w-10 px-4 py-3">
                                        <x-mary-checkbox wire:model.live="selectAll" label="" />
                                    </th>
                                    <th scope="col" class="px-4 py-3">ID</th>
                                    <th scope="col" class="px-4 py-3">Name</th>
                                    <th scope="col" class="px-4 py-3">Age/Gender</th>
                                    <th scope="col" class="px-4 py-3">Household</th>
                                    <th scope="col" class="px-4 py-3">Tags</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($residents as $resident)
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <x-mary-checkbox wire:model.live="selectedResidents.{{ $resident->id }}"
                                                label="" />
                                        </td>
                                        <td class="px-4 py-3 font-medium text-gray-900">
                                            {{ $resident->resident_id }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                @if ($resident->photo_path)
                                                    <img src="{{ Storage::url($resident->photo_path) }}"
                                                        alt="{{ $resident->full_name }}"
                                                        class="object-cover w-8 h-8 mr-2 rounded-full">
                                                @else
                                                    <div
                                                        class="flex items-center justify-center w-8 h-8 mr-2 bg-gray-200 rounded-full">
                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                            class="w-4 h-4 text-gray-500" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                    </div>
                                                @endif
                                                <div>
                                                    {{ $resident->last_name }}, {{ $resident->first_name }}
                                                    {{ $resident->middle_name ? substr($resident->middle_name, 0, 1) . '.' : '' }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $resident->getAge() }} / {{ ucfirst($resident->gender) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($resident->household)
                                                {{ $resident->household->household_id }}
                                                <div class="text-xs text-gray-500">{{ $resident->household->address }}
                                                </div>
                                            @else
                                                <span class="text-gray-500">No household</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-1">
                                                @if ($resident->is_senior_citizen)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">Senior</span>
                                                @endif
                                                @if ($resident->is_pwd)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">PWD</span>
                                                @endif
                                                @if ($resident->is_solo_parent)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">Solo
                                                        Parent</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr class="bg-white border-b">
                                        <td colspan="6" class="px-4 py-6 text-center text-gray-500">
                                            <div class="flex flex-col items-center justify-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2"
                                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                <p class="mb-1">No residents found in this barangay</p>
                                                <p class="text-sm">Try selecting a different barangay or adjusting your
                                                    search</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if (count($residents) > 0)
                        <div class="flex flex-col items-center justify-between px-4 py-3 border-t sm:flex-row">
                            <div class="flex items-center mb-3 sm:mb-0">
                                <span class="text-sm text-gray-700">{{ count(array_filter($selectedResidents)) }}
                                    selected</span>
                            </div>
                            {{ $residents->links() }}
                        </div>
                    @endif
                @else
                    <div class="p-6 text-center text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-4 text-gray-400"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <p class="mb-1 text-lg font-medium">Select a Barangay</p>
                        <p class="text-sm">Choose a barangay to view and select residents for batch distribution</p>
                    </div>
                @endif
            </x-mary-card>
        </div>

        <div class="flex justify-end space-x-3">
            <x-mary-button wire:click="resetForm" class="tagged-color btn-secondary btn-outline btn-secline"
                label="Reset Form" />
            <x-mary-button wire:click="checkForDuplicates" class="tagged-color btn-secondary btn-outline btn-secline"
                icon="o-check-circle">
                Check for Duplicates
            </x-mary-button>
            <x-mary-button wire:click="previewBatchDistribution" icon="o-clipboard-document-check">
                Preview Distribution
            </x-mary-button>
        </div>

        <!-- Preview Modal -->
        @if ($showPreview)
            <x-mary-modal title="Batch Distribution Preview" wire:model="showPreview" max-width="4xl">
                <div class="mb-4">
                    <div class="grid grid-cols-1 gap-4 p-4 rounded-lg bg-blue-50 md:grid-cols-2">
                        <div>
                            <p class="font-medium">Program: <span
                                    class="font-normal">{{ optional(\App\Models\AyudaProgram::find($selectedProgramId))->name }}</span>
                            </p>
                            <p class="font-medium">Barangay: <span class="font-normal">{{ $barangay }}</span></p>
                            <p class="font-medium">Distribution Date: <span
                                    class="font-normal">{{ \Carbon\Carbon::parse($distributionDate)->format('M d, Y') }}</span>
                            </p>
                            <p class="font-medium">Status: <span class="font-normal">{{ ucfirst($status) }}</span>
                            </p>
                        </div>
                        <div>
                            <p class="font-medium">Amount: <span
                                    class="font-normal">₱{{ number_format($amount, 2) }}</span></p>
                            @if ($goodsDetails)
                                <p class="font-medium">Goods: <span class="font-normal">{{ $goodsDetails }}</span>
                                </p>
                            @endif
                            @if ($servicesDetails)
                                <p class="font-medium">Services: <span
                                        class="font-normal">{{ $servicesDetails }}</span></p>
                            @endif
                            @if ($notes)
                                <p class="font-medium">Notes: <span class="font-normal">{{ $notes }}</span>
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <h3 class="mb-2 font-medium">Selected Recipients ({{ count(array_filter($selectedResidents)) }})
                    </h3>
                    <div class="overflow-y-auto max-h-64">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-2">ID</th>
                                    <th scope="col" class="px-4 py-2">Name</th>
                                    <th scope="col" class="px-4 py-2">Age/Gender</th>
                                    <th scope="col" class="px-4 py-2">Household</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $selectedIds = array_keys(array_filter($selectedResidents));
                                    $previewResidents = \App\Models\Resident::whereIn('id', $selectedIds)
                                        ->with('household')
                                        ->get();
                                @endphp

                                @foreach ($previewResidents as $resident)
                                    <tr class="bg-white border-b">
                                        <td class="px-4 py-2 font-medium text-gray-900">
                                            {{ $resident->resident_id }}
                                        </td>
                                        <td class="px-4 py-2">
                                            {{ $resident->last_name }}, {{ $resident->first_name }}
                                            {{ $resident->middle_name ? substr($resident->middle_name, 0, 1) . '.' : '' }}
                                        </td>
                                        <td class="px-4 py-2">
                                            {{ $resident->getAge() }} / {{ ucfirst($resident->gender) }}
                                        </td>
                                        <td class="px-4 py-2">
                                            @if ($resident->household)
                                                {{ $resident->household->household_id }}
                                            @else
                                                <span class="text-gray-500">No household</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-4">
                    <h3 class="mb-2 font-medium">Distribution Summary</h3>
                    <div class="grid grid-cols-2 gap-4 p-4 rounded-lg md:grid-cols-3 bg-gray-50">
                        <div>
                            <p class="text-sm text-gray-600">Total Recipients</p>
                            <p class="text-lg font-medium">{{ $previewStats['total'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">From Barangay</p>
                            <p class="text-lg font-medium">{{ $barangay }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Amount</p>
                            <p class="text-lg font-medium">₱{{ number_format($previewStats['totalAmount'], 2) }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Senior Citizens</p>
                            <p class="text-lg font-medium">{{ $previewStats['seniors'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">PWD</p>
                            <p class="text-lg font-medium">{{ $previewStats['pwd'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Solo Parents</p>
                            <p class="text-lg font-medium">{{ $previewStats['soloParents'] }}</p>
                        </div>

                        @if (isset($previewStats['potentialDuplicates']) && $previewStats['potentialDuplicates'] > 0)
                            <div class="p-3 mt-2 text-red-700 bg-red-100 rounded-lg col-span-full">
                                <p class="flex items-center font-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    Warning: {{ $previewStats['potentialDuplicates'] }} potential duplicate
                                    distributions detected
                                </p>
                                <p class="mt-1 text-sm ml-7">
                                    Some residents may have already received aid from this program.
                                    Use the "Check for Duplicates" button to remove them from selection.
                                </p>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="p-4 rounded-lg bg-yellow-50">
                    <p class="text-yellow-800">
                        <strong>Warning:</strong> This will create {{ count(array_filter($selectedResidents)) }}
                        distribution records with status "{{ ucfirst($status) }}".
                        @if ($status === 'distributed')
                            This will immediately update program statistics and cannot be easily undone.
                        @endif
                    </p>
                </div>

                <x-slot:actions>
                    <x-mary-button wire:click="cancelPreview"
                        class="tagged-color btn-secondary btn-outline btn-secline">Cancel</x-mary-button>
                    <x-mary-button wire:click="processBatchDistribution" class="tagged-color btn-primary"
                        wire:loading.attr="disabled" wire:loading.class="opacity-75">
                        <span wire:loading.remove wire:target="processBatchDistribution">Process Batch
                            Distribution</span>
                        <span wire:loading wire:target="processBatchDistribution">Processing...</span>
                    </x-mary-button>
                </x-slot:actions>
            </x-mary-modal>
        @endif
    @else
        <!-- Processing Results -->
        <x-mary-card title="Batch Distribution Results">
            <div class="p-4 mb-6 text-center rounded-lg bg-blue-50">
                <h2 class="text-lg font-medium text-blue-800">Batch Processing Complete</h2>
                <p class="text-blue-700">Distribution has been processed for {{ $processingResults['total'] }}
                    residents</p>
            </div>

            <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-3">
                <div class="p-4 text-center border rounded-lg">
                    <p class="text-lg font-medium">Total</p>
                    <p class="text-2xl font-semibold">{{ $processingResults['total'] }}</p>
                </div>
                <div class="p-4 text-center border border-green-200 rounded-lg bg-green-50">
                    <p class="text-lg font-medium text-green-800">Successful</p>
                    <p class="text-2xl font-semibold text-green-800">{{ $processingResults['successful'] }}</p>
                </div>
                <div class="p-4 text-center border border-red-200 rounded-lg bg-red-50">
                    <p class="text-lg font-medium text-red-800">Failed</p>
                    <p class="text-2xl font-semibold text-red-800">{{ $processingResults['failed'] }}</p>
                </div>
            </div>

            <div class="mb-4">
                <h3 class="mb-2 font-medium">Processing Details</h3>
                <div class="overflow-y-auto max-h-96">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-2">Resident ID</th>
                                <th scope="col" class="px-4 py-2">Name</th>
                                <th scope="col" class="px-4 py-2">Status</th>
                                <th scope="col" class="px-4 py-2">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($processingResults['details'] as $detail)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 font-medium text-gray-900">
                                        {{ $detail['resident_id'] }}
                                    </td>
                                    <td class="px-4 py-2">
                                        {{ $detail['name'] }}
                                    </td>
                                    <td class="px-4 py-2">
                                        @if ($detail['status'] === 'success')
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Success
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                Failed
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2">
                                        @if ($detail['status'] === 'success')
                                            Reference: {{ $detail['reference'] }}
                                        @else
                                            {{ $detail['reason'] }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <x-mary-button link="{{ route('distributions.index') }}"
                    class="tagged-color btn-secondary btn-outline btn-secline">
                    Go to Distribution List
                </x-mary-button>
                <x-mary-button wire:click="resetForm" class="tagged-color btn-primary">
                    Create New Batch Distribution
                </x-mary-button>
            </div>
        </x-mary-card>
    @endif
</div>
@push('scripts')
    <script>
        document.addEventListener('livewire:initialized', function() {
            Livewire.on('scrollToTop', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });
    </script>
@endpush
