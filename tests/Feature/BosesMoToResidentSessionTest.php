<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BosesMoToResidentSessionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--force' => true]);
    }

    public function test_resident_portal_session_can_create_bosesmoto_session(): void
    {
        $resident = $this->createResident();

        Sanctum::actingAs($resident);

        $this->postJson('/api/mobile/auth/resident-session', [
            'device_name' => 'phpunit',
        ])
            ->assertOk()
            ->assertJsonStructure(['message', 'token', 'user' => ['id', 'resident_id', 'name', 'email', 'role']])
            ->assertJsonPath('user.resident_id', $resident->id)
            ->assertJsonPath('user.role', User::ROLE_CITIZEN);

        $this->assertDatabaseHas('users', [
            'resident_id' => $resident->id,
            'email' => 'ana@example.test',
        ]);
    }

    private function createResident(): Resident
    {
        $household = Household::create([
            'household_id' => 'HH-202607-0001',
            'address' => '789 Quezon Ave',
            'barangay' => 'Poblacion',
            'city_municipality' => 'Alaminos',
            'province' => 'Pangasinan',
        ]);

        return Resident::create([
            'household_id' => $household->id,
            'resident_id' => 'R-202607-0001',
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'birth_date' => '1995-04-12',
            'gender' => 'female',
            'civil_status' => 'single',
            'email' => 'ana@example.test',
            'password' => 'secret123',
            'is_active' => true,
        ]);
    }
}
