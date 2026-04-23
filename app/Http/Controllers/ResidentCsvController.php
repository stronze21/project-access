<?php

namespace App\Http\Controllers;

use App\Services\ResidentCsvService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResidentCsvController extends Controller
{
    protected $csvService;

    /**
     * The middleware assigned to the controller.
     *
     * @var array
     */
    protected $middleware = [
        'permission:export-residents' => ['only' => ['export', 'download']],
        'permission:import-residents' => ['only' => ['import', 'processImport']],
    ];

    /**
     * Constructor
     */
    public function __construct(ResidentCsvService $csvService)
    {
        $this->csvService = $csvService;
    }


    /**
     * Show the export options page
     */
    public function export()
    {
        $barangayList = \App\Models\Household::select('barangay')
            ->distinct()
            ->orderBy('barangay')
            ->pluck('barangay')
            ->toArray();

        $specialSectorList = \App\Models\Resident::select('special_sector')
            ->whereNotNull('special_sector')
            ->where('special_sector', '!=', '')
            ->distinct()
            ->orderBy('special_sector')
            ->pluck('special_sector')
            ->toArray();

        return view('residents.export', [
            'barangayList' => $barangayList,
            'specialSectorList' => $specialSectorList
        ]);
    }

    /**
     * Generate and download CSV file
     */
    public function download(Request $request)
    {
        $filters = $request->only('barangay', 'special_sector', 'status');
        $csv = $this->csvService->exportToCsv($filters);

        $filename = 'residents_export_' . now()->format('Y-m-d_His') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Show the import form
     */
    public function import()
    {
        return view('residents.import');
    }

    /**
     * Process the import
     */
    public function processImport(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120', // max 5MB
        ]);

        try {
            // Store file temporarily
            $path = $request->file('csv_file')->store('temp');
            $fullPath = Storage::path($path);

            // Process the file
            $importStats = $this->csvService->importFromCsv($fullPath);

            // Delete the temporary file
            Storage::delete($path);

            if (!empty($importStats['errors'])) {
                return redirect()->route('residents.import')
                    ->with('warning', 'Import completed with errors')
                    ->with('importStats', $importStats);
            }

            return redirect()->route('residents.index')
                ->with('success', 'Import completed successfully')
                ->with('importStats', $importStats);
        } catch (\Exception $e) {
            return redirect()->route('residents.import')
                ->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Download sample CSV template
     */
    public function downloadTemplate()
    {
        $headers = [
            'resident_id', 'first_name', 'last_name', 'middle_name', 'suffix',
            'birth_date', 'birthplace', 'gender', 'civil_status', 'contact_number',
            'email', 'occupation', 'monthly_income', 'educational_attainment',
            'special_sector', 'is_registered_voter', 'is_pwd', 'is_senior_citizen',
            'is_solo_parent', 'is_pregnant', 'is_lactating', 'is_indigenous',
            'is_active', 'date_issue', 'notes', 'address', 'barangay',
            'city_municipality', 'province', 'region'
        ];

        $sampleData = [
            [
                '', 'Juan', 'Dela Cruz', 'Santos', 'Jr',
                '1990-01-15', 'Manila', 'male', 'married', '09191234567',
                'juan@example.com', 'Teacher', '25000', 'college',
                '4Ps', 'Yes', 'No', 'No',
                'No', 'No', 'No', 'No',
                'Yes', '2023-01-01', 'Additional notes', '123 Main St, Purok 3', 'Barangay A',
                'Alicia', 'Isabela', 'Region II'
            ],
            [
                '', 'Maria', 'Santos', 'Garcia', '',
                '1985-05-20', 'Quezon City', 'female', 'single', '09187654321',
                'maria@example.com', 'Nurse', '30000', 'college',
                'SOLO PARENT', 'Yes', 'No', 'No',
                'Yes', 'No', 'No', 'No',
                'Yes', '2023-01-02', '', '456 Second St, Purok 2', 'Barangay B',
                'Alicia', 'Isabela', 'Region II'
            ]
        ];

        $output = fopen('php://temp', 'w+');
        // Add BOM for Excel UTF-8 compatibility
        fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

        // Write headers and sample data
        fputcsv($output, $headers);
        foreach ($sampleData as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return response($csv)
            ->header('Content-Type', 'text/csv; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="residents_template.csv"');
    }
}
