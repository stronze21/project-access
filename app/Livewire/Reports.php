<?php

namespace App\Livewire;

use App\Models\Region;
use Livewire\Attributes\On;
use Mary\Traits\Toast;
use Livewire\Component;
use App\Models\Province;
use App\Models\Resident;
use App\Models\Household;
use App\Models\AyudaProgram;
use App\Models\Distribution;
use App\Services\ExportService;
use App\Models\CityMunicipality;
use App\Models\DistributionBatch;
use Illuminate\Support\Facades\DB;

class Reports extends Component
{
    use Toast;


    public $regions = [];
    public $provinces = [];
    public $cities = [];
    public $barangays = [];

    public $selectedRegion = '';
    public $selectedProvince = '';
    public $selectedCity = '';
    public $selectedBarangay = '';

    public $regionName = '';
    public $provinceName = '';
    public $cityName = '';
    public $barangayName = '';
    // Report type selection
    public $reportType = 'distributions';

    // Date range
    public $dateFrom;
    public $dateTo;

    // Filters
    public $program = '';
    public $barangay = '';
    public $status = 'distributed';

    // Programs and barangays for filters
    public $programs = [];

    // Charts data
    public $chartData = [];

    // Report data
    public $reportData = [];
    public $summaryData = [];
    public $isGenerating = false;
    public $isExporting = false;

    // Export service
    protected $exportService;

