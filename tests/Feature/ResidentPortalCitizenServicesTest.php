<?php

namespace Tests\Feature;

use App\Models\CitizenServiceRequest;
use App\Models\Household;
use App\Models\PublicServiceLink;
use App\Models\Resident;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResidentPortalCitizenServicesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--force' => true]);
    }

    public function test_authenticated_resident_can_view_service_tracking_and_public_service_links(): void
    {
        $resident = $this->createResident();

        CitizenServiceRequest::create([
            'resident_id' => $resident->id,
            'service_type' => 'business-permit',
            'service_name' => 'Business Permit',
            'status' => 'processing',
            'current_step' => 'Assessment review',
        ]);

        PublicServiceLink::create([
            'title' => 'Business Permit Portal',
            'slug' => 'business-permit-portal',
            'service_type' => 'business-permit',
            'url' => 'https://example.com/bpls',
            'is_active' => true,
        ]);

        Sanctum::actingAs($resident);

        $this->getJson('/api/resident-portal/services')
            ->assertOk()
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('data.0.service_name', 'Business Permit');

        $this->getJson('/api/resident-portal/public-services')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Business Permit Portal');
    }

    public function test_authenticated_resident_can_create_service_request_with_derived_name(): void
    {
        $resident = $this->createResident();

        Sanctum::actingAs($resident);

        $this->postJson('/api/resident-portal/services', [
            'service_type' => 'certificate',
            'notes' => 'Needed for employment.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.service_type', 'certificate')
            ->assertJsonPath('data.service_name', 'Certificate');

        $this->assertDatabaseHas('citizen_service_requests', [
            'resident_id' => $resident->id,
            'service_type' => 'certificate',
            'service_name' => 'Certificate',
            'notes' => 'Needed for employment.',
        ]);
    }

    public function test_service_create_ignores_client_provided_service_name(): void
    {
        $resident = $this->createResident();

        Sanctum::actingAs($resident);

        $this->postJson('/api/resident-portal/services', [
            'service_type' => 'permit',
            'service_name' => 'Custom Title That Should Be Ignored',
        ])
            ->assertCreated()
            ->assertJsonPath('data.service_name', 'Permit');

        $this->assertDatabaseHas('citizen_service_requests', [
            'resident_id' => $resident->id,
            'service_type' => 'permit',
            'service_name' => 'Permit',
        ]);
    }

    public function test_household_members_endpoint_returns_only_authenticated_resident(): void
    {
        $resident = $this->createResident();
        $member = Resident::create([
            'household_id' => null,
            'resident_id' => 'R-202604-HOUSEHOLD-MEMBER',
            'first_name' => 'Maria',
            'last_name' => 'Dela Cruz',
            'birth_date' => '2010-06-15',
            'gender' => 'female',
            'civil_status' => 'single',
            'relationship_to_head' => 'child',
            'is_active' => true,
        ]);

        $otherHousehold = Household::create([
            'household_id' => 'HH-202604-OTHER',
            'address' => 'Other Address',
            'barangay' => 'Poblacion',
            'city_municipality' => 'Alaminos',
            'province' => 'Pangasinan',
        ]);
        Resident::create([
            'household_id' => $otherHousehold->id,
            'resident_id' => 'R-202604-OTHER-MEMBER',
            'first_name' => 'Other',
            'last_name' => 'Resident',
            'birth_date' => '1995-01-01',
            'gender' => 'male',
            'civil_status' => 'single',
            'is_active' => true,
        ]);

        Sanctum::actingAs($resident);

        $this->getJson('/api/resident-portal/household/members')
            ->assertOk()
            ->assertJsonPath('member_count', 1)
            ->assertJsonFragment(['resident_id' => $resident->resident_id])
            ->assertJsonMissing(['resident_id' => $member->resident_id])
            ->assertJsonMissing(['resident_id' => 'R-202604-OTHER-MEMBER']);
    }

    private function createResident(): Resident
    {
        $household = Household::create([
            'household_id' => 'HH-202604-0001',
            'address' => '123 Mabini St',
            'barangay' => 'Poblacion',
            'city_municipality' => 'Alaminos',
            'province' => 'Pangasinan',
        ]);

        return Resident::create([
            'household_id' => $household->id,
            'resident_id' => 'R-202604-0001',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'birth_date' => '1990-01-15',
            'gender' => 'male',
            'civil_status' => 'single',
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }
}
