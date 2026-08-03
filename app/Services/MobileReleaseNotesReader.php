<?php

namespace App\Services;

class MobileReleaseNotesReader
{
    public function forRelease(string $versionName, string $versionCode): ?string
    {
        $path = base_path('ProjectAccessApp/RELEASE_NOTES.md');
        if (! is_file($path) || ! is_readable($path)) {
            return null;
        }

        $contents = file_get_contents($path);
        if (! is_string($contents) || $contents === '') {
            return null;
        }

        $version = preg_quote(trim($versionName), '/');
        $build = preg_quote(trim($versionCode), '/');
        $pattern = "/^##\\s+{$version}\\s+\\(build\\s+{$build}\\)\\s*\\R(?<notes>.*?)(?=^##\\s+|\\z)/msi";

        if (! preg_match($pattern, $contents, $matches)) {
            return null;
        }

        $notes = collect(preg_split('/\r\n|\r|\n/', trim($matches['notes'])) ?: [])
            ->map(fn (string $line) => preg_replace('/^\s*[-*]\s+/', '• ', trim($line)))
            ->filter()
            ->implode(PHP_EOL);

        return $notes !== '' ? $notes : null;
    }
}
