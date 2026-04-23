<?php

namespace App\Livewire\Reports;

use App\Models\AyudaProgram;
use App\Models\Household;
use App\Services\ExportService;
use App\Services\Reports\DistributionReportService;
use App\Services\Reports\GeographicReportService;
use App\Services\Reports\ProgramReportService;
use App\Services\Reports\ResidentExportService;
use App\Services\Reports\ResidentReportService;
use App\Services\Reports\ResidentsWithIdReportService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Mary\Traits\Toast;

class ReportController extends Component
{
    use Toast;

    // Report type selection
    public $reportType = 'distributions';

    // Export type
    public $exportType = 'regular'; // 'regular' or 'residents-full'

    // Date range
    public $dateFrom;
    public $dateTo;

    // Filters
    public $searchTerm = '';
    public $program = '';
    public $barangay = '';
    public $barangayCode = '';
    public $status = 'distributed';

    // Programs for filters
    public $programs = [];

    // Charts data
    public $chartData = [];

    // Report data
    public $reportData = [];
    public $summaryData = [];
    public $isGenerating = false;
    public $isExporting = false;

    // Services
    protected $exportService;
    protected $distributionReportService;
    protected $programReportService;
    protected $residentReportService;
    protected $geographicReportService;
    protected $residentExportService;
    protected $residentsWithIdReportService;

    // Pagination
    public $perPage = 15;
    public $currentPage = 1;
    public $totalPages = 1;
    public $totalItems = 0;

    /**
     * Constructor with dependency injection
     */
    public function boot(
        ExportService $exportService,
        DistributionReportService $distributionReportService,
        ProgramReportService $programReportService,
        ResidentReportService $residentReportService,
        GeographicReportService $geographicReportService,
        ResidentExportService $residentExportService,
        ResidentsWithIdReportService $residentsWithIdReportService
    ) {
        $this->exportService = $exportService;
        $this->distributionReportService = $distributionReportService;
        $this->programReportService = $programReportService;
        $this->residentReportService = $residentReportService;
        $this->geographicReportService = $geographicReportService;
        $this->residentExportService = $residentExportService;
        $this->residentsWithIdReportService = $residentsWithIdReportService;
    }

    /**
     * Mount the component
     */
    public function mount()
    {
        // Set default date range to current month
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');

        $this->loadPrograms();
    }

    /**
     * Handle location change event from address selector
     */
    #[On('address-updated')]
    public function handleLocationChange($data)
    {
        $this->barangayCode = $data['barangay']['code'] ?? '';
        $this->barangay = $data['barangay']['name'] ?? '';
    }

    /**
     * Load programs for filter
     */
    protected function loadPrograms()
    {
        $this->programs = AyudaProgram::orderBy('name')->get();
    }

    /**
     * Change report type when changed in the report type selector
     */
    #[On('reportTypeChanged')]
    public function changeReportType($type)
    {
        // For debugging
        logger()->info('Report type changed to: ' . $type);

        // Update the report type
        $this->reportType = $type;

        // IMPORTANT: Reset report data when changing type
        $this->resetReport();

        // Notify the export component about the report type change
        $this->dispatch('reportTypeUpdated', $this->reportType);
    }

    /**
     * Reset report data
     */
    public function resetReport()
    {
        $this->reportData = [];
        $this->summaryData = [];
        $this->chartData = [];
        $this->currentPage = 1;
        $this->totalPages = 1;
        $this->totalItems = 0;
        $this->searchTerm = ''; // Reset search term as well
    }

