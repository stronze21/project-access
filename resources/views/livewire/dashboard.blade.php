<div>
    <!-- Welcome Header based on Role -->
    <x-mary-card class="p-6 mb-6 border-base">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800">
                    @role('system-administrator')
                        System Administrator Dashboard
                        @elserole('program-manager')
                        Program Management Dashboard
                        @elserole('registration-officer')
                        Registration Officer Dashboard
                        @elserole('distribution-officer')
                        Distribution Management Dashboard
                        @elserole('reporting-user')
                        Reports & Analytics Dashboard
                    @else
                        Welcome to AyudaHub
                    @endrole
                </h1>
                <p class="mt-1 text-gray-600">
                    @role('system-administrator')
                        Manage system-wide settings, users, roles, and monitor all activities.
                        @elserole('program-manager')
                        Manage aid programs, approve applications, and monitor program progress.
                        @elserole('registration-officer')
                        Register and manage residents, households, and beneficiary information.
                        @elserole('distribution-officer')
                        Manage aid distributions, verify beneficiaries, and process transactions.
                        @elserole('reporting-user')
                        Generate and view reports across all system activities.
                    @else
                        Here's an overview of system activities.
                    @endrole
                </p>
            </div>

            <!-- Quick Actions based on Role -->
            <div class="mt-4 md:mt-0">
                <div class="flex flex-wrap gap-2">
                    @role('system-administrator')
                        <x-mary-button link="{{ route('admin.users') }}" label="User Management"
                            class="tagged-color btn-primary" icon="o-user-group" />
                        <x-mary-button link="{{ route('admin.roles') }}" label="Role Management"
                            class="tagged-color btn-secondary btn-outline btn-secline" icon="o-shield-check" />
                    @endrole

                    @permission('create-programs')
                        <x-mary-button link="{{ route('programs.create') }}" label="Create Program"
                            class="tagged-color btn-primary" icon="o-plus" />
                    @endpermission

                    @permission('create-residents')
                        <x-mary-button link="{{ route('residents.create') }}" label="Register Resident"
                            class="tagged-color btn-success" icon="o-user-plus" />
                    @endpermission

                    @permission('create-distributions')
                        <x-mary-button link="{{ route('distributions.create') }}" label="Distribute Aid"
                            class="tagged-color btn-warning" icon="o-banknotes" />
                    @endpermission

                    @permission('view-reports')
                        <x-mary-button link="{{ route('report.controller') }}" label="View Reports"
                            class="tagged-color btn-info" icon="o-chart-bar" />
                    @endpermission
                </div>
            </div>
        </div>
    </x-mary-card>

    <!-- Filter Controls - Show for roles that need them -->
    @if ($canViewDistributions || $canViewReports)
        <x-mary-card class="p-4 mb-6 border rounded-lg shadow-sm bg-base border-base-100">
            <div class="flex flex-col gap-4 md:flex-row md:items-end">
                <div>
                    <x-mary-select label="Date Range" :options="$dateRanges" wire:model.live="dateRange" />
                </div>

                @if ($dateRange === 'custom')
                    <div>
                        <x-mary-datetime label="Start Date" wire:model.live="startDate" />
                    </div>
                    <div>
                        <x-mary-datetime label="End Date" wire:model.live="endDate" />
                    </div>
                @endif

                @if ($canViewPrograms && count($programs) > 0)
                    <div>
                        <x-mary-select label="Program" :options="$programs" wire:model.live="programId"
                            placeholder="All programs" placeholder-value="" />
                    </div>
                @endif

                @if ($canViewDistributions)
                    <div class="md:ml-auto">
                        <x-mary-toggle label="By Barangay" wire:model.live="byBarangay"
                            hint="{{ $byBarangay ? 'View by barangay' : 'View by city/municipality' }}" />
                    </div>
                @endif
            </div>
        </x-mary-card>
    @endif

    <!-- System Administrator Specific Section -->
    @role('system-administrator')
        @if (isset($adminStats) && $adminStats)
            <div class="mb-6">
                <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2 lg:grid-cols-4">
                    <x-mary-stat title="Total Users" value="{{ number_format($adminStats['users_count']) }}" icon="o-users"
                        class="tagged-color text-primary" />

                    <x-mary-stat title="User Roles" value="{{ number_format($adminStats['roles_count']) }}"
                        icon="o-shield-check" class="tagged-color text-secondary" />

                    <x-mary-stat title="Permissions" value="{{ number_format($adminStats['permissions_count']) }}"
                        icon="o-lock-closed" class="tagged-color text-info" />

                    <x-mary-stat title="Active Programs" value="{{ $this->summaryStats['active_programs'] ?? 0 }}"
                        icon="o-clipboard-document-list" class="tagged-color text-success" />
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <x-mary-card title="Recent User Registrations">
                        <div class="divide-y">
                            @foreach ($adminStats['recent_users'] as $user)
                                <div class="flex items-center justify-between py-3">
                                    <div>
                                        <div class="font-medium">{{ $user->name }}</div>
                                        <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        {{ $user->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-4 text-right">
                            <x-mary-button link="{{ route('admin.users') }}" label="View All Users" size="sm"
                                class="tagged-color btn-primary" />
                        </div>
                    </x-mary-card>

                    <x-mary-card title="User Role Distribution">
                        <div class="h-72" wire:ignore>
                            <canvas id="roleDistributionChart"></canvas>
                        </div>
                    </x-mary-card>
                </div>
            </div>
        @endif
    @endrole

    <!-- General Summary Stats - Visible to most roles -->
    @if ($canViewDistributions || $canViewReports)
        <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2 lg:grid-cols-4">
            <x-mary-stat title="Total Distributions"
                value="{{ number_format($this->summaryStats['total_distributions']) }}" icon="o-arrows-right-left"
                class="tagged-color text-primary" />

            <x-mary-stat title="Total Amount Distributed"
                value="₱ {{ number_format($this->summaryStats['total_amount'], 2) }}" icon="o-banknotes"
                class="tagged-color text-success" />

            <x-mary-stat title="Unique Beneficiaries"
                value="{{ number_format($this->summaryStats['unique_residents']) }}" icon="o-user"
                class="tagged-color text-info" />

            <x-mary-stat title="Households Reached"
                value="{{ number_format($this->summaryStats['unique_households']) }}" icon="o-home"
                class="tagged-color text-warning" />
        </div>
    @endif

    <!-- Charts Row - Only show for those with appropriate permissions -->
    @if ($canViewDistributions || $canViewReports)
        <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
            <!-- Distribution by Program Chart -->
            <x-mary-card title="Distribution by Program">
                <div class="h-80" wire:ignore>
                    <canvas id="programChart"></canvas>
                </div>
            </x-mary-card>

            <!-- Distribution by Location Chart -->
            <x-mary-card title="{{ $byBarangay ? 'Distribution by Barangay' : 'Distribution by City/Municipality' }}">
                <div class="h-80" wire:ignore>
                    <canvas id="locationChart"></canvas>
                </div>
            </x-mary-card>
        </div>

        <!-- Trend Chart -->
        <x-mary-card title="Distribution Trend" class="mb-6">
            <div class="h-72" wire:ignore>
                <canvas id="trendChart"></canvas>
            </div>
        </x-mary-card>
    @endif

    <!-- Program Progress and Recent Distributions -->
    <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
        <!-- Program Progress - Visible for program managers and reporting users -->
        @if ($canViewPrograms || $canViewReports)
            <x-mary-card title="Program Progress">
                <div class="space-y-4">
                    @foreach ($this->programProgress as $program)
                        <div class="p-3 border rounded-lg">
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="font-medium">{{ $program->name }}</h3>
                                <span class="text-sm text-gray-600">
                                    {{ $program->current_beneficiaries }}/{{ $program->max_beneficiaries ?? 'Unlimited' }}
                                    beneficiaries
                                </span>
                            </div>

                            @if ($program->max_beneficiaries)
                                <div class="mb-2">
                                    <div class="flex justify-between mb-1 text-xs">
                                        <span>Beneficiaries</span>
                                        <span>{{ $program->beneficiary_progress }}%</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-base-200">
                                        <div class="h-2 bg-blue-600 rounded-full"
                                            style="width: {{ $program->beneficiary_progress }}%"></div>
                                    </div>
                                </div>
                            @endif

                            @if ($program->total_budget)
                                <div>
                                    <div class="flex justify-between mb-1 text-xs">
                                        <span>Budget
                                            (₱{{ number_format($program->budget_used, 2) }}/{{ number_format($program->total_budget, 2) }})
                                        </span>
                                        <span>{{ $program->budget_progress }}%</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-base-200">
                                        <div class="h-2 bg-green-600 rounded-full"
                                            style="width: {{ $program->budget_progress }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @if (count($this->programProgress) === 0)
                        <p class="py-4 text-center text-gray-500">No active programs found</p>
                    @endif
                </div>

                @permission('create-programs')
                    <div class="mt-4 text-right">
                        <x-mary-button link="{{ route('programs.create') }}" label="Create New Program" size="sm"
                            class="tagged-color btn-primary" />
                    </div>
                @endpermission
            </x-mary-card>
        @endif

        <!-- Recent Distributions - Visible for distribution officers and reporting users -->
        @if ($canViewDistributions || $canViewReports)
            <x-mary-card title="Recent Distributions">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left">
                        <thead class="text-xs text-gray-700 uppercase bg-base-50">
                            <tr>
                                <th class="px-4 py-2">Reference #</th>
                                <th class="px-4 py-2">Beneficiary</th>
                                <th class="px-4 py-2">Program</th>
                                <th class="px-4 py-2">Date</th>
                                <th class="px-4 py-2">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->recentDistributions as $distribution)
                                <tr class="border-b bg-base hover:bg-base-50">
                                    <td class="px-4 py-2 font-medium">
                                        <a href="{{ route('distributions.show', $distribution->id) }}"
                                            class="text-blue-600 hover:underline">
                                            {{ $distribution->reference_number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-2">{{ $distribution->first_name }}
                                        {{ $distribution->last_name }}
                                    </td>
                                    <td class="px-4 py-2">{{ $distribution->program_name }}</td>
                                    <td class="px-4 py-2">
                                        {{ \Carbon\Carbon::parse($distribution->distribution_date)->format('M d, Y') }}
                                    </td>
                                    <td class="px-4 py-2">₱{{ number_format($distribution->amount, 2) }}</td>
                                </tr>
                            @endforeach

                            @if (count($this->recentDistributions) === 0)
                                <tr class="border-b bg-base">
                                    <td colspan="5" class="px-4 py-4 text-center text-gray-500">No recent
                                        distributions
                                        found</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-right">
                    <x-mary-button link="{{ route('distributions.index') }}" label="View All" size="sm"
                        class="tagged-color btn-primary" />
                </div>
            </x-mary-card>
        @endif
    </div>

    <!-- Top Distribution Batches - Only visible for distribution officers and reporting users -->
    @if ($canViewDistributions || $canViewReports)
        <x-mary-card title="Top Distribution Batches">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="text-xs text-gray-700 uppercase bg-base-50">
                        <tr>
                            <th class="px-4 py-2">Batch Number</th>
                            <th class="px-4 py-2">Program</th>
                            <th class="px-4 py-2">Location</th>
                            <th class="px-4 py-2">Date</th>
                            <th class="px-4 py-2">Beneficiaries</th>
                            <th class="px-4 py-2">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->topBatches as $batch)
                            <tr class="border-b bg-base hover:bg-base-50">
                                <td class="px-4 py-2 font-medium">{{ $batch->batch_number }}</td>
                                <td class="px-4 py-2">{{ $batch->program_name }}</td>
                                <td class="px-4 py-2">{{ $batch->location }}</td>
                                <td class="px-4 py-2">
                                    {{ \Carbon\Carbon::parse($batch->batch_date)->format('M d, Y') }}
                                </td>
                                <td class="px-4 py-2">{{ $batch->actual_beneficiaries }}</td>
                                <td class="px-4 py-2">₱{{ number_format($batch->total_amount, 2) }}</td>
                            </tr>
                        @endforeach

                        @if (count($this->topBatches) === 0)
                            <tr class="border-b bg-base">
                                <td colspan="6" class="px-4 py-4 text-center text-gray-500">No distribution batches
                                    found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            @permission('manage-distribution-batches')
                <div class="mt-4 text-right">
                    <x-mary-button link="{{ route('distributions.batches') }}" label="Manage Batches" size="sm"
                        class="tagged-color btn-primary" />
                </div>
            @endpermission
        </x-mary-card>
    @endif

    <!-- Registration Officer Section - Only visible for registration officers -->
    @role('registration-officer')
        <div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
            <x-mary-card title="Recent Resident Registrations">
                <div class="space-y-4">
                    @foreach ($recentResidents ?? [] as $resident)
                        <div class="p-3 border rounded-lg">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h3 class="font-medium">{{ $resident->last_name }}, {{ $resident->first_name }}
                                        {{ $resident->middle_name }}</h3>
                                    <div class="text-sm text-gray-500">{{ $resident->barangay }},
                                        {{ $resident->city_municipality }}</div>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($resident->created_at)->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if (empty($recentResidents) || count($recentResidents) === 0)
                        <p class="py-4 text-center text-gray-500">No recent registrations found</p>
                    @endif
                </div>

                <div class="mt-4 text-right">
                    <x-mary-button link="{{ route('residents.create') }}" label="Register New Resident" size="sm"
                        class="tagged-color btn-primary" />
                </div>
            </x-mary-card>

            <x-mary-card title="Household Statistics">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 border rounded-lg">
                            <div class="text-3xl font-bold">{{ $householdStats['total'] ?? 0 }}</div>
                            <div class="text-sm text-gray-500">Total Households</div>
                        </div>
                        <div class="p-4 border rounded-lg">
                            <div class="text-3xl font-bold">{{ $householdStats['recent'] ?? 0 }}</div>
                            <div class="text-sm text-gray-500">New This Month</div>
                        </div>
                    </div>

                    <div class="p-3 border rounded-lg">
                        <h3 class="mb-2 font-medium">Top Barangays by Households</h3>
                        <div class="space-y-2">
                            @foreach ($householdStats['byBarangay'] ?? [] as $barangayStat)
                                <div class="flex items-center justify-between">
                                    <div>{{ $barangayStat->barangay }}</div>
                                    <div class="font-medium">{{ $barangayStat->count }}</div>
                                </div>
                                <div class="w-full h-2 rounded-full bg-base-200">
                                    <div class="h-2 bg-blue-600 rounded-full"
                                        style="width: {{ $barangayStat->percentage }}%"></div>
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
        </div>
    @endrole
</div>

@script
    <script>
        // Distribution by Program Chart
        document.addEventListener('livewire:initialized', function() {
            const renderProgramChart = (data) => {
                const ctx = document.getElementById('programChart');
                if (!ctx) return; // Safety check

                const chartCtx = ctx.getContext('2d');

                // Check if chart instance exists before destroying
                if (window.programChart instanceof Chart) {
                    window.programChart.destroy();
                }

                // Prepare data
                const labels = data.map(item => item.program_name);
                const counts = data.map(item => item.distribution_count);
                const amounts = data.map(item => item.total_amount);

                // Create chart
                window.programChart = new Chart(chartCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                                label: 'Number of Distributions',
                                data: counts,
                                backgroundColor: 'rgba(59, 130, 246, 0.6)',
                                borderColor: 'rgba(59, 130, 246, 1)',
                                borderWidth: 1,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Total Amount (₱)',
                                data: amounts,
                                backgroundColor: 'rgba(16, 185, 129, 0.6)',
                                borderColor: 'rgba(16, 185, 129, 1)',
                                borderWidth: 1,
                                type: 'line',
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Distributions'
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false
                                },
                                title: {
                                    display: true,
                                    text: 'Amount (₱)'
                                }
                            }
                        }
                    }
                });
            };

            // Initial render with safety check
            if (document.getElementById('programChart')) {
                renderProgramChart(@json($this->distributionByProgram ?? []));
            }

            // Listen for updates
            Livewire.on('updateCharts', (stats) => {
                if (document.getElementById('programChart')) {
                    renderProgramChart(stats.distributionByProgram ?? []);
                }
            });

            // Update on filter changes
            @this.watch('dateRange', value => {
                setTimeout(() => {
                    if (document.getElementById('programChart')) {
                        renderProgramChart(@json($this->distributionByProgram ?? []));
                    }
                }, 300);
            });

            @this.watch('programId', value => {
                setTimeout(() => {
                    if (document.getElementById('programChart')) {
                        renderProgramChart(@json($this->distributionByProgram ?? []));
                    }
                }, 300);
            });
        });

        // Distribution by Location Chart
        document.addEventListener('livewire:initialized', function() {
            const renderLocationChart = (data) => {
                const ctx = document.getElementById('locationChart');
                if (!ctx) return; // Safety check

                const chartCtx = ctx.getContext('2d');

                // Check if chart instance exists before destroying
                if (window.locationChart instanceof Chart) {
                    window.locationChart.destroy();
                }

                // Prepare data
                const labels = data.map(item => item.location_name || 'Unknown');
                const counts = data.map(item => item.distribution_count);
                const households = data.map(item => item.household_count);

                // Create chart
                window.locationChart = new Chart(chartCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                                label: 'Distributions',
                                data: counts,
                                backgroundColor: 'rgba(249, 115, 22, 0.6)',
                                borderColor: 'rgba(249, 115, 22, 1)',
                                borderWidth: 1,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Households Reached',
                                data: households,
                                backgroundColor: 'rgba(139, 92, 246, 0.6)',
                                borderColor: 'rgba(139, 92, 246, 1)',
                                borderWidth: 1,
                                type: 'line',
                                yAxisID: 'y'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Count'
                                }
                            }
                        }
                    }
                });
            };

            // Initial render with safety check
            if (document.getElementById('locationChart')) {
                renderLocationChart(@json($this->distributionByLocation ?? []));
            }

            // Listen for updates
            @this.watch('byBarangay', value => {
                setTimeout(() => {
                    if (document.getElementById('locationChart')) {
                        renderLocationChart(@json($this->distributionByLocation ?? []));
                    }
                }, 300);
            });

            @this.watch('dateRange', value => {
                setTimeout(() => {
                    if (document.getElementById('locationChart')) {
                        renderLocationChart(@json($this->distributionByLocation ?? []));
                    }
                }, 300);
            });

            @this.watch('programId', value => {
                setTimeout(() => {
                    if (document.getElementById('locationChart')) {
                        renderLocationChart(@json($this->distributionByLocation ?? []));
                    }
                }, 300);
            });
        });

        // Distribution Trend Chart
        document.addEventListener('livewire:initialized', function() {
            const renderTrendChart = (data) => {
                const ctx = document.getElementById('trendChart');
                if (!ctx) return; // Safety check

                const chartCtx = ctx.getContext('2d');

                // Check if chart instance exists before destroying
                if (window.trendChart instanceof Chart) {
                    window.trendChart.destroy();
                }

                // Prepare data
                const labels = data.map(item => new Date(item.distribution_day).toLocaleDateString());
                const counts = data.map(item => item.distribution_count);
                const amounts = data.map(item => item.daily_amount);

                // Create chart
                window.trendChart = new Chart(chartCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                                label: 'Number of Distributions',
                                data: counts,
                                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                                borderColor: 'rgba(59, 130, 246, 1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.3,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Daily Amount (₱)',
                                data: amounts,
                                backgroundColor: 'rgba(16, 185, 129, 0.2)',
                                borderColor: 'rgba(16, 185, 129, 1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.3,
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Number of Distributions'
                                }
                            },
                            y1: {
                                beginAtZero: true,
                                position: 'right',
                                grid: {
                                    drawOnChartArea: false
                                },
                                title: {
                                    display: true,
                                    text: 'Amount (₱)'
                                }
                            }
                        }
                    }
                });
            };

            // Initial render with safety check
            if (document.getElementById('trendChart')) {
                renderTrendChart(@json($this->distributionTrend ?? []));
            }

            // Update on filter changes
            @this.watch('dateRange', value => {
                setTimeout(() => {
                    if (document.getElementById('trendChart')) {
                        renderTrendChart(@json($this->distributionTrend ?? []));
                    }
                }, 300);
            });

            @this.watch('programId', value => {
                setTimeout(() => {
                    if (document.getElementById('trendChart')) {
                        renderTrendChart(@json($this->distributionTrend ?? []));
                    }
                }, 300);
            });
        });

        // Role Distribution Chart (Admin only)
        document.addEventListener('livewire:initialized', function() {
            const renderRoleDistributionChart = (data) => {
                const ctx = document.getElementById('roleDistributionChart');
                if (!ctx) return; // Safety check

                const chartCtx = ctx.getContext('2d');

                // Check if chart instance exists before destroying
                if (window.roleDistributionChart instanceof Chart) {
                    window.roleDistributionChart.destroy();
                }

                // Prepare data
                const labels = data.map(item => item.name);
                const counts = data.map(item => item.count);
                const colors = [
                    'rgba(59, 130, 246, 0.8)', // Blue
                    'rgba(16, 185, 129, 0.8)', // Green
                    'rgba(249, 115, 22, 0.8)', // Orange
                    'rgba(139, 92, 246, 0.8)', // Purple
                    'rgba(236, 72, 153, 0.8)' // Pink
                ];

                // Create chart
                window.roleDistributionChart = new Chart(chartCtx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: counts,
                            backgroundColor: colors,
                            borderColor: colors.map(color => color.replace('0.8', '1')),
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'right',
                            }
                        }
                    }
                });
            };

            // Initial render only for admin
            if (document.getElementById('roleDistributionChart')) {
                renderRoleDistributionChart(@json($adminStats['role_distribution'] ?? []));
            }
        });
    </script>
@endscript
