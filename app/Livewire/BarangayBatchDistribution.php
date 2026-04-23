<?php

namespace App\Livewire;

use App\Models\AyudaProgram;
use App\Models\Distribution;
use App\Models\DistributionBatch;
use App\Models\Resident;
use Livewire\Component;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class BarangayBatchDistribution extends Component
{
    use WithPagination;
    use Toast;

    #[Url()]
    public $barangay = '';
    public $selectedBatchId = null;
    public $selectedProgramId = null;
    public $distributionDate;
    public $amount = null;
    public $goodsDetails = '';
    public $servicesDetails = '';
    public $notes = '';
    public $selectedResidents = [];
    public $selectAll = false;

    public $availableBatches = [];
    public $availablePrograms = [];
    public $barangayList = [];
    public $programType = 'cash';
    public $programAmount = 0;
    public $status = 'pending';
    public $search = '';
    public $perPage = 10;
    public $showPreview = false;
    public $processingBatch = false;
    public $processingComplete = false;
    public $filterSeniors = false;
    public $filterPwd = false;
    public $filterSoloParent = false;
    public $filterHouseholdHead = false;
    public $processingResults = [
        'total' => 0,
        'successful' => 0,
        'failed' => 0,
        'details' => []
    ];

    public $previewStats = [
        'total' => 0,
        'seniors' => 0,
        'pwd' => 0,
        'soloParents' => 0,
        // Not tracking household count
        'totalAmount' => 0
    ];

    public function mount()
    {
        $this->distributionDate = now()->format('Y-m-d');
        $this->loadAvailablePrograms();
        $this->loadBarangayList();
    }

    public function loadAvailablePrograms()
    {
        $this->availablePrograms = AyudaProgram::active()->get();
    }

    public function loadBarangayList()
    {
        // Get unique barangays from residents with households
        $this->barangayList = Resident::whereHas('household')
            ->join('households', 'residents.household_id', '=', 'households.id')
            ->select('households.barangay')
            ->distinct()
            ->pluck('households.barangay')
            ->toArray();

        sort($this->barangayList);
    }

    public function updatedSelectedProgramId()
    {
        if (!$this->selectedProgramId) {
            $this->programType = 'cash';
            $this->programAmount = 0;
            $this->amount = 0;
            return;
        }

        $program = AyudaProgram::findOrFail($this->selectedProgramId);
        $this->programType = $program->type;
        $this->programAmount = $program->amount;
        $this->amount = $program->amount;

        // Load available batches for this program
        $this->availableBatches = DistributionBatch::where('ayuda_program_id', $this->selectedProgramId)
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->orderBy('batch_date', 'desc')
            ->get();
    }

    public function updatedBarangay()
    {
        $this->selectedResidents = [];
        $this->selectAll = false;
        $this->resetPage();
    }

    public function updatedSelectAll()
    {
        if ($this->selectAll) {
            // Select all residents on the current page
            $residents = $this->filteredResidentsQuery()->get();
            foreach ($residents as $resident) {
                $this->selectedResidents[$resident->id] = true;
            }
        } else {
            $this->selectedResidents = [];
        }
    }


    // public function updatedSelectAll($value)
    // {
    //     if ($value) {
    //         $this->selectedResidents = $this->filteredResidentsQuery()
    //             ->pluck('id')
    //             ->toArray();
    //     } else {
    //         $this->selectedResidents = [];
    //     }
    // }

    protected function filteredResidentsQuery()
    {
        if (empty($this->barangay)) {
            return collect();
        }

        $query = Resident::whereHas('household', function ($query) {
            $query->where('barangay', $this->barangay);
        })
            ->where('is_active', true)
            ->with(['household']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                    ->orWhere('last_name', 'like', '%' . $this->search . '%')
                    ->orWhere('resident_id', 'like', '%' . $this->search . '%');
            });
        }

        // Apply filters
        if ($this->filterSeniors) {
            $query->where('is_senior_citizen', true);
        }

        if ($this->filterPwd) {
            $query->where('is_pwd', true);
        }

        if ($this->filterSoloParent) {
            $query->where('is_solo_parent', true);
        }

        if ($this->filterHouseholdHead) {
            $query->where('relationship_to_head', 'head');
        }

        return $query->orderBy('last_name');
    }

    public function getBarangayResidents()
    {
        if (empty($this->barangay)) {
            return collect();
        }

        $query = Resident::whereHas('household', function ($query) {
            $query->where('barangay', $this->barangay);
        })
            ->where('is_active', true)
            ->with(['household']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', '%' . $this->search . '%')
                    ->orWhere('last_name', 'like', '%' . $this->search . '%')
                    ->orWhere('resident_id', 'like', '%' . $this->search . '%');
            });
        }

        // Apply filters
        if ($this->filterSeniors) {
            $query->where('is_senior_citizen', true);
        }

        if ($this->filterPwd) {
            $query->where('is_pwd', true);
        }

        if ($this->filterSoloParent) {
            $query->where('is_solo_parent', true);
        }

        if ($this->filterHouseholdHead) {
            $query->where('relationship_to_head', 'head');
        }

        return $query->orderBy('last_name')->paginate($this->perPage);
    }

    public function previewBatchDistribution()
    {
        if (!$this->selectedProgramId) {
            $this->warning('Please select an Ayuda program');
            return;
        }

        if (count(array_filter($this->selectedResidents)) === 0) {
            $this->warning('Please select at least one resident');
            return;
        }

        // Calculate preview stats for the UI
        $this->calculatePreviewStats();
        $this->showPreview = true;
    }

    /**
     * Calculate statistics for preview
     */
    protected function calculatePreviewStats()
    {
        $selectedIds = array_keys(array_filter($this->selectedResidents));

        // Get preview residents with their details
        $previewResidents = Resident::whereIn('id', $selectedIds)
            ->with('household')
            ->get();

        // Check for existing distributions
        $potentialDuplicates = 0;
        if ($this->selectedProgramId) {
            $potentialDuplicates = Distribution::where('ayuda_program_id', $this->selectedProgramId)
                ->whereIn('resident_id', $selectedIds)
                ->whereIn('status', ['distributed', 'verified', 'pending'])
                ->count();
        }

        // Count demographics
        $this->previewStats = [
            'total' => count($previewResidents),
            'seniors' => $previewResidents->where('is_senior_citizen', true)->count(),
            'pwd' => $previewResidents->where('is_pwd', true)->count(),
            'soloParents' => $previewResidents->where('is_solo_parent', true)->count(),
            // Not tracking household count as per requirements
            'totalAmount' => count($previewResidents) * $this->amount,
            'potentialDuplicates' => $potentialDuplicates
        ];
    }

    /**
     * Check for existing distributions and generate a report
     */
    public function checkForDuplicates()
    {
        if (!$this->selectedProgramId) {
            $this->warning('Please select an Ayuda program first');
            return;
        }

        $selectedIds = array_keys(array_filter($this->selectedResidents));

        if (count($selectedIds) === 0) {
            $this->warning('Please select at least one resident');
            return;
        }

        // Find existing distributions for the selected residents under the selected program
        $existingDistributions = Distribution::where('ayuda_program_id', $this->selectedProgramId)
            ->whereIn('resident_id', $selectedIds)
            ->whereIn('status', ['distributed', 'verified', 'pending'])
            ->with(['resident'])
            ->get();

        if ($existingDistributions->count() === 0) {
            $this->success('No existing distributions found for the selected residents in this program');
            return;
        }

        // Remove residents with existing distributions from the selection
        foreach ($existingDistributions as $distribution) {
            unset($this->selectedResidents[$distribution->resident_id]);
        }

        $this->warning(
            'Found ' . $existingDistributions->count() . ' existing distributions. ' .
                'These residents have been removed from your selection.'
        );

        $this->calculatePreviewStats();
    }

    public function processBatchDistribution()
    {
        if (!$this->selectedProgramId) {
            $this->warning('Please select an Ayuda program');
            return;
        }

        $selectedResidentIds = array_keys(array_filter($this->selectedResidents));

        if (count($selectedResidentIds) === 0) {
            $this->warning('Please select at least one resident');
            return;
        }

        $this->processingBatch = true;
        $this->processingResults = [
            'total' => count($selectedResidentIds),
            'successful' => 0,
            'failed' => 0,
            'details' => []
        ];

        try {
            DB::beginTransaction();

            // Get program details
            $program = AyudaProgram::findOrFail($this->selectedProgramId);

            // Get all selected residents
            $residents = Resident::whereIn('id', $selectedResidentIds)
                ->with('household')
                ->get();

            foreach ($residents as $resident) {
                try {
                    // Check eligibility (simplified check for batch processing)
                    $alreadyReceived = Distribution::where('ayuda_program_id', $this->selectedProgramId)
                        ->where('resident_id', $resident->id)
                        ->whereIn('status', ['distributed', 'verified', 'pending'])
                        ->exists();

                    if ($alreadyReceived) {
                        $this->processingResults['failed']++;
                        $this->processingResults['details'][] = [
                            'resident_id' => $resident->resident_id,
                            'name' => $resident->full_name,
                            'status' => 'failed',
                            'reason' => 'Already received aid from this program'
                        ];
                        continue;
                    }

                    // Prepare distribution data
                    $distributionData = [
                        'reference_number' => Distribution::generateReferenceNumber(),
                        'ayuda_program_id' => $this->selectedProgramId,
                        'resident_id' => $resident->id,
                        'household_id' => $resident->household_id,
                        'batch_id' => $this->selectedBatchId,
                        'distribution_date' => $this->distributionDate,
                        'amount' => $this->amount,
                        'goods_details' => $this->goodsDetails,
                        'services_details' => $this->servicesDetails,
                        'notes' => $this->notes,
                        'status' => $this->status,
                    ];

                    // Create distribution record
                    $distribution = Distribution::create($distributionData);

                    // Update program and batch statistics if distributed immediately
                    if ($distribution->status === 'distributed') {
                        $program->recordDistribution($this->amount);

                        // Update batch statistics if in batch mode
                        if ($this->selectedBatchId) {
                            $batch = DistributionBatch::find($this->selectedBatchId);
                            $batch->updateStats();
                        }
                    }

                    $this->processingResults['successful']++;
                    $this->processingResults['details'][] = [
                        'resident_id' => $resident->resident_id,
                        'name' => $resident->full_name,
                        'status' => 'success',
                        'reference' => $distribution->reference_number
                    ];
                } catch (\Exception $e) {
                    $this->processingResults['failed']++;
                    $this->processingResults['details'][] = [
                        'resident_id' => $resident->resident_id,
                        'name' => $resident->full_name,
                        'status' => 'failed',
                        'reason' => 'Error: ' . $e->getMessage()
                    ];
                }
            }

            DB::commit();
            $this->processingComplete = true;
            $this->success('Batch distribution completed successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error processing batch: ' . $e->getMessage());
        }

        $this->processingBatch = false;
        $this->showPreview = false;
        $this->processingComplete = false;
        $this->processingResults = [
            'total' => 0,
            'successful' => 0,
            'failed' => 0,
            'details' => []
        ];
    }

    public function cancelPreview()
    {
        $this->showPreview = false;
    }

    public function render()
    {
        return view('livewire.barangay-batch-distribution', [
            'residents' => !empty($this->barangay) ? $this->getBarangayResidents() : []
        ]);
    }
}
