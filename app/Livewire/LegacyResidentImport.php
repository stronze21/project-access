<?php

namespace App\Livewire;

use App\Models\LegacyImportBatch;
use App\Services\Legacy\LegacyCsvImporter;
use App\Services\Legacy\LegacyDataPromoter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Throwable;

class LegacyResidentImport extends Component
{
    use WithFileUploads;
    use WithPagination;

    public array $csvFiles = [];

    #[Url]
    public ?int $batch = null;

    public array $promotionReport = [];

    public string $confirmation = '';

    public bool $confirmSafePromotion = false;

    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(): void
    {
        $this->authorizeImporter();
        if ($this->batch) {
            LegacyImportBatch::findOrFail($this->batch);
        }
    }

    public function stage(LegacyCsvImporter $importer): void
    {
        $this->authorizeImporter();
        $validated = $this->validate([
            'csvFiles' => ['required', 'array', 'min:1', 'max:7'],
            'csvFiles.*' => ['required', 'file', 'mimes:csv,txt', 'max:102400'],
        ]);
        $directory = 'legacy-imports/'.Str::uuid();
        $paths = [];

        try {
            foreach ($validated['csvFiles'] as $file) {
                $filename = basename($file->getClientOriginalName());
                $storedPath = $file->storeAs($directory, $filename, 'local');
                $paths[] = Storage::disk('local')->path($storedPath);
            }
            $result = $importer->import($paths, false);
            $this->batch = $result['batch_id'];
            $this->noticeType = $result['already_imported'] ? 'warning' : 'success';
            $this->notice = $result['already_imported']
                ? "These exact files were already staged as batch #{$this->batch}."
                : "Legacy CSV files were validated and staged as batch #{$this->batch}.";
            $this->csvFiles = [];
            $this->promotionReport = [];
            $this->resetPage();
        } catch (Throwable $exception) {
            report($exception);
            $this->noticeType = 'error';
            $this->notice = 'Legacy import failed: '.$exception->getMessage();
        } finally {
            Storage::disk('local')->deleteDirectory($directory);
        }
    }

    public function selectBatch(int $batchId): void
    {
        $this->authorizeImporter();
        LegacyImportBatch::findOrFail($batchId);
        $this->batch = $batchId;
        $this->promotionReport = [];
        $this->confirmation = '';
        $this->confirmSafePromotion = false;
        $this->notice = null;
        $this->resetValidation();
    }

    public function preview(LegacyDataPromoter $promoter): void
    {
        $this->authorizeImporter();
        $batch = $this->selectedBatch();

        try {
            $this->promotionReport = $promoter->promote($batch, false);
            session()->put("legacy_import.previewed.{$batch->id}", $batch->manifest_checksum);
            $this->noticeType = 'success';
            $this->notice = 'Promotion preview completed. No canonical records were changed.';
            $this->confirmation = '';
            $this->confirmSafePromotion = false;
        } catch (Throwable $exception) {
            report($exception);
            $this->noticeType = 'error';
            $this->notice = 'Promotion preview failed: '.$exception->getMessage();
        }
    }

    public function promote(LegacyDataPromoter $promoter): void
    {
        $this->authorizeImporter();
        $batch = $this->selectedBatch();
        $this->validate([
            'confirmation' => ['required', 'in:PROMOTE-'.$batch->id],
            'confirmSafePromotion' => ['accepted'],
        ], [
            'confirmation.in' => 'Type PROMOTE-'.$batch->id.' exactly to confirm.',
            'confirmSafePromotion.accepted' => 'Confirm that you reviewed the promotion preview.',
        ]);

        if (! $this->previewIsCurrent($batch)) {
            $this->noticeType = 'error';
            $this->notice = 'Run a fresh promotion preview before committing this batch.';

            return;
        }

        try {
            $this->promotionReport = $promoter->promote($batch, true);
            session()->forget("legacy_import.previewed.{$batch->id}");
            $this->noticeType = 'success';
            $this->notice = "Batch #{$batch->id} promotion completed.";
            $this->confirmation = '';
            $this->confirmSafePromotion = false;
        } catch (Throwable $exception) {
            report($exception);
            $this->noticeType = 'error';
            $this->notice = 'Promotion failed: '.$exception->getMessage();
        }
    }

    public function render()
    {
        $selectedBatch = $this->batch ? LegacyImportBatch::findOrFail($this->batch) : null;
        $rowSummary = $selectedBatch
            ? $selectedBatch->rows()
                ->selectRaw('source_table, validation_status, COUNT(*) AS aggregate')
                ->groupBy('source_table', 'validation_status')
                ->orderBy('source_table')
                ->get()
            : collect();

        return view('livewire.legacy-resident-import', [
            'batches' => LegacyImportBatch::query()->latest()->paginate(10),
            'selectedBatch' => $selectedBatch,
            'rowSummary' => $rowSummary,
            'previewIsCurrent' => $selectedBatch && $this->previewIsCurrent($selectedBatch),
            'serverUploadLimit' => ini_get('upload_max_filesize'),
        ])->layout('layouts.app');
    }

    private function selectedBatch(): LegacyImportBatch
    {
        abort_unless($this->batch, 404);

        return LegacyImportBatch::findOrFail($this->batch);
    }

    private function previewIsCurrent(LegacyImportBatch $batch): bool
    {
        return session("legacy_import.previewed.{$batch->id}") === $batch->manifest_checksum;
    }

    private function authorizeImporter(): void
    {
        abort_unless(auth()->user()?->can('import-residents'), 403);
    }
}
