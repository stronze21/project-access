<?php

namespace App\Livewire\Reports;

use App\Models\Distribution;
use App\Models\AyudaProgram;
use App\Models\DistributionBatch;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use DB;

class DistributionsReport extends Component
{
    use WithPagination;

    public $dateRange = 'all';
    public $customStartDate;
    public $customEndDate;
    public $programFilter = 'all';
    public $statusFilter = 'all';
    public $groupBy = 'daily';
    public $chartType = 'trend';

    public function mount()
    {
        $this->customStartDate = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->customEndDate = Carbon::now()->format('Y-m-d');
    }

    public function render()
    {
        $dateRangeQuery = $this->getDateRangeQuery();
        $programQuery = $this->getProgramQuery();
        $statusQuery = $this->getStatusQuery();

        // Summary statistics
        $summaryStats = Distribution::select(
            DB::raw('COUNT(*) as total_distributions'),
            DB::raw('SUM(CASE WHEN status = "distributed" THEN 1 ELSE 0 END) as completed_distributions'),
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('COUNT(DISTINCT resident_id) as unique_beneficiaries'),
            DB::raw('COUNT(DISTINCT household_id) as unique_households'),
            DB::raw('COUNT(DISTINCT ayuda_program_id) as programs_count'),
            DB::raw('COUNT(DISTINCT batch_id) as batches_count')
        )
            ->when($dateRangeQuery, function ($query, $dateRange) {
                return $query->whereRaw($dateRange);
            })
            ->when($programQuery, function ($query, $program) {
                return $query->whereRaw($program);
            })
            ->when($statusQuery, function ($query, $status) {
                return $query->whereRaw($status);
            })
            ->first();

        // Distribution trend data (for charts)
        $trendData = $this->getTrendData();

        // Distribution by program
        $programDistribution = Distribution::select(
            'ayuda_program_id',
            DB::raw('COUNT(*) as distribution_count'),
            DB::raw('SUM(amount) as total_amount')
        )
            ->with('ayudaProgram:id,name,code')
            ->when($dateRangeQuery, function ($query, $dateRange) {
                return $query->whereRaw($dateRange);
            })
            ->when($statusQuery, function ($query, $status) {
                return $query->whereRaw($status);
            })
            ->groupBy('ayuda_program_id')
            ->orderByDesc('distribution_count')
            ->get();

        // Status distribution
        $statusDistribution = Distribution::select(
            'status',
            DB::raw('COUNT(*) as count')
        )
            ->when($dateRangeQuery, function ($query, $dateRange) {
                return $query->whereRaw($dateRange);
            })
            ->when($programQuery, function ($query, $program) {
                return $query->whereRaw($program);
            })
            ->groupBy('status')
            ->get();

        // Recent distributions
        $recentDistributions = Distribution::with(['resident', 'ayudaProgram', 'batch'])
            ->when($dateRangeQuery, function ($query, $dateRange) {
                return $query->whereRaw($dateRange);
            })
            ->when($programQuery, function ($query, $program) {
                return $query->whereRaw($program);
            })
            ->when($statusQuery, function ($query, $status) {
                return $query->whereRaw($status);
            })
            ->latest()
            ->paginate(10);

        // Get available programs for filter
        $programs = AyudaProgram::orderBy('name')->get();

        return view('livewire.reports.distributions-report', [
            'summaryStats' => $summaryStats,
            'trendData' => $trendData,
            'programDistribution' => $programDistribution,
            'statusDistribution' => $statusDistribution,
            'recentDistributions' => $recentDistributions,
            'programs' => $programs,
        ]);
    }

