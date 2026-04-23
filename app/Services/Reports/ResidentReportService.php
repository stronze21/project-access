<?php

namespace App\Services\Reports;

use App\Models\Resident;
use App\Models\Distribution;
use Illuminate\Support\Facades\DB;

class ResidentReportService extends ReportService
{
    /**
     * Generate residents report
     *
     * @param array $filters
     * @return array
     */
    public function generateReport(array $filters)
    {
        // Get residents with distribution data
        $query = $this->buildResidentQuery($filters);

        $residents = $query->get();

        // Enhance residents with distribution details
        $this->enhanceResidentsWithDistributionData($residents, $filters);

        // Generate summary data
        $summaryData = $this->generateSummaryData($residents);

        // Generate chart data
        $chartData = $this->generateChartData($residents);

        return [
            'reportData' => $residents,
            'summaryData' => $summaryData,
            'chartData' => $chartData
        ];
    }

    /**
     * Generate paginated residents report
     *
     * @param array $filters
     * @param int $perPage
     * @return array
     */
    public function generatePaginatedReport(array $filters, int $perPage = 15)
    {
        // Get residents with distribution data
        $query = $this->buildResidentQuery($filters);

        // Get paginated data
        $residents = $this->paginateResults($query, $perPage);

        // Enhance residents with distribution details
        $this->enhanceResidentsWithDistributionData($residents, $filters);

        // Get counts for summary without pagination
        $totalResidents = $query->count();

        // Get total distributions and amount
        $distributionQuery = Distribution::where('status', $filters['status'] ?? 'distributed');
        $this->applyCommonFilters($distributionQuery, $filters);

        $totalDistributions = $distributionQuery->count();
        $totalAmount = $distributionQuery->sum('amount');
        $averagePerBeneficiary = $totalResidents > 0 ? $totalAmount / $totalResidents : 0;

        // Demographic counts
        $seniorsCount = $query->where('is_senior_citizen', true)->count();
        $pwdCount = $query->where('is_pwd', true)->count();
        $soloParentsCount = $query->where('is_solo_parent', true)->count();

        // Generate summary data
        $summaryData = [
            'total_beneficiaries' => $totalResidents,
            'total_distributions' => $totalDistributions,
            'total_amount' => $totalAmount,
            'average_per_beneficiary' => $averagePerBeneficiary,
            'seniors_count' => $seniorsCount,
            'pwd_count' => $pwdCount,
            'solo_parents_count' => $soloParentsCount,
        ];

        // Generate chart data from all residents that match the filter
        $allResidents = $query->get();
        $chartData = $this->generateChartData($allResidents);

        return [
            'reportData' => $residents,
            'summaryData' => $summaryData,
            'chartData' => $chartData
        ];
    }

    /**
     * Build base resident query
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildResidentQuery(array $filters)
    {
        $query = Resident::with('household')
            ->whereHas('distributions', function ($q) use ($filters) {
                $q->where('status', $filters['status'] ?? 'distributed');

                // Apply date filters
                if (!empty($filters['dateFrom'])) {
                    $q->whereDate('distribution_date', '>=', $filters['dateFrom']);
                }

                if (!empty($filters['dateTo'])) {
                    $q->whereDate('distribution_date', '<=', $filters['dateTo']);
                }

                // Apply program filter
                if (!empty($filters['program'])) {
                    $q->where('ayuda_program_id', $filters['program']);
                }
            });

        // Apply location filter
        if (!empty($filters['barangay'])) {
            $query->whereHas('household', function ($q) use ($filters) {
                $q->where('barangay', $filters['barangay']);
            });
        } elseif (!empty($filters['barangayCode'])) {
            $query->whereHas('household', function ($q) use ($filters) {
                $q->where('barangay_code', $filters['barangayCode']);
            });
        }

        return $query;
    }

    /**
     * Enhance residents with distribution data
     *
     * @param \Illuminate\Database\Eloquent\Collection $residents
     * @param array $filters
     * @return void
     */
    protected function enhanceResidentsWithDistributionData($residents, array $filters)
    {
        foreach ($residents as $resident) {
            $distributionQuery = Distribution::where('resident_id', $resident->id)
                ->where('status', $filters['status'] ?? 'distributed');

            // Apply date filters
            if (!empty($filters['dateFrom'])) {
                $distributionQuery->whereDate('distribution_date', '>=', $filters['dateFrom']);
            }

            if (!empty($filters['dateTo'])) {
                $distributionQuery->whereDate('distribution_date', '<=', $filters['dateTo']);
            }

            // Apply program filter
            if (!empty($filters['program'])) {
                $distributionQuery->where('ayuda_program_id', $filters['program']);
            }

            $resident->distributions_count = $distributionQuery->count();
            $resident->total_received = $distributionQuery->sum('amount');
            $resident->programs_count = $distributionQuery->distinct('ayuda_program_id')->count('ayuda_program_id');

            // Get list of programs
            $resident->programs_list = $distributionQuery
                ->with('ayudaProgram')
                ->get()
                ->pluck('ayudaProgram.name')
                ->unique()
                ->implode(', ');
        }
    }

    /**
     * Generate summary data
     *
     * @param \Illuminate\Database\Eloquent\Collection $residents
     * @return array
     */
    protected function generateSummaryData($residents)
    {
        return [
            'total_beneficiaries' => $residents->count(),
            'total_distributions' => $residents->sum('distributions_count'),
            'total_amount' => $residents->sum('total_received'),
            'average_per_beneficiary' => $residents->count() > 0 ? $residents->avg('total_received') : 0,
            'seniors_count' => $residents->where('is_senior_citizen', true)->count(),
            'pwd_count' => $residents->where('is_pwd', true)->count(),
            'solo_parents_count' => $residents->where('is_solo_parent', true)->count(),
        ];
    }

    /**
     * Generate chart data
     *
     * @param \Illuminate\Database\Eloquent\Collection $residents
     * @return array
     */
    protected function generateChartData($residents)
    {
        $chartData = [];

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

        // Format chart data
        $chartData['byDemographic'] = $this->formatBarChartData([
            'labels' => $demographicLabels,
            'datasets' => [
                [
                    'label' => 'Beneficiaries',
                    'data' => $demographicCounts,
                    'backgroundColor' => $this->getDefaultColors(count($demographicLabels)),
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
        ]);

        return $chartData;
    }

    /**
     * Format residents data for export
     *
     * @param \Illuminate\Database\Eloquent\Collection $residents
     * @return array
     */
    public function formatForExport($residents)
    {
        $data = [];
        $headers = [
            'Name',
            'Household ID',
            'Distributions',
            'Total Received',
            'Programs',
            'Demographics'
        ];

        foreach ($residents as $resident) {
            $demographics = [];
            if ($resident->is_senior_citizen) $demographics[] = 'Senior';
            if ($resident->is_pwd) $demographics[] = 'PWD';
            if ($resident->is_solo_parent) $demographics[] = 'Solo Parent';
            if ($resident->is_pregnant) $demographics[] = 'Pregnant';
            if ($resident->is_lactating) $demographics[] = 'Lactating';
            if ($resident->is_indigenous) $demographics[] = 'Indigenous';

            $data[] = [
                $resident->full_name,
                $resident->household ? $resident->household->household_id : 'N/A',
                $resident->distributions_count,
                $resident->total_received,
                $resident->programs_list,
                implode(', ', $demographics)
            ];
        }

        return [
            'data' => $data,
            'headers' => $headers
        ];
    }
}
