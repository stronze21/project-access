<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

class AndroidApkMetadataReader
{
    private const STRING_POOL = 0x0001;
    private const START_ELEMENT = 0x0102;
    private const UTF8_FLAG = 0x00000100;
    private const TYPE_STRING = 0x03;

    /** @return array{version_name: string, version_code: string} */
    public function read(string $apkPath): array
    {
        $zip = new ZipArchive;

        if ($zip->open($apkPath) !== true) {
            throw new RuntimeException('The selected file is not a readable APK archive.');
        }

        $manifest = $zip->getFromName('AndroidManifest.xml');
        $zip->close();

        if (! is_string($manifest) || strlen($manifest) < 8 || strlen($manifest) > 5 * 1024 * 1024) {
            throw new RuntimeException('The APK does not contain a valid Android manifest.');
        }

        return $this->parseManifest($manifest);
    }

    /** @return array{version_name: string, version_code: string} */
    private function parseManifest(string $manifest): array
    {
        $strings = [];
        $offset = $this->u16($manifest, 2);
        $length = strlen($manifest);

        while ($offset + 8 <= $length) {
            $type = $this->u16($manifest, $offset);
            $headerSize = $this->u16($manifest, $offset + 2);
            $chunkSize = $this->u32($manifest, $offset + 4);

            if ($headerSize < 8 || $chunkSize < $headerSize || $offset + $chunkSize > $length) {
                throw new RuntimeException('The APK Android manifest is malformed.');
            }

            if ($type === self::STRING_POOL) {
                $strings = $this->parseStringPool($manifest, $offset, $headerSize, $chunkSize);
            } elseif ($type === self::START_ELEMENT && $strings !== []) {
                $metadata = $this->parseManifestElement($manifest, $offset, $strings);
                if ($metadata !== null) {
                    return $metadata;
                }
            }

            $offset += $chunkSize;
        }

        throw new RuntimeException('The APK version metadata could not be read.');
    }

    /** @return list<string> */
    private function parseStringPool(string $data, int $offset, int $headerSize, int $chunkSize): array
    {
        $stringCount = $this->u32($data, $offset + 8);
        $flags = $this->u32($data, $offset + 16);
        $stringsStart = $this->u32($data, $offset + 20);
        $offsetsStart = $offset + $headerSize;
        $stringsBase = $offset + $stringsStart;

        if ($stringCount > 100000 || $offsetsStart + ($stringCount * 4) > $offset + $chunkSize) {
            throw new RuntimeException('The APK string table is malformed.');
        }

        $strings = [];
        for ($index = 0; $index < $stringCount; $index++) {
            $stringOffset = $this->u32($data, $offsetsStart + ($index * 4));
            $position = $stringsBase + $stringOffset;
            $strings[] = ($flags & self::UTF8_FLAG) !== 0
                ? $this->readUtf8String($data, $position)
                : $this->readUtf16String($data, $position);
        }

        return $strings;
    }

    /** @param list<string> $strings
     *  @return array{version_name: string, version_code: string}|null
     */
    private function parseManifestElement(string $data, int $offset, array $strings): ?array
    {
        $nameIndex = $this->u32($data, $offset + 20);
        if (($strings[$nameIndex] ?? null) !== 'manifest') {
            return null;
        }

        $attributeStart = $this->u16($data, $offset + 24);
        $attributeSize = $this->u16($data, $offset + 26);
        $attributeCount = $this->u16($data, $offset + 28);
        $attributesOffset = $offset + 16 + $attributeStart;
        $values = [];

        if ($attributeSize < 20 || $attributeCount > 1000) {
            throw new RuntimeException('The APK manifest attributes are malformed.');
        }

        for ($index = 0; $index < $attributeCount; $index++) {
            $attributeOffset = $attributesOffset + ($index * $attributeSize);
            $attributeName = $strings[$this->u32($data, $attributeOffset + 4)] ?? null;
            if (! in_array($attributeName, ['versionName', 'versionCode'], true)) {
                continue;
            }

            $rawValueIndex = $this->u32($data, $attributeOffset + 8);
            $valueType = ord($data[$attributeOffset + 15]);
            $typedValue = $this->u32($data, $attributeOffset + 16);
            $values[$attributeName] = $rawValueIndex !== 0xffffffff
                ? ($strings[$rawValueIndex] ?? '')
                : ($valueType === self::TYPE_STRING ? ($strings[$typedValue] ?? '') : (string) $typedValue);
        }

        if (($values['versionName'] ?? '') === '' || ($values['versionCode'] ?? '') === '') {
            throw new RuntimeException('The APK is missing its version name or version code.');
        }

        return [
            'version_name' => $values['versionName'],
            'version_code' => $values['versionCode'],
        ];
    }

    private function readUtf8String(string $data, int $position): string
    {
        [, $position] = $this->readLength8($data, $position);
        [$byteLength, $position] = $this->readLength8($data, $position);

        return substr($data, $position, $byteLength);
    }

    private function readUtf16String(string $data, int $position): string
    {
        [$characterLength, $position] = $this->readLength16($data, $position);
        $value = substr($data, $position, $characterLength * 2);

        return mb_convert_encoding($value, 'UTF-8', 'UTF-16LE');
    }

    /** @return array{int, int} */
    private function readLength8(string $data, int $position): array
    {
        $length = ord($data[$position++]);
        if (($length & 0x80) !== 0) {
            $length = (($length & 0x7f) << 8) | ord($data[$position++]);
        }

        return [$length, $position];
    }

    /** @return array{int, int} */
    private function readLength16(string $data, int $position): array
    {
        $length = $this->u16($data, $position);
        $position += 2;
        if (($length & 0x8000) !== 0) {
            $length = (($length & 0x7fff) << 16) | $this->u16($data, $position);
            $position += 2;
        }

        return [$length, $position];
    }

    private function u16(string $data, int $offset): int
    {
        return unpack('v', substr($data, $offset, 2))[1];
    }

    private function u32(string $data, int $offset): int
    {
        return unpack('V', substr($data, $offset, 4))[1];
    }
}
