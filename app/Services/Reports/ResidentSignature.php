<?php

namespace App\Services\Reports;

class ResidentSignature
{
    public static function image(?string $signature): ?string
    {
        if (! $signature) {
            return null;
        }

        if (preg_match('#^data:image/(png|jpeg|gif);base64,(.+)$#s', $signature, $matches)) {
            $bytes = base64_decode($matches[2], true);
        } else {
            // Only read files inside the public directory; never fetch remote URLs.
            $root = realpath(public_path());
            $path = realpath(public_path(ltrim($signature, '/')));
            if (! $root || ! $path || ! str_starts_with($path, $root.DIRECTORY_SEPARATOR) || ! is_file($path)) {
                return null;
            }
            $bytes = file_get_contents($path);
        }

        $info = $bytes ? @getimagesizefromstring($bytes) : false;
        if (! $info || ! in_array($info['mime'], ['image/png', 'image/jpeg', 'image/gif'], true)) {
            return null;
        }

        return 'data:'.$info['mime'].';base64,'.base64_encode($bytes);
    }
}
