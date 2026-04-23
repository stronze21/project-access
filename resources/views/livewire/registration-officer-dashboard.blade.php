<div>
    <!-- Welcome Header -->
    <x-mary-card class="p-6 mb-6 border-base">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">
                    Registration Officer Dashboard
                </h1>
                <p class="mt-1 text-gray-600">
                    Register and manage residents, households, and beneficiary information.
                </p>
            </div>

            <!-- Quick Actions -->
            <div class="mt-4 md:mt-0">
                <div class="flex flex-wrap gap-2">
                    <x-mary-button link="{{ route('residents.create') }}" label="Register Resident"
                        class="tagged-color btn-primary" icon="o-user-plus" />
                    <x-mary-button link="{{ route('households.create') }}" label="Register Household"
                        class="tagged-color btn-success" icon="o-home-modern" />
                    <x-mary-button link="{{ route('scanner') }}" label="QR Scanner" class="tagged-color btn-warning"
                        icon="o-qr-code" />
                </div>
            </div>
        </div>
    </x-mary-card>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2 lg:grid-cols-4">
        <x-mary-stat title="Total Residents" value="{{ number_format($residentStats['total']) }}" icon="o-users"
            class="tagged-color text-primary" />

        <x-mary-stat title="Total Households" value="{{ number_format($householdStats['total']) }}" icon="o-home"
            class="tagged-color text-success" />

        <x-mary-stat title="New Registrations" value="{{ number_format($residentStats['recent']) }}" icon="o-user-plus"
            class="tagged-color text-info" />

        <x-mary-stat title="Incomplete Records" value="{{ number_format($residentStats['incomplete']) }}"
            icon="o-exclamation-triangle" class="tagged-color text-warning" />
    </div>

    <!-- Demographics Section -->
    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
        <!-- Household Statistics -->
        <x-mary-card title="Household Statistics">
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="p-4 border rounded-lg bg-base-50">
                        <div class="text-3xl font-bold">{{ number_format($householdStats['total']) }}</div>
                        <div class="text-sm text-gray-500">Total Households</div>
                    </div>
                    <div class="p-4 border rounded-lg bg-base-50">
                        <div class="text-3xl font-bold">{{ number_format($householdStats['recent']) }}</div>
                        <div class="text-sm text-gray-500">New This Month</div>
                    </div>
                </div>

                <div class="p-3 border rounded-lg">
                    <h3 class="mb-2 font-medium">Top Barangays by Households</h3>
                    <div class="space-y-3">
                        @foreach ($householdStats['byBarangay'] as $barangayStat)
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <div>{{ $barangayStat->barangay }}</div>
                                    <div class="font-medium">{{ $barangayStat->count }}</div>
                                </div>
                                <div class="w-full h-2 rounded-full bg-base-200">
                                    <div class="h-2 bg-blue-600 rounded-full"
                                        style="width: {{ $barangayStat->percentage }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="mt-4 text-right">
                <x-mary-button link="{{ route('households.index') }}" label="View All Households" size="sm"
                    class="tagged-color btn-primary" />
            </div>
        </x-mary-card>

        <!-- Population Demographics -->
        <x-mary-card title="Population Demographics">
            <div class="h-72" wire:ignore>
                <canvas id="demographicsChart"></canvas>
            </div>

            <div class="grid grid-cols-3 gap-4 mt-4">
                <div class="p-3 text-center border rounded-lg">
                    <div class="text-2xl font-bold text-blue-600">
                        {{ number_format($residentStats['byAgeGroup']['children']) }}</div>
                    <div class="text-sm text-gray-500">Children<br>(0-17)</div>
                </div>
                <div class="p-3 text-center border rounded-lg">
                    <div class="text-2xl font-bold text-green-600">
                        {{ number_format($residentStats['byAgeGroup']['adults']) }}</div>
                    <div class="text-sm text-gray-500">Adults<br>(18-59)</div>
                </div>
                <div class="p-3 text-center border rounded-lg">
                    <div class="text-2xl font-bold text-amber-600">
                        {{ number_format($residentStats['byAgeGroup']['seniors']) }}</div>
                    <div class="text-sm text-gray-500">Seniors<br>(60+)</div>
                </div>
            </div>
        </x-mary-card>
    </div>

    <!-- Quick Tools -->
    <x-mary-card title="Quick Tools">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div class="p-4 border rounded-lg hover:bg-base-100">
                <div class="flex items-center mb-2">
                    <div class="p-2 mr-3 text-blue-600 bg-blue-100 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium">ID Generation</h3>
                </div>
                <p class="mb-3 text-sm text-gray-500">Generate identification cards and QR codes for residents</p>
                <div class="text-right">
                    <x-mary-button link="#" wire:click="generateQrCodes" label="Generate IDs" size="sm"
                        class="tagged-color btn-primary" />
                </div>
            </div>

            <div class="p-4 border rounded-lg hover:bg-base-100">
                <div class="flex items-center mb-2">
                    <div class="p-2 mr-3 rounded-full bg-amber-100 text-amber-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium">Data Validation</h3>
                </div>
                <p class="mb-3 text-sm text-gray-500">Identify incomplete or potentially duplicate records</p>
                <div class="text-right">
                    <x-mary-button link="#" wire:click="checkDuplicates" label="Check Records" size="sm"
                        class="tagged-color btn-primary" />
                </div>
            </div>

            <div class="p-4 border rounded-lg hover:bg-base-100">
                <div class="flex items-center mb-2">
                    <div class="p-2 mr-3 text-green-600 bg-green-100 rounded-full">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium">Reports</h3>
                </div>
                <p class="mb-3 text-sm text-gray-500">Generate resident registration reports and statistics</p>
                <div class="text-right">
                    <x-mary-button link="{{ route('report.controller') }}" label="View Reports" size="sm"
                        class="tagged-color btn-primary" />
                </div>
            </div>
        </div>
    </x-mary-card>


    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 gap-6 mt-6 lg:grid-cols-2">
        <!-- Recent Registrations -->
        <x-mary-card title="Recent Resident Registrations">
            <div class="space-y-4">
                @foreach ($recentResidents as $resident)
                    <div class="p-3 border rounded-lg hover:bg-base-100">
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-medium">
                                    <a href="{{ route('residents.show', $resident->id) }}"
                                        class="hover:text-blue-600">
                                        {{ $resident->last_name }}, {{ $resident->first_name }}
                                        {{ $resident->middle_name }}
                                    </a>
                                </h3>
                                <div class="text-sm text-gray-500">
                                    <span>{{ $resident->barangay }}, {{ $resident->city_municipality }}</span>
                                    <span class="mx-1">•</span>
                                    <span>{{ $resident->gender }}
                                        {{ $resident->date_of_birth ? '• ' . \Carbon\Carbon::parse($resident->date_of_birth)->age . ' years' : '' }}</span>
                                </div>
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ \Carbon\Carbon::parse($resident->created_at)->format('M d, Y') }}
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 mt-2">
                            <x-mary-button link="{{ route('residents.edit', $resident->id) }}" label="Edit"
                                size="xs" class="tagged-color btn-primary" icon="o-pencil" />

                            <x-mary-button link="{{ route('qrcode.download', $resident->id) }}" label="QR Code"
                                size="xs" class="tagged-color btn-secondary btn-outline btn-secline"
                                icon="o-qr-code" />
                        </div>
                    </div>
                @endforeach

                @if (count($recentResidents) === 0)
                    <p class="py-4 text-center text-gray-500">No recent registrations found</p>
                @endif
            </div>

            <div class="mt-4 text-right">
                <x-mary-button link="{{ route('residents.index') }}" label="View All Residents" size="sm"
                    class="tagged-color btn-primary" />
            </div>
        </x-mary-card>
    </div>
