<?php

namespace Tests\Feature;

use App\Livewire\Reports\Components\ReportDataTable;
use App\Livewire\Reports\Components\ReportTypeSelector;
use App\Models\CitizenServiceRequest;
use App\Models\Household;
use App\Models\Resident;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipProgram;
use App\Services\Reports\CitizenServiceRequestReportService;
use App\Services\Reports\ScholarshipApplicationReportService;
use App\Services\Reports\SpecialSectorReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SectorAndScholarshipReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_scholarship_report_summarizes_requests_by_status(): void
    {
        $program = $this->program();
        $submitted = $this->application($program, ScholarshipApplication::STATUS_SUBMITTED);
        $this->application($program, ScholarshipApplication::STATUS_AWARDED, [
            'gwa' => 96,
            'award_tier' => ScholarshipApplication::TIER_EXCELLENCE,
            'awarded_at' => now(),
        ]);
        $this->application($program, ScholarshipApplication::STATUS_REJECTED, [
            'rejection_reason' => 'Incomplete documents',
        ]);

        $report = app(ScholarshipApplicationReportService::class)->generatePaginatedReport([
            'status' => 'all',
            'page' => 1,
        ], 15);

        $this->assertSame(3, $report['summaryData']['total_applications']);
        $this->assertSame(1, $report['summaryData']['submitted_count']);
        $this->assertSame(1, $report['summaryData']['awarded_count']);
        $this->assertSame(1, $report['summaryData']['rejected_count']);
        $this->assertTrue(
            collect($report['reportData']->items())->contains(
                fn ($application) => $application->reference_number === $submitted->reference_number
            )
        );

        $export = app(ScholarshipApplicationReportService::class)->formatForExport($report['reportData']->items());
        $this->assertContains('Status', $export['headers']);
        $this->assertNotEmpty($export['data']);
    }

    public function test_scholarship_report_can_filter_by_status(): void
    {
        $program = $this->program();
        $awarded = $this->application($program, ScholarshipApplication::STATUS_AWARDED, ['awarded_at' => now()]);
        $this->application($program, ScholarshipApplication::STATUS_SUBMITTED);

        $report = app(ScholarshipApplicationReportService::class)->generatePaginatedReport([
            'status' => ScholarshipApplication::STATUS_AWARDED,
            'page' => 1,
        ], 15);

        $this->assertSame(1, $report['summaryData']['total_applications']);
        $this->assertSame($awarded->id, $report['reportData']->items()[0]->id);
    }

    public function test_special_sector_report_includes_scholars_and_other_sectors(): void
    {
        $scholar = $this->resident('R-SCHOLAR-1', ['is_scholar' => true]);
        $pwd = $this->resident('R-PWD-1', ['is_pwd' => true, 'special_sector' => 'PWD']);
        $this->resident('R-NONE-1');

        $allSectors = app(SpecialSectorReportService::class)->generatePaginatedReport([
            'sector' => '',
            'page' => 1,
        ], 15);

        $this->assertSame(2, $allSectors['summaryData']['total_in_sectors']);
        $this->assertSame(1, $allSectors['summaryData']['scholar_count']);
        $this->assertSame(1, $allSectors['summaryData']['pwd_count']);

        $ids = collect($allSectors['reportData']->items())->pluck('resident_id')->all();
        $this->assertContains($scholar->resident_id, $ids);
        $this->assertContains($pwd->resident_id, $ids);
        $this->assertNotContains('R-NONE-1', $ids);

        $scholarsOnly = app(SpecialSectorReportService::class)->generatePaginatedReport([
            'sector' => 'scholar',
            'page' => 1,
        ], 15);

        $this->assertSame(1, $scholarsOnly['summaryData']['total_in_sectors']);
        $this->assertSame($scholar->id, $scholarsOnly['reportData']->items()[0]->id);
        $this->assertContains('Scholars', $scholarsOnly['reportData']->items()[0]->sectors_list);
    }

    public function test_citizen_service_report_groups_other_request_sectors_by_status(): void
    {
        $resident = $this->resident('R-CSR-1');
        CitizenServiceRequest::create([
            'resident_id' => $resident->id,
            'service_type' => 'business-permit',
            'service_name' => 'Business Permit',
            'status' => 'processing',
            'current_step' => 'Assessment',
            'submitted_at' => now(),
        ]);
        CitizenServiceRequest::create([
            'resident_id' => $resident->id,
            'service_type' => 'civil-registry',
            'service_name' => 'Civil Registry',
            'status' => 'completed',
            'current_step' => 'Released',
            'submitted_at' => now(),
            'completed_at' => now(),
        ]);

        $report = app(CitizenServiceRequestReportService::class)->generatePaginatedReport([
            'status' => 'all',
            'page' => 1,
        ], 15);

        $this->assertSame(2, $report['summaryData']['total_requests']);
        $this->assertSame(1, $report['summaryData']['open_count']);
        $this->assertSame(1, $report['summaryData']['completed_count']);

        $byType = app(CitizenServiceRequestReportService::class)->generatePaginatedReport([
            'status' => 'all',
            'program' => 'business-permit',
            'page' => 1,
        ], 15);

        $this->assertSame(1, $byType['summaryData']['total_requests']);
        $this->assertSame('business-permit', $byType['reportData']->items()[0]->service_type);
    }

    public function test_report_type_selector_includes_scholarship_and_sector_reports(): void
    {
        Livewire::test(ReportTypeSelector::class)
            ->assertSee('Scholarship Requests')
            ->assertSee('Special Sectors')
            ->assertSee('Citizen Service Requests')
            ->call('selectReportType', 'scholarships')
            ->assertSet('reportType', 'scholarships')
            ->assertDispatched('reportTypeChanged');
    }

    public function test_scholarship_and_sector_tables_render_generated_rows(): void
    {
        $program = $this->program();
        $application = $this->application($program, ScholarshipApplication::STATUS_SUBMITTED);
        $scholar = $this->resident('R-SCHOLAR-UI', ['is_scholar' => true]);
        $scholar->setAttribute('sectors_list', ['Scholars']);
        $scholar->setAttribute('scholar_status', 'Scholar');

        Livewire::test(ReportDataTable::class, [
            'reportType' => 'scholarships',
            'reportData' => [$application->load(['resident.household', 'program'])->toArray()],
            'summaryData' => ['total_applications' => 1],
            'totalItems' => 1,
        ])
            ->assertSee($application->reference_number)
            ->assertSee('Submitted');

        Livewire::test(ReportDataTable::class, [
            'reportType' => 'sectors',
            'reportData' => [$scholar->load('household')->toArray() + [
                'full_name' => $scholar->full_name,
                'sectors_list' => ['Scholars'],
                'scholar_status' => 'Scholar',
            ]],
            'summaryData' => ['scholar_count' => 1],
            'totalItems' => 1,
        ])
            ->assertSee('R-SCHOLAR-UI')
            ->assertSee('Scholar');
    }

    private function program(): ScholarshipProgram
    {
        return ScholarshipProgram::create([
            'name' => 'Alaminos City Scholarship Program (ACSP)',
            'academic_year' => '2026-2027',
            'is_active' => true,
        ]);
    }

    private function application(ScholarshipProgram $program, string $status, array $overrides = []): ScholarshipApplication
    {
        $resident = $this->resident('R-APP-'.strtoupper(substr(md5($status.microtime()), 0, 6)));

        return ScholarshipApplication::create(array_merge([
            'resident_id' => $resident->id,
            'scholarship_program_id' => $program->id,
            'applicant_type' => ScholarshipApplication::APPLICANT_NEW,
            'status' => $status,
            'gwa' => 91.5,
            'submitted_at' => $status === ScholarshipApplication::STATUS_DRAFT ? null : now(),
        ], $overrides));
    }

    private function resident(string $residentId, array $overrides = []): Resident
    {
        $household = Household::create([
            'household_id' => 'HH-'.$residentId,
            'address' => '123 Mabini St',
            'barangay' => 'Poblacion',
            'city_municipality' => 'Alaminos',
            'province' => 'Pangasinan',
        ]);

        return Resident::create(array_merge([
            'household_id' => $household->id,
            'resident_id' => $residentId,
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'birth_date' => '2005-03-12',
            'gender' => 'female',
            'civil_status' => 'single',
            'password' => 'secret123',
            'is_active' => true,
        ], $overrides));
    }
}
