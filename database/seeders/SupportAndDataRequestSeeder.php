<?php

namespace Database\Seeders;

use App\Models\AccountDeletionRequest;
use App\Models\CitizenServiceRequest;
use App\Models\Resident;
use App\Models\SupportRequest;
use Illuminate\Database\Seeder;

class SupportAndDataRequestSeeder extends Seeder
{
    public function run(): void
    {
        $residents = CitizenServiceRequest::query()
            ->with('resident')
            ->latest()
            ->take(4)
            ->get()
            ->pluck('resident')
            ->filter()
            ->values();

        if ($residents->count() < 4) {
            $residents = Resident::query()->where('is_active', true)->take(4)->get();
        }

        if ($residents->isEmpty()) {
            return;
        }

        $supportTemplates = [
            [
                'reference_number' => 'SUP-LOCAL-0001',
                'category' => 'account',
                'subject' => 'Cannot update resident profile',
                'message' => 'Resident needs help updating contact number and profile information.',
                'status' => SupportRequest::STATUS_RECEIVED,
            ],
            [
                'reference_number' => 'SUP-LOCAL-0002',
                'category' => 'technical',
                'subject' => 'MPIN login assistance',
                'message' => 'Resident reports that the MPIN screen accepts input but does not proceed.',
                'status' => SupportRequest::STATUS_REVIEWING,
            ],
            [
                'reference_number' => 'SUP-LOCAL-0003',
                'category' => 'service-request',
                'subject' => 'Follow-up on certificate request',
                'message' => 'Resident is asking for an update on a submitted city service request.',
                'status' => SupportRequest::STATUS_RESOLVED,
                'resolved_at' => now()->subDay(),
                'admin_notes' => 'Resident was advised to check Track Services for the latest status.',
            ],
            [
                'reference_number' => 'SUP-LOCAL-0004',
                'category' => 'privacy',
                'subject' => 'Question about stored data',
                'message' => 'Resident wants to know what portal data is stored and how long it is retained.',
                'status' => SupportRequest::STATUS_CLOSED,
                'resolved_at' => now()->subDays(2),
                'admin_notes' => 'Privacy notice link was provided.',
            ],
        ];

        foreach ($supportTemplates as $index => $template) {
            $resident = $residents[$index % $residents->count()];

            SupportRequest::updateOrCreate(
                ['reference_number' => $template['reference_number']],
                $this->residentPayload($resident) + [
                    'category' => $template['category'],
                    'subject' => $template['subject'],
                    'message' => $template['message'],
                    'status' => $template['status'],
                    'source' => 'resident-web',
                    'platform' => 'Resident Portal',
                    'device_name' => 'Local test browser',
                    'submitted_at' => now()->subDays($index + 1),
                    'resolved_at' => $template['resolved_at'] ?? null,
                    'admin_notes' => $template['admin_notes'] ?? null,
                ]
            );
        }

        $dataTemplates = [
            [
                'reference_number' => 'ADR-LOCAL-0001',
                'requested_action' => 'delete-app-data-only',
                'reason' => 'Resident wants test app data removed but wants to keep the portal account active.',
                'status' => AccountDeletionRequest::STATUS_RECEIVED,
            ],
            [
                'reference_number' => 'ADR-LOCAL-0002',
                'requested_action' => 'delete-account-and-data',
                'reason' => 'Resident requested deletion of portal account and eligible app data.',
                'status' => AccountDeletionRequest::STATUS_REVIEWING,
                'admin_notes' => 'Identity verification is pending.',
            ],
            [
                'reference_number' => 'ADR-LOCAL-0003',
                'requested_action' => 'delete-app-data-only',
                'reason' => 'Resident requested removal of old support and app activity data.',
                'status' => AccountDeletionRequest::STATUS_COMPLETED,
                'processed_at' => now()->subDay(),
                'admin_notes' => 'Eligible app data cleanup completed.',
            ],
        ];

        foreach ($dataTemplates as $index => $template) {
            $resident = $residents[$index % $residents->count()];

            AccountDeletionRequest::updateOrCreate(
                ['reference_number' => $template['reference_number']],
                $this->residentPayload($resident) + [
                    'reason' => $template['reason'],
                    'requested_action' => $template['requested_action'],
                    'retention_acknowledged' => true,
                    'status' => $template['status'],
                    'source' => 'resident-web',
                    'platform' => 'Resident Portal',
                    'device_name' => 'Local test browser',
                    'submitted_at' => now()->subDays($index + 2),
                    'processed_at' => $template['processed_at'] ?? null,
                    'admin_notes' => $template['admin_notes'] ?? null,
                ]
            );
        }
    }

    private function residentPayload(Resident $resident): array
    {
        return [
            'resident_id' => $resident->id,
            'resident_identifier' => $resident->resident_id,
            'resident_name' => trim($resident->first_name.' '.$resident->last_name),
            'email' => $resident->email,
            'contact_number' => $resident->contact_number,
        ];
    }
}
