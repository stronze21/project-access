<?php

namespace Database\Seeders;

use App\Models\Resident;
use Illuminate\Database\Seeder;
use App\Models\CitizenServiceRequest;

class CitizenServiceRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $residents = Resident::query()->inRandomOrder()->limit(12)->get();

        if ($residents->isEmpty()) {
            return;
        }

        $templates = [
            [
                'service_type' => 'business-permit',
                'service_name' => 'Business Permit Renewal',
                'status' => 'processing',
                'current_step' => 'Assessment and fee validation',
                'expected_completion_at' => now()->addDays(4),
                'notes' => 'Submitted through the city BPLS portal.',
            ],
            [
                'service_type' => 'civil-registry',
                'service_name' => 'Birth Certificate Request',
                'status' => 'submitted',
                'current_step' => 'Application received',
                'expected_completion_at' => now()->addDays(7),
                'notes' => 'Awaiting supporting document review.',
            ],
            [
                'service_type' => 'tax-payment',
                'service_name' => 'Real Property Tax Clearance',
                'status' => 'for-release',
                'current_step' => 'Ready for digital release',
                'expected_completion_at' => now()->addDay(),
                'notes' => 'Resident will receive SMS once clearance is released.',
            ],
            [
                'service_type' => 'financial-aid',
                'service_name' => 'Medical Assistance Application',
                'status' => 'completed',
                'current_step' => 'Approved and released',
                'expected_completion_at' => now()->subDay(),
                'completed_at' => now()->subDays(2),
                'notes' => 'Assistance voucher already claimed.',
            ],
        ];

        foreach ($residents as $index => $resident) {
            $template = $templates[$index % count($templates)];

            CitizenServiceRequest::create([
                'resident_id' => $resident->id,
                'service_type' => $template['service_type'],
                'service_name' => $template['service_name'],
                'status' => $template['status'],
                'current_step' => $template['current_step'],
                'submitted_at' => now()->subDays(rand(1, 14)),
                'status_updated_at' => now()->subHours(rand(2, 72)),
                'expected_completion_at' => $template['expected_completion_at'],
                'completed_at' => $template['completed_at'] ?? null,
                'notes' => $template['notes'],
                'metadata' => [
                    'channel' => 'mobile',
                    'priority' => $template['status'] === 'completed' ? 'normal' : 'high',
                ],
            ]);
        }
    }
}
