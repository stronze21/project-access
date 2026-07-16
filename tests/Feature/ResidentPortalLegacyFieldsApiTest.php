<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\SourceIncomeType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResidentPortalLegacyFieldsApiTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--force' => true]);
    }

    public function test_profile_and_id_card_apis_include_recent_resident_fields_and_flags(): void
    {
        $resident = $this->createResident();
        Sanctum::actingAs($resident);

        $this->getJson('/api/resident-portal/profile')
            ->assertOk()
            ->assertJsonPath('data.source_income_type.name', 'Employment')
            ->assertJsonPath('data.household.building_registry_number', 'BLDG-2026-001')
            ->assertJsonPath('data.ethnicity', 'Pangasinense')
            ->assertJsonPath('data.educational_attainment', 'College Graduate')
            ->assertJsonPath('data.is_scholar', true)
            ->assertJsonPath('data.is_bhw', true)
            ->assertJsonPath('data.is_legacy_imported', true);

        $this->getJson('/api/resident-portal/profile/id-card')
            ->assertOk()
            ->assertJsonPath('data.source_income_type.name', 'Employment')
            ->assertJsonPath('data.building_registry_number', 'BLDG-2026-001')
            ->assertJsonPath('data.ethnicity', 'Pangasinense')
            ->assertJsonPath('data.is_scholar', true)
            ->assertJsonPath('data.is_bhw', true)
            ->assertJsonPath('data.is_legacy_imported', true);
    }

    public function test_login_api_includes_recent_resident_fields_and_income_source(): void
    {
        $resident = $this->createResident();

        $this->postJson('/api/resident-portal/login', [
            'login' => $resident->resident_id,
            'mpin' => '123456',
            'device_name' => 'phpunit',
        ])
            ->assertOk()
            ->assertJsonPath('resident.source_income_type.name', 'Employment')
            ->assertJsonPath('resident.is_scholar', true)
            ->assertJsonPath('resident.is_bhw', true)
            ->assertJsonPath('resident.is_legacy_imported', true);
    }

    private function createResident(): Resident
    {
        $incomeType = SourceIncomeType::query()->firstOrCreate(
            ['legacy_code' => 'API-EMP'],
            ['name' => 'Employment', 'is_active' => true]
        );
        $household = Household::create([
            'household_id' => 'HH-API-2026-001',
            'building_registry_number' => 'BLDG-2026-001',
            'address' => '123 Quezon Avenue',
            'barangay' => 'Poblacion',
            'city_municipality' => 'Alaminos City',
            'province' => 'Pangasinan',
        ]);

        return Resident::create([
            'household_id' => $household->id,
            'resident_id' => 'R-API-2026-001',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '1995-01-10',
            'gender' => 'female',
            'civil_status' => 'single',
            'mpin' => Hash::make('123456'),
            'occupation' => 'Teacher',
            'source_income_type_id' => $incomeType->id,
            'monthly_income' => 25000,
            'educational_attainment' => 'College Graduate',
            'ethnicity' => 'Pangasinense',
            'is_scholar' => true,
            'is_bhw' => true,
            'is_legacy_imported' => true,
            'is_active' => true,
        ]);
    }
}
