<?php

namespace Tests\Feature;

use App\Services\ExportService;
use App\Services\Reports\ResidentSignature;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ResidentSignatureExportTest extends TestCase
{
    public function test_excel_embeds_signatures_in_the_correct_rows_and_preserves_text(): void
    {
        Storage::fake();
        $image = imagecreatetruecolor(160, 55);
        ob_start();
        imagepng($image);
        $signature = 'data:image/png;base64,'.base64_encode(ob_get_clean());
        imagedestroy($image);

        foreach ([['Name'], ['Name', 'SIGNATURE']] as $headers) {
            $path = app(ExportService::class)->generateResidentExcel(
                [['=1+1', 'YES'], ['000123', 'NO'], ['Third', 'YES']],
                $headers,
                'residents.csv',
                [['signature' => $signature], ['signature' => null], ['signature' => $signature]],
            );
            $book = IOFactory::load(Storage::path($path));
            $sheet = $book->getActiveSheet();
            $this->assertSame('=1+1', $sheet->getCell('A2')->getValue());
            $this->assertSame('s', $sheet->getCell('A2')->getDataType());
            $this->assertSame('000123', $sheet->getCell('A3')->getValue());
            $this->assertSame('No signature', $sheet->getCell('B3')->getValue());
            $this->assertCount(2, $sheet->getDrawingCollection());
            $this->assertSame('B2', $sheet->getDrawingCollection()[0]->getCoordinates());
            $this->assertSame('B4', $sheet->getDrawingCollection()[1]->getCoordinates());
            $book->disconnectWorksheets();
        }
    }

    public function test_missing_invalid_and_external_signature_sources_are_ignored(): void
    {
        foreach ([null, '', 'missing.png', '../.env', 'https://example.com/signature.png', 'data:image/png;base64,bm90IGFuIGltYWdl'] as $signature) {
            $this->assertNull(ResidentSignature::image($signature));
        }
    }
}
