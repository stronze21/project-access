<?php

namespace App\Livewire;

use Mary\Traits\Toast;
use Livewire\Component;
use App\Models\AyudaProgram;
use App\Models\Distribution;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class DistributionsList extends Component
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
    public $sortField = 'distribution_date';

    #[Url]
    public $sortDirection = 'desc';

    #[Url]
    public $perPage = 10;

    // Flags
    public $showFilters = false;
    public $showQrScanner = false;

    // Programs list for filter
    public $programs = [];

    // Listeners
    protected $listeners = [
        'scan-result' => 'handleScanResult'
    ];

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
     * Handle QR scan result
     */
    public function handleScanResult($result)
    {
        if ($result['found']) {
            if ($result['type'] === 'resident') {
                $this->search = $result['object']->full_name;
            } elseif ($result['type'] === 'household') {
                $this->search = $result['object']->household_id;
            }
        }
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
        $this->sortField = 'distribution_date';
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
     * Update distribution status
     */
    public function updateStatus($distributionId, $status)
    {
        $distribution = Distribution::find($distributionId);

        if (!$distribution) {
            $this->error('Distribution not found');
            return;
        }

        $distribution->status = $status;
        $distribution->save();

        // If distributed, update program statistics
        if ($status === 'distributed') {
            $program = $distribution->ayudaProgram;
            $program->recordDistribution($distribution->amount);

            if ($distribution->batch_id) {
                $distribution->batch->updateStats();
            }
        }

        $this->success("Distribution marked as " . ucfirst($status))
            ;
    }

    /**
     * Render the component
     */
    public function render()
    {
        $query = Distribution::query()
            ->with(['resident', 'household', 'ayudaProgram', 'batch']);

        // Apply search
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('reference_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('resident', function($q) {
                      $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$this->search}%");
                  })
                  ->orWhereHas('household', function($q) {
                      $q->where('household_id', 'like', "%{$this->search}%");
                  });
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
            $query->whereDate('distribution_date', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->whereDate('distribution_date', '<=', $this->dateTo);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        // Paginate results
        $distributions = $query->paginate($this->perPage);

        return view('livewire.distributions-list', [
            'distributions' => $distributions
        ]);
    }
}
