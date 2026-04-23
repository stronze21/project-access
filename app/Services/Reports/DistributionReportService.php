<?php

namespace App\Services\Reports;

use App\Models\Distribution;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DistributionReportService extends BaseReportService
{
    /**
     * Initialize the query - creates a fresh query instance
     */
    protected function initializeQuery(): void
    {
        $this->query = Distribution::query()
            ->with(['resident', 'household', 'ayudaProgram'])
            ->where('status', 'distributed');
    }

    /**
     * Apply custom filters specific to distributions
     */
    protected function applyCustomFilters(array $filters): void
    {
        // Custom filters can be added here if needed
    }

    /**
     * Get report data with all distributions
     */
    public function getReportData(array $filters = []): Collection
    {
        // Get a fresh query
        $query = $this->getFreshQuery();
        $this->applyFilters($filters, $query);

        return $query->with([
            'resident',
            'household',
            'ayudaProgram:id,name,goods_description,services_description'
        ])
            ->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get summary data for the report
     */
    public function getSummary(array $filters = []): array
    {
        // Create a fresh query for summary to avoid conflicts
        $query = Distribution::query()->where('status', 'distributed');
        $this->applyFilters($filters, $query);

        $totalCount = $query->count();
        $totalAmount = $query->sum('amount');
        $uniqueBeneficiaries = $query->distinct('resident_id')->count('resident_id');
        $uniqueHouseholds = $query->whereNotNull('household_id')->distinct('household_id')->count('household_id');
        $averageAmount = $totalCount > 0 ? $totalAmount / $totalCount : 0;

        return [
            'total_distributions' => $totalCount,
            'total_amount' => $totalAmount,
            'unique_beneficiaries' => $uniqueBeneficiaries,
            'unique_households' => $uniqueHouseholds,
            'average_amount' => $averageAmount,
        ];
    }

    /**
     * Get chart data for the report
     */
    public function getChartData(array $filters = [], string $chartType = 'bar'): array
    {
        $chartData = [];

        // Get distribution by date data
        $dateData = $this->getDistributionsByDateData($filters);
        $chartData['byDate'] = $this->formatBarChartData([
            'labels' => array_column($dateData, 'date'),
            'datasets' => [
                [
                    'label' => 'Count',
                    'data' => array_column($dateData, 'count'),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1
                ],
                [
                    'label' => 'Amount',
                    'data' => array_column($dateData, 'amount'),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 1,
                    'type' => 'line',
                    'yAxisID' => 'y1'
                ]
            ],
            'options' => [
                'scales' => [
                    'y' => [
                        'beginAtZero' => true,
                        'title' => [
                            'display' => true,
                            'text' => 'Count'
                        ]
                    ],
                    'y1' => [
                        'beginAtZero' => true,
                        'position' => 'right',
                        'title' => [
                            'display' => true,
                            'text' => 'Amount'
                        ],
                        'grid' => [
                            'drawOnChartArea' => false,
                        ]
                    ],
                    'x' => [
                        'title' => [
                            'display' => true,
                            'text' => 'Date'
                        ]
                    ]
                ],
                'responsive' => true,
                'maintainAspectRatio' => false
            ]
        ]);

        // Get distribution by program data
        $programData = $this->getDistributionsByProgramData($filters);
        $chartData['byProgram'] = $this->formatPieChartData([
            'labels' => array_column($programData, 'program_name'),
            'values' => array_column($programData, 'count'),
            'colors' => $this->getDefaultColors(count($programData)),
            'options' => [
                'responsive' => true,
                'maintainAspectRatio' => false,
                'plugins' => [
                    'legend' => [
                        'position' => 'right',
                    ]
                ]
            ]
        ]);

        return $chartData;
    }

    /**
     * Get distributions by date data
     */
    private function getDistributionsByDateData(array $filters = []): array
    {
        // Create a fresh query to avoid conflicts with existing orders/groups
        $query = Distribution::query()->where('status', 'distributed');
        $this->applyFilters($filters, $query);

        return $query->select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(amount) as amount')
        )
            ->groupBy('date')
            ->orderBy('date', 'desc')  // Order by the grouped date field
            ->get()
            ->toArray();
    }

    /**
     * Get distributions by program data
     */
    private function getDistributionsByProgramData(array $filters = []): array
    {
        // Create a fresh query to avoid conflicts
        $query = Distribution::query()->where('status', 'distributed');
        $this->applyFilters($filters, $query);

        return $query->select(
            'ayuda_programs.name as program_name',
            DB::raw('COUNT(*) as count'),
            DB::raw('SUM(distributions.amount) as amount')
        )
            ->join('ayuda_programs', 'distributions.ayuda_program_id', '=', 'ayuda_programs.id')
            ->groupBy('ayuda_programs.name', 'ayuda_programs.id')  // Include ID to satisfy GROUP BY
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /**
     * Format distributions data for export
     */
    public function formatForExport($distributions): array
    {
        $data = [];
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

        foreach ($distributions as $distribution) {
            $data[] = [
                $distribution->reference_number,
                $distribution->created_at->format('Y-m-d g:i A'),
                $distribution->resident->full_name,
                $distribution->household ? $distribution->household->household_id : 'N/A',
                $distribution->household ? $distribution->household->barangay : 'N/A',
                $distribution->ayudaProgram->name,
                $distribution->amount,
                ucfirst($distribution->status),
                $distribution->distributor ? $distribution->distributor->name : 'N/A',
                $distribution->notes
            ];
        }

        return [
            'headers' => $headers,
            'data' => $data
        ];
    }

    /**
     * Get default chart options for distributions by date
     */
    protected function getDefaultBarChartOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Count'
                    ]
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Date'
                    ]
                ]
            ],
            'responsive' => true,
            'maintainAspectRatio' => false
        ];
    }

    /**
     * Get default chart options for pie charts
     */
    protected function getDefaultPieChartOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                ]
            ]
        ];
    }
}
