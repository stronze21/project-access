<?php

namespace App\Console\Commands;

use App\Models\Complaint;
use App\Notifications\ComplaintStatusNotification;
use App\Services\ComplaintAuditLogger;
use App\Services\ComplaintWorkflowService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoCloseResolvedComplaints extends Command
{
    protected $signature = 'complaints:auto-close';
    protected $description = 'Automatically closes resolved complaints awaiting citizen confirmation past SLA.';

    public function __construct(
        private ComplaintWorkflowService $workflowService,
        private ComplaintAuditLogger $auditLogger
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) config('complaints.sla.auto_close_days', 7);
        $cutoff = Carbon::now()->subDays($days);

        $complaints = Complaint::query()
            ->where('status', Complaint::STATUS_RESOLVED)
            ->where('is_anonymous_submission', false)
            ->whereNull('citizen_confirmed_at')
            ->whereNotNull('resolved_at')
            ->where('resolved_at', '<=', $cutoff)
            ->get();

        foreach ($complaints as $complaint) {
            $this->workflowService->transition(
                $complaint,
                Complaint::STATUS_CLOSED,
                null,
                "Auto-closed after {$days} days without citizen confirmation."
            );

            $complaint->auto_closed_at = Carbon::now();
            $complaint->save();

            $this->auditLogger->log('complaint_auto_closed', $complaint, $complaint, null, null, [
                'days' => $days,
            ]);

            $submitter = $complaint->submitter;
            if ($submitter && $submitter->email_verified_at !== null) {
                $submitter->notify(new ComplaintStatusNotification(
                    $complaint,
                    'Your complaint was automatically closed after the confirmation window.'
                ));
            }
        }

        $this->info('Auto-close complete. Closed: '.$complaints->count());

        return self::SUCCESS;
    }
}
