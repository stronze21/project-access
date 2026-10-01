<?php

namespace Tests\Feature;

use App\Livewire\Reports\Components\ReportDataTable;
use Livewire\Livewire;
use Tests\TestCase;

class ReportDataTableRenderingTest extends TestCase
{
    public function test_resident_and_scholarship_reports_render_independently(): void
    {
        foreach (['residents', 'residents-with-id', 'scholarships'] as $type) {
            $row = $type === 'scholarships'
                ? ['reference_number' => 'SCH-TEST', 'resident' => ['full_name' => 'Test Resident'], 'status' => 'submitted']
                : ['full_name' => 'Test Resident', 'signature' => null];

            Livewire::test(ReportDataTable::class, [
                'reportType' => $type,
                'reportData' => [$row],
                'totalItems' => 1,
            ])->assertSee('Test Resident')
                ->assertDontSee('@endphp')
                ->assertDontSee('$isObject');
        }
    }
}
