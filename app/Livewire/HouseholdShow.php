<?php

namespace App\Livewire;

use App\Models\Distribution;
use App\Models\Household;
use App\Models\Resident;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class HouseholdShow extends Component
{
    use Toast;
    use WithPagination;

    public $household;

    public $householdId;

    public $showQrCode = false;

    public $perPage = 10;

    public $showAddMemberModal = false;

    public $memberSearch = '';

    public $selectedMemberId = null;

    public $memberRelationship = 'other_relative';

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
        $this->showQrCode = ! $this->showQrCode;
    }

    /**
     * Set household status
     */
    public function setHouseholdStatus($status)
    {
        $this->household->is_active = $status === 'active';
        $this->household->save();

        $this->success('Household marked as '.($this->household->is_active ? 'active' : 'inactive'));

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

        $this->success('Household member count updated');
    }

    /**
     * Calculate total household income
     */
    public function calculateTotalIncome()
    {
        $this->household->calculateTotalIncome();
        $this->loadHousehold();

        $this->success('Household income updated');
    }

    public function openAddMemberModal(): void
    {
        $this->resetValidation();
        $this->memberSearch = '';
        $this->selectedMemberId = null;
        $this->memberRelationship = 'other_relative';
        $this->showAddMemberModal = true;
    }

    public function addMember(): void
    {
        $validated = $this->validate([
            'selectedMemberId' => ['required', 'integer', 'exists:residents,id'],
            'memberRelationship' => ['required', 'in:spouse,child,sibling,parent,grandchild,grandparent,in-law,other_relative,non-relative,domestic_worker,boarder'],
        ]);

        $previousHouseholdId = null;

        DB::transaction(function () use ($validated, &$previousHouseholdId) {
            $resident = Resident::query()->lockForUpdate()->findOrFail($validated['selectedMemberId']);

            if ($resident->household_id && (int) $resident->household_id !== (int) $this->householdId) {
                $previousHouseholdId = (int) $resident->household_id;
                $previousHouseholdMembers = Resident::query()
                    ->where('household_id', $previousHouseholdId)
                    ->lockForUpdate()
                    ->get(['id']);

                if ($previousHouseholdMembers->count() !== 1) {
                    $this->addError(
                        'selectedMemberId',
                        'This resident can only be transferred when they are the sole member of their household.'
                    );

                    return;
                }
            }

            $resident->update([
                'household_id' => $this->householdId,
                'relationship_to_head' => $validated['memberRelationship'],
            ]);
        });

        if ($this->getErrorBag()->has('selectedMemberId')) {
            return;
        }

        $this->household->updateMemberCount();
        $this->household->calculateTotalIncome();

        if ($previousHouseholdId) {
            $previousHousehold = Household::find($previousHouseholdId);
            $previousHousehold?->updateMemberCount();
            $previousHousehold?->calculateTotalIncome();
        }

        $this->showAddMemberModal = false;
        $this->loadHousehold();
        $this->success('Household member added successfully.');
    }

    /**
     * Render the component
     */
    public function render()
    {
        $availableResidents = collect();

        if ($this->showAddMemberModal && mb_strlen(trim($this->memberSearch)) >= 2) {
            $term = trim($this->memberSearch);
            $nameParts = preg_split('/\s+/', $term, -1, PREG_SPLIT_NO_EMPTY);
            $singleResidentHouseholdIds = Resident::query()
                ->whereNotNull('household_id')
                ->where('household_id', '!=', $this->householdId)
                ->select('household_id')
                ->groupBy('household_id')
                ->havingRaw('COUNT(*) = 1')
                ->pluck('household_id');

            $availableResidents = Resident::query()
                ->with('household:id,household_id')
                ->where('is_active', true)
                ->where(function ($query) use ($singleResidentHouseholdIds) {
                    $query->whereNull('household_id')
                        ->orWhereIn('household_id', $singleResidentHouseholdIds);
                })
                ->where(function ($searchQuery) use ($term, $nameParts) {
                    $searchQuery->where(function ($query) use ($term) {
                        $query->where('resident_id', 'like', "%{$term}%")
                            ->orWhere('first_name', 'like', "%{$term}%")
                            ->orWhere('middle_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%")
                            ->orWhere('contact_number', 'like', "%{$term}%");
                    })->orWhere(function ($query) use ($nameParts) {
                        foreach ($nameParts as $part) {
                            $query->where(function ($nameQuery) use ($part) {
                                $nameQuery->where('first_name', 'like', "%{$part}%")
                                    ->orWhere('middle_name', 'like', "%{$part}%")
                                    ->orWhere('last_name', 'like', "%{$part}%");
                            });
                        }
                    });
                })
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->limit(10)
                ->get();
        }

        return view('livewire.household-show', [
            'distributions' => $this->getDistributions(),
            'availableResidents' => $availableResidents,
        ]);
    }
}
