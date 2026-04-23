<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Export Residents') }}
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
                <h3 class="mb-6 text-xl font-semibold">Export Options</h3>

                <form action="{{ route('residents.export.download') }}" method="GET" class="space-y-6">
                    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                        <div>
                            <x-mary-select name="barangay" label="Filter by Barangay" placeholder="All Barangays"
                                :options="collect($barangayList)->map(fn($b) => ['key' => $b, 'id' => $b])->toArray()" option-value="key" option-label="id" />
                        </div>

                        <div>
                            <x-mary-select name="special_sector" label="Filter by Special Sector"
                                placeholder="All Sectors" :options="collect($specialSectorList)
                                    ->map(fn($s) => ['key' => $s, 'id' => $s])
                                    ->toArray()" option-value="key" option-label="id" />
                        </div>

                        <div>
                            <x-mary-select name="status" label="Filter by Status" :options="[
                                ['key' => 'all', 'id' => 'All Statuses'],
                                ['key' => 'active', 'id' => 'Active'],
                                ['key' => 'inactive', 'id' => 'Inactive'],
                            ]" option-value="key"
                                option-label="id" placeholder="All Statuses" />
                        </div>
                    </div>

                    <div class="pt-4 border-t">
                        <h4 class="mb-4 font-medium">Export Format</h4>

                        <div class="p-4 border rounded-lg bg-base-50">
                            <p class="mb-3">The export will include the following fields:</p>

                            <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                                <div>
                                    <ul class="pl-5 text-sm list-disc">
                                        <li>Resident ID</li>
                                        <li>First Name</li>
                                        <li>Last Name</li>
                                        <li>Middle Name</li>
                                        <li>Suffix</li>
                                        <li>Birth Date</li>
                                        <li>Birthplace</li>
                                        <li>Gender</li>
                                        <li>Civil Status</li>
                                        <li>Contact Number</li>
                                    </ul>
                                </div>

                                <div>
                                    <ul class="pl-5 text-sm list-disc">
                                        <li>Email</li>
                                        <li>Occupation</li>
                                        <li>Monthly Income</li>
                                        <li>Educational Attainment</li>
                                        <li>Special Sector</li>
                                        <li>Is Registered Voter</li>
                                        <li>Is PWD</li>
                                        <li>Is Senior Citizen</li>
                                        <li>Is Solo Parent</li>
                                        <li>Is Pregnant/Lactating</li>
                                    </ul>
                                </div>

                                <div>
                                    <ul class="pl-5 text-sm list-disc">
                                        <li>Is Indigenous</li>
                                        <li>Is Active</li>
                                        <li>Date Issue</li>
                                        <li>Notes</li>
                                        <li>Address</li>
                                        <li>Barangay</li>
                                        <li>City/Municipality</li>
                                        <li>Province</li>
                                        <li>Region</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-4 border-t">
                        <x-mary-button type="submit" class="tagged-color btn-primary" icon="o-arrow-down-tray">
                            Download CSV
                        </x-mary-button>
                    </div>
                </form>
            </x-mary-card>
        </div>
    </div>
</x-app-layout>
