<?php

namespace App\Http\Controllers;

use App\Services\BatchImageDownloadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class BatchImageDownloadController extends Controller
{
    protected $batchImageDownloadService;

    /**
     * Constructor
     */
    public function __construct(BatchImageDownloadService $batchImageDownloadService)
    {
        $this->batchImageDownloadService = $batchImageDownloadService;
    }

    /**
     * Download batch images
     */
    public function download(Request $request)
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'resident_ids' => 'required|array',
            'resident_ids.*' => 'required|integer|exists:residents,id',
            'image_types' => 'required|array',
            'image_types.*' => 'required|in:qr_code,signature,photo',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Generate ZIP file
            $zipFilePath = $this->batchImageDownloadService->generateBatchZip(
                $request->input('resident_ids'),
                $request->input('image_types')
            );

            // Set up headers for download
            $headers = [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="resident_images.zip"',
                'Content-Length' => File::size($zipFilePath),
            ];

            // Create response to download file
            $response = Response::download($zipFilePath, 'resident_images.zip', $headers);

            // Register a callback to delete the temporary file after download
            $response->deleteFileAfterSend(true);

            return $response;
        } catch (\Exception $e) {
            return back()->with('error', 'Error generating image batch: ' . $e->getMessage());
        }
    }
}
