<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\EmergencyAlert;

class EmergencyAlertSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userId = User::query()->value('id');

        $alerts = [
            [
                'title' => 'Heavy Rainfall Advisory',
                'message' => 'Moderate to heavy rainfall is expected this afternoon. Residents in low-lying areas should prepare for possible flooding.',
                'severity' => 'high',
                'status' => 'active',
                'alert_type' => 'weather',
                'send_push_notification' => false,
                'starts_at' => now()->subHours(2),
                'ends_at' => now()->addHours(10),
            ],
            [
                'title' => 'Water Service Interruption',
                'message' => 'Emergency pipe repairs may cause intermittent water interruption in selected barangays until tonight.',
                'severity' => 'medium',
                'status' => 'active',
                'alert_type' => 'utility',
                'send_push_notification' => false,
                'starts_at' => now()->subHour(),
                'ends_at' => now()->addHours(8),
            ],
            [
                'title' => 'Earthquake Drill Notice',
                'message' => 'A scheduled city-wide earthquake drill will be conducted tomorrow at 9:00 AM.',
                'severity' => 'low',
                'status' => 'scheduled',
                'alert_type' => 'drill',
                'send_push_notification' => false,
                'starts_at' => now()->addDay()->setTime(9, 0),
                'ends_at' => now()->addDay()->setTime(11, 0),
            ],
        ];

        foreach ($alerts as $alert) {
            EmergencyAlert::create(array_merge($alert, [
                'created_by' => $userId,
                'metadata' => [
                    'seeded' => true,
                ],
            ]));
        }
    }
}
