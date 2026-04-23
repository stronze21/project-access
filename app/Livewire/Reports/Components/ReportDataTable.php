<?php

namespace App\Livewire\Reports\Components;

use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ReportDataTable extends Component
{
    use WithPagination;

    // Report type and data
    public $reportType = 'distributions';
    public $reportData = [];
    public $summaryData = [];

    // Pagination
    public $perPage = 15;
    public $totalItems = 0;
    public $currentPage = 1;
    public $totalPages = 1;

    // Table state
    public $sortField = null;
    public $sortDirection = 'asc';
    public $searchTerm = '';

    /**
     * Mount the component
     */
    // In ReportDataTable component
    public function mount($reportType, $reportData = [], $summaryData = [], $currentPage = 1, $totalItems = 0, $totalPages = 1, $perPage = 15)
    {
        $this->reportType = $reportType;
        $this->reportData = $reportData;
        $this->summaryData = $summaryData;
        $this->currentPage = $currentPage;
        $this->totalItems = $totalItems;
        $this->totalPages = $totalPages;
        $this->perPage = $perPage;
    }

    /**
     * Update data when parent component changes
     */
    #[On('updateData')]
    public function updateData($reportData = [], $summaryData = [], $currentPage = 1, $totalItems = 0, $totalPages = 1, $perPage = 15)
    {
        $this->reportData = $reportData;
        $this->summaryData = $summaryData;
        $this->currentPage = $currentPage;
        $this->totalItems = $totalItems;
        $this->totalPages = $totalPages;
        $this->perPage = $perPage;
    }

    /**
     * Sort table by field
     */
    public function sortBy($field)
    {
        // If we're already sorting by this field, toggle direction
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        // Emit sort change event
        $this->dispatch('sortChanged', [
            'field' => $this->sortField,
            'direction' => $this->sortDirection
        ]);
    }

    public function setPage($page)
    {
        $this->currentPage = $page;
        $this->dispatch('pageChanged', ['page' => $page]);
    }

    public function updatedPerPage($perPage)
    {
        $this->perPage = $perPage;
        $this->dispatch('perPageChanged', ['perPage' => $perPage]);
    }


    /**
     * Perform search
     */
    public function updatedSearchTerm()
    {
        $this->dispatch('searchChanged', ['term' => $this->searchTerm]);
    }

    /**
     * Get header columns based on report type
     */
    protected function getHeaderColumns()
    {
        return match ($this->reportType) {
            'distributions' => [
                ['key' => 'reference_number', 'label' => 'Reference #', 'sortable' => true],
                ['key' => 'resident.full_name', 'label' => 'Beneficiary', 'sortable' => true],
                ['key' => 'household.household_id', 'label' => 'Household', 'sortable' => true],
                ['key' => 'household.barangay', 'label' => 'Barangay', 'sortable' => true],
                ['key' => 'ayudaProgram.name', 'label' => 'Program', 'sortable' => true],
                ['key' => 'created_at', 'label' => 'Date', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Amount', 'sortable' => true],
                ['key' => 'goods_description', 'label' => 'Goods', 'sortable' => true],
                ['key' => 'services_description', 'label' => 'Services', 'sortable' => true],
                ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ],
            'programs' => [
                ['key' => 'name', 'label' => 'Program', 'sortable' => true],
                ['key' => 'distributions_count', 'label' => 'Distributions', 'sortable' => true],
                ['key' => 'total_distributed', 'label' => 'Amount', 'sortable' => true],
                ['key' => 'unique_beneficiaries', 'label' => 'Beneficiaries', 'sortable' => true],
                ['key' => 'unique_households', 'label' => 'Households', 'sortable' => true],
                ['key' => 'utilization', 'label' => 'Budget Utilization', 'sortable' => true],
            ],
            'residents' => [
                ['key' => 'full_name', 'label' => 'Name', 'sortable' => true],
                ['key' => 'household.household_id', 'label' => 'Household', 'sortable' => true],
                ['key' => 'distributions_count', 'label' => 'Distributions', 'sortable' => true],
                ['key' => 'total_received', 'label' => 'Total Received', 'sortable' => true],
                ['key' => 'programs_list', 'label' => 'Programs', 'sortable' => false],
                ['key' => 'demographics', 'label' => 'Demographics', 'sortable' => false],
            ],
            'barangays' => [
                ['key' => 'barangay', 'label' => 'Barangay', 'sortable' => true],
                ['key' => 'distributions_count', 'label' => 'Distributions', 'sortable' => true],
                ['key' => 'total_amount', 'label' => 'Total Amount', 'sortable' => true],
                ['key' => 'unique_beneficiaries', 'label' => 'Beneficiaries', 'sortable' => true],
                ['key' => 'unique_households', 'label' => 'Households Reached', 'sortable' => true],
                ['key' => 'total_households', 'label' => 'Total Households', 'sortable' => true],
                ['key' => 'coverage_percentage', 'label' => 'Coverage', 'sortable' => true],
            ],
            'residents-with-id' => [
                ['key' => 'full_name', 'label' => 'Resident', 'sortable' => true],
                ['key' => 'household.address', 'label' => 'Address', 'sortable' => false],
                ['key' => 'household.city_municipality', 'label' => 'Municipality', 'sortable' => false],
                ['key' => 'household.province', 'label' => 'Province', 'sortable' => false],
                ['key' => 'updated_at', 'label' => 'Date Updated', 'sortable' => true],
            ],
            default => []
        };
    }

    /**
     * Render the component
     */
    public function render()
    {
        $columns = $this->getHeaderColumns();

        return view('livewire.reports.components.report-data-table', [
            'columns' => $columns
        ]);
    }
}
