<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\ComplaintAttachment;
use App\Models\ComplaintComment;
use App\Models\ComplaintCommentReaction;
use App\Services\AttachmentVirusScanner;
use App\Services\ComplaintAuditLogger;
use App\Services\ComplaintWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MobileComplaintController extends Controller
{
    public function __construct(
        private ComplaintWorkflowService $workflowService,
        private ComplaintAuditLogger $auditLogger,
        private AttachmentVirusScanner $virusScanner
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, $request->integer('per_page', 20)));

        $query = Complaint::query()
            ->publicListing()
            ->with([
                'category:id,name',
                'barangay:id,name',
                'previewImageAttachment:id,complaint_id,storage_disk,storage_path,mime_type,original_name,virus_scan_status',
            ])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->integer('category_id'));
        }

        $complaints = $query->paginate($perPage)->withQueryString();

        return response()->json([
            'data' => $complaints
                ->getCollection()
                ->map(fn (Complaint $complaint): array => $this->serializeComplaint($complaint))
                ->values(),
            'meta' => [
                'current_page' => $complaints->currentPage(),
                'last_page' => $complaints->lastPage(),
                'per_page' => $complaints->perPage(),
                'total' => $complaints->total(),
            ],
        ]);
    }

    public function show(Request $request, Complaint $complaint): JsonResponse
    {
        abort_unless($complaint->isPubliclyVisible(), 404);
        $viewerUserId = $request->user()?->id;

        $complaint->load([
            'category:id,name',
            'barangay:id,name',
            'previewImageAttachment:id,complaint_id,storage_disk,storage_path,mime_type,original_name,virus_scan_status',
        ]);

        $comments = $complaint->visibleComments()
            ->with('user:id,name,profile_photo_path')
            ->withCount([
                'reactions as likes_count' => fn ($query) => $query
                    ->where('reaction', ComplaintCommentReaction::REACTION_LIKE),
                'reactions as dislikes_count' => fn ($query) => $query
                    ->where('reaction', ComplaintCommentReaction::REACTION_DISLIKE),
            ])
            ->when(
                $viewerUserId !== null,
                fn ($query) => $query->with([
                    'reactions' => fn ($reactionQuery) => $reactionQuery
                        ->select(['id', 'complaint_comment_id', 'user_id', 'reaction'])
                        ->where('user_id', $viewerUserId),
                ])
            )
            ->oldest('created_at')
            ->get();

        return response()->json([
            'data' => $this->serializeComplaint($complaint, includeDescription: true),
            'comments' => $comments->map(
                fn (ComplaintComment $comment): array => $this->serializeComment($comment, $viewerUserId)
            )->values(),
        ]);
    }

    public function previewImage(Request $request, Complaint $complaint): StreamedResponse
    {
        $attachment = $complaint->previewImageAttachment()->first();
        abort_if($attachment === null, 404);

        $user = $request->user('sanctum') ?? $request->user();
        $canView = $complaint->isPubliclyVisible()
            || ($user && ((int) $complaint->submitted_by_user_id === (int) $user->id || $user->isInternalUser()));
        abort_unless($canView, 403);

        if (! Storage::disk($attachment->storage_disk)->exists($attachment->storage_path)) {
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

    public function store(Request $request): JsonResponse
    {
        $user = $request->user('sanctum') ?? $request->user();
        abort_unless($user?->isCitizen(), 403, 'Only authenticated residents can submit complaints.');

        $isCitizenSubmission = true;

        if ($isCitizenSubmission) {
            $limit = (int) config('complaints.submission_limits.citizen_daily', 5);
            $todayCount = Complaint::query()
                ->where('submitted_by_user_id', $user->id)
                ->whereDate('created_at', Carbon::today())
                ->count();

            abort_if(
                $todayCount >= $limit,
                422,
                "Daily complaint limit reached ({$limit}/day)."
            );
        }

        $validated = $request->validate([
            'reporter_name' => [$isCitizenSubmission ? 'exclude' : 'nullable', 'string', 'max:255'],
            'reporter_email' => [$isCitizenSubmission ? 'exclude' : 'nullable', 'email', 'max:255'],
            'device_fingerprint' => [$isCitizenSubmission ? 'exclude' : 'nullable', 'string', 'max:191'],
            'title' => ['required', 'string', 'max:255'],
            'short_summary' => ['nullable', 'string', 'max:280'],
            'description' => ['required', 'string'],
            'category_id' => ['required', Rule::exists('complaint_categories', 'id')],
            'visibility' => ['nullable', Rule::in(array_keys($this->visibilityOptions($isCitizenSubmission)))],
            'barangay_id' => ['nullable', Rule::exists('bosesmoto_barangays', 'id')],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'official_ids' => ['nullable', 'array'],
            'official_ids.*' => ['integer', Rule::exists('public_officials', 'id')],
            'photo' => [
                'nullable',
                'file',
                'max:'.(int) config('complaints.attachments.max_size_kb', 20480),
                'mimetypes:image/jpeg,image/png,image/webp',
            ],
        ]);

        $complaint = new Complaint([
            'reference_code' => $this->generateReferenceCode(),
            'submitted_by_user_id' => $isCitizenSubmission ? $user?->id : null,
            'is_anonymous_submission' => ! $isCitizenSubmission,
            'reporter_name' => $isCitizenSubmission ? null : ($validated['reporter_name'] ?? null),
            'reporter_email' => $isCitizenSubmission ? null : ($validated['reporter_email'] ?? null),
            'title' => $validated['title'],
            'short_summary' => $this->summaryFromInput($validated),
            'description' => $validated['description'],
            'category_id' => $validated['category_id'],
            'visibility' => Complaint::VISIBILITY_PRIVATE,
            'barangay_id' => null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'status' => Complaint::STATUS_RECEIVED,
            'moderation_status' => Complaint::MODERATION_NORMAL,
            'submitted_ip' => $request->ip(),
            'submitted_device_hash' => ! $isCitizenSubmission
                ? $this->hashAnonymousDeviceId(
                    trim($request->string('device_fingerprint')->toString()) !== ''
                        ? trim($request->string('device_fingerprint')->toString())
                        : 'ip:'.$request->ip()
                )
                : null,
        ]);

        $this->workflowService->initializeSla($complaint, Carbon::now());
        $complaint->save();
        $complaint->officials()->sync($validated['official_ids'] ?? []);

        $photo = $request->file('photo');
        if ($photo instanceof UploadedFile) {
            $this->persistPhoto($complaint, $photo, $user?->id, $request);
        }

        $this->auditLogger->log(
            'complaint_submitted',
            $complaint,
            $complaint,
            $user,
            $request,
            [
                'submission_type' => $isCitizenSubmission ? 'mobile_citizen' : 'mobile_anonymous',
            ]
        );

        $complaint->load([
            'category:id,name',
            'barangay:id,name',
            'previewImageAttachment:id,complaint_id,storage_disk,storage_path,mime_type,original_name,virus_scan_status',
        ]);

        return response()->json([
            'message' => 'Complaint submitted successfully.',
            'data' => $this->serializeComplaint($complaint, includeDescription: true),
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeComplaint(Complaint $complaint, bool $includeDescription = false): array
    {
        $payload = [
            'id' => $complaint->id,
            'reference_code' => $complaint->reference_code,
            'title' => $complaint->title,
            'short_summary' => $complaint->short_summary,
            'status' => $complaint->status,
            'visibility' => $complaint->visibility,
            'is_anonymous_submission' => (bool) $complaint->is_anonymous_submission,
            'support_count' => (int) $complaint->support_count,
            'created_at' => $complaint->created_at?->toISOString(),
            'resolved_at' => $complaint->resolved_at?->toISOString(),
            'closed_at' => $complaint->closed_at?->toISOString(),
            'category_name' => $complaint->category?->name,
            'barangay_name' => $complaint->barangay?->name,
            'preview_image_url' => $this->previewImageUrl($complaint),
            'category' => $complaint->category
                ? [
                    'id' => $complaint->category->id,
                    'name' => $complaint->category->name,
                ]
                : null,
            'barangay' => $complaint->barangay
                ? [
                    'id' => $complaint->barangay->id,
                    'name' => $complaint->barangay->name,
                ]
                : null,
            'coordinates' => ($complaint->latitude !== null && $complaint->longitude !== null)
                ? [
                    'latitude' => $complaint->latitude,
                    'longitude' => $complaint->longitude,
                ]
                : null,
        ];

        if ($includeDescription) {
            $payload['description'] = $complaint->description;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeComment(ComplaintComment $comment, ?int $viewerUserId): array
    {
        $userReaction = null;
        if ($viewerUserId !== null) {
            $viewerReaction = $comment->reactions
                ->firstWhere('user_id', $viewerUserId);
            $userReaction = $viewerReaction?->reaction;
        }

        return [
            'id' => $comment->id,
            'complaint_id' => $comment->complaint_id,
            'body' => $comment->body,
            'is_staff_response' => (bool) $comment->is_staff_response,
            'created_at' => $comment->created_at?->toISOString(),
            'likes_count' => (int) ($comment->likes_count ?? 0),
            'dislikes_count' => (int) ($comment->dislikes_count ?? 0),
            'user_reaction' => $userReaction,
            'user' => $comment->user
                ? [
                    'id' => $comment->user->id,
                    'name' => $comment->user->name,
                    'role' => $comment->user->role,
                    'profile_photo_url' => $comment->user->profilePhotoUrl(),
                ]
                : null,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function visibilityOptions(bool $isCitizenSubmission): array
    {
        if ($isCitizenSubmission) {
            return [
                Complaint::VISIBILITY_PUBLIC_NAMED => 'Show identity publicly',
                Complaint::VISIBILITY_PUBLIC_ANONYMOUS => 'Hide identity publicly',
                Complaint::VISIBILITY_PRIVATE => 'Private (internal only)',
            ];
        }

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

    /**
     * @param  array<string, mixed>  $validated
     */
    private function summaryFromInput(array $validated): string
    {
        $summary = trim((string) ($validated['short_summary'] ?? ''));
        if ($summary !== '') {
            return $summary;
        }

        $description = preg_replace('/\s+/', ' ', trim((string) $validated['description'])) ?: '';

        return Str::limit($description, 280, '');
    }

    private function hashAnonymousDeviceId(string $value): string
    {
        return hash('sha256', trim($value));
    }

    private function previewImageUrl(Complaint $complaint): ?string
    {
        if (! $complaint->relationLoaded('previewImageAttachment')) {
            return null;
        }

        if ($complaint->previewImageAttachment === null) {
            return null;
        }

        return route('api.mobile.complaints.preview-image', [
            'complaint' => $complaint->id,
        ]);
    }

    private function persistPhoto(
        Complaint $complaint,
        UploadedFile $photo,
        ?int $uploadedByUserId,
        Request $request
    ): void {
        $path = $photo->store('complaints/'.$complaint->id, 'local');
        $scan = $this->virusScanner->scan($photo);

        $attachment = $complaint->attachments()->create([
            'uploaded_by_user_id' => $uploadedByUserId,
            'type' => ComplaintAttachment::TYPE_EVIDENCE,
            'storage_disk' => 'local',
            'storage_path' => $path,
            'original_name' => $photo->getClientOriginalName(),
            'mime_type' => $photo->getMimeType() ?: 'application/octet-stream',
            'size_bytes' => (int) $photo->getSize(),
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
            $request->user('sanctum') ?? $request->user(),
            $request,
            ['scan_status' => $scan['status']]
        );
    }
}