    /**
     * Constructor with dependency injection
     */
    public function boot(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Mount the component
     */
    public function mount()
    {
        // Set default date range to current month
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');

        // Set defaults for new households
        $regionInfo = Region::where('regCode', '02')->first(); // Region II
        $provinceInfo = Province::where('provCode', '0231')->first(); // ISABELA
        $cityInfo = CityMunicipality::where('citymunCode', '023101')->first(); // ALICIA

        if ($regionInfo) {
            $this->regionCode = $regionInfo->regCode;
            $this->region = $regionInfo->regDesc;
        }

        if ($provinceInfo) {
            $this->provinceCode = $provinceInfo->provCode;
            $this->province = $provinceInfo->provDesc;
        }

        if ($cityInfo) {
            $this->cityMunicipalityCode = $cityInfo->citymunCode;
            $this->cityMunicipality = $cityInfo->citymunDesc;
        }

        $this->loadPrograms();
    }

    #[On('locationChanged')]
    public function handleLocationChange($data)
    {
        if ($data['type'] === 'barangay') {
            $this->barangay = $data['value'];
        }
    }

    /**
     * Load programs for filter
     */
    protected function loadPrograms()
    {
        $this->programs = AyudaProgram::orderBy('name')->get();
    }

    /**
     * Load barangays for filter
     */
    protected function loadBarangays()
    {
        $this->barangays = Household::select('barangay')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay')
            ->toArray();
    }

    /**
     * Change report type
     */
    public function changeReportType($type)
    {
        $this->reportType = $type;
        $this->resetReport();
    }

    /**
     * Reset report data
     */
    public function resetReport()
    {
        $this->reportData = [];
        $this->summaryData = [];
        $this->chartData = [];
    }

    /**
     * Generate the report
     */
    public function generateReport()
    {
        $this->isGenerating = true;
        $this->resetReport();

        try {
            // Call the appropriate report generation method based on type
            switch ($this->reportType) {
                case 'distributions':
                    $this->generateDistributionsReport();
                    break;
                case 'programs':
                    $this->generateProgramsReport();
                    break;
                case 'residents':
                    $this->generateResidentsReport();
                    break;
                case 'barangays':
                    $this->generateBarangaysReport();
                    break;
            }

            $this->success('Report generated successfully');

            // Dispatch event to update charts
            $this->dispatch('reportGenerated');
        } catch (\Exception $e) {
            $this->error('Error generating report: ' . $e->getMessage());
        }

        $this->isGenerating = false;
    }

    /**
     * Generate distributions report
     */
    protected function generateDistributionsReport()
    {
        // Build base query
        $query = Distribution::with(['resident', 'household', 'ayudaProgram'])
            ->where('status', $this->status);

        // Apply date range filter
        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        // Apply program filter
        if ($this->program) {
            $query->where('ayuda_program_id', $this->program);
        }

        // Apply barangay filter
        if ($this->barangay) {
            $query->whereHas('household', function ($q) {
                $q->where('barangay', $this->barangay);
            });
        }

        // Get report data
        $this->reportData = $query->orderBy('created_at', 'desc')->get();

        // Generate summary data
        $this->summaryData = [
            'total_distributions' => $this->reportData->count(),
            'total_amount' => $this->reportData->sum('amount'),
            'unique_beneficiaries' => $this->reportData->pluck('resident_id')->unique()->count(),
            'unique_households' => $this->reportData->whereNotNull('household_id')->pluck('household_id')->unique()->count(),
            'average_amount' => $this->reportData->count() > 0 ? $this->reportData->avg('amount') : 0,
        ];

        // Generate chart data - distribution by date
        $distributionsByDate = $this->reportData
            ->groupBy(function ($item) {
                return $item->created_at->format('Y-m-d');
            })
            ->map(function ($items) {
                return [
                    'count' => $items->count(),
                    'amount' => $items->sum('amount')
                ];
            });

        $dates = $distributionsByDate->keys()->toArray();
        $counts = $distributionsByDate->pluck('count')->toArray();
        $amounts = $distributionsByDate->pluck('amount')->toArray();

        $this->chartData['byDate'] = [
            'labels' => $dates,
            'datasets' => [
                [
                    'label' => 'Count',
                    'data' => $counts,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1
                ],
                [
                    'label' => 'Amount',
                    'data' => $amounts,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 1,
                    'type' => 'line',
                    'yAxisID' => 'y1'
                ]
            ]
        ];

        // Chart data - distribution by program
        $distributionsByProgram = $this->reportData
            ->groupBy('ayuda_program_id')
            ->map(function ($items) {
                $program = $items->first()->ayudaProgram;
                return [
                    'name' => $program->name,
                    'count' => $items->count(),
                    'amount' => $items->sum('amount')
                ];
            });

        $programNames = $distributionsByProgram->pluck('name')->toArray();
        $programCounts = $distributionsByProgram->pluck('count')->toArray();
        $programAmounts = $distributionsByProgram->pluck('amount')->toArray();

        $this->chartData['byProgram'] = [
            'labels' => $programNames,
            'datasets' => [
                [
                    'label' => 'Count',
                    'data' => $programCounts,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)',
                        'rgba(16, 185, 129, 0.5)',
                        'rgba(249, 115, 22, 0.5)',
                        'rgba(139, 92, 246, 0.5)',
                        'rgba(236, 72, 153, 0.5)'
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(249, 115, 22)',
                        'rgb(139, 92, 246)',
                        'rgb(236, 72, 153)'
                    ],
                    'borderWidth' => 1
                ]
            ]
        ];
    }

    /**
     * Generate programs report
     */
    protected function generateProgramsReport()
    {
        // Build base query
        $query = AyudaProgram::withCount(['distributions' => function ($q) {
            $q->where('status', $this->status);

            if ($this->dateFrom) {
                $q->whereDate('created_at', '>=', $this->dateFrom);
            }

            if ($this->dateTo) {
                $q->whereDate('created_at', '<=', $this->dateTo);
            }

            if ($this->barangay) {
                $q->whereHas('household', function ($sq) {
                    $sq->where('barangay', $this->barangay);
                });
            }
        }]);

        // Apply program filter
        if ($this->program) {
            $query->where('id', $this->program);
        }

        // Get base program data
        $programs = $query->get();

        // Get distribution amounts
        foreach ($programs as $program) {
            $program->total_distributed = Distribution::where('ayuda_program_id', $program->id)
                ->where('status', $this->status)
                ->when($this->dateFrom, function ($q) {
                    $q->whereDate('created_at', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($q) {
                    $q->whereDate('created_at', '<=', $this->dateTo);
                })
                ->when($this->barangay, function ($q) {
                    $q->whereHas('household', function ($sq) {
                        $sq->where('barangay', $this->barangay);
                    });
                })
                ->sum('amount');

            $program->unique_beneficiaries = Distribution::where('ayuda_program_id', $program->id)
                ->where('status', $this->status)
                ->when($this->dateFrom, function ($q) {
                    $q->whereDate('created_at', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($q) {
                    $q->whereDate('created_at', '<=', $this->dateTo);
                })
                ->when($this->barangay, function ($q) {
                    $q->whereHas('household', function ($sq) {
                        $sq->where('barangay', $this->barangay);
                    });
                })
                ->distinct('resident_id')
                ->count('resident_id');

            $program->unique_households = Distribution::where('ayuda_program_id', $program->id)
                ->where('status', $this->status)
                ->whereNotNull('household_id')
                ->when($this->dateFrom, function ($q) {
                    $q->whereDate('created_at', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($q) {
                    $q->whereDate('created_at', '<=', $this->dateTo);
                })
                ->when($this->barangay, function ($q) {
                    $q->whereHas('household', function ($sq) {
                        $sq->where('barangay', $this->barangay);
                    });
                })
                ->distinct('household_id')
                ->count('household_id');

            $program->utilization = $program->total_budget > 0
                ? round(($program->total_distributed / $program->total_budget) * 100, 1)
                : null;
        }

        $this->reportData = $programs;

        // Generate summary data
        $this->summaryData = [
            'total_programs' => $programs->count(),
            'total_distributions' => $programs->sum('distributions_count'),
            'total_amount' => $programs->sum('total_distributed'),
            'unique_beneficiaries' => Distribution::where('status', $this->status)
                ->when($this->dateFrom, function ($q) {
                    $q->whereDate('created_at', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($q) {
                    $q->whereDate('created_at', '<=', $this->dateTo);
                })
                ->when($this->barangay, function ($q) {
                    $q->whereHas('household', function ($sq) {
                        $sq->where('barangay', $this->barangay);
                    });
                })
                ->when($this->program, function ($q) {
                    $q->where('ayuda_program_id', $this->program);
                })
                ->distinct('resident_id')
                ->count('resident_id'),
            'unique_households' => Distribution::where('status', $this->status)
                ->whereNotNull('household_id')
                ->when($this->dateFrom, function ($q) {
                    $q->whereDate('created_at', '>=', $this->dateFrom);
                })
                ->when($this->dateTo, function ($q) {
                    $q->whereDate('created_at', '<=', $this->dateTo);
                })
                ->when($this->barangay, function ($q) {
                    $q->whereHas('household', function ($sq) {
                        $sq->where('barangay', $this->barangay);
                    });
                })
                ->when($this->program, function ($q) {
                    $q->where('ayuda_program_id', $this->program);
                })
                ->distinct('household_id')
                ->count('household_id'),
        ];

        // Chart data - distributions by program
        $programNames = $programs->pluck('name')->toArray();
        $programCounts = $programs->pluck('distributions_count')->toArray();
        $programAmounts = $programs->pluck('total_distributed')->toArray();

        $this->chartData['byProgram'] = [
            'labels' => $programNames,
            'datasets' => [
                [
                    'label' => 'Distributions',
                    'data' => $programCounts,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1
                ]
            ]
        ];

        $this->chartData['byAmount'] = [
            'labels' => $programNames,
            'datasets' => [
                [
                    'label' => 'Amount Distributed',
                    'data' => $programAmounts,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 1
                ]
            ]
        ];
    }

    /**
     * Generate residents report
     */
    protected function generateResidentsReport()
    {
        // Get residents with distribution data
        $query = Resident::with('household')
            ->whereHas('distributions', function ($q) {
                $q->where('status', $this->status)
                    ->when($this->dateFrom, function ($sq) {
                        $sq->whereDate('created_at', '>=', $this->dateFrom);
                    })
                    ->when($this->dateTo, function ($sq) {
                        $sq->whereDate('created_at', '<=', $this->dateTo);
                    })
                    ->when($this->program, function ($sq) {
                        $sq->where('ayuda_program_id', $this->program);
                    });
            });

        // Apply barangay filter
        if ($this->barangay) {
            $query->whereHas('household', function ($q) {
                $q->where('barangay', $this->barangay);
            });
        }

        $residents = $query->get();

        // Get distribution data for each resident
        foreach ($residents as $resident) {
            $distributionsQuery = Distribution::where('resident_id', $resident->id)
                ->where('status', $this->status);

            if ($this->dateFrom) {
                $distributionsQuery->whereDate('created_at', '>=', $this->dateFrom);
            }

            if ($this->dateTo) {
                $distributionsQuery->whereDate('created_at', '<=', $this->dateTo);
            }

            if ($this->program) {
                $distributionsQuery->where('ayuda_program_id', $this->program);
            }

            $resident->distributions_count = $distributionsQuery->count();
            $resident->total_received = $distributionsQuery->sum('amount');
            $resident->programs_count = $distributionsQuery->distinct('ayuda_program_id')->count('ayuda_program_id');

            // Get list of programs
            $resident->programs_list = $distributionsQuery
                ->with('ayudaProgram')
                ->get()
                ->pluck('ayudaProgram.name')
                ->unique()
                ->implode(', ');
        }

        $this->reportData = $residents;

        // Generate summary data
        $this->summaryData = [
            'total_beneficiaries' => $residents->count(),
            'total_distributions' => $residents->sum('distributions_count'),
            'total_amount' => $residents->sum('total_received'),
            'average_per_beneficiary' => $residents->count() > 0 ? $residents->avg('total_received') : 0,
            'seniors_count' => $residents->where('is_senior_citizen', true)->count(),
            'pwd_count' => $residents->where('is_pwd', true)->count(),
            'solo_parents_count' => $residents->where('is_solo_parent', true)->count(),
        ];

        // Chart data - distribution by demographic
        $demographics = [
            'Senior Citizens' => $residents->where('is_senior_citizen', true)->count(),
            'PWD' => $residents->where('is_pwd', true)->count(),
            'Solo Parents' => $residents->where('is_solo_parent', true)->count(),
            'Pregnant' => $residents->where('is_pregnant', true)->count(),
            'Lactating' => $residents->where('is_lactating', true)->count(),
            'Indigenous' => $residents->where('is_indigenous', true)->count(),
            'Other' => $residents->where('is_senior_citizen', false)
                ->where('is_pwd', false)
                ->where('is_solo_parent', false)
                ->where('is_pregnant', false)
                ->where('is_lactating', false)
                ->where('is_indigenous', false)
                ->count()
        ];

        $demographicLabels = array_keys($demographics);
        $demographicCounts = array_values($demographics);

        $this->chartData['byDemographic'] = [
            'labels' => $demographicLabels,
            'datasets' => [
                [
                    'label' => 'Beneficiaries',
                    'data' => $demographicCounts,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)',
                        'rgba(16, 185, 129, 0.5)',
                        'rgba(249, 115, 22, 0.5)',
                        'rgba(139, 92, 246, 0.5)',
                        'rgba(236, 72, 153, 0.5)',
                        'rgba(245, 158, 11, 0.5)',
                        'rgba(107, 114, 128, 0.5)',
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(249, 115, 22)',
                        'rgb(139, 92, 246)',
                        'rgb(236, 72, 153)',
                        'rgb(245, 158, 11)',
                        'rgb(107, 114, 128)',
                    ],
                    'borderWidth' => 1
                ]
            ]
        ];
    }

    /**
     * Generate barangays report
     */
    protected function generateBarangaysReport()
    {
        $selectedBarangays = $this->barangay ? [$this->barangay] : $this->barangays;

        $barangayData = [];
        foreach ($selectedBarangays as $barangay) {
            // Get distribution data for this barangay
            $query = Distribution::whereHas('household', function ($q) use ($barangay) {
                $q->where('barangay', $barangay);
            })
                ->where('status', $this->status);

            // Apply date range filter
            if ($this->dateFrom) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            }

            if ($this->dateTo) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            }

            // Apply program filter
            if ($this->program) {
                $query->where('ayuda_program_id', $this->program);
            }

            $distributionsCount = $query->count();
            $totalAmount = $query->sum('amount');
            $uniqueBeneficiaries = $query->distinct('resident_id')->count('resident_id');
            $uniqueHouseholds = $query->whereNotNull('household_id')->distinct('household_id')->count('household_id');

            // Get household count in barangay
            $totalHouseholds = Household::where('barangay', $barangay)->count();
            $coverage = $totalHouseholds > 0 ? round(($uniqueHouseholds / $totalHouseholds) * 100, 1) : 0;

            // Only include barangays with distributions
            if ($distributionsCount > 0) {
                $barangayData[] = [
                    'barangay' => $barangay,
                    'distributions_count' => $distributionsCount,
                    'total_amount' => $totalAmount,
                    'unique_beneficiaries' => $uniqueBeneficiaries,
                    'unique_households' => $uniqueHouseholds,
                    'total_households' => $totalHouseholds,
                    'coverage_percentage' => $coverage,
                ];
            }
        }

        // Sort by distribution count in descending order
        usort($barangayData, function ($a, $b) {
            return $b['distributions_count'] <=> $a['distributions_count'];
        });

        $this->reportData = $barangayData;

        // Generate summary data
        $this->summaryData = [
            'total_barangays' => count($barangayData),
            'total_distributions' => array_sum(array_column($barangayData, 'distributions_count')),
            'total_amount' => array_sum(array_column($barangayData, 'total_amount')),
            'total_beneficiaries' => array_sum(array_column($barangayData, 'unique_beneficiaries')),
            'total_households_reached' => array_sum(array_column($barangayData, 'unique_households')),
        ];

        // Chart data - distribution by barangay
        $barangayNames = array_column($barangayData, 'barangay');
        $barangayCounts = array_column($barangayData, 'distributions_count');
        $barangayAmounts = array_column($barangayData, 'total_amount');
        $barangayCoverage = array_column($barangayData, 'coverage_percentage');

        $this->chartData['byBarangay'] = [
            'labels' => $barangayNames,
            'datasets' => [
                [
                    'label' => 'Distributions',
                    'data' => $barangayCounts,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1
                ]
            ]
        ];

        $this->chartData['byAmount'] = [
            'labels' => $barangayNames,
            'datasets' => [
                [
                    'label' => 'Amount (₱)',
                    'data' => $barangayAmounts,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 1
                ]
            ]
        ];

        $this->chartData['byCoverage'] = [
            'labels' => $barangayNames,
            'datasets' => [
                [
                    'label' => 'Household Coverage (%)',
                    'data' => $barangayCoverage,
                    'backgroundColor' => 'rgba(249, 115, 22, 0.5)',
                    'borderColor' => 'rgb(249, 115, 22)',
                    'borderWidth' => 1
                ]
            ]
        ];
    }

    /**
     * Export report to CSV
     */
    public function exportCsv()
    {
        if (empty($this->reportData)) {
            $this->warning('Please generate a report first before exporting');
            return;
        }

        $this->isExporting = true;

        try {
            // Get filename with date range
            $dateRange = '';
            if ($this->dateFrom && $this->dateTo) {
                $dateRange = date('Y-m-d', strtotime($this->dateFrom)) . '_to_' . date('Y-m-d', strtotime($this->dateTo));
            }

            $filename = 'ayudahub_' . $this->reportType . '_report_' . $dateRange . '.csv';

            // Format data based on report type
            $exportData = [];

            switch ($this->reportType) {
                case 'distributions':
                    $exportData = $this->exportService->formatDistributionsForExport($this->reportData);
                    break;

                case 'programs':
                    $exportData = $this->exportService->formatProgramsForExport($this->reportData);
                    break;

                case 'residents':
                    $exportData = $this->exportService->formatResidentsForExport($this->reportData);
                    break;

                case 'barangays':
                    $exportData = $this->exportService->formatBarangaysForExport($this->reportData);
                    break;
            }

            // Generate CSV file
            $filePath = $this->exportService->generateCsv(
                $exportData['data'],
                $exportData['headers'],
                $filename
            );

            // Store file path in session for download
            session()->flash('export_file', $filePath);

            $this->success('CSV export successful. Click the download link to save the file.');
        } catch (\Exception $e) {
            $this->error('Error exporting CSV: ' . $e->getMessage());
        }

        $this->isExporting = false;
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.reports');
    }
}
