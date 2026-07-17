<?php

namespace App\Services\Bhwis;

use App\Models\BarangayHealthWorkerAssignment;
use App\Models\BarangayZone;
use App\Models\CivilStatus;
use App\Models\EducationalAttainment;
use App\Models\Household;
use App\Models\LegacyBarangayMapping;
use App\Models\LegacyHouseholdLink;
use App\Models\LegacyResidentLink;
use App\Models\Resident;
use App\Models\SourceIncomeType;
use App\Services\Legacy\LegacyDataPromoter;
use Database\Seeders\LocationsSeeder;
use Illuminate\Support\Facades\DB;

class BhwisResidentImporter
{
    public const SOURCE_SYSTEM = 'bhwis';

    public function __construct(private readonly BhwisResidentNormalizer $normalizer) {}

    public function import(array $records): Resident
    {
        return DB::transaction(function () use ($records) {
            $personal = $records['personal'][0];
            $normalized = $this->normalizer->normalize($personal, 'bhwis_live');
            if (! $this->normalizer->isImportable($normalized)) {
                throw new \InvalidArgumentException('BHWIS resident is missing a PIN, name, or valid birthdate.');
            }
            $pin = $normalized['resident_id'];
            $civil = $this->civilStatus($records['civil_status'][0] ?? null, $personal['CivilStatus'] ?? null);
            $income = $this->incomeType($records['source_income_type'][0] ?? null, $personal['SourceIncome_id'] ?? null);
            $education = $this->education($records['educational_attainment'][0] ?? null, $personal['Educational_id'] ?? null);
            $attributes = [
                'resident_id' => $pin,
                'first_name' => $normalized['first_name'],
                'last_name' => $normalized['last_name'],
                'middle_name' => $normalized['middle_name'],
                'birth_date' => $normalized['birth_date'],
                'gender' => $normalized['gender'],
                'civil_status' => $civil,
                'source_income_type_id' => $income?->id,
                'educational_attainment' => $education?->name,
                'is_pwd' => ! in_array(trim((string) ($personal['Disability_id'] ?? '')), ['', '0'], true),
                'ethnicity' => $normalized['ethnicity'],
                'is_active' => $normalized['is_active'],
                'locked_at' => $normalized['locked_at'],
                'is_legacy_imported' => true,
            ];
            $resident = Resident::create($attributes);

            LegacyResidentLink::updateOrCreate(
                ['source_system' => self::SOURCE_SYSTEM, 'legacy_pin' => $pin],
                ['resident_id' => $resident->id, 'status' => 'linked', 'match_method' => 'activation_exact_identity']
            );
            $this->importHousehold($resident, $records['family_members'] ?? [], $records['barangay'] ?? [], $personal);
            $this->importBhw($records['bhw_master'] ?? [], $records['barangay'] ?? []);

            return $resident->fresh(['household', 'sourceIncomeType']);
        });
    }

    private function importHousehold(Resident $resident, array $rows, array $barangays, array $personal): void
    {
        $own = collect($rows)->first(fn ($row) => trim((string) ($row['PIN'] ?? '')) === $resident->resident_id);
        if (! $own || trim((string) ($own['FamilyNumber'] ?? '')) === '') {
            return;
        }
        $familyNumber = trim((string) $own['FamilyNumber']);
        $building = $this->nullable($own['Building_RegistryNumber'] ?? null);
        $address = $this->nullable($personal['Address'] ?? null);
        if (! $address) {
            return;
        }
        $household = Household::firstOrCreate(
            ['household_id' => 'LEGACY-FAMILY-'.$familyNumber],
            [
                'building_registry_number' => $building,
                'address' => $address,
                'barangay' => 'Unknown',
                'city_municipality' => LocationsSeeder::CITY_NAME,
                'province' => LocationsSeeder::PROVINCE_NAME,
                'postal_code' => LocationsSeeder::POSTAL_CODE,
                'region' => LocationsSeeder::REGION_NAME,
                'is_active' => true,
            ]
        );
        $pins = collect($rows)->where('FamilyNumber', $own['FamilyNumber'])->pluck('PIN')
            ->map(fn ($pin) => trim((string) $pin))->filter()->unique()->all();
        Resident::whereIn('resident_id', $pins)->where(fn ($q) => $q->whereNull('household_id')->orWhere('household_id', $household->id))
            ->update(['household_id' => $household->id]);
        $resident->forceFill(['household_id' => $household->id])->saveQuietly();
        LegacyHouseholdLink::updateOrCreate(
            ['source_system' => self::SOURCE_SYSTEM, 'legacy_family_number' => $familyNumber],
            ['legacy_building_registry_number' => $building, 'household_id' => $household->id, 'status' => 'linked']
        );
    }

