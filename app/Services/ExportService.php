<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ExportService
{
    /**
     * Generate a CSV file from data
     *
     * @param array $data The data to export
     * @param array $headers Column headers
     * @param string $filename The filename to use
     * @return string The path to the generated file
     */

    public function generateCsv(iterable $rows, array $headers, string $filename = null): string
    {
        // Generate a filename if not provided
        if (!$filename) {
            $filename = 'export_' . date('Y-m-d_His') . '.csv';
        }

        // Ensure filename has .csv extension
        if (!Str::endsWith($filename, '.csv')) {
            $filename .= '.csv';
        }

        // Define the directory in the actual public path
        $directory = 'exports/';
        $publicPath = public_path($directory);

        // Ensure the directory exists
        if (!File::exists($publicPath)) {
            File::makeDirectory($publicPath, 0755, true);
        }

        // Full path to the file
        $fullPath = $publicPath . $filename;

        // Open file for writing
        $file = fopen($fullPath, 'w');

        // UTF-8 BOM for Excel
        fwrite($file, "\xEF\xBB\xBF");

        // Write headers
        fputcsv($file, $headers);

        // 🔥 STREAM rows (no memory buildup)
        foreach ($rows as $row) {
            fputcsv($file, $row);
        }

        fclose($file);

        return asset($directory . $filename);
    }

    // Helper method to ensure the directory exists
    private function ensureDirectoryExists(string $directory): void
    {
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }
    }

    /**
     * Format distributions data for CSV export
     *
     * @param \Illuminate\Database\Eloquent\Collection $distributions
     * @return array
     */
    public function formatDistributionsForExport($distributions): array
    {
        $headers = [
            'Reference Number',
            'Date',
            'Beneficiary Name',
            'Household ID',
            'Barangay',
            'Program',
            'Amount',
            'Status',
            'Distributed By',
            'Notes'
        ];

        $data = [];

        foreach ($distributions as $distribution) {
            $data[] = [
                $distribution->reference_number,
                $distribution->created_at->format('Y-m-d'),
                $distribution->resident->full_name,
                $distribution->household ? $distribution->household->household_id : 'N/A',
                $distribution->household ? $distribution->household->barangay : 'N/A',
                $distribution->ayudaProgram->name,
                $distribution->amount,
                ucfirst($distribution->status),
                $distribution->distributor ? $distribution->distributor->name : 'N/A',
                $distribution->notes
            ];
        }

        return [
            'headers' => $headers,
            'data' => $data
        ];
    }

    /**
     * Format programs data for CSV export
     *
     * @param array $programs
     * @return array
     */
    public function formatProgramsForExport($programs): array
    {
        $headers = [
            'Program Name',
            'Description',
            'Budget',
            'Start Date',
            'End Date',
            'Distributions',
            'Amount Distributed',
            'Beneficiaries',
            'Households',
            'Budget Utilization (%)'
        ];

        $data = [];

        foreach ($programs as $program) {
            $data[] = [
                $program->name,
                $program->description,
                $program->total_budget,
                $program->start_date ? $program->start_date->format('Y-m-d') : 'N/A',
                $program->end_date ? $program->end_date->format('Y-m-d') : 'N/A',
                $program->distributions_count,
                $program->total_distributed,
                $program->unique_beneficiaries,
                $program->unique_households,
                $program->utilization
            ];
        }

        return [
            'headers' => $headers,
            'data' => $data
        ];
    }

    /**
     * Format residents (beneficiaries) data for CSV export
     *
     * @param \Illuminate\Database\Eloquent\Collection $residents
     * @return array
     */
    public function formatResidentsForExport($residents): array
    {
        $headers = [
            'Name',
            'Household ID',
            'Barangay',
            'Gender',
            'Age',
            'Contact Number',
            'Demographics',
            'Distributions Count',
            'Total Amount Received',
            'Programs'
        ];

        $data = [];

        foreach ($residents as $resident) {
            // Build demographics string
            $demographics = [];
            if ($resident->is_senior_citizen) $demographics[] = 'Senior Citizen';
            if ($resident->is_pwd) $demographics[] = 'PWD';
            if ($resident->is_solo_parent) $demographics[] = 'Solo Parent';
            if ($resident->is_pregnant) $demographics[] = 'Pregnant';
            if ($resident->is_lactating) $demographics[] = 'Lactating';
            if ($resident->is_indigenous) $demographics[] = 'Indigenous';

            $data[] = [
                $resident->full_name,
                $resident->household ? $resident->household->household_id : 'N/A',
                $resident->household ? $resident->household->barangay : 'N/A',
                $resident->gender,
                $resident->birth_date ? now()->diffInYears($resident->birth_date) : 'N/A',
                $resident->contact_number,
                implode(', ', $demographics),
                $resident->distributions_count,
                $resident->total_received,
                $resident->programs_list
            ];
        }

        return [
            'headers' => $headers,
            'data' => $data
        ];
    }

    /**
     * Format barangays data for CSV export
     *
     * @param array $barangays
     * @return array
     */
    public function formatBarangaysForExport($barangays): array
    {
        $headers = [
            'Barangay',
            'Total Distributions',
            'Total Amount',
            'Unique Beneficiaries',
            'Households Reached',
            'Total Households',
            'Coverage Percentage'
        ];

        $data = [];

        foreach ($barangays as $barangay) {
            $data[] = [
                $barangay['barangay'],
                $barangay['distributions_count'],
                $barangay['total_amount'],
                $barangay['unique_beneficiaries'],
                $barangay['unique_households'],
                $barangay['total_households'],
                $barangay['coverage_percentage']
            ];
        }

        return [
            'headers' => $headers,
            'data' => $data
        ];
    }
}
