<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class HouseholdResidentSplitService
{
    private const ARCHIVE_NOTE = '[Archived by residents:split-households]';

    /**
     * @return array{residents:int,households_created:int,households_archived:int,distributions:int}
     */
    public function run(bool $dryRun = false): array
    {
        return DB::transaction(function () use ($dryRun) {
            $residents = DB::table('residents')->orderBy('id')->lockForUpdate()->get();
            $households = DB::table('households')->lockForUpdate()->get()->keyBy('id');
            $memberCounts = $residents->whereNotNull('household_id')->countBy('household_id');
            $alreadyMapped = DB::table('household_resident_split_audits')
                ->whereNull('reversed_at')
                ->pluck('resident_id')
                ->flip();

            $toMove = $residents->filter(fn ($resident) => ! $alreadyMapped->has($resident->id)
                && ($resident->household_id === null || ($memberCounts[$resident->household_id] ?? 0) > 1));

            $distributionCount = $toMove->sum(
                fn ($resident) => DB::table('distributions')->where('resident_id', $resident->id)->count()
            );
            $sharedHouseholdIds = $toMove
                ->pluck('household_id')
                ->filter(fn ($id) => ($memberCounts[$id] ?? 0) > 1)
                ->unique()
                ->values();

            $summary = [
                'residents' => $toMove->count(),
                'households_created' => $toMove->count(),
                'households_archived' => $sharedHouseholdIds->count(),
                'distributions' => $distributionCount,
            ];

            if ($dryRun) {
                return $summary;
            }

            $nextSequence = $this->nextHouseholdSequence();
            $now = now();

            foreach ($toMove as $resident) {
                $oldHousehold = $resident->household_id ? $households->get($resident->household_id) : null;
                $newHouseholdId = DB::table('households')->insertGetId(
                    $this->newHouseholdData($resident, $oldHousehold, $nextSequence++, $now)
                );

                $distributionMappings = DB::table('distributions')
                    ->where('resident_id', $resident->id)
                    ->pluck('household_id', 'id')
                    ->all();

                DB::table('household_resident_split_audits')->updateOrInsert(
                    ['resident_id' => $resident->id],
                    [
                        'operation' => $oldHousehold ? 'split' : 'orphan',
                        'original_household_id' => $resident->household_id,
                        'new_household_id' => $newHouseholdId,
                        'original_relationship_to_head' => $resident->relationship_to_head,
                        'original_household_snapshot' => $oldHousehold ? json_encode((array) $oldHousehold) : null,
                        'distribution_household_mappings' => json_encode($distributionMappings),
                        'converted_at' => $now,
                        'reversed_at' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                DB::table('residents')->where('id', $resident->id)->update([
                    'household_id' => $newHouseholdId,
                    'relationship_to_head' => 'head',
                    'updated_at' => $now,
                ]);
                DB::table('distributions')->where('resident_id', $resident->id)->update([
                    'household_id' => $newHouseholdId,
                    'updated_at' => $now,
                ]);
            }

            $this->normalizeSingletons($now);
            $this->archiveHouseholds($sharedHouseholdIds, $now);

            return $summary;
        });
    }

    public function reverse(): void
    {
        DB::transaction(function () {
            $audits = DB::table('household_resident_split_audits')
                ->whereNull('reversed_at')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();
            $now = now();

            foreach ($audits as $audit) {
                if ($audit->original_household_snapshot) {
                    $snapshot = (array) json_decode($audit->original_household_snapshot, true);
                    unset($snapshot['id']);
                    DB::table('households')->where('id', $audit->original_household_id)->update($snapshot);
                }

                DB::table('residents')->where('id', $audit->resident_id)->update([
                    'household_id' => $audit->original_household_id,
                    'relationship_to_head' => $audit->original_relationship_to_head,
                    'updated_at' => $now,
                ]);

                foreach ((array) json_decode($audit->distribution_household_mappings, true) as $distributionId => $householdId) {
                    DB::table('distributions')->where('id', $distributionId)->update([
                        'household_id' => $householdId,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('households')->where('id', $audit->new_household_id)->delete();
                DB::table('household_resident_split_audits')->where('id', $audit->id)->update([
                    'reversed_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });
    }

    private function nextHouseholdSequence(): int
    {
        $prefix = 'HH-'.now()->format('Ym').'-';

        return DB::table('households')
            ->where('household_id', 'like', $prefix.'%')
            ->pluck('household_id')
            ->map(fn (string $id) => (int) substr($id, strlen($prefix)))
            ->max() + 1;
    }

    private function newHouseholdData(object $resident, ?object $oldHousehold, int $sequence, mixed $now): array
    {
        $prefix = 'HH-'.now()->format('Ym').'-';

        return [
            'household_id' => $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT),
            'building_registry_number' => $oldHousehold?->building_registry_number,
            'is_provisional' => false,
            'provisional_for_pin' => null,
            'address' => $oldHousehold?->address,
            'barangay' => $oldHousehold?->barangay ?? 'Unknown',
            'barangay_code' => $oldHousehold?->barangay_code,
            'city_municipality' => $oldHousehold?->city_municipality ?? 'Unknown',
            'city_municipality_code' => $oldHousehold?->city_municipality_code,
            'province' => $oldHousehold?->province ?? 'Unknown',
            'province_code' => $oldHousehold?->province_code,
            'postal_code' => $oldHousehold?->postal_code,
            'region' => $oldHousehold?->region,
            'region_code' => $oldHousehold?->region_code,
            'monthly_income' => $resident->monthly_income ?? 0,
            'member_count' => 1,
            'dwelling_type' => $oldHousehold?->dwelling_type,
            'has_electricity' => $oldHousehold?->has_electricity ?? true,
            'has_water_supply' => $oldHousehold?->has_water_supply ?? true,
            'is_active' => $resident->is_active,
            'notes' => $oldHousehold
                ? 'Split from household '.$oldHousehold->household_id.'.'
                : 'Created for previously unassigned resident.',
            'qr_code' => null,
            'created_at' => $now,
            'updated_at' => $now,
            'deleted_at' => $resident->deleted_at,
        ];
    }

    private function normalizeSingletons(mixed $now): void
    {
        $singletons = DB::table('residents')
            ->select('household_id', DB::raw('MAX(id) as resident_id'), DB::raw('MAX(monthly_income) as monthly_income'))
            ->whereNotNull('household_id')
            ->groupBy('household_id')
            ->havingRaw('COUNT(*) = 1')
            ->get();

        foreach ($singletons as $singleton) {
            DB::table('residents')->where('id', $singleton->resident_id)->update([
                'relationship_to_head' => 'head',
                'updated_at' => $now,
            ]);
            DB::table('households')->where('id', $singleton->household_id)->update([
                'member_count' => 1,
                'monthly_income' => $singleton->monthly_income ?? 0,
                'updated_at' => $now,
            ]);
            DB::table('distributions')->where('resident_id', $singleton->resident_id)->update([
                'household_id' => $singleton->household_id,
                'updated_at' => $now,
            ]);
        }
    }

    private function archiveHouseholds(Collection $householdIds, mixed $now): void
    {
        foreach ($householdIds as $householdId) {
            $household = DB::table('households')->where('id', $householdId)->first();
            if (! $household) {
                throw new RuntimeException("Original household {$householdId} disappeared during conversion.");
            }

            $notes = trim(implode("\n", array_filter([$household->notes, self::ARCHIVE_NOTE])));
            DB::table('households')->where('id', $householdId)->update([
                'is_active' => false,
                'notes' => $notes,
                'deleted_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
