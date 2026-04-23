<?php

namespace App\Livewire;

use App\Models\Distribution;
use Livewire\Component;
use Mary\Traits\Toast;

class DistributionShow extends Component
{
    use Toast;

    public $distribution;
    public $distributionId;
    public $notes = '';
    public $isEditingNotes = false;

    /**
     * Mount the component
     */
    public function mount($distributionId)
    {
        $this->distributionId = $distributionId;
        $this->loadDistribution();
    }

    /**
     * Load distribution data
     */
    protected function loadDistribution()
    {
        $this->distribution = Distribution::with([
            'resident',
            'household',
            'ayudaProgram',
            'batch',
            'distributor',
            'verifier'
        ])->findOrFail($this->distributionId);

        $this->notes = $this->distribution->notes ?? '';
    }

    public function editNotes()
    {
        abort_unless(auth()->user()?->can('create-distributions'), 403);

        $this->isEditingNotes = true;
        $this->notes = $this->distribution->notes ?? '';
    }

    public function cancelEditNotes()
    {
        $this->isEditingNotes = false;
        $this->resetErrorBag('notes');
        $this->notes = $this->distribution->notes ?? '';
    }

    public function saveNotes()
    {
        abort_unless(auth()->user()?->can('create-distributions'), 403);

        $validated = $this->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->distribution->notes = trim($validated['notes'] ?? '') ?: null;
        $this->distribution->save();

        $this->isEditingNotes = false;

        $this->success('Distribution notes updated successfully.');

        $this->loadDistribution();
    }

    /**
     * Update distribution status
     */
    public function updateStatus($status)
    {
        $previousStatus = $this->distribution->status;
        $this->distribution->status = $status;

        // Handle verification
        if ($status === 'verified' && $previousStatus === 'pending') {
            $this->distribution->verified_by = auth()->id();
        }

        // Handle distribution
        if ($status === 'distributed' && ($previousStatus === 'pending' || $previousStatus === 'verified')) {
            $this->distribution->distributed_by = auth()->id();

            // Update program statistics
            $program = $this->distribution->ayudaProgram;
            $program->recordDistribution($this->distribution->amount);

            // Update batch statistics if applicable
            if ($this->distribution->batch_id) {
                $this->distribution->batch->updateStats();
            }
        }

        $this->distribution->save();

        $this->success("Distribution marked as " . ucfirst($status))
            ;

        $this->loadDistribution();
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.distribution-show');
    }
}
