<?php

namespace App\Services\Reports;

use App\Models\Distribution;
use App\Models\Household;
use Illuminate\Support\Facades\DB;

class GeographicReportService extends ReportService
{
    /**
     * Generate geographic (barangays) report
     *
     * @param array $filters
     * @return array
     */
    public function generateReport(array $filters)
    {
        // Get list of barangays to process
        $selectedBarangays = !empty($filters['barangay'])
            ? [$filters['barangay']]
            : $this->getBarangaysList();

        $barangayData = $this->processBarangaysData($selectedBarangays, $filters);

        // Generate summary data
        $summaryData = $this->generateSummaryData($barangayData);

        // Generate chart data
        $chartData = $this->generateChartData($barangayData);

        return [
            'reportData' => $barangayData,
            'summaryData' => $summaryData,
            'chartData' => $chartData
        ];
    }

    /**
     * Generate paginated geographic report
     *
     * @param array $filters
     * @param int $perPage
     * @return array
     */
    public function generatePaginatedReport(array $filters, int $perPage = 15)
    {
        // Get list of barangays to process
        $selectedBarangays = !empty($filters['barangay'])
            ? [$filters['barangay']]
            : $this->getBarangaysList();

        // Process data for all barangays
        $barangayData = $this->processBarangaysData($selectedBarangays, $filters);

        // Sort by distribution count in descending order
        usort($barangayData, function ($a, $b) {
            return $b['distributions_count'] <=> $a['distributions_count'];
        });

        // Generate summary before pagination
        $summaryData = $this->generateSummaryData($barangayData);

        // Generate chart data before pagination
        $chartData = $this->generateChartData($barangayData);

        // Paginate the results manually
        $page = request()->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedData = array_slice($barangayData, $offset, $perPage);

        // Create a custom paginator
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedData,
            count($barangayData),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'reportData' => $paginator,
            'summaryData' => $summaryData,
            'chartData' => $chartData
        ];
    }

    /**
     * Process barangays data
     *
     * @param array $barangays
     * @param array $filters
     * @return array
     */
    protected function processBarangaysData(array $barangays, array $filters)
    {
        $barangayData = [];

        foreach ($barangays as $barangay) {
            // Skip empty barangay names
            if (empty($barangay)) continue;

            // Get distribution data for this barangay
            $distributionQuery = Distribution::whereHas('household', function ($q) use ($barangay) {
                $q->where('barangay', $barangay);
            })
                ->where('status', $filters['status'] ?? 'distributed');

            // Apply date range filter
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

            $distributionsCount = $distributionQuery->count();

            // Only include barangays with distributions
            if ($distributionsCount > 0) {
                $totalAmount = $distributionQuery->sum('amount');
                $uniqueBeneficiaries = $distributionQuery->distinct('resident_id')->count('resident_id');
                $uniqueHouseholds = $distributionQuery->whereNotNull('household_id')->distinct('household_id')->count('household_id');

                // Get household count in barangay
                $totalHouseholds = Household::where('barangay', $barangay)->count();
                $coverage = $totalHouseholds > 0 ? round(($uniqueHouseholds / $totalHouseholds) * 100, 1) : 0;

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

        return $barangayData;
    }

    /**
     * Get list of all barangays
     *
     * @return array
     */
    protected function getBarangaysList()
    {
        return Household::select('barangay')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay')
            ->toArray();
    }

    /**
     * Generate summary data
     *
     * @param array $barangayData
     * @return array
     */
    protected function generateSummaryData($barangayData)
    {
        return [
            'total_barangays' => count($barangayData),
            'total_distributions' => array_sum(array_column($barangayData, 'distributions_count')),
            'total_amount' => array_sum(array_column($barangayData, 'total_amount')),
            'total_beneficiaries' => array_sum(array_column($barangayData, 'unique_beneficiaries')),
            'total_households_reached' => array_sum(array_column($barangayData, 'unique_households')),
        ];
    }

    /**
     * Generate chart data
     *
     * @param array $barangayData
     * @return array
     */
    protected function generateChartData($barangayData)
    {
        $chartData = [];

        // Sort by distributions count to show highest first
        usort($barangayData, function ($a, $b) {
            return $b['distributions_count'] <=> $a['distributions_count'];
        });

        // Limit to top 15 barangays for charts
        $topBarangays = array_slice($barangayData, 0, 15);

        $barangayNames = array_column($topBarangays, 'barangay');
        $barangayCounts = array_column($topBarangays, 'distributions_count');
        $barangayAmounts = array_column($topBarangays, 'total_amount');
        $barangayCoverage = array_column($topBarangays, 'coverage_percentage');

        // Format chart data - distribution by barangay
        $chartData['byBarangay'] = $this->formatBarChartData([
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
        ]);

        // Format chart data - amount by barangay
        $chartData['byAmount'] = $this->formatBarChartData([
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
        ]);

        // Format chart data - coverage by barangay
        $chartData['byCoverage'] = $this->formatBarChartData([
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
        ]);

        return $chartData;
    }

    /**
     * Format barangays data for export
     *
     * @param array $barangayData
     * @return array
     */
    public function formatForExport($barangayData)
    {
        $data = [];
        $headers = [
            'Barangay',
            'Distributions',
            'Total Amount',
            'Beneficiaries',
            'Households Reached',
            'Total Households',
            'Coverage (%)'
        ];

        foreach ($barangayData as $row) {
            $data[] = [
                $row['barangay'],
                $row['distributions_count'],
                $row['total_amount'],
                $row['unique_beneficiaries'],
                $row['unique_households'],
                $row['total_households'],
                $row['coverage_percentage'] . '%'
            ];
        }

        return [
            'data' => $data,
            'headers' => $headers
        ];
    }
}
