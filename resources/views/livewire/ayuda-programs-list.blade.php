<div>
    <div class="flex flex-col justify-between gap-4 mb-6 md:flex-row md:items-end">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Ayuda Programs</h1>
            <p class="mt-1 text-sm text-gray-600">Manage aid programs and eligibility criteria</p>
        </div>
        <div class="flex space-x-2">
            <x-mary-button link="{{ route('programs.create') }}" icon="o-plus" label="New Program" class="btn-primary" />

            <x-mary-button wire:click="toggleFilters" class="tagged-color" icon="o-funnel">
                {{ $showFilters ? 'Hide Filters' : 'Show Filters' }}
            </x-mary-button>
            <x-mary-button link="{{ route('distributions.barangay-batch') }}" icon="o-users" class="tagged-color "
                label="Barangay Batch" />
            <x-mary-button link="{{ route('distributions.batch-verification') }}" icon="o-check-circle"
                class="tagged-color " label="Batch Verification" />
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-6">
        <div class="flex flex-col gap-4 md:flex-row">
            <div class="flex-1">
                <x-mary-input wire:model.live.debounce.300ms="search"
                    placeholder="Search by name, code, or description..." label="Search" icon="o-magnifying-glass" />
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
                        <x-mary-select label="Program Type" wire:model.live="type" :options="[
                            ['key' => '', 'id' => 'All Types'],
                            ['key' => 'cash', 'id' => 'Cash'],
                            ['key' => 'goods', 'id' => 'Goods'],
                            ['key' => 'services', 'id' => 'Services'],
                            ['key' => 'mixed', 'id' => 'Mixed'],
                        ]" option-value="key"
                            option-label="id" />
                    </div>

                    <div>
                        <x-mary-select label="Status" wire:model.live="status" :options="[
                            ['key' => 'all', 'id' => 'All Statuses'],
                            ['key' => 'active', 'id' => 'Active'],
                            ['key' => 'inactive', 'id' => 'Inactive'],
                            ['key' => 'upcoming', 'id' => 'Upcoming'],
                            ['key' => 'completed', 'id' => 'Completed'],
                        ]" option-value="key"
                            option-label="id" />
                    </div>

                    <div>
                        <x-mary-select label="Sort By" wire:model.live="sortField" :options="[
                            ['key' => 'created_at', 'id' => 'Creation Date'],
                            ['key' => 'name', 'id' => 'Program Name'],
                            ['key' => 'start_date', 'id' => 'Start Date'],
                            ['key' => 'end_date', 'id' => 'End Date'],
                            ['key' => 'budget_used', 'id' => 'Budget Used'],
                            ['key' => 'current_beneficiaries', 'id' => 'Beneficiary Count'],
                        ]" option-value="key"
                            option-label="id" />
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

    <!-- Programs List -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @foreach ($programs as $program)
            <x-mary-card>
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-lg font-medium">{{ $program->name }}</h2>
                        <div class="flex items-center mt-1 space-x-2">
                            <span
                                class="px-2 py-1 text-xs text-gray-800 rounded bg-base-100">{{ $program->code }}</span>
                            {{-- <span
                                class="text-xs font-medium py-1 px-2 rounded
                                @if ($program->status['color'] === 'green') bg-green-100 text-green-800
                                @elseif($program->status['color'] === 'red') bg-red-100 text-red-800
                                @elseif($program->status['color'] === 'blue') bg-blue-100 text-blue-800
                                @elseif($program->status['color'] === 'yellow') bg-yellow-100 text-yellow-800
                                @elseif($program->status['color'] === 'amber') bg-amber-100 text-amber-800
                                @else bg-base-100 text-gray-800 @endif
                            ">
                                {{ $program->status['label'] }}
                            </span> --}}
                            <span class="px-2 py-1 text-xs text-purple-800 bg-purple-100 rounded">
                                {{ ucfirst($program->type) }}
                            </span>
                        </div>
                    </div>
                    <div class="flex space-x-1">
                        <x-mary-button link="{{ route('programs.show', $program->id) }}" icon="o-eye" size="xs"
                            class="btn-primary" tooltip="View Program" />
                        <x-mary-button link="{{ route('programs.edit', $program->id) }}" icon="o-pencil" size="xs"
                            class="btn-secondary btn-outline btn-secline" tooltip="Edit Program" />
                        <x-mary-button
                            wire:click="setProgramStatus({{ $program->id }}, '{{ $program->is_active ? 'inactive' : 'active' }}')"
                            icon="o-{{ $program->is_active ? 'x-circle' : 'check-circle' }}" size="xs"
                            class="{{ $program->is_active ? 'btn-error' : 'btn-success' }}"
                            tooltip="Toggle Active/Inactive" />
                    </div>
                </div>

                <div class="mt-3 text-sm text-gray-500">
                    {{ Str::limit($program->description, 120) }}
                </div>

                <div class="grid grid-cols-2 gap-4 mt-4">
                    <div>
                        <h3 class="mb-1 text-xs font-medium text-gray-500">Period</h3>
                        <p class="text-sm">
                            {{ $program->start_date->format('M d, Y') }}
                            @if ($program->end_date)
                                - {{ $program->end_date->format('M d, Y') }}
                            @else
                                - Ongoing
                            @endif
                        </p>
                    </div>
                    <div>
                        <h3 class="mb-1 text-xs font-medium text-gray-500">Frequency</h3>
                        <p class="text-sm">{{ ucfirst(str_replace('_', ' ', $program->frequency)) }}</p>
                    </div>
                </div>

                <div class="mt-2">
                    <h3 class="mb-1 text-xs font-medium text-gray-500">Amount</h3>
                    <p class="text-sm">
                        @if ($program->amount)
                            ₱{{ number_format($program->amount, 2) }} per beneficiary
                        @else
                            {{ ucfirst($program->type) }} aid
                        @endif
                    </p>
                </div>

                @if ($program->total_budget)
                    <div class="mt-3">
                        <div class="flex justify-between mb-1 text-xs">
                            <span>Budget
                                (₱{{ number_format($program->budget_used, 2) }}/{{ number_format($program->total_budget, 2) }})
                            </span>
                            <span>{{ $program->budget_percent }}%</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-base-200">
                            <div class="h-2 bg-blue-600 rounded-full" style="width: {{ $program->budget_percent }}%">
                            </div>
                        </div>
                    </div>
                @endif

                @if ($program->max_beneficiaries)
                    <div class="mt-3">
                        <div class="flex justify-between mb-1 text-xs">
                            <span>Beneficiaries
                                ({{ $program->current_beneficiaries }}/{{ $program->max_beneficiaries }})</span>
                            <span>{{ $program->beneficiary_percent }}%</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-base-200">
                            <div class="h-2 bg-green-600 rounded-full"
                                style="width: {{ $program->beneficiary_percent }}%"></div>
                        </div>
                    </div>
                @endif

                <div class="flex justify-between pt-4 mt-4 border-t">
                    <x-mary-button link="{{ route('distributions.create') }}?program={{ $program->id }}"
                        class="tagged-color btn-primary" size="sm">
                        Distribute Aid
                    </x-mary-button>
                    {{-- <x-mary-button link="{{ route('distributions.batches.create') }}?program={{ $program->id }}"
                        class="tagged-color btn-secondary btn-outline btn-secline" size="sm">
                        Create Batch
                    </x-mary-button> --}}
                </div>
            </x-mary-card>
        @endforeach
    </div>

    <!-- Empty State -->
    @if ($programs->count() === 0)
        <div class="p-8 text-center border rounded-lg bg-base">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none"
                viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            <h3 class="mb-1 text-lg font-medium text-gray-900">No programs found</h3>
            <p class="mb-4 text-gray-500">
                @if (!empty($search) || !empty($type) || $status !== 'active')
                    Try adjusting your filters
                @else
                    Create an ayuda program to start distributing aid
                @endif
            </p>
            @if (!empty($search) || !empty($type) || $status !== 'active')
                <x-mary-button wire:click="resetFilters" class="tagged-color btn-secondary btn-outline btn-secline">
                    Reset Filters
                </x-mary-button>
            @else
                <x-mary-button link="{{ route('programs.create') }}" class="tagged-color btn-primary"
                    icon="o-plus">
                    Create Program
                </x-mary-button>
            @endif
        </div>
    @endif

    <!-- Pagination -->
    @if ($programs->hasPages())
        <div class="mt-6">
            {{ $programs->links() }}
        </div>
    @endif
</div>
