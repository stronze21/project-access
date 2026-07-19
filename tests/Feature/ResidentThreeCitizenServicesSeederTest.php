<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\User;
use Database\Seeders\ResidentThreeCitizenServicesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentThreeCitizenServicesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_seeds_service_requests_and_complaints_for_resident_three(): void
    {
        Resident::query()->create([
            'resident_id' => 'R-SEED-RESIDENT-1', 'first_name' => 'First', 'last_name' => 'Resident',
            'birth_date' => '1990-01-01', 'gender' => 'male', 'civil_status' => 'single', 'is_active' => true,
        ]);
        Resident::query()->create([
            'resident_id' => 'R-SEED-RESIDENT-2', 'first_name' => 'Second', 'last_name' => 'Resident',
            'birth_date' => '1990-01-01', 'gender' => 'female', 'civil_status' => 'single', 'is_active' => true,
        ]);
        $resident = Resident::query()->create([
            'resident_id' => 'R-SEED-RESIDENT-3',
            'first_name' => 'Demo',
            'last_name' => 'Resident',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'civil_status' => 'single',
            'is_active' => true,
        ]);
        User::factory()->count(3)->create();

        $this->assertSame(3, $resident->id);
        $this->seed(ResidentThreeCitizenServicesSeeder::class);

        $this->assertDatabaseCount('citizen_service_requests', 4);
        $this->assertDatabaseCount('complaints', 4);
        $this->assertDatabaseMissing('citizen_service_requests', ['resident_id' => null]);
        $this->assertDatabaseMissing('complaints', ['submitted_by_user_id' => null]);
        $this->assertSame([$resident->id], \App\Models\CitizenServiceRequest::query()->distinct()->pluck('resident_id')->all());
        $complaintUserIds = \App\Models\Complaint::query()->distinct()->pluck('submitted_by_user_id');
        $this->assertCount(1, $complaintUserIds);
        $this->assertDatabaseHas('users', [
            'id' => $complaintUserIds->first(),
            'resident_id' => 3,
        ]);
        $this->assertNotSame(3, $complaintUserIds->first());
    }
}
