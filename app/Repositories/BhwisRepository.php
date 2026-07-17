<?php

namespace App\Repositories;

use App\Services\LocalPcDatabase;

class BhwisRepository
{
    private const PERSONAL_COLUMNS = 'PIN, Lastname, Firstname, Middlename, Birthdate, CivilStatus, Address, Gender, SpouseName, FatherName, MotherName, LockStatus, Lockdate, Date_Modified, Userid, Disability_id, TypeSchool_id, SourceIncome_id, Educational_id, Ethnicity';

    public function __construct(private readonly LocalPcDatabase $database) {}

    public function testConnection(): array
    {
        return $this->database->testConnection();
    }

    public function getPersonalInfoByPin(string $pin): ?array
    {
        return $this->database->first(
            'SELECT TOP 1 '.self::PERSONAL_COLUMNS.' FROM dbo.tblPersonalInfo WHERE LTRIM(RTRIM(PIN)) = ?',
            [trim($pin)]
        );
    }

    public function findPersonalInfo(string $pin, string $lastName, string $birthDate): ?array
    {
        return $this->database->first(
            'SELECT TOP 1 '.self::PERSONAL_COLUMNS.' FROM dbo.tblPersonalInfo WHERE LTRIM(RTRIM(PIN)) = ? AND LOWER(LTRIM(RTRIM(Lastname))) = LOWER(?) AND CAST(Birthdate AS date) = ?',
            [trim($pin), trim($lastName), $birthDate]
        );
    }

    public function getFamilyMembersByPin(string $pin): array
    {
        return $this->database->select(
            'SELECT FamilyNumber, Building_RegistryNumber, PIN, Date_Update, HouseHoldHead, FamilyHead, Relationship_id FROM dbo.tblFamilyMembers WHERE LTRIM(RTRIM(PIN)) = ?',
            [trim($pin)]
        );
    }

    public function getFamilyMembersByFamilyNumber(string $familyNumber): array
    {
        return $this->database->select(
            'SELECT FamilyNumber, Building_RegistryNumber, PIN, Date_Update, HouseHoldHead, FamilyHead, Relationship_id FROM dbo.tblFamilyMembers WHERE LTRIM(RTRIM(FamilyNumber)) = ?',
            [trim($familyNumber)]
        );
    }

    public function getBhwAssignments(string $pin): array
    {
        return $this->database->select(
            'SELECT ZoneID, PIN, Barangay_Code, ZoneName, PIN2 FROM dbo.tblBHWMaster WHERE LTRIM(RTRIM(PIN)) = ? OR LTRIM(RTRIM(PIN2)) = ?',
            [trim($pin), trim($pin)]
        );
    }

    public function getBarangay(string $code): array
    {
        return $this->database->select('SELECT Barangay_Code, Barangay FROM dbo.tblBarangay WHERE LTRIM(RTRIM(Barangay_Code)) = ?', [trim($code)]);
    }

    public function getCivilStatus(mixed $code): array
    {
        return $this->reference('tblCivilStatus', 'CivilStatus_Code', ['CivilStatus_Code', 'CivilStatus'], $code);
    }

    public function getSourceIncomeType(mixed $code): array
    {
        return $this->reference('tblSourceIncomeType', 'IncomeCode', ['IncomeCode', 'Income_description'], $code);
    }

    public function getEducationalAttainment(mixed $code): array
    {
        return $this->reference('tblEduc_Attainment', 'Educ_ID', ['Educ_ID', 'Educ_Attainment'], $code);
    }

    public function missingRequiredSchema(): array
    {
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

        foreach ($required as $table => $columns) {
            $rows = $this->database->select(
                'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
                ['dbo', $table]
            );
            $present = array_column($rows, 'COLUMN_NAME');
            if ($present === []) {
                $missing[] = $table;

                continue;
            }
            foreach (array_diff($columns, $present) as $column) {
                $missing[] = $table.'.'.$column;
            }
        }

        return $missing;
    }

    private function reference(string $table, string $key, array $columns, mixed $value): array
    {
        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        return $this->database->select(
            sprintf('SELECT %s FROM dbo.%s WHERE LTRIM(RTRIM(%s)) = ?', implode(', ', $columns), $table, $key),
            [$value]
        );
    }
}
