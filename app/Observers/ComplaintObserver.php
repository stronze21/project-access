<?php

namespace App\Observers;

use App\Models\Complaint;
use App\Services\FcmPushService;

class ComplaintObserver
{
    public function created(Complaint $complaint): void
    {
        if (!$complaint->isPubliclyVisible()) {
            return;
        }

        app(FcmPushService::class)->sendTopicNotification(
            FcmPushService::TOPIC_PUBLIC_COMPLAINTS,
            'New Public Complaint',
            'A new complaint was posted in Public Complaints.',
            [
                'type' => 'complaint',
                'id' => (string) $complaint->id,
            ]
        );
    }
}