</div>

@script
    <script>
        document.addEventListener('livewire:initialized', function() {
            const renderDemographicsChart = () => {
                const ctx = document.getElementById('demographicsChart');
                if (!ctx) return; // Safety check

                const chartCtx = ctx.getContext('2d');

                // Check if chart instance exists before destroying
                if (window.demographicsChart instanceof Chart) {
                    window.demographicsChart.destroy();
                }

                // Data from PHP
                const ageGroups = [
                    {{ $residentStats['byAgeGroup']['children'] }},
                    {{ $residentStats['byAgeGroup']['adults'] }},
                    {{ $residentStats['byAgeGroup']['seniors'] }}
                ];

                // Create chart
                window.demographicsChart = new Chart(chartCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Children (0-17)', 'Adults (18-59)', 'Seniors (60+)'],
                        datasets: [{
                            data: ageGroups,
                            backgroundColor: [
                                'rgba(59, 130, 246, 0.8)', // Blue
                                'rgba(16, 185, 129, 0.8)', // Green
                                'rgba(245, 158, 11, 0.8)' // Amber
                            ],
                            borderColor: [
                                'rgba(59, 130, 246, 1)',
                                'rgba(16, 185, 129, 1)',
                                'rgba(245, 158, 11, 1)'
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                            }
                        }
                    }
                });
            };

            // Render chart if element exists
            if (document.getElementById('demographicsChart')) {
                renderDemographicsChart();
            }
        });
    </script>
@endscript
