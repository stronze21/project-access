<div>
    <div class="flex items-center justify-between mb-3 cursor-pointer" wire:click="toggleExpanded">
        <h3 class="text-sm font-medium text-gray-500 dark:text-slate-300">Filter Options</h3>
        <button class="text-gray-400 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-200">
            @if ($expanded)
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                </svg>
            @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            @endif
        </button>
    </div>

    @if ($expanded)
        <div class="space-y-4">
            <!-- Date Range Shortcuts -->
            <div class="flex flex-wrap gap-2 mb-3">
                @foreach ($dateRangeOptions as $key => $label)
                    <button type="button"
                        class="px-3 py-1 text-xs font-medium {{ $selectedDateRange === $key ? 'bg-blue-100 text-blue-700 dark:bg-cyan-900/60 dark:text-cyan-100' : 'bg-gray-100 text-gray-700 dark:bg-slate-800 dark:text-slate-200' }} rounded-md hover:bg-blue-50 dark:hover:bg-cyan-950/45"
                        wire:click="applyDateRange('{{ $key }}')">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <x-mary-datetime label="From Date" wire:model.live="dateFrom" />
                        <x-mary-datetime label="To Date" wire:model.live="dateTo" />
                    </div>

                    <div>
                        <x-mary-select label="Program" wire:model.live="program" :options="$programs"
                            placeholder="All programs" placeholder-value="" />
                    </div>
                </div>

                <div class="space-y-4">
                    <!-- Location selector will be included here -->
                    <livewire:address-selector />

                    <div>
                        <x-mary-select label="Status" wire:model.live="status" :options="[
                            ['key' => 'distributed', 'id' => 'Distributed'],
                            ['key' => 'pending', 'id' => 'Pending'],
                            ['key' => 'verified', 'id' => 'Verified'],
                            ['key' => 'all', 'id' => 'All Statuses'],
                        ]" option-value="key"
                            option-label="id" />
                    </div>

                    <div class="flex justify-end">
                        <x-mary-button class="tagged-color btn-secondary btn-outline btn-secline" size="sm"
                            wire:click="resetFilters" icon="o-arrow-path">
                            Reset Filters
                        </x-mary-button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