    /**
     * Generate the report
     */
    public function generateReport()
    {
        $this->isGenerating = true;

        try {
            $filters = $this->getFilters();

            // Log the filters for debugging
            logger()->info('Generating report with filters:', $filters);

            // Get the report data from the appropriate service
            $reportData = match ($this->reportType) {
                'distributions' => $this->distributionReportService->generatePaginatedReport($filters, $this->perPage),
                'programs' => $this->programReportService->generatePaginatedReport($filters, $this->perPage),
                'residents' => $this->residentReportService->generatePaginatedReport($filters, $this->perPage),
                'barangays' => $this->geographicReportService->generatePaginatedReport($filters, $this->perPage),
                'residents-with-id' => $this->residentsWithIdReportService->generatePaginatedReport($filters, $this->perPage),
                default => throw new \Exception('Invalid report type selected')
            };

            // Store pagination metadata separately - check if reportData is a pagination object
            if (isset($reportData['reportData']) && is_object($reportData['reportData']) && method_exists($reportData['reportData'], 'toArray')) {
                $paginationData = $reportData['reportData']->toArray();
                $this->currentPage = $paginationData['current_page'];
                $this->totalPages = $paginationData['last_page'];
                $this->totalItems = $paginationData['total'];

                // Store only the data items, not the full pagination object
                $this->reportData = $paginationData['data'];
            } else {
                // If it's not a pagination object, just store it directly
                $this->reportData = $reportData['reportData'];

                // Try to extract pagination data if available in the array
                if (isset($reportData['pagination'])) {
                    $this->currentPage = $reportData['pagination']['current_page'] ?? 1;
                    $this->totalPages = $reportData['pagination']['last_page'] ?? 1;
                    $this->totalItems = $reportData['pagination']['total'] ?? count($this->reportData);
                }
            }

            $this->summaryData = $reportData['summaryData'];
            $this->chartData = $reportData['chartData'];

            // IMPORTANT: Dispatch event to update child components
            $this->dispatch(
                'updateData',
                reportData: $this->reportData,
                summaryData: $this->summaryData,
                currentPage: $this->currentPage,
                totalItems: $this->totalItems,
                totalPages: $this->totalPages,
                perPage: $this->perPage
            );

            $this->success('Report generated successfully');
            $this->dispatch('reportGenerated');
        } catch (\Exception $e) {
            logger()->error('Error generating report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->error('Error generating report: ' . $e->getMessage());
        }

        $this->isGenerating = false;
    }

    /**
     * Export report to CSV
     */
    #[On('exportRequested')]
    public function exportCsv($data = [])
    {
        // Set export type if provided
        if (isset($data['type'])) {
            $this->exportType = $data['type'];
        }

        // Special case
        if ($this->exportType === 'residents-full') {
            $this->exportResidentFull();
            return;
        }

        if (empty($this->summaryData)) {
            $this->warning('Please generate a report first before exporting');
            $this->dispatch('exportFailed', errorMessage: 'No report data to export');
            return;
        }

        $this->isExporting = true;

        try {
            $filters = $this->getFilters();

            $dateRange = '';
            if ($this->dateFrom && $this->dateTo) {
                $dateRange = date('Y-m-d', strtotime($this->dateFrom))
                    . '_to_' .
                    date('Y-m-d', strtotime($this->dateTo));
            }

            $filename = 'ayudahub_' . $this->reportType . '_report_' . $dateRange . '.csv';

            // Get FULL data for export (no pagination)
            $fullData = match ($this->reportType) {
                'distributions' => $this->distributionReportService->getReportData($filters),
                'programs' => $this->programReportService->getReportData($filters),
                'residents' => $this->residentReportService->getReportData($filters),
                'barangays' => $this->geographicReportService->getReportData($filters),
                'residents-with-id' => $this->residentsWithIdReportService->getReportData($filters),
                default => collect([])
            };

            // Format for export based on report type
            $exportData = match ($this->reportType) {
                'distributions' => $this->formatDistributionsForExport($fullData),
                'programs' => $this->formatProgramsForExport($fullData),
                'residents' => $this->formatResidentsForExport($fullData),
                'barangays' => $this->formatBarangaysForExport($fullData),
                'residents-with-id' => $this->formatResidentsWithIdForExport($fullData),
                default => ['headers' => [], 'data' => []]
            };

            // Generate CSV file using the export service
            $filePath = $this->exportService->generateCsv(
                $exportData['data'],
                $exportData['headers'],
                $filename
            );

            // Store file path in session for download
            session()->flash('export_file', $filePath);

            // Notify export component that export is completed
            $this->dispatch('exportCompleted', filePath: $filePath);

            $this->success('Export successful. Click the download link to save the file.');
        } catch (\Exception $e) {
            logger()->error('Error exporting report: ' . $e->getMessage());
            $this->error('Error exporting report: ' . $e->getMessage());
            $this->dispatch('exportFailed', errorMessage: $e->getMessage());
        }

        $this->isExporting = false;
    }

    /**
     * Format distributions data for export
     *
     * @param \Illuminate\Database\Eloquent\Collection $distributions
     * @return array
     */
    protected function formatDistributionsForExport($distributions): array
    {
        $headers = [
            'Reference Number',
            'Date',
            'Beneficiary Name',
            'Household ID',
            'Barangay',
            'Program',
            'Amount',
            'Status',
            'Distributed By',
            'Notes'
        ];

        $data = [];

        foreach ($distributions as $distribution) {
            // Check if distribution is object or array
            $isObject = is_object($distribution);

            // Get resident name with proper fallback
            $beneficiary = '';
            if ($isObject && isset($distribution->resident)) {
                $beneficiary = $distribution->resident->full_name ?? 'N/A';
            } elseif (isset($distribution['resident']['full_name'])) {
                $beneficiary = $distribution['resident']['full_name'];
            } elseif (isset($distribution['resident']['first_name'])) {
                // Construct full name from parts
                $fullName = [
                    $distribution['resident']['last_name'] . ',',
                    $distribution['resident']['first_name'],
                    $distribution['resident']['suffix'] ?? null,
                    isset($distribution['resident']['middle_name']) ? substr($distribution['resident']['middle_name'], 0, 1) . '.' : null,
                ];
                $beneficiary = implode(' ', array_filter($fullName));
            } elseif (isset($distribution['beneficiary'])) {
                $beneficiary = $distribution['beneficiary'];
            } else {
                $beneficiary = 'N/A';
            }

            // Get household ID with proper fallback
            $householdId = '';
            if ($isObject && isset($distribution->household)) {
                $householdId = $distribution->household->household_id ?? 'N/A';
            } elseif (isset($distribution['household']['household_id'])) {
                $householdId = $distribution['household']['household_id'];
            } elseif (isset($distribution['household_id'])) {
                $householdId = $distribution['household_id'];
            } else {
                $householdId = 'N/A';
            }

            // Get barangay with proper fallback
            $barangay = '';
            if ($isObject && isset($distribution->household)) {
                $barangay = $distribution->household->barangay ?? 'N/A';
            } elseif (isset($distribution['household']['barangay'])) {
                $barangay = $distribution['household']['barangay'];
            } else {
                $barangay = 'N/A';
            }

            // Get program name with proper fallback
            $program = '';
            if ($isObject && isset($distribution->ayudaProgram)) {
                $program = $distribution->ayudaProgram->name ?? 'N/A';
            } elseif (isset($distribution['ayudaProgram']['name'])) {
                $program = $distribution['ayudaProgram']['name'];
            } elseif (isset($distribution['program_name'])) {
                $program = $distribution['program_name'];
            } else {
                $program = 'N/A';
            }

            // Format date
            $date = '';
            if ($isObject && isset($distribution->created_at)) {
                $date = $distribution->created_at->format('M d, Y g:i A');
            } elseif (isset($distribution['created_at'])) {
                $date = is_string($distribution['created_at'])
                    ? date('M d, Y g:i A', strtotime($distribution['created_at']))
                    : ($distribution['created_at']->format('M d, Y g:i A') ?? 'N/A');
            } else {
                $date = 'N/A';
            }

            // Get distributor name
            $distributor = '';
            if ($isObject && isset($distribution->distributor)) {
                $distributor = $distribution->distributor->name ?? 'N/A';
            } elseif (isset($distribution['distributor']['name'])) {
                $distributor = $distribution['distributor']['name'];
            } else {
                $distributor = 'N/A';
            }

            $data[] = [
                $isObject ? $distribution->reference_number : ($distribution['reference_number'] ?? 'N/A'),
                $date,
                $beneficiary,
                $householdId,
                $barangay,
                $program,
                '₱' . number_format($isObject ? ($distribution->amount ?? 0) : ($distribution['amount'] ?? 0), 2),
                ucfirst($isObject ? ($distribution->status ?? 'N/A') : ($distribution['status'] ?? 'N/A')),
                $distributor,
                $isObject ? ($distribution->notes ?? '') : ($distribution['notes'] ?? '')
            ];
        }

        return [
            'headers' => $headers,
            'data' => $data
        ];
    }

    /**
     * Format programs data for export
     *
     * @param \Illuminate\Database\Eloquent\Collection $programs
     * @return array
     */
    protected function formatProgramsForExport($programs): array
    {
        $headers = [
            'Program Name',
            'Distributions',
            'Amount',
            'Beneficiaries',
            'Households',
            'Budget Utilization'
        ];

        $data = [];

        foreach ($programs as $program) {
            // Check if program is object or array
            $isObject = is_object($program);

            // Get utilization value
            $utilization = '';
            if ($isObject) {
                $utilization = isset($program->utilization) ? $program->utilization . '%' : 'N/A';
            } else {
                $utilization = isset($program['utilization']) ? $program['utilization'] . '%' : 'N/A';
            }

            $data[] = [
                $isObject ? ($program->name ?? 'N/A') : ($program['name'] ?? 'N/A'),
                number_format($isObject ? ($program->distributions_count ?? 0) : ($program['distributions_count'] ?? 0)),
                '₱' . number_format($isObject ? ($program->total_distributed ?? 0) : ($program['total_distributed'] ?? 0), 2),
                number_format($isObject ? ($program->unique_beneficiaries ?? 0) : ($program['unique_beneficiaries'] ?? 0)),
                number_format($isObject ? ($program->unique_households ?? 0) : ($program['unique_households'] ?? 0)),
                $utilization
            ];
        }

        return [
            'headers' => $headers,
            'data' => $data
        ];
    }

    /**
     * Format residents data for export
     *
     * @param \Illuminate\Database\Eloquent\Collection $residents
     * @return array
     */
    protected function formatResidentsForExport($residents): array
    {
        $headers = [
            'Name',
            'Household ID',
            'Barangay',
            'Distributions Count',
            'Total Received',
            'Programs',
            'Demographics'
        ];

        $data = [];

        foreach ($residents as $resident) {
            // Check if resident is object or array
            $isObject = is_object($resident);

            // Prepare special categories text
            $demographics = [];

            if ($isObject) {
                if (!empty($resident->is_senior_citizen)) $demographics[] = 'Senior Citizen';
                if (!empty($resident->is_pwd)) $demographics[] = 'PWD';
                if (!empty($resident->is_indigenous)) $demographics[] = 'Indigenous';
                if (!empty($resident->is_solo_parent)) $demographics[] = 'Solo Parent';
                if (!empty($resident->is_4ps)) $demographics[] = '4Ps';
            } else {
                if (!empty($resident['is_senior_citizen'])) $demographics[] = 'Senior Citizen';
                if (!empty($resident['is_pwd'])) $demographics[] = 'PWD';
                if (!empty($resident['is_indigenous'])) $demographics[] = 'Indigenous';
                if (!empty($resident['is_solo_parent'])) $demographics[] = 'Solo Parent';
                if (!empty($resident['is_4ps'])) $demographics[] = '4Ps';
            }

            $demographicsText = implode(', ', $demographics);

            // Get household ID
            $householdId = '';
            if ($isObject && isset($resident->household)) {
                $householdId = $resident->household->household_id ?? 'N/A';
            } elseif (isset($resident['household']['household_id'])) {
                $householdId = $resident['household']['household_id'];
            } elseif (isset($resident['household_id'])) {
                $householdId = $resident['household_id'];
            }

            // Get barangay
            $barangay = '';
            if ($isObject && isset($resident->household)) {
                $barangay = $resident->household->barangay ?? 'N/A';
            } elseif (isset($resident['household']['barangay'])) {
                $barangay = $resident['household']['barangay'];
            } else {
                $barangay = 'N/A';
            }

            // Add to export data
            $data[] = [
                'Name' => $isObject ? ($resident->full_name ?? 'N/A') : ($resident['full_name'] ?? 'N/A'),
                'Household ID' => $householdId,
                'Barangay' => $barangay,
                'Distributions Count' => $isObject ? ($resident->distributions_count ?? 0) : ($resident['distributions_count'] ?? 0),
                'Total Received' => '₱' . number_format($isObject ? ($resident->total_received ?? 0) : ($resident['total_received'] ?? 0), 2),
                'Programs' => $isObject ? ($resident->programs_list ?? 'N/A') : ($resident['programs_list'] ?? 'N/A'),
                'Demographics' => $demographicsText
            ];
        }

        return [
            'headers' => $headers,
            'data' => $data
        ];
    }

    /**
     * Format barangays data for export
     *
     * @param array $barangays
     * @return array
     */
    protected function formatBarangaysForExport($barangays): array
    {
        $headers = [
            'Barangay',
            'Distributions',
            'Total Amount',
            'Beneficiaries',
            'Households Reached',
            'Total Households',
            'Coverage'
        ];

        $data = [];

        foreach ($barangays as $barangay) {
            // Check if barangay is object or array
            $isObject = is_object($barangay);

            // Get barangay name
            $barangayName = '';
            if ($isObject) {
                $barangayName = $barangay->barangay ?? 'N/A';
            } else {
                $barangayName = $barangay['barangay'] ?? 'N/A';
            }

            // Get coverage percentage
            $coverage = '';
            if ($isObject) {
                $coverage = $barangay->coverage_percentage ?? 0;
            } else {
                $coverage = $barangay['coverage_percentage'] ?? 0;
            }

            // Add to export data
            $data[] = [
                'Barangay' => $barangayName,
                'Distributions' => number_format($isObject ? ($barangay->distributions_count ?? 0) : ($barangay['distributions_count'] ?? 0)),
                'Total Amount' => '₱' . number_format($isObject ? ($barangay->total_amount ?? 0) : ($barangay['total_amount'] ?? 0), 2),
                'Beneficiaries' => number_format($isObject ? ($barangay->unique_beneficiaries ?? 0) : ($barangay['unique_beneficiaries'] ?? 0)),
                'Households Reached' => number_format($isObject ? ($barangay->unique_households ?? 0) : ($barangay['unique_households'] ?? 0)),
                'Total Households' => number_format($isObject ? ($barangay->total_households ?? 0) : ($barangay['total_households'] ?? 0)),
                'Coverage' => $coverage . '%'
            ];
        }

        return [
            'headers' => $headers,
            'data' => $data
        ];
    }

    /**
     * Format residents with ID data for export
     *
     * @param \Illuminate\Database\Eloquent\Collection $residents
     * @return array
     */
    protected function formatResidentsWithIdForExport($residents): array
    {
        return $this->residentsWithIdReportService->formatForExport($residents);
    }

    /**
     * Export full resident data
     */
    protected function exportResidentFull()
    {
        $this->isExporting = true;

        try {
            $filters = $this->getFilters();

            // Use the resident export service to generate the export
            $filePath = $this->residentExportService->exportResidents($filters);

            // Store file path in session for download
            session()->flash('export_file', $filePath);

            // Notify export component that export is completed
            $this->dispatch('exportCompleted', filePath: $filePath);

            $this->success('Resident data export successful. Click the download link to save the file.');
        } catch (\Exception $e) {
            $this->error('Error exporting resident data: ' . $e->getMessage());
            $this->dispatch('exportFailed', errorMessage: $e->getMessage());
        }

        $this->isExporting = false;
    }


    #[On('pageChanged')]
    public function onPageChanged($data)
    {
        $this->currentPage = $data['page'] ?? 1;
        $this->generateReport();
        $this->dispatch('updateData', $this->reportData, $this->summaryData, $this->currentPage, $this->totalItems, $this->totalPages, $this->perPage);
    }

    #[On('perPageChanged')]
    public function onPerPageChanged($data)
    {
        $this->perPage = $data['perPage'] ?? $this->perPage;
        $this->currentPage = 1; // Reset to first page
        $this->generateReport();
        $this->dispatch('updateData', $this->reportData, $this->summaryData, $this->currentPage, $this->totalItems, $this->totalPages, $this->perPage);
    }

    #[On('searchChanged')]
    public function onSearchChanged($data)
    {
        $this->currentPage = 1; // Reset to first page
        $this->searchTerm = $data['term'] ?? '';
        $this->generateReport();
        $this->dispatch('updateData', $this->reportData, $this->summaryData, $this->currentPage, $this->totalItems, $this->totalPages, $this->perPage);
    }


    /**
     * Get active filters as array
     */
    protected function getFilters(): array
    {
        $start = Carbon::createFromFormat('Y-m-d', $this->dateFrom, 'Asia/Manila')
            ->startOfDay()
            ->utc()->format('Y-m-d H:i');

        $end = Carbon::createFromFormat('Y-m-d', $this->dateTo, 'Asia/Manila')
            ->endOfDay()
            ->utc()->format('Y-m-d H:i');

        return [
            'searchTerm' => $this->searchTerm,
            'dateFrom' => $start,
            'dateTo' => $end,
            'program' => $this->program,
            'barangay' => $this->barangay,
            'barangayCode' => $this->barangayCode,
            'status' => $this->status,
            'page' => $this->currentPage,
            'perPage' => $this->perPage
        ];
    }

    #[On('filtersChanged')]
    public function filtersChanged($filters)
    {
        $this->dateFrom = $filters['dateFrom'];
        $this->dateTo = $filters['dateTo'];
        $this->program = $filters['program'];
        $this->status = $filters['status'];

        // Reset to first page when filters change
        $this->currentPage = 1;
    }

    /**
     * Clear report cache - NO-OP since caching is disabled
     */
    public function clearCache()
    {
        $this->info('Cache has been disabled for reports. No cache to clear.');
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.reports.report-controller');
    }
}
