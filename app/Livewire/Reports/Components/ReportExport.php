<?php

namespace App\Livewire\Reports\Components;

use Livewire\Component;
use Mary\Traits\Toast;
use Livewire\Attributes\On;

class ReportExport extends Component
{
    use Toast;

    // Report type
    public $reportType = 'distributions';

    // Export status
    public $isExporting = false;
    public $exportProgress = 0;
    public $exportedFilePath = null;

    // Export formats
    public $exportFormats = [
        'csv' => 'CSV',
        'excel' => 'Excel',
        'pdf' => 'PDF'
    ];

    // Export types - restructured for Mary UI select
    public $exportTypes = [
        ['regular', 'Standard Report'],
        ['residents-full', 'Full Resident Data']
    ];

    public $selectedFormat = 'csv';
    public $selectedExportType = 'regular';

    /**
     * Mount the component
     */
    public function mount($reportType)
    {
        $this->reportType = $reportType;
    }

    /**
     * Set report type when changed in parent
     */
    #[On('reportTypeUpdated')]
    public function setReportType($type)
    {
        // For debugging
        logger()->info('Export component received type update: ' . $type);

        $this->reportType = $type;

        // Reset to regular export type when changing report types
        // except for residents report which can have multiple export types
        if ($this->reportType !== 'residents') {
            $this->selectedExportType = 'regular';
        }
    }

    /**
     * Trigger export
     */
    public function export()
    {
        $this->isExporting = true;
        $this->exportProgress = 0;
        $this->exportedFilePath = null;

        try {
            // Emit event to parent to handle actual export
            $this->dispatch('exportRequested', [
                'format' => $this->selectedFormat,
                'type' => $this->selectedExportType
            ]);

            // Start progress simulation
            $this->simulateExportProgress();
        } catch (\Exception $e) {
            $this->error('Error initiating export: ' . $e->getMessage());
            $this->isExporting = false;
        }
    }

    /**
     * Simulate export progress
     */
    protected function simulateExportProgress()
    {
        // This is just for UI feedback - actual export is handled by parent
        $this->dispatch('showExportProgress');
    }

    /**
     * Handle export completed event from parent
     */
    #[On('exportCompleted')]
    public function handleExportCompleted($filePath)
    {
        $this->exportProgress = 100;
        $this->exportedFilePath = $filePath;

        // Small delay to show 100% completion before closing
        $this->success('Export completed successfully');
    }

    /**
     * Handle export failed event from parent
     */
    #[On('exportFailed')]
    public function handleExportFailed($errorMessage)
    {
        $this->exportProgress = 0;
        $this->isExporting = false;

        $this->error('Export failed: ' . $errorMessage);
    }

    /**
     * Close the export modal and reset
     */
    public function closeModal()
    {
        $this->isExporting = false;
    }

    /**
     * Determine if full resident export is available
     */
    protected function showResidentFullExport()
    {
        return $this->reportType === 'residents';
    }

    /**
     * Get export label based on report type
     */
    protected function getExportLabel()
    {
        // If this is a resident-full export and we're on the residents report
        if ($this->selectedExportType === 'residents-full' && $this->reportType === 'residents') {
            return 'Export Full Resident Data';
        }

        // Otherwise use standard labels
        return match ($this->reportType) {
            'distributions' => 'Export Distributions',
            'programs' => 'Export Programs',
            'residents' => 'Export Beneficiaries',
            'barangays' => 'Export Geographic Data',
            default => 'Export Report'
        };
    }

    /**
     * Render the component
     */
    public function render()
    {
        $exportLabel = $this->getExportLabel();
        $showResidentFullExport = $this->showResidentFullExport();

        return view('livewire.reports.components.report-export', [
            'exportLabel' => $exportLabel,
            'showResidentFullExport' => $showResidentFullExport
        ]);
    }
}
