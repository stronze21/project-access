<?php

namespace Tests\Feature;

use App\Models\Resident;
use App\Models\User;
use Database\Seeders\UserThreeCitizenServicesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserThreeCitizenServicesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_seeds_service_requests_and_complaints_for_user_three(): void
    {
        User::factory()->count(2)->create();
        $resident = Resident::query()->create([
            'resident_id' => 'R-SEED-USER-3',
            'first_name' => 'Demo',
            'last_name' => 'Resident',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'civil_status' => 'single',
            'is_active' => true,
        ]);
        $user = User::factory()->create(['resident_id' => $resident->id]);

        $this->assertSame(3, $user->id);

        $this->seed(UserThreeCitizenServicesSeeder::class);

        $this->assertDatabaseCount('citizen_service_requests', 4);
        $this->assertDatabaseCount('complaints', 4);
        $this->assertDatabaseMissing('citizen_service_requests', ['resident_id' => null]);
        $this->assertDatabaseMissing('complaints', ['submitted_by_user_id' => null]);
        $this->assertSame([$resident->id], \App\Models\CitizenServiceRequest::query()->distinct()->pluck('resident_id')->all());
        $this->assertSame([3], \App\Models\Complaint::query()->distinct()->pluck('submitted_by_user_id')->all());
    }
}
