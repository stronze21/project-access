<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Household;
use App\Models\Resident;
use App\Models\ResidentIdPrintBatch;
use App\Models\User;
use App\Services\ResidentPinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ResidentPinAndIdBatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_generated_pin_uses_current_year_and_global_sequence(): void
    {
        $this->resident('25-00124');
        $this->resident('LEGACY-PIN');

        $pin = Resident::generateResidentId();

        $this->assertSame(now()->format('y').'-00125', $pin);
    }

    public function test_pin_service_normalizes_manual_pin_and_rejects_duplicates(): void
    {
        $service = app(ResidentPinService::class);
        $first = $service->create($this->residentAttributes(), ' 26-00001 ');

        $this->assertSame('26-00001', $first->resident_id);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->create($this->residentAttributes(['first_name' => 'Second']), '26-00001');
    }

    public function test_authorized_api_pin_change_is_confirmed_audited_and_regenerates_qr(): void
    {
        $resident = $this->resident('26-00001', ['photo_path' => 'resident-photos/original.jpg', 'qr_code' => 'AC-26-00001']);
        $user = $this->staff(['edit-residents', 'manage-resident-pins']);
        Sanctum::actingAs($user);

        $this->putJson("/api/residents/{$resident->id}", [
            'resident_id' => '26-00002',
            'resident_id_confirmation' => '26-00002',
        ])->assertOk()->assertJsonPath('data.resident_id', '26-00002');

        $resident->refresh();
        $this->assertSame('AC-26-00002', $resident->qr_code);
        $this->assertSame('resident-photos/original.jpg', $resident->photo_path);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'resident_pin_changed',
            'loggable_id' => $resident->id,
        ]);
        $this->assertSame(['resident_id' => '26-00001'], ActivityLog::latest('id')->first()->old_values);
    }

    public function test_general_resident_editor_cannot_change_pin_through_api(): void
    {
        $resident = $this->resident('26-00001');
        Sanctum::actingAs($this->staff(['edit-residents']));

        $this->putJson("/api/residents/{$resident->id}", [
            'resident_id' => '26-00002',
            'resident_id_confirmation' => '26-00002',
        ])->assertForbidden();

        $this->assertSame('26-00001', $resident->fresh()->resident_id);
    }

    public function test_id_card_api_rejects_more_than_one_hundred_or_duplicate_pins(): void
    {
        $user = $this->staff(['view-residents']);
        Sanctum::actingAs($user);

        $tooMany = collect(range(1, 101))->map(fn (int $number) => '26-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT))->all();
        $this->postJson('/api/residents/id-card/batch', ['resident_ids' => $tooMany])
            ->assertUnprocessable();

        $this->postJson('/api/residents/id-card/batch', ['resident_ids' => ['26-00001', '26-00001']])
            ->assertUnprocessable();
    }

    public function test_id_card_api_accepts_exactly_one_hundred_existing_unique_pins(): void
    {
        $pins = [];
        foreach (range(1, 100) as $number) {
            $pin = '26-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT);
            $this->resident($pin);
            $pins[] = $pin;
        }
        Sanctum::actingAs($this->staff(['view-residents']));

        $this->postJson('/api/residents/id-card/batch', ['resident_ids' => $pins])
            ->assertOk()
            ->assertJsonPath('count', 100);
    }

    public function test_web_print_batch_rejects_more_than_one_hundred_residents(): void
    {
        $user = $this->staff(['view-residents']);
        $ids = [];
        foreach (range(1, 101) as $number) {
            $ids[] = $this->resident('26-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT))->id;
        }

        $this->actingAs($user)
            ->from(route('residents.id-cards.form'))
            ->post(route('residents.id-cards.batch'), ['residents' => $ids])
            ->assertRedirect(route('residents.id-cards.form'))
            ->assertSessionHasErrors('residents');
    }

    public function test_id_card_resident_list_applies_barangay_and_status_filters(): void
    {
        $targetHousehold = $this->household('Poblacion');
        $otherHousehold = $this->household('San Roque');
        $included = $this->resident('26-00001', ['household_id' => $targetHousehold->id, 'is_active' => true]);
        $this->resident('26-00002', ['household_id' => $targetHousehold->id, 'is_active' => false]);
        $this->resident('26-00003', ['household_id' => $otherHousehold->id, 'is_active' => true]);
        Sanctum::actingAs($this->staff(['view-residents']));

        $this->getJson('/api/residents?barangay=Poblacion&status=active&perPage=100&sortField=last_name&sortDirection=asc')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $included->id);
    }

    public function test_barangay_printing_is_split_into_server_side_groups_of_one_hundred(): void
    {
        $household = $this->household('San Antonio');
        foreach (range(1, 101) as $number) {
            $this->resident('26-'.str_pad((string) $number, 5, '0', STR_PAD_LEFT), [
                'household_id' => $household->id,
                'first_name' => 'Resident '.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            ]);
        }
        $user = $this->staff(['view-residents']);

        $firstBatch = $this->actingAs($user)->post(route('residents.id-cards.batch'), [
            'barangay' => 'San Antonio',
            'status' => 'active',
            'batch_number' => 1,
        ])->assertRedirect();
        $this->actingAs($user)->get($firstBatch->headers->get('Location'))->assertOk()
            ->assertViewHas('residents', fn ($residents) => $residents->count() === 100)
            ->assertViewHas('hasNextBatch', true);

        $secondBatch = $this->actingAs($user)->post(route('residents.id-cards.batch'), [
            'barangay' => 'San Antonio',
            'status' => 'active',
            'batch_number' => 2,
        ])->assertRedirect();
        $this->actingAs($user)->get($secondBatch->headers->get('Location'))->assertOk()
            ->assertViewHas('residents', fn ($residents) => $residents->count() === 1)
            ->assertViewHas('hasNextBatch', false);
    }

    public function test_all_barangays_can_be_printed_in_server_side_batches(): void
    {
        $firstHousehold = $this->household('San Antonio');
        $secondHousehold = $this->household('Poblacion');
        $this->resident('26-00001', ['household_id' => $firstHousehold->id]);
        $this->resident('26-00002', ['household_id' => $secondHousehold->id]);

        $user = $this->staff(['view-residents']);
        $response = $this->actingAs($user)
            ->post(route('residents.id-cards.batch'), [
                'barangay' => 'all',
                'status' => 'active',
                'batch_number' => 1,
            ])->assertRedirect();
        $this->actingAs($user)->get($response->headers->get('Location'))->assertOk()
            ->assertViewHas('residents', fn ($residents) => $residents->count() === 2)
            ->assertViewHas('barangay', 'all');
    }

    public function test_generated_batch_tracks_resident_ids_and_print_initiation(): void
    {
        $household = $this->household('Poblacion');
        $resident = $this->resident('26-00001', ['household_id' => $household->id]);
        $user = $this->staff(['view-residents']);

        $response = $this->actingAs($user)->post(route('residents.id-cards.batch'), [
            'barangay' => 'Poblacion',
            'status' => 'active',
            'batch_number' => 1,
        ])->assertRedirect();

        /** @var ResidentIdPrintBatch $batch */
        $batch = ResidentIdPrintBatch::latest('id')->firstOrFail();
        $this->assertSame('generated', $batch->status);
        $this->assertDatabaseHas('resident_id_print_batch_items', [
            'print_batch_id' => $batch->id,
            'resident_id' => $resident->id,
            'resident_pin' => '26-00001',
        ]);

        $this->actingAs($user)
            ->postJson(route('residents.id-cards.batches.printed', $batch))
            ->assertOk()
            ->assertJsonPath('reference_number', $batch->reference_number);

        $this->assertSame('print_initiated', $batch->fresh()->status);
        $this->assertNotNull($batch->fresh()->printed_at);
        $this->assertNotNull($batch->items()->firstOrFail()->printed_at);

        $this->actingAs($user)
            ->post(route('residents.id-cards.batches.printed', $batch))
            ->assertRedirect(route('residents.id-cards.batches.print', ['printBatch' => $batch, 'print' => 1]));

        $this->actingAs($user)
            ->get(route('residents.id-cards.batches.show', $batch))
            ->assertOk()
            ->assertSee('26-00001')
            ->assertSee('Santos, Maria');
    }

    private function staff(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function resident(string $pin, array $overrides = []): Resident
    {
        return Resident::create($this->residentAttributes($overrides) + ['resident_id' => $pin]);
    }

    private function residentAttributes(array $overrides = []): array
    {
        return $overrides + [
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '1990-01-01',
            'gender' => 'female',
            'civil_status' => 'single',
            'is_active' => true,
        ];
    }

    private function household(string $barangay): Household
    {
        return Household::create([
            'household_id' => 'HH-'.str()->upper(str()->random(8)),
            'address' => 'Sample Street',
            'barangay' => $barangay,
            'city_municipality' => 'Alaminos City',
            'province' => 'Pangasinan',
            'region' => 'Region I',
        ]);
    }
}
