<div>
    <x-mary-card title="{{ $isEdit ? 'Edit Household' : 'Create New Household' }}">
        <x-slot:menu>
            @if ($isEdit)
                <x-mary-button link="{{ route('households.show', $householdId) }}" label="View"
                    class="tagged-color btn-primary" size="sm" />
            @endif
            <x-mary-button link="{{ route('households.index') }}" label="All Households"
                class="tagged-color btn-secondary btn-outline btn-secline" size="sm" />
        </x-slot:menu>

        <form wire:submit="save">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-6">
                <!-- Address Information -->
                <div>
                    <h3 class="mb-4 text-lg font-semibold">Address Information</h3>

                    <div class="mb-4">
                        <x-mary-textarea label="Street Address" wire:model="address" required
                            placeholder="Enter complete street address" error="{{ $errors->first('address') }}" />
                    </div>

                    <!-- PSGC Address Selector -->
                    <div class="mb-6">
                        <h4 class="mb-2 text-sm font-medium text-gray-700">Location</h4>
                        <livewire:address-selector :initialRegionCode="$regionCode" :initialProvinceCode="$provinceCode" :initialCityCode="$cityMunicipalityCode"
                            :initialBarangayCode="$barangayCode" />
                    </div>

                    <div class="mb-4">
                        <x-mary-input label="Postal Code" wire:model="postalCode"
                            placeholder="Enter postal code (optional)" error="{{ $errors->first('postalCode') }}" />
                    </div>
                </div>

                <!-- Household Details -->
                <div>
                    <h3 class="mb-4 text-lg font-semibold">Household Details</h3>

                    <div class="mb-4">
                        <x-mary-select label="Dwelling Type" wire:model="dwellingType" :options="[
                            ['key' => '', 'id' => 'Select dwelling type'],
                            ['key' => 'owned', 'id' => 'Owned'],
                            ['key' => 'rented', 'id' => 'Rented'],
                            ['key' => 'shared', 'id' => 'Shared with Family/Friends'],
                            ['key' => 'informal', 'id' => 'Informal Settlement'],
                            ['key' => 'other', 'id' => 'Other'],
                        ]"
                            option-value="key" option-label="id" error="{{ $errors->first('dwellingType') }}" />
                    </div>


                    <div class="mb-4">
                        <x-mary-input label="Monthly Household Income" wire:model="monthlyIncome" type="number"
                            step="0.01" placeholder="Enter amount in PHP (optional)"
                            hint="Total monthly income of all household members"
                            error="{{ $errors->first('monthlyIncome') }}" />
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <x-mary-checkbox label="Has Electricity" wire:model="hasElectricity" />
                        <x-mary-checkbox label="Has Water Supply" wire:model="hasWaterSupply" />
                    </div>

                    <div class="mb-6">
                        <x-mary-textarea label="Notes (Optional)" wire:model="notes"
                            placeholder="Additional information about this household" rows="3"
                            error="{{ $errors->first('notes') }}" />
                    </div>

                    <h3 class="mb-4 text-lg font-semibold">Head of Household</h3>

                    <div class="mb-4">
                        <label class="block mb-1 text-sm font-medium text-gray-700">Select Head of Household</label>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <x-mary-input wire:model.live.debounce.300ms="searchTerm"
                                    placeholder="Search for residents..." wire:keyup="searchResidents" />
                            </div>
                            <x-mary-button wire:click="searchResidents"
                                class="tagged-color btn-secondary btn-outline btn-secline" icon="o-magnifying-glass" />
                        </div>

                        @if (count($availableResidents) > 0)
                            <div class="mt-2 overflow-hidden border rounded-md">
                                <ul class="divide-y divide-gray-200">
                                    @foreach ($availableResidents as $resident)
                                        <li
                                            class="p-3 hover:bg-base-50 {{ $selectedResidentId == $resident->id ? 'bg-blue-50' : '' }}">
                                            <label class="flex items-center space-x-3 cursor-pointer">
                                                <input type="radio" wire:model="selectedResidentId"
                                                    value="{{ $resident->id }}"
                                                    class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500">
                                                <div class="flex-1">
                                                    <div class="font-medium">{{ $resident->full_name }}</div>
                                                    <div class="text-xs text-gray-500">
                                                        ID: {{ $resident->resident_id }} |
                                                        Age: {{ $resident->getAge() }} |
                                                        Gender: {{ ucfirst($resident->gender) }}
                                                    </div>
                                                </div>
                                            </label>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            @if ($selectedResidentId)
                                <div class="p-2 mt-2 text-sm border border-blue-200 rounded-md bg-blue-50">
                                    <p>
                                        <span class="font-medium">Selected:</span>
                                        {{ $availableResidents->firstWhere('id', $selectedResidentId)->full_name }}
                                    </p>
                                </div>
                            @endif
                        @else
                            <div class="p-3 mt-2 text-sm text-center text-gray-500 border rounded-md bg-base-50">
                                No available residents found.
                                <a href="{{ route('residents.create') }}" class="text-blue-600 hover:underline">Create
                                    a resident first.</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-8 space-x-3">
                <x-mary-button label="Cancel" class="tagged-color btn-secondary btn-outline btn-secline"
                    link="{{ route('households.index') }}" />
                <x-mary-button type="button" label="Reset Form" wire:click="resetForm"
                    class="tagged-color btn-warning" />
                <x-mary-button type="submit" label="{{ $isEdit ? 'Update Household' : 'Create Household' }}"
                    icon="o-paper-airplane" />
            </div>
        </form>
    </x-mary-card>
</div>
