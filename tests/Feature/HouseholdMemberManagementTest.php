<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\AyudaProgramController;
use App\Livewire\AyudaDistribution;
use App\Livewire\HouseholdShow;
use App\Models\AyudaProgram;
use App\Models\EligibilityCriteria;
use App\Models\Household;
use App\Models\Resident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Tests\TestCase;

class HouseholdMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_household_page_does_not_offer_member_mutations(): void
    {
        $household = $this->createHousehold('HH-TEST-001');

        Livewire::test(HouseholdShow::class, ['householdId' => $household->id])
            ->assertDontSee('Add Existing Member')
            ->assertDontSee('Create New Resident')
            ->assertDontSee('Add or Reassign Member');

        $this->assertFalse(method_exists(HouseholdShow::class, 'addMember'));
    }

    public function test_household_reassignment_api_is_disabled(): void
    {
        $controller = app(\App\Http\Controllers\Api\ResidentController::class);
        $response = $controller->updateHousehold(request(), '1');

        $this->assertSame(409, $response->getStatusCode());
        $this->assertStringContainsString('Household reassignment is disabled', $response->getContent());
    }

    public function test_household_criteria_use_individual_income_and_size_one(): void
    {
        $resident = new Resident(['monthly_income' => 1500]);
        $resident->setRelation('household', new Household([
            'monthly_income' => 50000,
            'member_count' => 8,
        ]));

        $income = new EligibilityCriteria([
            'criterion_type' => 'household_income',
            'operator' => 'less_than_or_equal',
            'value' => '2000',
            'is_required' => true,
        ]);
        $size = new EligibilityCriteria([
            'criterion_type' => 'household_size',
            'operator' => 'equals',
            'value' => '1',
            'is_required' => true,
        ]);

        $this->assertTrue($income->checkEligibility($resident));
        $this->assertTrue($size->checkEligibility($resident));
    }

    public function test_eligibility_api_uses_individual_income_and_size_one(): void
    {
        $household = $this->createHousehold('HH-TEST-ELIGIBILITY');
        $household->update(['monthly_income' => 50000, 'member_count' => 8]);
        $resident = $this->createResident('RES-TEST-ELIGIBILITY', $household);
        $resident->update(['monthly_income' => 1500]);
        $program = AyudaProgram::create([
            'name' => 'Individual Assistance',
            'type' => 'cash',
            'start_date' => now()->toDateString(),
        ]);
        $program->eligibilityCriteria()->createMany([
            [
                'criterion_name' => 'Income',
                'criterion_type' => 'household_income',
                'operator' => 'less_than_or_equal',
                'value' => '2000',
                'is_required' => true,
            ],
            [
                'criterion_name' => 'Size',
                'criterion_type' => 'household_size',
                'operator' => 'equals',
                'value' => '1',
                'is_required' => true,
            ],
        ]);

        $response = app(AyudaProgramController::class)->checkEligibility(
            Request::create('/', 'POST', ['resident_id' => $resident->id]),
            (string) $program->id,
        );

        $this->assertTrue($response->getData(true)['data']['is_eligible']);
    }

    public function test_sole_resident_resolution_requires_one_active_resident(): void
    {
        $household = $this->createHousehold('HH-TEST-SOLE');
        $this->createResident('RES-TEST-001', $household);

        $this->assertSame('RES-TEST-001', $household->soleResident()?->resident_id);

        $household->residents()->update(['is_active' => false]);

        $this->assertNull($household->soleResident());
    }

    public function test_household_qr_distribution_resolves_only_a_sole_resident(): void
    {
        $household = $this->createHousehold('HH-TEST-QR');
        $resident = $this->createResident('RES-TEST-QR', $household);
        $scan = [
            'found' => true,
            'type' => 'household',
            'object' => ['id' => $household->id],
        ];

        Livewire::test(AyudaDistribution::class)
            ->call('handleScanResult', $scan)
            ->assertSet('selectedResident.id', $resident->id);

        $resident->update(['is_active' => false]);

        Livewire::test(AyudaDistribution::class)
            ->call('handleScanResult', $scan)
            ->assertSet('selectedResident', null)
            ->assertSet('selectedHousehold', null);
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

    private function createResident(string $residentId, Household $household): Resident
    {
        return Resident::create([
            'resident_id' => $residentId,
            'household_id' => $household->id,
            'first_name' => 'Test',
            'last_name' => 'Resident',
            'birth_date' => '1990-01-01',
            'gender' => 'female',
            'civil_status' => 'single',
            'is_active' => true,
            'monthly_income' => 1000,
        ]);
    }
}
