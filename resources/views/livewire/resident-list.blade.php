<div>
    <div class="flex flex-col justify-between gap-4 mb-6 md:flex-row md:items-end">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Residents</h1>
            <p class="mt-1 text-sm text-gray-600">Manage resident information and households</p>
        </div>
        <div class="flex flex-col gap-1 md:space-x-2 md:flex-row md:items-center">
            <x-mary-button link="{{ route('residents.create') }}" icon="o-plus" label="New Resident" class="btn-primary" />

            <x-mary-dropdown>
                <x-slot name="trigger">
                    <x-mary-button class="tagged-color" icon="o-document-arrow-down">
                        Import/Export
                    </x-mary-button>
                </x-slot>

                <x-mary-menu-item icon="o-arrow-down-tray" href="{{ route('residents.import') }}">
                    Import Residents
                </x-mary-menu-item>
                <x-mary-menu-item icon="o-arrow-up-tray" href="{{ route('residents.export') }}">
                    Export Residents
                </x-mary-menu-item>
            </x-mary-dropdown>

            <x-mary-button wire:click="toggleFilters" class="tagged-color" icon="o-funnel">
                {{ $showFilters ? 'Hide Filters' : 'Show Filters' }}
            </x-mary-button>

            <x-mary-button wire:click="$toggle('showQrScanner')" class="tagged-color" icon="o-qr-code">
                {{ $showQrScanner ? 'Hide Scanner' : 'Scan QR/RFID' }}
            </x-mary-button>

            @role('system-administrator')
                <x-mary-dropdown>
                    <x-slot name="trigger">
                        <x-mary-button class="tagged-color" icon="o-identification">
                            Batch Actions
                        </x-mary-button>
                    </x-slot>
                    <x-mary-menu-item link="{{ route('residents.id-cards.form') }}" icon="o-identification">
                        Batch ID Cards
                    </x-mary-menu-item>
                    <x-mary-menu-item wire:click="toggleBatchImageModal" icon="o-photo">
                        Batch Image Download
                    </x-mary-menu-item>
                </x-mary-dropdown>
            @endrole
        </div>
    </div>

    <!-- Import Success Message -->
    @if (session('success') && session('importStats'))
        <x-mary-alert class="tagged-color alert-success" class="mb-6">
            <x-slot name="title">{{ session('success') }}</x-slot>
            <div class="grid grid-cols-1 gap-2 mt-2 sm:grid-cols-3">
                <div class="text-center">
                    <span class="text-sm">Total Records:</span>
                    <span class="block font-semibold">{{ session('importStats')['total'] }}</span>
                </div>
                <div class="text-center">
                    <span class="text-sm">Created:</span>
                    <span class="block font-semibold">{{ session('importStats')['created'] }}</span>
                </div>
                <div class="text-center">
                    <span class="text-sm">Updated:</span>
                    <span class="block font-semibold">{{ session('importStats')['updated'] }}</span>
                </div>
            </div>
        </x-mary-alert>
    @endif

    <!-- Selected Residents Bar (shows when residents are selected) -->
    @if (count($selectedResidents) > 0)
        <div class="flex items-center justify-between p-4 mb-4 border border-blue-200 rounded-lg bg-blue-50">
            <div class="flex items-center">
                <span class="text-blue-700">{{ count($selectedResidents) }} resident(s) selected</span>
            </div>
            <div class="flex space-x-2">
                <x-mary-button wire:click="toggleBatchImageModal" size="sm" class="tagged-color btn-primary"
                    icon="o-photo">
                    Download Images
                </x-mary-button>
                <x-mary-button wire:click="clearSelected" size="sm"
                    class="tagged-color btn-secondary btn-outline btn-secline" icon="o-x-mark">
                    Clear Selection
                </x-mary-button>
            </div>
        </div>
    @endif

    <!-- Search and Filters -->
    <div class="mb-6">
        <div class="flex flex-col gap-4 md:flex-row">
            <div class="flex-1">
                <x-mary-input wire:model.live.debounce.300ms="search" label="Search"
                    placeholder="Search by name, ID, QR code, RFID, or birthplace..." icon="o-magnifying-glass" />
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
                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div>
                        <x-mary-select label="Barangay" wire:model.live="barangay" placeholder="All Barangays"
                            :options="collect($barangayList)->map(fn($b) => ['key' => $b, 'id' => $b])->toArray()" option-value="key" option-label="id" class="w-48" />
                    </div>
                    <div>
                        <x-mary-select label="Special Sector" wire:model.live="specialSector" placeholder="All Sectors"
                            :options="collect($specialSectorList)->map(fn($s) => ['key' => $s, 'id' => $s])->toArray()" option-value="key" option-label="id" class="w-48" />
                    </div>
                    <div>
                        <x-mary-select label="Status" wire:model.live="status" :options="[
                            ['key' => 'all', 'id' => 'All Statuses'],
                            ['key' => 'active', 'id' => 'Active'],
                            ['key' => 'inactive', 'id' => 'Inactive'],
                        ]" option-value="key"
                            option-label="id" class="w-40" />
                    </div>
                    <div>
                        <x-mary-select label="Sort By" wire:model.live="sortField" :options="[
                            ['key' => 'created_at', 'id' => 'Registration Date'],
                            ['key' => 'last_name', 'id' => 'Last Name'],
                            ['key' => 'first_name', 'id' => 'First Name'],
                            ['key' => 'birth_date', 'id' => 'Age'],
                            ['key' => 'birthplace', 'id' => 'Birthplace'],
                            ['key' => 'date_issue', 'id' => 'Date Issued'],
                        ]" option-value="key"
                            option-label="id" class="w-48" />
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

    <!-- Residents Table -->
    <x-mary-card>
        <div class="overflow-x-auto min-h-[500px]">
            <table class="w-full text-sm text-left text-base-500">
                <thead class="text-xs text-gray-700 uppercase bg-base-50">
                    <tr>
                        <th scope="col" class="px-2 py-3">
                            <x-mary-checkbox
                                wire:click="toggleSelectAll({{ json_encode($residents->pluck('id')->toArray()) }})"
                                :checked="count($selectedResidents) === $residents->count() && $residents->count() > 0" />
                        </th>
                        <th scope="col" class="px-4 py-3">
                            <div class="flex items-center">
                                ID
                                <button wire:click="sortBy('resident_id')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'resident_id')
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
                                Name
                                <button wire:click="sortBy('last_name')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'last_name')
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
                                Age/Gender
                                <button wire:click="sortBy('birth_date')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'birth_date')
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
                        <th scope="col" class="px-4 py-3">Address</th>
                        <th scope="col" class="px-4 py-3">
                            <div class="flex items-center">
                                Special Sector
                                <button wire:click="sortBy('special_sector')" class="ml-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        @if ($sortField === 'special_sector')
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
                        <th scope="col" class="px-4 py-3">Contact</th>
                        <th scope="col" class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                            Portal Email
                        </th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                            Portal Status
                        </th>
                        <th class="px-4 py-3 text-xs font-medium tracking-wider text-left text-gray-700 uppercase">
                            Last Login
                        </th>
                        <th scope="col" class="px-4 py-3">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($residents as $resident)
                        <tr class="border-b bg-base hover:bg-base-50">
                            <td class="px-2 py-3">
                                <x-mary-checkbox wire:click="toggleResidentSelection({{ $resident->id }})"
                                    :checked="in_array($resident->id, $selectedResidents)" />
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ $resident->resident_id }}
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <div class="flex items-center">
                                    @if ($resident->photo_path)
                                        <img src="{{ Storage::url($resident->photo_path) }}"
                                            alt="{{ $resident->full_name }}"
                                            class="object-cover w-8 h-8 mr-2 rounded-full">
                                    @else
                                        <div
                                            class="flex items-center justify-center w-8 h-8 mr-2 rounded-full bg-base-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        </div>
                                    @endif
                                    <div>
                                        {{ $resident->last_name }}, {{ $resident->first_name }}
                                        {{ $resident->middle_name ? substr($resident->middle_name, 0, 1) . '.' : '' }}
                                        @if ($resident->is_senior_citizen)
                                            <span
                                                class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">Senior</span>
                                        @endif
                                        @if ($resident->is_pwd)
                                            <span
                                                class="ml-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">PWD</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                {{ $resident->getAge() }} / {{ ucfirst($resident->gender) }}
                                @if ($resident->blood_type)
                                    <span class="ml-1 text-xs text-gray-500">({{ $resident->blood_type }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($resident->household)
                                    <div class="text-xs text-gray-500">
                                        {{ $resident->household->getFullAddressAttribute() }}</div>
                                @else
                                    <span class="text-gray-500">No household</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                {{ $resident->special_sector ?: 'None' }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($resident->contact_number)
                                    {{ $resident->contact_number }}
                                @endif
                                @if ($resident->email)
                                    <div class="text-xs">{{ $resident->email }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $resident->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $resident->is_active ? 'Active' : 'Inactive' }}
                                </span>

                                @if ($resident->is_registered_voter && $resident->precinct_no)
                                    <span class="block mt-1 text-xs">Precinct: {{ $resident->precinct_no }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $resident->email ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($resident->email && $resident->password)
                                    <x-mary-badge value="Enabled" class="badge-success badge-sm" />
                                @else
                                    <x-mary-badge value="Disabled" class="badge-ghost badge-sm" />
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
                                {{ $resident->last_login_at ? $resident->last_login_at->format('M d, Y') : 'Never' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <!-- Also apply the same fix to the administrator dropdown -->
                                    @role('system-administrator')
                                        <x-mary-button icon="o-identification"
                                            link="{{ route('residents.id-card', $resident->id) }}" external="true" />
                                    @endrole

                                    <!-- Update the x-mary-dropdown component usage -->
                                    <x-mary-dropdown placement="bottom-end" class="dropdown-z-fix">
                                        <x-slot name="trigger">
                                            <x-mary-button icon="o-ellipsis-vertical" size="xs"
                                                class="btn-base" />
                                        </x-slot>
                                        <div class="px-4 py-2 text-xs border-b text-start">Actions</div>


                                        <x-mary-menu-item link="{{ route('residents.show', $resident->id) }}">
                                            View
                                        </x-mary-menu-item>
                                        <x-mary-menu-item link="{{ route('residents.edit', $resident->id) }}">
                                            Edit
                                        </x-mary-menu-item>
                                        <!-- Signature Actions -->
                                        @can('edit-residents')
                                            <x-mary-menu-item
                                                link="{{ route('residents.update-signature', $resident->id) }}">
                                                Update with XP-Pen
                                            </x-mary-menu-item>
                                        @endcan
                                        <x-mary-menu-item href="{{ route('qrcode.resident', $resident->id) }}"
                                            target="_blank">
                                            View QR Code
                                        </x-mary-menu-item>
                                        <x-mary-menu-item href="{{ route('qrcode.download', $resident->id) }}">
                                            Download QR Code
                                        </x-mary-menu-item>
                                        <x-mary-menu-item
                                            wire:click="setResidentStatus({{ $resident->id }}, '{{ $resident->is_active ? 'inactive' : 'active' }}')">
                                            Mark as {{ $resident->is_active ? 'Inactive' : 'Active' }}
                                        </x-mary-menu-item>
                                    </x-mary-dropdown>
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    @if ($residents->count() === 0)
                        <tr class="border-b bg-base">
                            <td colspan="10" class="px-4 py-6 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <p class="mb-1">No residents found</p>
                                    @if (!empty($search) || !empty($barangay) || !empty($specialSector) || $status !== 'active')
                                        <p class="text-sm ">Try adjusting your filters</p>
                                        <x-mary-button wire:click="resetFilters"
                                            class="tagged-color btn-secondary btn-outline btn-secline" size="sm"
                                            class="mt-3">
                                            Reset Filters
                                        </x-mary-button>
                                    @else
                                        <x-mary-button link="{{ route('residents.create') }}"
                                            class="tagged-color btn-primary" size="sm" class="mt-3"
                                            icon="o-plus">
                                            Add a Resident
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
            {{ $residents->links() }}
        </div>
    </x-mary-card>

    <!-- Batch Image Download Modal -->
    <x-mary-modal wire:model.live="showBatchImageModal" title="Batch Image Download" max-width="md">
        <div class="mb-6">
            <p class="text-sm text-gray-600">Download multiple images at once for selected residents
                ({{ count($selectedResidents) }} selected). Images will be organized in a ZIP file, with a separate
                folder for each resident.</p>
        </div>

        <!-- Image Type Selection -->
        <div class="mb-6">
            <h3 class="mb-2 text-base font-medium text-gray-700">Select image types to include:</h3>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                @foreach ($availableImageTypes as $type => $label)
                    <div>
                        <x-mary-checkbox wire:model.live="selectedImageTypes.{{ $type }}"
                            label="{{ $label }}" />
                    </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end gap-x-4">
            <x-mary-button wire:click="$set('showBatchImageModal', false)"
                class="tagged-color btn-secondary btn-outline btn-secline">
                Cancel
            </x-mary-button>
            <x-mary-button wire:click="downloadImages" class="tagged-color btn-primary" icon="o-arrow-down-tray">
                Download Images
            </x-mary-button>
        </div>
    </x-mary-modal>
</div>
