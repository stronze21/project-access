<?php

namespace App\Livewire;

use App\Services\ScholarPinImporter;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class ScholarPinImport extends Component
{
    use WithFileUploads;

    #[Validate('required|file|mimes:xlsx|max:10240')]
    public $workbook;

    public array $report = [];

    public bool $confirmImport = false;

    public ?string $notice = null;

    public string $noticeType = 'success';

    public function mount(): void
    {
        $this->authorizeImport();
    }

    public function updatedWorkbook(): void
    {
        $this->report = [];
        $this->confirmImport = false;
        $this->notice = null;
    }

    public function preview(ScholarPinImporter $importer): void
    {
        $this->authorizeImport();
        $this->validateOnly('workbook');

        try {
            $this->report = $importer->preview($this->workbook->getRealPath());
            $this->notice = 'Workbook validated. Review the matches before applying the scholar flags.';
            $this->noticeType = 'success';
        } catch (Throwable $exception) {
            report($exception);
            $this->notice = 'Workbook validation failed: '.$exception->getMessage();
            $this->noticeType = 'error';
        }
    }

    public function import(ScholarPinImporter $importer): void
    {
        $this->authorizeImport();
        $this->validate([
            'workbook' => ['required', 'file', 'mimes:xlsx', 'max:10240'],
            'confirmImport' => ['accepted'],
        ]);

        try {
            $this->report = $importer->import($this->workbook->getRealPath());
            $this->notice = number_format($this->report['updated']).' residents were newly marked as scholars.';
            $this->noticeType = 'success';
            $this->confirmImport = false;
        } catch (Throwable $exception) {
            report($exception);
            $this->notice = 'Scholar import failed: '.$exception->getMessage();
            $this->noticeType = 'error';
        }
    }

    public function render()
    {
        return view('livewire.scholar-pin-import')->layout('layouts.app');
    }

    private function authorizeImport(): void
    {
        abort_unless(auth()->user()?->can('import-residents'), 403);
    }
}
