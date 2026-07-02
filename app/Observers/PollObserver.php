<?php

namespace App\Observers;

use App\Models\Poll;
use App\Services\FcmPushService;

class PollObserver
{
    public function created(Poll $poll): void
    {
        if (!$poll->is_active) {
            return;
        }

        app(FcmPushService::class)->sendTopicNotification(
            FcmPushService::TOPIC_POLLS,
            'New Poll Available',
            'A new poll was posted in Community.',
            [
                'type' => 'poll',
                'id' => (string) $poll->id,
            ]
        );
    }
}
