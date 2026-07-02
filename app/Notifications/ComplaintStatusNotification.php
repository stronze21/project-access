<?php

namespace App\Notifications;

use App\Models\Complaint;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ComplaintStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private Complaint $complaint,
        private string $messageText
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Complaint Update: '.$this->complaint->reference_code)
            ->line($this->messageText)
            ->line('Reference: '.$this->complaint->reference_code)
            ->line('Current status: '.str_replace('_', ' ', ucfirst($this->complaint->status)))
            ->action('View Complaint', route('complaints.my.index'));
    }

    public function toArray($notifiable): array
    {
        return [
            'complaint_id' => $this->complaint->id,
            'reference_code' => $this->complaint->reference_code,
            'status' => $this->complaint->status,
            'message' => $this->messageText,
        ];
    }
}
