<?php

namespace App\Services\Reports;

use App\Models\AyudaProgram;
use App\Models\Distribution;
use App\Models\Household;
use App\Models\Resident;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Apply common filters to a query based on report parameters
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function applyCommonFilters($query, array $filters)
    {
        // Apply date range filter
        if (!empty($filters['dateFrom'])) {
            $query->whereDate('distribution_date', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->whereDate('distribution_date', '<=', $filters['dateTo']);
        }

        // Apply program filter
        if (!empty($filters['program'])) {
            $query->where('ayuda_program_id', $filters['program']);
        }

        // Apply status filter
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

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
     * Get formatted chart data for visualization
     *
     * @param array $data
     * @param string $type
     * @return array
     */
    protected function formatChartData(array $data, string $type)
    {
        switch ($type) {
            case 'bar':
                return $this->formatBarChartData($data);
            case 'pie':
            case 'doughnut':
                return $this->formatPieChartData($data);
            case 'line':
                return $this->formatLineChartData($data);
            default:
                return $data;
        }
    }

    /**
     * Format data for bar charts
     *
     * @param array $data
     * @return array
     */
    protected function formatBarChartData(array $data)
    {
        // Implementation of bar chart data formatting
        return [
            'labels' => $data['labels'] ?? [],
            'datasets' => $data['datasets'] ?? [],
        ];
    }

    /**
     * Format data for pie/doughnut charts
     *
     * @param array $data
     * @return array
     */
    protected function formatPieChartData(array $data)
    {
        // Implementation of pie chart data formatting
        return [
            'labels' => $data['labels'] ?? [],
            'datasets' => [
                [
                    'data' => $data['values'] ?? [],
                    'backgroundColor' => $data['colors'] ?? $this->getDefaultColors(count($data['labels'] ?? [])),
                    'borderWidth' => 1
                ]
            ]
        ];
    }

    /**
     * Format data for line charts
     *
     * @param array $data
     * @return array
     */
    protected function formatLineChartData(array $data)
    {
        // Implementation of line chart data formatting
        return [
            'labels' => $data['labels'] ?? [],
            'datasets' => $data['datasets'] ?? [],
        ];
    }

    /**
     * Get default colors for charts
     *
     * @param int $count
     * @return array
     */
    protected function getDefaultColors(int $count)
    {
        $baseColors = [
            'rgba(59, 130, 246, 0.5)',   // Blue
            'rgba(16, 185, 129, 0.5)',   // Green
            'rgba(249, 115, 22, 0.5)',   // Orange
            'rgba(139, 92, 246, 0.5)',   // Purple
            'rgba(236, 72, 153, 0.5)',   // Pink
            'rgba(245, 158, 11, 0.5)',   // Amber
            'rgba(107, 114, 128, 0.5)',  // Gray
        ];

        $colors = [];
        for ($i = 0; $i < $count; $i++) {
            $colors[] = $baseColors[$i % count($baseColors)];
        }

        return $colors;
    }

    /**
     * Apply pagination to results
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $perPage
     * @param string $pageName
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected function paginateResults($query, $perPage = 15, $pageName = 'page')
    {
        return $query->paginate($perPage, ['*'], $pageName);
    }
}
