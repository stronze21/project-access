@php
    $isEdit = isset($complaint) && $complaint !== null;
    $selectedOfficials = collect(old('official_ids', $isEdit ? $complaint->officials->pluck('id')->all() : []))
        ->map(fn ($value) => (int) $value)
        ->all();
@endphp

@if (!empty($isAnonymousForm))
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div>
            <x-input-label for="reporter_name" :value="__('Name (optional)')" />
            <x-text-input id="reporter_name" name="reporter_name" type="text" class="mt-1 block w-full"
                :value="old('reporter_name', $isEdit ? $complaint->reporter_name : '')" />
            <x-input-error :messages="$errors->get('reporter_name')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="reporter_email" :value="__('Email (optional)')" />
            <x-text-input id="reporter_email" name="reporter_email" type="email" class="mt-1 block w-full"
                :value="old('reporter_email', $isEdit ? $complaint->reporter_email : '')" />
            <x-input-error :messages="$errors->get('reporter_email')" class="mt-2" />
        </div>
    </div>
@endif

<div>
    <x-input-label for="title" :value="__('Title')" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
        :value="old('title', $isEdit ? $complaint->title : '')" required />
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div>
    <x-input-label for="short_summary" :value="__('Short Summary')" />
    <x-text-input id="short_summary" name="short_summary" type="text" class="mt-1 block w-full"
        :value="old('short_summary', $isEdit ? $complaint->short_summary : '')" required />
    <x-input-error :messages="$errors->get('short_summary')" class="mt-2" />
</div>

