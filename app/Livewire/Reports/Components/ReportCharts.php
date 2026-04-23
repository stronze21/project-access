<?php

namespace App\Livewire\Reports\Components;

use Livewire\Component;

class ReportCharts extends Component
{
    // Report type and chart data
    public $reportType = 'distributions';
    public $chartData = [];

    // Chart display options
    public $showCharts = true;
    public $activeChartIndex = 0;

    /**
     * Mount the component
     */
    public function mount($reportType, $chartData = [])
    {
        $this->reportType = $reportType;
        $this->chartData = $chartData;
    }

    /**
     * Update chart data when parent component changes
     */
    public function updateChartData($chartData)
    {
        $this->chartData = $chartData;
        $this->dispatch('chartDataUpdated', $this->getChartConfigurations());
    }

    /**
     * Toggle chart visibility
     */
    public function toggleCharts()
    {
        $this->showCharts = !$this->showCharts;
    }

    /**
     * Change the active chart
     */
    public function setActiveChart($index)
    {
        $this->activeChartIndex = $index;
    }

    /**
     * Get chart configurations based on report type
     */
    protected function getChartConfigurations()
    {
        // Define chart metadata with IDs, titles and types
        return match ($this->reportType) {
            'distributions' => [
                [
                    'id' => 'distributionByDateChart',
                    'title' => 'Distribution by Date',
                    'type' => 'bar',
                    'dataKey' => 'byDate',
                    'description' => 'Shows distribution counts and amounts by date'
                ],
                [
                    'id' => 'distributionByProgramChart',
                    'title' => 'Distribution by Program',
                    'type' => 'doughnut',
                    'dataKey' => 'byProgram',
                    'description' => 'Shows breakdown of distributions by program'
                ]
            ],
            'programs' => [
                [
                    'id' => 'distributionsByProgramChart',
                    'title' => 'Distributions by Program',
                    'type' => 'bar',
                    'dataKey' => 'byProgram',
                    'description' => 'Shows distribution counts by program'
                ],
                [
                    'id' => 'amountByProgramChart',
                    'title' => 'Amount by Program',
                    'type' => 'pie',
                    'dataKey' => 'byAmount',
                    'description' => 'Shows distribution amounts by program'
                ]
            ],
            'residents' => [
                [
                    'id' => 'beneficiariesByDemographicChart',
                    'title' => 'Beneficiaries by Demographic',
                    'type' => 'bar',
                    'dataKey' => 'byDemographic',
                    'description' => 'Shows distribution of beneficiaries by demographic category'
                ]
            ],
            'barangays' => [
                [
                    'id' => 'distributionsByBarangayChart',
                    'title' => 'Distributions by Barangay',
                    'type' => 'bar',
                    'dataKey' => 'byBarangay',
                    'description' => 'Shows distribution counts by barangay'
                ],
                [
                    'id' => 'coverageByBarangayChart',
                    'title' => 'Household Coverage by Barangay',
                    'type' => 'bar',
                    'dataKey' => 'byCoverage',
                    'description' => 'Shows percentage of households reached in each barangay'
                ]
            ],
            default => []
        };
    }

    /**
     * Get active chart data
     */
    protected function getActiveChartData()
    {
        $chartConfigs = $this->getChartConfigurations();

        if (empty($chartConfigs) || !isset($chartConfigs[$this->activeChartIndex])) {
            return null;
        }

        $activeChart = $chartConfigs[$this->activeChartIndex];
        $dataKey = $activeChart['dataKey'];

        return [
            'config' => $activeChart,
            'data' => $this->chartData[$dataKey] ?? null
        ];
    }

    /**
     * Download chart as image
     */
    public function downloadChart($chartId)
    {
        $this->dispatch('downloadChart', ['chartId' => $chartId]);
    }

    /**
     * Render the component
     */
    public function render()
    {
        $chartConfigs = $this->getChartConfigurations();
        $activeChartData = $this->getActiveChartData();

        return view('livewire.reports.components.report-charts', [
            'chartConfigs' => $chartConfigs,
            'activeChartData' => $activeChartData
        ]);
    }
}
