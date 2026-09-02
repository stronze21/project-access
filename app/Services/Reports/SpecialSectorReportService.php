<?php

namespace App\Services\Reports;

use App\Models\Resident;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SpecialSectorReportService extends ReportService
{
    public const FLAG_SECTORS = [
        'scholar' => ['label' => 'Scholars', 'column' => 'is_scholar'],
        'pwd' => ['label' => 'PWD', 'column' => 'is_pwd'],
        'senior' => ['label' => 'Senior Citizens', 'column' => 'is_senior_citizen'],
        'solo_parent' => ['label' => 'Solo Parents', 'column' => 'is_solo_parent'],
        '4ps' => ['label' => '4Ps', 'column' => 'is_4ps'],
        'indigenous' => ['label' => 'Indigenous', 'column' => 'is_indigenous'],
        'bhw' => ['label' => 'BHW', 'column' => 'is_bhw'],
        'pregnant' => ['label' => 'Pregnant', 'column' => 'is_pregnant'],
        'lactating' => ['label' => 'Lactating', 'column' => 'is_lactating'],
    ];

    /**
     * @return list<array{id: string, name: string}>
     */
    public static function sectorOptions(): array
    {
        $options = [];

        foreach (self::FLAG_SECTORS as $id => $sector) {
            $options[] = ['id' => $id, 'name' => $sector['label']];
        }

        $customSectors = Resident::query()
            ->select('special_sector')
            ->whereNotNull('special_sector')
            ->where('special_sector', '!=', '')
            ->distinct()
            ->orderBy('special_sector')
            ->pluck('special_sector');

        foreach ($customSectors as $sector) {
            $options[] = [
                'id' => 'special:'.$sector,
                'name' => $sector,
            ];
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function generatePaginatedReport(array $filters, int $perPage = 15): array
    {
        $query = $this->buildQuery($filters);
        $page = (int) ($filters['page'] ?? 1);
        $residents = $query->paginate($perPage, ['*'], 'page', $page);
        $residents->getCollection()->transform(fn (Resident $resident) => $this->decorateResident($resident));

        return [
            'reportData' => $residents,
            'summaryData' => $this->getSummaryData($filters),
            'chartData' => $this->getChartData($filters),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Support\Collection<int, Resident>
     */
    public function getReportData(array $filters)
    {
        return $this->buildQuery($filters)
            ->get()
            ->map(fn (Resident $resident) => $this->decorateResident($resident));
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function buildQuery(array $filters): Builder
    {
        $query = Resident::query()
            ->with('household')
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name');

        $this->applySectorFilter($query, $filters['sector'] ?? '');
        $this->applyLocationFilter($query, $filters);
        $this->applySearchFilter($query, $filters);

        return $query;
    }

    protected function applySectorFilter(Builder $query, string $sector): void
    {
        if ($sector === '' || $sector === 'all') {
            $query->where(function ($sectorQuery) {
                foreach (self::FLAG_SECTORS as $definition) {
                    $sectorQuery->orWhere($definition['column'], true);
                }

                $sectorQuery->orWhere(function ($specialQuery) {
                    $specialQuery->whereNotNull('special_sector')
                        ->where('special_sector', '!=', '');
                });
            });

            return;
        }

        if (str_starts_with($sector, 'special:')) {
            $query->where('special_sector', substr($sector, strlen('special:')));

            return;
        }

        if (isset(self::FLAG_SECTORS[$sector])) {
            $query->where(self::FLAG_SECTORS[$sector]['column'], true);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    protected function applyLocationFilter(Builder $query, array $filters): void
    {
        if (! empty($filters['barangay'])) {
            $query->whereHas('household', function ($householdQuery) use ($filters) {
                $householdQuery->where('barangay', $filters['barangay']);
            });
        } elseif (! empty($filters['barangayCode'])) {
            $query->whereHas('household', function ($householdQuery) use ($filters) {
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
            $searchQuery->where('first_name', 'like', "%{$term}%")
                ->orWhere('last_name', 'like', "%{$term}%")
                ->orWhere('middle_name', 'like', "%{$term}%")
                ->orWhere('resident_id', 'like', "%{$term}%")
                ->orWhere('special_sector', 'like', "%{$term}%");
        });
    }

    protected function decorateResident(Resident $resident): Resident
    {
        $resident->setAttribute('sectors_list', $this->sectorsFor($resident));
        $resident->setAttribute('scholar_status', $resident->is_scholar ? 'Scholar' : 'Not a scholar');

        return $resident;
    }

    /**
     * @return list<string>
     */
    public function sectorsFor(Resident $resident): array
    {
        $sectors = [];

        foreach (self::FLAG_SECTORS as $definition) {
            if ($resident->{$definition['column']}) {
                $sectors[] = $definition['label'];
            }
        }

        if (filled($resident->special_sector) && ! in_array($resident->special_sector, $sectors, true)) {
            $sectors[] = $resident->special_sector;
        }

        return $sectors;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    protected function getSummaryData(array $filters): array
    {
        $baseQuery = $this->buildQuery($filters);

        $counts = [];
        foreach (self::FLAG_SECTORS as $id => $definition) {
            $counts[$id.'_count'] = (clone $baseQuery)->where($definition['column'], true)->count();
        }

        return array_merge([
            'total_in_sectors' => (clone $baseQuery)->count(),
            'custom_sector_count' => (clone $baseQuery)
                ->whereNotNull('special_sector')
                ->where('special_sector', '!=', '')
                ->count(),
        ], $counts);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    protected function getChartData(array $filters): array
    {
        $query = $this->buildQuery($filters);
        $labels = [];
        $values = [];

        foreach (self::FLAG_SECTORS as $definition) {
            $labels[] = $definition['label'];
            $values[] = (clone $query)->where($definition['column'], true)->count();
        }

        $customSectors = (clone $query)
            ->whereNotNull('special_sector')
            ->where('special_sector', '!=', '')
            ->select('special_sector', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('special_sector')
            ->pluck('aggregate', 'special_sector');

        foreach ($customSectors as $name => $count) {
            if (in_array($name, $labels, true)) {
                continue;
            }
            $labels[] = $name;
            $values[] = (int) $count;
        }

        return [
            'bySector' => $this->formatBarChartData([
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Residents',
                    'data' => $values,
                    'backgroundColor' => $this->getDefaultColors(count($labels)),
                    'borderWidth' => 1,
                ]],
            ]),
        ];
    }

    public function formatForExport($residents): array
    {
        $headers = [
            'Name',
            'Resident ID',
            'Barangay',
            'Scholar Status',
            'Sectors',
            'Special Sector',
        ];

        $data = [];

        foreach ($residents as $resident) {
            $isObject = is_object($resident);
            $household = $isObject ? $resident->household : ($resident['household'] ?? []);
            $sectors = $isObject
                ? ($resident->sectors_list ?? $this->sectorsFor($resident))
                : ($resident['sectors_list'] ?? []);

            $data[] = [
                $isObject ? ($resident->full_name ?? 'N/A') : ($resident['full_name'] ?? 'N/A'),
                $isObject ? ($resident->resident_id ?? 'N/A') : ($resident['resident_id'] ?? 'N/A'),
                $isObject ? ($household?->barangay ?? 'N/A') : ($household['barangay'] ?? 'N/A'),
                $isObject
                    ? ($resident->is_scholar ? 'Scholar' : 'Not a scholar')
                    : (! empty($resident['is_scholar']) ? 'Scholar' : 'Not a scholar'),
                is_array($sectors) ? implode(', ', $sectors) : (string) $sectors,
                $isObject ? ($resident->special_sector ?: '') : ($resident['special_sector'] ?? ''),
            ];
        }

        return [
            'headers' => $headers,
            'data' => $data,
        ];
    }
}
