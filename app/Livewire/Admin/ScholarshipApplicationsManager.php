<?php

namespace App\Livewire\Admin;

use App\Models\ScholarshipApplication;
use App\Models\ScholarshipApplicationDocument;
use App\Models\ScholarshipProgram;
use App\Services\ScholarshipApplicationService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class ScholarshipApplicationsManager extends Component
{
    use Toast, WithPagination;

    #[Url]
    public string $activeTab = 'queue';

    #[Url]
    public string $status = 'submitted';

    #[Url]
    public string $applicantType = 'all';

    #[Url]
    public string $programId = 'all';

    #[Url]
    public string $search = '';

    public ?int $selectedApplicationId = null;

    public string $actionReason = '';

    public string $staffNotes = '';

    public string $gwa = '';

    public string $awardTier = '';

    public array $rejectedDocumentIds = [];

    public bool $showActionModal = false;

    public string $pendingAction = '';

    public string $programName = '';

    public string $programAcademicYear = '';

    public string $programDescription = '';

    public ?string $programOpenAt = null;

    public ?string $programCloseAt = null;

    public string $programOfficeAddress = '';

    public string $programOfficeHours = '';

    public string $programRequiredOriginals = '';

    public bool $programIsActive = true;

    public ?int $editingProgramId = null;

    public function mount(): void
    {
        $this->authorizeManage();
    }

    public function updated($property): void
    {
        if (in_array($property, ['status', 'applicantType', 'programId', 'search', 'activeTab'], true)) {
            $this->resetPage();
            $this->selectedApplicationId = null;
        }
    }

    public function selectApplication(int $id): void
    {
        $this->authorizeManage();
        $application = ScholarshipApplication::with(['resident', 'program', 'documents.documentType', 'events'])->findOrFail($id);
        $this->selectedApplicationId = $application->id;
        $this->gwa = $application->gwa !== null ? (string) $application->gwa : '';
        $this->awardTier = $application->award_tier ?? '';
        $this->staffNotes = $application->staff_notes ?? '';
        $this->actionReason = '';
        $this->rejectedDocumentIds = [];
    }

    public function markUnderReview(): void
    {
        $application = $this->selected();
        app(ScholarshipApplicationService::class)->markUnderReview($application, auth()->user());
        $this->success('Application marked under review.');
        $this->selectApplication($application->id);
    }

    public function openActionModal(string $action): void
    {
        $this->authorizeManage();
        $this->pendingAction = $action;
        $this->actionReason = '';
        $this->showActionModal = true;
        $this->resetValidation();
    }

    public function confirmAction(): void
    {
        $this->authorizeManage();
        $application = $this->selected();
        $service = app(ScholarshipApplicationService::class);

        if (in_array($this->pendingAction, ['resubmit', 'reject'], true)) {
            $this->validate(['actionReason' => ['required', 'string', 'min:5', 'max:2000']]);
        }

        match ($this->pendingAction) {
            'resubmit' => $service->requestResubmission(
                $application,
                auth()->user(),
                $this->actionReason,
                array_map('intval', $this->rejectedDocumentIds),
            ),
            'reject' => $service->reject($application, auth()->user(), $this->actionReason),
            'conditional' => $service->conditionallyApprove(
                $application,
                auth()->user(),
                $this->gwa !== '' ? (float) $this->gwa : null,
                $this->awardTier !== '' ? $this->awardTier : null,
                $this->staffNotes !== '' ? $this->staffNotes : null,
            ),
            'award' => $service->award(
                $application,
                auth()->user(),
                $this->staffNotes !== '' ? $this->staffNotes : null,
            ),
            default => null,
        };

        $this->showActionModal = false;
        $this->pendingAction = '';
        $this->success('Application updated.');
        $this->selectApplication($application->id);
    }

    public function verifyDocument(int $documentId): void
    {
        $application = $this->selected();
        $document = $application->documents()->findOrFail($documentId);
        app(ScholarshipApplicationService::class)->markDocumentVerified($document, auth()->user());
        $this->success('Document marked verified.');
        $this->selectApplication($application->id);
    }

    public function rejectDocument(int $documentId): void
    {
        $this->validate(['actionReason' => ['required', 'string', 'min:3', 'max:2000']]);
        $application = $this->selected();
        $document = $application->documents()->findOrFail($documentId);
        app(ScholarshipApplicationService::class)->markDocumentRejected($document, auth()->user(), $this->actionReason);
        if (! in_array($documentId, $this->rejectedDocumentIds, true)) {
            $this->rejectedDocumentIds[] = $documentId;
        }
        $this->success('Document marked rejected.');
        $this->selectApplication($application->id);
    }

    public function editProgram(int $id): void
    {
        $this->authorizeManage();
        $program = ScholarshipProgram::findOrFail($id);
        $this->editingProgramId = $program->id;
        $this->programName = $program->name;
        $this->programAcademicYear = $program->academic_year ?? '';
        $this->programDescription = $program->description ?? '';
        $this->programOpenAt = optional($program->open_at)?->format('Y-m-d\TH:i');
        $this->programCloseAt = optional($program->close_at)?->format('Y-m-d\TH:i');
        $this->programOfficeAddress = $program->office_address ?? '';
        $this->programOfficeHours = $program->office_hours ?? '';
        $this->programRequiredOriginals = implode("\n", $program->required_originals ?? []);
        $this->programIsActive = $program->is_active;
        $this->activeTab = 'programs';
    }

    public function saveProgram(): void
    {
        $this->authorizeManage();
        $this->validate([
            'programName' => ['required', 'string', 'max:255'],
            'programAcademicYear' => ['nullable', 'string', 'max:50'],
            'programDescription' => ['nullable', 'string', 'max:5000'],
            'programOfficeAddress' => ['nullable', 'string', 'max:500'],
            'programOfficeHours' => ['nullable', 'string', 'max:255'],
            'programRequiredOriginals' => ['nullable', 'string', 'max:5000'],
        ]);

        $originals = collect(preg_split('/\r\n|\r|\n/', $this->programRequiredOriginals) ?: [])
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        $payload = [
            'name' => $this->programName,
            'academic_year' => $this->programAcademicYear ?: null,
            'description' => $this->programDescription ?: null,
            'open_at' => $this->programOpenAt ?: null,
            'close_at' => $this->programCloseAt ?: null,
            'office_address' => $this->programOfficeAddress ?: null,
            'office_hours' => $this->programOfficeHours ?: null,
            'required_originals' => $originals,
            'is_active' => $this->programIsActive,
        ];

        if ($this->editingProgramId) {
            $program = ScholarshipProgram::findOrFail($this->editingProgramId);
            app(ScholarshipApplicationService::class)->updateProgramSettings($program, $payload);
            $this->success('Scholarship program updated.');
        } else {
            ScholarshipProgram::create($payload);
            $this->success('Scholarship program created.');
        }

        $this->resetProgramForm();
    }

    public function resetProgramForm(): void
    {
        $this->editingProgramId = null;
        $this->programName = '';
        $this->programAcademicYear = '';
        $this->programDescription = '';
        $this->programOpenAt = null;
        $this->programCloseAt = null;
        $this->programOfficeAddress = '';
        $this->programOfficeHours = '';
        $this->programRequiredOriginals = '';
        $this->programIsActive = true;
    }

    public function render()
    {
        $applications = ScholarshipApplication::query()
            ->with(['resident', 'program', 'documents'])
            ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
            ->when($this->applicantType !== 'all', fn ($q) => $q->where('applicant_type', $this->applicantType))
            ->when($this->programId !== 'all', fn ($q) => $q->where('scholarship_program_id', (int) $this->programId))
            ->when(trim($this->search) !== '', function ($q): void {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($inner) use ($term): void {
                    $inner->where('reference_number', 'like', $term)
                        ->orWhereHas('resident', fn ($resident) => $resident
                            ->where('resident_id', 'like', $term)
                            ->orWhere('first_name', 'like', $term)
                            ->orWhere('last_name', 'like', $term));
                });
            })
            ->latest('updated_at')
            ->paginate(12);

        $selected = $this->selectedApplicationId
            ? ScholarshipApplication::with(['resident.household', 'program', 'documents.documentType', 'events', 'reviewer'])
                ->find($this->selectedApplicationId)
            : null;

        $programs = ScholarshipProgram::orderByDesc('open_at')->get();

        return view('livewire.admin.scholarship-applications-manager', [
            'applications' => $applications,
            'selected' => $selected,
            'programs' => $programs,
            'counts' => ScholarshipApplication::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'statusOptions' => [
                ['id' => 'all', 'name' => 'All statuses'],
                ['id' => 'submitted', 'name' => 'Submitted'],
                ['id' => 'under_review', 'name' => 'Under review'],
                ['id' => 'needs_resubmission', 'name' => 'Needs resubmission'],
                ['id' => 'conditionally_approved', 'name' => 'Conditionally approved'],
                ['id' => 'awarded', 'name' => 'Awarded'],
                ['id' => 'rejected', 'name' => 'Rejected'],
                ['id' => 'draft', 'name' => 'Draft'],
            ],
            'applicantTypeOptions' => [
                ['id' => 'all', 'name' => 'All types'],
                ['id' => 'new', 'name' => 'New applicants'],
                ['id' => 'ongoing', 'name' => 'On-going scholars'],
            ],
            'programOptions' => collect([['id' => 'all', 'name' => 'All programs']])
                ->merge($programs->map(fn ($p) => ['id' => (string) $p->id, 'name' => $p->name]))
                ->values()
                ->all(),
            'tierOptions' => [
                ['id' => '', 'name' => 'Auto from GWA / unset'],
                ['id' => ScholarshipApplication::TIER_EXCELLENCE, 'name' => 'Academic Excellence (≥95)'],
                ['id' => ScholarshipApplication::TIER_ACHIEVEMENT, 'name' => 'Academic Achievement (90–94.9)'],
            ],
        ])->layout('layouts.app');
    }

    private function selected(): ScholarshipApplication
    {
        $this->authorizeManage();
        abort_unless($this->selectedApplicationId, 422, 'Select an application first.');

        return ScholarshipApplication::with(['documents', 'program', 'resident'])->findOrFail($this->selectedApplicationId);
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()?->can('manage-scholarship-applications'), 403);
    }
}
