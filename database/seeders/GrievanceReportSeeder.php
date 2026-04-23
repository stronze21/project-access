<?php

namespace Database\Seeders;

use App\Models\Resident;
use Illuminate\Database\Seeder;
use App\Models\GrievanceReport;

class GrievanceReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $residents = Resident::query()->inRandomOrder()->limit(8)->get();

        if ($residents->isEmpty()) {
            return;
        }

        $entries = [
            ['category' => 'roads', 'subject' => 'Potholes near market road', 'description' => 'Large potholes are making tricycle access difficult during rush hours.', 'status' => 'submitted', 'location_label' => 'Market Road'],
            ['category' => 'drainage', 'subject' => 'Clogged drainage canal', 'description' => 'Water overflows during heavy rain and affects nearby homes.', 'status' => 'under-review', 'location_label' => 'Barangay Poblacion creekside'],
            ['category' => 'lighting', 'subject' => 'Broken streetlight', 'description' => 'Streetlight has been out for several nights and the area feels unsafe.', 'status' => 'in-progress', 'location_label' => 'Rizal Avenue corner Mabini'],
            ['category' => 'sanitation', 'subject' => 'Missed garbage collection', 'description' => 'Waste collection did not pass the area this week.', 'status' => 'resolved', 'location_label' => 'Purok 3'],
        ];

        foreach ($residents as $index => $resident) {
            $entry = $entries[$index % count($entries)];

            GrievanceReport::create([
                'resident_id' => $resident->id,
                'category' => $entry['category'],
                'subject' => $entry['subject'],
                'description' => $entry['description'],
                'status' => $entry['status'],
                'latitude' => 16.1551000 + ($index * 0.0007),
                'longitude' => 119.9812000 + ($index * 0.0006),
                'location_label' => $entry['location_label'],
                'admin_response' => $entry['status'] === 'resolved' ? 'Issue endorsed and completed by the engineering and sanitation teams.' : null,
                'resolved_at' => $entry['status'] === 'resolved' ? now()->subDay() : null,
                'created_at' => now()->subDays(rand(1, 12)),
                'updated_at' => now()->subHours(rand(1, 36)),
            ]);
        }
    }
}
