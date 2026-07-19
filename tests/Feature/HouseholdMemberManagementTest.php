<?php

namespace Tests\Feature;

use App\Livewire\HouseholdShow;
use App\Models\Household;
use App\Models\Resident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HouseholdMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_searches_unassigned_residents_and_adds_one_to_the_household(): void
    {
        $household = $this->createHousehold('HH-TEST-001');
        $resident = $this->createResident('RES-TEST-001', 'Maria', 'Cruz');

        Livewire::test(HouseholdShow::class, ['householdId' => $household->id])
            ->call('openAddMemberModal')
            ->set('memberSearch', 'Maria Cruz')
            ->assertSee('RES-TEST-001')
            ->set('selectedMemberId', $resident->id)
            ->set('memberRelationship', 'child')
            ->call('addMember')
            ->assertHasNoErrors()
            ->assertSet('showAddMemberModal', false);

        $this->assertDatabaseHas('residents', [
            'id' => $resident->id,
            'household_id' => $household->id,
            'relationship_to_head' => 'child',
        ]);
        $this->assertSame(1, $household->fresh()->member_count);
    }

    public function test_search_does_not_offer_residents_from_another_household(): void
    {
        $household = $this->createHousehold('HH-TEST-001');
        $otherHousehold = $this->createHousehold('HH-TEST-002');
        $this->createResident('RES-TEST-002', 'Already', 'Assigned', $otherHousehold->id);

        Livewire::test(HouseholdShow::class, ['householdId' => $household->id])
            ->call('openAddMemberModal')
            ->set('memberSearch', 'Already Assigned')
            ->assertDontSee('RES-TEST-002');
    }

    private function createHousehold(string $householdId): Household
    {
        return Household::create([
            'household_id' => $householdId,
            'address' => 'Test Street',
            'barangay' => 'Poblacion',
            'city_municipality' => 'Alaminos City',
            'province' => 'Pangasinan',
            'region' => 'Ilocos Region',
            'is_active' => true,
        ]);
    }

    private function createResident(
        string $residentId,
        string $firstName,
        string $lastName,
        ?int $householdId = null,
    ): Resident {
        return Resident::create([
            'resident_id' => $residentId,
            'household_id' => $householdId,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'birth_date' => '1990-01-01',
            'gender' => 'female',
            'civil_status' => 'single',
            'is_active' => true,
            'monthly_income' => 1000,
        ]);
    }
}
