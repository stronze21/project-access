<?php

namespace App\Http\Controllers;

use App\Models\Resident;
use App\Models\Household;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeController extends Controller
{
    protected $qrCodeService;

    /**
     * Constructor
     */
    public function __construct(QrCodeService $qrCodeService)
    {
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Get resident QR code as image
     */
    public function getResidentQrCode($id)
    {
        $resident = Resident::findOrFail($id);

        // Generate QR code if not exists
        if (!$resident->qr_code) {
            $this->qrCodeService->generateResidentQrCode($resident);
        }

        // Generate QR code image as PNG
        $qrCodePng = QrCode::format('png')
            ->size(300)
            ->errorCorrection('H')
            ->margin(1)
            ->generate($resident->qr_code);

        // Convert PNG to JPEG
        $image = imagecreatefromstring($qrCodePng);

        // Create white background (JPEG doesn't support transparency)
        $width = imagesx($image);
        $height = imagesy($image);
        $jpegImage = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($jpegImage, 255, 255, 255);
        imagefill($jpegImage, 0, 0, $white);
        imagecopy($jpegImage, $image, 0, 0, 0, 0, $width, $height);

        // Output as JPEG
        ob_start();
        imagejpeg($jpegImage, null, 90); // 90 is quality (0-100)
        $qrCodeJpeg = ob_get_clean();

        imagedestroy($image);
        imagedestroy($jpegImage);

        return response($qrCodeJpeg)->header('Content-Type', 'image/jpeg');
    }

    /**
     * Get household QR code as image
     */
    public function getHouseholdQrCode($id)
    {
        $household = Household::findOrFail($id);

        // Generate QR code if not exists
        if (!$household->qr_code) {
            $this->qrCodeService->generateHouseholdQrCode($household);
        }

        if ($household->householdHead()) {
            $qr_code = $household->householdHead()->qr_code;
        } else {
            $qr_code = $household->qr_code;
        }

        // Generate QR code image as PNG
        $qrCodePng = QrCode::format('png')
            ->size(300)
            ->errorCorrection('H')
            ->margin(1)
            ->generate($qr_code);

        // Convert PNG to JPEG
        $image = imagecreatefromstring($qrCodePng);

        // Create white background (JPEG doesn't support transparency)
        $width = imagesx($image);
        $height = imagesy($image);
        $jpegImage = imagecreatetruecolor($width, $height);
        $white = imagecolorallocate($jpegImage, 255, 255, 255);
        imagefill($jpegImage, 0, 0, $white);
        imagecopy($jpegImage, $image, 0, 0, 0, 0, $width, $height);

        // Output as JPEG
        ob_start();
        imagejpeg($jpegImage, null, 90); // 90 is quality (0-100)
        $qrCodeJpeg = ob_get_clean();

        imagedestroy($image);
        imagedestroy($jpegImage);

        return response($qrCodeJpeg)->header('Content-Type', 'image/jpeg');
    }

    /**
     * Download resident QR code as image file
     */
    public function downloadResidentQrCode($id)
    {
        $resident = Resident::findOrFail($id);

        // Generate QR code if not exists
        if (!$resident->qr_code) {
            $this->qrCodeService->generateResidentQrCode($resident);
        }

        // Generate QR code image path
        $path = $this->qrCodeService->generateResidentQrCodeImage($resident);

        // Download the file
        $fullPath = Storage::path($path);
        $fileName = 'resident_qr_' . $resident->full_name . '.jpg';

        return response()->download($fullPath, $fileName);
    }

    /**
     * Download household QR code as image file
     */
    public function downloadHouseholdQrCode($id)
    {
        $household = Household::findOrFail($id);

        // Generate QR code if not exists
        if (!$household->qr_code) {
            $this->qrCodeService->generateHouseholdQrCode($household);
        }

        // Generate QR code image path
        $path = $this->qrCodeService->generateHouseholdQrCodeImage($household);

        // Download the file
        $fullPath = Storage::path($path);
        $fileName = 'household_qr_' . $household->household_id . '.jpg';

        return response()->download($fullPath, $fileName);
    }
}