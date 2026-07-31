<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Resident;
use App\Services\Legacy\LegacyCsvImporter;
use Carbon\Carbon;
use Database\Seeders\LocationsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ResidentCsvService
{
    private ?array $managedBarangays = null;

    public function previewFromCsv(string $filePath): array
    {
        $file = fopen($filePath, 'r');
        $headers = $this->readHeaders($file);
        $preview = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
            'rows' => [],
        ];

        $missingHeaders = array_diff($this->requiredHeaders(), $headers);
        if ($missingHeaders !== []) {
            fclose($file);
            $preview['errors'][] = 'Missing required headers: '.implode(', ', $missingHeaders);

            return $preview;
        }

        $seenResidentIds = [];
        $rowNumber = 1;

        while (($data = fgetcsv($file)) !== false) {
            $rowNumber++;
            $preview['total']++;
            $rowData = $this->normalizeHouseholdLocation($this->mapCsvRow($headers, $data));
            $residentId = $rowData['resident_id'] ?? '';
            $errors = $this->validatePreviewRow($rowData);

            if ($residentId !== '' && isset($seenResidentIds[$residentId])) {
                $errors[] = "Duplicate resident_id; first appears on row {$seenResidentIds[$residentId]}.";
            }
            if ($residentId !== '') {
                $seenResidentIds[$residentId] ??= $rowNumber;
            }

            $status = 'new';
            if ($errors !== []) {
                $status = 'error';
                $preview['failed']++;
            } elseif ($residentId !== '' && Resident::where('resident_id', $residentId)->exists()) {
                $status = 'update';
                $preview['updated']++;
            } else {
                $preview['created']++;
            }

            $preview['rows'][] = [
                'row' => $rowNumber,
                'resident_id' => $residentId,
                'name' => trim(($rowData['first_name'] ?? '').' '.($rowData['last_name'] ?? '')),
                'birth_date' => $rowData['birth_date'] ?? '',
                'address' => $rowData['address'] ?? '',
                'barangay' => $rowData['barangay'] ?? '',
                'status' => $status,
                'errors' => $errors,
            ];
        }

        fclose($file);

        return $preview;
    }

    /**
     * Generate CSV content for exporting residents
     *
     * @return string
     */
    public function exportToCsv(array $filters = [])
    {
        $query = Resident::with('household');

        // Apply filters if provided
        if (! empty($filters['barangay'])) {
            $query->whereHas('household', function ($q) use ($filters) {
                $q->where('barangay', $filters['barangay']);
            });
        }

        if (! empty($filters['special_sector'])) {
            $query->where('special_sector', $filters['special_sector']);
        }

        if (isset($filters['status']) && $filters['status'] !== 'all') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        $residents = $query->get();
        $output = $this->openMemoryFile();

        // Write headers
        fputcsv($output, [
            'resident_id',
            'first_name',
            'last_name',
            'middle_name',
            'suffix',
            'birth_date',
            'birthplace',
            'gender',
            'civil_status',
            'contact_number',
            'email',
            'occupation',
            'monthly_income',
            'educational_attainment',
            'special_sector',
            'is_registered_voter',
            'is_pwd',
            'is_senior_citizen',
            'is_solo_parent',
            'is_pregnant',
            'is_lactating',
            'is_indigenous',
            'is_4ps',
            'is_scholar',
            'is_active',
            'date_issue',
            'notes',
            'address',
            'barangay',
            'city_municipality',
            'province',
            'region',
        ]);

        // Write data
        foreach ($residents as $resident) {
            fputcsv($output, [
                $resident->resident_id,
                $resident->first_name,
                $resident->last_name,
                $resident->middle_name,
                $resident->suffix,
                $resident->birth_date ? $resident->birth_date->format('Y-m-d') : '',
                $resident->birthplace,
                $resident->gender,
                $resident->civil_status,
                $resident->contact_number,
                $resident->email,
                $resident->occupation,
                $resident->monthly_income,
                $resident->educational_attainment,
                $resident->special_sector,
                $resident->is_registered_voter ? 'Yes' : 'No',
                $resident->is_pwd ? 'Yes' : 'No',
                $resident->is_senior_citizen ? 'Yes' : 'No',
                $resident->is_solo_parent ? 'Yes' : 'No',
                $resident->is_pregnant ? 'Yes' : 'No',
                $resident->is_lactating ? 'Yes' : 'No',
                $resident->is_indigenous ? 'Yes' : 'No',
                $resident->is_4ps ? 'Yes' : 'No',
                $resident->is_scholar ? 'Yes' : 'No',
                $resident->is_active ? 'Yes' : 'No',
                $resident->date_issue ? $resident->date_issue->format('Y-m-d') : '',
                $resident->notes,
                $resident->household ? $resident->household->address : '',
                $resident->household ? $resident->household->barangay : '',
                $resident->household ? $resident->household->city_municipality : '',
                $resident->household ? $resident->household->province : '',
                $resident->household ? $resident->household->region : '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Import residents from CSV file
     *
     * @param  string  $filePath
     * @return array
     */
    public function importFromCsv($filePath)
    {
        $file = fopen($filePath, 'r');
        $headers = $this->readHeaders($file);
        $stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // Validate required headers
        $missingHeaders = array_diff($this->requiredHeaders(), $headers);

        if (! empty($missingHeaders)) {
            $stats['errors'][] = 'Missing required headers: '.implode(', ', $missingHeaders);

            return $stats;
        }

        DB::beginTransaction();
        try {
            $row = 1; // Accounting for header row
            while (($data = fgetcsv($file)) !== false) {
                $row++;
                $stats['total']++;

                // Map CSV data to array with keys from headers
                $rowData = $this->normalizeHouseholdLocation($this->mapCsvRow($headers, $data));

                try {
                    // Check if resident already exists by resident_id
                    $existingResident = null;
                    if (! empty($rowData['resident_id'])) {
                        $existingResident = Resident::where('resident_id', $rowData['resident_id'])->first();
                    }

                    // Process the household first
                    $household = $this->processHousehold($rowData);

                    // Process the resident
                    $residentData = $this->mapResidentData($rowData);
                    $residentData['household_id'] = $household->id;

                    if ($existingResident) {
                        $existingResident->update($residentData);
                        $stats['updated']++;
                    } else {
                        if (empty($residentData['resident_id'])) {
                            $residentData['resident_id'] = Resident::generateResidentId();
                        }
                        Resident::create($residentData);
                        $stats['created']++;
                    }
                } catch (\Exception $e) {
                    $stats['failed']++;
                    $stats['errors'][] = "Row {$row}: ".$e->getMessage();
                    Log::error("CSV Import Error - Row {$row}: ".$e->getMessage());
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $stats['errors'][] = 'General error: '.$e->getMessage();
            Log::error('CSV Import General Error: '.$e->getMessage());
        }

        fclose($file);

        return $stats;
    }

    /**
     * Process and find or create household
     *
     * @param  array  $data
     * @return Household
     */
    private function processHousehold($data)
    {
        // Try to find existing household with the same address and barangay
        $household = Household::where('address', $data['address'])
            ->where('barangay', $data['barangay'])
            ->first();

        if (! $household) {
            // Create new household
            $household = Household::create([
                'household_id' => Household::generateHouseholdId(),
                'address' => $data['address'],
                'barangay' => $data['barangay'],
                'city_municipality' => $data['city_municipality'] ?? '',
                'province' => $data['province'] ?? '',
                'region' => $data['region'] ?? '',
                // Set default values for required fields
                'has_electricity' => true,
                'has_water_supply' => true,
            ]);
        }

        return $household;
    }

    /**
     * Map CSV data to resident model fields
     *
     * @param  array  $data
     * @return array
     */
    private function mapResidentData($data)
    {
        $residentData = [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'suffix' => $data['suffix'] ?? null,
            'birth_date' => ! empty($data['birth_date']) ? Carbon::parse($data['birth_date']) : null,
            'birthplace' => $data['birthplace'] ?? null,
            'gender' => $data['gender'] ?? 'other',
            'civil_status' => $data['civil_status'] ?? 'single',
            'contact_number' => $data['contact_number'] ?? null,
            'email' => $data['email'] ?? null,
            'occupation' => $data['occupation'] ?? null,
            'monthly_income' => isset($data['monthly_income']) && $data['monthly_income'] !== ''
                ? $data['monthly_income']
                : null,
            'educational_attainment' => $data['educational_attainment'] ?? null,
            'special_sector' => $data['special_sector'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        // Handle boolean fields
        $booleanFields = [
            'is_registered_voter', 'is_pwd', 'is_senior_citizen',
            'is_solo_parent', 'is_pregnant', 'is_lactating', 'is_indigenous', 'is_4ps',
            'is_scholar', 'is_active',
        ];

        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $value = strtolower($data[$field]);
                $residentData[$field] = in_array($value, ['yes', 'true', '1', 'y']);
            } else {
                $residentData[$field] = false;
            }
        }

        // Handle date_issue
        if (! empty($data['date_issue'])) {
            $residentData['date_issue'] = Carbon::parse($data['date_issue']);
        }

        // If resident_id is provided, include it
        if (! empty($data['resident_id'])) {
            $residentData['resident_id'] = $data['resident_id'];
        }

        // Auto-set is_senior_citizen based on birth_date if not explicitly set
        if (isset($residentData['birth_date']) && $residentData['birth_date']) {
            $age = Carbon::parse($residentData['birth_date'])->age;
            if ($age >= 60 && ! isset($data['is_senior_citizen'])) {
                $residentData['is_senior_citizen'] = true;
            }
        }

        return $residentData;
    }

    private function readHeaders($file): array
    {
        $headers = fgetcsv($file);

        if (! is_array($headers)) {
            return [];
        }

        return array_map(function ($header) {
            $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);

            return Str::snake(strtolower(trim($header)));
        }, $headers);
    }

    private function requiredHeaders(): array
    {
        return ['first_name', 'last_name', 'birth_date', 'gender', 'address', 'barangay'];
    }

    private function mapCsvRow(array $headers, array $data): array
    {
        $rowData = [];

        foreach ($headers as $index => $header) {
            if (isset($data[$index])) {
                $rowData[$header] = trim($data[$index]);
            }
        }

        return $rowData;
    }

    private function validatePreviewRow(array $data): array
    {
        $errors = [];

        foreach ($this->requiredHeaders() as $field) {
            if (($data[$field] ?? '') === '') {
                $errors[] = Str::headline($field).' is required.';
            }
        }

        if (strcasecmp((string) ($data['barangay'] ?? ''), 'Unknown') === 0) {
            $errors[] = 'Barangay could not be resolved from the managed mappings.';
        }

        if (($data['monthly_income'] ?? '') !== '' && ! is_numeric($data['monthly_income'])) {
            $errors[] = 'Monthly income must be numeric.';
        }

        if (($data['gender'] ?? '') !== '' && ! in_array(strtolower($data['gender']), ['male', 'female', 'other'], true)) {
            $errors[] = 'Gender must be male, female, or other.';
        }

        if (($data['civil_status'] ?? '') !== '' && ! in_array(strtolower($data['civil_status']), ['single', 'married', 'widowed', 'divorced', 'separated', 'other'], true)) {
            $errors[] = 'Civil status is invalid.';
        }

        foreach (['birth_date', 'date_issue'] as $field) {
            if (($data[$field] ?? '') === '') {
                continue;
            }

            try {
                Carbon::parse($data[$field]);
            } catch (\Throwable) {
                $errors[] = Str::headline($field).' is not a valid date.';
            }
        }

        return $errors;
    }

    private function normalizeHouseholdLocation(array $data): array
    {
        $address = trim((string) ($data['address'] ?? ''));
        $barangay = trim((string) ($data['barangay'] ?? ''));

        if ($barangay === '' || strcasecmp($barangay, 'Unknown') === 0) {
            $barangay = $this->barangayFromAddress($address) ?? $barangay;
        }

        $data['barangay'] = $barangay;
        $cleanAddress = $this->addressWithoutLocation($address, [
            $barangay,
            (string) ($data['city_municipality'] ?? ''),
            (string) ($data['province'] ?? ''),
            (string) ($data['region'] ?? ''),
        ]);
        $data['address'] = $cleanAddress !== '' ? $cleanAddress : ',';

        return $data;
    }

    private function barangayFromAddress(string $address): ?string
    {
        $normalizedAddress = $this->normalizeLocationName($address);

        foreach ($this->managedBarangays() as $barangay) {
            if (str_contains($normalizedAddress, $this->normalizeLocationName($barangay['legacy_name']))) {
                return $barangay['project_name'];
            }
        }

        return null;
    }

    private function managedBarangays(): array
    {
        if ($this->managedBarangays !== null) {
            return $this->managedBarangays;
        }

        $projectNames = collect(LocationsSeeder::getBarangayRecords())->pluck('name', 'code');

        return $this->managedBarangays = DB::table('legacy_barangay_mappings')
            ->where('source_system', LegacyCsvImporter::SOURCE_SYSTEM)
            ->where('status', 'mapped')
            ->get(['legacy_name', 'brgy_code'])
            ->map(fn ($mapping) => [
                'legacy_name' => $mapping->legacy_name,
                'project_name' => $projectNames[$mapping->brgy_code] ?? $mapping->legacy_name,
            ])
            ->sortByDesc(fn ($mapping) => strlen($this->normalizeLocationName($mapping['legacy_name'])))
            ->values()
            ->all();
    }

    private function addressWithoutLocation(string $address, array $locationNames): string
    {
        $locationNames = array_values(array_filter(array_unique([
            ...$locationNames,
            'City of Alaminos',
            'Alaminos City',
            'Alaminos',
            'Pangasinan',
            'Region I (Ilocos Region)',
            'Region I',
        ])));

        foreach ($locationNames as $locationName) {
            $address = str_ireplace($locationName, '', $address);
        }

        $address = preg_replace('/\b(?:brgy|barangay)\.?\s*/i', '', $address) ?? $address;
        $address = preg_replace('/\bPang\.?\b/i', '', $address) ?? $address;
        $address = preg_replace('/\s*,\s*,+/', ',', $address) ?? $address;

        return trim($address, " \t\n\r\0\x0B,.-");
    }

    private function normalizeLocationName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = str_replace(['sta.', 'sta ', 'pocal-pocal', 'tawin-tawin'], ['santa', 'santa ', 'pocalpocal', 'tawintawin'], $name);

        return preg_replace('/[^a-z0-9]+/', '', $name) ?? '';
    }

    /**
     * Open a memory file for CSV output
     *
     * @return resource
     */
    private function openMemoryFile()
    {
        $output = fopen('php://temp', 'w+');
        // Add BOM for Excel UTF-8 compatibility
        fwrite($output, $bom = (chr(0xEF).chr(0xBB).chr(0xBF)));

        return $output;
    }
}
