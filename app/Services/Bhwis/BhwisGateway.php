<?php

namespace App\Services\Bhwis;

use App\Exceptions\BhwisUnavailableException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

class BhwisGateway
{
    /** @return array<string, mixed>|null */
    public function findResident(string $pin, string $lastName, string $birthDate): ?array
    {
        $this->ensureEnabled();

        try {
            $row = $this->connection()->selectOne(
                'SELECT TOP 1 * FROM [tblPersonalInfo] WHERE LTRIM(RTRIM([PIN])) = ? AND LOWER(LTRIM(RTRIM([Lastname]))) = LOWER(?) AND CAST([Birthdate] AS date) = ?',
                [$pin, trim($lastName), $birthDate]
            );

            return $row ? (array) $row : null;
        } catch (Throwable $e) {
            throw new BhwisUnavailableException('BHWIS resident lookup failed.', previous: $e);
        }
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public function linkedRecords(string $pin, array $personal): array
    {
        try {
            $familyMemberships = $this->rows('SELECT * FROM [tblFamilyMembers] WHERE LTRIM(RTRIM([PIN])) = ?', [$pin]);
            $familyNumbers = collect($familyMemberships)
                ->pluck('FamilyNumber')->map(fn ($value) => trim((string) $value))->filter()->unique()->values();
            $family = $familyMemberships;
            foreach ($familyNumbers as $familyNumber) {
                $family = array_merge($family, $this->rows(
                    'SELECT * FROM [tblFamilyMembers] WHERE LTRIM(RTRIM([FamilyNumber])) = ?',
                    [$familyNumber]
                ));
            }

            $bhw = $this->rows(
                'SELECT * FROM [tblBHWMaster] WHERE LTRIM(RTRIM([PIN])) = ? OR LTRIM(RTRIM([PIN2])) = ?',
                [$pin, $pin]
            );
            $barangayCodes = collect($bhw)->pluck('Barangay_Code')->map(fn ($v) => trim((string) $v))->filter()->unique();
            $barangays = [];
            foreach ($barangayCodes as $code) {
                $barangays = array_merge($barangays, $this->rows(
                    'SELECT * FROM [tblBarangay] WHERE LTRIM(RTRIM([Barangay_Code])) = ?', [$code]
                ));
            }

            return [
                'personal' => [$personal],
                'family_members' => collect($family)->unique(fn ($row) => ($row['FamilyNumber'] ?? '').'|'.($row['PIN'] ?? ''))->values()->all(),
                'bhw_master' => $bhw,
                'barangay' => $barangays,
                'civil_status' => $this->reference('tblCivilStatus', 'CivilStatus_Code', $personal['CivilStatus'] ?? null),
                'source_income_type' => $this->reference('tblSourceIncomeType', 'IncomeCode', $personal['SourceIncome_id'] ?? null),
                'educational_attainment' => $this->reference('tblEduc_Attainment', 'Educ_ID', $personal['Educational_id'] ?? null),
            ];
        } catch (BhwisUnavailableException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new BhwisUnavailableException('BHWIS linked-record lookup failed.', previous: $e);
        }
    }

    /** @return array<int, string> */
    public function checkSchema(): array
    {
        $this->ensureEnabled();
        $required = [
            'tblPersonalInfo' => ['PIN', 'Lastname', 'Firstname', 'Birthdate'],
            'tblFamilyMembers' => ['FamilyNumber', 'PIN'],
            'tblBHWMaster' => ['ZoneID', 'PIN', 'Barangay_Code', 'ZoneName', 'PIN2'],
            'tblBarangay' => ['Barangay_Code', 'Barangay'],
            'tblCivilStatus' => ['CivilStatus_Code', 'CivilStatus'],
            'tblSourceIncomeType' => ['IncomeCode', 'Income_description'],
            'tblEduc_Attainment' => ['Educ_ID', 'Educ_Attainment'],
        ];
        $missing = [];

        try {
            foreach ($required as $table => $columns) {
                $present = collect($this->connection()->select(
                    'SELECT [COLUMN_NAME] FROM [INFORMATION_SCHEMA].[COLUMNS] WHERE [TABLE_NAME] = ?', [$table]
                ))->pluck('COLUMN_NAME')->all();
                if ($present === []) {
                    $missing[] = $table;

                    continue;
                }
                foreach (array_diff($columns, $present) as $column) {
                    $missing[] = $table.'.'.$column;
                }
            }
        } catch (Throwable $e) {
            throw new BhwisUnavailableException('BHWIS schema check failed.', previous: $e);
        }

        return $missing;
    }

    protected function connection(): ConnectionInterface
    {
        return DB::connection(config('bhwis.connection', 'bhwis'));
    }

    private function ensureEnabled(): void
    {
        if (! config('bhwis.enabled')) {
            throw new BhwisUnavailableException('BHWIS integration is disabled.');
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function rows(string $sql, array $bindings): array
    {
        return array_map(fn ($row) => (array) $row, $this->connection()->select($sql, $bindings));
    }

    private function reference(string $table, string $column, mixed $value): array
    {
        $value = trim((string) $value);

        return $value === '' ? [] : $this->rows(
            "SELECT * FROM [{$table}] WHERE LTRIM(RTRIM([{$column}])) = ?", [$value]
        );
    }
}
