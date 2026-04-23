<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Generate Batch ID Cards') }}
            </h2>
            <div class="flex space-x-2">
                <x-mary-button link="{{ route('residents.index') }}"
                    class="tagged-color btn-secondary btn-outline btn-secline" icon="o-arrow-left">
                    Back to Residents
                </x-mary-button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <x-mary-card>
                <h3 class="mb-6 text-xl font-semibold">Select Residents for ID Card Generation</h3>

                <form action="{{ route('residents.id-cards.batch') }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="p-4 mt-6 border rounded-lg bg-base-50">
                        <h4 class="mb-3 font-medium text-gray-700">ID Card Layout</h4>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <label
                                class="flex items-start p-4 transition-colors border rounded-lg cursor-pointer hover:bg-base-100">
                                <input type="radio" name="orientation" value="landscape" class="mt-1 mr-3" checked>
                                <div>
                                    <div class="font-medium">Landscape Format</div>
                                    <div class="text-sm text-gray-500">3.375 × 2.125 inches</div>
                                    <div class="w-20 h-12 mt-2 bg-indigo-100 border border-indigo-200 rounded"></div>
                                </div>
                            </label>

                            <label
                                class="flex items-start p-4 transition-colors border rounded-lg cursor-pointer hover:bg-base-100">
                                <input type="radio" name="orientation" value="portrait" class="mt-1 mr-3">
                                <div>
                                    <div class="font-medium">Portrait Format</div>
                                    <div class="text-sm text-gray-500">2.125 × 3.375 inches</div>
                                    <div class="w-12 h-20 mt-2 bg-indigo-100 border border-indigo-200 rounded"></div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div>
                            <x-mary-select name="barangay" id="filter-barangay" label="Filter by Barangay"
                                placeholder="All Barangays" :options="collect($barangayList)->map(fn($b) => ['key' => $b, 'id' => $b])->toArray()" option-value="key" option-label="id" />
                        </div>

                        <div>
                            <x-mary-select name="status" id="filter-status" label="Filter by Status" :options="[
                                ['key' => 'all', 'id' => 'All Statuses'],
                                ['key' => 'active', 'id' => 'Active'],
                                ['key' => 'inactive', 'id' => 'Inactive'],
                            ]"
                                option-value="key" option-label="id" placeholder="All Statuses" />
                        </div>
                    </div>

                    <div class="py-4 border-t">
                        <h4 class="mb-2 font-medium text-gray-700">Select Residents</h4>

                        <div id="residents-loading" class="py-4 text-center">
                            <p class="text-gray-500">Loading residents...</p>
                        </div>

                        <div id="residents-container" class="hidden border rounded-lg bg-base-50">
                            <div class="p-4 border-b">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="select-all"
                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                        <label for="select-all"
                                            class="block ml-2 text-sm font-medium text-gray-700">Select All</label>
                                    </div>

                                    <div>
                                        <span id="selected-count" class="text-sm font-medium">0</span>
                                        <span class="text-sm text-gray-500">residents selected</span>
                                    </div>
                                </div>
                            </div>

                            <div id="residents-list" class="p-4 overflow-y-auto max-h-96">
                                <!-- Resident checkboxes will be populated here by JavaScript -->
                                <p class="text-gray-500">No residents found. Try adjusting your filters.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t">
                        <x-mary-button type="submit" class="tagged-color btn-primary" icon="o-document-arrow-down">
                            Generate ID Cards
                        </x-mary-button>
                    </div>
                </form>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const barangayFilter = document.getElementById('filter-barangay');
                        const statusFilter = document.getElementById('filter-status');
                        const residentsLoading = document.getElementById('residents-loading');
                        const residentsContainer = document.getElementById('residents-container');
                        const residentsList = document.getElementById('residents-list');
                        const selectAllCheckbox = document.getElementById('select-all');
                        const selectedCountEl = document.getElementById('selected-count');

                        // Load residents on page load and when filters change
                        loadResidents();

                        barangayFilter.addEventListener('change', loadResidents);
                        statusFilter.addEventListener('change', loadResidents);

                        // Select All functionality
                        selectAllCheckbox.addEventListener('change', function() {
                            const checkboxes = document.querySelectorAll('#residents-list input[type="checkbox"]');
                            checkboxes.forEach(checkbox => {
                                checkbox.checked = selectAllCheckbox.checked;
                            });
                            updateSelectedCount();
                        });

                        // Function to load residents based on filters
                        function loadResidents() {
                            // Show loading
                            residentsLoading.classList.remove('hidden');
                            residentsContainer.classList.add('hidden');

                            // Get filter values
                            const barangay = barangayFilter.value;
                            const status = statusFilter.value;

                            // Fetch residents from API
                            fetch(`/api/residents?barangay=${barangay}&status=${status}`)
                                .then(response => response.json())
                                .then(data => {
                                    // Hide loading, show container
                                    residentsLoading.classList.add('hidden');
                                    residentsContainer.classList.remove('hidden');

                                    // Populate residents list
                                    if (data.length > 0) {
                                        residentsList.innerHTML = '';

                                        data.forEach(resident => {
                                            const div = document.createElement('div');
                                            div.className = 'flex items-center p-2 border-b';

                                            div.innerHTML = `
                                                <input type="checkbox" name="residents[]" value="${resident.id}"
                                                    id="resident-${resident.id}" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                                <label for="resident-${resident.id}" class="block ml-2 text-sm font-medium text-gray-700">
                                                    ${resident.last_name}, ${resident.first_name} ${resident.middle_name ? resident.middle_name.charAt(0) + '.' : ''}
                                                    <span class="ml-2 text-xs text-gray-500">${resident.resident_id}</span>
                                                </label>
                                            `;

                                            residentsList.appendChild(div);

                                            // Add change event to update selected count
                                            div.querySelector('input[type="checkbox"]').addEventListener('change',
                                                updateSelectedCount);
                                        });
                                    } else {
                                        residentsList.innerHTML =
                                            '<p class="text-gray-500">No residents found. Try adjusting your filters.</p>';
                                    }
                                })
                                .catch(error => {
                                    console.error('Error fetching residents:', error);
                                    residentsLoading.classList.add('hidden');
                                    residentsContainer.classList.remove('hidden');
                                    residentsList.innerHTML =
                                        '<p class="text-red-500">Error loading residents. Please try again.</p>';
                                });
                        }

                        // Function to update selected count
                        function updateSelectedCount() {
                            const checkboxes = document.querySelectorAll('#residents-list input[type="checkbox"]:checked');
                            selectedCountEl.textContent = checkboxes.length;
                        }
                    });
                </script>
            </x-mary-card>
        </div>
    </div>
</x-app-layout>
