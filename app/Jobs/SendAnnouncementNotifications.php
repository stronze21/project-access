<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Services\PushNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendAnnouncementNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $announcementId,
    ) {}

    public function handle(PushNotificationService $pushService): void
    {
        $announcement = Announcement::find($this->announcementId);

        if (!$announcement) {
            Log::warning('SendAnnouncementNotifications: Announcement not found', [
                'announcement_id' => $this->announcementId,
            ]);
            return;
        }

        $title = $announcement->title;

        // Create a short body from the content (strip HTML, limit length)
        $body = strip_tags($announcement->content);
        if (strlen($body) > 150) {
            $body = substr($body, 0, 147) . '...';
        }

        $type = 'announcement_' . $announcement->type;
        $recipientType = $announcement->recipient_type ?? 'all';

        $result = $pushService->broadcastAnnouncementNotification(
            $announcement->id,
            $title,
            $body,
            $type,
            $recipientType,
            $announcement->ayuda_program_id
        );

        Log::info('Announcement push notifications sent', [
            'announcement_id' => $announcement->id,
            'title' => $title,
            'sent' => $result['sent'],
            'failed' => $result['failed'],
            'total' => $result['total'],
        ]);
    }
}
