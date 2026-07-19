<?php

namespace Database\Seeders;

use App\Models\CitizenServiceRequest;
use App\Models\Complaint;
use App\Models\ComplaintCategory;
use App\Models\Resident;
use App\Services\ResidentCitizenAccountService;
use Illuminate\Database\Seeder;

class ResidentThreeCitizenServicesSeeder extends Seeder
{
    public function run(): void
    {
        $resident = Resident::query()->find(3);

        if (! $resident) {
            $this->command?->warn('Resident ID 3 was not found; service request and complaint demo data were not seeded.');

            return;
        }

        $citizen = app(ResidentCitizenAccountService::class)->resolve($resident);

        $category = ComplaintCategory::query()->firstOrCreate(
            ['name' => 'General Concern'],
            ['description' => 'General resident concerns requiring city action.', 'is_active' => true]
        );

        $serviceRequests = [
            ['reference_number' => 'CSR-RESIDENT3-001', 'service_type' => 'certificate', 'service_name' => 'Barangay Clearance', 'status' => 'submitted', 'current_step' => 'Application received', 'days_ago' => 2, 'expected_days' => 5, 'notes' => 'For local employment requirements.'],
            ['reference_number' => 'CSR-RESIDENT3-002', 'service_type' => 'permit', 'service_name' => 'Business Permit Renewal', 'status' => 'processing', 'current_step' => 'Assessment and fee validation', 'days_ago' => 7, 'expected_days' => 3, 'notes' => 'Renewal documents are under review.'],
            ['reference_number' => 'CSR-RESIDENT3-003', 'service_type' => 'assistance', 'service_name' => 'Medical Assistance', 'status' => 'for-release', 'current_step' => 'Approved for release', 'days_ago' => 12, 'expected_days' => 1, 'notes' => 'Bring a valid resident ID when claiming.'],
            ['reference_number' => 'CSR-RESIDENT3-004', 'service_type' => 'certificate', 'service_name' => 'Residency Certificate', 'status' => 'completed', 'current_step' => 'Certificate released', 'days_ago' => 20, 'expected_days' => -14, 'notes' => 'Digital copy released to the resident.'],
        ];

        foreach ($serviceRequests as $row) {
            $submittedAt = now()->subDays($row['days_ago']);
            $isComplete = $row['status'] === 'completed';

            CitizenServiceRequest::query()->updateOrCreate(
                ['reference_number' => $row['reference_number']],
                [
                    'resident_id' => $resident->id,
                    'service_type' => $row['service_type'],
                    'service_name' => $row['service_name'],
                    'status' => $row['status'],
                    'current_step' => $row['current_step'],
                    'submitted_at' => $submittedAt,
                    'status_updated_at' => $submittedAt->copy()->addDay(),
                    'expected_completion_at' => now()->addDays($row['expected_days']),
                    'completed_at' => $isComplete ? now()->subDays(14) : null,
                    'notes' => $row['notes'],
                    'metadata' => ['channel' => 'demo-seeder', 'resident_id' => 3],
                ]
            );
        }

        $complaints = [
            ['reference_code' => 'BMT-RESIDENT3-001', 'title' => 'Streetlight outage near residence', 'summary' => 'Two streetlights have been out for several nights.', 'description' => 'The streetlights near the main intersection are not working and the area is very dark at night.', 'status' => Complaint::STATUS_RECEIVED, 'priority' => Complaint::PRIORITY_MEDIUM, 'days_ago' => 2],
            ['reference_code' => 'BMT-RESIDENT3-002', 'title' => 'Uncollected waste on scheduled day', 'summary' => 'Household waste was not collected on the posted schedule.', 'description' => 'Waste has remained outside since the scheduled collection day and needs pickup.', 'status' => Complaint::STATUS_ASSIGNED, 'priority' => Complaint::PRIORITY_MEDIUM, 'days_ago' => 5],
            ['reference_code' => 'BMT-RESIDENT3-003', 'title' => 'Drainage obstruction after rainfall', 'summary' => 'A blocked drainage channel causes water to collect on the road.', 'description' => 'Debris is obstructing the drainage channel and causes minor flooding after heavy rain.', 'status' => Complaint::STATUS_IN_PROGRESS, 'priority' => Complaint::PRIORITY_HIGH, 'days_ago' => 9],
            ['reference_code' => 'BMT-RESIDENT3-004', 'title' => 'Damaged pedestrian pathway', 'summary' => 'A damaged section of pathway creates a tripping hazard.', 'description' => 'The concrete pathway has a broken and uneven section that needs repair.', 'status' => Complaint::STATUS_RESOLVED, 'priority' => Complaint::PRIORITY_LOW, 'days_ago' => 16],
        ];

        foreach ($complaints as $row) {
            $createdAt = now()->subDays($row['days_ago']);
            $isResolved = $row['status'] === Complaint::STATUS_RESOLVED;

            Complaint::query()->updateOrCreate(
                ['reference_code' => $row['reference_code']],
                [
                    'submitted_by_user_id' => $citizen->id,
                    'is_anonymous_submission' => false,
                    'reporter_name' => $resident->full_name,
                    'reporter_email' => $resident->email,
                    'title' => $row['title'],
                    'short_summary' => $row['summary'],
                    'description' => $row['description'],
                    'category_id' => $category->id,
                    'visibility' => Complaint::VISIBILITY_PRIVATE,
                    'status' => $row['status'],
                    'priority' => $row['priority'],
                    'moderation_status' => Complaint::MODERATION_NORMAL,
                    'resolution_summary' => $isResolved ? 'The reported area was inspected and repaired.' : null,
                    'resolved_at' => $isResolved ? $createdAt->copy()->addDays(10) : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt->copy()->addDay(),
                ]
            );
        }
    }
}
