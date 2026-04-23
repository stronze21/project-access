<?php

namespace Database\Seeders;

use App\Models\Resident;
use Illuminate\Database\Seeder;
use App\Models\SosAlert;

class SosAlertSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $residents = Resident::query()->whereNotNull('contact_number')->inRandomOrder()->limit(5)->get();

        if ($residents->isEmpty()) {
            return;
        }

        $statuses = ['open', 'acknowledged', 'resolved'];

        foreach ($residents as $index => $resident) {
            $status = $statuses[$index % count($statuses)];

            SosAlert::create([
                'resident_id' => $resident->id,
                'status' => $status,
                'contact_number' => $resident->contact_number,
                'message' => [
                    'Requesting medical response.',
                    'Need barangay rescue assistance for flooding.',
                    'Senior citizen needs immediate help.',
                ][$index % 3],
                'latitude' => 16.1560000 + ($index * 0.0005),
                'longitude' => 119.9820000 + ($index * 0.0004),
                'location_label' => 'Response Zone ' . ($index + 1),
                'acknowledged_at' => in_array($status, ['acknowledged', 'resolved'], true) ? now()->subMinutes(rand(20, 120)) : null,
                'resolved_at' => $status === 'resolved' ? now()->subMinutes(rand(5, 60)) : null,
                'created_at' => now()->subHours(rand(1, 24)),
                'updated_at' => now()->subMinutes(rand(5, 180)),
            ]);
        }
    }
}
