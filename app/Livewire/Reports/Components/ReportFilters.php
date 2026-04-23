<?php

namespace App\Livewire\Reports\Components;

use Livewire\Component;
use App\Models\AyudaProgram;
use Carbon\Carbon;

class ReportFilters extends Component
{
    // Date range
    public $dateFrom;
    public $dateTo;

    // Filters
    public $program = '';
    public $status = 'distributed';
    public $expanded = true;

    // Programs list
    public $programs = [];

    // Date range shortcuts
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
        'custom' => 'Custom Range'
    ];

    public $selectedDateRange = 'this_month';

    /**
     * Mount the component
     */
    public function mount()
    {
        // Set default date range to current month
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');

        $this->loadPrograms();
    }

    /**
     * Load programs for filter
     */
    protected function loadPrograms()
    {
        $this->programs = AyudaProgram::orderBy('name')->get();
    }

    /**
     * Apply date range shortcut
     */
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
                // Do nothing, keep the current values
                break;
        }

        // If the date has changed programmatically, we need to dispatch
        // an event to notify the parent component
        $this->dispatch('filtersChanged', [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'program' => $this->program,
            'status' => $this->status
        ]);
    }

    /**
     * Update filter values when inputs change
     */
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

    public function updatedStatus()
    {
        $this->emitFilterChanged();
    }

    /**
     * Emit filter changed event to parent
     */
    private function emitFilterChanged()
    {
        $this->dispatch('filtersChanged', [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'program' => $this->program,
            'status' => $this->status
        ]);
    }

    /**
     * Toggle filter section expansion
     */
    public function toggleExpanded()
    {
        $this->expanded = !$this->expanded;
    }

    /**
     * Reset filters to default values
     */
    public function resetFilters()
    {
        $this->selectedDateRange = 'this_month';
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
        $this->program = '';
        $this->status = 'distributed';

        $this->emitFilterChanged();
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.reports.components.report-filters');
    }
}
