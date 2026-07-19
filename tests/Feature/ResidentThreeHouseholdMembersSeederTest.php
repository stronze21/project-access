<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use Database\Seeders\ResidentThreeHouseholdMembersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResidentThreeHouseholdMembersSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_five_residents_to_resident_threes_household(): void
    {
        $otherHousehold = $this->household('HH-SEED-OTHER');
        $targetHousehold = $this->household('HH-SEED-TARGET');

        $this->resident('R-SEED-1', $otherHousehold, 'First');
        $this->resident('R-SEED-2', $otherHousehold, 'Second');
        $residentThree = $this->resident('R-SEED-3', $targetHousehold, 'Target');

        $this->assertSame(3, $residentThree->id);

        $this->seed(ResidentThreeHouseholdMembersSeeder::class);
        $this->seed(ResidentThreeHouseholdMembersSeeder::class);

        $this->assertSame(6, Resident::query()->where('household_id', $targetHousehold->id)->count());
        $this->assertSame(2, Resident::query()->where('household_id', $otherHousehold->id)->count());
        $this->assertSame(6, $targetHousehold->fresh()->member_count);
        $this->assertSame(33000.0, (float) $targetHousehold->fresh()->monthly_income);
        $this->assertSame(
            5,
            Resident::query()->where('resident_id', 'like', 'R-RESIDENT3-MEMBER-%')->count()
        );
    }

    private function household(string $identifier): Household
    {
        return Household::query()->create([
            'household_id' => $identifier,
            'address' => 'Demo Address',
            'barangay' => 'Poblacion',
            'city_municipality' => 'Alaminos City',
            'province' => 'Pangasinan',
            'is_active' => true,
        ]);
    }

    private function resident(string $identifier, Household $household, string $firstName): Resident
    {
        return Resident::query()->create([
            'resident_id' => $identifier,
            'household_id' => $household->id,
            'first_name' => $firstName,
            'last_name' => 'Demo',
            'birth_date' => '1990-01-01',
            'gender' => 'male',
            'civil_status' => 'single',
            'monthly_income' => 0,
            'is_active' => true,
        ]);
    }
}
