<?php

namespace App\Services;

use App\Models\Resident;
use DOMDocument;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class ScholarPinImporter
{
    public function preview(string $path): array
    {
        $pins = $this->readPins($path);
        $validPins = [];
        $invalidPins = [];

        foreach ($pins as $pin) {
            $pin = trim($pin);
            if (! preg_match('/^\d{2}-\d{5}$/', $pin)) {
                $invalidPins[] = $pin;

                continue;
            }
            $validPins[$pin] = true;
        }

        $validPins = array_keys($validPins);
        $matchedPins = Resident::query()
            ->whereIn('resident_id', $validPins)
            ->pluck('resident_id')
            ->all();

        return [
            'source_rows' => count($pins),
            'unique_valid' => count($validPins),
            'matched' => count($matchedPins),
            'already_scholars' => Resident::query()
                ->whereIn('resident_id', $matchedPins)
                ->where('is_scholar', true)
                ->count(),
            'unmatched' => array_values(array_diff($validPins, $matchedPins)),
            'invalid' => array_values(array_unique(array_filter($invalidPins))),
            'matched_pins' => $matchedPins,
        ];
    }

    public function import(string $path): array
    {
        $report = $this->preview($path);
        $report['updated'] = Resident::query()
            ->whereIn('resident_id', $report['matched_pins'])
            ->where('is_scholar', false)
            ->update([
                'is_scholar' => true,
                'updated_at' => now(),
            ]);

        return $report;
    }

    private function readPins(string $path): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new RuntimeException('The Excel workbook could not be opened.');
        }

        try {
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheetXml === false) {
                throw new RuntimeException('The workbook does not contain a readable first worksheet.');
            }

            $sharedStrings = $this->sharedStrings($zip);
            $document = new DOMDocument;
            $document->loadXML($sheetXml, LIBXML_NONET | LIBXML_NOBLANKS);
            $xpath = new DOMXPath($document);
            $rows = $xpath->query('//*[local-name()="sheetData"]/*[local-name()="row"]');
            $pins = [];
            $pinColumn = null;

            foreach ($rows as $row) {
                foreach ($xpath->query('./*[local-name()="c"]', $row) as $cell) {
                    $reference = $cell->attributes?->getNamedItem('r')?->nodeValue ?? '';
                    preg_match('/^[A-Z]+/', $reference, $columnMatch);
                    $column = $columnMatch[0] ?? '';
                    $value = $this->cellValue($xpath, $cell, $sharedStrings);

                    if ($pinColumn === null && strcasecmp(trim($value), 'PIN') === 0) {
                        $pinColumn = $column;

                        continue;
                    }
                    if ($pinColumn !== null && $column === $pinColumn && trim($value) !== '') {
                        $pins[] = trim($value);
                    }
                }
            }

            if ($pinColumn === null) {
                throw new RuntimeException('A PIN column was not found in the first worksheet.');
            }

            return $pins;
        } finally {
            $zip->close();
        }
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $document = new DOMDocument;
        $document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS);
        $xpath = new DOMXPath($document);
        $strings = [];
        foreach ($xpath->query('//*[local-name()="si"]') as $item) {
            $value = '';
            foreach ($xpath->query('.//*[local-name()="t"]', $item) as $text) {
                $value .= $text->textContent;
            }
            $strings[] = $value;
        }

        return $strings;
    }

    private function cellValue(DOMXPath $xpath, $cell, array $sharedStrings): string
    {
        $type = $cell->attributes?->getNamedItem('t')?->nodeValue;
        if ($type === 'inlineStr') {
            return (string) $xpath->evaluate('string(.//*[local-name()="t"])', $cell);
        }

        $value = (string) $xpath->evaluate('string(./*[local-name()="v"])', $cell);

        return $type === 's' ? (string) ($sharedStrings[(int) $value] ?? '') : $value;
    }
}
