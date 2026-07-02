<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\ComplaintBarangay;
use App\Models\ComplaintAttachment;
use App\Models\ComplaintCategory;
use App\Models\ComplaintCommentReaction;
use App\Models\PublicOfficial;
use App\Services\AttachmentVirusScanner;
use App\Services\ComplaintAuditLogger;
use App\Services\ComplaintSimilarityService;
use App\Services\ComplaintWorkflowService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ComplaintController extends Controller
{
    public function __construct(
        private ComplaintSimilarityService $similarityService,
        private ComplaintWorkflowService $workflowService,
        private ComplaintAuditLogger $auditLogger,
        private AttachmentVirusScanner $virusScanner
    ) {
    }

    public function publicIndex(Request $request): View
    {
        $viewerUserId = $request->user()?->id;

        $query = Complaint::query()
            ->publicListing()
            ->with(array_merge([
                'category:id,name',
                'barangay:id,name',
                'submitter:id,name,profile_photo_path',
                'previewImageAttachment:id,complaint_id,storage_disk,storage_path,mime_type,original_name',
            ], $this->publicCommentRelations($viewerUserId)))
            ->withCount('visibleComments')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        $view = $request->user()
            ? 'complaints.public-index-app'
            : 'complaints.public-index';

        return view($view, [
            'complaints' => $query->paginate(12)->withQueryString(),
            'categories' => ComplaintCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'statuses' => config('complaints.workflow.statuses'),
        ]);
    }

    public function publicShow(Request $request, Complaint $complaint): View
    {
        abort_unless($complaint->isPubliclyVisible(), 404);

        $complaint->load(array_merge([
            'category:id,name',
            'barangay:id,name',
            'submitter:id,name,profile_photo_path',
            'officials:id,name,position',
            'previewImageAttachment:id,complaint_id,storage_disk,storage_path,mime_type,original_name',
        ], $this->publicCommentRelations($request->user()?->id)));

        $view = $request->user()
            ? 'complaints.public-show-app'
            : 'complaints.public-show';

        return view($view, [
            'complaint' => $complaint,
        ]);
    }

    public function previewImage(Request $request, Complaint $complaint): StreamedResponse
    {
        $attachment = $complaint->previewImageAttachment()->first();
        abort_if($attachment === null, 404);

        $user = $request->user();
        $canView = $complaint->isPubliclyVisible()
            || ($user && ((int) $complaint->submitted_by_user_id === (int) $user->id || $user->isInternalUser()));
        abort_unless($canView, 403);

        if (!Storage::disk($attachment->storage_disk)->exists($attachment->storage_path)) {
            abort(404);
        }

        $stream = Storage::disk($attachment->storage_disk)->readStream($attachment->storage_path);
        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => $attachment->mime_type ?: 'image/jpeg',
            'Content-Disposition' => 'inline; filename="'.$attachment->original_name.'"',
            'Cache-Control' => 'public, max-age=300',
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('createCitizen', Complaint::class);

        return view('complaints.create', [
            'categories' => ComplaintCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'barangays' => ComplaintBarangay::query()->where('is_active', true)->orderBy('name')->get(),
            'officials' => PublicOfficial::query()->where('is_active', true)->orderBy('position')->orderBy('name')->get(),
            'visibilityOptions' => $this->visibilityOptionsForCitizen(),
        ]);
    }

    public function createQuick(Request $request): View
    {
        $this->authorize('createCitizen', Complaint::class);

        return view('complaints.create-quick', [
            'categories' => ComplaintCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'visibilityOptions' => [
                Complaint::VISIBILITY_PUBLIC_ANONYMOUS => 'Hide my identity publicly',
                Complaint::VISIBILITY_PUBLIC_NAMED => 'Show my identity publicly',
                Complaint::VISIBILITY_PRIVATE => 'Private (internal only)',
            ],
        ]);
    }

    public function createAnonymous(Request $request): View
    {
        abort_if($request->user()?->isInternalUser(), 403, 'Internal government users cannot submit anonymous complaints.');

        $view = $request->user()
            ? 'complaints.create-anonymous-app'
            : 'complaints.create-anonymous';

        return view($view, [
            'categories' => ComplaintCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'barangays' => ComplaintBarangay::query()->where('is_active', true)->orderBy('name')->get(),
            'officials' => PublicOfficial::query()->where('is_active', true)->orderBy('position')->orderBy('name')->get(),
            'visibilityOptions' => $this->visibilityOptionsForAnonymous(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('createCitizen', Complaint::class);

        $limit = (int) config('complaints.submission_limits.citizen_daily', 5);
        $todayCount = Complaint::query()
            ->where('submitted_by_user_id', $request->user()->id)
            ->whereDate('created_at', Carbon::today())
            ->count();

        if ($todayCount >= $limit) {
            return back()->withErrors([
                'title' => "Daily complaint limit reached ({$limit}/day).",
            ])->withInput();
        }

        $validated = $this->validateComplaintInput($request, false);

        $complaint = new Complaint([
            'reference_code' => $this->generateReferenceCode(),
            'submitted_by_user_id' => $request->user()->id,
            'is_anonymous_submission' => false,
            'title' => $validated['title'],
            'short_summary' => $validated['short_summary'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'visibility' => $validated['visibility'],
            'barangay_id' => $validated['barangay_id'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'status' => Complaint::STATUS_RECEIVED,
            'moderation_status' => Complaint::MODERATION_NORMAL,
            'submitted_ip' => $request->ip(),
        ]);

        $this->workflowService->initializeSla($complaint, Carbon::now());
        $complaint->save();

        $complaint->officials()->sync($validated['official_ids'] ?? []);
        $this->persistAttachments($complaint, $request->file('attachments', []), $request->user()->id, ComplaintAttachment::TYPE_EVIDENCE, $request);

        $this->auditLogger->log('complaint_submitted', $complaint, $complaint, $request->user(), $request, [
            'submission_type' => 'citizen',
        ]);

        return redirect()->route('complaints.my.index')->with('status', 'Complaint submitted successfully.');
    }

    public function storeQuick(Request $request): RedirectResponse
    {
        $this->authorize('createCitizen', Complaint::class);

        $limit = (int) config('complaints.submission_limits.citizen_daily', 5);
        $todayCount = Complaint::query()
            ->where('submitted_by_user_id', $request->user()->id)
            ->whereDate('created_at', Carbon::today())
            ->count();

        if ($todayCount >= $limit) {
            return back()->withErrors([
                'issue_summary' => "Daily complaint limit reached ({$limit}/day).",
            ])->withInput();
        }

        $validated = $request->validate([
            'photo' => [
                'required',
                'file',
                'max:'.(int) config('complaints.attachments.max_size_kb', 20480),
                'mimetypes:image/jpeg,image/png,image/webp',
            ],
            'category_id' => ['required', Rule::exists('complaint_categories', 'id')],
            'issue_summary' => ['required', 'string', 'max:280'],
            'details' => ['nullable', 'string'],
            'visibility' => ['required', Rule::in(array_keys($this->visibilityOptionsForCitizen()))],
        ]);

        $shortSummary = trim($validated['issue_summary']);
        $title = Str::limit($shortSummary, 120, '');
        $description = trim((string) ($validated['details'] ?? '')) ?: $shortSummary;

        $complaint = new Complaint([
            'reference_code' => $this->generateReferenceCode(),
            'submitted_by_user_id' => $request->user()->id,
            'is_anonymous_submission' => false,
            'title' => $title,
            'short_summary' => $shortSummary,
            'description' => $description,
            'category_id' => (int) $validated['category_id'],
            'visibility' => $validated['visibility'],
            'status' => Complaint::STATUS_RECEIVED,
            'moderation_status' => Complaint::MODERATION_NORMAL,
            'submitted_ip' => $request->ip(),
        ]);

        $this->workflowService->initializeSla($complaint, Carbon::now());
        $complaint->save();

        $this->persistAttachments(
            $complaint,
            [$request->file('photo')],
            $request->user()->id,
            ComplaintAttachment::TYPE_EVIDENCE,
            $request
        );

        $this->auditLogger->log('complaint_submitted', $complaint, $complaint, $request->user(), $request, [
            'submission_type' => 'citizen_quick',
        ]);

        return redirect()->route('complaints.my.index')->with('status', 'Quick ticket submitted successfully.');
    }

    public function storeAnonymous(Request $request): RedirectResponse
    {
        abort_if($request->user()?->isInternalUser(), 403, 'Internal government users cannot submit anonymous complaints.');

        $limit = (int) config(
            'complaints.submission_limits.anonymous_device_daily',
            (int) config('complaints.submission_limits.anonymous_ip_daily', 2)
        );

        $rawDeviceFingerprint = trim($request->string('device_fingerprint')->toString());
        $deviceHash = $this->hashAnonymousDeviceId(
            $rawDeviceFingerprint !== '' ? $rawDeviceFingerprint : 'ip:'.$request->ip()
        );

        $todayCount = Complaint::query()
            ->whereNull('submitted_by_user_id')
            ->where('submitted_device_hash', $deviceHash)
            ->whereDate('created_at', Carbon::today())
            ->count();

        if ($todayCount >= $limit) {
            return back()->withErrors([
                'title' => "Anonymous submission limit reached ({$limit} per device/day).",
            ])->withInput();
        }

        $validated = $this->validateComplaintInput($request, true);

        $complaint = new Complaint([
            'reference_code' => $this->generateReferenceCode(),
            'submitted_by_user_id' => null,
            'is_anonymous_submission' => true,
            'reporter_name' => $validated['reporter_name'] ?? null,
            'reporter_email' => $validated['reporter_email'] ?? null,
            'title' => $validated['title'],
            'short_summary' => $validated['short_summary'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'visibility' => $validated['visibility'],
            'barangay_id' => $validated['barangay_id'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'status' => Complaint::STATUS_RECEIVED,
            'moderation_status' => Complaint::MODERATION_NORMAL,
            'submitted_ip' => $request->ip(),
            'submitted_device_hash' => $deviceHash,
        ]);

        $this->workflowService->initializeSla($complaint, Carbon::now());
        $complaint->save();

        $complaint->officials()->sync($validated['official_ids'] ?? []);
        $this->persistAttachments($complaint, $request->file('attachments', []), null, ComplaintAttachment::TYPE_EVIDENCE, $request);

        $this->auditLogger->log('complaint_submitted', $complaint, $complaint, null, $request, [
            'submission_type' => 'anonymous',
        ]);

        return redirect()->route('complaints.public.index')
            ->with('status', 'Complaint submitted. Keep this reference code: '.$complaint->reference_code);
    }

    public function myIndex(Request $request): View
    {
        abort_unless($request->user()->isCitizen(), 403);

        $complaints = Complaint::query()
            ->where('submitted_by_user_id', $request->user()->id)
            ->with([
                'category:id,name',
                'assignedDepartment:id,name',
                'assignedOfficer:id,name',
                'previewImageAttachment:id,complaint_id,storage_disk,storage_path,mime_type,original_name',
            ])
            ->latest('id')
            ->paginate(10);

        return view('complaints.my-index', [
            'complaints' => $complaints,
        ]);
    }

    public function edit(Request $request, Complaint $complaint): View
    {
        $this->authorize('updateCitizen', $complaint);

        return view('complaints.edit', [
            'complaint' => $complaint->load('officials:id'),
            'categories' => ComplaintCategory::query()->where('is_active', true)->orderBy('name')->get(),
            'barangays' => ComplaintBarangay::query()->where('is_active', true)->orderBy('name')->get(),
            'officials' => PublicOfficial::query()->where('is_active', true)->orderBy('position')->orderBy('name')->get(),
            'visibilityOptions' => $this->visibilityOptionsForCitizen(),
        ]);
    }

    public function update(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('updateCitizen', $complaint);
        $validated = $this->validateComplaintInput($request, false);

        $complaint->fill([
            'title' => $validated['title'],
            'short_summary' => $validated['short_summary'],
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'visibility' => $validated['visibility'],
            'barangay_id' => $validated['barangay_id'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);
        $complaint->save();

        $complaint->officials()->sync($validated['official_ids'] ?? []);
        $this->persistAttachments($complaint, $request->file('attachments', []), $request->user()->id, ComplaintAttachment::TYPE_EVIDENCE, $request);

        $this->auditLogger->log('complaint_updated_by_citizen', $complaint, $complaint, $request->user(), $request);

        return redirect()->route('complaints.my.index')->with('status', 'Complaint updated.');
    }

    public function confirmResolution(Request $request, Complaint $complaint): RedirectResponse
    {
        $this->authorize('confirmResolution', $complaint);

        $this->workflowService->transition(
            $complaint,
            Complaint::STATUS_CLOSED,
            $request->user(),
            'Citizen confirmed resolution.'
        );

        $complaint->citizen_confirmed_at = Carbon::now();
        $complaint->save();

        $this->auditLogger->log('complaint_resolution_confirmed', $complaint, $complaint, $request->user(), $request);

        return back()->with('status', 'Resolution confirmed. Complaint closed.');
    }

    public function similar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'short_summary' => ['nullable', 'string', 'max:280'],
            'exclude_id' => ['nullable', 'integer', Rule::exists('complaints', 'id')],
        ]);

        $similar = $this->similarityService->findSimilar(
            $validated['title'],
            $validated['short_summary'] ?? null,
            isset($validated['exclude_id']) ? (int) $validated['exclude_id'] : null
        );

        return response()->json([
            'data' => $similar->map(fn (Complaint $complaint) => [
                'id' => $complaint->id,
                'reference_code' => $complaint->reference_code,
                'title' => $complaint->title,
                'short_summary' => $complaint->short_summary,
                'status' => $complaint->status,
                'support_count' => $complaint->support_count,
                'url' => route('complaints.public.show', $complaint),
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateComplaintInput(Request $request, bool $isAnonymous): array
    {
        $visibilityOptions = $isAnonymous ? $this->visibilityOptionsForAnonymous() : $this->visibilityOptionsForCitizen();

        return $request->validate([
            'reporter_name' => [$isAnonymous ? 'nullable' : 'exclude', 'string', 'max:255'],
            'reporter_email' => [$isAnonymous ? 'nullable' : 'exclude', 'email', 'max:255'],
            'device_fingerprint' => [$isAnonymous ? 'nullable' : 'exclude', 'string', 'max:191'],
            'title' => ['required', 'string', 'max:255'],
            'short_summary' => ['required', 'string', 'max:280'],
            'description' => ['required', 'string'],
            'category_id' => ['required', Rule::exists('complaint_categories', 'id')],
            'visibility' => ['required', Rule::in(array_keys($visibilityOptions))],
            'barangay_id' => ['nullable', Rule::exists('bosesmoto_barangays', 'id')],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'official_ids' => ['nullable', 'array'],
            'official_ids.*' => ['integer', Rule::exists('public_officials', 'id')],
            'attachments' => ['nullable', 'array', 'max:'.(int) config('complaints.attachments.max_files_per_complaint', 5)],
            'attachments.*' => [
                'file',
                'max:'.(int) config('complaints.attachments.max_size_kb', 20480),
                'mimetypes:'.implode(',', config('complaints.attachments.allowed_mime_types', [])),
            ],
        ]);
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function persistAttachments(
        Complaint $complaint,
        array $files,
        ?int $uploadedByUserId,
        string $type,
        Request $request
    ): void {
        if (empty($files)) {
            return;
        }

        $currentCount = $complaint->attachments()->count();
        $maxFiles = (int) config('complaints.attachments.max_files_per_complaint', 5);
        if (($currentCount + count($files)) > $maxFiles) {
            abort(422, "Attachment limit exceeded. Max {$maxFiles} files per complaint.");
        }

        foreach ($files as $file) {
            $path = $file->store('complaints/'.$complaint->id, 'local');
            $scan = $this->virusScanner->scan($file);

            $attachment = $complaint->attachments()->create([
                'uploaded_by_user_id' => $uploadedByUserId,
                'type' => $type,
                'storage_disk' => 'local',
                'storage_path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size_bytes' => (int) $file->getSize(),
                'virus_scan_status' => $scan['status'],
                'virus_scan_message' => $scan['message'],
                'scanned_at' => Carbon::now(),
            ]);

            if ($scan['status'] === ComplaintAttachment::SCAN_INFECTED || $scan['status'] === ComplaintAttachment::SCAN_FAILED) {
                Storage::disk('local')->delete($path);
            }

            $this->auditLogger->log(
                'attachment_uploaded',
                $complaint,
                $attachment,
                $request->user(),
                $request,
                ['scan_status' => $scan['status']]
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private function visibilityOptionsForCitizen(): array
    {
        return [
            Complaint::VISIBILITY_PUBLIC_NAMED => 'Show identity publicly',
            Complaint::VISIBILITY_PUBLIC_ANONYMOUS => 'Hide identity publicly',
            Complaint::VISIBILITY_PRIVATE => 'Private (internal only)',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function visibilityOptionsForAnonymous(): array
    {
        return [
            Complaint::VISIBILITY_PUBLIC_ANONYMOUS => 'Public (anonymous)',
            Complaint::VISIBILITY_PRIVATE => 'Private (internal only)',
        ];
    }

    private function generateReferenceCode(): string
    {
        do {
            $code = 'CMP-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Complaint::query()->where('reference_code', $code)->exists());

        return $code;
    }

    private function hashAnonymousDeviceId(string $value): string
    {
        return hash('sha256', trim($value));
    }

    /**
     * @return array<string, mixed>
     */
    private function publicCommentRelations(?int $viewerUserId): array
    {
        return [
            'visibleComments' => function ($query) use ($viewerUserId): void {
                $query
                    ->select(['id', 'complaint_id', 'user_id', 'body', 'is_staff_response', 'created_at'])
                    ->with('user:id,name,profile_photo_path')
                    ->withCount([
                        'reactions as likes_count' => fn ($reactionQuery) => $reactionQuery
                            ->where('reaction', ComplaintCommentReaction::REACTION_LIKE),
                        'reactions as dislikes_count' => fn ($reactionQuery) => $reactionQuery
                            ->where('reaction', ComplaintCommentReaction::REACTION_DISLIKE),
                    ])
                    ->oldest('created_at');

                if ($viewerUserId !== null) {
                    $query->with([
                        'reactions' => fn ($reactionQuery) => $reactionQuery
                            ->select(['id', 'complaint_comment_id', 'user_id', 'reaction'])
                            ->where('user_id', $viewerUserId),
                    ]);
                }
            },
        ];
    }
}
