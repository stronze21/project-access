<?php

namespace App\Livewire;

use App\Models\AyudaProgram;
use App\Models\Distribution;
use App\Models\DistributionBatch;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BatchVerification extends Component
{
    use WithPagination;
    use Toast;

    public $barangay = '';
    public $programId = '';
    public $batchId = '';
    public $status = 'pending'; // Default to pending distributions
    public $search = '';
    public $dateFrom;
    public $dateTo;
    public $perPage = 10;

    public $selectedDistributions = [];
    public $selectAll = false;
    public $processingVerification = false;
    public $verificationComplete = false;
    public $verificationResults = [];
    public $verificationNote = '';
    public $targetStatus = 'verified';

    // Lists for dropdowns
    public $barangayList = [];
    public $programList = [];
    public $batchList = [];

    public function mount()
    {
        // Set default date range to current month
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');

        $this->loadDropdownData();
    }

    protected function loadDropdownData()
    {
        // Load barangay list
        $this->barangayList = Distribution::join('residents', 'distributions.resident_id', '=', 'residents.id')
            ->join('households', 'residents.household_id', '=', 'households.id')
            ->select('households.barangay')
            ->distinct()
            ->pluck('households.barangay')
            ->toArray();

        sort($this->barangayList);

        // Load programs
        $this->programList = AyudaProgram::orderBy('name')->get();

        // Load batches
        $this->batchList = DistributionBatch::orderBy('batch_date', 'desc')->get();
    }

    public function updatedSelectAll()
    {
        if ($this->selectAll) {
            $distributions = $this->getDistributionsQuery()->get();
            foreach ($distributions as $distribution) {
                $this->selectedDistributions[$distribution->id] = true;
            }
        } else {
            $this->selectedDistributions = [];
        }
    }

    public function getDistributionsQuery()
    {
        $query = Distribution::query()
            ->with(['resident', 'household', 'ayudaProgram', 'batch'])
            ->where('status', $this->status);

        // Apply search
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('reference_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('resident', function($sq) {
                      $sq->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$this->search}%");
                  });
            });
        }

        // Apply program filter
        if (!empty($this->programId)) {
            $query->where('ayuda_program_id', $this->programId);
        }

        // Apply batch filter
        if (!empty($this->batchId)) {
            $query->where('batch_id', $this->batchId);
        }

        // Apply barangay filter
        if (!empty($this->barangay)) {
            $query->whereHas('resident.household', function($q) {
                $q->where('barangay', $this->barangay);
            });
        }

        // Apply date range filter
        if (!empty($this->dateFrom)) {
            $query->whereDate('distribution_date', '>=', $this->dateFrom);
        }

        if (!empty($this->dateTo)) {
            $query->whereDate('distribution_date', '<=', $this->dateTo);
        }

        return $query;
    }

    public function getDistributionsPaginated()
    {
        return $this->getDistributionsQuery()->paginate($this->perPage);
    }

    public function previewVerification()
    {
        $selectedIds = array_keys(array_filter($this->selectedDistributions));

        if (count($selectedIds) === 0) {
            $this->warning('Please select at least one distribution to verify');
            return;
        }

        // Preview the selected distributions
        $this->verificationResults = Distribution::whereIn('id', $selectedIds)
            ->with(['resident', 'ayudaProgram'])
            ->get()
            ->map(function($distribution) {
                return [
                    'id' => $distribution->id,
                    'reference_number' => $distribution->reference_number,
                    'resident_name' => $distribution->resident->full_name,
                    'program_name' => $distribution->ayudaProgram->name,
                    'amount' => $distribution->amount,
                    'status' => $distribution->status
                ];
            })
            ->toArray();
    }

    public function processVerification()
    {
        $selectedIds = array_keys(array_filter($this->selectedDistributions));

        if (count($selectedIds) === 0) {
            $this->warning('Please select at least one distribution to verify');
            return;
        }

        $this->processingVerification = true;

        try {
            DB::beginTransaction();

            // Get the selected distributions for results display
            $this->verificationResults = Distribution::whereIn('id', $selectedIds)
                ->with(['resident', 'ayudaProgram'])
                ->get()
                ->map(function($distribution) {
                    return [
                        'id' => $distribution->id,
                        'reference_number' => $distribution->reference_number,
                        'resident_name' => $distribution->resident->full_name,
                        'program_name' => $distribution->ayudaProgram->name,
                        'amount' => $distribution->amount,
                        'status' => $distribution->status
                    ];
                })
                ->toArray();

            // Update all selected distributions
            $updateData = [
                'status' => $this->targetStatus,
                'verified_by' => Auth::id()
            ];

            if (!empty($this->verificationNote)) {
                $updateData['notes'] = DB::raw("CONCAT(IFNULL(notes, ''), ' [Verification Note: " .
                    addslashes($this->verificationNote) . " - " . now()->format('Y-m-d H:i') . "]')");
            }

            // If marking as distributed, set distributed_by and update program/batch stats
            if ($this->targetStatus === 'distributed') {
                $updateData['distributed_by'] = Auth::id();

                // Update program and batch statistics
                $distributions = Distribution::whereIn('id', $selectedIds)
                    ->with(['ayudaProgram', 'batch'])
                    ->get();

                foreach ($distributions as $distribution) {
                    // Update program statistics
                    $distribution->ayudaProgram->recordDistribution($distribution->amount);

                    // Update batch statistics if applicable
                    if ($distribution->batch_id) {
                        $distribution->batch->updateStats();
                    }
                }
            }

            // Update all selected distributions
            $count = Distribution::whereIn('id', $selectedIds)
                ->update($updateData);

            DB::commit();

            $this->verificationComplete = true;
            $this->success($count . ' distributions have been ' . ($this->targetStatus === 'verified' ? 'verified' : 'processed'));

            // Reset selection
            $this->selectedDistributions = [];
            $this->selectAll = false;
            $this->resetPage();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error processing verification: ' . $e->getMessage());
        }

        $this->processingVerification = false;
    }

    public function resetFilters()
    {
        $this->barangay = '';
        $this->programId = '';
        $this->batchId = '';
        $this->search = '';
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        $this->resetPage();
    }

    public function resetVerification()
    {
        $this->selectedDistributions = [];
        $this->selectAll = false;
        $this->verificationNote = '';
        $this->verificationComplete = false;
        $this->verificationResults = [];
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.batch-verification', [
            'distributions' => $this->getDistributionsPaginated()
        ]);
    }
}
