<?php

namespace App\Services\Reports;

use App\Models\AyudaProgram;
use App\Models\Distribution;
use Illuminate\Support\Facades\DB;

class ProgramReportService extends ReportService
{
    /**
     * Generate programs report
     *
     * @param array $filters
     * @return array
     */
    public function generateReport(array $filters)
    {
        // Build base query
        $query = AyudaProgram::withCount(['distributions' => function ($q) use ($filters) {
            $q->where('status', $filters['status'] ?? 'distributed');

            // Apply date filters
            if (!empty($filters['dateFrom'])) {
                $q->whereDate('distribution_date', '>=', $filters['dateFrom']);
            }

            if (!empty($filters['dateTo'])) {
                $q->whereDate('distribution_date', '<=', $filters['dateTo']);
            }

            // Apply location filter
            if (!empty($filters['barangay'])) {
                $q->whereHas('household', function ($sq) use ($filters) {
                    $sq->where('barangay', $filters['barangay']);
                });
            } elseif (!empty($filters['barangayCode'])) {
                $q->whereHas('household', function ($sq) use ($filters) {
                    $sq->where('barangay_code', $filters['barangayCode']);
                });
            }
        }]);

        // Apply program filter
        if (!empty($filters['program'])) {
            $query->where('id', $filters['program']);
        }

        // Get base program data
        $programs = $query->get();

        // Enhance program data with distribution details
        $this->enhanceProgramsWithDistributionData($programs, $filters);

        // Generate summary data
        $summaryData = $this->generateSummaryData($programs, $filters);

        // Generate chart data
        $chartData = $this->generateChartData($programs);

        return [
            'reportData' => $programs,
            'summaryData' => $summaryData,
            'chartData' => $chartData
        ];
    }

    /**
     * Generate paginated programs report
     *
     * @param array $filters
     * @param int $perPage
     * @return array
     */
    public function generatePaginatedReport(array $filters, int $perPage = 15)
    {
        // Build base query with counts
        $query = AyudaProgram::withCount(['distributions' => function ($q) use ($filters) {
            $q->where('status', $filters['status'] ?? 'distributed');

            // Apply date filters
            if (!empty($filters['dateFrom'])) {
                $q->whereDate('distribution_date', '>=', $filters['dateFrom']);
            }

            if (!empty($filters['dateTo'])) {
                $q->whereDate('distribution_date', '<=', $filters['dateTo']);
            }

            // Apply location filter
            if (!empty($filters['barangay'])) {
                $q->whereHas('household', function ($sq) use ($filters) {
                    $sq->where('barangay', $filters['barangay']);
                });
            } elseif (!empty($filters['barangayCode'])) {
                $q->whereHas('household', function ($sq) use ($filters) {
                    $sq->where('barangay_code', $filters['barangayCode']);
                });
            }
        }]);

        // Apply program filter
        if (!empty($filters['program'])) {
            $query->where('id', $filters['program']);
        }

        // Get paginated program data
        $programs = $this->paginateResults($query, $perPage);

        // Enhance paginated programs with distribution data
        $this->enhanceProgramsWithDistributionData($programs, $filters);

        // Summary data should be based on all matching programs, not just the current page
        $summaryData = $this->generateSummaryData($programs, $filters);

        // Generate chart data from all programs that match the filter
        $allPrograms = clone $query;
        $chartData = $this->generateChartData($allPrograms->get());

        return [
            'reportData' => $programs,
            'summaryData' => $summaryData,
            'chartData' => $chartData
        ];
    }

    /**
     * Enhance programs with distribution data
     *
     * @param \Illuminate\Database\Eloquent\Collection $programs
     * @param array $filters
     * @return void
     */
    protected function enhanceProgramsWithDistributionData($programs, array $filters)
    {
        foreach ($programs as $program) {
            $distributionQuery = Distribution::where('ayuda_program_id', $program->id)
                ->where('status', $filters['status'] ?? 'distributed');

            // Apply date filters
            if (!empty($filters['dateFrom'])) {
                $distributionQuery->whereDate('distribution_date', '>=', $filters['dateFrom']);
            }

            if (!empty($filters['dateTo'])) {
                $distributionQuery->whereDate('distribution_date', '<=', $filters['dateTo']);
            }

            // Apply location filter
            if (!empty($filters['barangay'])) {
                $distributionQuery->whereHas('household', function ($q) use ($filters) {
                    $q->where('barangay', $filters['barangay']);
                });
            } elseif (!empty($filters['barangayCode'])) {
                $distributionQuery->whereHas('household', function ($q) use ($filters) {
                    $q->where('barangay_code', $filters['barangayCode']);
                });
            }

            $program->total_distributed = $distributionQuery->sum('amount');
            $program->unique_beneficiaries = $distributionQuery->distinct('resident_id')->count('resident_id');
            $program->unique_households = $distributionQuery
                ->whereNotNull('household_id')
                ->distinct('household_id')
                ->count('household_id');

            $program->utilization = $program->total_budget > 0
                ? round(($program->total_distributed / $program->total_budget) * 100, 1)
                : null;
        }
    }

