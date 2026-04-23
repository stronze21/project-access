<div>
    <div class="flex flex-col justify-between gap-4 mb-6 md:flex-row md:items-end">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Households</h1>
            <p class="mt-1 text-sm text-gray-600">Manage household information and members</p>
        </div>
        <div class="flex space-x-2">
            <x-mary-button link="{{ route('households.create') }}" icon="o-plus" label="New Household"
                class="btn-primary" />

            <x-mary-button wire:click="toggleFilters" class="tagged-color" icon="o-funnel">
                {{ $showFilters ? 'Hide Filters' : 'Show Filters' }}
            </x-mary-button>

            <x-mary-button wire:click="$toggle('showQrScanner')" class="tagged-color" icon="o-qr-code">
                {{ $showQrScanner ? 'Hide Scanner' : 'Scan QR' }}
            </x-mary-button>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="mb-6">
        <div class="flex flex-col gap-4 md:flex-row">
            <div class="flex-1">
                <x-mary-input wire:model.live.debounce.300ms="search" label="Search"
                    placeholder="Search by ID, address, barangay..." icon="o-magnifying-glass" />
            </div>
            <div>
                <x-mary-select label="Items per page" :options="[
                    ['key' => '10', 'id' => '10 per page'],
                    ['key' => '25', 'id' => '25 per page'],
                    ['key' => '50', 'id' => '50 per page'],
                    ['key' => '100', 'id' => '100 per page'],
                ]" option-value="key" option-label="id"
                    placeholder="Select items per page" placeholder-value="" wire:model.live="perPage" />

            </div>
        </div>

        <!-- Filters -->
        @if ($showFilters)
            <div class="p-4 mt-4 border rounded-lg bg-base-50">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-sm font-medium">Filter Households</h3>
                    <x-mary-button wire:click="toggleAdvancedFilters"
                        class="tagged-color btn-secondary btn-outline btn-secline" size="xs">
                        {{ $showAdvancedFilters ? 'Basic Filters' : 'Advanced Filters (PSGC)' }}
                    </x-mary-button>
                </div>

                @if (!$showAdvancedFilters)
                    <!-- Basic Filters -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <x-mary-select label="Barangay" wire:model.live="barangay" placeholder="All Barangays"
                                :options="collect($barangayList)->map(fn($b) => ['key' => $b, 'id' => $b])->toArray()" option-value="key" option-label="id" class="w-48" />
                        </div>
                        <div class="flex space-x-4">
                            <x-mary-select label="Status" wire:model.live="status" :options="[
                                ['key' => 'all', 'id' => 'All Statuses'],
                                ['key' => 'active', 'id' => 'Active'],
                                ['key' => 'inactive', 'id' => 'Inactive'],
                            ]" option-value="key"
                                option-label="id" />

                            <x-mary-select label="Sort By" wire:model.live="sortField" :options="[
                                ['key' => 'created_at', 'id' => 'Registration Date'],
                                ['key' => 'household_id', 'id' => 'Household ID'],
                                ['key' => 'barangay', 'id' => 'Barangay'],
                                ['key' => 'city_municipality', 'id' => 'City/Municipality'],
                            ]"
                                option-value="key" option-label="id" />
                        </div>
                    </div>
                @else
                    <!-- PSGC Advanced Filters -->
                    <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-2">
                        <!-- Region -->
                        <div>
                            <x-mary-select label="Region" wire:model.live="regionCode" placeholder="All Regions"
                                :options="$regions
                                    ->map(fn($region) => ['key' => $region->regCode, 'id' => $region->regDesc])
                                    ->toArray()" option-value="key" option-label="id" />
                        </div>

                        <!-- Province -->
                        <div>
                            <x-mary-select label="Province" wire:model.live="provinceCode" placeholder="All Provinces"
                                :options="$provinces
                                    ->map(fn($province) => ['key' => $province->provCode, 'id' => $province->provDesc])
                                    ->toArray()" option-value="key" option-label="id" :disabled="empty($provinces)" />
                        </div>

                        <!-- City/Municipality -->
                        <div>
                            <x-mary-select label="City/Municipality" wire:model.live="cityCode"
                                placeholder="All Cities/Municipalities" :options="$cities
                                    ->map(fn($city) => ['key' => $city->citymunCode, 'id' => $city->citymunDesc])
                                    ->toArray()" option-value="key"
                                option-label="id" :disabled="empty($cities)" />
                        </div>

                        <!-- Barangay -->
                        <div>
                            <x-mary-select label="Barangay" wire:model.live="barangayCode" placeholder="All Barangays"
                                :options="$barangays
                                    ->map(fn($barangay) => ['key' => $barangay->brgyCode, 'id' => $barangay->brgyDesc])
                                    ->toArray()" option-value="key" option-label="id" :disabled="empty($barangays)" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <x-mary-select label="Status" wire:model.live="status" :options="[
                            ['key' => 'all', 'id' => 'All Statuses'],
                            ['key' => 'active', 'id' => 'Active'],
                            ['key' => 'inactive', 'id' => 'Inactive'],
                        ]" option-value="key"
                            option-label="id" />

                        <x-mary-select label="Sort By" wire:model.live="sortField" :options="[
                            ['key' => 'created_at', 'id' => 'Registration Date'],
                            ['key' => 'household_id', 'id' => 'Household ID'],
                            ['key' => 'barangay', 'id' => 'Barangay'],
                            ['key' => 'city_municipality', 'id' => 'City/Municipality'],
                        ]" option-value="key"
                            option-label="id" />
                    </div>
                @endif

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

    <!-- Households Table -->
    <x-mary-card>
        <div class="overflow-x-auto min-h-[500px]">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-base-50">
                    <tr>
                        <th scope="col" class="px-4 py-3">
                            <div class="flex items-center">
                                ID
                                <button wire:click="sortBy('household_id')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'household_id')
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
                                Address
                                <button wire:click="sortBy('address')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'address')
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
                                Barangay
                                <button wire:click="sortBy('barangay')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'barangay')
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
                                Members
                                <button wire:click="sortBy('member_count')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'member_count')
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
                        <th scope="col" class="px-4 py-3">Monthly Income</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th scope="col" class="px-4 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($households as $household)
                        <tr class="border-b bg-base hover:bg-base-50">
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $household->household_id }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $household->address }}
                                <div class="text-xs text-gray-500">{{ $household->city_municipality }},
                                    {{ $household->province }}</div>
                            </td>
                            <td class="px-4 py-3">
                                {{ $household->barangay }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $household->residents_count }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $household->monthly_income ? '₱ ' . number_format($household->monthly_income, 2) : 'N/A' }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $household->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $household->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <x-mary-button link="{{ route('households.show', $household->id) }}"
                                        icon="o-eye" size="xs" class="tagged-color btn-primary" />
                                    <x-mary-button link="{{ route('households.edit', $household->id) }}"
                                        icon="o-pencil" size="xs"
                                        class="tagged-color btn-secondary btn-outline btn-secline" />
                                    <x-mary-dropdown>
                                        <x-slot name="trigger">
                                            <x-mary-button icon="o-ellipsis-vertical" size="xs"
                                                class="tagged-color btn-ghost btn-outline" />
                                        </x-slot>

                                        <div class="px-4 py-2 text-xs text-gray-400">Actions</div>

                                        <x-mary-menu-item href="{{ route('qrcode.household', $household->id) }}"
                                            target="_blank">
                                            View QR Code
                                        </x-mary-menu-item>

                                        <x-mary-menu-item
                                            href="{{ route('qrcode.download.household', $household->id) }}">
                                            Download QR Code
                                        </x-mary-menu-item>

                                        <x-mary-menu-item
                                            wire:click="setHouseholdStatus({{ $household->id }}, '{{ $household->is_active ? 'inactive' : 'active' }}')">
                                            Mark as {{ $household->is_active ? 'Inactive' : 'Active' }}
                                        </x-mary-menu-item>
                                    </x-mary-dropdown>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    @if ($households->count() === 0)
                        <tr class="border-b bg-base">
                            <td colspan="7" class="px-4 py-6 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2 text-gray-400"
                                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                                    </svg>
                                    <p class="mb-1 text-gray-500">No households found</p>
                                    @if (
                                        !empty($search) ||
                                            !empty($barangay) ||
                                            $status !== 'active' ||
                                            !empty($regionCode) ||
                                            !empty($provinceCode) ||
                                            !empty($cityCode) ||
                                            !empty($barangayCode))
                                        <p class="text-sm text-gray-400">Try adjusting your filters</p>
                                        <x-mary-button wire:click="resetFilters"
                                            class="tagged-color btn-secondary btn-outline btn-secline" size="sm"
                                            class="mt-3">
                                            Reset Filters
                                        </x-mary-button>
                                    @else
                                        <x-mary-button link="{{ route('households.create') }}"
                                            class="tagged-color btn-primary" size="sm" class="mt-3"
                                            icon="o-plus">
                                            Add a Household
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
            {{ $households->links() }}
        </div>
    </x-mary-card>
</div>
