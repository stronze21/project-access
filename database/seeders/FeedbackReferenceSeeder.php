<?php

namespace Database\Seeders;

use App\Models\ComplaintBarangay;
use App\Models\ComplaintCategory;
use App\Models\Department;
use App\Models\PublicOfficial;
use Illuminate\Database\Seeder;

class FeedbackReferenceSeeder extends Seeder
{
    public function run()
    {
        Department::query()->upsert([
            ['name' => 'Engineering Office', 'email' => null, 'description' => 'Infrastructure and public works concerns.', 'is_active' => true],
            ['name' => 'Health Office', 'email' => null, 'description' => 'Public health-related concerns.', 'is_active' => true],
            ['name' => 'Environment Office', 'email' => null, 'description' => 'Waste, cleanliness, and environmental issues.', 'is_active' => true],
            ['name' => 'Public Safety Office', 'email' => null, 'description' => 'Traffic, lighting, and safety concerns.', 'is_active' => true],
        ], ['name'], ['email', 'description', 'is_active']);

        ComplaintCategory::query()->upsert([
            ['name' => 'Roads & Infrastructure', 'description' => 'Potholes, drainage, bridges, and roads', 'is_active' => true],
            ['name' => 'Sanitation & Waste', 'description' => 'Garbage collection and sanitation issues', 'is_active' => true],
            ['name' => 'Water & Utilities', 'description' => 'Water interruptions and utility issues', 'is_active' => true],
            ['name' => 'Public Safety', 'description' => 'Street lights, hazards, and emergency concerns', 'is_active' => true],
            ['name' => 'Health Services', 'description' => 'Healthcare access and facility concerns', 'is_active' => true],
        ], ['name'], ['description', 'is_active']);

        ComplaintBarangay::query()->upsert([
            ['name' => 'Poblacion', 'code' => 'BRGY-001', 'is_active' => true],
            ['name' => 'Barangay 1', 'code' => 'BRGY-002', 'is_active' => true],
            ['name' => 'Barangay 2', 'code' => 'BRGY-003', 'is_active' => true],
            ['name' => 'Barangay 3', 'code' => 'BRGY-004', 'is_active' => true],
            ['name' => 'Barangay 4', 'code' => 'BRGY-005', 'is_active' => true],
        ], ['name'], ['code', 'is_active']);

        PublicOfficial::query()->upsert([
            ['name' => 'Juan Dela Cruz', 'position' => 'Mayor', 'is_active' => true],
            ['name' => 'Maria Santos', 'position' => 'Vice Mayor', 'is_active' => true],
            ['name' => 'Carlos Reyes', 'position' => 'Councilor', 'is_active' => true],
        ], ['name', 'position'], ['is_active']);
    }
}
