<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Services\Legacy\LegacyCsvImporter;
use App\Services\ResidentCsvService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResidentCsvServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_treats_blank_monthly_income_as_null(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('resident-import.csv', implode("\n", [
            'resident_id,first_name,last_name,birth_date,gender,address,barangay,monthly_income',
            ',Christian Joseph,David,2003-08-09,male,Test Address,Test Barangay,',
        ]));

        $result = app(ResidentCsvService::class)
            ->importFromCsv(Storage::disk('local')->path('resident-import.csv'));

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame([], $result['errors']);
        $this->assertNull(Resident::firstOrFail()->monthly_income);
    }

    public function test_import_never_merges_residents_with_the_same_address(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('same-address.csv', implode("\n", [
            'resident_id,first_name,last_name,birth_date,gender,address,barangay',
            '00-11001,First,Resident,2000-01-01,male,Same House,Same Barangay',
            '00-11002,Second,Resident,2001-01-01,female,Same House,Same Barangay',
        ]));

        $result = app(ResidentCsvService::class)
            ->importFromCsv(Storage::disk('local')->path('same-address.csv'));

        $this->assertSame(2, $result['created']);
        $this->assertDatabaseCount('households', 2);
        $this->assertSame(2, Resident::pluck('household_id')->unique()->count());
        $this->assertTrue(Resident::get()->every(
            fn (Resident $resident) => $resident->household->residents()->count() === 1
        ));
    }

    public function test_preview_labels_new_updates_and_errors_without_writing_data(): void
    {
        Storage::fake('local');

        Resident::create([
            'resident_id' => '00-01003',
            'first_name' => 'Existing',
            'last_name' => 'Resident',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'civil_status' => 'single',
        ]);

        Storage::disk('local')->put('resident-preview.csv', implode("\n", [
            'resident_id,first_name,last_name,birth_date,gender,address,barangay,monthly_income',
            '00-01003,Updated,Resident,2000-01-01,male,Address One,Barangay One,',
            '00-99999,New,Resident,2001-01-01,female,Address Two,Barangay Two,1000',
            ',Invalid,Resident,2002-01-01,invalid,Address Three,Barangay Three,not-a-number',
        ]));

        $preview = app(ResidentCsvService::class)
            ->previewFromCsv(Storage::disk('local')->path('resident-preview.csv'));

        $this->assertSame(3, $preview['total']);
        $this->assertSame(1, $preview['created']);
        $this->assertSame(1, $preview['updated']);
        $this->assertSame(1, $preview['failed']);
        $this->assertSame(['update', 'new', 'error'], array_column($preview['rows'], 'status'));
        $this->assertDatabaseCount('residents', 1);
        $this->assertDatabaseCount('households', 0);
    }

    public function test_import_resolves_managed_barangay_and_removes_location_from_address(): void
    {
        Storage::fake('local');

        $now = now();
        \DB::table('legacy_barangay_mappings')->insert([
            'source_system' => LegacyCsvImporter::SOURCE_SYSTEM,
            'legacy_code' => '16',
            'legacy_name' => 'Linmansangan',
            'brgy_code' => '015503014',
            'status' => 'mapped',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Storage::disk('local')->put('resident-location.csv', implode("\n", [
            'resident_id,first_name,last_name,birth_date,gender,address,barangay,city_municipality,province,region',
            '00-10001,Location,Test,2000-01-01,male,"Purok 2, Linmansangan, Alaminos City, Pangasinan",Unknown,Alaminos City,Pangasinan,Region I',
        ]));

        $path = Storage::disk('local')->path('resident-location.csv');
        $preview = app(ResidentCsvService::class)->previewFromCsv($path);

        $this->assertSame('Linmansangan', $preview['rows'][0]['barangay']);
        $this->assertSame('Purok 2', $preview['rows'][0]['address']);
        $this->assertSame('new', $preview['rows'][0]['status']);
        $this->assertDatabaseCount('residents', 0);

        $result = app(ResidentCsvService::class)->importFromCsv($path);
        $household = Household::firstOrFail();

        $this->assertSame(1, $result['created']);
        $this->assertSame('Linmansangan', $household->barangay);
        $this->assertSame('Purok 2', $household->address);
        $this->assertSame('Alaminos City', $household->city_municipality);
        $this->assertSame('Pangasinan', $household->province);
        $this->assertSame('Region I', $household->region);
    }

    public function test_full_address_does_not_repeat_structured_location_values_from_legacy_address(): void
    {
        $household = Household::create([
            'household_id' => 'HH-LEGACY-0001',
            'address' => 'De Guzman St., Palamis, Alaminos City, Pangasinan',
            'barangay' => 'Palamis',
            'city_municipality' => 'City of Alaminos',
            'province' => 'Pangasinan',
            'postal_code' => '2404',
        ]);

        $this->assertSame(
            'DE GUZMAN ST., PALAMIS, CITY OF ALAMINOS, PANGASINAN, 2404',
            $household->full_address
        );
    }

    public function test_location_only_address_uses_placeholder_and_removes_barangay_abbreviation(): void
    {
        Storage::fake('local');

        $now = now();
        DB::table('legacy_barangay_mappings')->insert([
            'source_system' => LegacyCsvImporter::SOURCE_SYSTEM,
            'legacy_code' => '14',
            'legacy_name' => 'Inerangan',
            'brgy_code' => '015503013',
            'status' => 'mapped',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Storage::disk('local')->put('resident-location-only.csv', implode("\n", [
            'resident_id,first_name,last_name,birth_date,gender,address,barangay,city_municipality,province,region',
            '00-10002,Location,Only,2000-01-01,male,"Brgy. Inerangan, Alaminos City, Pangasinan",Unknown,Alaminos City,Pangasinan,Region I',
        ]));

        $path = Storage::disk('local')->path('resident-location-only.csv');
        $preview = app(ResidentCsvService::class)->previewFromCsv($path);

        $this->assertSame('Inerangan', $preview['rows'][0]['barangay']);
        $this->assertSame(',', $preview['rows'][0]['address']);
        $this->assertSame('new', $preview['rows'][0]['status']);
        $this->assertSame(0, $preview['failed']);

        app(ResidentCsvService::class)->importFromCsv($path);

        $this->assertSame(',', Household::firstOrFail()->address);
    }

    public function test_exported_resident_with_blank_monthly_income_can_be_imported_as_an_update(): void
    {
        Storage::fake('local');

        $household = Household::create([
            'household_id' => 'HH-TEST-0001',
            'address' => 'Existing Address',
            'barangay' => 'Existing Barangay',
            'city_municipality' => 'Alicia',
            'province' => 'Isabela',
        ]);
        $resident = Resident::create([
            'household_id' => $household->id,
            'resident_id' => 'R-TEST-0001',
            'first_name' => 'Original',
            'last_name' => 'Resident',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'civil_status' => 'single',
            'monthly_income' => null,
        ]);

        $csv = app(ResidentCsvService::class)->exportToCsv();
        $csv = str_replace(',Original,', ',Updated,', $csv);
        Storage::disk('local')->put('resident-export.csv', $csv);

        $result = app(ResidentCsvService::class)
            ->importFromCsv(Storage::disk('local')->path('resident-export.csv'));

        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame('Updated', $resident->fresh()->first_name);
        $this->assertNull($resident->fresh()->monthly_income);
    }

    public function test_exported_resident_without_changes_is_classified_and_imported_as_unchanged(): void
    {
        Storage::fake('local');

        $household = Household::create([
            'household_id' => 'HH-UNCHANGED-0001',
            'address' => 'Existing Address',
            'barangay' => 'Existing Barangay',
            'city_municipality' => 'Alicia',
            'province' => 'Isabela',
        ]);
        $resident = Resident::create([
            'household_id' => $household->id,
            'resident_id' => '00-UNCHANGED',
            'first_name' => 'No',
            'last_name' => 'Changes',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'civil_status' => 'single',
            'is_active' => true,
        ]);

        Storage::disk('local')->put('unchanged-export.csv', app(ResidentCsvService::class)->exportToCsv());
        $path = Storage::disk('local')->path('unchanged-export.csv');

        $preview = app(ResidentCsvService::class)->previewFromCsv($path);
        $this->assertSame('unchanged', $preview['rows'][0]['status'], implode(', ', $preview['rows'][0]['changes']));
        $this->assertSame(1, $preview['unchanged']);
        $this->assertSame(0, $preview['updated']);

        $originalUpdatedAt = $resident->updated_at;
        $result = app(ResidentCsvService::class)->importFromCsv($path);

        $this->assertSame(1, $result['unchanged']);
        $this->assertSame(0, $result['updated']);
        $this->assertTrue($resident->fresh()->updated_at->equalTo($originalUpdatedAt));
    }
}
