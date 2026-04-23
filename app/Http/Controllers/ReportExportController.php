<?php

namespace App\Http\Controllers;

use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportExportController extends Controller
{
    protected $exportService;

    /**
     * Constructor
     */
    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Download a generated report file
     */
    public function download(Request $request)
    {
        $filePath = $request->input('file');

        if (!$filePath || !Storage::exists($filePath)) {
            return back()->with('error', 'Export file not found or has expired.');
        }

        $filename = basename($filePath);

        return response()->download(
            Storage::path($filePath),
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8']
        )->deleteFileAfterSend(true);
    }
}