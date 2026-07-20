<?php

namespace App\Livewire;

use App\Models\Resident;
use App\Services\ResidentMediaStagingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;
use Throwable;

class ResidentPhotoSignatureManager extends Component
{
    use Toast;
    use WithFileUploads;

    public const MAX_FILES_PER_BATCH = 20;

    public const MAX_BATCH_SIZE_MB = 60;

    public array $photoFiles = [];

    public array $signatureFiles = [];

    public array $photoResults = [];

    public array $signatureResults = [];

    public function mount(): void
    {
        $this->authorizeImport();
    }

    public function updatedPhotoFiles(): void
    {
        $this->resetValidation('photoFiles');
        if (! $this->validateBatchLimits($this->photoFiles, 'photoFiles', 'photo')) {
            $this->photoResults = [];

            return;
        }

        $this->photoResults = $this->analyzeFiles($this->photoFiles, 'photo');
    }

    public function updatedSignatureFiles(): void
    {
        $this->resetValidation('signatureFiles');
        if (! $this->validateBatchLimits($this->signatureFiles, 'signatureFiles', 'signature')) {
            $this->signatureResults = [];

            return;
        }

        $this->signatureResults = $this->analyzeFiles($this->signatureFiles, 'signature');
    }

    public function importPhotos(ResidentMediaStagingService $mediaStagingService): void
    {
        $this->authorizeImport();
        if (! $this->validateBatchLimits($this->photoFiles, 'photoFiles', 'photo')) {
            return;
        }
        $this->photoResults = $this->analyzeFiles($this->photoFiles, 'photo', $this->photoResults);
        $imported = 0;

        foreach ($this->photoResults as $index => $result) {
            if (! $result['valid'] || ($result['imported'] ?? false) || ! isset($this->photoFiles[$index])) {
                continue;
            }

            try {
                if ($result['resident_db_id']) {
                    $resident = Resident::findOrFail($result['resident_db_id']);
                    $path = $this->photoFiles[$index]->storeAs(
                        'resident-photos',
                        $result['file_name'],
                        'public'
                    );

                    if ($resident->photo_path && $resident->photo_path !== $path) {
                        Storage::disk('public')->delete($resident->photo_path);
                    }

                    $resident->update(['photo_path' => $path]);
                    $this->markImported($this->photoResults, $index);
                } else {
                    $mediaStagingService->stagePhoto(
                        $this->photoFiles[$index],
                        $result['resident_id'],
                        pathinfo($result['file_name'], PATHINFO_EXTENSION)
                    );
                    $this->markImported($this->photoResults, $index, 'Staged until this resident is registered');
                }
                $imported++;
            } catch (Throwable $exception) {
                report($exception);
                $this->markFailed($this->photoResults, $index, 'The photo could not be saved.');
            }
        }

        $this->notifyImportResult($imported, 'photo');
    }

    public function importSignatures(ResidentMediaStagingService $mediaStagingService): void
    {
        $this->authorizeImport();
        if (! $this->validateBatchLimits($this->signatureFiles, 'signatureFiles', 'signature')) {
            return;
        }
        $this->signatureResults = $this->analyzeFiles($this->signatureFiles, 'signature', $this->signatureResults);
        $imported = 0;

        foreach ($this->signatureResults as $index => $result) {
            if (! $result['valid'] || ($result['imported'] ?? false) || ! isset($this->signatureFiles[$index])) {
                continue;
            }

            try {
                if ($result['resident_db_id']) {
                    $resident = Resident::findOrFail($result['resident_db_id']);
                    $contents = file_get_contents($this->signatureFiles[$index]->getRealPath());
                    $this->signatureFiles[$index]->storeAs(
                        'resident-signatures',
                        $result['file_name'],
                        'public'
                    );
                    $resident->update([
                        'signature' => 'data:image/png;base64,'.base64_encode($contents),
                        'signature_status' => 'verified',
                    ]);
                    $this->markImported($this->signatureResults, $index);
                } else {
                    $mediaStagingService->stageSignature(
                        $this->signatureFiles[$index],
                        $result['resident_id']
                    );
                    $this->markImported($this->signatureResults, $index, 'Staged until this resident is registered');
                }
                $imported++;
            } catch (Throwable $exception) {
                report($exception);
                $this->markFailed($this->signatureResults, $index, 'The signature could not be saved.');
            }
        }

        $this->notifyImportResult($imported, 'signature');
    }

