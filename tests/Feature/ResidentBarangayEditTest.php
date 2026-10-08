<?php

namespace Tests\Feature;

use App\Livewire\AddressSelector;
use App\Livewire\ResidentRegistration;
use App\Models\Household;
use App\Models\Resident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class ResidentBarangayEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            'refregion' => ['regCode', 'regDesc'],
            'refprovince' => ['provCode', 'provDesc', 'regCode'],
            'refcitymun' => ['citymunCode', 'citymunDesc', 'provCode'],
            'refbrgy' => ['brgyCode', 'brgyDesc', 'citymunCode'],
        ] as $name => $columns) {
            Schema::create($name, function ($table) use ($columns) {
                $table->id();
                foreach ($columns as $column) {
                    $table->string($column)->nullable();
                }
            });
        }
        DB::table('refregion')->insert([
            ['regCode' => '01', 'regDesc' => 'Region I'],
            ['regCode' => '02', 'regDesc' => 'Region II'],
        ]);
        DB::table('refprovince')->insert([
            ['provCode' => '0155', 'provDesc' => 'Pangasinan', 'regCode' => '01'],
            ['provCode' => '0231', 'provDesc' => 'Isabela', 'regCode' => '02'],
        ]);
        DB::table('refcitymun')->insert([
            ['citymunCode' => '015503', 'citymunDesc' => 'Alaminos City', 'provCode' => '0155'],
            ['citymunCode' => '023101', 'citymunDesc' => 'Alicia', 'provCode' => '0231'],
        ]);
        DB::table('refbrgy')->insert([
            ['brgyCode' => '015503001', 'brgyDesc' => 'Poblacion', 'citymunCode' => '015503'],
            ['brgyCode' => '015503002', 'brgyDesc' => 'San Roque', 'citymunCode' => '015503'],
            ['brgyCode' => '023101001', 'brgyDesc' => 'Poblacion', 'citymunCode' => '023101'],
        ]);
    }

    public function test_edit_preserves_saved_location_and_resolves_name_without_guessing_another_city(): void
    {
        $resident = $this->resident(['barangay' => ' POBLACION ', 'barangay_code' => null]);
        Livewire::test(ResidentRegistration::class, ['residentId' => $resident->id])
            ->assertSet('regionCode', '01')
            ->assertSet('provinceCode', '0155')
            ->assertSet('cityMunicipalityCode', '015503')
            ->assertSet('barangayCode', '015503001')
            ->set('firstName', 'Updated')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('residents.show', $resident->id));
        $this->assertSame('Updated', $resident->fresh()->first_name);
        $this->assertSame(' POBLACION ', $resident->fresh()->household->barangay);
        $this->assertSame('015503001', $resident->fresh()->household->barangay_code);
        $this->assertSame('Alaminos City', $resident->fresh()->household->city_municipality);
    }

    public function test_unmatched_legacy_name_is_displayed_and_preserved_on_save(): void
    {
        $resident = $this->resident(['barangay' => 'Legacy Barangay', 'barangay_code' => 'invalid']);
        Livewire::test(ResidentRegistration::class, ['residentId' => $resident->id])
            ->assertSet('barangayCode', null)
            ->assertSee('Saved barangay: Legacy Barangay')
            ->set('firstName', 'Updated')
            ->call('save')->assertHasNoErrors()
            ->assertRedirect(route('residents.show', $resident->id));
        $this->assertSame('Legacy Barangay', $resident->fresh()->household->barangay);
    }

    public function test_invalid_code_is_resolved_from_unique_name_within_saved_city(): void
    {
        $resident = $this->resident(['barangay_code' => 'invalid']);
        Livewire::test(ResidentRegistration::class, ['residentId' => $resident->id])
            ->assertSet('barangay', 'Poblacion')->assertSet('barangayCode', '015503001');
    }

    public function test_name_only_address_without_location_codes_survives_an_unrelated_edit(): void
    {
        $resident = $this->resident([
            'region_code' => null, 'province_code' => null,
            'city_municipality_code' => null, 'barangay_code' => null,
        ]);
        Livewire::test(ResidentRegistration::class, ['residentId' => $resident->id])
            ->assertSee('Saved barangay: Poblacion')
            ->set('firstName', 'Updated')->call('save')->assertHasNoErrors()
            ->assertRedirect(route('residents.show', $resident->id));
        $this->assertSame('Poblacion', $resident->fresh()->household->barangay);
        $this->assertSame('Alaminos City', $resident->fresh()->household->city_municipality);
        $this->assertNull($resident->fresh()->household->barangay_code);
    }

    public function test_ambiguous_names_are_not_automatically_matched(): void
    {
        DB::table('refbrgy')->insert(['brgyCode' => '015503099', 'brgyDesc' => 'Poblacion', 'citymunCode' => '015503']);
        $resident = $this->resident(['barangay_code' => null]);
        Livewire::test(ResidentRegistration::class, ['residentId' => $resident->id])
            ->assertSet('barangay', 'Poblacion')->assertSet('barangayCode', null);
    }

    public function test_blank_barangay_event_cannot_erase_saved_barangay(): void
    {
        $resident = $this->resident();
        Livewire::test(ResidentRegistration::class, ['residentId' => $resident->id])
            ->dispatch('address-updated', $this->address(null, ''))
            ->call('save')->assertHasErrors(['barangay' => 'required']);
        $this->assertSame('Poblacion', $resident->fresh()->household->barangay);
        $this->assertSame('015503001', $resident->fresh()->household->barangay_code);
    }

    public function test_explicit_barangay_change_is_saved(): void
    {
        $resident = $this->resident();
        Livewire::test(ResidentRegistration::class, ['residentId' => $resident->id])
            ->assertSet('barangayCode', '015503001')
            ->dispatch('address-updated', $this->address('015503002', 'San Roque'))
            ->call('save')->assertHasNoErrors()
            ->assertRedirect(route('residents.show', $resident->id));
        $this->assertSame('San Roque', $resident->fresh()->household->barangay);
        $this->assertSame('015503002', $resident->fresh()->household->barangay_code);
    }

    public function test_selector_suppresses_only_initial_edit_event_and_still_emits_user_changes(): void
    {
        Livewire::test(AddressSelector::class, [
            'initialRegionCode' => '01', 'initialProvinceCode' => '0155',
            'initialCityCode' => '015503', 'dispatchInitialAddress' => false,
        ])->assertNotDispatched('address-updated')
            ->set('selectedBarangay', '015503002')
            ->assertDispatched('address-updated', fn ($event, $params) => $params[0]['barangay']['name'] === 'San Roque');
        Livewire::test(AddressSelector::class)->assertDispatched('address-updated');
    }

    private function address(?string $code, string $name): array
    {
        return [
            'region' => ['code' => '01', 'name' => 'Region I'],
            'province' => ['code' => '0155', 'name' => 'Pangasinan'],
            'city' => ['code' => '015503', 'name' => 'Alaminos City'],
            'barangay' => ['code' => $code, 'name' => $name],
        ];
    }

    private function resident(array $address = []): Resident
    {
        $household = Household::create(array_merge([
            'household_id' => 'HH-TEST', 'address' => 'Test Street',
            'barangay' => 'Poblacion', 'barangay_code' => '015503001',
            'city_municipality' => 'Alaminos City', 'city_municipality_code' => '015503',
            'province' => 'Pangasinan', 'province_code' => '0155',
            'region' => 'Region I', 'region_code' => '01', 'qr_code' => 'existing-household-qr',
        ], $address));
        return Resident::create([
            'resident_id' => '26-00001', 'first_name' => 'Sample', 'last_name' => 'Resident',
            'birth_date' => '2000-01-01', 'gender' => 'female', 'civil_status' => 'single',
            'household_id' => $household->id, 'qr_code' => 'existing-resident-qr',
        ]);
    }
}
