<div>
    <div class="flex flex-col justify-between gap-4 mb-6 md:flex-row md:items-end">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Distribution Batches</h1>
            <p class="mt-1 text-sm text-gray-600">Manage distribution batches for group aid delivery</p>
        </div>
        <div class="flex space-x-2">
            <x-mary-button link="{{ route('distributions.batches.create') }}" icon="o-plus" label="New Batch" />

            <x-mary-button wire:click="toggleFilters" class="tagged-color btn-secondary btn-outline btn-secline"
                icon="o-funnel">
                {{ $showFilters ? 'Hide Filters' : 'Show Filters' }}
            </x-mary-button>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-6">
        <div class="flex flex-col gap-4 md:flex-row">
            <div class="flex-1">
                <x-mary-input wire:model.live.debounce.300ms="search" label="Search"
                    placeholder="Search by batch number or location..." icon="o-magnifying-glass" />
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
            <div class="p-4 mt-4 border rounded-lg bg-gray-50">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <x-mary-select label="Program" wire:model.live="program" :options="$programs" />
                    </div>
                    <div>
                        <x-mary-select label="Status" wire:model.live="status" :options="[
                            ['key' => 'all', 'id' => 'All Statuses'],
                            ['key' => 'scheduled', 'id' => 'Scheduled'],
                            ['key' => 'ongoing', 'id' => 'Ongoing'],
                            ['key' => 'completed', 'id' => 'Completed'],
                            ['key' => 'cancelled', 'id' => 'Cancelled'],
                        ]" option-value="key"
                            option-label="id" />
                    </div>

                    <div>
                        <x-mary-select label="Sort By" wire:model.live="sortField" :options="[
                            ['key' => 'batch_date', 'id' => 'Batch Date'],
                            ['key' => 'created_at', 'id' => 'Creation Date'],
                            ['key' => 'batch_number', 'id' => 'Batch Number'],
                            ['key' => 'actual_beneficiaries', 'id' => 'Beneficiary Count'],
                            ['key' => 'total_amount', 'id' => 'Total Amount'],
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
    </div>

    <!-- Batches Table -->
    <x-mary-card>
        <div class="overflow-x-auto min-h-[500px]">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                    <tr>
                        <th scope="col" class="px-4 py-3">
                            <div class="flex items-center">
                                Batch #
                                <button wire:click="sortBy('batch_number')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'batch_number')
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
                        <th scope="col" class="px-4 py-3">Location</th>
                        <th scope="col" class="px-4 py-3">
                            <div class="flex items-center">
                                Date
                                <button wire:click="sortBy('batch_date')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'batch_date')
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
                                Beneficiaries
                                <button wire:click="sortBy('actual_beneficiaries')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'actual_beneficiaries')
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
                                Total Amount
                                <button wire:click="sortBy('total_amount')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'total_amount')
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
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th scope="col" class="px-4 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($batches as $batch)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $batch->batch_number }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('programs.show', $batch->ayuda_program_id) }}"
                                    class="text-blue-600 hover:underline">
                                    {{ $batch->ayudaProgram->name }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                {{ $batch->location }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $batch->batch_date->format('M d, Y') }}
                                @if ($batch->start_time && $batch->end_time)
                                    <div class="text-xs text-gray-500">
                                        {{ $batch->start_time->format('h:i A') }} -
                                        {{ $batch->end_time->format('h:i A') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                {{ $batch->actual_beneficiaries }}
                                @if ($batch->target_beneficiaries)
                                    <div class="text-xs text-gray-500">
                                        out of {{ $batch->target_beneficiaries }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                ₱{{ number_format($batch->total_amount, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $batch->status === 'completed'
                                        ? 'bg-green-100 text-green-800'
                                        : ($batch->status === 'ongoing'
                                            ? 'bg-blue-100 text-blue-800'
                                            : ($batch->status === 'scheduled'
                                                ? 'bg-yellow-100 text-yellow-800'
                                                : 'bg-red-100 text-red-800')) }}">
                                    {{ ucfirst($batch->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <x-mary-button link="{{ route('distributions.batches.show', $batch->id) }}"
                                        icon="o-eye" size="xs" class="tagged-color btn-primary" />

                                    <x-mary-dropdown>
                                        <x-slot name="trigger">
                                            <x-mary-button icon="o-ellipsis-vertical" size="xs"
                                                class="tagged-color btn-ghost btn-outline" />
                                        </x-slot>

                                        <div class="px-4 py-2 text-xs text-gray-400">Actions</div>

                                        <x-mary-menu-item
                                            href="{{ route('distributions.batches.edit', $batch->id) }}">
                                            Edit Batch
                                        </x-mary-menu-item>

                                        @if ($batch->status === 'scheduled')
                                            <x-mary-menu-item
                                                wire:click="updateStatus({{ $batch->id }}, 'ongoing')">
                                                Mark as Ongoing
                                            </x-mary-menu-item>
                                        @endif

                                        @if ($batch->status === 'ongoing' || $batch->status === 'scheduled')
                                            <x-mary-menu-item
                                                wire:click="updateStatus({{ $batch->id }}, 'completed')">
                                                Mark as Completed
                                            </x-mary-menu-item>

                                            <x-mary-menu-item
                                                wire:click="updateStatus({{ $batch->id }}, 'cancelled')">
                                                Mark as Cancelled
                                            </x-mary-menu-item>
                                        @endif
                                    </x-mary-dropdown>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    @if ($batches->count() === 0)
                        <tr class="bg-white border-b">
                            <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2 text-gray-400"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    <p class="mb-1 text-gray-500">No distribution batches found</p>
                                    @if (!empty($search) || !empty($program) || $status !== 'all' || !empty($dateFrom) || !empty($dateTo))
                                        <p class="text-sm text-gray-400">Try adjusting your filters</p>
                                        <x-mary-button wire:click="resetFilters"
                                            class="tagged-color btn-secondary btn-outline btn-secline" size="sm"
                                            class="mt-3">
                                            Reset Filters
                                        </x-mary-button>
                                    @else
                                        <x-mary-button link="{{ route('distributions.batches.create') }}"
                                            class="tagged-color btn-primary" size="sm" class="mt-3"
                                            icon="o-plus">
                                            Create Batch
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
            {{ $batches->links() }}
        </div>
    </x-mary-card>
</div>
