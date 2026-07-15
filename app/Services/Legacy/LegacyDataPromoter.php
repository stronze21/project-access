<?php

namespace App\Services\Legacy;

use App\Models\BarangayHealthWorkerAssignment;
use App\Models\BarangayZone;
use App\Models\CivilStatus;
use App\Models\EducationalAttainment;
use App\Models\Household;
use App\Models\LegacyImportBatch;
use App\Models\LegacyImportRow;
use App\Models\Resident;
use App\Models\SourceIncomeType;
use Carbon\Carbon;
use Database\Seeders\LocationsSeeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class LegacyDataPromoter
{
    private ?array $psgcBarangayRecords = null;

    private const CIVIL_STATUS_MAP = [
        '1' => 'single',
        '2' => 'married',
        '3' => 'widowed',
        '4' => 'other',
        '5' => 'separated',
        '6' => 'other',
        '7' => 'divorced',
        'single' => 'single',
        'married' => 'married',
        'widow/widower' => 'widowed',
        'widowed' => 'widowed',
        'live-in/common-in-law' => 'other',
        'separated' => 'separated',
        'annulled' => 'other',
        'divorced' => 'divorced',
    ];

    private const DEFAULT_CIVIL_STATUS_REFERENCES = [
        '1' => ['name' => 'Single', 'canonical_value' => 'single'],
        '2' => ['name' => 'Married', 'canonical_value' => 'married'],
        '3' => ['name' => 'Widow/Widower', 'canonical_value' => 'widowed'],
        '4' => ['name' => 'Live-In/Common-in-Law', 'canonical_value' => 'other'],
        '5' => ['name' => 'Separated', 'canonical_value' => 'separated'],
        '6' => ['name' => 'Annulled', 'canonical_value' => 'other'],
        '7' => ['name' => 'Divorced', 'canonical_value' => 'divorced'],
    ];

    private const DEFAULT_EDUCATION_MAP = [
        '1' => 'Elementary Undergraduate',
        '2' => 'Elementary Graduate',
        '3' => 'High School Undergraduate',
        '4' => 'High School Graduate (Old Curriculum)',
        '5' => 'Senior High School Graduate Undergraduate',
        '6' => 'Senior High School Graduate (New Curriculum)',
        '7' => 'Vocational',
        '8' => 'College Undergraduate',
        '9' => 'College Graduate',
        '10' => "Master's Degree",
        '11' => 'Doctorate Degree',
        '12' => 'Others',
    ];

    private const DEFAULT_INCOME_MAP = [
        '1' => 'Farm Owner',
        '2' => 'Farm Worker/Fisherman',
        '3' => 'Housemaid/Laundrywoman',
        '4' => 'Tricycle/jeepney Driver',
        '5' => 'Beautician',
        '6' => 'Sidewalk Vendor',
        '7' => 'Laborer/Carpenter',
        '8' => 'Pastor',
        '9' => 'Businessman/Businesswoman',
        '10' => 'Government Employee',
        '11' => 'Private Employee',
        '12' => 'OFW DH',
        '13' => 'OFW (Non DH)',
        '14' => 'Others (Please Specify)',
    ];

    public function promote(LegacyImportBatch $batch, bool $commit = false): array
    {
        if (! in_array($batch->status, ['staged', 'promoted_with_conflicts', 'promoted'], true)) {
            throw new RuntimeException("Batch #{$batch->id} is not ready for promotion.");
        }

        $runner = function () use ($batch, $commit) {
            $context = $this->buildReferenceContext($batch, $commit);
            $personal = $this->promoteResidents($batch, $context, $commit);
            $households = $this->promoteHouseholds(
                $batch,
                $personal['addresses'],
                $personal['eligible_pins'],
                $context,
                $commit
            );
            $bhw = $this->promoteBhwAssignments($batch, $context, $commit);

            $result = [
                'dry_run' => ! $commit,
                'batch_id' => $batch->id,
                'references' => $context['stats'],
                'residents' => Arr::except($personal, ['addresses', 'eligible_pins', 'cursor', 'has_more']),
                'households' => $households,
                'bhw' => $bhw,
            ];

            if ($commit) {
                $hasConflicts = collect([$result['residents'], $households, $bhw])
                    ->sum(fn (array $stats) => ($stats['conflicts'] ?? 0) + ($stats['incomplete'] ?? 0)) > 0;
                $batch->update([
                    'status' => $hasConflicts ? 'promoted_with_conflicts' : 'promoted',
                    'promoted_at' => now(),
                ]);
            }

            return $result;
        };

        return $commit ? DB::transaction($runner) : $runner();
    }

    /**
     * Promote one bounded slice of personal information. The returned cursor
     * lets the browser request the next slice without keeping one PHP request
     * alive for the complete CSV.
     */
    public function promotePersonalChunk(
        LegacyImportBatch $batch,
        bool $commit,
        int $afterId = 0,
        int $chunkSize = 250,
    ): array {
        if (! in_array($batch->status, ['staged', 'promoted_with_conflicts', 'promoted'], true)) {
            throw new RuntimeException("Batch #{$batch->id} is not ready for promotion.");
        }

        $runner = function () use ($batch, $commit, $afterId, $chunkSize) {
            $context = $this->buildReferenceContext($batch, $commit);
            $personal = $this->promoteResidents($batch, $context, $commit, $afterId, $chunkSize);
            $households = $this->promoteProvisionalHouseholds(
                $batch,
                $personal['addresses'],
                $personal['eligible_pins'],
                $context,
                $commit,
            );

            return [
                'cursor' => $personal['cursor'],
                'has_more' => $personal['has_more'],
                'references' => $context['stats'],
                'residents' => Arr::except($personal, ['addresses', 'eligible_pins', 'cursor', 'has_more']),
                'households' => $households,
            ];
        };

        return $commit ? DB::transaction($runner) : $runner();
    }

    public function promoteFamilyChunk(
        LegacyImportBatch $batch,
        bool $commit,
        string $afterFamily = '',
        int $chunkSize = 500,
    ): array {
        $familyRows = LegacyImportRow::query()
            ->where('legacy_import_batch_id', $batch->id)
            ->where('source_table', 'family_members')
            ->where('raw_payload->FamilyNumber', '>', $afterFamily)
            ->orderBy('raw_payload->FamilyNumber')
            ->orderBy('id')
            ->limit($chunkSize)
            ->get();
        $familyNumbers = $familyRows
            ->map(fn (LegacyImportRow $row) => trim((string) ($row->raw_payload['FamilyNumber'] ?? '')))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $cursor = $familyNumbers === [] ? $afterFamily : max($familyNumbers);

        $runner = function () use ($batch, $commit, $familyNumbers) {
            $context = $this->buildReferenceContext($batch, $commit);

            return $this->promoteHouseholds($batch, [], [], $context, $commit, $familyNumbers);
        };
        $households = $commit ? DB::transaction($runner) : $runner();
        $hasMore = LegacyImportRow::query()
            ->where('legacy_import_batch_id', $batch->id)
            ->where('source_table', 'family_members')
            ->where('raw_payload->FamilyNumber', '>', $cursor)
            ->exists();

        return [
            'cursor' => $cursor,
            'has_more' => $hasMore,
            'processed_rows' => $households['source_rows'],
            'households' => $households,
        ];
    }

    /** Complete the smaller BHW export after personal and family chunks. */
    public function promoteRelatedData(LegacyImportBatch $batch, bool $commit): array
    {
        $runner = function () use ($batch, $commit) {
            $context = $this->buildReferenceContext($batch, $commit);

            return [
                'bhw' => $this->promoteBhwAssignments($batch, $context, $commit),
            ];
        };

        return $commit ? DB::transaction($runner) : $runner();
    }

    public function finalizeChunkedPromotion(LegacyImportBatch $batch, array $report, bool $commit): void
    {
        if (! $commit) {
            return;
        }

        $hasConflicts = collect([$report['residents'] ?? [], $report['households'] ?? [], $report['bhw'] ?? []])
            ->sum(fn (array $stats) => ($stats['conflicts'] ?? 0) + ($stats['incomplete'] ?? 0)) > 0;

        $batch->update([
            'status' => $hasConflicts ? 'promoted_with_conflicts' : 'promoted',
            'promoted_at' => now(),
        ]);
    }

    private function buildReferenceContext(LegacyImportBatch $batch, bool $commit): array
    {
        $hasPersonalInfo = LegacyImportRow::query()
            ->where('legacy_import_batch_id', $batch->id)
            ->where('source_table', 'personal_info')
            ->exists();
        $education = EducationalAttainment::where('is_active', true)
            ->pluck('name', 'legacy_code')
            ->all();
        if ($hasPersonalInfo) {
            $knownEducationCodes = EducationalAttainment::pluck('legacy_code')->all();
            $education += array_diff_key(self::DEFAULT_EDUCATION_MAP, array_flip($knownEducationCodes));
        }
        foreach ($this->sourceRows($batch, 'educational_attainment') as $row) {
            $payload = $row->raw_payload;
            if (($payload['Educ_ID'] ?? '') !== '') {
                $education[$payload['Educ_ID']] = $payload['Educ_Attainment'] ?? null;
            }
        }

        if ($commit && $education !== []) {
            $now = now();
            DB::table('educational_attainments')->upsert(
                collect($education)->map(fn ($name, $code) => [
                    'legacy_code' => (string) $code,
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->values()->all(),
                ['legacy_code'],
                ['name', 'is_active', 'updated_at']
            );
        }

        $civilReferences = CivilStatus::where('is_active', true)->get()->keyBy('legacy_code');
        if ($hasPersonalInfo) {
            $knownCivilStatusCodes = CivilStatus::pluck('legacy_code')->all();
            foreach (self::DEFAULT_CIVIL_STATUS_REFERENCES as $code => $reference) {
                if (! in_array($code, $knownCivilStatusCodes, true)) {
                    $civilReferences->put($code, (object) ['legacy_code' => $code, ...$reference]);
                }
            }
        }
        foreach ($this->sourceRows($batch, 'civil_status') as $row) {
            $payload = $row->raw_payload;
            $code = trim((string) ($payload['CivilStatus_Code'] ?? ''));
            $name = trim((string) ($payload['CivilStatus'] ?? ''));
            if ($code !== '' && $name !== '') {
                $civilReferences->put($code, (object) [
                    'legacy_code' => $code,
                    'name' => $name,
                    'canonical_value' => self::CIVIL_STATUS_MAP[strtolower($name)] ?? 'other',
                ]);
            }
        }
        if ($commit && $civilReferences->isNotEmpty()) {
            $now = now();
            DB::table('civil_statuses')->upsert(
                $civilReferences->map(fn ($reference) => [
                    'legacy_code' => (string) $reference->legacy_code,
                    'name' => $reference->name,
                    'canonical_value' => $reference->canonical_value,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->values()->all(),
                ['legacy_code'],
                ['name', 'canonical_value', 'is_active', 'updated_at']
            );
        }
        $civilStatusMap = self::CIVIL_STATUS_MAP;
        foreach ($civilReferences as $code => $reference) {
            $civilStatusMap[strtolower((string) $code)] = $reference->canonical_value;
            $civilStatusMap[strtolower($reference->name)] = $reference->canonical_value;
        }

        $incomeNames = SourceIncomeType::where('is_active', true)
            ->pluck('name', 'legacy_code')
            ->all();
        if ($hasPersonalInfo) {
            $knownIncomeCodes = SourceIncomeType::pluck('legacy_code')->all();
            $incomeNames += array_diff_key(self::DEFAULT_INCOME_MAP, array_flip($knownIncomeCodes));
        }
        foreach ($this->sourceRows($batch, 'source_income_type') as $row) {
            $payload = $row->raw_payload;
            $code = trim((string) ($payload['IncomeCode'] ?? ''));
            $name = trim((string) ($payload['Income_description'] ?? ''));
            if ($code !== '' && $name !== '') {
                $incomeNames[$code] = $name;
            }
        }

        if ($commit && $incomeNames !== []) {
            $now = now();
            $values = [];
            foreach ($incomeNames as $code => $name) {
                $values[] = [
                    'legacy_code' => $code,
                    'name' => $name,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('source_income_types')->upsert($values, ['legacy_code'], ['name', 'is_active', 'updated_at']);
        }

        $incomeIds = SourceIncomeType::whereIn('legacy_code', array_keys($incomeNames))
            ->pluck('id', 'legacy_code')
            ->all();

        $barangayMappings = [];
        foreach ($this->sourceRows($batch, 'barangay') as $row) {
            $payload = $row->raw_payload;
            $legacyCode = $this->normalizeLegacyCode($payload['Barangay_Code'] ?? '');
            $legacyName = trim((string) ($payload['Barangay'] ?? ''));
            if ($legacyCode === '' || $legacyName === '') {
                continue;
            }
            $barangayMappings[$legacyCode] = [
                'name' => $legacyName,
                'brgy_code' => $this->resolvePsgcBarangayCode($legacyName),
            ];
        }

        foreach (DB::table('legacy_barangay_mappings')
            ->where('source_system', LegacyCsvImporter::SOURCE_SYSTEM)
            ->where('status', '!=', 'ignored')
            ->get() as $storedMapping) {
            $barangayMappings[$storedMapping->legacy_code] ??= [
                'name' => $storedMapping->legacy_name,
                'brgy_code' => $storedMapping->brgy_code,
            ];
        }

        if ($commit && $barangayMappings !== []) {
            $now = now();
            $values = [];
            foreach ($barangayMappings as $legacyCode => $mapping) {
                $values[] = [
                    'source_system' => LegacyCsvImporter::SOURCE_SYSTEM,
                    'legacy_code' => $legacyCode,
                    'legacy_name' => $mapping['name'],
                    'brgy_code' => $mapping['brgy_code'],
                    'status' => $mapping['brgy_code'] ? 'mapped' : 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('legacy_barangay_mappings')->upsert(
                $values,
                ['source_system', 'legacy_code'],
                ['legacy_name', 'brgy_code', 'status', 'updated_at']
            );
        }

        return [
            'education' => $education,
            'civil_status' => $civilStatusMap,
            'income_ids' => $incomeIds,
            'barangays' => $barangayMappings,
            'stats' => [
                'education_values' => count($education),
                'civil_statuses' => $civilReferences->count(),
                'income_types' => count($incomeNames),
                'barangays' => count($barangayMappings),
                'unmapped_barangays' => collect($barangayMappings)->whereNull('brgy_code')->count(),
            ],
        ];
    }

    private function promoteResidents(
        LegacyImportBatch $batch,
        array $context,
        bool $commit,
        int $afterId = 0,
        ?int $limit = null,
    ): array {
        $stats = [
            'source_rows' => 0,
            'created' => 0,
            'matched' => 0,
            'backfilled' => 0,
            'incomplete' => 0,
            'conflicts' => 0,
            'skipped_invalid' => 0,
        ];
        $addresses = [];
        $eligiblePins = [];

        $query = LegacyImportRow::query()
            ->where('legacy_import_batch_id', $batch->id)
            ->where('source_table', 'personal_info')
            ->where('id', '>', $afterId)
            ->orderBy('id');

        $lastProcessedId = $afterId;
        $processRows = function ($rows) use ($batch, $context, $commit, &$stats, &$addresses, &$eligiblePins, &$lastProcessedId) {
            $lastProcessedId = (int) ($rows->last()?->id ?? $lastProcessedId);
            $pins = $rows->pluck('natural_key')->filter()->unique()->values()->all();
            $existing = Resident::withTrashed()
                ->whereIn('resident_id', $pins)
                ->get()
                ->keyBy('resident_id');
            $newRecords = [];
            $rowStates = [];

            foreach ($rows as $row) {
                $stats['source_rows']++;
                $payload = $row->raw_payload;
                $pin = trim((string) ($payload['PIN'] ?? ''));
                if ($pin === '' || $row->validation_status !== 'valid') {
                    $stats['skipped_invalid']++;
                    if ($pin !== '') {
                        $rowStates[$pin] = ['status' => 'conflict', 'reason' => 'staging_validation'];
                    }

                    continue;
                }

                $candidate = $this->residentCandidate($payload, $context);
                if ($candidate === null) {
                    $stats['incomplete']++;
                    $rowStates[$pin] = ['status' => 'incomplete', 'reason' => 'missing_required_fields'];

                    continue;
                }

                if (! $existing->has($pin)) {
                    $candidate['is_legacy_imported'] = true;
                    $eligiblePins[$pin] = true;
                    if (trim((string) ($payload['Address'] ?? '')) !== '') {
                        $addresses[$pin] = trim((string) $payload['Address']);
                    }
                    $newRecords[$pin] = $candidate;
                    $rowStates[$pin] = ['status' => 'linked', 'reason' => null];
                    $stats['created']++;

                    continue;
                }

                /** @var Resident $resident */
                $resident = $existing->get($pin);
                [$backfill, $conflicts] = $this->residentChanges($resident, $candidate);
                if ($conflicts !== []) {
                    $stats['conflicts']++;
                    $rowStates[$pin] = ['status' => 'conflict', 'reason' => implode(',', $conflicts)];
                } else {
                    $eligiblePins[$pin] = true;
                    if (trim((string) ($payload['Address'] ?? '')) !== '') {
                        $addresses[$pin] = trim((string) $payload['Address']);
                    }
                    $stats['matched']++;
                    $rowStates[$pin] = ['status' => 'linked', 'reason' => null];
                }

                if ($backfill !== []) {
                    $stats['backfilled']++;
                    if ($commit) {
                        $oldValues = Arr::only($resident->getAttributes(), array_keys($backfill));
                        $sourceUpdatedAt = $candidate['updated_at'];
                        if ($resident->updated_at && $resident->updated_at->greaterThan($sourceUpdatedAt)) {
                            $sourceUpdatedAt = $resident->updated_at;
                        }
                        DB::table('residents')->where('id', $resident->id)->update([
                            ...$backfill,
                            'updated_at' => $sourceUpdatedAt,
                        ]);
                        $this->recordPromotionEvent(
                            $batch,
                            Resident::class,
                            $resident->id,
                            'backfill',
                            $oldValues,
                            $backfill
                        );
                    }
                }
            }

            if ($commit && $newRecords !== []) {
                DB::table('residents')->insert(array_values($newRecords));
                $createdResidents = Resident::whereIn('resident_id', array_keys($newRecords))
                    ->get()
                    ->keyBy('resident_id');
                foreach ($createdResidents as $pin => $resident) {
                    $this->recordPromotionEvent(
                        $batch,
                        Resident::class,
                        $resident->id,
                        'create',
                        null,
                        $newRecords[$pin]
                    );
                }
                foreach ($createdResidents as $pin => $resident) {
                    $existing->put($pin, $resident);
                }
            }

            if ($commit && $rowStates !== []) {
                $now = now();
                $links = [];
                foreach ($rowStates as $pin => $state) {
                    $links[] = [
                        'source_system' => LegacyCsvImporter::SOURCE_SYSTEM,
                        'legacy_pin' => $pin,
                        'resident_id' => $existing->get($pin)?->id,
                        'source_batch_id' => $batch->id,
                        'status' => $state['status'],
                        'match_method' => $existing->has($pin) ? 'exact_resident_id' : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('legacy_resident_links')->upsert(
                    $links,
                    ['source_system', 'legacy_pin'],
                    ['resident_id', 'source_batch_id', 'status', 'match_method', 'updated_at']
                );
            }
        };

        if ($limit !== null) {
            $processRows($query->limit($limit)->get());
        } else {
            $query->chunkById(500, $processRows);
        }

        $hasMore = $limit !== null && LegacyImportRow::query()
            ->where('legacy_import_batch_id', $batch->id)
            ->where('source_table', 'personal_info')
            ->where('id', '>', $lastProcessedId)
            ->exists();

        return [
            ...$stats,
            'addresses' => $addresses,
            'eligible_pins' => array_keys($eligiblePins),
            'cursor' => $lastProcessedId,
            'has_more' => $hasMore,
        ];
    }

    private function residentCandidate(array $payload, array $context): ?array
    {
        $pin = trim((string) ($payload['PIN'] ?? ''));
        $firstName = trim((string) ($payload['Firstname'] ?? ''));
        $lastName = trim((string) ($payload['Lastname'] ?? ''));
        $birthDate = $this->parseDate($payload['Birthdate'] ?? null);
        $gender = $this->normalizeGender($payload['Gender'] ?? null);

        if ($pin === '' || $firstName === '' || $lastName === '' || ! $birthDate || ! $gender) {
            return null;
        }

        $lockStatus = strtolower(trim((string) ($payload['LockStatus'] ?? '0')));
        $locked = in_array($lockStatus, ['1', 'true', 'yes', 'locked'], true);
        $sourceUpdatedAt = $this->parseDateTime($payload['Date_Modified'] ?? null) ?? now();
        $incomeCode = trim((string) ($payload['SourceIncome_id'] ?? ''));
        $educationCode = trim((string) ($payload['Educational_id'] ?? ''));
        $disabilityCode = trim((string) ($payload['Disability_id'] ?? ''));

        return [
            'resident_id' => $pin,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => $this->nullable($payload['Middlename'] ?? null),
            'birth_date' => $birthDate->toDateString(),
            'gender' => $gender,
            'civil_status' => $this->normalizeCivilStatus(
                $payload['CivilStatus'] ?? null,
                $context['civil_status']
            ),
            'source_income_type_id' => $context['income_ids'][$incomeCode] ?? null,
            'educational_attainment' => $context['education'][$educationCode] ?? null,
            'is_pwd' => $disabilityCode !== '' && $disabilityCode !== '0',
            'is_active' => ! $locked,
            'ethnicity' => $this->nullable($payload['Ethnicity'] ?? null),
            'locked_at' => $locked ? $this->parseDateTime($payload['Lockdate'] ?? null) : null,
            'created_at' => $sourceUpdatedAt,
            'updated_at' => $sourceUpdatedAt,
        ];
    }

    private function residentChanges(Resident $resident, array $candidate): array
    {
        $backfill = [];
        $conflicts = [];
        $comparable = Arr::except($candidate, ['resident_id', 'created_at', 'updated_at']);

        foreach ($comparable as $field => $incoming) {
            if ($incoming === null || $incoming === '') {
                continue;
            }
            $current = $resident->{$field};
            if ($current === null || $current === '') {
                $backfill[$field] = $incoming;

                continue;
            }

            $currentNormalized = $this->normalizeComparable($current);
            $incomingNormalized = $this->normalizeComparable($incoming);
            if ($currentNormalized !== $incomingNormalized) {
                $conflicts[] = $field;
            }
        }

        return [$backfill, $conflicts];
    }

    private function promoteProvisionalHouseholds(
        LegacyImportBatch $batch,
        array $addresses,
        array $eligiblePins,
        array $context,
        bool $commit
    ): array {
        $candidatePins = array_values(array_intersect($eligiblePins, array_keys($addresses)));
        $stats = [
            'provisional_source_residents' => count($candidatePins),
            'provisional_created' => 0,
            'provisional_matched' => 0,
            'provisional_members_linked' => 0,
            'provisional_existing_household_preserved' => 0,
            'provisional_without_resident' => 0,
            'provisional_conflicts' => 0,
        ];

        foreach (array_chunk($candidatePins, 500) as $pins) {
            $householdKeys = collect($pins)
                ->mapWithKeys(fn (string $pin) => [$pin => $this->provisionalHouseholdKey($pin)])
                ->all();
            $residents = Resident::whereIn('resident_id', $pins)->get()->keyBy('resident_id');
            $currentHouseholds = Household::withTrashed()
                ->whereIn('id', $residents->pluck('household_id')->filter()->unique())
                ->get()
                ->keyBy('id');
            $households = Household::withTrashed()
                ->whereIn('household_id', array_values($householdKeys))
                ->get()
                ->keyBy('household_id');
            $createdPins = [];
            $householdRows = [];
            $now = now();

            foreach ($pins as $pin) {
                /** @var Resident|null $resident */
                $resident = $residents->get($pin);
                if ($commit && ! $resident) {
                    $stats['provisional_without_resident']++;

                    continue;
                }

                if ($resident?->household_id) {
                    $current = $currentHouseholds->get($resident->household_id);
                    if ($current?->is_provisional && $current->provisional_for_pin === $pin) {
                        $stats['provisional_matched']++;
                    } else {
                        $stats['provisional_existing_household_preserved']++;
                    }

                    continue;
                }

                $householdKey = $householdKeys[$pin];
                /** @var Household|null $household */
                $household = $households->get($householdKey);
                if ($household && (! $household->is_provisional || $household->provisional_for_pin !== $pin)) {
                    $stats['provisional_conflicts']++;

                    continue;
                }

                if ($household) {
                    $stats['provisional_matched']++;
                    if ($commit && $household->trashed()) {
                        DB::table('households')->where('id', $household->id)->update([
                            'is_active' => true,
                            'deleted_at' => null,
                            'updated_at' => $now,
                        ]);
                    }
                } else {
                    $stats['provisional_created']++;
                    $createdPins[$pin] = true;
                    if ($commit) {
                        [$barangay, $barangayCode] = $this->barangayFromAddress($addresses[$pin], $context['barangays']);
                        $householdRows[] = [
                            'household_id' => $householdKey,
                            'building_registry_number' => null,
                            'is_provisional' => true,
                            'provisional_for_pin' => $pin,
                            'address' => $addresses[$pin],
                            'barangay' => $barangay ?? 'Unknown',
                            'barangay_code' => $barangayCode,
                            'city_municipality' => LocationsSeeder::CITY_NAME,
                            'city_municipality_code' => LocationsSeeder::CITY_CODE,
                            'province' => LocationsSeeder::PROVINCE_NAME,
                            'province_code' => LocationsSeeder::PROVINCE_CODE,
                            'postal_code' => LocationsSeeder::POSTAL_CODE,
                            'region' => LocationsSeeder::REGION_NAME,
                            'region_code' => LocationsSeeder::REGION_CODE,
                            'monthly_income' => $resident?->monthly_income,
                            'member_count' => 1,
                            'has_electricity' => true,
                            'has_water_supply' => true,
                            'is_active' => true,
                            'notes' => 'Provisional household created from legacy personal information.',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                $stats['provisional_members_linked']++;
            }

            if (! $commit) {
                continue;
            }

            if ($householdRows !== []) {
                DB::table('households')->insertOrIgnore($householdRows);
            }

            $households = Household::withTrashed()
                ->whereIn('household_id', array_values($householdKeys))
                ->get()
                ->keyBy('household_id');
            $residentUpdates = [];
            $linkRows = [];
            $eventRows = [];
            foreach ($pins as $pin) {
                /** @var Resident|null $resident */
                $resident = $residents->get($pin);
                /** @var Household|null $household */
                $household = $households->get($householdKeys[$pin]);
                if (! $resident || $resident->household_id || ! $household?->is_provisional
                    || $household->provisional_for_pin !== $pin) {
                    continue;
                }

                $residentUpdates[] = [
                    'id' => $resident->id,
                    'resident_id' => $resident->resident_id,
                    'first_name' => $resident->first_name,
                    'last_name' => $resident->last_name,
                    'birth_date' => $resident->birth_date->toDateString(),
                    'household_id' => $household->id,
                    'updated_at' => $resident->updated_at,
                ];
                $linkRows[] = [
                    'source_system' => LegacyCsvImporter::SOURCE_SYSTEM,
                    'legacy_family_number' => 'PIN:'.$pin,
                    'legacy_building_registry_number' => null,
                    'household_id' => $household->id,
                    'source_batch_id' => $batch->id,
                    'status' => 'provisional',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (isset($createdPins[$pin])) {
                    $eventRows[] = $this->promotionEventRow(
                        $batch,
                        Household::class,
                        $household->id,
                        'create_provisional',
                        null,
                        $household->getAttributes(),
                        $now
                    );
                }
                $eventRows[] = $this->promotionEventRow(
                    $batch,
                    Resident::class,
                    $resident->id,
                    'link_provisional_household',
                    ['household_id' => null],
                    ['household_id' => $household->id],
                    $now
                );
            }

            if ($residentUpdates !== []) {
                DB::table('residents')->upsert($residentUpdates, ['id'], ['household_id', 'updated_at']);
            }
            if ($linkRows !== []) {
                DB::table('legacy_household_links')->upsert(
                    $linkRows,
                    ['source_system', 'legacy_family_number'],
                    ['household_id', 'source_batch_id', 'status', 'updated_at']
                );
            }
            if ($eventRows !== []) {
                DB::table('legacy_promotion_events')->insert($eventRows);
            }
        }

        return $stats;
    }

    private function provisionalHouseholdKey(string $pin): string
    {
        return 'LEGACY-PIN-'.$pin;
    }

    private function promoteHouseholds(
        LegacyImportBatch $batch,
        array $addresses,
        array $eligiblePins,
        array $context,
        bool $commit,
        ?array $onlyFamilies = null,
    ): array {
        $stats = [
            'source_rows' => 0,
            'created' => 0,
            'matched' => 0,
            'members_linked' => 0,
            'incomplete' => 0,
            'conflicts' => 0,
            'address_variations' => 0,
            ...$this->promoteProvisionalHouseholds($batch, $addresses, $eligiblePins, $context, $commit),
        ];
        $families = [];
        $pinFamilies = [];
        $provisionalHouseholdIds = [];

        $familyRows = LegacyImportRow::query()
            ->where('legacy_import_batch_id', $batch->id)
            ->where('source_table', 'family_members');
        if ($onlyFamilies !== null) {
            $familyRows->whereIn('raw_payload->FamilyNumber', $onlyFamilies);
        }

        foreach ($familyRows->lazyById(1000) as $row) {
            $stats['source_rows']++;
            if ($row->validation_status !== 'valid') {
                continue;
            }
            $payload = $row->raw_payload;
            $familyNumber = trim((string) ($payload['FamilyNumber'] ?? ''));
            $pin = trim((string) ($payload['PIN'] ?? ''));
            if ($familyNumber === '' || $pin === '') {
                continue;
            }
            $families[$familyNumber][] = $payload;
            $pinFamilies[$pin][$familyNumber] = true;
        }

        if ($onlyFamilies !== null && $pinFamilies !== []) {
            $selectedPins = array_fill_keys(array_keys($pinFamilies), true);
            $pinFamilies = [];
            foreach ($this->sourceRows($batch, 'family_members') as $row) {
                if ($row->validation_status !== 'valid') {
                    continue;
                }
                $payload = $row->raw_payload;
                $pin = trim((string) ($payload['PIN'] ?? ''));
                $familyNumber = trim((string) ($payload['FamilyNumber'] ?? ''));
                if (isset($selectedPins[$pin]) && $familyNumber !== '') {
                    $pinFamilies[$pin][$familyNumber] = true;
                }
            }
        }

        $conflictingPins = array_filter($pinFamilies, fn ($familySet) => count($familySet) > 1);
        $addresses = $this->resolveStagedPersonalAddresses($addresses, array_keys($pinFamilies));

        foreach ($families as $familyNumber => $members) {
            $memberPins = array_values(array_unique(array_column($members, 'PIN')));
            $usablePins = array_values(array_filter(
                $memberPins,
                fn ($pin) => ! isset($conflictingPins[$pin])
            ));
            $familyAddresses = array_values(array_unique(array_filter(array_map(
                fn ($pin) => $addresses[$pin] ?? null,
                $usablePins
            ))));
            $buildingNumbers = array_values(array_unique(array_filter(array_map(
                fn ($member) => $this->nullableBuildingNumber($member['Building_RegistryNumber'] ?? null),
                $members
            ))));

            if (count($buildingNumbers) > 1) {
                $stats['conflicts']++;
                if ($commit) {
                    $this->upsertHouseholdLink($batch, $familyNumber, $buildingNumbers[0] ?? null, null, 'conflict');
                }

                continue;
            }

            if (count($familyAddresses) > 1) {
                $stats['address_variations']++;
            }

            $address = $familyAddresses[0] ?? null;
            if (! $address) {
                $stats['incomplete']++;
                if ($commit) {
                    $this->upsertHouseholdLink($batch, $familyNumber, $buildingNumbers[0] ?? null, null, 'incomplete');
                }

                continue;
            }

            $household = Household::withTrashed()->where('household_id', $familyNumber)->first();
            if (! $household) {
                $stats['created']++;
                if ($commit) {
                    [$barangay, $barangayCode] = $this->barangayFromAddress($address, $context['barangays']);
                    $householdId = DB::table('households')->insertGetId([
                        'household_id' => $familyNumber,
                        'building_registry_number' => $buildingNumbers[0] ?? null,
                        'address' => $address,
                        'barangay' => $barangay ?? 'Unknown',
                        'barangay_code' => $barangayCode,
                        'city_municipality' => LocationsSeeder::CITY_NAME,
                        'city_municipality_code' => LocationsSeeder::CITY_CODE,
                        'province' => LocationsSeeder::PROVINCE_NAME,
                        'province_code' => LocationsSeeder::PROVINCE_CODE,
                        'postal_code' => LocationsSeeder::POSTAL_CODE,
                        'region' => LocationsSeeder::REGION_NAME,
                        'region_code' => LocationsSeeder::REGION_CODE,
                        'member_count' => 0,
                        'has_electricity' => true,
                        'has_water_supply' => true,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $household = Household::findOrFail($householdId);
                    $this->recordPromotionEvent(
                        $batch,
                        Household::class,
                        $household->id,
                        'create',
                        null,
                        $household->getAttributes()
                    );
                }
            } else {
                $stats['matched']++;
            }

            if (! $commit || ! $household) {
                continue;
            }

            $this->upsertHouseholdLink(
                $batch,
                $familyNumber,
                $buildingNumbers[0] ?? null,
                $household->id,
                'linked'
            );

            $residents = Resident::with('household')->whereIn('resident_id', $usablePins)->get();
            foreach ($residents as $resident) {
                if ($resident->household_id && $resident->household_id !== $household->id) {
                    $currentHousehold = $resident->household;
                    $isOwnProvisionalHousehold = $currentHousehold?->is_provisional
                        && $currentHousehold->provisional_for_pin === $resident->resident_id;

                    if (! $isOwnProvisionalHousehold) {
                        $stats['conflicts']++;

                        continue;
                    }

                    $provisionalHouseholdIds[$currentHousehold->id] = true;
                }

                if ($resident->household_id !== $household->id) {
                    $oldHouseholdId = $resident->household_id;
                    DB::table('residents')->where('id', $resident->id)->update([
                        'household_id' => $household->id,
                    ]);
                    $this->recordPromotionEvent(
                        $batch,
                        Resident::class,
                        $resident->id,
                        $oldHouseholdId ? 'replace_provisional_household' : 'link_household',
                        ['household_id' => $oldHouseholdId],
                        ['household_id' => $household->id]
                    );
                    $stats['members_linked']++;
                }
            }

            $memberCount = Resident::where('household_id', $household->id)->where('is_active', true)->count();
            $income = Resident::where('household_id', $household->id)->where('is_active', true)->sum('monthly_income');
            DB::table('households')->where('id', $household->id)->update([
                'member_count' => $memberCount,
                'monthly_income' => $income,
                'updated_at' => now(),
            ]);
        }

        $stats['provisional_archived'] = $this->archiveEmptyProvisionalHouseholds(
            $batch,
            array_keys($provisionalHouseholdIds),
            $commit
        );
        $stats['conflicting_member_pins'] = count($conflictingPins);

        return $stats;
    }

    /**
     * Reuse the newest valid staged personal address when family membership is
     * imported in a later batch than personal information.
     */
    private function resolveStagedPersonalAddresses(array $addresses, array $pins): array
    {
        $unresolved = array_fill_keys(array_diff($pins, array_keys($addresses)), true);
        if ($unresolved === []) {
            return $addresses;
        }

        foreach (LegacyImportRow::query()
            ->select(['id', 'natural_key', 'raw_payload'])
            ->where('source_table', 'personal_info')
            ->where('validation_status', 'valid')
            ->lazyByIdDesc(1000) as $row) {
            $pin = trim((string) $row->natural_key);
            if ($pin === '' || ! isset($unresolved[$pin])) {
                continue;
            }

            $address = trim((string) ($row->raw_payload['Address'] ?? ''));
            if ($address === '') {
                continue;
            }

            $addresses[$pin] = $address;
            unset($unresolved[$pin]);
            if ($unresolved === []) {
                break;
            }
        }

        return $addresses;
    }

    private function promoteBhwAssignments(
        LegacyImportBatch $batch,
        array $context,
        bool $commit
    ): array {
        $stats = [
            'source_rows' => 0,
            'zones_created' => 0,
            'assignments_created' => 0,
            'assignments_updated' => 0,
            'assignments_removed' => 0,
            'unresolved_residents' => 0,
            'resident_bhw_flagged' => 0,
            'resident_bhw_unflagged' => 0,
            'duplicate_zone_pins' => 0,
            'incomplete' => 0,
            'conflicts' => 0,
        ];
        $affectedResidentIds = [];
        $plannedBhwResidentIds = [];

        $bhwRows = LegacyImportRow::query()
            ->where('legacy_import_batch_id', $batch->id)
            ->where('source_table', 'bhw_master')
            ->orderBy('id')
            ->get();

        foreach ($bhwRows as $row) {
            $stats['source_rows']++;
            $payload = $row->raw_payload;
            $legacyCode = $this->normalizeLegacyCode($payload['Barangay_Code'] ?? '');
            $zoneId = trim((string) ($payload['ZoneID'] ?? ''));
            $zoneName = trim((string) ($payload['ZoneName'] ?? ''));

            if ($row->validation_status !== 'valid' || $legacyCode === '' || $zoneId === '' || $zoneName === '') {
                $stats['incomplete']++;

                continue;
            }
            if ($legacyCode === '40') {
                $stats['conflicts']++;

                continue;
            }

            $mapping = $context['barangays'][$legacyCode] ?? null;
            if (! $mapping) {
                $stats['conflicts']++;

                continue;
            }

            $zone = BarangayZone::where([
                'source_system' => LegacyCsvImporter::SOURCE_SYSTEM,
                'legacy_barangay_code' => $legacyCode,
                'legacy_zone_id' => $zoneId,
                'name' => $zoneName,
            ])->first();

            if (! $zone) {
                $stats['zones_created']++;
                if ($commit) {
                    $zone = BarangayZone::create([
                        'source_system' => LegacyCsvImporter::SOURCE_SYSTEM,
                        'legacy_barangay_code' => $legacyCode,
                        'legacy_zone_id' => $zoneId,
                        'name' => $zoneName,
                        'brgy_code' => $mapping['brgy_code'],
                    ]);
                }
            }

            $slotPins = [];
            foreach (['PIN' => 'primary', 'PIN2' => 'secondary'] as $field => $slot) {
                $pin = trim((string) ($payload[$field] ?? ''));
                if ($pin === '' || strtoupper($pin) === 'NULL') {
                    $slotPins[$slot] = null;

                    continue;
                }
                $slotPins[$slot] = $pin;
            }

            if ($slotPins['primary'] && $slotPins['primary'] === $slotPins['secondary']) {
                $slotPins['secondary'] = null;
                $stats['duplicate_zone_pins']++;
                $stats['conflicts']++;
            }

            $existingAssignments = $zone
                ? BarangayHealthWorkerAssignment::where('barangay_zone_id', $zone->id)
                    ->whereIn('assignment_slot', ['primary', 'secondary'])
                    ->get()
                    ->keyBy('assignment_slot')
                : collect();
            foreach ($existingAssignments as $assignment) {
                if ($assignment->resident_id) {
                    $affectedResidentIds[$assignment->resident_id] = true;
                }
            }

            $resolvedSlots = [];
            foreach ($slotPins as $slot => $pin) {
                $existingAssignment = $existingAssignments->get($slot);
                if (! $pin) {
                    if ($existingAssignment) {
                        $stats['assignments_removed']++;
                    }

                    continue;
                }

                $resident = Resident::where('resident_id', $pin)->first();
                if (! $resident) {
                    $stats['unresolved_residents']++;
                } else {
                    $affectedResidentIds[$resident->id] = true;
                    $plannedBhwResidentIds[$resident->id] = true;
                }
                if ($existingAssignment) {
                    $stats['assignments_updated']++;
                } else {
                    $stats['assignments_created']++;
                }
                $resolvedSlots[$slot] = ['pin' => $pin, 'resident' => $resident];
            }

            if ($commit && $zone) {
                BarangayHealthWorkerAssignment::where('barangay_zone_id', $zone->id)
                    ->whereIn('assignment_slot', ['primary', 'secondary'])
                    ->delete();
                foreach ($resolvedSlots as $slot => $assignment) {
                    BarangayHealthWorkerAssignment::create([
                        'barangay_zone_id' => $zone->id,
                        'resident_id' => $assignment['resident']?->id,
                        'source_batch_id' => $batch->id,
                        'legacy_pin' => $assignment['pin'],
                        'assignment_slot' => $slot,
                    ]);
                }
            }
        }

        if (! $commit) {
            $stats['resident_bhw_flagged'] = count($plannedBhwResidentIds);
        }

        if ($commit && $affectedResidentIds !== []) {
            $residents = Resident::whereIn('id', array_keys($affectedResidentIds))->get();
            $assignedResidentIds = BarangayHealthWorkerAssignment::whereIn(
                'resident_id',
                array_keys($affectedResidentIds)
            )->whereNotNull('resident_id')->pluck('resident_id')->unique()->flip();

            foreach ($residents as $resident) {
                $shouldBeBhw = $assignedResidentIds->has($resident->id);
                if ($resident->is_bhw === $shouldBeBhw) {
                    if ($shouldBeBhw) {
                        $stats['resident_bhw_flagged']++;
                    }

                    continue;
                }

                DB::table('residents')->where('id', $resident->id)->update(['is_bhw' => $shouldBeBhw]);
                $this->recordPromotionEvent(
                    $batch,
                    Resident::class,
                    $resident->id,
                    $shouldBeBhw ? 'flag_bhw' : 'unflag_bhw',
                    ['is_bhw' => $resident->is_bhw],
                    ['is_bhw' => $shouldBeBhw]
                );
                if ($shouldBeBhw) {
                    $stats['resident_bhw_flagged']++;
                } else {
                    $stats['resident_bhw_unflagged']++;
                }
            }
        }

        return $stats;
    }

    private function sourceRows(LegacyImportBatch $batch, string $sourceTable)
    {
        return LegacyImportRow::query()
            ->where('legacy_import_batch_id', $batch->id)
            ->where('source_table', $sourceTable)
            ->lazyById(1000);
    }

    private function upsertHouseholdLink(
        LegacyImportBatch $batch,
        string $familyNumber,
        ?string $buildingNumber,
        ?int $householdId,
        string $status
    ): void {
        DB::table('legacy_household_links')->upsert([[
            'source_system' => LegacyCsvImporter::SOURCE_SYSTEM,
            'legacy_family_number' => $familyNumber,
            'legacy_building_registry_number' => $buildingNumber,
            'household_id' => $householdId,
            'source_batch_id' => $batch->id,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]], ['source_system', 'legacy_family_number'], [
            'legacy_building_registry_number', 'household_id', 'source_batch_id', 'status', 'updated_at',
        ]);
    }

    private function archiveEmptyProvisionalHouseholds(
        LegacyImportBatch $batch,
        array $householdIds,
        bool $commit
    ): int {
        $archived = 0;

        foreach (array_chunk($householdIds, 500) as $ids) {
            $households = Household::query()
                ->whereIn('id', $ids)
                ->where('is_provisional', true)
                ->whereDoesntHave('residents')
                ->whereDoesntHave('distributions')
                ->get();
            $archived += $households->count();

            if (! $commit || $households->isEmpty()) {
                continue;
            }

            $now = now();
            DB::table('households')->whereIn('id', $households->pluck('id'))->update([
                'is_active' => false,
                'deleted_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('legacy_promotion_events')->insert(
                $households->map(fn (Household $household) => $this->promotionEventRow(
                    $batch,
                    Household::class,
                    $household->id,
                    'archive_provisional',
                    ['is_active' => true, 'deleted_at' => null],
                    ['is_active' => false, 'deleted_at' => $now],
                    $now
                ))->all()
            );
        }

        return $archived;
    }

    private function promotionEventRow(
        LegacyImportBatch $batch,
        string $modelType,
        int $modelId,
        string $action,
        ?array $oldValues,
        ?array $newValues,
        Carbon $now
    ): array {
        return [
            'legacy_import_batch_id' => $batch->id,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'action' => $action,
            'old_values' => $oldValues === null ? null : json_encode($oldValues, JSON_UNESCAPED_UNICODE),
            'new_values' => $newValues === null ? null : json_encode($newValues, JSON_UNESCAPED_UNICODE),
            'promoted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function recordPromotionEvent(
        LegacyImportBatch $batch,
        string $modelType,
        int $modelId,
        string $action,
        ?array $oldValues,
        ?array $newValues
    ): void {
        $now = now();
        DB::table('legacy_promotion_events')->insert($this->promotionEventRow(
            $batch,
            $modelType,
            $modelId,
            $action,
            $oldValues,
            $newValues,
            $now
        ));
    }

    private function parseDate(mixed $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '' || strtoupper($value) === 'NULL') {
            return null;
        }

        foreach (['Y-m-d', 'm/d/Y', 'n/j/Y', 'Y-m-d H:i:s'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false && $date->format($format) === $value) {
                    return $date;
                }
            } catch (Throwable) {
                // Try the next known source format.
            }
        }

        return null;
    }

    private function parseDateTime(mixed $value): ?Carbon
    {
        $value = trim((string) $value);
        if ($value === '' || strtoupper($value) === 'NULL') {
            return null;
        }

        foreach (['Y-m-d H:i:s', 'Y-m-d', 'm/d/Y H:i:s', 'm/d/Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);
                if ($date !== false) {
                    return $date;
                }
            } catch (Throwable) {
                // Try the next known source format.
            }
        }

        return null;
    }

    private function normalizeGender(mixed $value): ?string
    {
        return match (strtolower(trim((string) $value))) {
            'm', 'male' => 'male',
            'f', 'female' => 'female',
            'o', 'other', 'non-binary', 'nonbinary' => 'other',
            default => null,
        };
    }

    private function normalizeCivilStatus(mixed $value, array $statusMap = self::CIVIL_STATUS_MAP): ?string
    {
        $key = strtolower(trim((string) $value));

        return $statusMap[$key] ?? null;
    }

    private function normalizeComparable(mixed $value): string
    {
        if ($value instanceof Carbon) {
            return $value->toDateString();
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return strtolower(trim((string) $value));
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || strtoupper($value) === 'NULL' || strtoupper($value) === 'N/A'
            ? null
            : $value;
    }

    private function nullableBuildingNumber(mixed $value): ?string
    {
        $value = $this->nullable($value);

        return $value === '0' ? null : $value;
    }

    private function normalizeLegacyCode(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || strtoupper($value) === 'NULL') {
            return '';
        }

        return ltrim($value, '0') ?: '0';
    }

    private function resolvePsgcBarangayCode(string $legacyName): ?string
    {
        $needle = $this->normalizeBarangayName($legacyName);
        $this->psgcBarangayRecords ??= LocationsSeeder::getBarangayRecords();
        foreach ($this->psgcBarangayRecords as $record) {
            if ($this->normalizeBarangayName($record['name']) === $needle) {
                return $record['code'];
            }
        }

        return null;
    }

    private function normalizeBarangayName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = str_replace(['sta.', 'sta ', 'pocal-pocal', 'tawin-tawin'], ['santa', 'santa ', 'pocalpocal', 'tawintawin'], $name);
        $name = preg_replace('/\s*\(r\.?\s*magsaysay\)\s*/i', '', $name);

        return preg_replace('/[^a-z0-9]+/', '', $name) ?? '';
    }

    private function barangayFromAddress(string $address, array $mappings): array
    {
        if (preg_match('/Barangay\s+([^,]+)/i', $address, $matches)) {
            $name = trim($matches[1]);
            foreach ($mappings as $mapping) {
                if ($this->normalizeBarangayName($mapping['name']) === $this->normalizeBarangayName($name)) {
                    return [$mapping['name'], $mapping['brgy_code']];
                }
            }

            return [$name, $this->resolvePsgcBarangayCode($name)];
        }

        foreach ($mappings as $mapping) {
            if (str_contains($this->normalizeBarangayName($address), $this->normalizeBarangayName($mapping['name']))) {
                return [$mapping['name'], $mapping['brgy_code']];
            }
        }

        return [null, null];
    }
}
