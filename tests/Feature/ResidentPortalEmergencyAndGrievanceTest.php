<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\ResidentNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResidentPortalEmergencyAndGrievanceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--force' => true]);
    }

    public function test_authenticated_resident_can_submit_grievance_and_sos(): void
    {
        $resident = $this->createResident();

        Sanctum::actingAs($resident);

        $this->postJson('/api/resident-portal/grievances', [
            'category' => 'roads',
            'subject' => 'Blocked drainage',
            'description' => 'Drainage is causing flooding near the market.',
            'latitude' => 16.1554321,
            'longitude' => 119.9812345,
            'location_label' => 'Market Road',
        ])->assertCreated()
            ->assertJsonPath('data.category', 'roads');

        $this->postJson('/api/resident-portal/emergency/sos', [
            'message' => 'Need urgent medical assistance.',
            'latitude' => 16.1554321,
            'longitude' => 119.9812345,
            'location_label' => 'Market Road',
        ])->assertCreated()
            ->assertJsonPath('data.status', 'open');

        $this->assertDatabaseHas('grievance_reports', [
            'resident_id' => $resident->id,
            'category' => 'roads',
        ]);

        $this->assertDatabaseHas('sos_alerts', [
            'resident_id' => $resident->id,
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('resident_notifications', [
            'resident_id' => $resident->id,
            'type' => 'sos',
        ]);

        $this->assertSame(1, ResidentNotification::where('resident_id', $resident->id)->where('type', 'sos')->count());
    }

    private function createResident(): Resident
    {
        $household = Household::create([
            'household_id' => 'HH-202604-0002',
            'address' => '456 Rizal Ave',
            'barangay' => 'Poblacion',
            'city_municipality' => 'Alaminos',
            'province' => 'Pangasinan',
        ]);

        return Resident::create([
            'household_id' => $household->id,
            'resident_id' => 'R-202604-0002',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '1992-06-20',
            'gender' => 'female',
            'civil_status' => 'single',
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }
}
