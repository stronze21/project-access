<?php

namespace Database\Seeders;

use App\Models\Complaint;
use App\Models\ComplaintBarangay;
use App\Models\ComplaintCategory;
use App\Models\ComplaintComment;
use App\Models\ComplaintSupport;
use App\Models\PublicOfficial;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PublicComplaintSeeder extends Seeder
{
    public function run()
    {
        $categoryIds = ComplaintCategory::query()->pluck('id');
        if ($categoryIds->isEmpty()) {
            return;
        }

        $barangayIds = ComplaintBarangay::query()->pluck('id')->values();
        $officialIds = PublicOfficial::query()->pluck('id')->values();
        Role::findOrCreate('citizen', 'web');

        $citizens = collect([
            ['name' => 'Juan Dela Rosa', 'email' => 'citizen.juan@example.com'],
            ['name' => 'Maria Gonzales', 'email' => 'citizen.maria@example.com'],
            ['name' => 'Leo Santos', 'email' => 'citizen.leo@example.com'],
            ['name' => 'Ana Villanueva', 'email' => 'citizen.ana@example.com'],
            ['name' => 'Paolo Reyes', 'email' => 'citizen.paolo@example.com'],
        ])->map(function (array $person): User {
            $user = User::query()->firstOrCreate(
                ['email' => $person['email']],
                [
                    'name' => $person['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->assignRole('citizen');

            return $user;
        })->values();

        $rows = [
            [
                'reference_code' => 'CMP-20260213-A10001',
                'submitted_by_user_id' => $citizens[0]->id,
                'is_anonymous_submission' => false,
                'title' => 'Large potholes along the public market road',
                'short_summary' => 'Multiple potholes are causing traffic slowdowns and vehicle damage.',
                'description' => 'The lane near the market entrance has several deep potholes and worsens during rain.',
                'category_id' => $categoryIds[0],
                'visibility' => Complaint::VISIBILITY_PUBLIC_NAMED,
                'barangay_id' => $barangayIds[0] ?? null,
                'status' => Complaint::STATUS_RESOLVED,
                'priority' => Complaint::PRIORITY_HIGH,
                'resolution_summary' => 'Public works completed asphalt patching and repainting of lane guides.',
                'days_ago' => 20,
            ],
            [
                'reference_code' => 'CMP-20260213-A10002',
                'submitted_by_user_id' => null,
                'is_anonymous_submission' => true,
                'title' => 'Garbage not collected for 3 days',
                'short_summary' => 'Waste collection was skipped, causing foul smell near homes.',
                'description' => 'Collection truck did not arrive on schedule for the past three days.',
                'category_id' => $categoryIds[1] ?? $categoryIds[0],
                'visibility' => Complaint::VISIBILITY_PUBLIC_ANONYMOUS,
                'barangay_id' => $barangayIds[1] ?? null,
                'status' => Complaint::STATUS_IN_PROGRESS,
                'priority' => Complaint::PRIORITY_MEDIUM,
                'resolution_summary' => null,
                'days_ago' => 9,
            ],
            [
                'reference_code' => 'CMP-20260213-A10003',
                'submitted_by_user_id' => $citizens[1]->id,
                'is_anonymous_submission' => false,
                'title' => 'Streetlights not working on main crossing',
                'short_summary' => 'The crossing becomes unsafe at night because lights are out.',
                'description' => 'Three posts at the main crossing have not been lit for over a week.',
                'category_id' => $categoryIds[3] ?? $categoryIds[0],
                'visibility' => Complaint::VISIBILITY_PUBLIC_ANONYMOUS,
                'barangay_id' => $barangayIds[2] ?? null,
                'status' => Complaint::STATUS_ASSIGNED,
                'priority' => Complaint::PRIORITY_HIGH,
                'resolution_summary' => null,
                'days_ago' => 7,
            ],
            [
                'reference_code' => 'CMP-20260213-A10004',
                'submitted_by_user_id' => $citizens[2]->id,
                'is_anonymous_submission' => false,
                'title' => 'Water interruption every evening',
                'short_summary' => 'Households report no water pressure from 6 PM onwards.',
                'description' => 'Water supply drops significantly in the evening affecting household use.',
                'category_id' => $categoryIds[2] ?? $categoryIds[0],
                'visibility' => Complaint::VISIBILITY_PUBLIC_NAMED,
                'barangay_id' => $barangayIds[3] ?? null,
                'status' => Complaint::STATUS_RECEIVED,
                'priority' => Complaint::PRIORITY_URGENT,
                'resolution_summary' => null,
                'days_ago' => 3,
            ],
            [
                'reference_code' => 'CMP-20260213-A10005',
                'submitted_by_user_id' => null,
                'is_anonymous_submission' => true,
                'title' => 'Clogged drainage causing minor flooding',
                'short_summary' => 'Rainwater accumulates and floods part of the road after heavy rain.',
                'description' => 'Drain outlets are blocked by debris, causing overflow into residential streets.',
                'category_id' => $categoryIds[0],
                'visibility' => Complaint::VISIBILITY_PUBLIC_ANONYMOUS,
                'barangay_id' => $barangayIds[4] ?? null,
                'status' => Complaint::STATUS_CLOSED,
                'priority' => Complaint::PRIORITY_MEDIUM,
                'resolution_summary' => 'Drain clearing operation completed and follow-up inspection scheduled.',
                'days_ago' => 28,
            ],
            [
                'reference_code' => 'CMP-20260213-A10006',
                'submitted_by_user_id' => $citizens[3]->id,
                'is_anonymous_submission' => false,
                'title' => 'Health center queue management issue',
                'short_summary' => 'Patients queue too long due to unclear numbering process.',
                'description' => 'Senior citizens and walk-ins queue in one line, creating confusion and delays.',
                'category_id' => $categoryIds[4] ?? $categoryIds[0],
                'visibility' => Complaint::VISIBILITY_PUBLIC_NAMED,
                'barangay_id' => $barangayIds[0] ?? null,
                'status' => Complaint::STATUS_IN_PROGRESS,
                'priority' => Complaint::PRIORITY_LOW,
                'resolution_summary' => null,
                'days_ago' => 5,
            ],
            [
                'reference_code' => 'CMP-20260213-A10007',
                'submitted_by_user_id' => $citizens[4]->id,
                'is_anonymous_submission' => false,
                'title' => 'Illegal dumping near riverside',
                'short_summary' => 'Household trash is being dumped near the riverside pathway.',
                'description' => 'The area has visible mixed waste bags and foul smell.',
                'category_id' => $categoryIds[1] ?? $categoryIds[0],
                'visibility' => Complaint::VISIBILITY_PUBLIC_ANONYMOUS,
                'barangay_id' => $barangayIds[1] ?? null,
                'status' => Complaint::STATUS_RESOLVED,
                'priority' => Complaint::PRIORITY_HIGH,
                'resolution_summary' => 'Site cleaned, warning signage installed, and monitoring ongoing.',
                'days_ago' => 14,
            ],
            [
                'reference_code' => 'CMP-20260213-A10008',
                'submitted_by_user_id' => null,
                'is_anonymous_submission' => true,
                'title' => 'Broken pedestrian railing by school road',
                'short_summary' => 'Safety railing is damaged and may cause accidents.',
                'description' => 'Railing near the school crossing has missing bars and sharp edges.',
                'category_id' => $categoryIds[3] ?? $categoryIds[0],
                'visibility' => Complaint::VISIBILITY_PUBLIC_ANONYMOUS,
                'barangay_id' => $barangayIds[2] ?? null,
                'status' => Complaint::STATUS_ASSIGNED,
                'priority' => Complaint::PRIORITY_URGENT,
                'resolution_summary' => null,
                'days_ago' => 2,
            ],
        ];

        foreach ($rows as $index => $row) {
            $createdAt = Carbon::now()->subDays((int) $row['days_ago']);
            $status = $row['status'];

            $payload = [
                'submitted_by_user_id' => $row['submitted_by_user_id'],
                'is_anonymous_submission' => $row['is_anonymous_submission'],
                'title' => $row['title'],
                'short_summary' => $row['short_summary'],
                'description' => $row['description'],
                'category_id' => $row['category_id'],
                'visibility' => $row['visibility'],
                'barangay_id' => $row['barangay_id'],
                'status' => $status,
                'priority' => $row['priority'],
                'moderation_status' => Complaint::MODERATION_NORMAL,
                'resolution_summary' => $row['resolution_summary'],
                'due_ack_at' => $createdAt->copy()->addDay(),
                'due_first_action_at' => $createdAt->copy()->addDays(3),
                'due_resolution_at' => $createdAt->copy()->addDays(7),
                'resolved_at' => in_array($status, [Complaint::STATUS_RESOLVED, Complaint::STATUS_CLOSED], true) ? $createdAt->copy()->addDays(6) : null,
                'closed_at' => $status === Complaint::STATUS_CLOSED ? $createdAt->copy()->addDays(9) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt->copy()->addHours(12),
            ];

            $complaint = Complaint::query()->updateOrCreate(
                ['reference_code' => $row['reference_code']],
                $payload
            );

            $tagIds = $officialIds->slice(0, ($index % 3))->all();
            if (!empty($tagIds)) {
                $complaint->officials()->syncWithoutDetaching($tagIds);
            }

            ComplaintComment::query()->firstOrCreate([
                'complaint_id' => $complaint->id,
                'body' => 'Thank you for reporting this concern. The case is now in review.',
            ], [
                'user_id' => $citizens[0]->id,
                'is_staff_response' => false,
                'is_hidden' => false,
            ]);
        }

        $supportMap = [
            'CMP-20260213-A10001' => [1, 2, 3],
            'CMP-20260213-A10002' => [0, 2],
            'CMP-20260213-A10003' => [0, 3, 4],
            'CMP-20260213-A10004' => [1],
            'CMP-20260213-A10005' => [2, 3],
            'CMP-20260213-A10006' => [0, 1, 4],
            'CMP-20260213-A10007' => [0, 1, 2, 3],
            'CMP-20260213-A10008' => [4],
        ];

        foreach ($supportMap as $referenceCode => $indexes) {
            $complaint = Complaint::query()->where('reference_code', $referenceCode)->first();
            if (!$complaint) {
                continue;
            }

            foreach ($indexes as $index) {
                $supporter = $citizens[$index] ?? null;
                if (!$supporter || (int) $complaint->submitted_by_user_id === (int) $supporter->id) {
                    continue;
                }

                ComplaintSupport::query()->firstOrCreate([
                    'complaint_id' => $complaint->id,
                    'user_id' => $supporter->id,
                ]);
            }

            $complaint->support_count = ComplaintSupport::query()
                ->where('complaint_id', $complaint->id)
                ->count();
            $complaint->save();
        }
    }
}
