<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SplitHouseholdsPerResidentCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (collect(Schema::getIndexes('residents'))->contains('name', 'residents_household_id_unique')) {
            Schema::table('residents', function (Blueprint $table) {
                $table->dropUnique('residents_household_id_unique');
            });
        }
    }

    public function test_dry_run_reports_changes_without_writing_anything(): void
    {
        $household = $this->household('HH-SHARED-001');
        $first = $this->resident('RES-001', $household->id);
        $second = $this->resident('RES-002', $household->id);

        $this->artisan('residents:split-households', ['--dry-run' => true])
            ->expectsOutputToContain('Household conversion dry run complete.')
            ->assertSuccessful();

        $this->assertSame($household->id, $first->fresh()->household_id);
        $this->assertSame($household->id, $second->fresh()->household_id);
        $this->assertDatabaseCount('households', 1);
        $this->assertDatabaseCount('household_resident_split_audits', 0);
    }

    public function test_execution_splits_shared_households_copies_address_and_archives_the_original(): void
    {
        $household = $this->household('HH-SHARED-001');
        $first = $this->resident('RES-001', $household->id, 1250);
        $second = $this->resident('RES-002', $household->id, 2500);

        $this->artisan('residents:split-households')->assertSuccessful();

        foreach ([$first->fresh(), $second->fresh()] as $resident) {
            $newHousehold = Household::withTrashed()->findOrFail($resident->household_id);
            $this->assertNotSame($household->id, $newHousehold->id);
            $this->assertSame('head', $resident->relationship_to_head);
            $this->assertSame('42 Rizal Street', $newHousehold->address);
            $this->assertSame('Poblacion', $newHousehold->barangay);
            $this->assertSame(1, $newHousehold->member_count);
            $this->assertSame($resident->monthly_income, $newHousehold->monthly_income);
        }

        $archived = Household::withTrashed()->findOrFail($household->id);
        $this->assertNotNull($archived->deleted_at);
        $this->assertFalse($archived->is_active);
        $this->assertStringContainsString('Archived by residents:split-households', $archived->notes);
        $this->assertDatabaseCount('household_resident_split_audits', 2);
    }

    public function test_it_assigns_an_orphan_to_a_dedicated_household(): void
    {
        $resident = $this->resident('RES-ORPHAN', null, 800);

        $this->artisan('residents:split-households')->assertSuccessful();

        $resident->refresh();
        $household = Household::findOrFail($resident->household_id);
        $this->assertSame('head', $resident->relationship_to_head);
        $this->assertSame('Unknown', $household->barangay);
        $this->assertSame(1, $household->member_count);
        $this->assertDatabaseHas('household_resident_split_audits', [
            'resident_id' => $resident->id,
            'operation' => 'orphan',
            'original_household_id' => null,
            'new_household_id' => $household->id,
        ]);
    }

    public function test_it_reassigns_distributions_to_each_residents_new_household(): void
    {
        $household = $this->household('HH-SHARED-001');
        $resident = $this->resident('RES-001', $household->id);
        $this->resident('RES-002', $household->id);
        $distributionId = $this->distribution($resident->id, $household->id);

        $this->artisan('residents:split-households')->assertSuccessful();

        $this->assertDatabaseHas('distributions', [
            'id' => $distributionId,
            'resident_id' => $resident->id,
            'household_id' => $resident->fresh()->household_id,
        ]);
        $mapping = DB::table('household_resident_split_audits')->where('resident_id', $resident->id)->first();
        $this->assertSame(
            $household->id,
            json_decode($mapping->distribution_household_mappings, true)[$distributionId]
        );
    }

    public function test_rerunning_the_command_is_idempotent(): void
    {
        $household = $this->household('HH-SHARED-001');
        $this->resident('RES-001', $household->id);
        $this->resident('RES-002', $household->id);

        $this->artisan('residents:split-households')->assertSuccessful();
        $residentAssignments = DB::table('residents')->orderBy('id')->pluck('household_id', 'id')->all();
        $householdCount = DB::table('households')->count();
        $auditCount = DB::table('household_resident_split_audits')->count();

        $this->artisan('residents:split-households')->assertSuccessful();

        $this->assertSame($residentAssignments, DB::table('residents')->orderBy('id')->pluck('household_id', 'id')->all());
        $this->assertSame($householdCount, DB::table('households')->count());
        $this->assertSame($auditCount, DB::table('household_resident_split_audits')->count());
    }

    private function household(string $householdId): Household
    {
        return Household::create([
            'household_id' => $householdId,
            'address' => '42 Rizal Street',
            'barangay' => 'Poblacion',
            'city_municipality' => 'Alaminos City',
            'province' => 'Pangasinan',
            'postal_code' => '2404',
            'region' => 'Ilocos Region',
            'is_active' => true,
        ]);
    }

    private function resident(string $residentId, ?int $householdId, float $income = 1000): Resident
    {
        return Resident::create([
            'resident_id' => $residentId,
            'household_id' => $householdId,
            'first_name' => 'Test',
            'last_name' => $residentId,
            'birth_date' => '1990-01-01',
            'gender' => 'female',
            'civil_status' => 'single',
            'relationship_to_head' => 'child',
            'monthly_income' => $income,
            'is_active' => true,
        ]);
    }

    private function distribution(int $residentId, int $householdId): int
    {
        $now = now();
        $programId = DB::table('ayuda_programs')->insertGetId([
            'name' => 'Test Assistance',
            'code' => 'TEST-ASSISTANCE',
            'start_date' => $now->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return DB::table('distributions')->insertGetId([
            'reference_number' => 'DIST-001',
            'ayuda_program_id' => $programId,
            'resident_id' => $residentId,
            'household_id' => $householdId,
            'distribution_date' => $now->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
