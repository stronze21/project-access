<?php

namespace Database\Seeders;

use App\Models\SentimentComment;
use App\Models\SentimentFollow;
use App\Models\SentimentPost;
use App\Models\SentimentReaction;
use App\Models\SentimentReport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SentimentSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->orderBy('id')->get();
        if ($users->count() < 3) {
            $this->command?->warn('SentimentSeeder skipped: at least 3 users are required.');
            return;
        }

        $reactionTypes = array_keys(config('sentiments.reaction_types', []));
        if (empty($reactionTypes)) {
            $reactionTypes = ['like', 'love', 'haha', 'wow', 'sad', 'angry', 'care'];
        }

        $samples = [
            [
                'body' => 'Good evening, team. City clean-up drive this weekend. @Citizen',
                'external_url' => null,
                'media_kind' => SentimentPost::MEDIA_NONE,
            ],
            [
                'body' => 'Can we improve drainage near the market? Recent rain was tough.',
                'external_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'media_kind' => SentimentPost::MEDIA_EXTERNAL,
            ],
            [
                'body' => 'Road repair update: patching started in Barangay East.',
                'external_url' => null,
                'media_kind' => SentimentPost::MEDIA_NONE,
            ],
            [
                'body' => 'Public hearing schedule posted. Please share your feedback respectfully.',
                'external_url' => null,
                'media_kind' => SentimentPost::MEDIA_NONE,
            ],
            [
                'body' => 'Reminder: report streetlight outages with exact location details.',
                'external_url' => null,
                'media_kind' => SentimentPost::MEDIA_NONE,
            ],
        ];

        $createdPosts = collect();

        foreach ($samples as $index => $sample) {
            $author = $users[$index % $users->count()];

            $post = SentimentPost::query()->updateOrCreate(
                [
                    'user_id' => $author->id,
                    'body' => $sample['body'],
                ],
                [
                    'media_kind' => $sample['media_kind'],
                    'external_url' => $sample['external_url'],
                    'is_pinned' => $index === 0,
                    'is_comments_locked' => false,
                    'hidden_at' => null,
                    'hidden_reason' => null,
                    'is_permanently_deleted' => false,
                    'edited_at' => null,
                    'deleted_at' => null,
                    'updated_at' => Carbon::now()->subHours(max(1, 5 - $index)),
                    'created_at' => Carbon::now()->subHours(12 - $index),
                ]
            );

            $createdPosts->push($post);
        }

        foreach ($createdPosts as $post) {
            $firstComment = SentimentComment::query()->firstOrCreate([
                'post_id' => $post->id,
                'parent_id' => null,
                'user_id' => $users->random()->id,
                'body' => 'Thanks for the update. Noted.',
            ]);

            $secondComment = SentimentComment::query()->firstOrCreate([
                'post_id' => $post->id,
                'parent_id' => $firstComment->id,
                'user_id' => $users->random()->id,
                'body' => 'Agree. Please include timelines.',
            ]);

            SentimentComment::query()->firstOrCreate([
                'post_id' => $post->id,
                'parent_id' => $secondComment->id,
                'user_id' => $users->random()->id,
                'body' => 'Following this thread.',
            ]);
        }

        foreach ($createdPosts as $post) {
            $voters = $users->shuffle()->take(min(6, $users->count()));
            foreach ($voters as $voter) {
                if ((int) $voter->id === (int) $post->user_id) {
                    continue;
                }

                SentimentReaction::query()->updateOrCreate(
                    [
                        'user_id' => $voter->id,
                        'reactionable_type' => SentimentPost::class,
                        'reactionable_id' => $post->id,
                    ],
                    [
                        'reaction' => $reactionTypes[array_rand($reactionTypes)],
                    ]
                );
            }
        }

        $allComments = SentimentComment::query()->get();
        foreach ($allComments as $comment) {
            $reactors = $users->shuffle()->take(min(3, $users->count()));
            foreach ($reactors as $reactor) {
                if ((int) $reactor->id === (int) $comment->user_id) {
                    continue;
                }

                SentimentReaction::query()->updateOrCreate(
                    [
                        'user_id' => $reactor->id,
                        'reactionable_type' => SentimentComment::class,
                        'reactionable_id' => $comment->id,
                    ],
                    [
                        'reaction' => $reactionTypes[array_rand($reactionTypes)],
                    ]
                );
            }
        }

        foreach ($users as $follower) {
            $followed = $users->where('id', '!=', $follower->id)->shuffle()->take(2);
            foreach ($followed as $followedUser) {
                SentimentFollow::query()->firstOrCreate([
                    'follower_user_id' => $follower->id,
                    'followed_user_id' => $followedUser->id,
                ]);
            }
        }

        // Add one auto-hidden sample post by creating 10 open reports.
        $targetPost = $createdPosts->last();
        if ($targetPost) {
            $reporters = $users->where('id', '!=', $targetPost->user_id)->take(10);
            foreach ($reporters as $reporter) {
                SentimentReport::query()->updateOrCreate(
                    [
                        'reporter_user_id' => $reporter->id,
                        'reportable_type' => SentimentPost::class,
                        'reportable_id' => $targetPost->id,
                    ],
                    [
                        'status' => SentimentReport::STATUS_OPEN,
                        'reason' => 'Seeder sample report',
                        'reviewed_by_user_id' => null,
                        'reviewed_at' => null,
                    ]
                );
            }

            $openCount = SentimentReport::query()
                ->where('reportable_type', SentimentPost::class)
                ->where('reportable_id', $targetPost->id)
                ->where('status', SentimentReport::STATUS_OPEN)
                ->count();

            $targetPost->reports_count = $openCount;
            if ($openCount >= (int) config('sentiments.report_auto_hide_threshold', 10)) {
                $targetPost->hidden_at = now();
                $targetPost->hidden_reason = 'auto_hidden_reports';
            }
            $targetPost->save();
        }

        $this->command?->info('Sentiment module sample data seeded.');
    }
}