    public function clearPhotos(): void
    {
        $this->reset('photoFiles', 'photoResults');
        $this->resetValidation('photoFiles');
    }

    public function clearSignatures(): void
    {
        $this->reset('signatureFiles', 'signatureResults');
        $this->resetValidation('signatureFiles');
    }

    public function render()
    {
        return view('livewire.resident-photo-signature-manager')->layout('layouts.app');
    }

    private function analyzeFiles(array $files, string $type, array $existing = []): array
    {
        $results = [];
        $residentIdsSeen = [];

        foreach ($files as $index => $file) {
            $fileName = basename($file->getClientOriginalName());
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $baseName = pathinfo($fileName, PATHINFO_FILENAME);
            $residentId = $type === 'signature' && str_ends_with(strtolower($baseName), '_signature')
                ? substr($baseName, 0, -10)
                : $baseName;
            $valid = true;
            $message = 'Ready to import';

            $rules = $type === 'photo'
                ? ['file' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:10240']]
                : ['file' => ['required', 'image', 'mimes:png', 'max:10240']];
            $validator = Validator::make(['file' => $file], $rules);

            if ($validator->fails()) {
                $valid = false;
                $message = $type === 'photo'
                    ? 'Photo must be a JPG, JPEG, or PNG image up to 10 MB.'
                    : 'Signature must be a PNG image up to 10 MB.';
            } elseif ($type === 'photo' && ! in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                $valid = false;
                $message = 'Photo filename must end in .jpg, .jpeg, or .png.';
            } elseif ($type === 'signature' && ($extension !== 'png' || ! str_ends_with(strtolower($baseName), '_signature'))) {
                $valid = false;
                $message = 'Signature filename must be {resident_id}_signature.png.';
            }

            $resident = $valid ? Resident::where('resident_id', $residentId)->first() : null;
            if ($resident && $resident->resident_id !== $residentId) {
                $resident = null;
            }
            if ($valid && ! $resident) {
                $message = 'No resident exists yet; ready to stage for automatic mapping.';
            }
            if ($valid && in_array($residentId, $residentIdsSeen, true)) {
                $valid = false;
                $message = 'Duplicate file for this resident in the current batch.';
            }

            $residentIdsSeen[] = $residentId;
            $wasImported = ($existing[$index]['file_name'] ?? null) === $fileName
                && ($existing[$index]['imported'] ?? false);

            $results[$index] = [
                'file_name' => $fileName,
                'resident_id' => $residentId,
                'resident_db_id' => $resident?->id,
                'resident_name' => $resident?->full_name,
                'previewable' => str_starts_with((string) $file->getMimeType(), 'image/'),
                'valid' => $valid,
                'imported' => $wasImported,
                'message' => $wasImported ? 'Imported successfully' : $message,
            ];
        }

        return $results;
    }

    private function markImported(array &$results, int $index, string $message = 'Imported successfully'): void
    {
        $results[$index]['imported'] = true;
        $results[$index]['message'] = $message;
    }

    private function markFailed(array &$results, int $index, string $message): void
    {
        $results[$index]['valid'] = false;
        $results[$index]['message'] = $message;
    }

    private function notifyImportResult(int $imported, string $type): void
    {
        if ($imported > 0) {
            $this->success("{$imported} resident {$type}".($imported === 1 ? ' was' : 's were').' imported or staged successfully.');

            return;
        }

        $this->warning("No valid {$type} files were available to import.");
    }

    private function validateBatchLimits(array $files, string $field, string $type): bool
    {
        if (count($files) > self::MAX_FILES_PER_BATCH) {
            $this->addError(
                $field,
                'Too many files selected. Choose no more than '.self::MAX_FILES_PER_BATCH." {$type} files per batch."
            );

            return false;
        }

        $totalBytes = collect($files)->sum(fn ($file) => (int) $file->getSize());
        if ($totalBytes > self::MAX_BATCH_SIZE_MB * 1024 * 1024) {
            $this->addError(
                $field,
                'The selected files are too large as a group. Keep the total batch size at or below '.self::MAX_BATCH_SIZE_MB.' MB.'
            );

            return false;
        }

        return true;
    }

    private function authorizeImport(): void
    {
        abort_unless(auth()->user()?->can('import-residents'), 403);
    }
}
