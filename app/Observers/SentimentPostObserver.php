<?php

namespace App\Observers;

use App\Models\SentimentPost;
use App\Services\FcmPushService;

class SentimentPostObserver
{
    public function created(SentimentPost $post): void
    {
        if ($post->hidden_at !== null || $post->is_permanently_deleted) {
            return;
        }

        app(FcmPushService::class)->sendTopicNotification(
            FcmPushService::TOPIC_SENTIMENTS,
            'New People Sentiment',
            'There is a new post in People Sentiments.',
            [
                'type' => 'sentiment',
                'id' => (string) $post->id,
            ]
        );
    }
}