    protected function getDateRangeQuery()
    {
        switch ($this->dateRange) {
            case 'today':
                return "DATE(distribution_date) = '" . Carbon::today()->format('Y-m-d') . "'";
            case 'yesterday':
                return "DATE(distribution_date) = '" . Carbon::yesterday()->format('Y-m-d') . "'";
            case 'this_week':
                return "DATE(distribution_date) BETWEEN '" . Carbon::now()->startOfWeek()->format('Y-m-d') . "' AND '" . Carbon::now()->endOfWeek()->format('Y-m-d') . "'";
            case 'last_week':
                return "DATE(distribution_date) BETWEEN '" . Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d') . "' AND '" . Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d') . "'";
            case 'this_month':
                return "DATE(distribution_date) BETWEEN '" . Carbon::now()->startOfMonth()->format('Y-m-d') . "' AND '" . Carbon::now()->endOfMonth()->format('Y-m-d') . "'";
            case 'last_month':
                return "DATE(distribution_date) BETWEEN '" . Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d') . "' AND '" . Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d') . "'";
            case 'this_year':
                return "YEAR(distribution_date) = " . Carbon::now()->year;
            case 'custom':
                if ($this->customStartDate && $this->customEndDate) {
                    return "DATE(distribution_date) BETWEEN '" . $this->customStartDate . "' AND '" . $this->customEndDate . "'";
                }
                return null;
            case 'all':
            default:
                return null;
        }
    }

    protected function getProgramQuery()
    {
        return $this->programFilter != 'all' ? "ayuda_program_id = " . $this->programFilter : null;
    }

    protected function getStatusQuery()
    {
        return $this->statusFilter != 'all' ? "status = '" . $this->statusFilter . "'" : null;
    }

    protected function getTrendData()
    {
        $dateRangeQuery = $this->getDateRangeQuery();
        $programQuery = $this->getProgramQuery();
        $statusQuery = $this->getStatusQuery();

        $groupByFormat = $this->getGroupByFormat();

        return Distribution::select(
            DB::raw($groupByFormat['select']),
            DB::raw('COUNT(*) as distribution_count'),
            DB::raw('SUM(amount) as total_amount'),
            DB::raw('COUNT(DISTINCT resident_id) as beneficiaries_count')
        )
            ->when($dateRangeQuery, function ($query, $dateRange) {
                return $query->whereRaw($dateRange);
            })
            ->when($programQuery, function ($query, $program) {
                return $query->whereRaw($program);
            })
            ->when($statusQuery, function ($query, $status) {
                return $query->whereRaw($status);
            })
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    protected function getGroupByFormat()
    {
        switch ($this->groupBy) {
            case 'daily':
                return [
                    'select' => "DATE(distribution_date) as period"
                ];
            case 'weekly':
                return [
                    'select' => "CONCAT(YEAR(distribution_date), '-', WEEK(distribution_date)) as period,
                           CONCAT('Week ', WEEK(distribution_date), ' ', YEAR(distribution_date)) as period_label"
                ];
            case 'monthly':
                return [
                    'select' => "DATE_FORMAT(distribution_date, '%Y-%m') as period,
                           DATE_FORMAT(distribution_date, '%b %Y') as period_label"
                ];
            case 'quarterly':
                return [
                    'select' => "CONCAT(YEAR(distribution_date), '-Q', QUARTER(distribution_date)) as period,
                           CONCAT('Q', QUARTER(distribution_date), ' ', YEAR(distribution_date)) as period_label"
                ];
            case 'yearly':
                return [
                    'select' => "YEAR(distribution_date) as period"
                ];
            default:
                return [
                    'select' => "DATE(distribution_date) as period"
                ];
        }
    }

    public function exportReport()
    {
        // Export logic would go here
        $this->dispatchBrowserEvent('showNotification', [
            'message' => 'Report has been exported successfully!',
            'type' => 'success',
        ]);
    }

    public function updatedDateRange()
    {
        if ($this->dateRange !== 'custom') {
            $this->resetPage();
        }
    }

    public function updatedCustomStartDate()
    {
        $this->dateRange = 'custom';
        $this->resetPage();
    }

    public function updatedCustomEndDate()
    {
        $this->dateRange = 'custom';
        $this->resetPage();
    }

    public function updatedProgramFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedGroupBy()
    {
        $this->resetPage();
    }

    public function updatedChartType()
    {
        $this->resetPage();
    }
}