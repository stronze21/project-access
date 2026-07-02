<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SlaOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Complaint $complaint,
        private string $stage
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SLA Overdue Alert: '.$this->complaint->reference_code)
            ->line("The complaint is overdue for SLA stage: {$this->stage}.")
            ->line('Reference: '.$this->complaint->reference_code)
            ->line('Current status: '.str_replace('_', ' ', ucfirst($this->complaint->status)))
            ->action('Open Case Queue', route('complaints.manage.index'));
    }

    public function toArray($notifiable): array
    {
        return [
            'complaint_id' => $this->complaint->id,
            'reference_code' => $this->complaint->reference_code,
            'sla_stage' => $this->stage,
            'status' => $this->complaint->status,
            'message' => 'Complaint exceeded SLA threshold.',
        ];
    }
}
