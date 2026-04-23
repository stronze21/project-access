<div>
    <div class="mb-6">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ $batch->batch_number }}</h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $batch->ayudaProgram->name }} - {{ $batch->batch_date->format('M d, Y') }}
                </p>
            </div>
            <div class="flex space-x-2">
                @if ($batch->status === 'scheduled' || $batch->status === 'ongoing')
                    <x-mary-button icon="o-pencil" class="tagged-color btn-info"
                        link="{{ route('distributions.batches.edit', $batch->id) }}">
                        Edit
                    </x-mary-button>
                @endif

                <x-mary-button icon="o-document-arrow-down" class="tagged-color btn-secondary btn-outline btn-secline"
                    wire:click="exportBatchData">
                    Export
                </x-mary-button>

                <x-mary-button link="{{ route('distributions.batch', $batch->id) }}" class="tagged-color btn-primary">
                    Add Distributions
                </x-mary-button>
            </div>
        </div>
    </div>

    <!-- Batch Information -->
    <div class="mb-6">
        <x-mary-card>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">Batch Information</h2>
                        <div>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $batch->status === 'completed'
                                    ? 'bg-green-100 text-green-800'
                                    : ($batch->status === 'ongoing'
                                        ? 'bg-blue-100 text-blue-800'
                                        : ($batch->status === 'scheduled'
                                            ? 'bg-purple-100 text-purple-800'
                                            : 'bg-red-100 text-red-800')) }}">
                                {{ ucfirst($batch->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-y-2">
                        <div class="text-sm font-medium text-gray-500">Program:</div>
                        <div class="text-sm text-gray-900">{{ $batch->ayudaProgram->name }}</div>

                        <div class="text-sm font-medium text-gray-500">Location:</div>
                        <div class="text-sm text-gray-900">{{ $batch->location }}</div>

                        <div class="text-sm font-medium text-gray-500">Date:</div>
                        <div class="text-sm text-gray-900">{{ $batch->batch_date->format('M d, Y') }}</div>

                        <div class="text-sm font-medium text-gray-500">Time:</div>
                        <div class="text-sm text-gray-900">{{ $batch->start_time->format('h:i A') }} -
                            {{ $batch->end_time->format('h:i A') }}</div>

                        <div class="text-sm font-medium text-gray-500">Created By:</div>
                        <div class="text-sm text-gray-900">{{ $batch->creator->name ?? 'N/A' }}</div>

                        <div class="text-sm font-medium text-gray-500">Last Updated:</div>
                        <div class="text-sm text-gray-900">{{ $batch->updated_at->format('M d, Y h:i A') }}</div>

                        @if ($batch->notes)
                            <div class="col-span-2 mt-2 text-sm font-medium text-gray-500">Notes:</div>
                            <div class="col-span-2 text-sm text-gray-900">{{ $batch->notes }}</div>
                        @endif
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-medium text-gray-900">Distribution Progress</h2>
                        <button wire:click="toggleStats" class="text-sm text-blue-600 hover:text-blue-800">
                            {{ $showStats ? 'Hide Stats' : 'Show Stats' }}
                        </button>
                    </div>

                    @if ($showStats)
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between mb-1 text-sm">
                                    <span>Beneficiaries
                                        ({{ $batch->actual_beneficiaries }}/{{ $batch->target_beneficiaries }})</span>
                                    <span>{{ $batch->completion_percentage }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-blue-600 h-2.5 rounded-full"
                                        style="width: {{ $batch->completion_percentage }}%"></div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-y-2">
                                <div class="text-sm font-medium text-gray-500">Target Beneficiaries:</div>
                                <div class="text-sm text-gray-900">{{ number_format($batch->target_beneficiaries) }}
                                </div>

                                <div class="text-sm font-medium text-gray-500">Actual Beneficiaries:</div>
                                <div class="text-sm text-gray-900">{{ number_format($batch->actual_beneficiaries) }}
                                </div>

                                <div class="text-sm font-medium text-gray-500">Total Amount:</div>
                                <div class="text-sm text-gray-900">₱{{ number_format($batch->total_amount, 2) }}</div>

                                <div class="text-sm font-medium text-gray-500">Amount per Beneficiary:</div>
                                <div class="text-sm text-gray-900">
                                    ₱{{ number_format($batch->ayudaProgram->amount, 2) }}</div>
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-between mt-4 space-x-2">
                        @if ($batch->status === 'scheduled')
                            <x-mary-button wire:click="updateBatchStatus('ongoing')" class="tagged-color btn-success"
                                size="sm">
                                Start Distribution
                            </x-mary-button>

                            <x-mary-button wire:click="confirmCancelBatch" class="tagged-color btn-error"
                                size="sm">
                                Cancel Batch
                            </x-mary-button>
                        @elseif($batch->status === 'ongoing')
                            <x-mary-button wire:click="confirmCompleteBatch" class="tagged-color btn-success"
                                size="sm">
                                Complete Batch
                            </x-mary-button>

                            <x-mary-button wire:click="confirmCancelBatch" class="tagged-color btn-error"
                                size="sm">
                                Cancel Batch
                            </x-mary-button>
                        @else
                            <div></div>
                            <div></div>
                        @endif
                    </div>
                </div>
            </div>
        </x-mary-card>
    </div>

    <!-- Distributions List -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-gray-900">Distributions</h2>

            <div class="flex items-center space-x-4">
                <!-- Search Box -->
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="searchQuery" placeholder="Search..."
                        class="block w-64 border-gray-300 rounded-md shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" />
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <!-- Status Filter -->
                <x-mary-select wire:model.live="statusFilter" :options="[
                    ['key' => 'all', 'id' => 'All Status'],
                    ['key' => 'pending', 'id' => 'Pending'],
                    ['key' => 'distributed', 'id' => 'Distributed'],
                    ['key' => 'verified', 'id' => 'Verified'],
                    ['key' => 'cancelled', 'id' => 'Cancelled'],
                ]" option-value="key" option-label="id"
                    size="sm" />


                @if ($batch->status === 'ongoing' || $batch->status === 'scheduled')
                    <x-mary-button wire:click="processAllDistributions" class="tagged-color btn-success"
                        wire:loading.attr="disabled" wire:loading.class="opacity-75">
                        <span wire:loading.remove wire:target="processAllDistributions">Process All Pending</span>
                        <span wire:loading wire:target="processAllDistributions">Processing...</span>
                    </x-mary-button>
                @endif
            </div>
        </div>

        <x-mary-card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Reference #
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Beneficiary
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Household
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Amount
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Status
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($distributions as $distribution)
                            <tr>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900 whitespace-nowrap">
                                    <a href="{{ route('distributions.show', $distribution->id) }}"
                                        class="text-blue-600 hover:text-blue-900">
                                        {{ $distribution->reference_number }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                    <a href="{{ route('residents.show', $distribution->resident_id) }}"
                                        class="text-blue-600 hover:text-blue-900">
                                        {{ $distribution->resident->full_name }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                    @if ($distribution->household_id)
                                        <a href="{{ route('households.show', $distribution->household_id) }}"
                                            class="text-blue-600 hover:text-blue-900">
                                            {{ $distribution->household->household_id }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                    ₱{{ number_format($distribution->amount, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $distribution->status === 'distributed'
                                            ? 'bg-green-100 text-green-800'
                                            : ($distribution->status === 'pending'
                                                ? 'bg-yellow-100 text-yellow-800'
                                                : ($distribution->status === 'verified'
                                                    ? 'bg-blue-100 text-blue-800'
                                                    : 'bg-red-100 text-red-800')) }}">
                                        {{ ucfirst($distribution->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('distributions.show', $distribution->id) }}"
                                            class="text-blue-600 hover:text-blue-900">
                                            View
                                        </a>

                                        @if ($batch->status === 'ongoing' || $batch->status === 'scheduled')
                                            @if ($distribution->status === 'pending')
                                                <button
                                                    wire:click="processDistribution({{ $distribution->id }}, 'distributed')"
                                                    class="text-green-600 hover:text-green-900">
                                                    Process
                                                </button>

                                                <button
                                                    wire:click="processDistribution({{ $distribution->id }}, 'cancelled')"
                                                    class="text-red-600 hover:text-red-900">
                                                    Cancel
                                                </button>
                                            @elseif($distribution->status === 'distributed')
                                                <button
                                                    wire:click="processDistribution({{ $distribution->id }}, 'pending')"
                                                    class="text-yellow-600 hover:text-yellow-900">
                                                    Revert
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-sm text-center text-gray-500">
                                    No distributions found for this batch.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $distributions->links() }}
            </div>
        </x-mary-card>
    </div>

    <!-- Confirmation Modals -->
    <x-mary-modal wire:model="showCompleteBatchModal" title="Complete Batch">
        <p class="mb-4">Are you sure you want to mark this batch as completed? This will finalize all distributions
            and update program statistics.</p>

        <x-slot:actions>
            <x-mary-button class="tagged-color btn-secondary btn-outline btn-secline"
                wire:click="$set('showCompleteBatchModal', false)">Cancel</x-mary-button>
            <x-mary-button class="tagged-color btn-success" wire:click="updateBatchStatus('completed')">Complete
                Batch</x-mary-button>
        </x-slot:actions>
    </x-mary-modal>

    <x-mary-modal wire:model="showCancelBatchModal" title="Cancel Batch">
        <p class="mb-4">Are you sure you want to cancel this batch? This will mark the batch as cancelled and prevent
            any further distributions.</p>

        <x-slot:actions>
            <x-mary-button class="tagged-color btn-secondary btn-outline btn-secline"
                wire:click="$set('showCancelBatchModal', false)">No,
                Keep
                It</x-mary-button>
            <x-mary-button class="tagged-color btn-error" wire:click="updateBatchStatus('cancelled')">Yes, Cancel
                Batch</x-mary-button>
        </x-slot:actions>
    </x-mary-modal>
</div>
