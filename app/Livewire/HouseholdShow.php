<?php

namespace App\Livewire;

use App\Models\Household;
use App\Models\Distribution;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class HouseholdShow extends Component
{
    use WithPagination;
    use Toast;

    public $household;
    public $householdId;
    public $showQrCode = false;
    public $perPage = 10;

    // Stats
    public $totalDistributions = 0;
    public $totalAidReceived = 0;

    /**
     * Mount the component
     */
    public function mount($householdId)
    {
        $this->householdId = $householdId;
        $this->loadHousehold();
        $this->loadStatistics();
    }

    /**
     * Load household data
     */
    protected function loadHousehold()
    {
        $this->household = Household::with(['residents' => function ($query) {
            $query->orderByRaw("CASE
                WHEN relationship_to_head = 'head' THEN 1
                WHEN relationship_to_head = 'spouse' THEN 2
                WHEN relationship_to_head = 'child' THEN 3
                ELSE 4
                END");
        }])->findOrFail($this->householdId);
    }

    /**
     * Load household statistics
     */
    protected function loadStatistics()
    {
        $this->totalDistributions = Distribution::where('household_id', $this->householdId)
            ->where('status', 'distributed')
            ->count();

        $this->totalAidReceived = Distribution::where('household_id', $this->householdId)
            ->where('status', 'distributed')
            ->sum('amount');
    }

    /**
     * Toggle QR code visibility
     */
    public function toggleQrCode()
    {
        $this->showQrCode = !$this->showQrCode;
    }

    /**
     * Set household status
     */
    public function setHouseholdStatus($status)
    {
        $this->household->is_active = $status === 'active';
        $this->household->save();

        $this->success("Household marked as " . ($this->household->is_active ? 'active' : 'inactive'))
            ;

        $this->loadHousehold();
    }

    /**
     * Get household's distributions
     */
    public function getDistributions()
    {
        return Distribution::where('household_id', $this->householdId)
            ->with(['ayudaProgram', 'resident', 'batch'])
            ->orderByDesc('distribution_date')
            ->paginate($this->perPage);
    }

    /**
     * Update household member count
     */
    public function updateMemberCount()
    {
        $this->household->updateMemberCount();
        $this->loadHousehold();

        $this->success("Household member count updated")
            ;
    }

    /**
     * Calculate total household income
     */
    public function calculateTotalIncome()
    {
        $this->household->calculateTotalIncome();
        $this->loadHousehold();

        $this->success("Household income updated")
            ;
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.household-show', [
            'distributions' => $this->getDistributions()
        ]);
    }
}
