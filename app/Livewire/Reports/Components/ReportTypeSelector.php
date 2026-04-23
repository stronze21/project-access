<?php

namespace App\Livewire\Reports\Components;

use Livewire\Component;

class ReportTypeSelector extends Component
{
    // Selected report type
    public $reportType = 'distributions';

    // Available report types
    public $reportTypes = [
        'distributions' => [
            'id' => 'report-distributions',
            'label' => 'Distributions Report',
            'hint' => 'Analysis of all aid distributions',
            'icon' => 'o-arrow-right-circle'
        ],
        'programs' => [
            'id' => 'report-programs',
            'label' => 'Programs Report',
            'hint' => 'Performance analysis of ayuda programs',
            'icon' => 'o-clipboard-document-list'
        ],
        'residents' => [
            'id' => 'report-residents',
            'label' => 'Beneficiaries Report',
            'hint' => 'Analysis of aid recipients',
            'icon' => 'o-user'
        ],
        'barangays' => [
            'id' => 'report-barangays',
            'label' => 'Geographic Report',
            'hint' => 'Analysis by location',
            'icon' => 'o-map-pin'
        ],
        'residents-with-id' => [
            'id' => 'report-residents-with-id',
            'label' => 'Residents With ID Report',
            'hint' => 'List of residents with signed ID',
            'icon' => 'o-identification'
        ]
    ];

    /**
     * Mount the component
     */
    public function mount($initialReportType = 'distributions')
    {
        $this->reportType = $initialReportType;
    }

    /**
     * Change report type when selected by user
     */
    public function selectReportType($type)
    {
        if (empty($type) || !array_key_exists($type, $this->reportTypes)) {
            return;
        }

        // Update local state first
        $this->reportType = $type;

        // For debugging - log to browser console
        $this->js("console.log('Report type selected: $type')");

        // Dispatch event using Livewire 3 syntax
        $this->dispatch('reportTypeChanged', $type);
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.reports.components.report-type-selector');
    }
}