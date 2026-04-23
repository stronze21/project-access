<?php

namespace App\Livewire;

use App\Models\AyudaProgram;
use App\Models\EligibilityCriteria;
use App\Models\Distribution;
use App\Models\DistributionBatch;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AyudaProgramShow extends Component
{
    use WithPagination;
    use Toast;

    public $program;
    public $programId;
    public $perPage = 10;
    public $distributionTab = 'individual';

    // Charts data
    public $distributionData = [];
    public $locationData = [];

    /**
     * Mount the component
     */
    public function mount($programId)
    {
        $this->programId = $programId;
        $this->loadProgram();
        $this->loadChartData();
    }

    /**
     * Load program data
     */
    protected function loadProgram()
    {
        $this->program = AyudaProgram::with('eligibilityCriteria')
            ->findOrFail($this->programId);
    }

    /**
     * Load chart data
     */
    protected function loadChartData()
    {
        // Distribution by date
        $this->distributionData = Distribution::where('ayuda_program_id', $this->programId)
            ->where('status', 'distributed')
            ->select(
                DB::raw('DATE(distribution_date) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        // Distribution by location
        $this->locationData = Distribution::where('distributions.ayuda_program_id', $this->programId)
            ->where('distributions.status', 'distributed')
            ->join('households', 'distributions.household_id', '=', 'households.id')
            ->select(
                'households.barangay',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(distributions.amount) as total_amount'),
                DB::raw('COUNT(DISTINCT distributions.household_id) as household_count')
            )
            ->groupBy('households.barangay')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }

    /**
     * Set program status
     */
    public function setProgramStatus($status)
    {
        $this->program->is_active = $status === 'active';
        $this->program->save();

        $this->success("Program marked as " . ($this->program->is_active ? 'active' : 'inactive'))
            ;

        $this->loadProgram();
    }

    /**
     * Get individual distributions
     */
    public function getIndividualDistributions()
    {
        return Distribution::where('ayuda_program_id', $this->programId)
            ->with(['resident', 'household', 'distributor'])
            ->latest('distribution_date')
            ->paginate($this->perPage);
    }

    /**
     * Get distribution batches
     */
    public function getDistributionBatches()
    {
        return DistributionBatch::where('ayuda_program_id', $this->programId)
            ->withCount(['distributions'])
            ->latest('batch_date')
            ->paginate($this->perPage);
    }

    /**
     * Switch distribution tab
     */
    public function switchTab($tab)
    {
        $this->distributionTab = $tab;
        $this->resetPage();
    }

    /**
     * Get program status label and color
     */
    public function getProgramStatus()
    {
        $today = Carbon::today();

        if (!$this->program->is_active) {
            return ['label' => 'Inactive', 'color' => 'red'];
        }

        if ($today->lt($this->program->start_date)) {
            return ['label' => 'Upcoming', 'color' => 'blue'];
        }

        if ($this->program->end_date && $today->gt($this->program->end_date)) {
            return ['label' => 'Completed', 'color' => 'gray'];
        }

        if ($this->program->max_beneficiaries && $this->program->current_beneficiaries >= $this->program->max_beneficiaries) {
            return ['label' => 'Full', 'color' => 'yellow'];
        }

        if ($this->program->total_budget && $this->program->budget_used >= $this->program->total_budget) {
            return ['label' => 'Budget Exhausted', 'color' => 'amber'];
        }

        return ['label' => 'Active', 'color' => 'green'];
    }

    /**
     * Calculate budget and beneficiary percentages
     */
    protected function calculatePercentages()
    {
        if ($this->program->total_budget > 0) {
            $this->program->budget_percent = min(100, round(($this->program->budget_used / $this->program->total_budget) * 100, 1));
        } else {
            $this->program->budget_percent = 0;
        }

        if ($this->program->max_beneficiaries > 0) {
            $this->program->beneficiary_percent = min(100, round(($this->program->current_beneficiaries / $this->program->max_beneficiaries) * 100, 1));
        } else {
            $this->program->beneficiary_percent = 0;
        }
    }

    /**
     * Render the component
     */
    public function render()
    {
        $this->program->status = $this->getProgramStatus();
        $this->calculatePercentages();

        return view('livewire.ayuda-program-show', [
            'individualDistributions' => $this->getIndividualDistributions(),
            'distributionBatches' => $this->getDistributionBatches(),
        ]);
    }
}