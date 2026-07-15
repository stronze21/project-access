<?php

namespace App\Services\Legacy;

use App\Models\LegacyImportBatch;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

class LegacyCsvImporter
{
    public const SOURCE_SYSTEM = 'on_prem_legacy';

    private const DEFINITIONS = [
        'personal_info' => [
            'filename' => 'tblPersonalInfo.csv',
            'headers' => [
                'PIN', 'Lastname', 'Firstname', 'Middlename', 'Birthdate', 'CivilStatus',
                'Gender', 'Address', 'SpouseName', 'FatherName', 'MotherName', 'LockStatus',
                'Lockdate', 'Date_Modified', 'Userid', 'Disability_id', 'TypeSchool_id',
                'SourceIncome_id', 'Educational_id', 'Age', 'Ethnicity',
            ],
            'key' => ['PIN'],
        ],
        'family_members' => [
            'filename' => 'tblFamilyMembers.csv',
            'headers' => [
                'FamilyNumber', 'Building_RegistryNumber', 'PIN', 'Date_Update', 'Userid',
                'HouseHoldHead', 'FamilyHead', 'DateUpdate_Head', 'Relationship_id',
                'Disability_id', 'TypeSchool_id', 'SourceIncome_id', 'Educational_ID',
                'Year_Survey', 'Month_Survey',
            ],
            'key' => ['FamilyNumber', 'PIN'],
        ],
        'bhw_master' => [
            'filename' => 'tblBHWMaster.csv',
            'headers' => ['ZoneID', 'PIN', 'Barangay_Code', 'ZoneName', 'PIN2'],
            'key' => ['Barangay_Code', 'ZoneID', 'ZoneName'],
        ],
        'barangay' => [
            'filename' => 'tblBarangay.csv',
            'headers' => ['Barangay_Code', 'Barangay'],
            'key' => ['Barangay_Code'],
        ],
        'civil_status' => [
            'filename' => 'tblCivilStatus.csv',
            'headers' => ['CivilStatus_Code', 'CivilStatus'],
            'key' => ['CivilStatus_Code'],
        ],
        'source_income_type' => [
            'filename' => 'tblSourceIncomeType.csv',
            'headers' => ['IncomeCode', 'Income_description'],
            'key' => ['IncomeCode'],
        ],
        'educational_attainment' => [
            'filename' => 'tblEduc_Attainment.csv',
            'headers' => ['Educ_ID', 'Educ_Attainment'],
            'key' => ['Educ_ID'],
        ],
    ];

    public function import(array $inputPaths, bool $dryRun = true): array
    {
        $files = $this->resolveFiles($inputPaths);
        $manifest = $this->buildManifest($files);
        $checksum = hash('sha256', json_encode($manifest, JSON_UNESCAPED_SLASHES));

        if (! $dryRun) {
            $existing = LegacyImportBatch::where('manifest_checksum', $checksum)->first();
            if ($existing) {
                return [
                    'dry_run' => false,
                    'already_imported' => true,
                    'batch_id' => $existing->id,
                    'manifest_checksum' => $checksum,
                    'files' => $manifest,
                    'stats' => $existing->stats ?? [],
                ];
            }
        }

        $batch = null;
        $result = DB::transaction(function () use ($files, $manifest, $checksum, $dryRun, &$batch) {
            if (! $dryRun) {
                $batch = LegacyImportBatch::create([
                    'source_system' => self::SOURCE_SYSTEM,
                    'manifest_checksum' => $checksum,
                    'status' => 'staging',
                    'file_manifest' => $manifest,
                ]);
            }

            $processed = $this->processFiles($files, $batch?->id);

            if ($batch) {
                $this->markDuplicateNaturalKeys($batch->id, $processed['duplicate_keys']);
                $batch->update([
                    'status' => 'staged',
                    'stats' => $processed['stats'],
                    'error_summary' => $processed['error_summary'],
                    'imported_at' => now(),
                ]);
            }

            return $processed;
        });

        return [
            'dry_run' => $dryRun,
            'already_imported' => false,
            'batch_id' => $batch?->id,
            'manifest_checksum' => $checksum,
            'files' => $manifest,
            'stats' => $result['stats'],
            'error_summary' => $result['error_summary'],
        ];
    }

