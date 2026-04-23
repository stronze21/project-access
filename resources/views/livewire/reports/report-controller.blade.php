<div>
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Reports</h1>
        <p class="mt-1 text-sm text-gray-600">Generate and analyze various reports for AyudaPortal</p>
    </div>

    <!-- Report Type Selection -->
    <div class="mb-6">
        <livewire:reports.components.report-type-selector :initialReportType="$reportType" />
    </div>

    <!-- Report Filters -->
    <x-mary-card class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium">Report Filters</h3>
            <div class="flex space-x-2">
                <x-mary-button class="tagged-color btn-secondary btn-outline btn-secline" size="sm"
                    wire:click="clearCache" icon="o-arrow-path">
                    Clear Cache
                </x-mary-button>
            </div>
        </div>

        <livewire:reports.components.report-filters :dateFrom="$dateFrom" :dateTo="$dateTo" :program="$program"
            :status="$status" />

        <div class="flex justify-end mt-6 space-x-3">
            <livewire:reports.components.report-export :reportType="$reportType" />

            <x-mary-button wire:click="generateReport" wire:loading.attr="disabled" wire:loading.class="opacity-75">
                <span wire:loading.remove wire:target="generateReport">Generate Report</span>
                <span wire:loading wire:target="generateReport">Generating...</span>
            </x-mary-button>
        </div>
    </x-mary-card>

    <!-- Export Success Message -->
    @if (session()->has('export_file'))
        <div class="mb-6">
            <x-mary-alert icon="o-document-arrow-down" class="tagged-color btn-success" title="Export Ready">
                Your report has been exported successfully.
                <x-slot:actions>
                    <a href="{{ session('export_file') }}" class="underline">
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
            <livewire:reports.components.report-summary :reportType="$reportType" :summaryData="$summaryData" :key="'summary-' . $reportType . '-' . md5(json_encode($summaryData))" />
        </div>

        <!-- Detailed Report Data -->
        <div class="mb-6">
            <h2 class="mb-4 text-xl font-semibold text-gray-900">
                Detailed Report Data
            </h2>
            <livewire:reports.components.report-data-table :reportType="$reportType" :reportData="$reportData" :summaryData="$summaryData"
                :perPage="$perPage" :currentPage="$currentPage" :totalItems="$totalItems" :totalPages="$totalPages" :key="'datatable-' . $reportType . '-' . $currentPage . '-' . md5(json_encode($reportData))" />

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
        document.addEventListener('livewire:initialized', () => {
            // Listen for Livewire events and log them for debugging
            Livewire.hook('message.processed', (message, component) => {
                console.log('Livewire event processed:', message);
            });

            // Listen for the reportTypeChanged event specifically
            Livewire.on('reportTypeChanged', (type) => {
                console.log('Report type changed event received:', type);
            });

            // Listen for reportGenerated event
            Livewire.on('reportGenerated', () => {
                console.log('Report generated with type:', @js($reportType));
                // Dispatch event to child components
                Livewire.dispatch('chartDataUpdated', @json($chartData));
            });

            // Listen for filter changes
            Livewire.on('filtersChanged', (event) => {
                console.log('Filters changed:', event);
                @this.dateFrom = event.dateFrom;
                @this.dateTo = event.dateTo;
                @this.program = event.program;
                @this.status = event.status;
            });

            // Listen for export request
            Livewire.on('exportRequested', (event) => {
                console.log('Export requested for type:', @js($reportType));
                @this.exportCsv(event);
            });
        });
    </script>
@endscript
