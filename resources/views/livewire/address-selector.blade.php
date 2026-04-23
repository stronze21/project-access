<div>
    <div class="grid grid-cols-1 gap-4 mb-4 md:grid-cols-2">
        <!-- Region Selection -->
        <div>
            <x-mary-select label="Region" wire:model.live="selectedRegion" placeholder="Select Region" :options="collect($regions)
                ->map(fn($region) => ['key' => $region->regCode, 'value' => $region->regDesc])
                ->toArray()"
                option-value="key" option-label="value" disabled />
        </div>

        <!-- Province Selection -->
        <div>
            <x-mary-select label="Province" wire:model.live="selectedProvince" placeholder="Select Province"
                :options="collect($provinces)
                    ->map(fn($province) => ['key' => $province->provCode, 'value' => $province->provDesc])
                    ->toArray()" option-value="key" option-label="value" :disabled="empty($provinces)" disabled />
        </div>

        <!-- City/Municipality Selection -->
        <div>
            <x-mary-select label="City/Municipality" wire:model.live="selectedCity"
                placeholder="Select City/Municipality" :options="collect($cities)
                    ->map(fn($city) => ['key' => $city->citymunCode, 'value' => $city->citymunDesc])
                    ->toArray()" option-value="key" option-label="value"
                :disabled="empty($cities)" disabled />
        </div>

        <!-- Barangay Selection -->
        <div>
            <x-mary-select label="Barangay" wire:model.live="selectedBarangay" placeholder="Select Barangay"
                :options="collect($barangays)
                    ->map(fn($barangay) => ['key' => $barangay->brgyCode, 'value' => strtoupper($barangay->brgyDesc)])
                    ->toArray()" option-value="key" option-label="value" :disabled="empty($barangays)" />
        </div>
    </div>

    <!-- Hidden inputs for form submission -->
    <input type="hidden" name="region_code" value="{{ $selectedRegion }}">
    <input type="hidden" name="region_name" value="{{ $regionName }}">
    <input type="hidden" name="province_code" value="{{ $selectedProvince }}">
    <input type="hidden" name="province_name" value="{{ $provinceName }}">
    <input type="hidden" name="city_municipality_code" value="{{ $selectedCity }}">
    <input type="hidden" name="city_municipality_name" value="{{ $cityName }}">
    <input type="hidden" name="barangay_code" value="{{ $selectedBarangay }}">
    <input type="hidden" name="barangay_name" value="{{ $barangayName }}">

    <div wire:loading class="mt-2 text-xs text-gray-500">
        Loading...
    </div>
</div>
