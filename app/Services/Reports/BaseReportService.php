<?php

namespace App\Services\Reports;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

abstract class BaseReportService
{
    /**
     * The base query to use for the report
     */
    protected Builder $query;

    /**
     * The cache key prefix for this report
     */
    protected string $cachePrefix;

    /**
     * Default filter values
     */
    protected array $defaultFilters = [
        'dateFrom' => null,
        'dateTo' => null,
        'program' => null,
        'barangay' => null,
        'barangayCode' => null,
        'status' => 'distributed',
    ];

    /**
     * Construct the report service
     */
    public function __construct()
    {
        // Don't initialize query here - it will be initialized fresh for each request
        $this->cachePrefix = static::class . ':';
    }

    /**
     * Initialize the query - must be called before each operation
     */
    abstract protected function initializeQuery(): void;

    /**
     * Get a fresh query instance
     */
    protected function getFreshQuery(): Builder
    {
        // Always create a new query instance
        $this->initializeQuery();
        return $this->query;
    }

    /**
     * Apply filters to the query
     */
    public function applyFilters(array $filters = [], ?Builder $query = null): Builder
    {
        $filters = array_merge($this->defaultFilters, $filters);

        // If no query provided, get a fresh one
        if ($query === null) {
            $query = $this->getFreshQuery();
        }

        // Apply date filters
        if (!empty($filters['dateFrom'])) {
            $query->whereDate('distributions.created_at', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->whereDate('distributions.created_at', '<=', $filters['dateTo']);
        }

        // Apply program filter
        if (!empty($filters['program'])) {
            $query->where('ayuda_program_id', $filters['program']);
        }

        // Apply status filter
        if (!empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        // Apply location filters
        if (!empty($filters['barangay'])) {
            $query->whereHas('household', function ($q) use ($filters) {
                $q->where('barangay', $filters['barangay']);
            });
        } elseif (!empty($filters['barangayCode'])) {
            $query->whereHas('household', function ($q) use ($filters) {
                $q->where('barangay_code', $filters['barangayCode']);
            });
        }

        if (!empty($filters['searchTerm'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('reference_number', 'like', '%' . $filters['searchTerm'] . '%')
                    ->orWhereHas('resident', function ($q) use ($filters) {
                        $q->where(\DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%" . $filters['searchTerm'] . "%");
                    })
                    ->orWhereHas('household', function ($q) use ($filters) {
                        $q->where('household_id', 'like', "%" . $filters['searchTerm'] . "%");
                    });
            });
        }

        // Apply custom filters specific to report type
        $this->applyCustomFilters($filters);

        return $query;
    }

    /**
     * Apply custom filters specific to the report type
     */
    protected function applyCustomFilters(array $filters): void {}

    /**
     * Get summary data for the report
     */
    abstract public function getSummary(array $filters = []): array;

    /**
     * Get chart data for the report
     */
    abstract public function getChartData(array $filters = [], string $chartType = 'bar'): array;

    /**
     * Generate a full report
     */
    public function generateReport(array $filters = []): array
    {
        // Generate report data
        $reportData = $this->getReportData($filters);

        // Generate summary data
        $summaryData = $this->getSummary($filters);

        // Generate chart data
        $chartData = $this->getChartData($filters);

        return [
            'reportData' => $reportData,
            'summaryData' => $summaryData,
            'chartData' => $chartData
        ];
    }

    /**
     * Generate a paginated report
     */
    public function generatePaginatedReport(array $filters = [], int $perPage = 15): array
    {
        // Get paginated data
        $paginatedData = $this->getPaginatedData($filters, $perPage);

        // Get summary data
        $summaryData = $this->getSummary($filters);

        // Get chart data
        $chartData = $this->getChartData($filters);

        return [
            'reportData' => $paginatedData['items'],
            'pagination' => $paginatedData['pagination'],
            'summaryData' => $summaryData,
            'chartData' => $chartData
        ];
    }

    /**
     * Get detailed data for the report
     */
    abstract protected function getReportData(array $filters = []): Collection;

    /**
     * Get paginated data for the report
     */
    protected function getPaginatedData(array $filters = [], int $perPage = 15): array
    {
        $page = $filters['page'] ?? request()->get('page', 1);

        // Get a fresh query and apply filters
        $query = $this->getFreshQuery();
        $this->applyFilters($filters, $query);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        // Return both the paginator object and the pagination metadata
        return [
            'items' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage()
            ]
        ];
    }

    /**
     * Format data for export
     */
    abstract public function formatForExport($data): array;

    /**
     * Export report data to CSV
     */
    public function exportToCsv(array $filters = []): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $data = $this->getReportData($filters);
        $formattedData = $this->formatForExport($data);

        $headers = $formattedData['headers'];
        $data = $formattedData['data'];

        $callback = function () use ($headers, $data) {
            $file = fopen('php://output', 'w');
            // Add UTF-8 BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            fputcsv($file, $headers);

            foreach ($data as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="report-' . date('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Cache the report results - DISABLED
     */
    protected function cacheResults(string $key, $data, int $minutes = 60)
    {
        // Caching disabled - just return data
        return $data;
    }

    /**
     * Get cached report results - DISABLED
     */
    protected function getCachedResults(string $key, \Closure $callback)
    {
        // Caching disabled - just execute callback
        return $callback();
    }

    /**
     * Clear the cache for this report
     */
    public function clearCache(string $key = null): void
    {
        // No-op since caching is disabled
    }

    /**
     * Get cache key for filters
     */
    protected function getCacheKey(array $filters): string
    {
        return md5(serialize($filters));
    }

    /**
     * Format data for bar charts
     */
    protected function formatBarChartData(array $data): array
    {
        return [
            'labels' => $data['labels'] ?? [],
            'datasets' => $data['datasets'] ?? [],
            'options' => $data['options'] ?? $this->getDefaultBarChartOptions(),
        ];
    }

    /**
     * Format data for pie/doughnut charts
     */
    protected function formatPieChartData(array $data): array
    {
        return [
            'labels' => $data['labels'] ?? [],
            'datasets' => [
                [
                    'data' => $data['values'] ?? [],
                    'backgroundColor' => $data['colors'] ?? $this->getDefaultColors(count($data['labels'] ?? [])),
                    'borderWidth' => 1
                ]
            ],
            'options' => $data['options'] ?? $this->getDefaultPieChartOptions(),
        ];
    }

    /**
     * Format data for line charts
     */
    protected function formatLineChartData(array $data): array
    {
        return [
            'labels' => $data['labels'] ?? [],
            'datasets' => $data['datasets'] ?? [],
            'options' => $data['options'] ?? $this->getDefaultLineChartOptions(),
        ];
    }

    /**
     * Get default colors for charts
     */
    protected function getDefaultColors(int $count): array
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
     * Get default bar chart options
     */
    protected function getDefaultBarChartOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Value'
                    ]
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Category'
                    ]
                ]
            ],
            'responsive' => true,
            'maintainAspectRatio' => false
        ];
    }

    /**
     * Get default pie chart options
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

    /**
     * Get default line chart options
     */
    protected function getDefaultLineChartOptions(): array
    {
        return [
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Value'
                    ]
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Period'
                    ]
                ]
            ],
            'responsive' => true,
            'maintainAspectRatio' => false
        ];
    }
}