    private function resolveFiles(array $inputPaths): array
    {
        if ($inputPaths === []) {
            throw new RuntimeException('Provide at least one CSV file or directory.');
        }

        $candidates = [];
        foreach ($inputPaths as $inputPath) {
            $path = realpath($inputPath);
            if ($path === false) {
                throw new RuntimeException("Path does not exist: {$inputPath}");
            }

            if (is_dir($path)) {
                foreach (self::DEFINITIONS as $definition) {
                    $candidate = $path.DIRECTORY_SEPARATOR.$definition['filename'];
                    if (is_file($candidate)) {
                        $candidates[] = $candidate;
                    }
                }

                continue;
            }

            if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'csv') {
                throw new RuntimeException("Only CSV files are supported: {$path}");
            }
            $candidates[] = $path;
        }

        $files = [];
        foreach (array_values(array_unique($candidates)) as $path) {
            $headers = $this->readHeaders($path);
            $sourceTable = $this->identifySourceTable($headers);
            if (isset($files[$sourceTable])) {
                throw new RuntimeException(
                    "Multiple files were supplied for {$sourceTable}; provide exactly one version per source table."
                );
            }
            $files[$sourceTable] = [
                'path' => $path,
                'headers' => $headers,
                'definition' => self::DEFINITIONS[$sourceTable],
            ];
        }

        if ($files === []) {
            throw new RuntimeException('No recognized legacy CSV files were found.');
        }

        ksort($files);

