<div>
    <div class="flex flex-col justify-between gap-4 mb-6 md:flex-row md:items-end">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Aid Distributions</h1>
            <p class="mt-1 text-sm text-gray-600">Track and manage aid distributions</p>
        </div>
        <div class="flex space-x-2">
            <x-mary-button link="{{ route('distributions.create') }}" icon="o-plus" label="New Distribution" />

            <x-mary-button wire:click="toggleFilters" class="tagged-color" icon="o-funnel">
                {{ $showFilters ? 'Hide Filters' : 'Show Filters' }}
            </x-mary-button>

            <x-mary-button wire:click="$toggle('showQrScanner')" class="tagged-color" icon="o-qr-code">
                {{ $showQrScanner ? 'Hide Scanner' : 'Scan QR/RFID' }}
            </x-mary-button>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-6">
        <div class="flex flex-col gap-4 md:flex-row">
            <div class="flex-1">
                <x-mary-input wire:model.live.debounce.300ms="search" label="Search"
                    placeholder="Search by reference number, resident, or household..." icon="o-magnifying-glass" />
            </div>
            <div>
                <x-mary-select label="Items per page" :options="[
                    ['key' => '10', 'id' => '10 per page'],
                    ['key' => '25', 'id' => '25 per page'],
                    ['key' => '50', 'id' => '50 per page'],
                    ['key' => '100', 'id' => '100 per page'],
                ]" option-value="key" option-label="id"
                    placeholder="Select items per page" placeholder-value="" wire:model="perPage" />

            </div>
        </div>

        <!-- Filters -->
        @if ($showFilters)
            <div class="p-4 mt-4 border rounded-lg bg-base-50">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <x-mary-select label="Program" wire:model.live="program" :options="$programs"
                            placeholder="All Programs" placeholder-value="" />
                    </div>
                    <div>
                        <x-mary-select label="Status" wire:model.live="status" :options="[
                            ['key' => 'all', 'id' => 'All Statuses'],
                            ['key' => 'distributed', 'id' => 'Distributed'],
                            ['key' => 'pending', 'id' => 'Pending'],
                            ['key' => 'verified', 'id' => 'Verified'],
                            ['key' => 'rejected', 'id' => 'Rejected'],
                            ['key' => 'cancelled', 'id' => 'Cancelled'],
                        ]" option-value="key"
                            option-label="id" />
                    </div>

                    <div>
                        <x-mary-select label="Sort By" wire:model.live="sortField" :options="[
                            ['key' => 'distribution_date', 'id' => 'Distribution Date'],
                            ['key' => 'created_at', 'id' => 'Creation Date'],
                            ['key' => 'reference_number', 'id' => 'Reference Number'],
                            ['key' => 'amount', 'id' => 'Amount'],
                        ]" option-value="key"
                            option-label="id" />
                    </div>

                </div>

                <div class="grid grid-cols-1 gap-4 mt-4 md:grid-cols-2">
                    <div>
                        <x-mary-datetime label="From Date" wire:model.live="dateFrom" />
                    </div>
                    <div>
                        <x-mary-datetime label="To Date" wire:model.live="dateTo" />
                    </div>
                </div>

                <div class="flex justify-end mt-4">
                    <x-mary-button wire:click="resetFilters" class="tagged-color btn-secondary btn-outline btn-secline"
                        size="sm">Reset
                        Filters</x-mary-button>
                </div>
            </div>
        @endif

        <!-- QR Scanner -->
        @if ($showQrScanner)
            <div class="mt-4">
                <livewire:qr-rfid-scanner />
            </div>
        @endif
    </div>

    <!-- Distributions Table -->
    <x-mary-card>
        <div class="overflow-x-auto min-h-[500px]">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-base-50">
                    <tr>
                        <th scope="col" class="px-4 py-3">
                            <div class="flex items-center">
                                Reference #
                                <button wire:click="sortBy('reference_number')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'reference_number')
                                            @if ($sortDirection === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 15l7-7 7 7" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            @endif
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16V4m0 0L3 8m4-4l4 4" />
                                        @endif
                                    </svg>
                                </button>
                            </div>
                        </th>
                        <th scope="col" class="px-4 py-3">Program</th>
                        <th scope="col" class="px-4 py-3">Beneficiary</th>
                        <th scope="col" class="px-4 py-3">
                            <div class="flex items-center">
                                Date
                                <button wire:click="sortBy('distribution_date')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'distribution_date')
                                            @if ($sortDirection === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 15l7-7 7 7" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            @endif
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16V4m0 0L3 8m4-4l4 4" />
                                        @endif
                                    </svg>
                                </button>
                            </div>
                        </th>
                        <th scope="col" class="px-4 py-3">
                            <div class="flex items-center">
                                Amount
                                <button wire:click="sortBy('amount')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'amount')
                                            @if ($sortDirection === 'asc')
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 15l7-7 7 7" />
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7" />
                                            @endif
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 16V4m0 0L3 8m4-4l4 4" />
                                        @endif
                                    </svg>
                                </button>
                            </div>
                        </th>
                        <th scope="col" class="px-4 py-3">Batch</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th scope="col" class="px-4 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($distributions as $distribution)
                        <tr class="border-b bg-base hover:bg-base-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $distribution->reference_number }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('programs.show', $distribution->ayuda_program_id) }}"
                                    class="text-blue-600 hover:underline">
                                    {{ $distribution->ayudaProgram->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('residents.show', $distribution->resident_id) }}"
                                    class="text-blue-600 hover:underline">
                                    {{ $distribution->resident->full_name }}
                                </a>
                                @if ($distribution->household)
                                    <div class="text-xs text-gray-500">
                                        <a href="{{ route('households.show', $distribution->household_id) }}"
                                            class="hover:underline">
                                            {{ $distribution->household->household_id }}
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $distribution->created_at->format('M d, Y g:i A') }}
                            </td>
                            <td class="px-4 py-3">
                                ₱{{ number_format($distribution->amount, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($distribution->batch)
                                    <a href="{{ route('distributions.batches.show', $distribution->batch_id) }}"
                                        class="text-xs text-blue-600 hover:underline">
                                        {{ $distribution->batch->batch_number }}
                                    </a>
                                @else
                                    <span class="text-xs text-gray-500">Individual</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
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
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <x-mary-button link="{{ route('distributions.show', $distribution->id) }}"
                                        icon="o-eye" size="xs" class="tagged-color btn-primary" />

                                    @if ($distribution->status === 'pending')
                                        <x-mary-dropdown>
                                            <x-slot name="trigger">
                                                <x-mary-button icon="o-ellipsis-vertical" size="xs"
                                                    class="tagged-color btn-ghost btn-outline" />
                                            </x-slot>

                                            <div class="px-4 py-2 text-xs text-gray-400">Actions</div>

                                            <x-mary-menu-item
                                                wire:click="updateStatus({{ $distribution->id }}, 'verified')">
                                                Mark as Verified
                                            </x-mary-menu-item>

                                            <x-mary-menu-item
                                                wire:click="updateStatus({{ $distribution->id }}, 'distributed')">
                                                Mark as Distributed
                                            </x-mary-menu-item>

                                            <x-mary-menu-item
                                                wire:click="updateStatus({{ $distribution->id }}, 'rejected')">
                                                Mark as Rejected
                                            </x-mary-menu-item>

                                            <x-mary-menu-item
                                                wire:click="updateStatus({{ $distribution->id }}, 'cancelled')">
                                                Mark as Cancelled
                                            </x-mary-menu-item>
                                        </x-mary-dropdown>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    @if ($distributions->count() === 0)
                        <tr class="border-b bg-base">
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2 text-gray-400"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                    <p class="mb-1 text-gray-500">No distributions found</p>
                                    @if (!empty($search) || !empty($program) || $status !== 'all' || !empty($dateFrom) || !empty($dateTo))
                                        <p class="text-sm text-gray-400">Try adjusting your filters</p>
                                        <x-mary-button wire:click="resetFilters" class="tagged-color" size="sm"
                                            class="mt-3">
                                            Reset Filters
                                        </x-mary-button>
                                    @else
                                        <x-mary-button link="{{ route('distributions.create') }}"
                                            class="tagged-color btn-primary" size="sm" class="mt-3"
                                            icon="o-plus">
                                            Create Distribution
                                        </x-mary-button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t">
            {{ $distributions->links() }}
        </div>
    </x-mary-card>
</div>
