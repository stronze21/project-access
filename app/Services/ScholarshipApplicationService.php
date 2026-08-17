<?php

namespace App\Services;

use App\Models\Resident;
use App\Models\ScholarshipApplication;
use App\Models\ScholarshipApplicationDocument;
use App\Models\ScholarshipApplicationEvent;
use App\Models\ScholarshipDocumentType;
use App\Models\ScholarshipProgram;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ScholarshipApplicationService
{
    public function __construct(
        private AttachmentVirusScanner $virusScanner,
        private PushNotificationService $pushNotifications,
        private UploadedDocumentOptimizer $documentOptimizer,
    ) {}

    public function createDraft(Resident $resident, ScholarshipProgram $program, string $applicantType, ?float $gwa = null): ScholarshipApplication
    {
        if (! $program->isOpen()) {
            throw ValidationException::withMessages([
                'scholarship_program_id' => 'This scholarship program is not open for applications.',
            ]);
        }

        if (! in_array($applicantType, [ScholarshipApplication::APPLICANT_NEW, ScholarshipApplication::APPLICANT_ONGOING], true)) {
            throw ValidationException::withMessages([
                'applicant_type' => 'Select New Applicant or On-going Scholar.',
            ]);
        }

        $exists = ScholarshipApplication::query()
            ->where('resident_id', $resident->id)
            ->where('scholarship_program_id', $program->id)
            ->whereIn('status', ScholarshipApplication::ACTIVE_STATUSES)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'scholarship_program_id' => 'You already have an active application for this program.',
            ]);
        }

        return DB::transaction(function () use ($resident, $program, $applicantType, $gwa) {
            $application = ScholarshipApplication::create([
                'resident_id' => $resident->id,
                'scholarship_program_id' => $program->id,
                'applicant_type' => $applicantType,
                'status' => ScholarshipApplication::STATUS_DRAFT,
                'gwa' => $gwa,
            ]);

            $this->recordEvent(
                $application,
                null,
                ScholarshipApplication::STATUS_DRAFT,
                ScholarshipApplicationEvent::ACTOR_RESIDENT,
                $resident->id,
                'Application draft created.'
            );

            return $application->fresh(['program', 'documents.documentType', 'events']);
        });
    }

    public function uploadDocument(
        ScholarshipApplication $application,
        ScholarshipDocumentType $documentType,
        UploadedFile $file,
        Resident $resident,
    ): ScholarshipApplicationDocument {
        abort_unless($application->resident_id === $resident->id, 403);
        abort_unless($application->canResidentEditDocuments(), 422, 'Documents cannot be changed in the current status.');

        $allowed = ScholarshipDocumentType::forApplicantType($application->applicant_type)
            ->where('id', $documentType->id)
            ->exists();

        if (! $allowed) {
            throw ValidationException::withMessages([
                'document_type_id' => 'This document type is not required for your applicant type.',
            ]);
        }

        $scan = $this->virusScanner->scan($file);
        if ($scan['status'] === ScholarshipApplicationDocument::SCAN_INFECTED) {
            throw ValidationException::withMessages([
                'file' => 'The uploaded file failed the security scan.',
            ]);
        }

        return DB::transaction(function () use ($application, $documentType, $file, $scan) {
            $existing = $application->documents()
                ->where('document_type_id', $documentType->id)
                ->get();

            foreach ($existing as $old) {
                Storage::disk($old->storage_disk)->delete($old->storage_path);
                $old->delete();
            }

            $directory = 'scholarship-applications/'.$application->id;
            $stored = $this->documentOptimizer->store($file, $directory, 'local');

            return ScholarshipApplicationDocument::create([
                'scholarship_application_id' => $application->id,
                'document_type_id' => $documentType->id,
                'storage_disk' => 'local',
                'storage_path' => $stored['path'],
                'original_name' => $stored['original_name'],
                'mime_type' => $stored['mime_type'],
                'size_bytes' => $stored['size_bytes'],
                'virus_scan_status' => $scan['status'],
                'virus_scan_message' => $scan['message'],
                'scanned_at' => now(),
                'status' => ScholarshipApplicationDocument::STATUS_UPLOADED,
                'rejection_reason' => null,
            ]);
        });
    }

    public function deleteDocument(
        ScholarshipApplication $application,
        ScholarshipApplicationDocument $document,
        Resident $resident,
    ): void {
        abort_unless($application->resident_id === $resident->id, 403);
        abort_unless($document->scholarship_application_id === $application->id, 404);
        abort_unless($application->canResidentEditDocuments(), 422, 'Documents cannot be changed in the current status.');

        Storage::disk($document->storage_disk)->delete($document->storage_path);
        $document->delete();
    }

    public function submit(ScholarshipApplication $application, Resident $resident): ScholarshipApplication
    {
        abort_unless($application->resident_id === $resident->id, 403);
        abort_unless($application->canResidentSubmit(), 422, 'This application cannot be submitted in its current status.');

        $this->assertRequiredDocumentsReady($application);

        return DB::transaction(function () use ($application, $resident) {
            $from = $application->status;
            $application->update([
                'status' => ScholarshipApplication::STATUS_SUBMITTED,
                'submitted_at' => now(),
                'rejection_reason' => null,
            ]);

            $this->recordEvent(
                $application,
                $from,
                ScholarshipApplication::STATUS_SUBMITTED,
                ScholarshipApplicationEvent::ACTOR_RESIDENT,
                $resident->id,
                'Application submitted for review.'
            );

            $this->notifyResident(
                $application,
                'Scholarship application submitted',
                "Your application {$application->reference_number} was submitted and is awaiting staff review."
            );

            return $application->fresh(['program', 'documents.documentType', 'events']);
        });
    }

    public function markUnderReview(ScholarshipApplication $application, User $staff): ScholarshipApplication
    {
        $this->assertTransition($application, [
            ScholarshipApplication::STATUS_SUBMITTED,
            ScholarshipApplication::STATUS_UNDER_REVIEW,
        ]);

        if ($application->status === ScholarshipApplication::STATUS_UNDER_REVIEW) {
            return $application;
        }

        return $this->transition(
            $application,
            ScholarshipApplication::STATUS_UNDER_REVIEW,
            $staff,
            'Staff started reviewing the application.',
            ['reviewed_by' => $staff->id, 'reviewed_at' => now()],
            notify: false,
        );
    }

    public function requestResubmission(
        ScholarshipApplication $application,
        User $staff,
        string $reason,
        array $rejectedDocumentIds = [],
    ): ScholarshipApplication {
        $this->assertTransition($application, [
            ScholarshipApplication::STATUS_SUBMITTED,
            ScholarshipApplication::STATUS_UNDER_REVIEW,
            ScholarshipApplication::STATUS_CONDITIONALLY_APPROVED,
        ]);

        return DB::transaction(function () use ($application, $staff, $reason, $rejectedDocumentIds) {
            if ($rejectedDocumentIds !== []) {
                $application->documents()
                    ->whereIn('id', $rejectedDocumentIds)
                    ->update([
                        'status' => ScholarshipApplicationDocument::STATUS_REJECTED,
                        'rejection_reason' => $reason,
                    ]);
            }

            return $this->transition(
                $application->fresh(),
                ScholarshipApplication::STATUS_NEEDS_RESUBMISSION,
                $staff,
                $reason,
                [
                    'rejection_reason' => $reason,
                    'reviewed_by' => $staff->id,
                    'reviewed_at' => now(),
                ],
                notifyTitle: 'Scholarship documents need correction',
                notifyBody: "Your application {$application->reference_number} needs corrections: {$reason}",
            );
        });
    }

    public function reject(ScholarshipApplication $application, User $staff, string $reason): ScholarshipApplication
    {
        $this->assertTransition($application, [
            ScholarshipApplication::STATUS_SUBMITTED,
            ScholarshipApplication::STATUS_UNDER_REVIEW,
            ScholarshipApplication::STATUS_CONDITIONALLY_APPROVED,
        ]);

        return $this->transition(
            $application,
            ScholarshipApplication::STATUS_REJECTED,
            $staff,
            $reason,
            [
                'rejection_reason' => $reason,
                'reviewed_by' => $staff->id,
                'reviewed_at' => now(),
            ],
            notifyTitle: 'Scholarship application rejected',
            notifyBody: "Your application {$application->reference_number} was rejected: {$reason}",
        );
    }

    public function conditionallyApprove(
        ScholarshipApplication $application,
        User $staff,
        ?float $gwa = null,
        ?string $awardTier = null,
        ?string $notes = null,
    ): ScholarshipApplication {
        $this->assertTransition($application, [
            ScholarshipApplication::STATUS_SUBMITTED,
            ScholarshipApplication::STATUS_UNDER_REVIEW,
        ]);

        $gwaValue = $gwa ?? ($application->gwa !== null ? (float) $application->gwa : null);
        $tier = $awardTier;
        if ($tier === null && $gwaValue !== null) {
            $temp = clone $application;
            $temp->gwa = $gwaValue;
            $tier = $temp->awardTierSuggestedFromGwa();
        }

        if ($tier !== null && ! in_array($tier, [
            ScholarshipApplication::TIER_EXCELLENCE,
            ScholarshipApplication::TIER_ACHIEVEMENT,
        ], true)) {
            throw ValidationException::withMessages([
                'award_tier' => 'Invalid award tier.',
            ]);
        }

        $application->documents()
            ->where('status', '!=', ScholarshipApplicationDocument::STATUS_REJECTED)
            ->update(['status' => ScholarshipApplicationDocument::STATUS_VERIFIED]);

        $program = $application->program;
        $walkIn = trim(implode(' ', array_filter([
            $program?->office_address,
            $program?->office_hours ? "Hours: {$program->office_hours}" : null,
        ])));

        return $this->transition(
            $application,
            ScholarshipApplication::STATUS_CONDITIONALLY_APPROVED,
            $staff,
            $notes ?? 'Digitally verified. Visit the office with hard copies for final verification.',
            [
                'gwa' => $gwaValue,
                'award_tier' => $tier,
                'staff_notes' => $notes,
                'rejection_reason' => null,
                'reviewed_by' => $staff->id,
                'reviewed_at' => now(),
                'conditionally_approved_at' => now(),
            ],
            notifyTitle: 'Scholarship conditionally approved',
            notifyBody: "Your application {$application->reference_number} is CONDITIONALLY APPROVED. Please visit the office for final hard-copy verification".($walkIn ? ": {$walkIn}" : '.'),
        );
    }

    public function award(ScholarshipApplication $application, User $staff, ?string $notes = null): ScholarshipApplication
    {
        $this->assertTransition($application, [
            ScholarshipApplication::STATUS_CONDITIONALLY_APPROVED,
        ]);

        return DB::transaction(function () use ($application, $staff, $notes) {
            $updated = $this->transition(
                $application,
                ScholarshipApplication::STATUS_AWARDED,
                $staff,
                $notes ?? 'Physical verification completed. Scholarship awarded.',
                [
                    'staff_notes' => $notes ?? $application->staff_notes,
                    'reviewed_by' => $staff->id,
                    'reviewed_at' => now(),
                    'physically_verified_at' => now(),
                    'awarded_at' => now(),
                    'rejection_reason' => null,
                ],
                notifyTitle: 'Scholarship awarded',
                notifyBody: "Congratulations! Your application {$application->reference_number} has been fully awarded.",
            );

            $application->resident()->update(['is_scholar' => true]);

            return $updated->fresh(['program', 'documents.documentType', 'events', 'resident']);
        });
    }

    public function markDocumentVerified(ScholarshipApplicationDocument $document, User $staff): void
    {
        $document->update([
            'status' => ScholarshipApplicationDocument::STATUS_VERIFIED,
            'rejection_reason' => null,
        ]);
    }

    public function markDocumentRejected(ScholarshipApplicationDocument $document, User $staff, string $reason): void
    {
        $document->update([
            'status' => ScholarshipApplicationDocument::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);
    }

    public function updateProgramSettings(ScholarshipProgram $program, array $data): ScholarshipProgram
    {
        $program->update($data);

        return $program->fresh();
    }

    public function checklistFor(ScholarshipApplication $application): array
    {
        $types = ScholarshipDocumentType::forApplicantType($application->applicant_type)->get();
        $docsByType = $application->documents->keyBy('document_type_id');

        return $types->map(function (ScholarshipDocumentType $type) use ($docsByType) {
            $doc = $docsByType->get($type->id);

            return [
                'document_type_id' => $type->id,
                'code' => $type->code,
                'label' => $type->label,
                'is_required' => $type->is_required,
                'uploaded' => $doc !== null && $doc->isScanClean() && $doc->status !== ScholarshipApplicationDocument::STATUS_REJECTED,
                'document' => $doc,
            ];
        })->values()->all();
    }

    private function assertRequiredDocumentsReady(ScholarshipApplication $application): void
    {
        $application->loadMissing('documents');
        $types = ScholarshipDocumentType::forApplicantType($application->applicant_type)
            ->where('is_required', true)
            ->get();

        if ($types->isEmpty()) {
            throw ValidationException::withMessages([
                'documents' => 'The scholarship document checklist is not configured. Please contact the scholarship office.',
            ]);
        }

        $missing = [];
        foreach ($types as $type) {
            $doc = $application->documents->first(function (ScholarshipApplicationDocument $document) use ($type) {
                return $document->document_type_id === $type->id
                    && $document->isScanClean()
                    && $document->status !== ScholarshipApplicationDocument::STATUS_REJECTED;
            });

            if (! $doc) {
                $missing[] = $type->label;
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'documents' => 'Upload all required documents before submitting: '.implode(', ', $missing),
            ]);
        }
    }

    /**
     * @param  list<string>  $allowedFrom
     */
    private function assertTransition(ScholarshipApplication $application, array $allowedFrom): void
    {
        if (! in_array($application->status, $allowedFrom, true)) {
            throw ValidationException::withMessages([
                'status' => "Cannot perform this action while the application is {$application->status}.",
            ]);
        }
    }

    private function transition(
        ScholarshipApplication $application,
        string $toStatus,
        User $staff,
        string $note,
        array $attributes = [],
        bool $notify = true,
        ?string $notifyTitle = null,
        ?string $notifyBody = null,
    ): ScholarshipApplication {
        return DB::transaction(function () use ($application, $toStatus, $staff, $note, $attributes, $notify, $notifyTitle, $notifyBody) {
            $from = $application->status;
            $application->fill(array_merge($attributes, ['status' => $toStatus]));
            $application->save();

            $this->recordEvent(
                $application,
                $from,
                $toStatus,
                ScholarshipApplicationEvent::ACTOR_STAFF,
                $staff->id,
                $note
            );

            if ($notify && $notifyTitle && $notifyBody) {
                $this->notifyResident($application, $notifyTitle, $notifyBody);
            }

            return $application->fresh(['program', 'documents.documentType', 'events', 'resident']);
        });
    }

    private function recordEvent(
        ScholarshipApplication $application,
        ?string $from,
        string $to,
        string $actorType,
        ?int $actorId,
        string $note,
        array $meta = [],
    ): void {
        ScholarshipApplicationEvent::create([
            'scholarship_application_id' => $application->id,
            'from_status' => $from,
            'to_status' => $to,
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'note' => $note,
            'meta' => $meta ?: null,
        ]);
    }

    private function notifyResident(ScholarshipApplication $application, string $title, string $body): void
    {
        $this->pushNotifications->createInAppNotificationForResident(
            $application->resident_id,
            $title,
            $body,
            'scholarship-application',
            [
                'application_id' => $application->id,
                'reference_number' => $application->reference_number,
                'status' => $application->status,
            ],
            true
        );
    }
}
