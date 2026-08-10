<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ResidentPhotoProcessor
{
    public const MAX_DIMENSION = 1600;

    public function store(UploadedFile $file, string $directory, string $filename): string
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('Server image compression is unavailable because the GD extension is not enabled.');
        }

        $sourceBytes = file_get_contents($file->getRealPath());
        $source = $sourceBytes === false ? false : @imagecreatefromstring($sourceBytes);
        if ($source === false) {
            throw new RuntimeException('The file contents are not a readable JPG or PNG image.');
        }

        try {
            $width = imagesx($source);
            $height = imagesy($source);
            $scale = min(1, self::MAX_DIMENSION / max($width, $height));
            $targetWidth = max(1, (int) round($width * $scale));
            $targetHeight = max(1, (int) round($height * $scale));
            $target = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($target === false) {
                throw new RuntimeException('The server could not allocate memory to resize this image.');
            }

            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            if ($extension === 'png') {
                imagealphablending($target, false);
                imagesavealpha($target, true);
                $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
                imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
            }

            if (! imagecopyresampled($target, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height)) {
                imagedestroy($target);
                throw new RuntimeException('The image could not be resized.');
            }

            ob_start();
            $encoded = $extension === 'png' ? imagepng($target, null, 8) : imagejpeg($target, null, 85);
            $contents = ob_get_clean();
            imagedestroy($target);

            if (! $encoded || ! is_string($contents) || $contents === '') {
                throw new RuntimeException('The resized image could not be encoded.');
            }

            $path = trim($directory, '/').'/'.$filename;
            if (! Storage::disk('public')->put($path, $contents)) {
                throw new RuntimeException('The compressed image could not be written to storage.');
            }

            return $path;
        } finally {
            imagedestroy($source);
        }
    }
}
