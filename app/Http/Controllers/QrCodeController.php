<?php

namespace App\Http\Controllers;

use App\Models\Household;
use App\Models\Resident;
use App\Services\QrCodeService;
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

        $qrCodeValue = $this->qrCodeService->generateResidentQrCode($resident);

        // SVG rendering does not require the Imagick PHP extension and remains
        // sharp at the physical size used on the printed card.
        $qrCodeSvg = QrCode::format('svg')
            ->size(300)
            ->errorCorrection('H')
            ->margin(1)
            ->generate($qrCodeValue);

        return response($qrCodeSvg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'private, max-age=3600');
    }

    /**
     * Get household QR code as image
     */
    public function getHouseholdQrCode($id)
    {
        $household = Household::findOrFail($id);

        // Generate QR code if not exists
        if (! $household->qr_code) {
            $this->qrCodeService->generateHouseholdQrCode($household);
        }

        if ($household->householdHead()) {
            $qr_code = $household->householdHead()->qr_code;
        } else {
            $qr_code = $household->qr_code;
        }

        $qrCodeSvg = QrCode::format('svg')
            ->size(300)
            ->errorCorrection('H')
            ->margin(1)
            ->generate($qr_code);

        return response($qrCodeSvg)
            ->header('Content-Type', 'image/svg+xml')
            ->header('Cache-Control', 'private, max-age=3600');
    }

    /**
     * Download resident QR code as image file
     */
    public function downloadResidentQrCode($id)
    {
        $resident = Resident::findOrFail($id);

        // Generate QR code image path
        $path = $this->qrCodeService->generateResidentQrCodeImage($resident);

        // Download the file
        $fullPath = Storage::path($path);
        $fileName = 'resident_qr_'.$resident->full_name.'.svg';

        return response()->download($fullPath, $fileName);
    }

    /**
     * Download household QR code as image file
     */
    public function downloadHouseholdQrCode($id)
    {
        $household = Household::findOrFail($id);

        // Generate QR code if not exists
        if (! $household->qr_code) {
            $this->qrCodeService->generateHouseholdQrCode($household);
        }

        // Generate QR code image path
        $path = $this->qrCodeService->generateHouseholdQrCodeImage($household);

        // Download the file
        $fullPath = Storage::path($path);
        $fileName = 'household_qr_'.$household->household_id.'.svg';

        return response()->download($fullPath, $fileName);
    }
}
