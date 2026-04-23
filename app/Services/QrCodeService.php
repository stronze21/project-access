<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\Household;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    /**
     * Generate a QR code for a resident.
     *
     * @param Resident $resident
     * @param bool $force Whether to force regeneration if QR code already exists
     * @return string The QR code value
     */
    public function generateResidentQrCode(Resident $resident, bool $force = false): string
    {
        if (!$resident->qr_code || $force) {
            $qrCode = 'R-' . strtoupper(Str::random(4)) . '-' . $resident->id;
            $resident->qr_code = $qrCode;
            $resident->save();
        }

        return $resident->qr_code;
    }

    /**
     * Generate a QR code for a household.
     *
     * @param Household $household
     * @param bool $force Whether to force regeneration if QR code already exists
     * @return string The QR code value
     */
    public function generateHouseholdQrCode(Household $household, bool $force = false): string
    {
        if (!$household->qr_code || $force) {
            $qrCode = 'HH-' . strtoupper(Str::random(4)) . '-' . $household->id;
            $household->qr_code = $qrCode;
            $household->save();
        }

        return $household->qr_code;
    }

    /**
     * Generate a QR code image for a resident and save it.
     *
     * @param Resident $resident
     * @param int $size Size of the QR code image in pixels
     * @return string Path to the generated QR code image
     */
    public function generateResidentQrCodeImage(Resident $resident, int $size = 300): string
    {
        // Generate QR code if not exists
        $qrCodeValue = $this->generateResidentQrCode($resident);

        // Create directory if not exists
        $directory = 'qrcodes/residents';
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        // Generate file path
        $fileName = 'resident_' . $resident->id . '.png';
        $path = $directory . '/' . $fileName;

        // Generate QR code image
        $image = QrCode::format('png')
            ->size($size)
            ->errorCorrection('H')
            ->margin(1)
            ->generate($qrCodeValue);

        // Save the image
        Storage::put($path, $image);

        return $path;
    }

    /**
     * Generate a QR code image for a household and save it.
     *
     * @param Household $household
     * @param int $size Size of the QR code image in pixels
     * @return string Path to the generated QR code image
     */
    public function generateHouseholdQrCodeImage(Household $household, int $size = 300): string
    {
        // Generate QR code if not exists
        $qrCodeValue = $this->generateHouseholdQrCode($household);

        // Create directory if not exists
        $directory = 'qrcodes/households';
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        // Generate file path
        $fileName = 'household_' . $household->id . '.png';
        $path = $directory . '/' . $fileName;

        // Generate QR code image
        $image = QrCode::format('png')
            ->size($size)
            ->errorCorrection('H')
            ->margin(1)
            ->generate($qrCodeValue);

        // Save the image
        Storage::put($path, $image);

        return $path;
    }

    /**
     * Generate a resident ID card with QR code.
     *
     * @param Resident $resident
     * @return string Path to the generated ID card
     */
    public function generateResidentIdCard(Resident $resident): string
    {
        // Generate QR code if not exists
        $this->generateResidentQrCode($resident);

        // Create directory if not exists
        $directory = 'id-cards/residents';
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        // Generate file path
        $fileName = 'id_' . $resident->id . '.pdf';
        $path = $directory . '/' . $fileName;

        // In a real implementation, this would involve PDF generation
        // For now, we'll just create a placeholder
        $idCardContent = "RESIDENT ID CARD\n";
        $idCardContent .= "Name: {$resident->full_name}\n";
        $idCardContent .= "ID: {$resident->resident_id}\n";
        $idCardContent .= "QR Code: {$resident->qr_code}\n";

        // Save the placeholder
        Storage::put($path, $idCardContent);

        return $path;
    }

    /**
     * Find a resident by QR code.
     *
     * @param string $qrCode
     * @return Resident|null
     */
    public function findResidentByQrCode(string $qrCode): ?Resident
    {
        return Resident::where('qr_code', $qrCode)->orWhere('qr_code', 'QR-' . $qrCode)->first();
    }

    /**
     * Find a household by QR code.
     *
     * @param string $qrCode
     * @return Household|null
     */
    public function findHouseholdByQrCode(string $qrCode): ?Household
    {
        return Household::where('qr_code', $qrCode)->orWhere('qr_code', 'QR-' . $qrCode)->first();
    }

    /**
     * Process a scanned QR code.
     *
     * @param string $qrCode
     * @return array Information about the scanned QR code
     */
    public function processQrCode(string $qrCode): array
    {
        $result = [
            'type' => null,
            'id' => null,
            'object' => null,
            'found' => false,
            'message' => 'Invalid QR code format'
        ];

        // Check QR code format
        if (Str::startsWith($qrCode, 'RR-') or Str::startsWith($qrCode, 'R-') or Str::startsWith($qrCode, 'QR-R-')) {
            $result['type'] = 'resident';
            $resident = $this->findResidentByQrCode($qrCode);

            if ($resident) {
                $result['found'] = true;
                $result['object'] = $resident;
                $result['id'] = $resident->id;
                $result['message'] = 'Resident found: ' . $resident->full_name;
            } else {
                $result['message'] = 'Resident not found with QR code: ' . $qrCode;
            }
        } elseif (Str::startsWith($qrCode, 'HH-') or Str::startsWith($qrCode, 'QR-HH-')) {
            $result['type'] = 'household';
            $household = $this->findHouseholdByQrCode($qrCode);

            if ($household) {
                $result['found'] = true;
                $result['object'] = $household;
                $result['id'] = $household->id;
                $result['message'] = 'Household found: ' . $household->household_id;
            } else {
                $result['message'] = 'Household not found with QR code: ' . $qrCode;
            }
        }

        return $result;
    }
}
