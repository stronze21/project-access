<?php

namespace App\Services\Reports;

use App\Models\ScholarshipApplication;
use Illuminate\Database\Eloquent\Builder;

class ScholarshipApplicationReportService extends ReportService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generatePaginatedReport(array $filters, int $perPage = 15): array
    {
        $query = $this->buildQuery($filters);
        $page = (int) ($filters['page'] ?? 1);
        $applications = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'reportData' => $applications,
            'summaryData' => $this->getSummaryData($filters),
            'chartData' => $this->getChartData($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Database\Eloquent\Collection<int, ScholarshipApplication>
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
        $query = ScholarshipApplication::query()
            ->with(['resident.household', 'program', 'reviewer'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at');

        $this->applyDateFilter($query, $filters);
        $this->applyLocationFilter($query, $filters);
        $this->applySearchFilter($query, $filters);

        if (! empty($filters['program'])) {
            $query->where('scholarship_program_id', $filters['program']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'all') {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyDateFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['dateFrom'])) {
            $query->where('created_at', '>=', $filters['dateFrom']);
        }

        if (! empty($filters['dateTo'])) {
            $query->where('created_at', '<=', $filters['dateTo']);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyLocationFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['barangay'])) {
            $query->whereHas('resident.household', function ($householdQuery) use ($filters) {
                $householdQuery->where('barangay', $filters['barangay']);
            });
        } elseif (! empty($filters['barangayCode'])) {
            $query->whereHas('resident.household', function ($householdQuery) use ($filters) {
                $householdQuery->where('barangay_code', $filters['barangayCode']);
            });
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applySearchFilter(Builder $query, array $filters): void
    {
        if (empty($filters['searchTerm'])) {
            return;
        }

        $term = $filters['searchTerm'];
        $query->where(function ($searchQuery) use ($term) {
            $searchQuery->where('reference_number', 'like', "%{$term}%")
                ->orWhere('status', 'like', "%{$term}%")
                ->orWhereHas('resident', function ($residentQuery) use ($term) {
                    $residentQuery->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('middle_name', 'like', "%{$term}%")
                        ->orWhere('resident_id', 'like', "%{$term}%");
                })
                ->orWhereHas('program', function ($programQuery) use ($term) {
                    $programQuery->where('name', 'like', "%{$term}%");
                });
        });
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

        return [
            'total_applications' => (clone $baseQuery)->count(),
            'submitted_count' => (int) ($counts[ScholarshipApplication::STATUS_SUBMITTED] ?? 0),
            'under_review_count' => (int) ($counts[ScholarshipApplication::STATUS_UNDER_REVIEW] ?? 0),
            'conditionally_approved_count' => (int) ($counts[ScholarshipApplication::STATUS_CONDITIONALLY_APPROVED] ?? 0),
            'awarded_count' => (int) ($counts[ScholarshipApplication::STATUS_AWARDED] ?? 0),
            'rejected_count' => (int) ($counts[ScholarshipApplication::STATUS_REJECTED] ?? 0),
            'needs_resubmission_count' => (int) ($counts[ScholarshipApplication::STATUS_NEEDS_RESUBMISSION] ?? 0),
            'draft_count' => (int) ($counts[ScholarshipApplication::STATUS_DRAFT] ?? 0),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function getChartData(array $filters): array
    {
        $applications = $this->buildQuery($filters)->get();

        $byStatus = $applications->groupBy('status')->map->count();
        $statusLabels = $byStatus->keys()->map(function ($status) {
            $application = new ScholarshipApplication(['status' => $status]);

            return $application->statusLabel();
        })->values()->all();

        $byProgram = $applications->groupBy(function ($application) {
            return $application->program?->name ?? 'Unassigned';
        })->map->count();

        return [
            'byStatus' => $this->formatBarChartData([
                'labels' => $statusLabels,
                'datasets' => [[
                    'label' => 'Applications',
                    'data' => $byStatus->values()->all(),
                    'backgroundColor' => $this->getDefaultColors($byStatus->count()),
                    'borderWidth' => 1,
                ]],
            ]),
            'byProgram' => $this->formatBarChartData([
                'labels' => $byProgram->keys()->all(),
                'datasets' => [[
                    'label' => 'Applications',
                    'data' => $byProgram->values()->all(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                ]],
            ]),
        ];
    }

    public function formatForExport($applications): array
    {
        $headers = [
            'Reference Number',
            'Applicant',
            'Resident ID',
            'Barangay',
            'Program',
            'Applicant Type',
            'Status',
            'GWA',
            'Award Tier',
            'Submitted At',
        ];

        $data = [];

        foreach ($applications as $application) {
            $isObject = is_object($application);
            $resident = $isObject ? $application->resident : ($application['resident'] ?? []);
            $household = $isObject ? $resident?->household : ($resident['household'] ?? []);
            $program = $isObject ? $application->program : ($application['program'] ?? []);

            $applicant = 'N/A';
            if ($isObject && $resident) {
                $applicant = $resident->full_name;
            } elseif (isset($resident['full_name'])) {
                $applicant = $resident['full_name'];
            } elseif (isset($resident['last_name'], $resident['first_name'])) {
                $applicant = trim($resident['last_name'].', '.$resident['first_name']);
            }

            $submittedAt = 'N/A';
            $rawSubmitted = $isObject ? $application->submitted_at : ($application['submitted_at'] ?? null);
            if ($rawSubmitted) {
                $submittedAt = $rawSubmitted instanceof \DateTimeInterface
                    ? $rawSubmitted->timezone('Asia/Manila')->format('M d, Y g:i A')
                    : date('M d, Y g:i A', strtotime((string) $rawSubmitted));
            }

            $status = $isObject ? $application->status : ($application['status'] ?? '');
            $applicantType = $isObject ? $application->applicant_type : ($application['applicant_type'] ?? '');
            $awardTier = $isObject ? $application->award_tier : ($application['award_tier'] ?? null);

            $data[] = [
                $isObject ? $application->reference_number : ($application['reference_number'] ?? 'N/A'),
                $applicant,
                $isObject ? ($resident?->resident_id ?? 'N/A') : ($resident['resident_id'] ?? 'N/A'),
                $isObject ? ($household?->barangay ?? 'N/A') : ($household['barangay'] ?? 'N/A'),
                $isObject ? ($program?->name ?? 'N/A') : ($program['name'] ?? 'N/A'),
                $isObject ? $application->applicantTypeLabel() : ucfirst(str_replace('_', ' ', (string) $applicantType)),
                $isObject ? $application->statusLabel() : ucfirst(str_replace('_', ' ', (string) $status)),
                $isObject ? ($application->gwa !== null ? (string) $application->gwa : '') : ($application['gwa'] ?? ''),
                $isObject ? ($application->awardTierLabel() ?? '') : (ScholarshipApplication::TIER_LABELS[$awardTier] ?? ''),
                $submittedAt,
            ];
        }

        return [
            'headers' => $headers,
            'data' => $data,
        ];
    }
}
