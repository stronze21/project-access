<?php

namespace App\Services\Reports;

use App\Models\Resident;
use App\Services\ExportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ResidentExportService
{
    protected $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Generate a complete resident export based on filters
     *
     * @param array $filters
     * @return string Path to the exported file
     */
    public function exportResidents(array $filters): string
    {
        // Get residents based on filters
        $residents = $this->getResidentsQuery($filters)->get();

        // Format for export
        $exportData = $this->formatForResidentExport($residents);

        // Generate filename with date range
        $dateRange = '';
        if (!empty($filters['dateFrom']) && !empty($filters['dateTo'])) {
            $dateRange = date('Y-m-d', strtotime($filters['dateFrom'])) . '_to_' .
                         date('Y-m-d', strtotime($filters['dateTo']));
        } else {
            $dateRange = date('Y-m-d');
        }

        $filename = 'residents_export_' . $dateRange . '.csv';

        // Ensure temp directory exists
        if (!Storage::exists('temp')) {
            Storage::makeDirectory('temp');
        }

        // Generate CSV file
        return $this->exportService->generateCsv(
            $exportData['data'],
            $exportData['headers'],
            $filename
        );
    }

    /**
     * Get residents query builder based on filters
     */
    public function getResidentsQuery(array $filters): Builder
    {
        $query = Resident::query()
            ->with(['household'])
            ->when(!empty($filters['barangay']), function ($q) use ($filters) {
                $q->whereHas('household', function ($query) use ($filters) {
                    $query->where('barangay', $filters['barangay']);
                });
            })
            ->when(!empty($filters['status']), function ($q) use ($filters) {
                if ($filters['status'] === 'active') {
                    $q->where('is_active', true);
                } elseif ($filters['status'] === 'inactive') {
                    $q->where('is_active', false);
                }
            });

        // Add date range filters if specified
        if (!empty($filters['dateFrom'])) {
            $query->where('created_at', '>=', $filters['dateFrom'] . ' 00:00:00');
        }

        if (!empty($filters['dateTo'])) {
            $query->where('created_at', '<=', $filters['dateTo'] . ' 23:59:59');
        }

        return $query;
    }

    /**
     * Format residents data for export according to the Excel format
     */
    public function formatForResidentExport($residents): array
    {
        $headers = [
            'FIRSTNAME',
            'MIDDLE NAME',
            'LAST NAME',
            'ID',
            'BIRTHDAY',
            'ADDRESS',
            'SIGNATURE',
            'DATE ISSUE',
            'SEX',
            'STATUS',
            'BIRTHPLACE',
            'EMERGENCY',
            'OCCUPATION',
            'ELIGIBILITY',
            'IDNO',
            'QRCODE',
        ];

        $data = [];

        foreach ($residents as $resident) {
            // Get full address from household
            $address = $resident->household ? $resident->household->getFullAddressAttribute() : '';

            // Format emergency contact
            $emergency = $resident->emergency_contact_name ?
                         $resident->emergency_contact_name . '/' .
                         $resident->emergency_contact_number : '';

            // Eligibility flags
            $eligibility = '';
            if ($resident->is_senior_citizen) $eligibility .= 'Senior Citizen';
            if ($resident->is_pwd) $eligibility .= ($eligibility ? ', ' : '') . 'PWD';
            if ($resident->is_4ps) $eligibility .= ($eligibility ? ', ' : '') . '4Ps';

            // Build the data row
            $data[] = [
                'FIRSTNAME' => strtoupper($resident->first_name),
                'MIDDLE NAME' => strtoupper($resident->middle_name),
                'LAST NAME' => strtoupper($resident->last_name),
                'ID' => strtoupper($resident->resident_id),
                'BIRTHDAY' => strtoupper($resident->birth_date ? $resident->birth_date->format('M. j, Y') : ''),
                'ADDRESS' => strtoupper($address),
                'SIGNATURE' => strtoupper($resident->signature ? 'Yes' : 'No'),
                'DATE ISSUE' => strtoupper($resident->date_issue ? $resident->date_issue->format('M. d, Y') : ''),
                'SEX' => strtoupper($resident->gender),
                'STATUS' => strtoupper($resident->civil_status),
                'BIRTHPLACE' => strtoupper($resident->birthplace),
                'EMERGENCY' => strtoupper($emergency),
                'OCCUPATION' => strtoupper($resident->occupation),
                'ELIGIBILITY' => strtoupper($eligibility),
                'IDNO' => strtoupper($resident->rfid_number),
                'QRCODE' => strtoupper($resident->qr_code),
            ];
        }

        return [
            'headers' => $headers,
            'data' => $data
        ];
    }
}