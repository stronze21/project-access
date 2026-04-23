<?php

namespace App\Livewire;

use App\Models\DistributionBatch;
use App\Models\AyudaProgram;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Mary\Traits\Toast;

class DistributionBatchesList extends Component
{
    use WithPagination;
    use Toast;

    // Search and filters
    #[Url]
    public $search = '';

    #[Url]
    public $program = '';

    #[Url]
    public $status = 'all';

    #[Url]
    public $dateFrom = '';

    #[Url]
    public $dateTo = '';

    #[Url]
    public $sortField = 'batch_date';

    #[Url]
    public $sortDirection = 'desc';

    #[Url]
    public $perPage = 10;

    // Flags
    public $showFilters = false;

    // Programs list for filter
    public $programs = [];

    /**
     * Mount the component
     */
    public function mount()
    {
        $this->loadPrograms();

        // Set default date range to current month
        if (empty($this->dateFrom)) {
            $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        }

        if (empty($this->dateTo)) {
            $this->dateTo = now()->endOfMonth()->format('Y-m-d');
        }
    }

    /**
     * Load programs for filter
     */
    protected function loadPrograms()
    {
        $this->programs = AyudaProgram::orderBy('name')->get();
    }

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
        $this->program = '';
        $this->status = 'all';
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
        $this->sortField = 'batch_date';
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
     * Update batch status
     */
    public function updateStatus($batchId, $status)
    {
        $batch = DistributionBatch::find($batchId);

        if (!$batch) {
            $this->error('Batch not found');
            return;
        }

        $batch->status = $status;
        $batch->save();

        $this->success("Batch marked as " . ucfirst($status))
            ;
    }

    /**
     * Render the component
     */
    public function render()
    {
        $query = DistributionBatch::query()
            ->with('ayudaProgram')
            ->withCount('distributions');

        // Apply search
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('batch_number', 'like', '%' . $this->search . '%')
                  ->orWhere('location', 'like', '%' . $this->search . '%');
            });
        }

        // Apply program filter
        if (!empty($this->program)) {
            $query->where('ayuda_program_id', $this->program);
        }

        // Apply status filter
        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        // Apply date range filter
        if (!empty($this->dateFrom)) {
            $query->whereDate('batch_date', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->whereDate('batch_date', '<=', $this->dateTo);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        // Paginate results
        $batches = $query->paginate($this->perPage);

        return view('livewire.distribution-batches-list', [
            'batches' => $batches
        ]);
    }
}