<div>
    <x-input-label for="description" :value="__('Full Description (internal only)')" />
    <textarea id="description" name="description" rows="5" class="mt-1 block w-full rounded-md border-base-300 textarea textarea-bordered" required>{{ old('description', $isEdit ? $complaint->description : '') }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
    <div>
        <x-input-label for="category_id" :value="__('Category')" />
        <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-base-300 select select-bordered" required>
            <option value="">Select category</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $isEdit ? $complaint->category_id : '') === (string) $category->id)>
                    {{ $category->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="visibility" :value="__('Visibility')" />
        <select id="visibility" name="visibility" class="mt-1 block w-full rounded-md border-base-300 select select-bordered" required>
            @foreach ($visibilityOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('visibility', $isEdit ? $complaint->visibility : \App\Models\Complaint::VISIBILITY_PUBLIC_ANONYMOUS) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('visibility')" class="mt-2" />
    </div>
</div>

<div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
    <div>
        <x-input-label for="barangay_id" :value="__('Barangay (optional)')" />
        <select id="barangay_id" name="barangay_id" class="mt-1 block w-full rounded-md border-base-300 select select-bordered">
            <option value="">Select barangay</option>
            @foreach ($barangays as $barangay)
                <option value="{{ $barangay->id }}" @selected((string) old('barangay_id', $isEdit ? $complaint->barangay_id : '') === (string) $barangay->id)>
                    {{ $barangay->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('barangay_id')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="latitude" :value="__('Latitude (optional)')" />
        <x-text-input id="latitude" name="latitude" type="text" class="mt-1 block w-full"
            :value="old('latitude', $isEdit ? $complaint->latitude : '')" />
        <p class="mt-1 text-xs text-base-content/60">Use "Pin My Location" below to fill automatically.</p>
        <x-input-error :messages="$errors->get('latitude')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="longitude" :value="__('Longitude (optional)')" />
        <x-text-input id="longitude" name="longitude" type="text" class="mt-1 block w-full"
            :value="old('longitude', $isEdit ? $complaint->longitude : '')" />
        <p class="mt-1 text-xs text-base-content/60">You can still edit manually if needed.</p>
        <x-input-error :messages="$errors->get('longitude')" class="mt-2" />
    </div>
</div>

<div class="rounded-xl border border-base-300 bg-base-200 p-3 sm:p-4">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-semibold text-base-content">Location Pin (optional)</p>
            <p class="text-xs text-base-content/70">Tap the button to detect your current location and place a pin on the map.</p>
        </div>
        <button type="button"
                id="pin-my-location"
                class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700 btn btn-primary btn-xs">
            Pin My Location
        </button>
    </div>

    <p id="pin-location-status" class="mt-2 text-xs text-base-content/70"></p>

    <div id="pin-location-map-wrapper" class="mt-3 hidden">
        <div id="pin-location-map" class="h-64 w-full rounded-lg border border-base-300"></div>
        <p class="mt-2 text-xs text-base-content/70">Tip: tap on the map or drag the marker to adjust your pin.</p>
    </div>
</div>

<div>
    <x-input-label for="official_ids" :value="__('Tag Public Officials (optional)')" />
    <select id="official_ids" name="official_ids[]" class="mt-1 block w-full rounded-md border-base-300 select select-bordered" multiple>
        @foreach ($officials as $official)
            <option value="{{ $official->id }}" @selected(in_array((int) $official->id, $selectedOfficials, true))>
                {{ $official->position }} - {{ $official->name }}
            </option>
        @endforeach
    </select>
    <p class="mt-1 text-xs text-base-content/60">Hold Ctrl/Cmd to select multiple.</p>
    <x-input-error :messages="$errors->get('official_ids')" class="mt-2" />
    <x-input-error :messages="$errors->get('official_ids.*')" class="mt-2" />
</div>

<div>
    <x-input-label for="attachments" :value="__('Attachments (max 5 files, 20MB each)')" />
    <input id="attachments" name="attachments[]" type="file" multiple
           accept=".jpg,.jpeg,.png,.webp,.mp4,.mov,.pdf,.doc,.docx,.xls,.xlsx"
           class="mt-1 block w-full rounded-md border border-base-300 px-3 py-2 text-sm file-input file-input-bordered">
    <x-input-error :messages="$errors->get('attachments')" class="mt-2" />
    <x-input-error :messages="$errors->get('attachments.*')" class="mt-2" />
</div>

@once
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endonce

<script>
    (() => {
        const pinButton = document.getElementById('pin-my-location');
        const mapWrapper = document.getElementById('pin-location-map-wrapper');
        const mapElement = document.getElementById('pin-location-map');
        const statusElement = document.getElementById('pin-location-status');
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');

        if (!pinButton || !mapWrapper || !mapElement || !statusElement || !latitudeInput || !longitudeInput) {
            return;
        }

        let map = null;
        let marker = null;
        const defaultCenter = [16.1555, 119.9814];

        const writeCoordinates = (lat, lng) => {
            latitudeInput.value = Number(lat).toFixed(6);
            longitudeInput.value = Number(lng).toFixed(6);
        };

        const placeMarker = (lat, lng, shouldCenter = true) => {
            if (!map) {
                return;
            }

            if (!marker) {
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on('dragend', () => {
                    const pos = marker.getLatLng();
                    writeCoordinates(pos.lat, pos.lng);
                });
            } else {
                marker.setLatLng([lat, lng]);
            }

            writeCoordinates(lat, lng);

            if (shouldCenter) {
                map.setView([lat, lng], 16);
            }
        };

        const ensureMap = () => {
            mapWrapper.classList.remove('hidden');

            if (typeof L === 'undefined') {
                statusElement.textContent = 'Map could not load. You can still type latitude and longitude manually.';
                return false;
            }

            if (!map) {
                map = L.map(mapElement).setView(defaultCenter, 13);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);

                map.on('click', (event) => {
                    placeMarker(event.latlng.lat, event.latlng.lng, false);
                    statusElement.textContent = 'Pin updated from map click.';
                });
            }

            setTimeout(() => map.invalidateSize(), 0);
            return true;
        };

        const initializeFromExistingCoordinates = () => {
            const lat = Number.parseFloat(latitudeInput.value);
            const lng = Number.parseFloat(longitudeInput.value);
            if (Number.isFinite(lat) && Number.isFinite(lng) && ensureMap()) {
                placeMarker(lat, lng, true);
                statusElement.textContent = 'Existing pin loaded. You can move it on the map.';
            }
        };

        pinButton.addEventListener('click', () => {
            if (!ensureMap()) {
                return;
            }

            if (!navigator.geolocation) {
                statusElement.textContent = 'Geolocation is not supported on this browser. Tap the map to pin manually.';
                return;
            }

            statusElement.textContent = 'Detecting your location...';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    placeMarker(position.coords.latitude, position.coords.longitude, true);
                    statusElement.textContent = 'Location pinned successfully. You can move the marker if needed.';
                },
                () => {
                    statusElement.textContent = 'Location access denied or unavailable. Tap the map to pin manually.';
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 60000,
                }
            );
        });

        initializeFromExistingCoordinates();
    })();
</script>
