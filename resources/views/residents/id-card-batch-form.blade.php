<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Generate Batch ACCESS ID Cards</h2>
            <x-mary-button link="{{ route('residents.index') }}"
                class="tagged-color btn-secondary btn-outline btn-secline" icon="o-arrow-left">
                Back to Residents
            </x-mary-button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <x-mary-card>
                <h3 class="text-xl font-semibold">Select Residents</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Landscape CR80 format (3.375 × 2.125 inches) with print bleed.
                </p>

                <form action="{{ route('residents.id-cards.batch') }}" method="POST" class="mt-6 space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <x-mary-select name="barangay" id="filter-barangay" label="Filter by Barangay"
                            placeholder="All Barangays" :options="collect($barangayList)->map(fn ($barangay) => ['key' => $barangay, 'id' => $barangay])->toArray()"
                            option-value="key" option-label="id" />

                        <x-mary-select name="status" id="filter-status" label="Filter by Status" :options="[
                            ['key' => 'all', 'id' => 'All Statuses'],
                            ['key' => 'active', 'id' => 'Active'],
                            ['key' => 'inactive', 'id' => 'Inactive'],
                        ]"
                            option-value="key" option-label="id" placeholder="All Statuses" />
                    </div>

                    <div class="overflow-hidden border rounded-lg bg-base-50">
                        <div class="flex items-center justify-between p-4 border-b">
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                                <input type="checkbox" id="select-all"
                                    class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                Select All
                            </label>
                            <p class="text-sm text-gray-500"><span id="selected-count" class="font-semibold">0</span> / {{ \App\Http\Controllers\ResidentIdCardController::MAX_BATCH_SIZE }} selected</p>
                        </div>

                        <div id="residents-loading" class="p-6 text-center text-gray-500">Loading residents...</div>
                        <div id="residents-list" class="hidden p-4 overflow-y-auto max-h-96"></div>
                    </div>

                    @error('residents')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('residents.*')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p id="selection-limit-message" class="hidden text-sm text-amber-700" role="status">
                        The 100-resident print limit has been reached. Print this group, then select the next group.
                    </p>

                    <div class="flex justify-end pt-4 border-t">
                        <x-mary-button id="generate-id-cards" type="submit" class="tagged-color btn-primary" icon="o-printer" disabled>
                            Generate ID Cards
                        </x-mary-button>
                    </div>
                </form>
            </x-mary-card>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const barangayFilter = document.getElementById('filter-barangay');
            const statusFilter = document.getElementById('filter-status');
            const loading = document.getElementById('residents-loading');
            const list = document.getElementById('residents-list');
            const selectAll = document.getElementById('select-all');
            const selectedCount = document.getElementById('selected-count');
            const limitMessage = document.getElementById('selection-limit-message');
            const generateButton = document.getElementById('generate-id-cards');
            const maxBatchSize = {{ \App\Http\Controllers\ResidentIdCardController::MAX_BATCH_SIZE }};
            const residentsEndpoint = @json(route('api.residents.index'));

            const updateSelectedCount = () => {
                const checkboxes = list.querySelectorAll('input[name="residents[]"]');
                const checked = list.querySelectorAll('input[name="residents[]"]:checked');
                selectedCount.textContent = checked.length;
                generateButton.disabled = checked.length === 0;
                checkboxes.forEach((checkbox) => {
                    checkbox.disabled = checked.length >= maxBatchSize && !checkbox.checked;
                });
                limitMessage.classList.toggle('hidden', checked.length < maxBatchSize);
                selectAll.checked = checkboxes.length > 0 && checked.length === checkboxes.length;
                selectAll.indeterminate = checked.length > 0 && checked.length < checkboxes.length;
            };

            const loadResidents = async () => {
                loading.classList.remove('hidden');
                list.classList.add('hidden');
                selectAll.checked = false;
                selectAll.indeterminate = false;

                const query = new URLSearchParams({
                    barangay: barangayFilter.value || '',
                    status: statusFilter.value || 'all',
                    perPage: maxBatchSize,
                    sortField: 'last_name',
                    sortDirection: 'asc',
                });

                try {
                    const controller = new AbortController();
                    const timeout = window.setTimeout(() => controller.abort(), 15000);
                    const response = await fetch(`${residentsEndpoint}?${query.toString()}`, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                        signal: controller.signal,
                    });
                    window.clearTimeout(timeout);
                    if (!response.ok) throw new Error(`Unable to load residents (${response.status}).`);

                    const payload = await response.json();
                    const residents = Array.isArray(payload) ? payload : payload.data;
                    if (!Array.isArray(residents)) throw new Error('The resident list response was invalid.');
                    list.replaceChildren();

                    if (!residents.length) {
                        const empty = document.createElement('p');
                        empty.className = 'text-gray-500';
                        empty.textContent = 'No residents found. Try adjusting the filters.';
                        list.appendChild(empty);
                    }

                    residents.forEach((resident) => {
                        const row = document.createElement('label');
                        row.className = 'flex items-center gap-2 p-2 border-b cursor-pointer last:border-b-0';

                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.name = 'residents[]';
                        checkbox.value = resident.id;
                        checkbox.className = 'w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500';
                        checkbox.addEventListener('change', updateSelectedCount);

                        const name = document.createElement('span');
                        name.className = 'text-sm font-medium text-gray-700';
                        const middleInitial = resident.middle_name ? ` ${resident.middle_name.charAt(0)}.` : '';
                        name.textContent = `${resident.last_name}, ${resident.first_name}${middleInitial} (${resident.resident_id})`;

                        row.append(checkbox, name);
                        list.appendChild(row);
                    });
                } catch (error) {
                    const message = error.name === 'AbortError'
                        ? 'Loading residents timed out. Please check the connection and try again.'
                        : error.message || 'Unable to load residents. Please try again.';
                    list.replaceChildren();
                    const failure = document.createElement('p');
                    failure.className = 'text-red-600';
                    failure.textContent = message;
                    list.appendChild(failure);
                } finally {
                    loading.classList.add('hidden');
                    list.classList.remove('hidden');
                    updateSelectedCount();
                }
            };

            selectAll.addEventListener('change', () => {
                list.querySelectorAll('input[name="residents[]"]').forEach((checkbox, index) => {
                    checkbox.checked = selectAll.checked && index < maxBatchSize;
                });
                updateSelectedCount();
            });

            barangayFilter.addEventListener('change', loadResidents);
            statusFilter.addEventListener('change', loadResidents);
            loadResidents();
        });
    </script>
</x-app-layout>
