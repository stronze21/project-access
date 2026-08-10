<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipApplicationDocument;
use App\Models\ScholarshipDocumentType;
use App\Models\ScholarshipProgram;
use App\Services\ScholarshipApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScholarshipController extends Controller
{
    public function __construct(
        private ScholarshipApplicationService $scholarships,
    ) {}

    public function programs(): JsonResponse
    {
        $programs = ScholarshipProgram::open()
            ->orderByDesc('open_at')
            ->get()
            ->map(fn (ScholarshipProgram $program) => $this->serializeProgram($program));

        return response()->json(['data' => $programs]);
    }

    public function documentTypes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'applicant_type' => ['required', Rule::in([
                ScholarshipApplication::APPLICANT_NEW,
                ScholarshipApplication::APPLICANT_ONGOING,
            ])],
        ]);

        $types = ScholarshipDocumentType::forApplicantType($validated['applicant_type'])->get([
            'id', 'code', 'label', 'applicant_type', 'sort_order', 'is_required',
        ]);

        return response()->json(['data' => $types]);
    }

    public function index(Request $request): JsonResponse
    {
        $applications = ScholarshipApplication::query()
            ->with(['program', 'documents.documentType'])
            ->where('resident_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'data' => collect($applications->items())->map(fn ($app) => $this->serializeApplication($app)),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $application = $this->findOwned($request, $id);

        return response()->json([
            'data' => $this->serializeApplication($application, detailed: true),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scholarship_program_id' => ['required', 'exists:scholarship_programs,id'],
            'applicant_type' => ['required', Rule::in([
                ScholarshipApplication::APPLICANT_NEW,
                ScholarshipApplication::APPLICANT_ONGOING,
            ])],
            'gwa' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $program = ScholarshipProgram::findOrFail($validated['scholarship_program_id']);
        $application = $this->scholarships->createDraft(
            $request->user(),
            $program,
            $validated['applicant_type'],
            isset($validated['gwa']) ? (float) $validated['gwa'] : null,
        );

        return response()->json([
            'message' => 'Scholarship application draft created.',
            'data' => $this->serializeApplication($application, detailed: true),
        ], 201);
    }

    public function uploadDocument(Request $request, int $id): JsonResponse
    {
        $application = $this->findOwned($request, $id);

        $validated = $request->validate([
            'document_type_id' => ['required', 'exists:scholarship_document_types,id'],
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,webp'],
        ]);

        $documentType = ScholarshipDocumentType::findOrFail($validated['document_type_id']);
        $document = $this->scholarships->uploadDocument(
            $application,
            $documentType,
            $validated['file'],
            $request->user(),
        );

        return response()->json([
            'message' => 'Document uploaded.',
            'data' => $this->serializeDocument($document->load('documentType')),
            'application' => $this->serializeApplication($application->fresh(['program', 'documents.documentType', 'events']), detailed: true),
        ], 201);
    }

    public function deleteDocument(Request $request, int $id, int $documentId): JsonResponse
    {
        $application = $this->findOwned($request, $id);
        $document = $application->documents()->findOrFail($documentId);
        $this->scholarships->deleteDocument($application, $document, $request->user());

        return response()->json([
            'message' => 'Document removed.',
            'application' => $this->serializeApplication($application->fresh(['program', 'documents.documentType', 'events']), detailed: true),
        ]);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $application = $this->findOwned($request, $id);
        $application = $this->scholarships->submit($application, $request->user());

        return response()->json([
            'message' => 'Application submitted for review.',
            'data' => $this->serializeApplication($application, detailed: true),
        ]);
    }

    public function downloadDocument(Request $request, int $id, int $documentId): StreamedResponse
    {
        $application = $this->findOwned($request, $id);
        $document = $application->documents()->findOrFail($documentId);

        abort_unless(Storage::disk($document->storage_disk)->exists($document->storage_path), 404);

        return Storage::disk($document->storage_disk)->download($document->storage_path, $document->original_name);
    }

    private function findOwned(Request $request, int $id): ScholarshipApplication
    {
        return ScholarshipApplication::with(['program', 'documents.documentType', 'events'])
            ->where('resident_id', $request->user()->id)
            ->findOrFail($id);
    }

    private function serializeProgram(ScholarshipProgram $program): array
    {
        return [
            'id' => $program->id,
            'name' => $program->name,
            'academic_year' => $program->academic_year,
            'description' => $program->description,
            'open_at' => $program->open_at?->toIso8601String(),
            'close_at' => $program->close_at?->toIso8601String(),
            'office_address' => $program->office_address,
            'office_hours' => $program->office_hours,
            'required_originals' => $program->required_originals ?? [],
            'is_open' => $program->isOpen(),
        ];
    }

    private function serializeApplication(ScholarshipApplication $application, bool $detailed = false): array
    {
        $data = [
            'id' => $application->id,
            'reference_number' => $application->reference_number,
            'applicant_type' => $application->applicant_type,
            'applicant_type_label' => $application->applicant_type === ScholarshipApplication::APPLICANT_NEW
                ? 'New Applicant'
                : 'On-going Scholar',
            'status' => $application->status,
            'status_label' => $application->statusLabel(),
            'gwa' => $application->gwa,
            'award_tier' => $application->award_tier,
            'award_tier_label' => $application->awardTierLabel(),
            'award_tier_benefits' => $application->awardTierBenefits(),
            'rejection_reason' => $application->rejection_reason,
            'submitted_at' => $application->submitted_at?->toIso8601String(),
            'conditionally_approved_at' => $application->conditionally_approved_at?->toIso8601String(),
            'awarded_at' => $application->awarded_at?->toIso8601String(),
            'updated_at' => $application->updated_at?->toIso8601String(),
            'program' => $application->program ? $this->serializeProgram($application->program) : null,
            'can_edit_documents' => $application->canResidentEditDocuments(),
            'can_submit' => $application->canResidentSubmit(),
        ];

        if ($detailed) {
            $checklist = $this->scholarships->checklistFor($application);
            $data['checklist'] = collect($checklist)->map(fn (array $item) => [
                'document_type_id' => $item['document_type_id'],
                'code' => $item['code'],
                'label' => $item['label'],
                'is_required' => $item['is_required'],
                'uploaded' => $item['uploaded'],
                'document' => $item['document']
                    ? $this->serializeDocument($item['document'])
                    : null,
            ])->values();
            $data['documents'] = $application->documents->map(fn ($doc) => $this->serializeDocument($doc))->values();
            $data['events'] = $application->events->map(fn ($event) => [
                'from_status' => $event->from_status,
                'to_status' => $event->to_status,
                'note' => $event->note,
                'created_at' => $event->created_at?->toIso8601String(),
            ])->values();
            $data['staff_notes'] = $application->staff_notes;
        }

        return $data;
    }

    private function serializeDocument(ScholarshipApplicationDocument $document): array
    {
        return [
            'id' => $document->id,
            'document_type_id' => $document->document_type_id,
            'document_type_label' => $document->documentType?->label,
            'original_name' => $document->original_name,
            'mime_type' => $document->mime_type,
            'size_bytes' => $document->size_bytes,
            'status' => $document->status,
            'virus_scan_status' => $document->virus_scan_status,
            'rejection_reason' => $document->rejection_reason,
            'created_at' => $document->created_at?->toIso8601String(),
        ];
    }
}
