<?php

namespace App\Services;

use App\Models\Resident;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ResidentMediaStagingService
{
    public const PHOTO_DIRECTORY = 'resident-media-staging/photos';

    public const SIGNATURE_DIRECTORY = 'resident-media-staging/signatures';

    private const PHOTO_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    public function stagePhoto(UploadedFile $file, string $residentId, string $extension): string
    {
        $extension = strtolower($extension);

        if (! in_array($extension, self::PHOTO_EXTENSIONS, true)) {
            throw new RuntimeException('Unsupported staged photo format.');
        }

        foreach ($this->stagedPhotoPaths($residentId) as $existingPath) {
            Storage::disk('public')->delete($existingPath);
        }

        return $this->storeUploadedFile(
            $file,
            self::PHOTO_DIRECTORY,
            "{$residentId}.{$extension}"
        );
    }

    public function stageSignature(UploadedFile $file, string $residentId): string
    {
        return $this->storeUploadedFile(
            $file,
            self::SIGNATURE_DIRECTORY,
            "{$residentId}_signature.png"
        );
    }

    /**
     * Attach any staged media whose filename matches the new resident ID.
     *
     * @return array{photo: bool, signature: bool}
     */
    public function attachTo(Resident $resident): array
    {
        $disk = Storage::disk('public');
        $updates = [];
        $stagedPathsToDelete = [];
        $attached = ['photo' => false, 'signature' => false];

        if (empty($resident->photo_path)) {
            foreach ($this->stagedPhotoPaths($resident->resident_id) as $stagedPath) {
                if (! $disk->exists($stagedPath)) {
                    continue;
                }

                $destination = 'resident-photos/'.basename($stagedPath);
                $this->copyOrFail($stagedPath, $destination);
                $updates['photo_path'] = $destination;
                $stagedPathsToDelete[] = $stagedPath;
                $attached['photo'] = true;
                break;
            }
        }

        $stagedSignature = self::SIGNATURE_DIRECTORY."/{$resident->resident_id}_signature.png";
        if (empty($resident->signature) && $disk->exists($stagedSignature)) {
            $contents = $disk->get($stagedSignature);
            $destination = "resident-signatures/{$resident->resident_id}_signature.png";

            if (! $disk->put($destination, $contents)) {
                throw new RuntimeException('The staged resident signature could not be copied.');
            }

            $updates['signature'] = 'data:image/png;base64,'.base64_encode($contents);
            $updates['signature_status'] = 'verified';
            $stagedPathsToDelete[] = $stagedSignature;
            $attached['signature'] = true;
        }

        if ($updates !== []) {
            $resident->forceFill($updates)->saveQuietly();
            $disk->delete($stagedPathsToDelete);
        }

        return $attached;
    }

    /** @return list<string> */
    private function stagedPhotoPaths(string $residentId): array
    {
        return array_map(
            fn (string $extension) => self::PHOTO_DIRECTORY."/{$residentId}.{$extension}",
            self::PHOTO_EXTENSIONS
        );
    }

    private function storeUploadedFile(UploadedFile $file, string $directory, string $filename): string
    {
        $path = $file->storeAs($directory, $filename, 'public');

        if (! is_string($path)) {
            throw new RuntimeException('The resident media file could not be staged.');
        }

        return $path;
    }

    private function copyOrFail(string $source, string $destination): void
    {
        $disk = Storage::disk('public');

        if (! $disk->put($destination, $disk->get($source))) {
            throw new RuntimeException('The staged resident photo could not be copied.');
        }
    }
}
