<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ResidentSignatureProcessor
{
    public function convert(UploadedFile $file): string
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('Server signature conversion is unavailable.');
        }

        $bytes = file_get_contents($file->getRealPath());
        $info = $bytes === false ? false : @getimagesizefromstring($bytes);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG], true)) {
            throw new RuntimeException('The signature is not a readable JPG, JPEG, or PNG image.');
        }
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            throw new RuntimeException('The signature image could not be decoded.');
        }

        try {
            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
            $width = imagesx($image);
            $height = imagesy($image);
            for ($y = 0; $y < $height; $y++) {
                for ($x = 0; $x < $width; $x++) {
                    $color = imagecolorat($image, $x, $y);
                    if ((($color >> 16) & 255) >= 245 && (($color >> 8) & 255) >= 245 && ($color & 255) >= 245) {
                        imagesetpixel($image, $x, $y, $transparent);
                    }
                }
            }

            ob_start();
            try {
                $encoded = imagepng($image);
                $contents = ob_get_contents();
            } finally {
                ob_end_clean();
            }
            if (! $encoded || ! is_string($contents) || $contents === '') {
                throw new RuntimeException('The signature could not be converted to PNG.');
            }

            return $contents;
        } finally {
            imagedestroy($image);
        }
    }

    public function store(UploadedFile $file, string $directory, string $residentId): string
    {
        $contents = $this->convert($file);
        $path = trim($directory, '/')."/{$residentId}_signature.png";
        if (! Storage::disk('public')->put($path, $contents)) {
            throw new RuntimeException('The signature could not be written to storage.');
        }

        return $contents;
    }
}
