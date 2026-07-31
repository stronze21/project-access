<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Services\ResidentCsvService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
