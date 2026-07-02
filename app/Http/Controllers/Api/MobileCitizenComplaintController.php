<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Services\ComplaintAuditLogger;
use App\Services\ComplaintWorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class MobileCitizenComplaintController extends Controller
{
    public function __construct(
        private ComplaintWorkflowService $workflowService,
        private ComplaintAuditLogger $auditLogger
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->isCitizen(), 403);

        $perPage = max(1, min(50, $request->integer('per_page', 20)));

        $query = Complaint::query()
            ->where('submitted_by_user_id', $user->id)
            ->with([
                'category:id,name',
                'barangay:id,name',
                'previewImageAttachment:id,complaint_id,storage_disk,storage_path,mime_type,original_name,virus_scan_status',
            ])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
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
        $user = $request->user();
        abort_unless($user->isCitizen(), 403);
        abort_unless((int) $complaint->submitted_by_user_id === (int) $user->id, 404);

        $complaint->load([
            'category:id,name',
            'barangay:id,name',
            'previewImageAttachment:id,complaint_id,storage_disk,storage_path,mime_type,original_name,virus_scan_status',
        ]);

        return response()->json([
            'data' => $this->serializeComplaint($complaint, includeDescription: true),
        ]);
    }

    public function update(Request $request, Complaint $complaint): JsonResponse
    {
        $this->authorize('updateCitizen', $complaint);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'short_summary' => ['required', 'string', 'max:280'],
            'description' => ['required', 'string'],
            'category_id' => ['required', Rule::exists('complaint_categories', 'id')],
            'visibility' => ['required', Rule::in([
                Complaint::VISIBILITY_PUBLIC_NAMED,
                Complaint::VISIBILITY_PUBLIC_ANONYMOUS,
                Complaint::VISIBILITY_PRIVATE,
            ])],
            'barangay_id' => ['nullable', Rule::exists('bosesmoto_barangays', 'id')],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'official_ids' => ['nullable', 'array'],
            'official_ids.*' => ['integer', Rule::exists('public_officials', 'id')],
        ]);

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

        $this->auditLogger->log(
            'complaint_updated_by_citizen',
            $complaint,
            $complaint,
            $request->user(),
            $request
        );

        $complaint->load([
            'category:id,name',
            'barangay:id,name',
            'previewImageAttachment:id,complaint_id,storage_disk,storage_path,mime_type,original_name,virus_scan_status',
        ]);

        return response()->json([
            'message' => 'Complaint updated.',
            'data' => $this->serializeComplaint($complaint, includeDescription: true),
        ]);
    }

    public function confirmResolution(Request $request, Complaint $complaint): JsonResponse
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

        $this->auditLogger->log(
            'complaint_resolution_confirmed',
            $complaint,
            $complaint,
            $request->user(),
            $request
        );

        $complaint->load([
            'category:id,name',
            'barangay:id,name',
            'previewImageAttachment:id,complaint_id,storage_disk,storage_path,mime_type,original_name,virus_scan_status',
        ]);

        return response()->json([
            'message' => 'Resolution confirmed. Complaint closed.',
            'data' => $this->serializeComplaint($complaint, includeDescription: true),
        ]);
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
            $payload['resolution_summary'] = $complaint->resolution_summary;
        }

        return $payload;
    }

    private function previewImageUrl(Complaint $complaint): ?string
    {
        if (!$complaint->relationLoaded('previewImageAttachment')) {
            return null;
        }

        if ($complaint->previewImageAttachment === null) {
            return null;
        }

        return route('api.mobile.complaints.preview-image', [
            'complaint' => $complaint->id,
        ]);
    }
}
