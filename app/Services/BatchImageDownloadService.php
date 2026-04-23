<?php

namespace App\Services;

use App\Models\Resident;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BatchImageDownloadService
{
    /**
     * Generate a ZIP file containing images for the specified residents.
     *
     * @param array $residentIds Array of resident IDs
     * @param array $imageTypes Types of images to include ('qr_code', 'signature', 'photo')
     * @return string Path to the generated ZIP file
     */
    public function generateBatchZip(array $residentIds, array $imageTypes = ['qr_code', 'signature', 'photo']): string
    {
        // Create temp directory if it doesn't exist
        $tempDir = storage_path('app/temp');
        if (!File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        // Create QR code service instance for generating QR codes
        $qrCodeService = app(QrCodeService::class);

        // Create ZIP file
        $zipFileName = 'resident_images_' . time() . '.zip';
        $zipFilePath = $tempDir . '/' . $zipFileName;

        $zip = new ZipArchive();
        if ($zip->open($zipFilePath, ZipArchive::CREATE) !== true) {
            throw new \Exception("Cannot create ZIP file");
        }

        // Log summary header
        Log::info("Starting batch image download for " . count($residentIds) . " residents");

        // Get residents
        $residents = Resident::whereIn('id', $residentIds)->get();
        $successCount = 0;
        $errorCount = 0;

        foreach ($residents as $resident) {
            $residentName = $this->sanitizeFileName($resident->full_name);
            $residentFolder = $residentName . '_' . $resident->id;
            $residentImages = 0;

            // Debug log for this resident
            Log::info("Processing resident: {$resident->id} - {$resident->full_name}");

            try {
                if (in_array('qr_code', $imageTypes) && $resident->qr_code) {
                    // Generate QR code image
                    $qrCodePath = $qrCodeService->generateResidentQrCodeImage($resident);
                    if (Storage::exists($qrCodePath)) {
                        $qrCodeContent = Storage::get($qrCodePath);
                        $zip->addFromString(
                            $residentFolder . '/qr_code.png',
                            $qrCodeContent
                        );
                        $residentImages++;
                        Log::info("Added QR code for resident {$resident->id}");
                    } else {
                        Log::warning("QR code file not found at path: {$qrCodePath} for resident {$resident->id}");
                    }
                }

                if (in_array('signature', $imageTypes) && $resident->signature) {
                    // Process base64 signature
                    if (strpos($resident->signature, 'data:image') === 0) {
                        $signatureContent = $this->getBase64ImageContent($resident->signature);
                        if ($signatureContent) {
                            $zip->addFromString(
                                $residentFolder . '/signature.png',
                                $signatureContent
                            );
                            $residentImages++;
                            Log::info("Added signature for resident {$resident->id}");
                        } else {
                            Log::warning("Failed to process base64 signature for resident {$resident->id}");
                        }
                    } else {
                        Log::warning("Signature for resident {$resident->id} is not in base64 format: " . substr($resident->signature, 0, 30) . "...");
                    }
                }

                if (in_array('photo', $imageTypes) && $resident->photo_path) {
                    // Add photo from storage
                    Log::info("Attempting to get photo at path: {$resident->photo_path} for resident {$resident->id}");

                    if (Storage::exists($resident->photo_path)) {
                        $photoContent = Storage::get($resident->photo_path);
                        $extension = pathinfo($resident->photo_path, PATHINFO_EXTENSION) ?: 'jpg';
                        $zip->addFromString(
                            $residentFolder . '/photo.' . $extension,
                            $photoContent
                        );
                        $residentImages++;
                        Log::info("Added photo for resident {$resident->id}");
                    } else {
                        // Try with public disk if main disk fails
                        if (Storage::disk('public')->exists($resident->photo_path)) {
                            $photoContent = Storage::disk('public')->get($resident->photo_path);
                            $extension = pathinfo($resident->photo_path, PATHINFO_EXTENSION) ?: 'jpg';
                            $zip->addFromString(
                                $residentFolder . '/photo.' . $extension,
                                $photoContent
                            );
                            $residentImages++;
                            Log::info("Added photo (from public disk) for resident {$resident->id}");
                        } else {
                            Log::warning("Photo file not found at path: {$resident->photo_path} for resident {$resident->id}");
                        }
                    }
                }

                if ($residentImages > 0) {
                    $successCount++;
                } else {
                    Log::warning("No images were added for resident {$resident->id}");
                    $errorCount++;
                }
            } catch (\Exception $e) {
                Log::error("Error processing resident {$resident->id}: " . $e->getMessage());
                $errorCount++;
            }
        }

        // Log summary
        Log::info("Batch image download complete: {$successCount} residents processed successfully, {$errorCount} with errors");

        $zip->close();

        return $zipFilePath;
    }

    /**
     * Sanitize a string to make it safe for use as a filename.
     *
     * @param string $fileName
     * @return string
     */
    private function sanitizeFileName(string $fileName): string
    {
        // Remove any character that isn't a letter, digit, space, underscore, or dash
        $fileName = preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $fileName);
        // Replace spaces with underscores
        $fileName = str_replace(' ', '_', $fileName);
        // Remove multiple underscores
        $fileName = preg_replace('/_+/', '_', $fileName);
        // Trim underscores from beginning and end
        $fileName = trim($fileName, '_');

        return $fileName;
    }

    /**
     * Extract image content from a base64 encoded string.
     *
     * @param string $base64Image
     * @return string|null
     */
    private function getBase64ImageContent(string $base64Image): ?string
    {
        try {
            $parts = explode(',', $base64Image);
            if (count($parts) === 2) {
                return base64_decode($parts[1]);
            } elseif (count($parts) === 1 && base64_encode(base64_decode($parts[0], true)) === $parts[0]) {
                // It's already raw base64 without the data URL prefix
                return base64_decode($parts[0]);
            }

            Log::warning("Invalid base64 image format: " . substr($base64Image, 0, 30) . "...");
            return null;
        } catch (\Exception $e) {
            Log::error("Error processing base64 image: " . $e->getMessage());
            return null;
        }
    }
}
