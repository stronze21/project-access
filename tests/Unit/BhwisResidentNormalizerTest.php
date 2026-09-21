<?php

namespace Tests\Unit;

use App\Services\Bhwis\BhwisResidentNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BhwisResidentNormalizerTest extends TestCase
{
    public function test_live_and_csv_rows_produce_the_same_canonical_payload(): void
    {
        $normalizer = new BhwisResidentNormalizer;
        $row = [
            'PIN' => ' 00-123 ', 'Lastname' => ' Santos ', 'Firstname' => ' Maria ',
            'Middlename' => '', 'Birthdate' => '1990-05-21 00:00:00', 'Gender' => 'F',
            'CivilStatus' => 'Married', 'Address' => ' Poblacion ', 'Disability_id' => '0',
            'Ethnicity' => '', 'Date_Modified' => '2026-07-01 08:30:00',
        ];

        $live = $normalizer->normalize($row, 'bhwis_live');
        $csv = $normalizer->normalize($row, 'bhwis_csv');

        $this->assertSame('bhwis_live', $live['source']);
        $this->assertSame('bhwis_csv', $csv['source']);
        unset($live['source'], $csv['source']);
        $this->assertSame($live, $csv);
        $this->assertSame('00-123', $live['resident_id']);
        $this->assertSame('1990-05-21', $live['birth_date']);
        $this->assertNull($live['middle_name']);
        $this->assertTrue($normalizer->isImportable($live));
    }

    public function test_bhwis_csv_slash_dates_are_read_as_month_day_year(): void
    {
        $normalizer = new BhwisResidentNormalizer;

        $row = [
            'PIN' => '06-26220',
            'Lastname' => 'Agra',
            'Firstname' => 'Merly',
            'Birthdate' => '5/23/1963',
            'Gender' => 'Female',
            'CivilStatus' => 'Married',
            'Address' => 'Zone I',
            'Disability_id' => '0',
            'Date_Modified' => '7/1/2026 08:30:00',
        ];

        $payload = $normalizer->normalize($row, 'bhwis_csv');

        $this->assertSame('1963-05-23', $payload['birth_date']);
        $this->assertSame('2026-07-01 08:30:00', $payload['date_modified']);
        $this->assertTrue($normalizer->isImportable($payload));
    }

    #[DataProvider('invalidRows')]
    public function test_missing_required_fields_are_not_importable(array $row): void
    {
        $normalizer = new BhwisResidentNormalizer;

        $this->assertFalse($normalizer->isImportable($normalizer->normalize($row, 'bhwis_csv')));
    }

    public static function invalidRows(): array
    {
        return [
            'missing pin' => [[
                'Firstname' => 'Maria', 'Lastname' => 'Santos', 'Birthdate' => '1990-05-21',
            ]],
            'invalid birthdate' => [[
                'PIN' => '1', 'Firstname' => 'Maria', 'Lastname' => 'Santos', 'Birthdate' => 'not-a-date',
            ]],
        ];
    }
}
