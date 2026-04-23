<?php

namespace App\Services;

use App\Models\Resident;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class XpPenService
{
    /**
     * Save a signature from the XP-Pen tablet to a resident.
     *
     * @param Resident $resident The resident to save the signature for
     * @param string $signatureData The signature data URL
     * @return bool Whether the operation was successful
     */
    public function saveSignature(Resident $resident, string $signatureData): bool
    {
        try {
            // Update the resident's signature
            $resident->signature = $signatureData;
            $resident->signature_status = 'verified';

            // Log the signature update
            Log::info("Signature updated for resident ID: {$resident->resident_id}");

            return $resident->save();
        } catch (\Exception $e) {
            Log::error("Error saving XP-Pen signature: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean up and optimize a signature image.
     *
     * @param string $signatureData The input signature data URL
     * @return string The optimized signature data URL
     */
    public function optimizeSignature(string $signatureData): string
    {
        try {
            // Get the image data without the data URL prefix
            $parts = explode(',', $signatureData);
            if (count($parts) < 2) {
                return $signatureData; // Return original if not valid format
            }

            $imageData = base64_decode($parts[1]);

            // Create a GD image from the data
            $image = imagecreatefromstring($imageData);
            if (!$image) {
                return $signatureData; // Return original if can't create image
            }

            // Get image dimensions
            $width = imagesx($image);
            $height = imagesy($image);

            // Create a new image with transparent background
            $optimized = imagecreatetruecolor($width, $height);
            imagesavealpha($optimized, true);
            $transparent = imagecolorallocatealpha($optimized, 0, 0, 0, 127);
            imagefill($optimized, 0, 0, $transparent);

            // Copy with transparency
            imagecopy($optimized, $image, 0, 0, 0, 0, $width, $height);

            // Apply slight sharpening filter
            $sharpen = [
                [-1, -1, -1],
                [-1, 16, -1],
                [-1, -1, -1]
            ];
            imageconvolution($optimized, $sharpen, 8, 0);

            // Output to buffer
            ob_start();
            imagepng($optimized, null, 9); // Maximum compression
            $optimizedData = ob_get_contents();
            ob_end_clean();

            // Clean up
            imagedestroy($image);
            imagedestroy($optimized);

            // Create new data URL
            return 'data:image/png;base64,' . base64_encode($optimizedData);
        } catch (\Exception $e) {
            Log::error("Error optimizing signature: " . $e->getMessage());
            return $signatureData; // Return original on error
        }
    }

    /**
     * Save the signature as a file in the storage system.
     *
     * @param Resident $resident The resident to save the signature for
     * @param string $signatureData The signature data URL
     * @return string|null The file path if successful, null otherwise
     */
    public function saveSignatureFile(Resident $resident, string $signatureData): ?string
    {
        try {
            // Extract the base64 data
            $imageData = explode(',', $signatureData);

            if (count($imageData) > 1) {
                $imageData = base64_decode($imageData[1]);
                $filename = 'resident-' . $resident->id . '-signature-' . time() . '.png';
                $path = 'resident-signatures/' . $filename;

                // Save the file
                Storage::disk('public')->put($path, $imageData);

                // Log successful save
                Log::info("Signature file saved for resident ID: {$resident->resident_id}, path: {$path}");

                return $path;
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Error saving signature file: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if the XP-Pen tablet driver is properly installed.
     *
     * @return bool Whether the driver is detected
     */
    public function isDriverInstalled(): bool
    {
        // For Windows systems, check for WinTab or Windows Ink drivers
        if (PHP_OS_FAMILY === 'Windows') {
            $winTabPath = 'C:\\Windows\\System32\\Wintab32.dll';
            $inkPath = 'C:\\Windows\\System32\\InkObj.dll';

            return file_exists($winTabPath) || file_exists($inkPath);
        }

        // For macOS, check for the driver bundle
        if (PHP_OS_FAMILY === 'Darwin') {
            $macDriverPath = '/Library/Application Support/XP-Pen/Tablets';
            return file_exists($macDriverPath);
        }

        // Default assumption for other OS
        return true;
    }

    /**
     * Check if the XP-Pen tablet is connected and working.
     *
     * This is a simple placeholder. In reality, tablet detection would
     * be handled on the client side with JavaScript.
     *
     * @return bool Whether the tablet is detected
     */
    public function isTabletConnected(): bool
    {
        // In a real implementation, you might check for API responses
        // or other server-side indicators that the tablet is functioning
        return true;
    }

    /**
     * Get tablet specifications for the XP-Pen G430S.
     *
     * @return array Tablet specifications
     */
    public function getTabletSpecs(): array
    {
        return [
            'model' => 'XP-Pen G430S',
            'active_area' => '4 x 3 inches (102 x 76mm)',
            'resolution' => '5080 LPI (lines per inch)',
            'report_rate' => '266 RPS (reports per second)',
            'pressure_levels' => '8192 levels',
            'reading_height' => '10mm',
            'connection' => 'USB',
            'driver_version' => '3.2.0',
            'compatibility' => 'Windows 7/8/10/11, macOS 10.10+, Linux',
            'additional_features' => [
                'Battery-free stylus',
                'Tilt sensitivity: No',
                'Customizable express keys: No',
                'Compatible with Windows Ink'
            ]
        ];
    }

    /**
     * Get recommended settings for signature capture.
     *
     * @return array Recommended settings
     */
    public function getRecommendedSettings(): array
    {
        return [
            'driver_settings' => [
                'pressure_sensitivity' => '5 (medium)',
                'mapping_mode' => 'Pen Mode (not Mouse Mode)',
                'windows_ink' => 'Enabled',
                'express_keys' => 'Disabled for signature capture'
            ],
            'application_settings' => [
                'minimum_pen_size' => '2px',
                'recommended_pen_size' => '3px',
                'signature_height' => '100-200px',
                'line_smoothing' => 'Enabled'
            ]
        ];
    }
}
