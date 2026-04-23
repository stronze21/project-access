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
            'service_name' => 'Business Permit Renewal',
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
            ->assertJsonPath('data.0.service_name', 'Business Permit Renewal');

        $this->getJson('/api/resident-portal/public-services')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Business Permit Portal');
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
