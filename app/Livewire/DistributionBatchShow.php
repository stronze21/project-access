<?php

namespace App\Livewire;

use App\Models\DistributionBatch;
use App\Models\Distribution;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DistributionBatchShow extends Component
{
    use WithPagination;
    use Toast;

    public $batch;
    public $batchId;
    public $perPage = 10;
    public $searchQuery = '';
    public $statusFilter = 'all';
    public $showStats = true;

    // For confirmation modals
    public $showCompleteBatchModal = false;
    public $showCancelBatchModal = false;

    // For distribution processing
    public $processingDistributions = false;

    /**
     * Mount the component
     */
    public function mount($batchId)
    {
        $this->batchId = $batchId;
        $this->loadBatch();
    }

    /**
     * Load batch data
     */
    protected function loadBatch()
    {
        $this->batch = DistributionBatch::with([
            'ayudaProgram',
            'creator',
            'updater'
        ])->findOrFail($this->batchId);

        // Calculate completion percentage
        if ($this->batch->target_beneficiaries > 0) {
            $this->batch->completion_percentage = min(100, round(($this->batch->actual_beneficiaries / $this->batch->target_beneficiaries) * 100, 1));
        } else {
            $this->batch->completion_percentage = 0;
        }
    }

    /**
     * Toggle stats visibility
     */
    public function toggleStats()
    {
        $this->showStats = !$this->showStats;
    }

    /**
     * Get distributions for this batch based on filters
     */
    public function getDistributions()
    {
        $query = Distribution::where('batch_id', $this->batchId)
            ->with(['resident', 'household', 'ayudaProgram']);

        // Apply status filter
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // Apply search
        if (!empty($this->searchQuery)) {
            $query->where(function($q) {
                $q->where('reference_number', 'like', '%' . $this->searchQuery . '%')
                  ->orWhereHas('resident', function($sq) {
                      $sq->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$this->searchQuery}%");
                  })
                  ->orWhereHas('household', function($sq) {
                      $sq->where('household_id', 'like', "%{$this->searchQuery}%");
                  });
            });
        }

        return $query->orderBy('status')
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    /**
     * Update batch status
     */
    public function updateBatchStatus($status)
    {
        $this->batch->status = $status;
        $this->batch->updated_by = Auth::id();
        $this->batch->save();

       $this->success("Batch marked as " . ucfirst($status));

        $this->loadBatch();
        $this->resetPage();

        // Hide modals if they were open
        $this->showCompleteBatchModal = false;
        $this->showCancelBatchModal = false;
    }

    /**
     * Process a single distribution
     */
    public function processDistribution($distributionId, $status)
    {
        $distribution = Distribution::find($distributionId);

        if (!$distribution) {
           $this->error('Distribution not found');
            return;
        }

        try {
            DB::beginTransaction();

            $distribution->status = $status;

            // If distributing, record the user and update program stats
            if ($status === 'distributed') {
                $distribution->distributed_by = Auth::id();
                $distribution->save();

                // Update program statistics
                $program = $distribution->ayudaProgram;
                $program->recordDistribution($distribution->amount);

                // Update batch statistics
                $this->batch->actual_beneficiaries = $this->batch->distributions()
                    ->where('status', 'distributed')
                    ->count();

                $this->batch->total_amount = $this->batch->distributions()
                    ->where('status', 'distributed')
                    ->sum('amount');

                $this->batch->save();
            } else {
                $distribution->save();
            }

            DB::commit();

           $this->success("Distribution marked as " . ucfirst($status));

            $this->loadBatch();

        } catch (\Exception $e) {
            DB::rollBack();

           $this->error('Error processing distribution: ' . $e->getMessage());
        }
    }

    /**
     * Process all pending distributions (mark as distributed)
     */
    public function processAllDistributions()
    {
        $this->processingDistributions = true;

        try {
            DB::beginTransaction();

            $pendingDistributions = Distribution::where('batch_id', $this->batchId)
                ->where('status', 'pending')
                ->get();

            $count = $pendingDistributions->count();

            if ($count === 0) {
               $this->warning('No pending distributions to process')
                    ->send();

                $this->processingDistributions = false;
                return;
            }

            // Process each distribution
            foreach ($pendingDistributions as $distribution) {
                $distribution->status = 'distributed';
                $distribution->distributed_by = Auth::id();
                $distribution->save();

                // Update program statistics
                $program = $distribution->ayudaProgram;
                $program->recordDistribution($distribution->amount);
            }

            // Update batch statistics
            $this->batch->actual_beneficiaries = $this->batch->distributions()
                ->where('status', 'distributed')
                ->count();

            $this->batch->total_amount = $this->batch->distributions()
                ->where('status', 'distributed')
                ->sum('amount');

            // If all distributions are processed, mark batch as completed
            $pendingCount = Distribution::where('batch_id', $this->batchId)
                ->where('status', 'pending')
                ->count();

            if ($pendingCount === 0) {
                $this->batch->status = 'completed';
            }

            $this->batch->updated_by = Auth::id();
            $this->batch->save();

            DB::commit();

           $this->success("Processed {$count} distributions successfully");

            $this->loadBatch();
            $this->resetPage();

        } catch (\Exception $e) {
            DB::rollBack();

           $this->error('Error processing distributions: ' . $e->getMessage());
        }

        $this->processingDistributions = false;
    }

    /**
     * Show the complete batch confirmation modal
     */
    public function confirmCompleteBatch()
    {
        $this->showCompleteBatchModal = true;
    }

    /**
     * Show the cancel batch confirmation modal
     */
    public function confirmCancelBatch()
    {
        $this->showCancelBatchModal = true;
    }

    /**
     * Export batch data to CSV
     */
    public function exportBatchData()
    {
        // This would typically use your ExportService to create a CSV
       $this->info('Export functionality coming soon');
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.distribution-batch-show', [
            'distributions' => $this->getDistributions(),
        ]);
    }
}