    private function importBhw(array $rows, array $barangays): void
    {
        $names = collect($barangays)->mapWithKeys(fn ($row) => [trim((string) $row['Barangay_Code']) => trim((string) $row['Barangay'])]);
        $affectedResidentIds = [];
        foreach ($rows as $row) {
            $code = trim((string) ($row['Barangay_Code'] ?? ''));
            $zoneId = trim((string) ($row['ZoneID'] ?? ''));
            $zoneName = trim((string) ($row['ZoneName'] ?? ''));
            if ($code === '' || $zoneId === '' || $zoneName === '') {
                continue;
            }
            if ($names->has($code)) {
                LegacyBarangayMapping::updateOrCreate(
                    ['source_system' => self::SOURCE_SYSTEM, 'legacy_code' => $code],
                    ['legacy_name' => $names[$code], 'status' => 'pending']
                );
            }
            $zone = BarangayZone::firstOrCreate(
                ['source_system' => self::SOURCE_SYSTEM, 'legacy_barangay_code' => $code, 'legacy_zone_id' => $zoneId],
                ['name' => $zoneName, 'is_active' => true]
            );
            foreach (['PIN' => 'primary', 'PIN2' => 'secondary'] as $field => $slot) {
                $pin = trim((string) ($row[$field] ?? ''));
                $existing = BarangayHealthWorkerAssignment::where('barangay_zone_id', $zone->id)
                    ->where('assignment_slot', $slot)->first();
                if ($pin === '' || strtoupper($pin) === 'NULL') {
                    if ($existing?->resident_id) {
                        $affectedResidentIds[] = $existing->resident_id;
                    }
                    $existing?->delete();

                    continue;
                }
                $worker = Resident::where('resident_id', $pin)->first();
                if ($existing?->resident_id) {
                    $affectedResidentIds[] = $existing->resident_id;
                }
                BarangayHealthWorkerAssignment::updateOrCreate(
                    ['barangay_zone_id' => $zone->id, 'assignment_slot' => $slot],
                    ['legacy_pin' => $pin, 'resident_id' => $worker?->id]
                );
                if ($worker) {
                    $affectedResidentIds[] = $worker->id;
                }
            }
        }

        foreach (Resident::whereIn('id', array_unique($affectedResidentIds))->get() as $resident) {
            $resident->forceFill(['is_bhw' => $resident->bhwAssignments()->exists()])->saveQuietly();
        }
    }

    private function civilStatus(?array $row, mixed $fallback): ?string
    {
        $code = trim((string) ($row['CivilStatus_Code'] ?? $fallback));
        $name = trim((string) ($row['CivilStatus'] ?? (LegacyDataPromoter::DEFAULT_CIVIL_STATUS_REFERENCES[$code]['name'] ?? $fallback)));
        $canonical = LegacyDataPromoter::CIVIL_STATUS_MAP[strtolower($name)]
            ?? LegacyDataPromoter::CIVIL_STATUS_MAP[$code]
            ?? null;
        if (! $canonical) {
            return null;
        }
        $model = CivilStatus::updateOrCreate(
            ['legacy_code' => $code],
            ['name' => $name, 'canonical_value' => $canonical, 'is_active' => true]
        );

        return $model->canonical_value;
    }

    private function incomeType(?array $row, mixed $fallback): ?SourceIncomeType
    {
        $code = trim((string) ($row['IncomeCode'] ?? $fallback));
        $name = trim((string) ($row['Income_description'] ?? (LegacyDataPromoter::DEFAULT_INCOME_MAP[$code] ?? '')));

        return $code !== '' && $name !== '' ? SourceIncomeType::updateOrCreate(
            ['legacy_code' => $code], ['name' => $name, 'is_active' => true]
        ) : null;
    }

    private function education(?array $row, mixed $fallback): ?EducationalAttainment
    {
        $code = trim((string) ($row['Educ_ID'] ?? $fallback));
        $name = trim((string) ($row['Educ_Attainment'] ?? (LegacyDataPromoter::DEFAULT_EDUCATION_MAP[$code] ?? '')));

        return $code !== '' && $name !== '' ? EducationalAttainment::updateOrCreate(
            ['legacy_code' => $code], ['name' => $name, 'is_active' => true]
        ) : null;
    }

    private function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
