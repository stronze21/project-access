<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Import Residents') }}
            </h2>
            <div class="flex space-x-2">
                <x-mary-button link="{{ route('residents.legacy-import.index') }}"
                    class="tagged-color btn-secondary btn-outline btn-secline" icon="o-server-stack">
                    Import Manager
                </x-mary-button>
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
                @if (session('error'))
                    <x-mary-alert class="tagged-color alert-error" class="mb-4">
                        <x-slot name="title">Import Failed</x-slot>
                        {{ session('error') }}
                    </x-mary-alert>
                @endif

                @if (session('warning'))
                    <x-mary-alert class="tagged-color alert-warning" class="mb-4">
                        <x-slot name="title">Import Completed with Warnings</x-slot>
                        {{ session('warning') }}
                    </x-mary-alert>

                    @if (session('importStats'))
                        <div class="p-4 mb-4 border rounded-lg bg-amber-50">
                            <h3 class="mb-2 text-lg font-medium text-amber-800">Import Statistics</h3>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
                                <div class="p-2 text-center border rounded bg-base">
                                    <p class="text-sm text-gray-600">Total Records</p>
                                    <p class="text-xl font-semibold">{{ session('importStats')['total'] }}</p>
                                </div>
                                <div class="p-2 text-center border rounded bg-green-50">
                                    <p class="text-sm text-gray-600">Created</p>
                                    <p class="text-xl font-semibold text-green-700">
                                        {{ session('importStats')['created'] }}</p>
                                </div>
                                <div class="p-2 text-center border rounded bg-blue-50">
                                    <p class="text-sm text-gray-600">Updated</p>
                                    <p class="text-xl font-semibold text-blue-700">
                                        {{ session('importStats')['updated'] }}</p>
                                </div>
                                <div class="p-2 text-center border rounded bg-gray-50">
                                    <p class="text-sm text-gray-600">Unchanged</p>
                                    <p class="text-xl font-semibold text-gray-700">
                                        {{ session('importStats')['unchanged'] ?? 0 }}</p>
                                </div>
                                <div class="p-2 text-center border rounded bg-red-50">
                                    <p class="text-sm text-gray-600">Failed</p>
                                    <p class="text-xl font-semibold text-red-700">{{ session('importStats')['failed'] }}
                                    </p>
                                </div>
                            </div>

                            @if (!empty(session('importStats')['errors']))
                                <div class="mt-4">
                                    <h4 class="mb-2 font-medium text-red-700">Errors</h4>
                                    <div
                                        class="p-2 overflow-auto text-sm text-red-700 border border-red-200 rounded-lg max-h-64 bg-red-50">
                                        <ul class="list-disc list-inside">
                                            @foreach (session('importStats')['errors'] as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                @endif

                @if (session('success'))
                    <x-mary-alert class="tagged-color alert-success mb-4">
                        {{ session('success') }}
                    </x-mary-alert>
                @endif

                @if ($preview)
                    <div class="mb-8">
                        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                            <div>
                                <h3 class="text-xl font-semibold">Import Preview</h3>
                                <p class="text-sm text-gray-600">{{ $pendingFilename }} — no records have been saved yet.</p>
                            </div>
                            <div class="flex gap-2">
                                <form action="{{ route('residents.import.cancel') }}" method="POST">
                                    @csrf
                                    <x-mary-button type="submit" class="btn-outline">Cancel</x-mary-button>
                                </form>
                                <form action="{{ route('residents.import.confirm') }}" method="POST">
                                    @csrf
                                    <x-mary-button type="submit" class="tagged-color btn-primary"
                                        icon="o-check" :disabled="$preview['failed'] > 0 || !empty($preview['errors'])">
                                        Confirm Import
                                    </x-mary-button>
                                </form>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 my-5 md:grid-cols-5">
                            <div class="p-3 text-center border rounded-lg">
                                <div class="text-sm text-gray-500">Total</div>
                                <div class="text-2xl font-semibold">{{ $preview['total'] }}</div>
                            </div>
                            <div class="p-3 text-center border border-green-200 rounded-lg bg-green-50">
                                <div class="text-sm text-green-700">New</div>
                                <div class="text-2xl font-semibold text-green-700">{{ $preview['created'] }}</div>
                            </div>
                            <div class="p-3 text-center border border-blue-200 rounded-lg bg-blue-50">
                                <div class="text-sm text-blue-700">Updates</div>
                                <div class="text-2xl font-semibold text-blue-700">{{ $preview['updated'] }}</div>
                            </div>
                            <div class="p-3 text-center border rounded-lg bg-gray-50">
                                <div class="text-sm text-gray-700">Unchanged</div>
                                <div class="text-2xl font-semibold text-gray-700">{{ $preview['unchanged'] }}</div>
                            </div>
                            <div class="p-3 text-center border border-red-200 rounded-lg bg-red-50">
                                <div class="text-sm text-red-700">Errors</div>
                                <div class="text-2xl font-semibold text-red-700">{{ $preview['failed'] }}</div>
                            </div>
                        </div>

                        @if (!empty($preview['errors']))
                            <div class="p-3 mb-4 text-sm text-red-800 border border-red-200 rounded-lg bg-red-50">
                                @foreach ($preview['errors'] as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="overflow-x-auto border rounded-lg max-h-[32rem]">
                            <table class="table w-full">
                                <thead class="sticky top-0 bg-base-100">
                                    <tr>
                                        <th>Row</th>
                                        <th>Resident ID</th>
                                        <th>Name</th>
                                        <th>Birth Date</th>
                                        <th>Address</th>
                                        <th>Barangay</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($preview['rows'] as $row)
                                        <tr>
                                            <td>{{ $row['row'] }}</td>
                                            <td>{{ $row['resident_id'] ?: 'Auto-generated' }}</td>
                                            <td>{{ $row['name'] }}</td>
                                            <td>{{ $row['birth_date'] }}</td>
                                            <td>{{ trim($row['address'], ' ,') !== '' ? $row['address'] : '—' }}</td>
                                            <td>{{ $row['barangay'] }}</td>
                                            <td>
                                                @if ($row['status'] === 'update')
                                                    <span class="badge badge-info">Update</span>
                                                    <div class="mt-1 text-xs text-blue-700">
                                                        Changes: {{ implode(', ', array_map(fn ($field) => str_replace('_', ' ', $field), $row['changes'])) }}
                                                    </div>
                                                @elseif ($row['status'] === 'new')
                                                    <span class="badge badge-success">New</span>
                                                @elseif ($row['status'] === 'unchanged')
                                                    <span class="badge badge-ghost">Unchanged</span>
                                                @else
                                                    <span class="badge badge-error">Error</span>
                                                    <div class="mt-1 text-xs text-red-700">
                                                        {{ implode(' ', $row['errors']) }}
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-8 md:grid-cols-5">
                    <div class="md:col-span-3">
                        <h3 class="mb-4 text-xl font-semibold">Upload CSV File</h3>

                        <form action="{{ route('residents.import.process') }}" method="POST"
                            enctype="multipart/form-data" class="space-y-4">
                            @csrf

                            <div>
                                <label for="csv_file" class="block text-sm font-medium text-gray-700">Choose CSV
                                    File</label>
                                <div class="mt-1">
                                    <input type="file" id="csv_file" name="csv_file" required
                                        class="block w-full text-sm text-gray-900 border border-gray-300 rounded-md shadow-sm file-input file-input-primary focus:ring-primary-500 focus:border-primary-500"
                                        accept=".csv,.txt">
                                </div>
                                <p class="mt-1 text-sm text-gray-500">File format: .csv, Maximum size: 5MB</p>

                                @error('csv_file')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="pt-4 mt-4 border-t">
                                <x-mary-button type="submit" class="tagged-color btn-primary" icon="o-arrow-up-tray">
                                    Upload and Preview
                                </x-mary-button>

                                <x-mary-button link="{{ route('residents.import.template') }}"
                                    class="tagged-color btn-secondary btn-outline btn-secline" class="ml-2"
                                    icon="o-arrow-down-tray">
                                    Download Template
                                </x-mary-button>
                            </div>
                        </form>
                    </div>

                    <div class="p-6 border border-blue-200 rounded-lg md:col-span-2 bg-blue-50">
                        <h3 class="mb-3 text-lg font-semibold text-blue-800">Import Instructions</h3>

                        <div class="prose-sm prose text-blue-900 max-w-none">
                            <ul class="space-y-2">
                                <li>Download the CSV template first to ensure correct formatting.</li>
                                <li>Required fields: <strong>first_name, last_name, birth_date, gender, address,
                                        barangay</strong></li>
                                <li>For boolean fields (is_pwd, etc.), use "Yes" or "No" values.</li>
                                <li>Date format should be YYYY-MM-DD (e.g., 1990-01-15).</li>
                                <li>If a resident_id is provided, the system will update the existing record.</li>
                                <li>If the resident_id does not exist or is blank, a new resident will be created.</li>
                                <li>Review each New, Update, or Error status before confirming the import.</li>
                                <li>The system will try to match households by address and barangay.</li>
                                <li>For gender, use "male", "female", or "other".</li>
                                <li>For civil_status, use "single", "married", "widowed", "divorced", "separated", or
                                    "other".</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </x-mary-card>
        </div>
    </div>
</x-app-layout>
