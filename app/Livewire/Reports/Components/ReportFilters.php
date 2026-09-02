<?php

namespace App\Livewire\Reports\Components;

use App\Models\AyudaProgram;
use App\Models\CitizenServiceType;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipProgram;
use App\Services\Reports\SpecialSectorReportService;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class ReportFilters extends Component
{
    public $dateFrom;

    public $dateTo;

    public $program = '';

    public $sector = '';

    public $status = 'distributed';

    public $expanded = true;

    public $reportType = 'distributions';

    public $programs = [];

    public $sectorOptions = [];

    public $statusOptions = [];

    public $dateRangeOptions = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'this_week' => 'This Week',
        'last_week' => 'Last Week',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'this_quarter' => 'This Quarter',
        'last_quarter' => 'Last Quarter',
        'this_year' => 'This Year',
        'last_year' => 'Last Year',
        'custom' => 'Custom Range',
    ];

    public $selectedDateRange = 'this_month';

    public function mount($reportType = 'distributions', $dateFrom = null, $dateTo = null, $program = '', $status = null)
    {
        $this->reportType = $reportType;
        $this->dateFrom = $dateFrom ?: now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = $dateTo ?: now()->endOfMonth()->format('Y-m-d');
        $this->program = $program;
        $this->status = $status ?? $this->defaultStatusFor($reportType);
        $this->refreshFilterOptions();
    }

    #[On('reportTypeChanged')]
    public function onReportTypeChanged($type): void
    {
        $this->reportType = $type;
        $this->program = '';
        $this->sector = '';
        $this->status = $this->defaultStatusFor($type);
        $this->refreshFilterOptions();
        $this->emitFilterChanged();
    }

    protected function refreshFilterOptions(): void
    {
        $this->programs = match ($this->reportType) {
            'scholarships' => ScholarshipProgram::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($program) => ['id' => $program->id, 'name' => $program->name])
                ->all(),
            'citizen-services' => CitizenServiceType::query()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['code', 'name'])
                ->map(fn ($type) => ['id' => $type->code, 'name' => $type->name])
                ->all(),
            default => AyudaProgram::query()
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn ($program) => ['id' => $program->id, 'name' => $program->name])
                ->all(),
        };

        $this->sectorOptions = SpecialSectorReportService::sectorOptions();

        $this->statusOptions = $this->statusOptionsFor($this->reportType);
    }

    /**
     * @return list<array{key: string, id: string}>
     */
    protected function statusOptionsFor(string $type): array
    {
        return match ($type) {
            'scholarships' => [
                ['key' => 'all', 'id' => 'All Statuses'],
                ['key' => ScholarshipApplication::STATUS_DRAFT, 'id' => 'Draft'],
                ['key' => ScholarshipApplication::STATUS_SUBMITTED, 'id' => 'Submitted'],
                ['key' => ScholarshipApplication::STATUS_UNDER_REVIEW, 'id' => 'Under Review'],
                ['key' => ScholarshipApplication::STATUS_NEEDS_RESUBMISSION, 'id' => 'Needs Resubmission'],
                ['key' => ScholarshipApplication::STATUS_CONDITIONALLY_APPROVED, 'id' => 'Conditionally Approved'],
                ['key' => ScholarshipApplication::STATUS_AWARDED, 'id' => 'Awarded'],
                ['key' => ScholarshipApplication::STATUS_REJECTED, 'id' => 'Rejected'],
            ],
            'citizen-services' => [
                ['key' => 'all', 'id' => 'All Statuses'],
                ['key' => 'submitted', 'id' => 'Submitted'],
                ['key' => 'reviewing', 'id' => 'Reviewing'],
                ['key' => 'processing', 'id' => 'Processing'],
                ['key' => 'for-release', 'id' => 'For Release'],
                ['key' => 'completed', 'id' => 'Completed'],
                ['key' => 'released', 'id' => 'Released'],
                ['key' => 'cancelled', 'id' => 'Cancelled'],
                ['key' => 'rejected', 'id' => 'Rejected'],
            ],
            'sectors', 'residents-with-id' => [
                ['key' => 'all', 'id' => 'All Statuses'],
            ],
            default => [
                ['key' => 'distributed', 'id' => 'Distributed'],
                ['key' => 'pending', 'id' => 'Pending'],
                ['key' => 'verified', 'id' => 'Verified'],
                ['key' => 'all', 'id' => 'All Statuses'],
            ],
        };
    }

    public function applyDateRange($range)
    {
        $this->selectedDateRange = $range;

        switch ($range) {
            case 'today':
                $this->dateFrom = Carbon::today()->format('Y-m-d');
                $this->dateTo = Carbon::today()->format('Y-m-d');
                break;

            case 'yesterday':
                $this->dateFrom = Carbon::yesterday()->format('Y-m-d');
                $this->dateTo = Carbon::yesterday()->format('Y-m-d');
                break;

            case 'this_week':
                $this->dateFrom = Carbon::now()->startOfWeek()->format('Y-m-d');
                $this->dateTo = Carbon::now()->endOfWeek()->format('Y-m-d');
                break;

            case 'last_week':
                $this->dateFrom = Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d');
                $this->dateTo = Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d');
                break;

            case 'this_month':
                $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
                $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
                break;

            case 'last_month':
                $this->dateFrom = Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d');
                $this->dateTo = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');
                break;

            case 'this_quarter':
                $this->dateFrom = Carbon::now()->startOfQuarter()->format('Y-m-d');
                $this->dateTo = Carbon::now()->endOfQuarter()->format('Y-m-d');
                break;

            case 'last_quarter':
                $this->dateFrom = Carbon::now()->subQuarter()->startOfQuarter()->format('Y-m-d');
                $this->dateTo = Carbon::now()->subQuarter()->endOfQuarter()->format('Y-m-d');
                break;

            case 'this_year':
                $this->dateFrom = Carbon::now()->startOfYear()->format('Y-m-d');
                $this->dateTo = Carbon::now()->endOfYear()->format('Y-m-d');
                break;

            case 'last_year':
                $this->dateFrom = Carbon::now()->subYear()->startOfYear()->format('Y-m-d');
                $this->dateTo = Carbon::now()->subYear()->endOfYear()->format('Y-m-d');
                break;

            case 'custom':
                break;
        }

        $this->emitFilterChanged();
    }

    public function updatedDateFrom()
    {
        $this->selectedDateRange = 'custom';
        $this->emitFilterChanged();
    }

    public function updatedDateTo()
    {
        $this->selectedDateRange = 'custom';
        $this->emitFilterChanged();
    }

    public function updatedProgram()
    {
        $this->emitFilterChanged();
    }

    public function updatedSector()
    {
        $this->emitFilterChanged();
    }

    public function updatedStatus()
    {
        $this->emitFilterChanged();
    }

    private function emitFilterChanged()
    {
        $this->dispatch('filtersChanged', [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'program' => $this->program,
            'sector' => $this->sector,
            'status' => $this->status,
        ]);
    }

    public function toggleExpanded()
    {
        $this->expanded = ! $this->expanded;
    }

    public function resetFilters()
    {
        $this->selectedDateRange = 'this_month';
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
        $this->program = '';
        $this->sector = '';
        $this->status = $this->defaultStatusFor($this->reportType);

        $this->emitFilterChanged();
    }

    public function showsDateFilters(): bool
    {
        return $this->reportType !== 'sectors';
    }

    public function showsProgramFilter(): bool
    {
        return in_array($this->reportType, ['distributions', 'programs', 'residents', 'barangays', 'scholarships', 'citizen-services'], true);
    }

    public function showsSectorFilter(): bool
    {
        return $this->reportType === 'sectors';
    }

    public function showsStatusFilter(): bool
    {
        return ! in_array($this->reportType, ['sectors', 'residents-with-id'], true);
    }

    public function programFilterLabel(): string
    {
        return match ($this->reportType) {
            'scholarships' => 'Scholarship Program',
            'citizen-services' => 'Service Type',
            default => 'Program',
        };
    }

    public function programFilterPlaceholder(): string
    {
        return match ($this->reportType) {
            'scholarships' => 'All programs',
            'citizen-services' => 'All service types',
            default => 'All programs',
        };
    }

    protected function defaultStatusFor(string $type): string
    {
        return match ($type) {
            'scholarships', 'sectors', 'citizen-services', 'residents-with-id' => 'all',
            default => 'distributed',
        };
    }

    public function render()
    {
        return view('livewire.reports.components.report-filters');
    }
}