        return $files;
    }

    private function readHeaders(string $path): array
    {
        $file = new SplFileObject($path, 'r');
        $headers = $file->fgetcsv();
        if ($headers === false || $headers === [null]) {
            throw new RuntimeException("CSV has no header row: {$path}");
        }

        return array_map(
            fn ($header) => trim((string) $header, "\xEF\xBB\xBF \t\n\r\0\x0B"),
            $headers
        );
    }

    private function identifySourceTable(array $headers): string
    {
        foreach (self::DEFINITIONS as $sourceTable => $definition) {
            if ($headers === $definition['headers']) {
                return $sourceTable;
            }
        }

        throw new RuntimeException('CSV headers do not match any supported legacy table: '.implode(', ', $headers));
    }

    private function buildManifest(array $files): array
    {
        $manifest = [];
        foreach ($files as $sourceTable => $file) {
            $manifest[] = [
                'source_table' => $sourceTable,
                'filename' => basename($file['path']),
                'size_bytes' => filesize($file['path']),
                'sha256' => hash_file('sha256', $file['path']),
            ];
        }

        return $manifest;
    }

    private function processFiles(array $files, ?int $batchId): array
    {
        $stats = [];
        $duplicateKeys = [];
        $personalPins = [];
        $familyPins = [];
        $bhwPins = [];

        foreach ($files as $sourceTable => $fileConfig) {
            $file = new SplFileObject($fileConfig['path'], 'r');
            $file->setFlags(SplFileObject::READ_CSV | SplFileObject::DROP_NEW_LINE);
            $file->seek(1);

            $headers = $fileConfig['headers'];
            $seenKeys = [];
            $duplicatesForFile = [];
            $chunk = [];
            $fileStats = [
                'rows' => 0,
                'valid_rows' => 0,
                'invalid_rows' => 0,
                'conflict_rows' => 0,
                'duplicate_natural_keys' => 0,
            ];

            while (! $file->eof()) {
                $values = $file->fgetcsv();
                if ($values === false || ($values === [null] && $file->eof())) {
                    continue;
                }

                $sourceRowNumber = $file->key() + 1;
                $fileStats['rows']++;
                $values = array_map(fn ($value) => trim((string) $value), $values);
                $errors = [];

                if (count($values) !== count($headers)) {
                    $errors[] = 'column_count_mismatch';
                    $values = array_slice(array_pad($values, count($headers), ''), 0, count($headers));
                }

                $payload = array_combine($headers, $values);
                $naturalKey = $this->naturalKey($payload, $fileConfig['definition']['key']);
                if ($naturalKey === null) {
                    $errors[] = 'missing_natural_key';
                } elseif (isset($seenKeys[$naturalKey])) {
                    $seenKeys[$naturalKey]++;
                    $duplicatesForFile[$naturalKey] = $seenKeys[$naturalKey];
                } else {
                    $seenKeys[$naturalKey] = 1;
                }

                if ($sourceTable === 'personal_info' && ($payload['PIN'] ?? '') !== '') {
                    $personalPins[$payload['PIN']] = true;
                } elseif ($sourceTable === 'family_members' && ($payload['PIN'] ?? '') !== '') {
                    $familyPins[$payload['PIN']] = true;
                } elseif ($sourceTable === 'bhw_master') {
                    foreach (['PIN', 'PIN2'] as $pinField) {
                        $pin = $payload[$pinField] ?? '';
                        if ($pin !== '' && strtoupper($pin) !== 'NULL') {
                            $bhwPins[$pin] = true;
                        }
                    }
                }

                $validationStatus = $errors === [] ? 'valid' : 'invalid';
                $fileStats[$validationStatus === 'valid' ? 'valid_rows' : 'invalid_rows']++;

                if ($batchId !== null) {
                    $now = now();
                    $chunk[] = [
                        'legacy_import_batch_id' => $batchId,
                        'source_table' => $sourceTable,
                        'source_row_number' => $sourceRowNumber,
                        'natural_key' => $naturalKey,
                        'row_hash' => hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE)),
                        'raw_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                        'validation_status' => $validationStatus,
                        'validation_errors' => $errors === [] ? null : json_encode($errors),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    if (count($chunk) >= 1000) {
                        DB::table('legacy_import_rows')->insert($chunk);
                        $chunk = [];
                    }
                }
            }

            if ($chunk !== []) {
                DB::table('legacy_import_rows')->insert($chunk);
            }

            $fileStats['conflict_rows'] = array_sum($duplicatesForFile);
            $fileStats['valid_rows'] -= $fileStats['conflict_rows'];
            $fileStats['duplicate_natural_keys'] = count($duplicatesForFile);
            $duplicateKeys[$sourceTable] = array_keys($duplicatesForFile);
            $stats[$sourceTable] = $fileStats;
        }

        $personalIncluded = isset($files['personal_info']);
        $missingFamilyPins = $personalIncluded
            ? array_values(array_diff(array_keys($familyPins), array_keys($personalPins)))
            : [];
        $missingBhwPins = $personalIncluded
            ? array_values(array_diff(array_keys($bhwPins), array_keys($personalPins)))
            : [];

        return [
            'stats' => $stats,
            'duplicate_keys' => $duplicateKeys,
            'error_summary' => [
                'family_pins_missing_from_personal_info' => [
                    'checked' => $personalIncluded,
                    'count' => $personalIncluded ? count($missingFamilyPins) : null,
                    'sample' => array_slice($missingFamilyPins, 0, 20),
                ],
                'bhw_pins_missing_from_personal_info' => [
                    'checked' => $personalIncluded,
                    'count' => $personalIncluded ? count($missingBhwPins) : null,
                    'sample' => array_slice($missingBhwPins, 0, 20),
                ],
            ],
        ];
    }

    private function naturalKey(array $payload, array $keyFields): ?string
    {
        $parts = [];
        foreach ($keyFields as $field) {
            $value = trim((string) ($payload[$field] ?? ''));
            if ($value === '' || strtoupper($value) === 'NULL') {
                return null;
            }
            $parts[] = $value;
        }

        return implode('|', $parts);
    }

    private function markDuplicateNaturalKeys(int $batchId, array $duplicateKeys): void
    {
        foreach ($duplicateKeys as $sourceTable => $keys) {
            foreach (array_chunk($keys, 500) as $chunk) {
                if ($chunk === []) {
                    continue;
                }
                DB::table('legacy_import_rows')
                    ->where('legacy_import_batch_id', $batchId)
                    ->where('source_table', $sourceTable)
                    ->whereIn('natural_key', $chunk)
                    ->update([
                        'validation_status' => 'conflict',
                        'validation_errors' => json_encode(['duplicate_natural_key']),
                        'updated_at' => now(),
                    ]);
            }
        }
    }
}
