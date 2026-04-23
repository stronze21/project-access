<?php

namespace App\Livewire;

use App\Models\AyudaProgram;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Mary\Traits\Toast;
use Carbon\Carbon;

class AyudaProgramsList extends Component
{
    use WithPagination;
    use Toast;

    // Search and filters
    #[Url]
    public $search = '';

    #[Url]
    public $type = '';

    #[Url]
    public $status = 'active';

    #[Url]
    public $sortField = 'created_at';

    #[Url]
    public $sortDirection = 'desc';

    #[Url]
    public $perPage = 10;

    // Flags
    public $showFilters = false;

    /**
     * Toggle the filters visibility
     */
    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    /**
     * Reset all filters
     */
    public function resetFilters()
    {
        $this->search = '';
        $this->type = '';
        $this->status = 'active';
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
    }

    /**
     * Sort results by field
     */
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Set program status
     */
    public function setProgramStatus($programId, $status)
    {
        $program = AyudaProgram::find($programId);

        if (!$program) {
            $this->error('Program not found');
            return;
        }

        $program->is_active = $status === 'active';
        $program->save();

        $this->success("Program marked as " . ($program->is_active ? 'active' : 'inactive'))
        ;
    }

    /**
     * Get program status label
     */
    public function getProgramStatus($program)
    {
        $today = Carbon::today();

        if (!$program->is_active) {
            return ['label' => 'Inactive', 'color' => 'red'];
        }

        if ($today->lt($program->start_date)) {
            return ['label' => 'Upcoming', 'color' => 'blue'];
        }

        if ($program->end_date && $today->gt($program->end_date)) {
            return ['label' => 'Completed', 'color' => 'gray'];
        }

        if ($program->max_beneficiaries && $program->current_beneficiaries >= $program->max_beneficiaries) {
            return ['label' => 'Full', 'color' => 'yellow'];
        }

        if ($program->total_budget && $program->budget_used >= $program->total_budget) {
            return ['label' => 'Budget Exhausted', 'color' => 'amber'];
        }

        return ['label' => 'Active', 'color' => 'green'];
    }

    /**
     * Render the component
     */
    public function render()
    {
        $query = AyudaProgram::query();

        // Apply search
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('code', 'like', '%' . $this->search . '%')
                ->orWhere('description', 'like', '%' . $this->search . '%');
            });
        }

        // Apply type filter
        if (!empty($this->type)) {
            $query->where('type', $this->type);
        }

        // Apply status filter
        if ($this->status === 'active') {
            $query->where('is_active', true);
        } elseif ($this->status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($this->status === 'upcoming') {
            $query->where('is_active', true)
                ->where('start_date', '>', now());
        } elseif ($this->status === 'completed') {
            $query->where('end_date', '<', now());
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        // Paginate results
        $programs = $query->paginate($this->perPage);

        // Calculate program statistics
        foreach ($programs as $program) {
            $program->status = $this->getProgramStatus($program);

            if ($program->total_budget > 0) {
                $program->budget_percent = min(100, round(($program->budget_used / $program->total_budget) * 100, 1));
            } else {
                $program->budget_percent = 0;
            }

            if ($program->max_beneficiaries > 0) {
                $program->beneficiary_percent = min(100, round(($program->current_beneficiaries / $program->max_beneficiaries) * 100, 1));
            } else {
                $program->beneficiary_percent = 0;
            }
        }

        return view('livewire.ayuda-programs-list', [
            'programs' => $programs
        ]);
    }
}