    /**
     * Generate summary data
     *
     * @param \Illuminate\Database\Eloquent\Collection $programs
     * @param array $filters
     * @return array
     */
    protected function generateSummaryData($programs, array $filters)
    {
        // For paginated collections, we need to get counts directly from DB for accuracy
        if ($programs instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $distributionQuery = Distribution::where('status', $filters['status'] ?? 'distributed');

            // Apply common filters
            if (!empty($filters['dateFrom'])) {
                $distributionQuery->whereDate('distribution_date', '>=', $filters['dateFrom']);
            }

            if (!empty($filters['dateTo'])) {
                $distributionQuery->whereDate('distribution_date', '<=', $filters['dateTo']);
            }

            if (!empty($filters['program'])) {
                $distributionQuery->where('ayuda_program_id', $filters['program']);
            }

            if (!empty($filters['barangay'])) {
                $distributionQuery->whereHas('household', function ($q) use ($filters) {
                    $q->where('barangay', $filters['barangay']);
                });
            } elseif (!empty($filters['barangayCode'])) {
                $distributionQuery->whereHas('household', function ($q) use ($filters) {
                    $q->where('barangay_code', $filters['barangayCode']);
                });
            }

            $totalPrograms = AyudaProgram::whereIn('id', $distributionQuery->select('ayuda_program_id')->distinct())->count();
            $totalDistributions = $distributionQuery->count();
            $totalAmount = $distributionQuery->sum('amount');
            $uniqueBeneficiaries = $distributionQuery->distinct('resident_id')->count('resident_id');
            $uniqueHouseholds = $distributionQuery->whereNotNull('household_id')->distinct('household_id')->count('household_id');

            return [
                'total_programs' => $totalPrograms,
                'total_distributions' => $totalDistributions,
                'total_amount' => $totalAmount,
                'unique_beneficiaries' => $uniqueBeneficiaries,
                'unique_households' => $uniqueHouseholds
            ];
        }

        // For normal collections, we can calculate from the collection
        return [
            'total_programs' => $programs->count(),
            'total_distributions' => $programs->sum('distributions_count'),
            'total_amount' => $programs->sum('total_distributed'),
            'unique_beneficiaries' => $this->getUniqueBeneficiariesCount($filters),
            'unique_households' => $this->getUniqueHouseholdsCount($filters),
        ];
    }

    /**
     * Get unique beneficiaries count
     *
     * @param array $filters
     * @return int
     */
    protected function getUniqueBeneficiariesCount(array $filters)
    {
        return Distribution::where('status', $filters['status'] ?? 'distributed')
            ->when(!empty($filters['dateFrom']), function ($q) use ($filters) {
                $q->whereDate('distribution_date', '>=', $filters['dateFrom']);
            })
            ->when(!empty($filters['dateTo']), function ($q) use ($filters) {
                $q->whereDate('distribution_date', '<=', $filters['dateTo']);
            })
            ->when(!empty($filters['barangay']), function ($q) use ($filters) {
                $q->whereHas('household', function ($sq) use ($filters) {
                    $sq->where('barangay', $filters['barangay']);
                });
            })
            ->when(!empty($filters['barangayCode']), function ($q) use ($filters) {
                $q->whereHas('household', function ($sq) use ($filters) {
                    $sq->where('barangay_code', $filters['barangayCode']);
                });
            })
            ->when(!empty($filters['program']), function ($q) use ($filters) {
                $q->where('ayuda_program_id', $filters['program']);
            })
            ->distinct('resident_id')
            ->count('resident_id');
    }

    /**
     * Get unique households count
     *
     * @param array $filters
     * @return int
     */
    protected function getUniqueHouseholdsCount(array $filters)
    {
        return Distribution::where('status', $filters['status'] ?? 'distributed')
            ->whereNotNull('household_id')
            ->when(!empty($filters['dateFrom']), function ($q) use ($filters) {
                $q->whereDate('distribution_date', '>=', $filters['dateFrom']);
            })
            ->when(!empty($filters['dateTo']), function ($q) use ($filters) {
                $q->whereDate('distribution_date', '<=', $filters['dateTo']);
            })
            ->when(!empty($filters['barangay']), function ($q) use ($filters) {
                $q->whereHas('household', function ($sq) use ($filters) {
                    $sq->where('barangay', $filters['barangay']);
                });
            })
            ->when(!empty($filters['barangayCode']), function ($q) use ($filters) {
                $q->whereHas('household', function ($sq) use ($filters) {
                    $sq->where('barangay_code', $filters['barangayCode']);
                });
            })
            ->when(!empty($filters['program']), function ($q) use ($filters) {
                $q->where('ayuda_program_id', $filters['program']);
            })
            ->distinct('household_id')
            ->count('household_id');
    }

    /**
     * Generate chart data
     *
     * @param \Illuminate\Database\Eloquent\Collection $programs
     * @return array
     */
    protected function generateChartData($programs)
    {
        $chartData = [];

        $programNames = $programs->pluck('name')->toArray();
        $programCounts = $programs->pluck('distributions_count')->toArray();
        $programAmounts = $programs->pluck('total_distributed')->toArray();

        // Format chart data for distributions by program
        $chartData['byProgram'] = $this->formatBarChartData([
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
        ]);

        // Format chart data for amounts by program
        $chartData['byAmount'] = $this->formatPieChartData([
            'labels' => $programNames,
            'values' => $programAmounts,
            'colors' => $this->getDefaultColors(count($programNames))
        ]);

        return $chartData;
    }

    /**
     * Format programs data for export
     *
     * @param \Illuminate\Database\Eloquent\Collection $programs
     * @return array
     */
    public function formatForExport($programs)
    {
        $data = [];
        $headers = [
            'Program Name',
            'Distributions',
            'Amount',
            'Beneficiaries',
            'Households',
            'Budget Utilization'
        ];

        foreach ($programs as $program) {
            $data[] = [
                $program->name,
                $program->distributions_count,
                $program->total_distributed,
                $program->unique_beneficiaries,
                $program->unique_households,
                $program->utilization ? $program->utilization . '%' : 'N/A'
            ];
        }

        return [
            'data' => $data,
            'headers' => $headers
        ];
    }
}
