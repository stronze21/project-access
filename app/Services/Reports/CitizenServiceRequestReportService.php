<?php

namespace App\Services\Reports;

use App\Models\CitizenServiceRequest;
use Illuminate\Database\Eloquent\Builder;

class CitizenServiceRequestReportService extends ReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generatePaginatedReport(array $filters, int $perPage = 15): array
    {
        $query = $this->buildQuery($filters);
        $page = (int) ($filters['page'] ?? 1);
        $requests = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'reportData' => $requests,
            'summaryData' => $this->getSummaryData($filters),
            'chartData' => $this->getChartData($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Database\Eloquent\Collection<int, CitizenServiceRequest>
     */
    public function getReportData(array $filters)
    {
        return $this->buildQuery($filters)->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function buildQuery(array $filters): Builder
    {
        $query = CitizenServiceRequest::query()
            ->with('resident.household')
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at');

        if (! empty($filters['dateFrom'])) {
            $query->where('created_at', '>=', $filters['dateFrom']);
        }

        if (! empty($filters['dateTo'])) {
            $query->where('created_at', '<=', $filters['dateTo']);
        }

        if (! empty($filters['program'])) {
            $query->where('service_type', $filters['program']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['barangay'])) {
            $query->whereHas('resident.household', function ($householdQuery) use ($filters) {
                $householdQuery->where('barangay', $filters['barangay']);
            });
        } elseif (! empty($filters['barangayCode'])) {
            $query->whereHas('resident.household', function ($householdQuery) use ($filters) {
                $householdQuery->where('barangay_code', $filters['barangayCode']);
            });
        }

        if (! empty($filters['searchTerm'])) {
            $term = $filters['searchTerm'];
            $query->where(function ($searchQuery) use ($term) {
                $searchQuery->where('reference_number', 'like', "%{$term}%")
                    ->orWhere('service_name', 'like', "%{$term}%")
                    ->orWhere('service_type', 'like', "%{$term}%")
                    ->orWhere('status', 'like', "%{$term}%")
                    ->orWhereHas('resident', function ($residentQuery) use ($term) {
                        $residentQuery->where('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhere('resident_id', 'like', "%{$term}%");
                    });
            });
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    protected function getSummaryData(array $filters): array
    {
        $baseQuery = $this->buildQuery($filters);
        $baseQuery->getQuery()->orders = null;
        $baseQuery->setEagerLoads([]);

        $counts = (clone $baseQuery)
            ->select('status')
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $openStatuses = ['submitted', 'reviewing', 'processing', 'for-release'];

        return [
            'total_requests' => (clone $baseQuery)->count(),
            'open_count' => (clone $baseQuery)->whereIn('status', $openStatuses)->count(),
            'completed_count' => (int) ($counts['completed'] ?? 0) + (int) ($counts['released'] ?? 0),
            'rejected_count' => (int) ($counts['rejected'] ?? 0) + (int) ($counts['cancelled'] ?? 0),
            'submitted_count' => (int) ($counts['submitted'] ?? 0),
            'processing_count' => (int) ($counts['processing'] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function getChartData(array $filters): array
    {
        $requests = $this->buildQuery($filters)->get();
        $byStatus = $requests->groupBy('status')->map->count();
        $byType = $requests->groupBy(function ($request) {
            return $request->service_name ?: $request->service_type;
        })->map->count();

        return [
            'byStatus' => $this->formatBarChartData([
                'labels' => $byStatus->keys()->map(fn ($status) => ucwords(str_replace('-', ' ', (string) $status)))->values()->all(),
                'datasets' => [[
                    'label' => 'Requests',
                    'data' => $byStatus->values()->all(),
                    'backgroundColor' => $this->getDefaultColors($byStatus->count()),
                    'borderWidth' => 1,
                ]],
            ]),
            'byType' => $this->formatBarChartData([
                'labels' => $byType->keys()->all(),
                'datasets' => [[
                    'label' => 'Requests',
                    'data' => $byType->values()->all(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 1,
                ]],
            ]),
        ];
    }

    public function formatForExport($requests): array
    {
        $headers = [
            'Reference Number',
            'Resident',
            'Barangay',
            'Service Type',
            'Service Name',
            'Status',
            'Current Step',
            'Submitted At',
        ];

        $data = [];

        foreach ($requests as $request) {
            $isObject = is_object($request);
            $resident = $isObject ? $request->resident : ($request['resident'] ?? []);
            $household = $isObject ? $resident?->household : ($resident['household'] ?? []);

            $residentName = 'N/A';
            if ($isObject && $resident) {
                $residentName = $resident->full_name;
            } elseif (isset($resident['full_name'])) {
                $residentName = $resident['full_name'];
            } elseif (isset($resident['last_name'], $resident['first_name'])) {
                $residentName = trim($resident['last_name'].', '.$resident['first_name']);
            }

            $submittedAt = 'N/A';
            $rawSubmitted = $isObject ? $request->submitted_at : ($request['submitted_at'] ?? null);
            if ($rawSubmitted) {
                $submittedAt = $rawSubmitted instanceof \DateTimeInterface
                    ? $rawSubmitted->timezone('Asia/Manila')->format('M d, Y g:i A')
                    : date('M d, Y g:i A', strtotime((string) $rawSubmitted));
            }

            $status = $isObject ? $request->status : ($request['status'] ?? '');

            $data[] = [
                $isObject ? $request->reference_number : ($request['reference_number'] ?? 'N/A'),
                $residentName,
                $isObject ? ($household?->barangay ?? 'N/A') : ($household['barangay'] ?? 'N/A'),
                $isObject ? ($request->service_type ?? 'N/A') : ($request['service_type'] ?? 'N/A'),
                $isObject ? ($request->service_name ?? 'N/A') : ($request['service_name'] ?? 'N/A'),
                ucwords(str_replace('-', ' ', (string) $status)),
                $isObject ? ($request->current_step ?? '') : ($request['current_step'] ?? ''),
                $submittedAt,
            ];
        }

        return [
            'headers' => $headers,
            'data' => $data,
        ];
    }
}
