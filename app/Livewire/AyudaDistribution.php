<?php

namespace App\Livewire;

use App\Models\AyudaProgram;
use App\Models\Distribution;
use App\Models\DistributionBatch;
use App\Models\Resident;
use App\Models\Household;
use App\Services\QrCodeService;
use App\Services\RfidService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AyudaDistribution extends Component
{
    use WithFileUploads;
    use Toast;

    #[Url()]
    public $resident;
    // Scanner and search
    public $showScanner = false;
    public $scanResult = null;
    public $selectedResident = null;
    public $selectedHousehold = null;
    public $searchQuery = '';

    // Distribution data
    #[Validate('required|exists:ayuda_programs,id')]
    public $selectedProgramId = null;

    #[Validate('nullable|exists:distribution_batches,id')]
    public $selectedBatchId = null;

    #[Validate('required|date')]
    public $distributionDate;

    #[Validate('nullable|numeric|min:0')]
    public $amount = null;

    #[Validate('nullable|string')]
    public $goodsDetails = '';

    #[Validate('nullable|string')]
    public $servicesDetails = '';

    #[Validate('nullable|string')]
    public $notes = '';

    #[Validate('nullable|image|max:2048')]
    public $receiptImage;

    // Flags and states
    public $isHouseholdDistribution = false;
    public $programType = 'cash';
    public $isBatchMode = false;
    public $isVerificationRequired = false;
    public $verificationData = [];

    // Continuous distribution
    public $continuesDistribution = false;
    public $showSuccessModal = false;
    public $lastDistribution = null;
    public $distributionCount = 0;
    public $batchProgress = 0;

    // Stored program info
    public $programAmount = 0;

    // Eligibility check
    public $isEligible = false;
    public $eligibilityMessage = '';

    // Lists
    public $availablePrograms = [];
    public $availableBatches = [];

    protected $listeners = [
        'scan-result' => 'handleScanResult'
    ];

    /**
     * Mount the component.
     */
    public function mount($programId = null, $batchId = null)
    {
        $this->distributionDate = now()->format('Y-m-d');

        $this->loadAvailablePrograms();

        if ($programId) {
            $this->selectedProgramId = $programId;
            $this->loadProgramDetails();
        }

        if ($batchId) {
            $this->selectedBatchId = $batchId;
            $this->isBatchMode = true;
            $this->loadBatchDetails();
        }

        if ($this->resident) {
            $this->selectedResident = Resident::findOrFail($this->resident);
        }
    }

    public function updatedSelectedProgramId()
    {
        $this->loadProgramDetails();
    }


    /**
     * Load available active ayuda programs.
     */
    public function loadAvailablePrograms()
    {
        $this->availablePrograms = AyudaProgram::active()->get();
    }

    /**
     * Load program details when a program is selected.
     */
    public function loadProgramDetails()
    {
        if (!$this->selectedProgramId) {
            return;
        }

        $program = AyudaProgram::findOrFail($this->selectedProgramId);
        $this->programType = $program->type;
        $this->programAmount = $program->amount;
        $this->amount = $program->amount;
        $this->isVerificationRequired = $program->requires_verification;

        // Load available batches for this program
        $this->availableBatches = DistributionBatch::where('ayuda_program_id', $this->selectedProgramId)
            ->whereIn('status', ['scheduled', 'ongoing'])
            ->orderBy('batch_date', 'desc')
            ->get();

        if ($this->selectedBatchId && $this->isBatchMode) {
            $this->updateBatchProgress();
        }

        if ($this->selectedResident) {
            $this->checkEligibility();
        }
    }

    /**
     * Update batch progress statistics.
     */
    public function updateBatchProgress()
    {
        if (!$this->selectedBatchId) {
            $this->batchProgress = 0;
            return;
        }

        $batch = DistributionBatch::findOrFail($this->selectedBatchId);
        $totalBeneficiaries = $batch->expected_beneficiaries ?: 1;
        $completedDistributions = Distribution::where('batch_id', $this->selectedBatchId)
            ->whereIn('status', ['distributed', 'verified'])
            ->count();

        $this->batchProgress = min(100, round(($completedDistributions / $totalBeneficiaries) * 100));
    }

    /**
     * Load batch details when a batch is selected.
     */
    public function loadBatchDetails()
    {
        if (!$this->selectedBatchId) {
            return;
        }

        $batch = DistributionBatch::findOrFail($this->selectedBatchId);
        $this->distributionDate = $batch->batch_date->format('Y-m-d');
        $this->updateBatchProgress();
    }

    /**
     * Toggle continuous distribution mode.
     */
    public function toggleContinuesDistribution()
    {
        $this->continuesDistribution = !$this->continuesDistribution;
    }

    /**
     * Continue to next distribution while keeping program/batch settings.
     */
    public function continueToNextDistribution()
    {
        $this->showSuccessModal = false;

        // Clear just the beneficiary and their data, but keep program settings
        $this->reset([
            'selectedResident',
            'selectedHousehold',
            'searchQuery',
            'scanResult',
            'goodsDetails',
            'servicesDetails',
            'notes',
            'receiptImage',
            'isHouseholdDistribution',
            'verificationData',
            'isEligible',
            'eligibilityMessage',
        ]);

        // Return amount to program default
        if ($this->selectedProgramId) {
            $this->amount = $this->programAmount;
        }

        // Activate scanner for next beneficiary if it was previously shown
        if ($this->showScanner) {
            $this->dispatch('scanner-toggled');
        }
    }

    /**
     * Handle scan result from QR/RFID scanner.
     */
    public function handleScanResult($result)
    {
        if (!$result['found']) {
            return;
        }

        if ($result['type'] === 'resident') {
            $this->selectedResident = Resident::findOrFail($result['object']['id']);
            $this->selectedHousehold = $this->selectedResident->household ?? [];

            if ($this->selectedResident && $this->selectedProgramId) {
                $this->checkEligibility();
            }
        } elseif ($result['type'] === 'household') {
            $this->selectedHousehold = Household::findOrFail($result['object']['id']);
            $this->isHouseholdDistribution = true;

            // Set the first household member as the recipient
            $headOfHousehold = $this->selectedHousehold->householdHead();
            if ($headOfHousehold) {
                $this->selectedResident = $headOfHousehold;

                if ($this->selectedResident && $this->selectedProgramId) {
                    $this->checkEligibility();
                }
            }
        }
    }

    /**
     * Search for a resident.
     */
    public function searchResident()
    {
        if (empty($this->searchQuery) || strlen($this->searchQuery) < 3) {
            $this->warning('Please enter at least 3 characters for search');
            return;
        }

        $resident = Resident::where('resident_id', $this->searchQuery)
            ->orWhere('qr_code', $this->searchQuery)
            ->orWhere('rfid_number', $this->searchQuery)
            ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$this->searchQuery}%")
            ->first();

        if ($resident) {
            $this->selectedResident = $resident;
            $this->selectedHousehold = $resident->household;

            $this->success('Resident found: ' . $resident->full_name);

            if ($this->selectedProgramId) {
                $this->checkEligibility();
            }
        } else {
            $this->error('No resident found with the provided information.');
        }
    }

    /**
     * Check if the selected resident is eligible for the selected program.
     */
    public function checkEligibility()
    {
        if (!$this->selectedProgramId || !$this->selectedResident) {
            return;
        }

        $program = AyudaProgram::find($this->selectedProgramId);

        // Check if resident already received this aid
        $alreadyReceived = Distribution::where('ayuda_program_id', $this->selectedProgramId)
            ->where('resident_id', $this->selectedResident->id)
            ->where('status', 'distributed')
            ->exists();

        if ($alreadyReceived) {
            $this->isEligible = false;
            $this->eligibilityMessage = 'This resident has already received aid from this program.';
            return;
        }

        // Check program eligibility
        $isEligible = true;
        $failedCriteria = [];

        foreach ($program->eligibilityCriteria as $criterion) {
            if (!$criterion->checkEligibility($this->selectedResident)) {
                $isEligible = false;
                $failedCriteria[] = $criterion->criterion_name;
            }
        }

        $this->isEligible = $isEligible;

        if ($isEligible) {
            $this->eligibilityMessage = 'Resident is eligible for this program.';
        } else {
            $this->eligibilityMessage = 'Resident does not meet the following criteria: ' . implode(', ', $failedCriteria);
        }
    }

    /**
     * Process the distribution.
     */
    public function distribute()
    {
        $this->validate();

        if (!$this->selectedResident) {
            $this->warning('Please select a resident first');
            return;
        }

        if (!$this->isEligible && !$this->isVerificationRequired) {
            $this->warning('Resident is not eligible for this program');
            return;
        }

        try {
            DB::beginTransaction();

            // Get program details
            $program = AyudaProgram::findOrFail($this->selectedProgramId);

            // Prepare distribution data
            $distributionData = [
                'reference_number' => Distribution::generateReferenceNumber(),
                'ayuda_program_id' => $this->selectedProgramId,
                'resident_id' => $this->selectedResident->id,
                'household_id' => $this->selectedResident->household_id,
                'batch_id' => $this->selectedBatchId,
                'distributed_by' => Auth::id(),
                'distribution_date' => $this->distributionDate,
                'amount' => $this->amount,
                'goods_details' => $this->goodsDetails,
                'services_details' => $this->servicesDetails,
                'notes' => $this->notes,
                'status' => $this->isVerificationRequired ? 'pending' : 'distributed',
            ];

            // Save verification data if required
            if ($this->isVerificationRequired && !empty($this->verificationData)) {
                $distributionData['verification_data'] = json_encode($this->verificationData);
            }

            // Create distribution record
            $distribution = Distribution::create($distributionData);

            // Handle receipt image upload
            if ($this->receiptImage) {
                $path = $this->receiptImage->store('receipts', 'public');
                $distribution->receipt_path = $path;
                $distribution->save();
            }

            // Update program statistics if distributed immediately
            if ($distribution->status === 'distributed') {
                $program->recordDistribution($this->amount);

                // Update batch statistics if in batch mode
                if ($this->selectedBatchId) {
                    $batch = DistributionBatch::find($this->selectedBatchId);
                    $batch->updateStats();
                    $this->updateBatchProgress();
                }
            }

            DB::commit();

            // Store last distribution info for success modal
            $this->lastDistribution = $distribution;
            $this->distributionCount++;

            // If continues distribution is enabled, show success modal instead of redirecting
            if ($this->continuesDistribution) {
                $this->showSuccessModal = true;
                $this->success('Aid distribution complete for: ' . $this->selectedResident->full_name);
            } else {
                $this->success('Aid distribution ' . ($this->isVerificationRequired ? 'recorded and pending verification' : 'completed successfully'));
                $this->resetDistribution();
                return redirect()->route('distributions.show', $distribution->id);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
        }
    }

    /**
     * Reset the distribution form.
     */
    public function resetDistribution()
    {
        $this->reset([
            'selectedResident',
            'selectedHousehold',
            'searchQuery',
            'scanResult',
            'amount',
            'goodsDetails',
            'servicesDetails',
            'notes',
            'receiptImage',
            'isHouseholdDistribution',
            'verificationData',
            'isEligible',
            'eligibilityMessage',
            'showSuccessModal',
            'lastDistribution',
        ]);

        $this->distributionDate = now()->format('Y-m-d');

        if ($this->selectedProgramId) {
            $this->amount = $this->programAmount;
        }
    }

    /**
     * Reset all selections and form.
     */
    public function resetAll()
    {
        $this->reset();
        $this->distributionDate = now()->format('Y-m-d');
        $this->distributionCount = 0;
        $this->loadAvailablePrograms();
    }

    /**
     * Update programs and batches when switching to/from batch mode.
     */
    public function updatedIsBatchMode()
    {
        if ($this->isBatchMode) {
            // Load only programs with active batches
            $this->availablePrograms = AyudaProgram::whereHas('distributionBatches', function ($query) {
                $query->whereIn('status', ['scheduled', 'ongoing']);
            })->get();

            $this->selectedProgramId = null;
            $this->selectedBatchId = null;
            $this->availableBatches = [];
        } else {
            $this->loadAvailablePrograms();
            $this->selectedBatchId = null;
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.ayuda-distribution');
    }
}
