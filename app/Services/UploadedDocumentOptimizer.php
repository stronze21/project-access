<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class UploadedDocumentOptimizer
{
    public const MAX_DIMENSION = 2000;

    public const JPEG_QUALITY = 80;

    /**
     * Store an uploaded document, compressing images to reduce disk usage.
     * Non-image files (e.g. PDF) are stored as-is.
     *
     * @return array{path: string, mime_type: string, size_bytes: int, original_name: string, optimized: bool}
     */
    public function store(UploadedFile $file, string $directory, string $disk = 'local'): array
    {
        $mime = strtolower((string) ($file->getMimeType() ?: $file->getClientMimeType() ?: ''));

        if ($this->isCompressibleImage($mime) && extension_loaded('gd')) {
            try {
                return $this->storeCompressedImage($file, $directory, $disk);
            } catch (RuntimeException) {
                // Fall through to raw storage if the image cannot be decoded/resized.
            }
        }

        $path = $file->store($directory, $disk);

        return [
            'path' => $path,
            'mime_type' => $mime !== '' ? $mime : 'application/octet-stream',
            'size_bytes' => (int) Storage::disk($disk)->size($path),
            'original_name' => $file->getClientOriginalName(),
            'optimized' => false,
        ];
    }

    private function isCompressibleImage(string $mime): bool
    {
        return in_array($mime, [
            'image/jpeg',
            'image/jpg',
            'image/pjpeg',
            'image/png',
            'image/webp',
            'image/gif',
        ], true);
    }

    /**
     * @return array{path: string, mime_type: string, size_bytes: int, original_name: string, optimized: bool}
     */
    private function storeCompressedImage(UploadedFile $file, string $directory, string $disk): array
    {
        $sourceBytes = file_get_contents($file->getRealPath() ?: '');
        $source = $sourceBytes === false ? false : @imagecreatefromstring($sourceBytes);
        if ($source === false) {
            throw new RuntimeException('The file contents are not a readable image.');
        }

        try {
            $width = imagesx($source);
            $height = imagesy($source);
            $scale = min(1, self::MAX_DIMENSION / max($width, $height, 1));
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));

            $target = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($target === false) {
                throw new RuntimeException('The server could not allocate memory to resize this image.');
            }

            $white = imagecolorallocate($target, 255, 255, 255);
            imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $white);

            if (! imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height)) {
                imagedestroy($target);
                throw new RuntimeException('The image could not be resized.');
            }

            ob_start();
            $encoded = imagejpeg($target, null, self::JPEG_QUALITY);
            $contents = ob_get_clean();
            imagedestroy($target);

            if (! $encoded || ! is_string($contents) || $contents === '') {
                throw new RuntimeException('The resized image could not be encoded.');
            }

            $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeBase = Str::slug($baseName !== '' ? $baseName : 'document') ?: 'document';
            $filename = $safeBase.'-'.Str::lower(Str::random(8)).'.jpg';
            $path = trim($directory, '/').'/'.$filename;

            if (! Storage::disk($disk)->put($path, $contents)) {
                throw new RuntimeException('The compressed image could not be written to storage.');
            }

            $originalName = $safeBase.'.jpg';

            return [
                'path' => $path,
                'mime_type' => 'image/jpeg',
                'size_bytes' => strlen($contents),
                'original_name' => $originalName,
                'optimized' => true,
            ];
        } finally {
            imagedestroy($source);
        }
    }
}
