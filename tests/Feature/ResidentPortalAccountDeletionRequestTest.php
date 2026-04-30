<?php

namespace Tests\Feature;

use App\Models\AccountDeletionRequest;
use App\Models\Household;
use App\Models\Resident;
use App\Models\SupportRequest;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResidentPortalAccountDeletionRequestTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--force' => true]);
    }

    public function test_authenticated_resident_can_submit_account_deletion_request(): void
    {
        $resident = $this->createResident();

        Sanctum::actingAs($resident);

        $this->postJson('/api/resident-portal/account-deletion-requests', [
            'reason' => 'I no longer use the app.',
            'platform' => 'android',
            'device_name' => 'Pixel Test',
            'requested_action' => 'delete-account-and-data',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Account deletion request received.')
            ->assertJsonPath('data.status', AccountDeletionRequest::STATUS_RECEIVED);

        $this->assertDatabaseHas('account_deletion_requests', [
            'resident_id' => $resident->id,
            'resident_identifier' => $resident->resident_id,
            'resident_name' => $resident->full_name,
            'source' => 'mobile-api',
            'platform' => 'android',
            'requested_action' => 'delete-account-and-data',
            'retention_acknowledged' => true,
        ]);

        $this->getJson('/api/resident-portal/account-deletion-requests')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', AccountDeletionRequest::STATUS_RECEIVED);
    }

    public function test_public_web_form_creates_account_deletion_request(): void
    {
        $this->post('/account-deletion', [
            'resident_identifier' => 'R-202604-0099',
            'resident_name' => 'Maria Santos',
            'email' => 'maria@example.test',
            'contact_number' => '09170000000',
            'reason' => 'Please delete my app account.',
            'requested_action' => 'delete-account-and-data',
            'retention_acknowledged' => '1',
        ])
            ->assertRedirect(route('account-deletion.create'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('account_deletion_requests', [
            'resident_identifier' => 'R-202604-0099',
            'resident_name' => 'Maria Santos',
            'email' => 'maria@example.test',
            'source' => 'web-form',
            'requested_action' => 'delete-account-and-data',
            'retention_acknowledged' => true,
        ]);
    }

    public function test_authenticated_resident_can_submit_support_request(): void
    {
        $resident = $this->createResident();

        Sanctum::actingAs($resident);

        $this->postJson('/api/resident-portal/support-requests', [
            'category' => 'technical',
            'subject' => 'Cannot open announcements',
            'message' => 'The announcements screen does not load.',
            'platform' => 'android',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Support request received.')
            ->assertJsonPath('data.status', SupportRequest::STATUS_RECEIVED);

        $this->assertDatabaseHas('support_requests', [
            'resident_id' => $resident->id,
            'resident_identifier' => $resident->resident_id,
            'category' => 'technical',
            'subject' => 'Cannot open announcements',
            'source' => 'mobile-api',
        ]);

        $this->getJson('/api/resident-portal/support-requests')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', SupportRequest::STATUS_RECEIVED);
    }

    public function test_public_support_form_creates_support_request(): void
    {
        $this->post('/support', [
            'resident_identifier' => 'R-202604-0099',
            'resident_name' => 'Maria Santos',
            'email' => 'maria@example.test',
            'contact_number' => '09170000000',
            'category' => 'privacy',
            'subject' => 'Privacy question',
            'message' => 'Please help me understand my data.',
        ])
            ->assertRedirect(route('legal.support'))
            ->assertSessionHas('support_status');

        $this->assertDatabaseHas('support_requests', [
            'resident_identifier' => 'R-202604-0099',
            'resident_name' => 'Maria Santos',
            'email' => 'maria@example.test',
            'category' => 'privacy',
            'source' => 'web-form',
        ]);
    }

    private function createResident(): Resident
    {
        $suffix = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        $household = Household::create([
            'household_id' => 'HH-202604-' . $suffix,
            'address' => '123 Rizal St',
            'barangay' => 'Poblacion',
            'city_municipality' => 'Alaminos',
            'province' => 'Pangasinan',
        ]);

        return Resident::create([
            'household_id' => $household->id,
            'resident_id' => 'R-202604-' . $suffix,
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '1992-02-20',
            'gender' => 'female',
            'civil_status' => 'single',
            'email' => 'maria@example.test',
            'contact_number' => '09170000000',
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }
}
