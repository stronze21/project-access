<?php

namespace Tests\Feature;

use App\Livewire\LegacyBhwManager;
use App\Livewire\LegacyReferenceDataManager;
use App\Livewire\LegacyResidentImport;
use App\Livewire\ResidentRegistration;
use App\Models\BarangayHealthWorkerAssignment;
use App\Models\BarangayZone;
use App\Models\Household;
use App\Models\LegacyImportBatch;
use App\Models\Resident;
use App\Models\SourceIncomeType;
use App\Models\User;
use App\Services\Legacy\LegacyCsvImporter;
use App\Services\Legacy\LegacyDataPromoter;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class LegacyCsvIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private string $fixtureDirectory;

    private string|false $previousDbConnection;

    private string|false $previousDbDatabase;

    protected function setUp(): void
    {
        $this->previousDbConnection = getenv('DB_CONNECTION');
        $this->previousDbDatabase = getenv('DB_DATABASE');
        $this->setDatabaseEnvironment('sqlite', ':memory:');

        parent::setUp();

        $this->fixtureDirectory = storage_path('framework/testing/legacy-'.uniqid());
        File::ensureDirectoryExists($this->fixtureDirectory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->fixtureDirectory);
        parent::tearDown();

        $this->restoreDatabaseEnvironment();
    }

    public function test_dry_run_validates_without_writing_and_committed_import_is_idempotent(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());

        $personal = $this->writeCsv('personal.csv', $this->personalHeaders(), [
            $this->personalRow('00-00001'),
            $this->personalRow('00-00001'),
            array_fill(0, 21, ''),
        ]);

        $importer = app(LegacyCsvImporter::class);
        $dryRun = $importer->import([$personal], true);

        $this->assertTrue($dryRun['dry_run']);
        $this->assertSame(3, $dryRun['stats']['personal_info']['rows']);
        $this->assertSame(1, $dryRun['stats']['personal_info']['duplicate_natural_keys']);
        $this->assertDatabaseCount('legacy_import_batches', 0);
        $this->assertDatabaseCount('legacy_import_rows', 0);

        $first = $importer->import([$personal], false);
        $second = $importer->import([$personal], false);

        $this->assertFalse($first['already_imported']);
        $this->assertTrue($second['already_imported']);
        $this->assertSame($first['batch_id'], $second['batch_id']);
        $this->assertDatabaseCount('legacy_import_batches', 1);
        $this->assertDatabaseCount('legacy_import_rows', 3);
        $this->assertDatabaseCount('legacy_import_rows', 3);
        $this->assertSame(
            2,
            DB::table('legacy_import_rows')->where('validation_status', 'conflict')->count()
        );
        $this->assertSame(
            1,
            DB::table('legacy_import_rows')->where('validation_status', 'invalid')->count()
        );
    }

    public function test_guarded_promotion_maps_identifiers_timestamp_household_and_bhw_assignment(): void
    {
        $files = [
            $this->writeCsv('personal.csv', $this->personalHeaders(), [
                $this->personalRow('00-00002'),
            ]),
            $this->writeCsv('barangay.csv', ['Barangay_Code', 'Barangay'], [
                ['01', 'Poblacion'],
            ]),
            $this->writeCsv('family.csv', [
                'FamilyNumber', 'Building_RegistryNumber', 'PIN', 'Date_Update', 'Userid',
                'HouseHoldHead', 'FamilyHead', 'DateUpdate_Head', 'Relationship_id',
                'Disability_id', 'TypeSchool_id', 'SourceIncome_id', 'Educational_ID',
                'Year_Survey', 'Month_Survey',
            ], [[
                '01-01-00001', '1001', '00-00002', '2026-07-01', '1', '1', '',
                '2026-07-01', '1', '0', '1', '10', '9', '2026', '7',
            ]]),
            $this->writeCsv('bhw.csv', ['ZoneID', 'PIN', 'Barangay_Code', 'ZoneName', 'PIN2'], [
                ['01', '00-00002', '01', 'Centro', 'NULL'],
            ]),
        ];

        $batchResult = app(LegacyCsvImporter::class)->import($files, false);
        $batch = LegacyImportBatch::findOrFail($batchResult['batch_id']);

        $dryRun = app(LegacyDataPromoter::class)->promote($batch, false);
        $this->assertSame(1, $dryRun['residents']['created']);
        $this->assertDatabaseCount('residents', 0);
        $this->assertDatabaseCount('households', 0);

        $result = app(LegacyDataPromoter::class)->promote($batch, true);

        $this->assertSame(1, $result['residents']['created']);
        $this->assertSame(1, $result['households']['created']);
        $this->assertSame(1, $result['households']['members_linked']);
        $this->assertSame(1, $result['bhw']['zones_created']);
        $this->assertSame(1, $result['bhw']['assignments_created']);

        $resident = Resident::where('resident_id', '00-00002')->firstOrFail();
        $this->assertSame('Juan', $resident->first_name);
        $this->assertSame('Dela Cruz', $resident->last_name);
        $this->assertSame('2026-07-01 10:30:00', $resident->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame('College Graduate', $resident->educational_attainment);
        $this->assertNotNull($resident->source_income_type_id);
        $this->assertNotNull($resident->household_id);
        $this->assertTrue($resident->is_bhw);
        $this->assertTrue($resident->is_legacy_imported);

        $this->assertDatabaseHas('households', [
            'household_id' => '01-01-00001',
            'building_registry_number' => '1001',
            'barangay' => 'Poblacion',
        ]);
        $this->assertDatabaseHas('legacy_resident_links', [
            'legacy_pin' => '00-00002',
            'resident_id' => $resident->id,
            'status' => 'linked',
        ]);
        $this->assertDatabaseHas('barangay_health_worker_assignments', [
            'legacy_pin' => '00-00002',
            'resident_id' => $resident->id,
            'assignment_slot' => 'primary',
        ]);
    }

    public function test_bhw_import_enforces_two_zone_slots_and_synchronizes_resident_flags(): void
    {
        $importer = app(LegacyCsvImporter::class);
        $promoter = app(LegacyDataPromoter::class);
        $initialFiles = [
            $this->writeCsv('personal.csv', $this->personalHeaders(), [
                $this->personalRow('00-02001'),
                $this->personalRow('00-02002'),
                $this->personalRow('00-02003'),
            ]),
            $this->writeCsv('barangay.csv', ['Barangay_Code', 'Barangay'], [
                ['01', 'Poblacion'],
            ]),
            $this->writeCsv('bhw.csv', ['ZoneID', 'PIN', 'Barangay_Code', 'ZoneName', 'PIN2'], [
                ['01', '00-02001', '01', 'Centro', '00-02002'],
            ]),
        ];

        $initialBatch = LegacyImportBatch::findOrFail($importer->import($initialFiles, false)['batch_id']);
        $initialResult = $promoter->promote($initialBatch, true);

        $this->assertSame(2, $initialResult['bhw']['assignments_created']);
        $this->assertDatabaseCount('barangay_health_worker_assignments', 2);
        $this->assertTrue(Resident::where('resident_id', '00-02001')->firstOrFail()->is_bhw);
        $this->assertTrue(Resident::where('resident_id', '00-02002')->firstOrFail()->is_bhw);
        $this->assertFalse(Resident::where('resident_id', '00-02003')->firstOrFail()->is_bhw);

        $replacement = $this->writeCsv(
            'bhw-replacement.csv',
            ['ZoneID', 'PIN', 'Barangay_Code', 'ZoneName', 'PIN2'],
            [['01', '00-02003', '01', 'Centro', '']]
        );
        $replacementBatch = LegacyImportBatch::findOrFail($importer->import([$replacement], false)['batch_id']);
        $replacementResult = $promoter->promote($replacementBatch, true);

        $this->assertSame(1, $replacementResult['bhw']['assignments_updated']);
        $this->assertSame(1, $replacementResult['bhw']['assignments_removed']);
        $this->assertSame(2, $replacementResult['bhw']['resident_bhw_unflagged']);
        $this->assertDatabaseCount('barangay_health_worker_assignments', 1);
        $assignment = BarangayHealthWorkerAssignment::firstOrFail();
        $this->assertSame('primary', $assignment->assignment_slot);
        $this->assertSame('00-02003', $assignment->legacy_pin);
        $this->assertFalse(Resident::where('resident_id', '00-02001')->firstOrFail()->is_bhw);
        $this->assertFalse(Resident::where('resident_id', '00-02002')->firstOrFail()->is_bhw);
        $this->assertTrue(Resident::where('resident_id', '00-02003')->firstOrFail()->is_bhw);
    }

    public function test_zone_rejects_more_than_one_assignment_per_bhw_slot(): void
    {
        $zone = BarangayZone::create([
            'source_system' => LegacyCsvImporter::SOURCE_SYSTEM,
            'legacy_barangay_code' => '1',
            'legacy_zone_id' => '01',
            'name' => 'Centro',
        ]);
        BarangayHealthWorkerAssignment::create([
            'barangay_zone_id' => $zone->id,
            'legacy_pin' => '00-03001',
            'assignment_slot' => 'primary',
        ]);
        BarangayHealthWorkerAssignment::create([
            'barangay_zone_id' => $zone->id,
            'legacy_pin' => '00-03002',
            'assignment_slot' => 'secondary',
        ]);

        $this->expectException(QueryException::class);
        BarangayHealthWorkerAssignment::create([
            'barangay_zone_id' => $zone->id,
            'legacy_pin' => '00-03003',
            'assignment_slot' => 'primary',
        ]);
    }

    public function test_existing_populated_resident_is_not_overwritten_by_conflicting_source_values(): void
    {
        DB::table('residents')->insert([
            'resident_id' => '00-00003',
            'first_name' => 'Existing',
            'last_name' => 'Resident',
            'birth_date' => '1990-01-01',
            'gender' => 'female',
            'civil_status' => 'single',
            'is_active' => true,
            'created_at' => '2026-07-10 00:00:00',
            'updated_at' => '2026-07-10 00:00:00',
        ]);

        $personal = $this->writeCsv('personal.csv', $this->personalHeaders(), [
            $this->personalRow('00-00003'),
        ]);
        $batchResult = app(LegacyCsvImporter::class)->import([$personal], false);
        $batch = LegacyImportBatch::findOrFail($batchResult['batch_id']);

        $result = app(LegacyDataPromoter::class)->promote($batch, true);

        $this->assertSame(1, $result['residents']['conflicts']);
        $resident = Resident::where('resident_id', '00-00003')->firstOrFail();
        $this->assertSame('Existing', $resident->first_name);
        $this->assertSame('Resident', $resident->last_name);
        $this->assertSame('1990-01-01', $resident->birth_date->format('Y-m-d'));
        $this->assertSame('2026-07-10 00:00:00', $resident->updated_at->format('Y-m-d H:i:s'));
        $this->assertFalse($resident->is_legacy_imported);
        $this->assertDatabaseHas('legacy_resident_links', [
            'legacy_pin' => '00-00003',
            'resident_id' => $resident->id,
            'status' => 'conflict',
        ]);
    }

    public function test_family_only_batch_reuses_staged_addresses_and_links_members(): void
    {
        $personal = $this->writeCsv('personal.csv', $this->personalHeaders(), [
            $this->personalRow('00-01001', 'House 1, Barangay Poblacion, Alaminos City, Pangasinan'),
            $this->personalRow('00-01002', 'House 2, Barangay Poblacion, Alaminos City, Pangasinan'),
        ]);

        $importer = app(LegacyCsvImporter::class);
        $promoter = app(LegacyDataPromoter::class);
        $personalBatch = LegacyImportBatch::findOrFail($importer->import([$personal], false)['batch_id']);
        $personalResult = $promoter->promote($personalBatch, true);

        $this->assertSame(2, $personalResult['households']['provisional_created']);
        $this->assertSame(2, $personalResult['households']['provisional_members_linked']);
        $this->assertSame(2, Household::where('is_provisional', true)->count());
        $this->assertSame(2, Resident::whereNotNull('household_id')->count());

        $family = $this->writeCsv('family.csv', [
            'FamilyNumber', 'Building_RegistryNumber', 'PIN', 'Date_Update', 'Userid',
            'HouseHoldHead', 'FamilyHead', 'DateUpdate_Head', 'Relationship_id',
            'Disability_id', 'TypeSchool_id', 'SourceIncome_id', 'Educational_ID',
            'Year_Survey', 'Month_Survey',
        ], [
            ['01-01-01001', '501', '00-01001', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['01-01-01001', '501', '00-01002', '', '', '', '', '', '', '', '', '', '', '', ''],
        ]);

        $familyBatch = LegacyImportBatch::findOrFail($importer->import([$family], false)['batch_id']);
        $result = $promoter->promote($familyBatch, true);

        $this->assertSame(1, $result['households']['created']);
        $this->assertSame(2, $result['households']['members_linked']);
        $this->assertSame(1, $result['households']['address_variations']);
        $this->assertSame(2, $result['households']['provisional_archived']);
        $this->assertDatabaseHas('households', [
            'household_id' => '01-01-01001',
            'building_registry_number' => '501',
            'address' => 'House 1, Barangay Poblacion, Alaminos City, Pangasinan',
            'member_count' => 2,
        ]);
        $this->assertSame(2, Resident::whereNotNull('household_id')->count());
        $this->assertSame(0, Household::where('is_provisional', true)->count());
        $this->assertSame(2, Household::withTrashed()->where('is_provisional', true)->count());
    }

    public function test_personal_promotion_preserves_an_existing_non_provisional_household(): void
    {
        $householdId = DB::table('households')->insertGetId([
            'household_id' => 'MANUAL-001',
            'address' => 'Existing household address',
            'barangay' => 'Poblacion',
            'city_municipality' => 'Alaminos City',
            'province' => 'Pangasinan',
            'member_count' => 1,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('residents')->insert([
            'household_id' => $householdId,
            'resident_id' => '00-01003',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'middle_name' => 'Santos',
            'birth_date' => '1990-01-15',
            'gender' => 'male',
            'civil_status' => 'married',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => '2026-07-01 10:30:00',
        ]);

        $personal = $this->writeCsv('personal.csv', $this->personalHeaders(), [
            $this->personalRow('00-01003'),
        ]);
        $batch = LegacyImportBatch::findOrFail(app(LegacyCsvImporter::class)->import([$personal], false)['batch_id']);
        $result = app(LegacyDataPromoter::class)->promote($batch, true);

        $this->assertSame(1, $result['households']['provisional_existing_household_preserved']);
        $this->assertSame(0, $result['households']['provisional_created']);
        $this->assertSame($householdId, Resident::where('resident_id', '00-01003')->value('household_id'));
        $this->assertDatabaseCount('households', 1);
    }

    public function test_authorized_user_can_stage_preview_and_promote_from_legacy_import_page(): void
    {
        $user = $this->userWithPermissions(['view-residents', 'import-residents']);
        $this->actingAs($user)
            ->get(route('residents.legacy-import.index'))
            ->assertOk()
            ->assertSee('Legacy Resident Import')
            ->assertSee('Validate and Stage');

        $csv = $this->csvString($this->personalHeaders(), [$this->personalRow('00-00004')]);
        $component = Livewire::actingAs($user)
            ->test(LegacyResidentImport::class)
            ->set('csvFiles', [UploadedFile::fake()->createWithContent('tblPersonalInfo.csv', $csv)])
            ->call('stage')
            ->assertHasNoErrors();

        $batch = LegacyImportBatch::firstOrFail();
        $component->assertSet('batch', $batch->id);
        $this->assertDatabaseCount('residents', 0);

        $component->call('preview')->assertHasNoErrors();
        $this->assertDatabaseCount('residents', 0);

        $component
            ->set('confirmation', 'PROMOTE-'.$batch->id)
            ->set('confirmSafePromotion', true)
            ->call('promote')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('residents', [
            'resident_id' => '00-00004',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
        ]);
    }

    public function test_legacy_import_page_requires_import_permission(): void
    {
        $user = $this->userWithPermissions(['view-residents']);

        $this->actingAs($user)
            ->get(route('residents.legacy-import.index'))
            ->assertForbidden();
    }

    public function test_reference_management_pages_require_permission_and_support_create_and_update(): void
    {
        $unauthorized = $this->userWithPermissions(['view-residents']);
        $this->actingAs($unauthorized)
            ->get(route('legacy-data.references.index', 'source-income-types'))
            ->assertForbidden();

        $manager = $this->userWithPermissions(['manage-legacy-reference-data']);
        $this->actingAs($manager)
            ->get(route('legacy-data.references.index', 'source-income-types'))
            ->assertOk()
            ->assertSee('data-testid="legacy-data-navbar"', false)
            ->assertSee('data-testid="legacy-data-mobile-navbar"', false);
        foreach (['source-income-types', 'educational-attainments', 'civil-statuses', 'barangays'] as $type) {
            $this->actingAs($manager)
                ->get(route('legacy-data.references.index', $type))
                ->assertOk();
        }

        Livewire::actingAs($manager)
            ->test(LegacyReferenceDataManager::class, ['type' => 'educational-attainments'])
            ->set('form.legacy_code', '99')
            ->set('form.name', 'Technical Vocational')
            ->set('form.is_active', true)
            ->call('save')
            ->assertHasNoErrors();

        $recordId = DB::table('educational_attainments')->where('legacy_code', '99')->value('id');
        Livewire::actingAs($manager)
            ->test(LegacyReferenceDataManager::class, ['type' => 'educational-attainments'])
            ->call('edit', $recordId)
            ->set('form.name', 'Technical-Vocational Graduate')
            ->set('form.is_active', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('educational_attainments', [
            'legacy_code' => '99',
            'name' => 'Technical-Vocational Graduate',
            'is_active' => false,
        ]);

        DB::table('educational_attainments')->insert([
            'legacy_code' => '9',
            'name' => 'Managed College Equivalent',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $personal = $this->writeCsv('managed-reference-personal.csv', $this->personalHeaders(), [
            $this->personalRow('00-05001'),
        ]);
        $batch = LegacyImportBatch::findOrFail(app(LegacyCsvImporter::class)->import([$personal], false)['batch_id']);
        app(LegacyDataPromoter::class)->promote($batch, true);

        $this->assertSame(
            'Managed College Equivalent',
            Resident::where('resident_id', '00-05001')->value('educational_attainment')
        );
    }

    public function test_bhw_management_supports_exactly_two_slots_and_resynchronizes_flags(): void
    {
        $manager = $this->userWithPermissions(['manage-legacy-reference-data']);
        foreach (['00-04001', '00-04002', '00-04003'] as $pin) {
            DB::table('residents')->insert([
                'resident_id' => $pin,
                'first_name' => 'Test',
                'last_name' => $pin,
                'birth_date' => '1990-01-01',
                'gender' => 'male',
                'civil_status' => 'single',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Livewire::actingAs($manager)
            ->test(LegacyBhwManager::class)
            ->set('form.legacy_barangay_code', '01')
            ->set('form.legacy_zone_id', '01')
            ->set('form.name', 'Centro')
            ->set('form.primary_pin', '00-04001')
            ->set('form.secondary_pin', '00-04002')
            ->call('save')
            ->assertHasNoErrors();

        $zone = BarangayZone::firstOrFail();
        $this->assertDatabaseCount('barangay_health_worker_assignments', 2);
        $this->assertTrue(Resident::where('resident_id', '00-04001')->firstOrFail()->is_bhw);
        $this->assertTrue(Resident::where('resident_id', '00-04002')->firstOrFail()->is_bhw);

        Livewire::actingAs($manager)
            ->test(LegacyBhwManager::class)
            ->call('edit', $zone->id)
            ->set('form.primary_pin', '00-04003')
            ->set('form.secondary_pin', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('barangay_health_worker_assignments', 1);
        $this->assertFalse(Resident::where('resident_id', '00-04001')->firstOrFail()->is_bhw);
        $this->assertFalse(Resident::where('resident_id', '00-04002')->firstOrFail()->is_bhw);
        $this->assertTrue(Resident::where('resident_id', '00-04003')->firstOrFail()->is_bhw);
    }

    public function test_resident_scholar_and_legacy_profile_fields_are_cast_and_loaded_for_editing(): void
    {
        $incomeType = SourceIncomeType::create([
            'legacy_code' => 'TEST',
            'name' => 'Scholarship Allowance',
            'is_active' => true,
        ]);
        $resident = Resident::create([
            'resident_id' => '00-06001',
            'first_name' => 'Maria',
            'last_name' => 'Scholar',
            'birth_date' => '2002-01-01',
            'gender' => 'female',
            'civil_status' => 'single',
            'source_income_type_id' => $incomeType->id,
            'educational_attainment' => 'College Undergraduate',
            'ethnicity' => 'Pangasinense',
            'is_scholar' => true,
            'is_active' => true,
        ]);

        $this->assertTrue($resident->fresh()->is_scholar);

        $form = app(ResidentRegistration::class);
        $form->loadResident($resident->id);

        $this->assertTrue($form->isScholar);
        $this->assertTrue($form->isActive);
        $this->assertSame($incomeType->id, $form->sourceIncomeTypeId);
        $this->assertSame('Pangasinense', $form->ethnicity);
        $this->assertSame('College Undergraduate', $form->educationalAttainment);
    }

    private function personalHeaders(): array
    {
        return [
            'PIN', 'Lastname', 'Firstname', 'Middlename', 'Birthdate', 'CivilStatus',
            'Gender', 'Address', 'SpouseName', 'FatherName', 'MotherName', 'LockStatus',
            'Lockdate', 'Date_Modified', 'Userid', 'Disability_id', 'TypeSchool_id',
            'SourceIncome_id', 'Educational_id', 'Age', 'Ethnicity',
        ];
    }

    private function personalRow(string $pin, ?string $address = null): array
    {
        return [
            $pin, 'Dela Cruz', 'Juan', 'Santos', '1990-01-15', '2', 'Male',
            $address ?? 'House 1, Rizal Street, Barangay Poblacion, Alaminos City, Pangasinan',
            'Maria Santos', 'Jose Dela Cruz', 'Ana Santos', '0', '2026-07-01',
            '2026-07-01 10:30:00', '1', '0', '1', '10', '9', '36', 'Pangasinense',
        ];
    }

    private function writeCsv(string $filename, array $headers, array $rows): string
    {
        $path = $this->fixtureDirectory.DIRECTORY_SEPARATOR.$filename;
        $file = fopen($path, 'wb');
        fputcsv($file, $headers);
        foreach ($rows as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        return $path;
    }

    private function csvString(array $headers, array $rows): string
    {
        $file = fopen('php://temp', 'w+');
        fputcsv($file, $headers);
        foreach ($rows as $row) {
            fputcsv($file, $row);
        }
        rewind($file);
        $csv = stream_get_contents($file);
        fclose($file);

        return $csv;
    }

    private function userWithPermissions(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function setDatabaseEnvironment(string $connection, string $database): void
    {
        putenv("DB_CONNECTION={$connection}");
        putenv("DB_DATABASE={$database}");
        $_ENV['DB_CONNECTION'] = $connection;
        $_ENV['DB_DATABASE'] = $database;
        $_SERVER['DB_CONNECTION'] = $connection;
        $_SERVER['DB_DATABASE'] = $database;
    }

    private function restoreDatabaseEnvironment(): void
    {
        foreach ([
            'DB_CONNECTION' => $this->previousDbConnection,
            'DB_DATABASE' => $this->previousDbDatabase,
        ] as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}
