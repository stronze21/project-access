<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Reports</h1>
        <p class="mt-1 text-sm text-gray-600">Generate and analyze various reports for AyudaPortal</p>
    </div>

    <!-- Report Control Panel -->
    <x-mary-card class="mb-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Report Type Selection -->
            <div>
                <h3 class="mb-3 text-sm font-medium text-gray-500">Report Type</h3>

                <div class="space-y-2">
                    <x-mary-button id="report-distributions" name="reportType" value="distributions"
                        label="Distributions Report" wire:model.live="reportType"
                        wire:click="changeReportType('distributions')" hint="Analysis of all aid distributions" />

                    <x-mary-button id="report-programs" name="reportType" value="programs" label="Programs Report"
                        wire:model.live="reportType" wire:click="changeReportType('programs')"
                        hint="Performance analysis of ayuda programs" />

                    <x-mary-button id="report-residents" name="reportType" value="residents"
                        label="Beneficiaries Report" wire:model.live="reportType"
                        wire:click="changeReportType('residents')" hint="Analysis of aid recipients" />

                    <x-mary-button id="report-barangays" name="reportType" value="barangays" label="Geographic Report"
                        wire:model.live="reportType" wire:click="changeReportType('barangays')"
                        hint="Analysis by location" />
                </div>
            </div>

            <!-- Report Filters -->
            <div>
                <h3 class="mb-3 text-sm font-medium text-gray-500">Report Filters</h3>

                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <x-mary-datetime label="From Date" wire:model="dateFrom" />
                        <x-mary-datetime label="To Date" wire:model="dateTo" />
                    </div>

                    <div>
                        <x-mary-select label="Program" wire:model="program" :options="$programs" placeholder="All programs"
                            placeholder-value="" />
                    </div>

                    <!-- Include the address selector component -->
                    <livewire:address-selector />

                    <div>
                        <x-mary-select label="Status" wire:model="status" :options="[
                            ['key' => 'distributed', 'id' => 'Distributed'],
                            ['key' => 'pending', 'id' => 'Pending'],
                            ['key' => 'verified', 'id' => 'Verified'],
                            ['key' => 'all', 'id' => 'All Statuses'],
                        ]" option-value="key"
                            option-label="id" />

                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end mt-6 space-x-3">
            <x-mary-button wire:click="exportCsv" class="tagged-color btn-secondary btn-outline btn-secline"
                icon="o-document-arrow-down" wire:loading.attr="disabled" wire:loading.class="opacity-75">
                <span wire:loading.remove wire:target="exportCsv">Export CSV</span>
                <span wire:loading wire:target="exportCsv">Exporting...</span>
            </x-mary-button>

            <x-mary-button wire:click="generateReport" wire:loading.attr="disabled" wire:loading.class="opacity-75">
                <span wire:loading.remove wire:target="generateReport">Generate Report</span>
                <span wire:loading wire:target="generateReport">Generating...</span>
            </x-mary-button>
        </div>
    </x-mary-card>

    <!-- Export Success Message -->
    @if (session()->has('export_file'))
        <div class="mb-6">
            <x-mary-alert icon="o-document-arrow-down" class="tagged-color alert-success" title="Export Ready">
                Your report has been exported successfully.
                <x-slot:actions>
                    <a href="{{ route('report.export.downloads', ['file' => session('export_file')]) }}"
                        class="underline">
                        Download CSV
                    </a>
                </x-slot:actions>
            </x-mary-alert>
        </div>
    @endif

    <!-- Report Results -->
    @if (!empty($summaryData))
        <!-- Summary Cards -->
        <div class="mb-6">
            <h2 class="mb-4 text-xl font-semibold text-gray-900">Report Summary</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @if ($reportType === 'distributions')
                    <x-mary-stat title="Total Distributions"
                        value="{{ number_format($summaryData['total_distributions']) }}" icon="o-arrow-right-circle"
                        class="tagged-color text-primary" />

                    <x-mary-stat title="Total Amount" value="₱ {{ number_format($summaryData['total_amount'], 2) }}"
                        icon="o-banknotes" class="tagged-color text-success" />

                    <x-mary-stat title="Unique Beneficiaries"
                        value="{{ number_format($summaryData['unique_beneficiaries']) }}" icon="o-user"
                        class="tagged-color text-info" />

                    <x-mary-stat title="Households Reached"
                        value="{{ number_format($summaryData['unique_households']) }}" icon="o-home"
                        class="tagged-color text-warning" />
                @elseif($reportType === 'programs')
                    <x-mary-stat title="Programs" value="{{ number_format($summaryData['total_programs']) }}"
                        icon="o-clipboard-document-list" class="tagged-color text-primary" />

                    <x-mary-stat title="Distributions" value="{{ number_format($summaryData['total_distributions']) }}"
                        icon="o-arrow-right-circle" class="tagged-color text-success" />

                    <x-mary-stat title="Total Amount" value="₱ {{ number_format($summaryData['total_amount'], 2) }}"
                        icon="o-banknotes" class="tagged-color text-warning" />

                    <x-mary-stat title="Beneficiaries"
                        value="{{ number_format($summaryData['unique_beneficiaries']) }}" icon="o-user"
                        class="tagged-color text-info" />
                @elseif($reportType === 'residents')
                    <x-mary-stat title="Total Beneficiaries"
                        value="{{ number_format($summaryData['total_beneficiaries']) }}" icon="o-user"
                        class="tagged-color text-primary" />

                    <x-mary-stat title="Total Distributions"
                        value="{{ number_format($summaryData['total_distributions']) }}" icon="o-arrow-right-circle"
                        class="tagged-color text-success" />

                    <x-mary-stat title="Total Amount" value="₱ {{ number_format($summaryData['total_amount'], 2) }}"
                        icon="o-banknotes" class="tagged-color text-warning" />

                    <x-mary-stat title="Average per Beneficiary"
                        value="₱ {{ number_format($summaryData['average_per_beneficiary'], 2) }}" icon="o-calculator"
                        class="tagged-color text-info" />
                @elseif($reportType === 'barangays')
                    <x-mary-stat title="Barangays" value="{{ number_format($summaryData['total_barangays']) }}"
                        icon="o-map-pin" class="tagged-color text-primary" />

                    <x-mary-stat title="Distributions"
                        value="{{ number_format($summaryData['total_distributions']) }}" icon="o-arrow-right-circle"
                        class="tagged-color text-success" />

                    <x-mary-stat title="Total Amount" value="₱ {{ number_format($summaryData['total_amount'], 2) }}"
                        icon="o-banknotes" class="tagged-color text-warning" />

                    <x-mary-stat title="Households Reached"
                        value="{{ number_format($summaryData['total_households_reached']) }}" icon="o-home"
                        class="tagged-color text-info" />
                @endif
            </div>
        </div>

        <!-- Charts -->
        {{-- <div class="mb-6">
            <h2 class="mb-4 text-xl font-semibold text-gray-900">
                Data Visualization
            </h2>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                @if ($reportType === 'distributions')
                    <!-- Distribution by Date Chart -->
                    <x-mary-card title="Distribution by Date">
                        <div class="h-80" wire:ignore>
                            <canvas id="distributionByDateChart"></canvas>
                        </div>
                    </x-mary-card>

                    <!-- Distribution by Program Chart -->
                    <x-mary-card title="Distribution by Program">
                        <div class="h-80" wire:ignore>
                            <canvas id="distributionByProgramChart"></canvas>
                        </div>
                    </x-mary-card>
                @elseif($reportType === 'programs')
                    <!-- Distributions by Program Chart -->
                    <x-mary-card title="Distributions by Program">
                        <div class="h-80" wire:ignore>
                            <canvas id="distributionsByProgramChart"></canvas>
                        </div>
                    </x-mary-card>

                    <!-- Amount by Program Chart -->
                    <x-mary-card title="Amount by Program">
                        <div class="h-80" wire:ignore>
                            <canvas id="amountByProgramChart"></canvas>
                        </div>
                    </x-mary-card>
                @elseif($reportType === 'residents')
                    <!-- Beneficiaries by Demographic Chart -->
                    <x-mary-card title="Beneficiaries by Demographic">
                        <div class="h-80" wire:ignore>
                            <canvas id="beneficiariesByDemographicChart"></canvas>
                        </div>
                    </x-mary-card>
                @elseif($reportType === 'barangays')
                    <!-- Distributions by Barangay Chart -->
                    <x-mary-card title="Distributions by Barangay">
                        <div class="h-80" wire:ignore>
                            <canvas id="distributionsByBarangayChart"></canvas>
                        </div>
                    </x-mary-card>

                    <!-- Household Coverage by Barangay Chart -->
                    <x-mary-card title="Household Coverage by Barangay">
                        <div class="h-80" wire:ignore>
                            <canvas id="coverageByBarangayChart"></canvas>
                        </div>
                    </x-mary-card>
                @endif
            </div>
        </div> --}}

        <!-- Detailed Report Data -->
        <div class="mb-6">
            <h2 class="mb-4 text-xl font-semibold text-gray-900">
                Detailed Report Data
            </h2>

            <x-mary-card>
                <div class="overflow-x-auto">
                    @if ($reportType === 'distributions')
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Reference #</th>
                                    <th scope="col" class="px-4 py-3">Beneficiary</th>
                                    <th scope="col" class="px-4 py-3">Household</th>
                                    <th scope="col" class="px-4 py-3">Program</th>
                                    <th scope="col" class="px-4 py-3">Date</th>
                                    <th scope="col" class="px-4 py-3">Amount</th>
                                    <th scope="col" class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reportData as $distribution)
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">
                                            {{ $distribution->reference_number }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $distribution->resident->full_name }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $distribution->household ? $distribution->household->household_id : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $distribution->ayudaProgram->name }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $distribution->distribution_date->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            ₱{{ number_format($distribution->amount, 2) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ ucfirst($distribution->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach

                                @if (count($reportData) === 0)
                                    <tr class="bg-white border-b">
                                        <td colspan="7" class="px-4 py-4 text-center text-gray-500">
                                            No distributions found with the selected filters
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    @elseif($reportType === 'programs')
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Program</th>
                                    <th scope="col" class="px-4 py-3">Distributions</th>
                                    <th scope="col" class="px-4 py-3">Amount</th>
                                    <th scope="col" class="px-4 py-3">Beneficiaries</th>
                                    <th scope="col" class="px-4 py-3">Households</th>
                                    <th scope="col" class="px-4 py-3">Budget Utilization</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reportData as $program)
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">
                                            {{ $program->name }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ number_format($program->distributions_count) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            ₱{{ number_format($program->total_distributed, 2) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ number_format($program->unique_beneficiaries) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ number_format($program->unique_households) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @if ($program->utilization !== null)
                                                <div class="flex items-center">
                                                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                        <div class="bg-blue-600 h-2.5 rounded-full"
                                                            style="width: {{ min($program->utilization, 100) }}%">
                                                        </div>
                                                    </div>
                                                    <span class="ml-2">{{ $program->utilization }}%</span>
                                                </div>
                                            @else
                                                <span class="text-gray-400">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach

                                @if (count($reportData) === 0)
                                    <tr class="bg-white border-b">
                                        <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                                            No programs found with the selected filters
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    @elseif($reportType === 'residents')
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Name</th>
                                    <th scope="col" class="px-4 py-3">Household</th>
                                    <th scope="col" class="px-4 py-3">Distributions</th>
                                    <th scope="col" class="px-4 py-3">Total Received</th>
                                    <th scope="col" class="px-4 py-3">Programs</th>
                                    <th scope="col" class="px-4 py-3">Demographics</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reportData as $resident)
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">
                                            {{ $resident->full_name }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ $resident->household ? $resident->household->household_id : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ number_format($resident->distributions_count) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            ₱{{ number_format($resident->total_received, 2) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="max-w-xs truncate" title="{{ $resident->programs_list }}">
                                                {{ $resident->programs_list }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex flex-wrap gap-1">
                                                @if ($resident->is_senior_citizen)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                                        Senior
                                                    </span>
                                                @endif

                                                @if ($resident->is_pwd)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                                        PWD
                                                    </span>
                                                @endif

                                                @if ($resident->is_solo_parent)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                        Solo Parent
                                                    </span>
                                                @endif

                                                @if ($resident->is_pregnant)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-pink-100 text-pink-800">
                                                        Pregnant
                                                    </span>
                                                @endif

                                                @if ($resident->is_lactating)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                                        Lactating
                                                    </span>
                                                @endif

                                                @if ($resident->is_indigenous)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                        Indigenous
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                @if (count($reportData) === 0)
                                    <tr class="bg-white border-b">
                                        <td colspan="6" class="px-4 py-4 text-center text-gray-500">
                                            No beneficiaries found with the selected filters
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    @elseif($reportType === 'barangays')
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-4 py-3">Barangay</th>
                                    <th scope="col" class="px-4 py-3">Distributions</th>
                                    <th scope="col" class="px-4 py-3">Total Amount</th>
                                    <th scope="col" class="px-4 py-3">Beneficiaries</th>
                                    <th scope="col" class="px-4 py-3">Households Reached</th>
                                    <th scope="col" class="px-4 py-3">Total Households</th>
                                    <th scope="col" class="px-4 py-3">Coverage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reportData as $barangayData)
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-900">
                                            {{ $barangayData['barangay'] }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ number_format($barangayData['distributions_count']) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            ₱{{ number_format($barangayData['total_amount'], 2) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ number_format($barangayData['unique_beneficiaries']) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ number_format($barangayData['unique_households']) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            {{ number_format($barangayData['total_households']) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="flex items-center">
                                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                    <div class="bg-blue-600 h-2.5 rounded-full"
                                                        style="width: {{ min($barangayData['coverage_percentage'], 100) }}%">
                                                    </div>
                                                </div>
                                                <span
                                                    class="ml-2">{{ $barangayData['coverage_percentage'] }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach

                                @if (count($reportData) === 0)
                                    <tr class="bg-white border-b">
                                        <td colspan="7" class="px-4 py-4 text-center text-gray-500">
                                            No data found for the selected filters
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    @endif
                </div>
            </x-mary-card>
        </div>
    @else
        <!-- No Report Generated Yet -->
        <div class="py-12 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 mb-4 text-blue-500 bg-blue-100 rounded-full">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
            </div>
            <h3 class="mb-2 text-lg font-medium text-gray-900">No Report Generated</h3>
            <p class="mb-6 text-gray-500">Select your report parameters and click "Generate Report" to see results.</p>
        </div>
    @endif
</div>

@script
    <script>
        // Initialize chart variables (not attached to window)
        let distributionByDateChart = null;
        let distributionByProgramChart = null;
        let distributionsByProgramChart = null;
        let amountByProgramChart = null;
        let beneficiariesByDemographicChart = null;
        let distributionsByBarangayChart = null;
        let coverageByBarangayChart = null;

        // Helper function to safely destroy a chart
        function safeDestroyChart(chart) {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
                return null;
            }
            return null;
        }

        // Distributions Report Charts
        function renderDistributionDateChart(chartData) {
            if (!chartData || !chartData.byDate) return;

            const canvas = document.getElementById('distributionByDateChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Destroy existing chart
            distributionByDateChart = safeDestroyChart(distributionByDateChart);

            distributionByDateChart = new Chart(ctx, {
                type: 'bar',
                data: chartData.byDate,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Count'
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Amount'
                            },
                            grid: {
                                drawOnChartArea: false,
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        function renderDistributionProgramChart(chartData) {
            if (!chartData || !chartData.byProgram) return;

            const canvas = document.getElementById('distributionByProgramChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Destroy existing chart
            distributionByProgramChart = safeDestroyChart(distributionByProgramChart);

            distributionByProgramChart = new Chart(ctx, {
                type: 'doughnut',
                data: chartData.byProgram,
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
        }

        // Programs Report Charts
        function renderProgramDistributionsChart(chartData) {
            if (!chartData || !chartData.byProgram) return;

            const canvas = document.getElementById('distributionsByProgramChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Destroy existing chart
            distributionsByProgramChart = safeDestroyChart(distributionsByProgramChart);

            distributionsByProgramChart = new Chart(ctx, {
                type: 'bar',
                data: chartData.byProgram,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Distributions'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Program'
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y'
                }
            });
        }

        function renderProgramAmountChart(chartData) {
            if (!chartData || !chartData.byAmount) return;

            const canvas = document.getElementById('amountByProgramChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Destroy existing chart
            amountByProgramChart = safeDestroyChart(amountByProgramChart);

            amountByProgramChart = new Chart(ctx, {
                type: 'pie',
                data: chartData.byAmount,
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
        }

        // Residents Report Charts
        function renderDemographicChart(chartData) {
            if (!chartData || !chartData.byDemographic) return;

            const canvas = document.getElementById('beneficiariesByDemographicChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Destroy existing chart
            beneficiariesByDemographicChart = safeDestroyChart(beneficiariesByDemographicChart);

            beneficiariesByDemographicChart = new Chart(ctx, {
                type: 'bar',
                data: chartData.byDemographic,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Count'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Demographic'
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        }

        // Barangays Report Charts
        function renderBarangayDistributionsChart(chartData) {
            if (!chartData || !chartData.byBarangay) return;

            const canvas = document.getElementById('distributionsByBarangayChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Destroy existing chart
            distributionsByBarangayChart = safeDestroyChart(distributionsByBarangayChart);

            distributionsByBarangayChart = new Chart(ctx, {
                type: 'bar',
                data: chartData.byBarangay,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Count'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Barangay'
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y'
                }
            });
        }

        function renderBarangayCoverageChart(chartData) {
            if (!chartData || !chartData.byCoverage) return;

            const canvas = document.getElementById('coverageByBarangayChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            // Destroy existing chart
            coverageByBarangayChart = safeDestroyChart(coverageByBarangayChart);

            coverageByBarangayChart = new Chart(ctx, {
                type: 'bar',
                data: chartData.byCoverage,
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            title: {
                                display: true,
                                text: 'Coverage (%)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Barangay'
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y'
                }
            });
        }

        // Function to render the appropriate charts based on report type
        function renderCharts(chartData) {
            if (!chartData) return;

            const reportType = @this.reportType;

            // Small delay to ensure DOM is ready
            setTimeout(() => {
                try {
                    // Render charts based on report type
                    if (reportType === 'distributions') {
                        renderDistributionDateChart(chartData);
                        renderDistributionProgramChart(chartData);
                    } else if (reportType === 'programs') {
                        renderProgramDistributionsChart(chartData);
                        renderProgramAmountChart(chartData);
                    } else if (reportType === 'residents') {
                        renderDemographicChart(chartData);
                    } else if (reportType === 'barangays') {
                        renderBarangayDistributionsChart(chartData);
                        renderBarangayCoverageChart(chartData);
                    }
                } catch (error) {
                    console.error('Error rendering charts:', error);
                }
            }, 300);
        }

        document.addEventListener('livewire:initialized', () => {
            // Initial chart rendering if data exists
            if (@this.summaryData && Object.keys(@this.summaryData).length > 0) {
                renderCharts(@json($this->chartData));
            }

            // Listen for the reportGenerated event
            Livewire.on('reportGenerated', () => {
                renderCharts(@json($this->chartData));
            });

            // Watch for report type changes
            @this.watch('reportType', value => {
                setTimeout(() => {
                    if (@this.chartData) {
                        renderCharts(@json($this->chartData));
                    }
                }, 300);
            });
        });
    </script>
@endscript
