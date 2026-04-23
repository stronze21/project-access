<div>
    <!-- Header -->
    <div class="flex flex-col justify-between gap-4 mb-6 sm:flex-row sm:items-center">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Household Details</h1>
            <p class="mt-1 text-sm text-gray-600">View and manage household information</p>
        </div>
        <div class="flex space-x-2">
            <x-mary-button link="{{ route('households.edit', $household->id) }}" icon="o-pencil" label="Edit" />
            <x-mary-button link="{{ route('households.index') }}"
                class="tagged-color btn-secondary btn-outline btn-secline" icon="o-arrow-left" label="Back to List" />
        </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-4">
        <x-mary-stat title="Household Members" value="{{ $household->member_count }}" icon="o-users"
            class="tagged-color text-primary" />

        <x-mary-stat title="Monthly Income" value="₱ {{ number_format($household->monthly_income ?? 0, 2) }}"
            icon="o-banknotes" class="tagged-color text-success" />

        <x-mary-stat title="Total Distributions" value="{{ $totalDistributions }}" icon="o-arrows-right-left"
            class="tagged-color text-warning" />

        <x-mary-stat title="Total Aid Received" value="₱ {{ number_format($totalAidReceived, 2) }}" icon="o-wallet"
            class="tagged-color text-info" />
    </div>

    <!-- Household Information -->
    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-3">
        <!-- Main Info Card -->
        <div class="lg:col-span-2">
            <x-mary-card>
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-semibold">{{ $household->household_id }}</h2>
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $household->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} mt-1">
                            {{ $household->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div>
                        <x-mary-button wire:click="toggleQrCode"
                            class="tagged-color btn-secondary btn-outline btn-secline" size="sm"
                            icon="o-{{ $showQrCode ? 'x-circle' : 'qr-code' }}">
                            {{ $showQrCode ? 'Hide QR' : 'Show QR' }}
                        </x-mary-button>

                        @if ($showQrCode)
                            <div class="mt-2 text-center">
                                <img src="{{ route('qrcode.household', $household->id) }}" alt="QR Code"
                                    class="w-32 h-32 mx-auto">
                                <a href="{{ route('qrcode.download.household', $household->id) }}"
                                    class="text-xs text-blue-600 hover:underline">Download</a>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <h3 class="mb-2 text-sm font-medium text-gray-500">Location Information</h3>
                        <dl class="mt-2 text-sm">
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-600">Address:</dt>
                                <dd class="font-medium text-gray-900">{{ $household->address }}</dd>
                            </div>
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-600">Barangay:</dt>
                                <dd class="font-medium text-gray-900">{{ $household->barangay }}</dd>
                            </div>
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-600">City/Municipality:</dt>
                                <dd class="font-medium text-gray-900">{{ $household->city_municipality }}</dd>
                            </div>
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-600">Province:</dt>
                                <dd class="font-medium text-gray-900">{{ $household->province }}</dd>
                            </div>
                            @if ($household->postal_code)
                                <div class="flex justify-between py-1">
                                    <dt class="text-gray-600">Postal Code:</dt>
                                    <dd class="font-medium text-gray-900">{{ $household->postal_code }}</dd>
                                </div>
                            @endif
                            @if ($household->region)
                                <div class="flex justify-between py-1">
                                    <dt class="text-gray-600">Region:</dt>
                                    <dd class="font-medium text-gray-900">{{ $household->region }}</dd>
                                </div>
                            @endif

                            <!-- PSGC Codes Section (Collapsible) -->
                            <div x-data="{ open: false }" class="pt-2 mt-2 border-t">
                                <button @click="open = !open" type="button"
                                    class="flex items-center text-xs text-blue-600 hover:text-blue-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" x-bind:class="{ 'rotate-90': open }">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                    <span>PSGC Location Codes</span>
                                </button>

                                <div x-show="open" class="p-2 mt-2 text-xs rounded bg-gray-50">
                                    @if ($household->region_code)
                                        <div class="flex justify-between py-1">
                                            <dt class="text-gray-600">Region Code:</dt>
                                            <dd class="font-mono text-gray-900">{{ $household->region_code }}</dd>
                                        </div>
                                    @endif

                                    @if ($household->province_code)
                                        <div class="flex justify-between py-1">
                                            <dt class="text-gray-600">Province Code:</dt>
                                            <dd class="font-mono text-gray-900">{{ $household->province_code }}</dd>
                                        </div>
                                    @endif

                                    @if ($household->city_municipality_code)
                                        <div class="flex justify-between py-1">
                                            <dt class="text-gray-600">City/Municipal Code:</dt>
                                            <dd class="font-mono text-gray-900">
                                                {{ $household->city_municipality_code }}</dd>
                                        </div>
                                    @endif

                                    @if ($household->barangay_code)
                                        <div class="flex justify-between py-1">
                                            <dt class="text-gray-600">Barangay Code:</dt>
                                            <dd class="font-mono text-gray-900">{{ $household->barangay_code }}</dd>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h3 class="mb-2 text-sm font-medium text-gray-500">Household Information</h3>
                        <dl class="mt-2 text-sm">
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-600">Dwelling Type:</dt>
                                <dd class="font-medium text-gray-900">
                                    {{ ucfirst($household->dwelling_type ?? 'Not specified') }}</dd>
                            </div>
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-600">Monthly Income:</dt>
                                <dd class="font-medium text-gray-900">
                                    {{ $household->monthly_income ? '₱ ' . number_format($household->monthly_income, 2) : 'N/A' }}
                                </dd>
                            </div>
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-600">Has Electricity:</dt>
                                <dd class="font-medium text-gray-900">{{ $household->has_electricity ? 'Yes' : 'No' }}
                                </dd>
                            </div>
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-600">Has Water Supply:</dt>
                                <dd class="font-medium text-gray-900">
                                    {{ $household->has_water_supply ? 'Yes' : 'No' }}
                                </dd>
                            </div>
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-600">Registered On:</dt>
                                <dd class="font-medium text-gray-900">{{ $household->created_at->format('M d, Y') }}
                                </dd>
                            </div>
                            <div class="flex justify-between py-1">
                                <dt class="text-gray-600">Last Updated:</dt>
                                <dd class="font-medium text-gray-900">{{ $household->updated_at->format('M d, Y') }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Notes Section -->
                @if ($household->notes)
                    <div class="pt-4 mt-6 border-t">
                        <h3 class="mb-2 text-sm font-medium text-gray-500">Notes</h3>
                        <div class="prose-sm prose max-w-none">
                            {{ $household->notes }}
                        </div>
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="flex justify-between pt-4 mt-6 border-t">
                    <div>
                        <x-mary-button
                            wire:click="setHouseholdStatus('{{ $household->is_active ? 'inactive' : 'active' }}')"
                            class="{{ $household->is_active ? 'btn-error' : 'btn-success' }}">
                            {{ $household->is_active ? 'Mark as Inactive' : 'Mark as Active' }}
                        </x-mary-button>
                    </div>
                    <div class="space-x-2">
                        <x-mary-button wire:click="updateMemberCount"
                            class="tagged-color btn-secondary btn-outline btn-secline" icon="o-arrow-path">
                            Update Member Count
                        </x-mary-button>
                        <x-mary-button wire:click="calculateTotalIncome"
                            class="tagged-color btn-secondary btn-outline btn-secline" icon="o-calculator">
                            Calculate Income
                        </x-mary-button>
                    </div>
                </div>
            </x-mary-card>
        </div>

        <!-- Household Members Card -->
        <div>
            <x-mary-card title="Household Members">
                <div class="divide-y">
                    @foreach ($household->residents as $resident)
                        <div class="flex items-start py-3">
                            <div class="flex-shrink-0 mr-3">
                                @if ($resident->photo_path)
                                    <img src="{{ Storage::url($resident->photo_path) }}"
                                        alt="{{ $resident->full_name }}" class="object-cover w-10 h-10 rounded-full">
                                @else
                                    <div class="flex items-center justify-center w-10 h-10 rounded-full bg-base-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-500"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <a href="{{ route('residents.show', $resident->id) }}"
                                        class="font-medium text-blue-600 hover:underline">
                                        {{ $resident->full_name }}
                                    </a>
                                    @if ($resident->relationship_to_head === 'head')
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Head
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ ucfirst(str_replace('_', ' ', $resident->relationship_to_head ?? 'Member')) }}
                                </div>
                                <div class="text-sm">
                                    {{ $resident->getAge() }} years, {{ ucfirst($resident->gender) }}
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if ($household->residents->count() === 0)
                        <div class="py-4 text-center text-gray-500">
                            No members in this household
                        </div>
                    @endif
                </div>

                <div class="flex justify-between pt-4 mt-4 border-t">
                    <x-mary-button link="{{ route('residents.create') }}?household={{ $household->id }}"
                        class="tagged-color btn-primary" size="sm" icon="o-plus">
                        Add Member
                    </x-mary-button>
                </div>
            </x-mary-card>
        </div>
    </div>

    <!-- Aid Distribution History -->
    <x-mary-card title="Aid Distribution History" class="mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left text-gray-500">
                <thead class="text-xs text-gray-700 uppercase bg-base-50">
                    <tr>
                        <th scope="col" class="px-4 py-3">Reference #</th>
                        <th scope="col" class="px-4 py-3">Program</th>
                        <th scope="col" class="px-4 py-3">Recipient</th>
                        <th scope="col" class="px-4 py-3">Date</th>
                        <th scope="col" class="px-4 py-3">Amount</th>
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
                                {{ $distribution->ayudaProgram->name }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('residents.show', $distribution->resident_id) }}"
                                    class="text-blue-600 hover:underline">
                                    {{ $distribution->resident->full_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                {{ $distribution->distribution_date->format('M d, Y') }}
                            </td>
                            <td class="px-4 py-3">
                                ₱{{ number_format($distribution->amount, 2) }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $distribution->status === 'distributed'
                                        ? 'bg-green-100 text-green-800'
                                        : ($distribution->status === 'pending'
                                            ? 'bg-yellow-100 text-yellow-800'
                                            : 'bg-red-100 text-red-800') }}">
                                    {{ ucfirst($distribution->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <x-mary-button link="{{ route('distributions.show', $distribution->id) }}"
                                    size="xs" class="tagged-color btn-secondary btn-outline btn-secline">
                                    Details
                                </x-mary-button>
                            </td>
                        </tr>
                    @endforeach

                    @if ($distributions->count() === 0)
                        <tr class="border-b bg-base">
                            <td colspan="7" class="px-4 py-4 text-center text-gray-500">
                                No aid distributions found for this household
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if ($distributions->hasPages())
            <div class="px-4 py-3 border-t">
                {{ $distributions->links() }}
            </div>
        @endif

        <div class="flex justify-end mt-4">
            <x-mary-button link="{{ route('distributions.create') }}?household={{ $household->id }}"
                class="tagged-color btn-primary" icon="o-banknotes">
                New Distribution
            </x-mary-button>
        </div>
    </x-mary-card>
</div>
