<?php

namespace Tests\Feature;

use App\Models\Household;
use App\Models\Resident;
use App\Models\ResidentNotification;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipApplicationDocument;
use App\Models\ScholarshipDocumentType;
use App\Models\ScholarshipProgram;
use App\Models\User;
use App\Services\ScholarshipApplicationService;
use App\Services\UploadedDocumentOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ScholarshipApplicationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seedScholarshipCatalog();
    }

    public function test_resident_can_create_upload_and_submit_application(): void
    {
        $resident = $this->resident();
        Sanctum::actingAs($resident);
        $program = ScholarshipProgram::firstOrFail();

        $create = $this->postJson('/api/resident-portal/scholarships/applications', [
            'scholarship_program_id' => $program->id,
            'applicant_type' => 'new',
            'gwa' => 96.5,
        ])->assertCreated();

        $applicationId = $create->json('data.id');
        $this->assertNotNull($applicationId);

        foreach (ScholarshipDocumentType::forApplicantType('new')->get() as $type) {
            $this->postJson("/api/resident-portal/scholarships/applications/{$applicationId}/documents", [
                'document_type_id' => $type->id,
                'file' => UploadedFile::fake()->create($type->code.'.pdf', 100, 'application/pdf'),
            ])->assertCreated();
        }

        $this->postJson("/api/resident-portal/scholarships/applications/{$applicationId}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', ScholarshipApplication::STATUS_SUBMITTED);

        $this->assertDatabaseHas('scholarship_applications', [
            'id' => $applicationId,
            'status' => ScholarshipApplication::STATUS_SUBMITTED,
        ]);
    }

    public function test_uploaded_images_are_compressed_to_jpeg(): void
    {
        $resident = $this->resident();
        Sanctum::actingAs($resident);
        $program = ScholarshipProgram::firstOrFail();

        $applicationId = $this->postJson('/api/resident-portal/scholarships/applications', [
            'scholarship_program_id' => $program->id,
            'applicant_type' => 'new',
        ])->assertCreated()->json('data.id');

        $type = ScholarshipDocumentType::forApplicantType('new')->firstOrFail();
        $image = UploadedFile::fake()->image('birth-certificate.png', 3200, 2400);

        $response = $this->postJson("/api/resident-portal/scholarships/applications/{$applicationId}/documents", [
            'document_type_id' => $type->id,
            'file' => $image,
        ])->assertCreated();

        $documentId = $response->json('data.id');
        $document = ScholarshipApplicationDocument::findOrFail($documentId);

        $this->assertSame('image/jpeg', $document->mime_type);
        $this->assertStringEndsWith('.jpg', $document->storage_path);
        $this->assertTrue(Storage::disk('local')->exists($document->storage_path));
        $this->assertLessThan(3200 * 2400, (int) $document->size_bytes);

        $binary = Storage::disk('local')->get($document->storage_path);
        $size = getimagesizefromstring($binary);
        $this->assertNotFalse($size);
        $this->assertLessThanOrEqual(UploadedDocumentOptimizer::MAX_DIMENSION, max($size[0], $size[1]));
    }

    public function test_submit_requires_all_required_documents(): void
    {
        $resident = $this->resident();
        Sanctum::actingAs($resident);
        $program = ScholarshipProgram::firstOrFail();

        $applicationId = $this->postJson('/api/resident-portal/scholarships/applications', [
            'scholarship_program_id' => $program->id,
            'applicant_type' => 'ongoing',
        ])->assertCreated()->json('data.id');

        $this->postJson("/api/resident-portal/scholarships/applications/{$applicationId}/submit")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['documents']);
    }

    public function test_duplicate_active_application_is_blocked(): void
    {
        $resident = $this->resident();
        Sanctum::actingAs($resident);
        $program = ScholarshipProgram::firstOrFail();

        $this->postJson('/api/resident-portal/scholarships/applications', [
            'scholarship_program_id' => $program->id,
            'applicant_type' => 'new',
        ])->assertCreated();

        $this->postJson('/api/resident-portal/scholarships/applications', [
            'scholarship_program_id' => $program->id,
            'applicant_type' => 'ongoing',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['scholarship_program_id']);
    }

    public function test_staff_conditional_approve_and_award_sets_scholar_flag_and_notifies(): void
    {
        $resident = $this->resident();
        $staff = $this->staff(['manage-scholarship-applications']);
        $service = app(ScholarshipApplicationService::class);
        $program = ScholarshipProgram::firstOrFail();

        $application = $service->createDraft($resident, $program, 'new', 95);
        $this->uploadAllRequired($service, $application, $resident);
        $application = $service->submit($application->fresh(), $resident);

        $application = $service->conditionallyApprove(
            $application,
            $staff,
            95,
            ScholarshipApplication::TIER_EXCELLENCE,
            'Digital docs look good.'
        );

        $this->assertSame(ScholarshipApplication::STATUS_CONDITIONALLY_APPROVED, $application->status);
        $this->assertSame(ScholarshipApplication::TIER_EXCELLENCE, $application->award_tier);
        $this->assertDatabaseHas('resident_notifications', [
            'resident_id' => $resident->id,
            'type' => 'scholarship-application',
            'title' => 'Scholarship conditionally approved',
        ]);

        $application = $service->award($application, $staff, 'Hard copies verified.');
        $this->assertSame(ScholarshipApplication::STATUS_AWARDED, $application->status);
        $this->assertTrue($resident->fresh()->is_scholar);
        $this->assertNotNull($application->awarded_at);
        $this->assertTrue(
            ResidentNotification::where('resident_id', $resident->id)
                ->where('title', 'Scholarship awarded')
                ->exists()
        );
    }

    public function test_staff_can_request_resubmission_with_reason(): void
    {
        $resident = $this->resident();
        $staff = $this->staff(['manage-scholarship-applications']);
        $service = app(ScholarshipApplicationService::class);
        $program = ScholarshipProgram::firstOrFail();

        $application = $service->createDraft($resident, $program, 'new');
        $this->uploadAllRequired($service, $application, $resident);
        $application = $service->submit($application->fresh(), $resident);

        $docId = $application->documents()->firstOrFail()->id;
        $application = $service->requestResubmission(
            $application,
            $staff,
            'Birth certificate is blurry.',
            [$docId]
        );

        $this->assertSame(ScholarshipApplication::STATUS_NEEDS_RESUBMISSION, $application->status);
        $this->assertSame(
            ScholarshipApplicationDocument::STATUS_REJECTED,
            $application->documents()->find($docId)?->status
        );
        $this->assertDatabaseHas('resident_notifications', [
            'resident_id' => $resident->id,
            'title' => 'Scholarship documents need correction',
        ]);
    }

    public function test_resident_portal_scholarship_screens_render(): void
    {
        $resident = $this->resident();
        $headers = [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_5 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1',
        ];

        $this->withHeaders($headers)
            ->actingAs($resident, 'resident')
            ->withSession(['resident_portal_expires_at' => now()->addDays(60)])
            ->get('/resident-portal/scholarships')
            ->assertOk()
            ->assertSee('Apply for Scholarships');

        $this->withHeaders($headers)
            ->actingAs($resident, 'resident')
            ->withSession(['resident_portal_expires_at' => now()->addDays(60)])
            ->get('/resident-portal/scholarships/apply')
            ->assertOk()
            ->assertSee('New Applicant');
    }

    public function test_application_cannot_be_submitted_when_document_checklist_is_not_configured(): void
    {
        ScholarshipDocumentType::query()->delete();
        $resident = $this->resident();
        $program = ScholarshipProgram::create([
            'name' => 'Unconfigured scholarship',
            'open_at' => now()->subDay(),
            'close_at' => now()->addDay(),
            'is_active' => true,
        ]);
        $application = app(ScholarshipApplicationService::class)
            ->createDraft($resident, $program, ScholarshipApplication::APPLICANT_NEW);

        $this->actingAs($resident)
            ->postJson("/api/resident-portal/scholarships/applications/{$application->id}/submit")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['documents']);
    }

    private function uploadAllRequired(
        ScholarshipApplicationService $service,
        ScholarshipApplication $application,
        Resident $resident,
    ): void {
        foreach (ScholarshipDocumentType::forApplicantType($application->applicant_type)->get() as $type) {
            $service->uploadDocument(
                $application->fresh(),
                $type,
                UploadedFile::fake()->create($type->code.'.pdf', 80, 'application/pdf'),
                $resident,
            );
        }
    }

    private function seedScholarshipCatalog(): void
    {
        ScholarshipProgram::create([
            'name' => 'Alaminos City Scholarship Program (ACSP)',
            'academic_year' => '2026-2027',
            'description' => 'Test program',
            'open_at' => now()->subDay(),
            'close_at' => now()->addMonths(3),
            'office_address' => 'Mayor\'s Office, City Hall',
            'office_hours' => 'Mon–Fri 8AM–5PM',
            'required_originals' => ['Form 137A', 'Parent ID'],
            'is_active' => true,
        ]);

        $types = [
            ['code' => 'accomplished_form', 'label' => 'Accomplished form', 'applicant_type' => 'both', 'sort_order' => 10],
            ['code' => 'photo_2x2', 'label' => '2x2 photo', 'applicant_type' => 'both', 'sort_order' => 20],
            ['code' => 'parent_id', 'label' => 'Parent ID', 'applicant_type' => 'both', 'sort_order' => 30],
            ['code' => 'form_137a', 'label' => 'Form 137A', 'applicant_type' => 'new', 'sort_order' => 40],
            ['code' => 'birth_certificate', 'label' => 'Birth certificate', 'applicant_type' => 'new', 'sort_order' => 50],
            ['code' => 'registration_certified_x2', 'label' => 'Registration form', 'applicant_type' => 'ongoing', 'sort_order' => 40],
            ['code' => 'true_copy_grades', 'label' => 'True copy of grades', 'applicant_type' => 'ongoing', 'sort_order' => 50],
        ];

        foreach ($types as $type) {
            ScholarshipDocumentType::query()->updateOrCreate(
                ['code' => $type['code'], 'applicant_type' => $type['applicant_type']],
                $type + ['is_required' => true],
            );
        }
    }

    private function resident(): Resident
    {
        $household = Household::create([
            'household_id' => 'HH-SCHOLAR-0001',
            'address' => '123 Mabini St',
            'barangay' => 'Poblacion',
            'city_municipality' => 'Alaminos',
            'province' => 'Pangasinan',
        ]);

        return Resident::create([
            'household_id' => $household->id,
            'resident_id' => 'R-SCHOLAR-0001',
            'first_name' => 'Ana',
            'last_name' => 'Santos',
            'birth_date' => '2005-03-12',
            'gender' => 'female',
            'civil_status' => 'single',
            'password' => 'secret123',
            'is_active' => true,
            'is_scholar' => false,
        ]);
    }

    private function staff(array $permissions): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }
}
