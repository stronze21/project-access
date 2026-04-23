<?php

namespace App\Livewire\Reports\Components;

use Livewire\Component;

class ReportSummary extends Component
{
    // Report type and summary data
    public $reportType = 'distributions';
    public $summaryData = [];

    // Display options
    public $showSummary = true;

    /**
     * Mount the component
     */
    public function mount($reportType, $summaryData = [])
    {
        $this->reportType = $reportType;
        $this->summaryData = $summaryData;
    }

    /**
     * Update summary data when parent component changes
     */
    public function updateSummaryData($summaryData)
    {
        $this->summaryData = $summaryData;
    }

    /**
     * Toggle summary visibility
     */
    public function toggleSummary()
    {
        $this->showSummary = !$this->showSummary;
    }

    /**
     * Get summary card configurations based on report type
     */
    protected function getSummaryCardConfigurations()
    {
        return match ($this->reportType) {
            'distributions' => [
                [
                    'title' => 'Total Distributions',
                    'key' => 'total_distributions',
                    'icon' => 'o-arrow-right-circle',
                    'color' => 'primary',
                    'formatter' => 'number'
                ],
                [
                    'title' => 'Total Amount',
                    'key' => 'total_amount',
                    'icon' => 'o-banknotes',
                    'color' => 'success',
                    'formatter' => 'currency'
                ],
                [
                    'title' => 'Unique Beneficiaries',
                    'key' => 'unique_beneficiaries',
                    'icon' => 'o-user',
                    'color' => 'info',
                    'formatter' => 'number'
                ],
                [
                    'title' => 'Households Reached',
                    'key' => 'unique_households',
                    'icon' => 'o-home',
                    'color' => 'warning',
                    'formatter' => 'number'
                ]
            ],
            'programs' => [
                [
                    'title' => 'Programs',
                    'key' => 'total_programs',
                    'icon' => 'o-clipboard-document-list',
                    'color' => 'primary',
                    'formatter' => 'number'
                ],
                [
                    'title' => 'Distributions',
                    'key' => 'total_distributions',
                    'icon' => 'o-arrow-right-circle',
                    'color' => 'success',
                    'formatter' => 'number'
                ],
                [
                    'title' => 'Total Amount',
                    'key' => 'total_amount',
                    'icon' => 'o-banknotes',
                    'color' => 'warning',
                    'formatter' => 'currency'
                ],
                [
                    'title' => 'Beneficiaries',
                    'key' => 'unique_beneficiaries',
                    'icon' => 'o-user',
                    'color' => 'info',
                    'formatter' => 'number'
                ]
            ],
            'residents' => [
                [
                    'title' => 'Total Beneficiaries',
                    'key' => 'total_beneficiaries',
                    'icon' => 'o-user',
                    'color' => 'primary',
                    'formatter' => 'number'
                ],
                [
                    'title' => 'Total Distributions',
                    'key' => 'total_distributions',
                    'icon' => 'o-arrow-right-circle',
                    'color' => 'success',
                    'formatter' => 'number'
                ],
                [
                    'title' => 'Total Amount',
                    'key' => 'total_amount',
                    'icon' => 'o-banknotes',
                    'color' => 'warning',
                    'formatter' => 'currency'
                ],
                [
                    'title' => 'Average per Beneficiary',
                    'key' => 'average_per_beneficiary',
                    'icon' => 'o-calculator',
                    'color' => 'info',
                    'formatter' => 'currency'
                ]
            ],
            'barangays' => [
                [
                    'title' => 'Barangays',
                    'key' => 'total_barangays',
                    'icon' => 'o-map-pin',
                    'color' => 'primary',
                    'formatter' => 'number'
                ],
                [
                    'title' => 'Distributions',
                    'key' => 'total_distributions',
                    'icon' => 'o-arrow-right-circle',
                    'color' => 'success',
                    'formatter' => 'number'
                ],
                [
                    'title' => 'Total Amount',
                    'key' => 'total_amount',
                    'icon' => 'o-banknotes',
                    'color' => 'warning',
                    'formatter' => 'currency'
                ],
                [
                    'title' => 'Households Reached',
                    'key' => 'total_households_reached',
                    'icon' => 'o-home',
                    'color' => 'info',
                    'formatter' => 'number'
                ]
            ],
            'residents-with-id' => [
                [
                    'title' => 'Residents With ID',
                    'key' => 'total_with_id',
                    'icon' => 'o-identification',
                    'color' => 'primary',
                    'formatter' => 'number'
                ],
                [
                    'title' => 'Total Residents',
                    'key' => 'total_residents',
                    'icon' => 'o-user',
                    'color' => 'info',
                    'formatter' => 'number'
                ],
                [
                    'title' => 'ID Coverage',
                    'key' => 'coverage',
                    'icon' => 'o-chart-bar',
                    'color' => 'success',
                    'formatter' => 'percentage'
                ]
            ],
            default => []
        };
    }

    /**
     * Format value based on formatter type
     */
    protected function formatValue($value, $formatter)
    {
        if (empty($value) && $value !== 0) {
            return 'N/A';
        }

        return match ($formatter) {
            'number' => number_format($value),
            'currency' => '₱ ' . number_format($value, 2),
            'percentage' => number_format($value, 1) . '%',
            default => $value
        };
    }

    /**
     * Get additional summary metrics based on report type
     */
    protected function getAdditionalSummaryMetrics()
    {
        return match ($this->reportType) {
            'residents' => [
                [
                    'title' => 'Demographic Breakdown',
                    'metrics' => [
                        [
                            'label' => 'Senior Citizens',
                            'key' => 'seniors_count',
                            'icon' => 'o-user',
                            'color' => 'amber'
                        ],
                        [
                            'label' => 'PWD',
                            'key' => 'pwd_count',
                            'icon' => 'o-user',
                            'color' => 'purple'
                        ],
                        [
                            'label' => 'Solo Parents',
                            'key' => 'solo_parents_count',
                            'icon' => 'o-user',
                            'color' => 'blue'
                        ]
                    ]
                ]
            ],
            'programs' => [
                [
                    'title' => 'Coverage Statistics',
                    'metrics' => [
                        [
                            'label' => 'Avg. per Household',
                            'value' => isset($this->summaryData['unique_households']) && $this->summaryData['unique_households'] > 0
                                ? $this->formatValue($this->summaryData['total_amount'] / $this->summaryData['unique_households'], 'currency')
                                : 'N/A',
                            'icon' => 'o-home',
                            'color' => 'blue'
                        ]
                    ]
                ]
            ],
            default => []
        };
    }

    /**
     * Render the component
     */
    public function render()
    {
        $summaryCards = $this->getSummaryCardConfigurations();
        $additionalMetrics = $this->getAdditionalSummaryMetrics();

        return view('livewire.reports.components.report-summary', [
            'summaryCards' => $summaryCards,
            'additionalMetrics' => $additionalMetrics
        ]);
    }
}
