<?php

namespace Database\Seeders;

use App\Models\Poll;
use App\Models\PollVote;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PollSeeder extends Seeder
{
    public function run()
    {
        $creator = User::query()->where('email', 'mayor@test.local')->first()
            ?? User::query()->where('email', 'admin@test.local')->first()
            ?? User::query()->first();

        $voters = User::query()
            ->whereNotNull('email_verified_at')
            ->orderBy('id')
            ->limit(12)
            ->get();

        $pollDefinitions = [
            [
                'question' => 'Which city project should be prioritized next?',
                'description' => 'Help us decide which municipal project to focus on first this quarter.',
                'is_active' => true,
                'starts_at' => Carbon::now()->subDays(3),
                'ends_at' => Carbon::now()->addDays(14),
                'options' => [
                    'Road and pothole repairs',
                    'Street lighting upgrades',
                    'Drainage and flood mitigation',
                    'Public park rehabilitation',
                ],
            ],
            [
                'question' => 'Preferred channel for city emergency updates',
                'description' => 'Select the communication channel you use most during emergencies.',
                'is_active' => true,
                'starts_at' => Carbon::now()->subDays(1),
                'ends_at' => Carbon::now()->addDays(21),
                'options' => [
                    'SMS text alerts',
                    'Facebook page posts',
                    'Barangay loudspeaker announcements',
                    'Mobile app notifications',
                ],
            ],
            [
                'question' => 'What community service needs expansion?',
                'description' => 'This poll has already closed. We will use results for monthly planning.',
                'is_active' => false,
                'starts_at' => Carbon::now()->subDays(30),
                'ends_at' => Carbon::now()->subDays(5),
                'options' => [
                    'Weekend health clinics',
                    'Youth skills training',
                    'Senior citizen support programs',
                    'Livelihood and job assistance',
                ],
            ],
        ];

        foreach ($pollDefinitions as $pollData) {
            $poll = Poll::query()->updateOrCreate(
                ['question' => $pollData['question']],
                [
                    'description' => $pollData['description'],
                    'created_by_user_id' => $creator?->id,
                    'is_active' => $pollData['is_active'],
                    'starts_at' => $pollData['starts_at'],
                    'ends_at' => $pollData['ends_at'],
                ]
            );

            // Reset options/votes to keep seeded polls deterministic.
            $poll->options()->delete();

            $optionIds = [];
            foreach ($pollData['options'] as $index => $optionText) {
                $option = $poll->options()->create([
                    'option_text' => $optionText,
                    'sort_order' => $index + 1,
                ]);
                $optionIds[] = $option->id;
            }

            if (!empty($optionIds)) {
                PollVote::query()->where('poll_id', $poll->id)->delete();

                $voterCount = min(count($optionIds) * 2, $voters->count());
                foreach ($voters->take($voterCount)->values() as $voterIndex => $voter) {
                    PollVote::query()->create([
                        'poll_id' => $poll->id,
                        'poll_option_id' => $optionIds[$voterIndex % count($optionIds)],
                        'user_id' => $voter->id,
                    ]);
                }
            }
        }

        $this->command?->info('Seeded sample polls and votes.');
    }
}
