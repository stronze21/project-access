<?php

namespace App\Console\Commands;

use App\Models\Complaint;
use App\Models\User;
use App\Notifications\SlaOverdueNotification;
use App\Services\ComplaintAuditLogger;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class EnforceComplaintSla extends Command
{
    protected $signature = 'complaints:enforce-sla';

    protected $description = 'Checks complaint SLA deadlines and sends escalation alerts.';

    public function __construct(private ComplaintAuditLogger $auditLogger)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $now = Carbon::now();
        $checked = 0;

        Complaint::query()
            ->where('moderation_status', Complaint::MODERATION_NORMAL)
            ->where('status', '!=', Complaint::STATUS_CLOSED)
            ->chunkById(100, function ($complaints) use ($now, &$checked) {
                foreach ($complaints as $complaint) {
                    $checked++;
                    $this->processSla($complaint, $now);
                }
            });

        $this->info("SLA check complete. Complaints checked: {$checked}");

        return self::SUCCESS;
    }

    private function processSla(Complaint $complaint, Carbon $now): void
    {
        $shouldSave = false;
        $recipients = $this->reminderRecipients($complaint);

        if ($complaint->due_ack_at && $complaint->acknowledged_at === null && $complaint->due_ack_at->lt($now) && $complaint->ack_overdue_notified_at === null) {
            foreach ($recipients as $user) {
                $user->notify(new SlaOverdueNotification($complaint, 'Acknowledge Time'));
            }

            $complaint->ack_overdue_notified_at = $now;
            $complaint->is_escalated = true;
            $this->auditLogger->log('sla_ack_overdue', $complaint, $complaint, null, null);
            $shouldSave = true;
        }

        if ($complaint->due_first_action_at
            && $complaint->first_action_at === null
            && $complaint->due_first_action_at->lt($now)
            && $complaint->first_action_overdue_notified_at === null
            && in_array($complaint->status, [Complaint::STATUS_ASSIGNED, Complaint::STATUS_IN_PROGRESS], true)
        ) {
            foreach ($recipients as $user) {
                $user->notify(new SlaOverdueNotification($complaint, 'First Action Time'));
            }

            $complaint->first_action_overdue_notified_at = $now;
            $complaint->is_escalated = true;
            $this->auditLogger->log('sla_first_action_overdue', $complaint, $complaint, null, null);
            $shouldSave = true;
        }

        if ($complaint->due_resolution_at
            && $complaint->due_resolution_at->lt($now)
            && $complaint->resolution_overdue_notified_at === null
            && in_array($complaint->status, [Complaint::STATUS_RECEIVED, Complaint::STATUS_ASSIGNED, Complaint::STATUS_IN_PROGRESS], true)
        ) {
            foreach ($recipients as $user) {
                $user->notify(new SlaOverdueNotification($complaint, 'Resolution Time'));
            }

            $complaint->resolution_overdue_notified_at = $now;
            $complaint->is_escalated = true;
            $this->auditLogger->log('sla_resolution_overdue', $complaint, $complaint, null, null);
            $shouldSave = true;
        }

        if ($complaint->resolution_overdue_notified_at !== null
            && $complaint->status !== Complaint::STATUS_RESOLVED
            && $complaint->status !== Complaint::STATUS_CLOSED
            && $complaint->mayor_notified_at === null
        ) {
            $mayors = User::query()->mayors()->get();
            foreach ($mayors as $mayor) {
                $mayor->notify(new SlaOverdueNotification($complaint, 'Executive Escalation'));
            }

            $complaint->mayor_notified_at = $now;
            $this->auditLogger->log('sla_mayor_notified', $complaint, $complaint, null, null);
            $shouldSave = true;
        }

        if ($shouldSave) {
            $complaint->save();
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function reminderRecipients(Complaint $complaint)
    {
        $recipients = collect();

        if ($complaint->assignedOfficer) {
            $recipients->push($complaint->assignedOfficer);
        }

        if ($complaint->assigned_department_id) {
            $heads = User::query()
                ->departmentHeads()
                ->where('department_id', $complaint->assigned_department_id)
                ->get();
            $recipients = $recipients->merge($heads);
        }

        return $recipients->unique('id')->values();
    }
}
