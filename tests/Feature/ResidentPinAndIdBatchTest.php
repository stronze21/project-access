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
            ->postJson(route('residents.id-cards.batches.printed', $batch), ['selected_residents' => [$resident->id]])
            ->assertOk()
            ->assertJsonPath('reference_number', $batch->reference_number);

        $this->assertSame('print_initiated', $batch->fresh()->status);
        $this->assertNotNull($batch->fresh()->printed_at);
        $this->assertNotNull($batch->items()->firstOrFail()->printed_at);

        $this->actingAs($user)
            ->post(route('residents.id-cards.batches.printed', $batch), ['selected_residents' => [$resident->id]])
            ->assertRedirect(route('residents.id-cards.batches.print', ['printBatch' => $batch, 'print' => 1, 'selected_residents' => [$resident->id]]));

        $this->actingAs($user)
            ->get(route('residents.id-cards.batches.show', $batch))
            ->assertOk()
            ->assertSee('26-00001')
            ->assertSee('Santos, Maria');
    }

    public function test_new_residents_append_to_open_batch_but_never_to_printed_batch(): void
    {
        $household = $this->household('Poblacion');
        $user = $this->staff(['view-residents']);
        $first = $this->resident('26-00001', ['household_id' => $household->id, 'last_name' => 'Zulu']);

        $this->actingAs($user)->post(route('residents.id-cards.batch'), [
            'barangay' => 'Poblacion', 'status' => 'active',
        ])->assertRedirect();
        $batchOne = ResidentIdPrintBatch::latest('id')->firstOrFail();

        $second = $this->resident('26-00002', ['household_id' => $household->id, 'last_name' => 'Abad']);
        $this->actingAs($user)->post(route('residents.id-cards.batch'), [
            'barangay' => 'Poblacion', 'status' => 'active',
        ])->assertRedirect(route('residents.id-cards.batches.print', $batchOne));

        $this->assertSame(1, ResidentIdPrintBatch::count());
        $this->assertSame([$first->id, $second->id], $batchOne->items()->orderBy('id')->pluck('resident_id')->all());

        $this->actingAs($user)->postJson(route('residents.id-cards.batches.printed', $batchOne), ['selected_residents' => $batchOne->items()->pluck('resident_id')->all()])->assertOk();
        $third = $this->resident('26-00003', ['household_id' => $household->id]);
        $this->actingAs($user)->post(route('residents.id-cards.batch'), [
            'barangay' => 'Poblacion', 'status' => 'active',
        ])->assertRedirect();

        $batchTwo = ResidentIdPrintBatch::latest('id')->firstOrFail();
        $this->assertNotSame($batchOne->id, $batchTwo->id);
        $this->assertSame(2, $batchTwo->batch_number);
        $this->assertSame([$third->id], $batchTwo->items()->pluck('resident_id')->all());
        $this->assertSame([$first->id, $second->id], $batchOne->items()->orderBy('id')->pluck('resident_id')->all());
    }

    public function test_printed_residents_are_hidden_by_default_but_can_be_explicitly_included_for_reprint(): void
    {
        $household = $this->household('Poblacion');
        $resident = $this->resident('26-00001', ['household_id' => $household->id]);
        $user = $this->staff(['view-residents']);

        $this->actingAs($user)->post(route('residents.id-cards.batch'), [
            'barangay' => 'Poblacion', 'status' => 'active', 'exclude_printed' => 1,
        ])->assertRedirect();
        $originalBatch = ResidentIdPrintBatch::latest('id')->firstOrFail();
        $this->actingAs($user)->postJson(route('residents.id-cards.batches.printed', $originalBatch), ['selected_residents' => $originalBatch->items()->pluck('resident_id')->all()])->assertOk();

        $this->actingAs($user)->from(route('residents.id-cards.form'))->post(route('residents.id-cards.batch'), [
            'barangay' => 'Poblacion', 'status' => 'active', 'exclude_printed' => 1,
        ])->assertRedirect(route('residents.id-cards.form'))
            ->assertSessionHasErrors('barangay');

        $this->actingAs($user)->post(route('residents.id-cards.batch'), [
            'barangay' => 'Poblacion', 'status' => 'active', 'exclude_printed' => 0,
        ])->assertRedirect();

        $reprintBatch = ResidentIdPrintBatch::latest('id')->firstOrFail();
        $this->assertFalse($reprintBatch->exclude_printed);
        $this->assertNotSame($originalBatch->id, $reprintBatch->id);
        $this->assertSame([$resident->id], $reprintBatch->items()->pluck('resident_id')->all());
    }

    public function test_255_residents_progress_through_normal_and_reprint_batches_without_repeating_ids(): void
    {
        $household = $this->household('San Jose');
        foreach (range(1, 255) as $number) {
            $this->resident('TEST-'.$number, ['household_id' => $household->id]);
        }
        $this->actingAs($this->staff(['view-residents']));
        foreach ([true, false] as $excludePrinted) {
            $seen = [];
            $start = null;
            foreach ([100, 100, 55] as $index => $expected) {
                $payload = ['barangay' => 'San Jose', 'status' => 'active', 'exclude_printed' => (int) $excludePrinted];
                if ($start !== null) {
                    $payload['reprint_start'] = $start;
                }
                $response = $this->post(route('residents.id-cards.batch'), $payload)->assertRedirect();
                $batch = ResidentIdPrintBatch::latest('id')->firstOrFail();
                if (! $excludePrinted) {
                    $start ??= $batch->id;
                }
                $ids = $batch->items()->pluck('resident_id')->all();
                $this->assertCount($expected, $ids);
                if (! $excludePrinted) {
                    $this->get(route('residents.id-cards.batches.print', $batch))->assertOk()
                        ->assertViewHas('reprintStart', $start)
                        ->assertViewHas('hasNextBatch', $index < 2);
                }
                $this->assertSame([], array_values(array_intersect($seen, $ids)));
                $seen = array_merge($seen, $ids);
                $this->get($response->headers->get('Location'))->assertOk()
                    ->assertViewHas('hasNextBatch', $index < 2);
                $printPayload = ['selected_residents' => $ids];
                if ($start !== null) {
                    $printPayload['reprint_start'] = $start;
                }
                $printed = $this->post(route('residents.id-cards.batches.printed', $batch), $printPayload)->assertRedirect();
                $this->get($printed->headers->get('Location'))->assertOk()
                    ->assertViewHas('hasNextBatch', $index < 2);
            }
            $this->assertCount(255, array_unique($seen));
        }
    }

    public function test_print_remaining_resumes_40_pending_ids_without_assigning_them_again(): void
    {
        $household = $this->household('San Jose');
        foreach (range(1, 100) as $number) {
            $this->resident('PENDING-'.$number, ['household_id' => $household->id]);
        }
        $this->actingAs($this->staff(['view-residents']));
        $this->post(route('residents.id-cards.batch'), ['barangay' => 'San Jose', 'status' => 'active'])->assertRedirect();
        $batch = ResidentIdPrintBatch::firstOrFail();
        $ids = $batch->items()->pluck('resident_id')->all();
        $this->postJson(route('residents.id-cards.batches.printed', $batch), ['selected_residents' => array_slice($ids, 0, 60)])->assertOk();
        $remaining = array_slice($ids, 60);
        $this->get(route('residents.id-cards.batches.print', ['printBatch' => $batch, 'remaining' => 1]))->assertOk()
            ->assertViewHas('selectedResidentIds', $remaining)->assertViewHas('hasNextBatch', false)
            ->assertSee('Print 40 Selected ID(s)');
        $this->get(route('residents.id-cards.batches.index'))->assertOk()->assertSee('Print Remaining (40)');
        $this->get(route('residents.id-cards.batches.show', $batch))->assertOk()->assertSee('Print Remaining (40)');
        $this->post(route('residents.id-cards.batch'), ['barangay' => 'San Jose', 'status' => 'active'])->assertSessionHasErrors('barangay');
        $this->assertSame(1, ResidentIdPrintBatch::count());
        $this->postJson(route('residents.id-cards.batches.printed', $batch), ['selected_residents' => $remaining])->assertOk();
        $this->get(route('residents.id-cards.batches.print', ['printBatch' => $batch, 'remaining' => 1]))->assertOk()
            ->assertViewHas('selectedResidentIds', [])->assertSee('Print 0 Selected ID(s)');
    }

    public function test_sector_preview_combines_filters_without_assigning_residents(): void
    {
        $household = $this->household('Poblacion');
        $other = $this->household('San Jose');
        $included = $this->resident('SECTOR-1', ['household_id' => $household->id, 'special_sector' => 'PWD']);
        $this->resident('SECTOR-2', ['household_id' => $household->id, 'special_sector' => 'PWD', 'is_active' => false]);
        $this->resident('SECTOR-3', ['household_id' => $household->id, 'special_sector' => 'Senior Citizen']);
        $this->resident('SECTOR-4', ['household_id' => $other->id, 'special_sector' => 'PWD']);
        $this->actingAs($this->staff(['view-residents']));
        $this->get(route('residents.id-cards.form'))->assertOk()->assertSee('Filter by sector')->assertSee('Preview Residents');
        $payload = ['barangay' => 'Poblacion', 'status' => 'active', 'filter_sectors' => 1, 'sectors' => ['PWD']];
        $this->post(route('residents.id-cards.batch'), $payload + ['action' => 'preview'])
            ->assertOk()->assertViewHas('matchingCount', 1)->assertViewHas('eligibleCount', 1)
            ->assertViewHas('previewResidents', fn ($residents) => $residents->pluck('id')->all() === [$included->id]);
        $this->assertSame(0, ResidentIdPrintBatch::count());
        $this->post(route('residents.id-cards.batch'), $payload)->assertRedirect();
        $this->post(route('residents.id-cards.batch'), $payload + ['action' => 'preview'])
            ->assertOk()->assertViewHas('eligibleCount', 0)->assertViewHas('excludedCount', 1);
        $this->assertSame(1, ResidentIdPrintBatch::count());
    }

    public function test_sector_toggle_off_ignores_stale_selection_and_includes_residents_without_sector(): void
    {
        $household = $this->household('Poblacion');
        $this->resident('OFF-1', ['household_id' => $household->id, 'special_sector' => 'PWD']);
        $this->resident('OFF-2', ['household_id' => $household->id, 'special_sector' => null, 'is_active' => false]);
        $this->actingAs($this->staff(['view-residents']))->post(route('residents.id-cards.batch'), [
            'barangay' => 'Poblacion', 'status' => 'all', 'filter_sectors' => 0, 'sectors' => ['Unknown'],
        ])->assertRedirect();
        $batch = ResidentIdPrintBatch::firstOrFail();
        $this->assertNull($batch->sector_filter);
        $this->assertSame(2, $batch->items()->count());
    }

    public function test_enabled_sector_filter_requires_valid_selection(): void
    {
        $this->household('Poblacion');
        $this->actingAs($this->staff(['view-residents']));
        $payload = ['barangay' => 'Poblacion', 'filter_sectors' => 1];
        $this->post(route('residents.id-cards.batch'), $payload)->assertSessionHasErrors('sectors');
        $this->post(route('residents.id-cards.batch'), $payload + ['sectors' => ['Unknown']])->assertSessionHasErrors('sectors.0');
        $this->assertSame(0, ResidentIdPrintBatch::count());
    }

    public function test_sector_scopes_stay_separate_and_protection_applies_across_scopes(): void
    {
        $household = $this->household('Poblacion');
        $first = $this->resident('SCOPE-1', ['household_id' => $household->id, 'special_sector' => 'PWD', 'last_name' => 'Zulu']);
        $this->actingAs($this->staff(['view-residents']));
        $base = ['barangay' => 'Poblacion', 'status' => 'active'];
        $this->post(route('residents.id-cards.batch'), $base)->assertRedirect();
        $unfiltered = ResidentIdPrintBatch::latest('id')->firstOrFail();
        $second = $this->resident('SCOPE-2', ['household_id' => $household->id, 'special_sector' => 'PWD', 'last_name' => 'Zulu']);
        $filtered = $base + ['filter_sectors' => 1, 'sectors' => ['PWD']];
        $this->post(route('residents.id-cards.batch'), $filtered)->assertRedirect();
        $batch = ResidentIdPrintBatch::latest('id')->firstOrFail();
        $this->assertNotSame($unfiltered->id, $batch->id);
        $this->assertSame([$second->id], $batch->items()->pluck('resident_id')->all());
        $this->assertSame([$first->id], $unfiltered->items()->pluck('resident_id')->all());
        $third = $this->resident('SCOPE-3', ['household_id' => $household->id, 'special_sector' => 'PWD', 'last_name' => 'Abad']);
        $this->post(route('residents.id-cards.batch'), $filtered)->assertRedirect(route('residents.id-cards.batches.print', $batch));
        $this->get(route('residents.id-cards.batches.print', $batch))->assertOk()
            ->assertViewHas('residents', fn ($residents) => $residents->pluck('id')->all() === [$third->id, $second->id]);
        $this->get(route('residents.id-cards.batches.index'))->assertOk()->assertSee('PWD');
        $this->get(route('residents.id-cards.batches.show', $batch))->assertOk()->assertSee('PWD');
        $this->postJson(route('residents.id-cards.batches.printed', $batch), ['selected_residents' => [$third->id]])->assertOk();
        $fourth = $this->resident('SCOPE-4', ['household_id' => $household->id, 'special_sector' => 'PWD']);
        $this->post(route('residents.id-cards.batch'), $filtered)->assertRedirect();
        $next = ResidentIdPrintBatch::latest('id')->firstOrFail();
        $this->assertNotSame($batch->id, $next->id);
        $this->assertSame([$fourth->id], $next->items()->pluck('resident_id')->all());
        $this->get(route('residents.id-cards.batches.print', ['printBatch' => $batch, 'remaining' => 1]))->assertOk()
            ->assertViewHas('selectedResidentIds', [$second->id]);
    }

    public function test_multiple_sectors_are_canonical_and_inactive_filter_is_respected(): void
    {
        $household = $this->household('Poblacion');
        foreach (['PWD', 'Senior Citizen', 'Other'] as $index => $sector) {
            $this->resident('MULTI-'.$index, ['household_id' => $household->id, 'special_sector' => $sector, 'is_active' => false]);
        }
        $this->resident('MULTI-ACTIVE', ['household_id' => $household->id, 'special_sector' => 'PWD']);
        $this->actingAs($this->staff(['view-residents']));
        $payload = ['barangay' => 'Poblacion', 'status' => 'inactive', 'filter_sectors' => 1];
        $this->post(route('residents.id-cards.batch'), $payload + ['sectors' => ['Senior Citizen', 'PWD']])->assertRedirect();
        $batch = ResidentIdPrintBatch::firstOrFail();
        $this->assertSame(['PWD', 'Senior Citizen'], $batch->sector_filter);
        $this->assertSame(2, $batch->items()->count());
        $this->post(route('residents.id-cards.batch'), $payload + ['sectors' => ['PWD', 'Senior Citizen']])
            ->assertRedirect(route('residents.id-cards.batches.print', $batch));
        $this->assertSame(1, ResidentIdPrintBatch::count());
    }

    public function test_sector_batches_and_reprints_continue_within_the_selected_sector(): void
    {
        $household = $this->household('Poblacion');
        foreach (range(1, 101) as $number) {
            $this->resident('NEXT-'.$number, ['household_id' => $household->id, 'special_sector' => 'PWD']);
        }
        $this->resident('NEXT-OTHER', ['household_id' => $household->id, 'special_sector' => 'Other']);
        $this->actingAs($this->staff(['view-residents']));
        foreach ([1, 0] as $protect) {
            $start = null;
            $seen = [];
            foreach ([100, 1] as $index => $count) {
                $payload = ['barangay' => 'Poblacion', 'status' => 'active', 'filter_sectors' => 1,
                    'sectors' => ['PWD'], 'exclude_printed' => $protect];
                if ($start !== null) {
                    $payload['reprint_start'] = $start;
                }
                $this->post(route('residents.id-cards.batch'), $payload)->assertRedirect();
                $batch = ResidentIdPrintBatch::latest('id')->firstOrFail();
                $start ??= $protect ? null : $batch->id;
                $ids = $batch->items()->pluck('resident_id')->all();
                $this->assertCount($count, $ids);
                $this->assertSame([], array_values(array_intersect($seen, $ids)));
                $seen = array_merge($seen, $ids);
                $response = $this->get(route('residents.id-cards.batches.print', $batch))->assertOk()
                    ->assertViewHas('hasNextBatch', $index === 0)->assertSee('PWD');
                if ($index === 0) {
                    $response->assertSee('name="sectors[]" value="PWD"', false);
                }
                $this->postJson(route('residents.id-cards.batches.printed', $batch), array_filter([
                    'selected_residents' => $ids, 'reprint_start' => $start,
                ]))->assertOk();
            }
        }
    }

    public function test_preview_retains_html_selected_barangay_and_status_for_generation(): void
    {
        $household = $this->household('Sta. Maria');
        $resident = $this->resident('PREVIEW-INACTIVE', ['household_id' => $household->id, 'is_active' => false, 'special_sector' => 'PWD']);
        $this->actingAs($this->staff(['view-residents']));
        $response = $this->post(route('residents.id-cards.batch'), [
            'barangay' => 'Sta. Maria', 'status' => 'inactive', 'filter_sectors' => 1,
            'sectors' => ['PWD'], 'action' => 'preview',
        ])->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $payload = ['filter_sectors' => 1, 'sectors' => ['PWD'], 'action' => 'generate'];
        foreach (['barangay', 'status'] as $field) {
            $options = $xpath->query("//select[@name='{$field}']/option[@selected]");
            $this->assertSame(1, $options->length);
            $payload[$field] = $options->item(0)->getAttribute('value');
        }
        $this->assertSame('Sta. Maria', $payload['barangay']);
        $this->assertSame('inactive', $payload['status']);
        $this->post(route('residents.id-cards.batch'), $payload)->assertRedirect();
        $this->assertSame([$resident->id], ResidentIdPrintBatch::firstOrFail()->items()->pluck('resident_id')->all());
    }

    public function test_preview_links_only_matching_pending_residents_in_all_barangays_batch(): void
    {
        $household = $this->household('Pocalpocal');
        $other = $this->household('Pandan');
        $resident = $this->resident('PENDING-SCOPE', ['household_id' => $household->id, 'special_sector' => 'PWD']);
        $this->resident('PENDING-OTHER', ['household_id' => $other->id, 'special_sector' => 'PWD']);
        $this->actingAs($this->staff(['view-residents']));
        $this->post(route('residents.id-cards.batch'), ['barangay' => 'all', 'status' => 'active'])->assertRedirect();
        $batch = ResidentIdPrintBatch::firstOrFail();
        $response = $this->post(route('residents.id-cards.batch'), [
            'barangay' => 'Pocalpocal', 'status' => 'active', 'filter_sectors' => 1, 'sectors' => ['PWD'], 'action' => 'preview',
        ])->assertOk()->assertViewHas('eligibleCount', 0)->assertSee('Open 1 Matching Pending ID(s)');
        $link = route('residents.id-cards.batches.print', ['printBatch' => $batch->id, 'selected_residents' => [$resident->id]]);
        $response->assertSee($link);
        $this->get($link)->assertOk()->assertViewHas('selectedResidentIds', [$resident->id]);
        $this->assertSame(1, ResidentIdPrintBatch::count());
        $this->assertNull($batch->fresh()->printed_at);
    }

    public function test_sorting_is_applied_before_batch_limit_and_retained_for_next_batch(): void
    {
        $household = $this->household('Poblacion');
        $ids = [];
        foreach (range(101, 1) as $number) {
            $ids[] = $this->resident('SORT-'.$number, [
                'household_id' => $household->id,
                'last_name' => 'Name'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            ])->id;
        }
        $this->actingAs($this->staff(['view-residents']));
        $base = ['barangay' => 'Poblacion', 'status' => 'active'];
        foreach ([0 => $ids, 1 => array_reverse($ids)] as $order => $expected) {
            $this->post(route('residents.id-cards.batch'), $base + ['alphabetical' => $order, 'action' => 'preview'])
                ->assertOk()->assertViewHas('alphabetical', (bool) $order)
                ->assertViewHas('previewResidents', fn ($rows) => $rows->pluck('id')->all() === array_slice($expected, 0, 100));
        }
        $this->post(route('residents.id-cards.batch'), $base + ['alphabetical' => 0])->assertRedirect();
        $batch = ResidentIdPrintBatch::firstOrFail();
        $this->assertFalse($batch->alphabetical);
        $this->assertSame(array_slice($ids, 0, 100), $batch->items()->orderBy('id')->pluck('resident_id')->all());
        $this->get(route('residents.id-cards.batches.print', $batch))->assertOk()
            ->assertSee('name="alphabetical" value="0"', false)
            ->assertViewHas('residents', fn ($rows) => $rows->pluck('id')->all() === array_slice($ids, 0, 100));
        $this->post(route('residents.id-cards.batch'), $base + ['alphabetical' => 0])->assertRedirect();
        $next = ResidentIdPrintBatch::latest('id')->firstOrFail();
        $this->assertFalse($next->alphabetical);
        $this->assertSame([$ids[100]], $next->items()->pluck('resident_id')->all());
    }

    public function test_existing_batch_order_can_change_without_losing_selection_or_print_status(): void
    {
        $zulu = $this->resident('ORDER-Z', ['last_name' => 'Zulu']);
        $bob = $this->resident('ORDER-B', ['last_name' => 'Abad', 'first_name' => 'Bob']);
        $ana = $this->resident('ORDER-A', ['last_name' => 'Abad', 'first_name' => 'Ana']);
        $this->actingAs($this->staff(['view-residents']));
        $ids = [$zulu->id, $bob->id, $ana->id];
        $this->post(route('residents.id-cards.batch'), ['residents' => $ids])->assertRedirect();
        $batch = ResidentIdPrintBatch::firstOrFail();
        $this->assertTrue($batch->alphabetical);
        $this->get(route('residents.id-cards.batches.print', $batch))->assertOk()
            ->assertViewHas('residents', fn ($rows) => $rows->pluck('id')->all() === [$ana->id, $bob->id, $zulu->id]);
        $this->postJson(route('residents.id-cards.batches.printed', $batch), ['selected_residents' => [$zulu->id]])->assertOk();
        $before = $batch->fresh()->only(['status', 'printed_at', 'resident_count']);
        $itemsBefore = $batch->items()->orderBy('id')->get()->toArray();
        $changed = $this->post(route('residents.id-cards.batches.order', $batch), [
            'alphabetical' => 0, 'selected_residents' => [$ana->id],
        ])->assertRedirect();
        $this->get($changed->headers->get('Location'))->assertOk()
            ->assertViewHas('selectedResidentIds', [$ana->id])
            ->assertViewHas('residents', fn ($rows) => $rows->pluck('id')->all() === $ids);
        $this->assertFalse($batch->fresh()->alphabetical);
        $this->assertEquals($before, $batch->fresh()->only(['status', 'printed_at', 'resident_count']));
        $this->assertSame($itemsBefore, $batch->items()->orderBy('id')->get()->toArray());
        $this->get(route('residents.id-cards.batches.print', ['printBatch' => $batch, 'remaining' => 1]))
            ->assertOk()->assertViewHas('selectedResidentIds', fn ($selected) => collect($selected)->sort()->values()->all() === [$bob->id, $ana->id]);
        $empty = $this->post(route('residents.id-cards.batches.order', $batch), ['alphabetical' => 1])->assertRedirect();
        $this->get($empty->headers->get('Location'))->assertOk()->assertViewHas('selectedResidentIds', []);
        $this->assertTrue($batch->fresh()->alphabetical);
        $outsider = $this->resident('ORDER-OUTSIDE');
        $this->post(route('residents.id-cards.batches.order', $batch), [
            'alphabetical' => 0, 'selected_residents' => [$outsider->id],
        ])->assertSessionHasErrors('selected_residents.0');
        $this->assertTrue($batch->fresh()->alphabetical);
    }

    public function test_different_sorting_settings_do_not_extend_the_same_open_batch(): void
    {
        $household = $this->household('Poblacion');
        $this->resident('OPEN-A', ['household_id' => $household->id]);
        $this->actingAs($this->staff(['view-residents']));
        $base = ['barangay' => 'Poblacion', 'status' => 'active'];
        $this->post(route('residents.id-cards.batch'), $base)->assertRedirect();
        $first = ResidentIdPrintBatch::firstOrFail();
        $new = $this->resident('OPEN-B', ['household_id' => $household->id]);
        $this->post(route('residents.id-cards.batch'), $base + ['alphabetical' => 0])->assertRedirect();
        $second = ResidentIdPrintBatch::latest('id')->firstOrFail();
        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(1, $first->items()->count());
        $this->assertSame([$new->id], $second->items()->pluck('resident_id')->all());
        $this->assertFalse($second->alphabetical);
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
        $householdId = $overrides['household_id'] ?? null;
        if ($householdId && Resident::where('household_id', $householdId)->exists()) {
            $household = Household::findOrFail($householdId)->replicate([
                'household_id',
                'qr_code',
                'provisional_for_pin',
            ]);
            $household->household_id = 'HH-'.str()->upper(str()->random(8));
            $household->save();
            $overrides['household_id'] = $household->id;
        }

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
