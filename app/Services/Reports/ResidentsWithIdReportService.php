<?php

namespace App\Services\Reports;

use App\Models\Resident;

class ResidentsWithIdReportService extends ReportService
{
    /**
     * Generate residents with ID report (paginated)
     *
     * @param array $filters
     * @param int $perPage
     * @return array
     */
    public function generatePaginatedReport(array $filters, int $perPage = 15)
    {
        $query = $this->buildQuery($filters);

        // Get total count before pagination
        $totalQuery = clone $query;
        $totalResidents = $totalQuery->count();

        // Paginate
        $residents = $this->paginateResults($query, $perPage);

        // Summary data
        $summaryData = $this->getSummaryData($filters);

        // Chart data
        $chartData = $this->getChartData($filters);

        return [
            'reportData' => $residents,
            'summaryData' => $summaryData,
            'chartData' => $chartData,
        ];
    }

    /**
     * Get full report data for export (no pagination)
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getReportData(array $filters)
    {
        return $this->buildQuery($filters)->get();
    }

    /**
     * Build the base query for residents with ID (signature is not null and not empty)
     *
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function buildQuery(array $filters)
    {
        $query = Resident::with('household')
            ->where(function ($q) {
                $q->whereNotNull('signature')
                  ->where('signature', '!=', '');
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

        // Apply search filter
        if (!empty($filters['searchTerm'])) {
            $term = $filters['searchTerm'];
            $query->where(function ($q) use ($term) {
                $q->where('first_name', 'like', "%{$term}%")
                  ->orWhere('last_name', 'like', "%{$term}%")
                  ->orWhere('middle_name', 'like', "%{$term}%")
                  ->orWhere('resident_id', 'like', "%{$term}%")
                  ->orWhereHas('household', function ($hq) use ($term) {
                      $hq->where('address', 'like', "%{$term}%")
                        ->orWhere('city_municipality', 'like', "%{$term}%")
                        ->orWhere('province', 'like', "%{$term}%");
                  });
            });
        }

        // Apply date filter on updated_at
        if (!empty($filters['dateFrom'])) {
            $query->where('updated_at', '>=', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $query->where('updated_at', '<=', $filters['dateTo']);
        }

        $query->orderBy('updated_at', 'desc');

        return $query;
    }

    /**
     * Get summary data for the report
     *
     * @param array $filters
     * @return array
     */
    protected function getSummaryData(array $filters)
    {
        $query = $this->buildQuery($filters);
        $totalWithId = $query->count();
        $totalResidents = Resident::count();
        $coverage = $totalResidents > 0 ? round(($totalWithId / $totalResidents) * 100, 1) : 0;

        return [
            'total_with_id' => $totalWithId,
            'total_residents' => $totalResidents,
            'coverage' => $coverage,
        ];
    }

    /**
     * Get chart data for the report
     *
     * @param array $filters
     * @return array
     */
    protected function getChartData(array $filters)
    {
        // Chart: residents with ID vs without ID
        $totalResidents = Resident::count();
        $withId = Resident::whereNotNull('signature')->where('signature', '!=', '')->count();
        $withoutId = $totalResidents - $withId;

        $chartData = [];

        $chartData['byIdStatus'] = $this->formatBarChartData([
            'labels' => ['With ID', 'Without ID'],
            'datasets' => [
                [
                    'label' => 'Residents',
                    'data' => [$withId, $withoutId],
                    'backgroundColor' => [
                        'rgba(16, 185, 129, 0.5)',
                        'rgba(239, 68, 68, 0.5)',
                    ],
                    'borderColor' => [
                        'rgb(16, 185, 129)',
                        'rgb(239, 68, 68)',
                    ],
                    'borderWidth' => 1
                ]
            ]
        ]);

        return $chartData;
    }

    /**
     * Format residents with ID data for CSV export
     *
     * @param \Illuminate\Database\Eloquent\Collection $residents
     * @return array
     */
    public function formatForExport($residents)
    {
        $headers = [
            'Resident',
            'Address',
            'Municipality',
            'Province',
            'Date Updated',
        ];

        $data = [];

        foreach ($residents as $resident) {
            $isObject = is_object($resident);

            $address = '';
            $municipality = '';
            $province = '';

            if ($isObject && isset($resident->household)) {
                $address = $resident->household->address ?? 'N/A';
                $municipality = $resident->household->city_municipality ?? 'N/A';
                $province = $resident->household->province ?? 'N/A';
            } elseif (isset($resident['household'])) {
                $address = $resident['household']['address'] ?? 'N/A';
                $municipality = $resident['household']['city_municipality'] ?? 'N/A';
                $province = $resident['household']['province'] ?? 'N/A';
            }

            $updatedAt = '';
            if ($isObject && isset($resident->updated_at)) {
                $updatedAt = $resident->updated_at->format('M d, Y g:i A');
            } elseif (isset($resident['updated_at'])) {
                $updatedAt = is_string($resident['updated_at'])
                    ? date('M d, Y g:i A', strtotime($resident['updated_at']))
                    : ($resident['updated_at']->format('M d, Y g:i A') ?? 'N/A');
            }

            $data[] = [
                $isObject ? ($resident->full_name ?? 'N/A') : ($resident['full_name'] ?? 'N/A'),
                $address,
                $municipality,
                $province,
                $updatedAt,
            ];
        }

        return [
            'headers' => $headers,
            'data' => $data,
        ];
    }
}
