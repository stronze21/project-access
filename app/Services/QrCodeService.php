<?php

namespace App\Services;

use App\Models\Household;
use App\Models\Resident;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    /**
     * Generate a QR code for a resident.
     *
     * @param  bool  $force  Whether to force regeneration if QR code already exists
     * @return string The QR code value
     */
    public function generateResidentQrCode(Resident $resident, bool $force = false): string
    {
        return $resident->generateQrCode();
    }

    /**
     * Generate a QR code for a household.
     *
     * @param  bool  $force  Whether to force regeneration if QR code already exists
     * @return string The QR code value
     */
    public function generateHouseholdQrCode(Household $household, bool $force = false): string
    {
        if (! $household->qr_code || $force) {
            $qrCode = 'HH-'.strtoupper(Str::random(4)).'-'.$household->id;
            $household->qr_code = $qrCode;
            $household->save();
        }

        return $household->qr_code;
    }

    /**
     * Generate a QR code image for a resident and save it.
     *
     * @param  int  $size  Size of the QR code image in pixels
     * @return string Path to the generated QR code image
     */
    public function generateResidentQrCodeImage(Resident $resident, int $size = 300): string
    {
        // Generate QR code if not exists
        $qrCodeValue = $this->generateResidentQrCode($resident);

        // Create directory if not exists
        $directory = 'qrcodes/residents';
        if (! Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        // Generate file path
        $fileName = 'resident_'.$resident->id.'.svg';
        $path = $directory.'/'.$fileName;

        // Generate QR code image
        $image = QrCode::format('svg')
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
     * @param  int  $size  Size of the QR code image in pixels
     * @return string Path to the generated QR code image
     */
    public function generateHouseholdQrCodeImage(Household $household, int $size = 300): string
    {
        // Generate QR code if not exists
        $qrCodeValue = $this->generateHouseholdQrCode($household);

        // Create directory if not exists
        $directory = 'qrcodes/households';
        if (! Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        // Generate file path
        $fileName = 'household_'.$household->id.'.svg';
        $path = $directory.'/'.$fileName;

        // Generate QR code image
        $image = QrCode::format('svg')
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
     * @return string Path to the generated ID card
     */
    public function generateResidentIdCard(Resident $resident): string
    {
        // Generate QR code if not exists
        $this->generateResidentQrCode($resident);

        // Create directory if not exists
        $directory = 'id-cards/residents';
        if (! Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }

        // Generate file path
        $fileName = 'id_'.$resident->id.'.pdf';
        $path = $directory.'/'.$fileName;

        $qrSvg = QrCode::format('svg')->size(180)->margin(1)->generate($resident->qr_code);
        $safe = static fn (?string $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $html = '<!doctype html><html><head><meta charset="utf-8"><style>'
            .'@page{margin:0}body{font-family:DejaVu Sans,sans-serif;margin:0;padding:24px;color:#17324d}'
            .'.card{width:520px;height:300px;border:3px solid #23689b;border-radius:18px;padding:24px;box-sizing:border-box}'
            .'h1{font-size:22px;margin:0 0 6px;color:#23689b}.subtitle{font-size:11px;margin-bottom:28px}'
            .'.content{display:table;width:100%}.details,.qr{display:table-cell;vertical-align:middle}.details{width:68%}'
            .'.name{font-size:24px;font-weight:bold;margin-bottom:14px}.label{font-size:10px;text-transform:uppercase;color:#64748b}'
            .'.value{font-size:15px;font-weight:bold;margin-bottom:9px}.qr{text-align:center}.qr svg{width:125px;height:125px}'
            .'.footer{font-size:9px;color:#64748b;margin-top:15px}</style></head><body><div class="card">'
            .'<h1>SmartCity ACCESS Resident ID</h1><div class="subtitle">City of Alaminos resident identification</div>'
            .'<div class="content"><div class="details"><div class="name">'.$safe($resident->full_name).'</div>'
            .'<div class="label">Resident ID</div><div class="value">'.$safe($resident->resident_id).'</div>'
            .'<div class="label">Date of birth</div><div class="value">'.$safe($resident->birthDateIso()).'</div></div>'
            .'<div class="qr">'.$qrSvg.'<div class="label">Scan to verify</div></div></div>'
            .'<div class="footer">Generated '.now()->toDateTimeString().' · Verification code: '.$safe($resident->qr_code).'</div>'
            .'</div></body></html>';

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, 576, 360]);
        $dompdf->render();

        Storage::put($path, $dompdf->output());

        return $path;
    }

    /**
     * Find a resident by QR code.
     */
    public function findResidentByQrCode(string $qrCode): ?Resident
    {
        $qrCode = trim($qrCode);

        if (Str::startsWith($qrCode, 'QR-')) {
            $qrCode = Str::after($qrCode, 'QR-');
        }

        if (Str::startsWith($qrCode, 'AC-')) {
            return Resident::where('resident_id', Str::after($qrCode, 'AC-'))->first();
        }

        return Resident::where('resident_id', $qrCode)
            ->orWhere('qr_code', $qrCode)
            ->orWhere('qr_code', 'QR-'.$qrCode)
            ->first();
    }

    /**
     * Find a household by QR code.
     */
    public function findHouseholdByQrCode(string $qrCode): ?Household
    {
        return Household::where('qr_code', $qrCode)->orWhere('qr_code', 'QR-'.$qrCode)->first();
    }

    /**
     * Process a scanned QR code.
     *
     * @return array Information about the scanned QR code
     */
    public function processQrCode(string $qrCode): array
    {
        $qrCode = trim($qrCode);

        $result = [
            'type' => null,
            'id' => null,
            'object' => null,
            'found' => false,
            'message' => 'Invalid QR code format',
        ];

        // Check QR code format
        if (Str::startsWith($qrCode, 'AC-') or Str::startsWith($qrCode, 'RR-') or Str::startsWith($qrCode, 'R-') or Str::startsWith($qrCode, 'QR-R-')) {
            $result['type'] = 'resident';
            $resident = $this->findResidentByQrCode($qrCode);

            if ($resident) {
                $result['found'] = true;
                $result['object'] = $resident;
                $result['id'] = $resident->id;
                $result['message'] = 'Resident found: '.$resident->full_name;
            } else {
                $result['message'] = 'Resident not found with QR code: '.$qrCode;
            }
        } elseif (Str::startsWith($qrCode, 'HH-') or Str::startsWith($qrCode, 'QR-HH-')) {
            $result['type'] = 'household';
            $household = $this->findHouseholdByQrCode($qrCode);

            if ($household) {
                $result['found'] = true;
                $result['object'] = $household;
                $result['id'] = $household->id;
                $result['message'] = 'Household found: '.$household->household_id;
            } else {
                $result['message'] = 'Household not found with QR code: '.$qrCode;
            }
        } else {
            $resident = $this->findResidentByQrCode($qrCode);

            if ($resident) {
                $result['type'] = 'resident';
                $result['found'] = true;
                $result['object'] = $resident;
                $result['id'] = $resident->id;
                $result['message'] = 'Resident found: '.$resident->full_name;
            }
        }

        return $result;
    }
}
