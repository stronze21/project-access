<?php

namespace App\Services\Bhwis;

use Carbon\CarbonImmutable;
use Throwable;

class BhwisResidentNormalizer
{
    /** @return array<string, mixed> */
    public function normalize(array $row, string $source): array
    {
        $pin = $this->string($this->value($row, 'PIN', 'resident_id'));
        $firstName = $this->string($this->value($row, 'Firstname', 'first_name'));
        $lastName = $this->string($this->value($row, 'Lastname', 'last_name'));

        return [
            'resident_id' => $pin,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => $this->string($this->value($row, 'Middlename', 'middle_name')),
            'birth_date' => $this->date($this->value($row, 'Birthdate', 'birth_date')),
            'gender' => $this->gender($this->value($row, 'Gender', 'gender')),
            'source_gender' => $this->string($this->value($row, 'Gender', 'gender')),
            'civil_status' => $this->civilStatus($this->value($row, 'CivilStatus', 'civil_status')),
            'source_civil_status' => $this->string($this->value($row, 'CivilStatus', 'civil_status')),
            'address' => $this->string($this->value($row, 'Address', 'address')),
            'is_pwd' => ! in_array($this->string($this->value($row, 'Disability_id')) ?? '', ['', '0'], true),
            'ethnicity' => $this->string($this->value($row, 'Ethnicity', 'ethnicity')),
            'locked_at' => $this->locked($row) ? $this->dateTime($this->value($row, 'Lockdate', 'locked_at')) : null,
            'is_active' => ! $this->locked($row),
            'source_income_code' => $this->string($this->value($row, 'SourceIncome_id')),
            'education_code' => $this->string($this->value($row, 'Educational_id')),
            'date_modified' => $this->dateTime($this->value($row, 'Date_Modified', 'date_modified')),
            'source' => $source,
        ];
    }

    public function isImportable(array $payload): bool
    {
        return filled($payload['resident_id'] ?? null)
            && filled($payload['first_name'] ?? null)
            && filled($payload['last_name'] ?? null)
            && filled($payload['birth_date'] ?? null);
    }

    private function value(array $row, string ...$keys): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return $row[$key];
            }
        }

        return null;
    }

    private function string(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' || strtoupper($value) === 'NULL' ? null : $value;
    }

    private function date(mixed $value): ?string
    {
        return ($dateTime = $this->parse($value))?->toDateString();
    }

    private function dateTime(mixed $value): ?string
    {
        return ($dateTime = $this->parse($value))?->format('Y-m-d H:i:s');
    }

    private function parse(mixed $value): ?CarbonImmutable
    {
        $value = $this->string($value);
        if ($value === null) {
            return null;
        }
        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    private function gender(mixed $value): ?string
    {
        return match (strtolower($this->string($value) ?? '')) {
            'm', 'male' => 'male',
            'f', 'female' => 'female',
            '', 'null' => null,
            default => 'other',
        };
    }

    private function civilStatus(mixed $value): ?string
    {
        return match (strtolower($this->string($value) ?? '')) {
            '1', 's', 'single' => 'single',
            '2', 'm', 'married' => 'married',
            '3', 'w', 'widow', 'widowed' => 'widowed',
            '4', 'live-in/common-in-law', 'annulled' => 'other',
            '5', 'sep', 'separated' => 'separated',
            '7', 'd', 'divorced' => 'divorced',
            '' => null,
            default => 'other',
        };
    }

    private function locked(array $row): bool
    {
        return in_array(strtolower($this->string($this->value($row, 'LockStatus')) ?? ''), ['1', 'true', 'yes', 'locked'], true);
    }
}
