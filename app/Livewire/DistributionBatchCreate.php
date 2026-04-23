<?php

namespace App\Livewire;

use App\Models\AyudaProgram;
use App\Models\DistributionBatch;
use Livewire\Component;
use Livewire\Attributes\Validate;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DistributionBatchCreate extends Component
{
    use Toast;

    // Form fields
    #[Validate('required|exists:ayuda_programs,id')]
    public $ayudaProgramId;

    #[Validate('required|string|max:100')]
    public $location = '';

    #[Validate('required|date')]
    public $batchDate;

    #[Validate('required')]
    public $startTime = '';

    #[Validate('required')]
    public $endTime = '';

    #[Validate('required|integer|min:1')]
    public $targetBeneficiaries = 50;

    #[Validate('nullable|string|max:1000')]
    public $notes = '';

    // Component state
    public $programs = [];
    public $isEdit = false;
    public $batchId = null;
    public $batch = null;

    /**
     * Mount the component.
     */
    public function mount($batchId = null)
    {
        // Load active programs
        $this->loadPrograms();

        // Set default batch date to tomorrow
        $this->batchDate = now()->addDay()->format('Y-m-d');
        $this->startTime = '08:00';
        $this->endTime = '17:00';

        // If editing an existing batch
        if ($batchId) {
            $this->batchId = $batchId;
            $this->isEdit = true;
            $this->loadBatch();
        }
    }

    /**
     * Load active ayuda programs.
     */
    protected function loadPrograms()
    {
        $this->programs = AyudaProgram::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Load batch data for editing.
     */
    protected function loadBatch()
    {
        $this->batch = DistributionBatch::findOrFail($this->batchId);

        $this->ayudaProgramId = $this->batch->ayuda_program_id;
        $this->location = $this->batch->location;
        $this->batchDate = $this->batch->batch_date->format('Y-m-d');
        $this->startTime = $this->batch->start_time->format('H:i');
        $this->endTime = $this->batch->end_time->format('H:i');
        $this->targetBeneficiaries = $this->batch->target_beneficiaries;
        $this->notes = $this->batch->notes;
    }

    /**
     * Save the distribution batch.
     */
    public function save()
    {
        $this->validate();

        // Validate that end time is after start time
        $startDateTime = Carbon::parse($this->batchDate . ' ' . $this->startTime);
        $endDateTime = Carbon::parse($this->batchDate . ' ' . $this->endTime);

        if ($endDateTime <= $startDateTime) {
            $this->warning('End time must be after start time');
            return;
        }

        try {
            // Prepare batch data
            $batchData = [
                'ayuda_program_id' => $this->ayudaProgramId,
                'location' => $this->location,
                'batch_date' => $this->batchDate,
                'start_time' => $startDateTime,
                'end_time' => $endDateTime,
                'target_beneficiaries' => $this->targetBeneficiaries,
                'notes' => $this->notes,
                'status' => $this->determineBatchStatus($this->batchDate),
            ];

            if ($this->isEdit) {
                // Update existing batch
                $this->batch->update($batchData);
                $this->batch->updated_by = Auth::id();
                $this->batch->save();

                $this->success('Distribution batch updated successfully');
            } else {
                // Create new batch
                $program = AyudaProgram::find($this->ayudaProgramId);

                // Generate batch number
                $batchNumber = $program->code . '-' . date('Ymd', strtotime($this->batchDate)) . '-' .
                    DistributionBatch::where('ayuda_program_id', $this->ayudaProgramId)
                        ->whereDate('batch_date', $this->batchDate)
                        ->count() + 1;

                $batchData['batch_number'] = $batchNumber;
                $batchData['created_by'] = Auth::id();
                $batchData['updated_by'] = Auth::id();

                $batch = DistributionBatch::create($batchData);

                $this->success('Distribution batch created successfully');

                // Redirect to batch show page
                return redirect()->route('distributions.batches.show', $batch->id);
            }

            // Redirect to batches list if not redirected already
            return redirect()->route('distributions.batches');

        } catch (\Exception $e) {
            $this->error('Error saving batch: ' . $e->getMessage());
        }
    }

    /**
     * Determine batch status based on date.
     */
    protected function determineBatchStatus($batchDate)
    {
        $date = Carbon::parse($batchDate);

        if ($date->isPast()) {
            return 'completed';
        } elseif ($date->isToday()) {
            return 'ongoing';
        } else {
            return 'scheduled';
        }
    }

    /**
     * Cancel and go back to batches list.
     */
    public function cancel()
    {
        return redirect()->route('distributions.batches');
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('livewire.distribution-batch-create');
    }
}