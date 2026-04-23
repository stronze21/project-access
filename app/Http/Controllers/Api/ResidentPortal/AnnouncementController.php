<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AyudaProgram;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AnnouncementController extends Controller
{
    /**
     * List published announcements visible to the authenticated resident.
     */
    public function index(Request $request): JsonResponse
    {
        $resident = $request->user();

        $query = Announcement::published();

        if ($request->type) {
            $query->where('type', $request->type);
        }

        // Filter by recipient type: always show 'all' announcements,
        // plus 'program_beneficiaries' where resident is eligible
        $eligibleProgramIds = $this->getEligibleProgramIds($resident);

        $query->where(function ($q) use ($eligibleProgramIds) {
            $q->where('recipient_type', 'all');

            if (!empty($eligibleProgramIds)) {
                $q->orWhere(function ($q2) use ($eligibleProgramIds) {
                    $q2->where('recipient_type', 'program_beneficiaries')
                       ->whereIn('ayuda_program_id', $eligibleProgramIds);
                });
            }
        });

        $announcements = $query->select([
                'id', 'title', 'content', 'type', 'priority',
                'image_path', 'ayuda_program_id', 'published_at',
                'is_pinned', 'recipient_type',
            ])
            ->with('program:id,name,code')
            ->orderBy('is_pinned', 'desc')
            ->orderBy('published_at', 'desc')
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'data' => $announcements->items(),
            'meta' => [
                'current_page' => $announcements->currentPage(),
                'last_page' => $announcements->lastPage(),
                'per_page' => $announcements->perPage(),
                'total' => $announcements->total(),
            ],
        ]);
    }

    /**
     * Get a specific announcement.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $eligibleProgramIds = $this->getEligibleProgramIds($request->user());

        $announcement = Announcement::published()
            ->where(function ($q) use ($eligibleProgramIds) {
                $q->where('recipient_type', 'all');

                if (!empty($eligibleProgramIds)) {
                    $q->orWhere(function ($q2) use ($eligibleProgramIds) {
                        $q2->where('recipient_type', 'program_beneficiaries')
                           ->whereIn('ayuda_program_id', $eligibleProgramIds);
                    });
                }
            })
            ->with('program:id,name,code,description')
            ->findOrFail($id);

        return response()->json(['data' => $announcement]);
    }

    /**
     * Get program IDs the resident is eligible for.
     * Wrapped in try-catch so eligibility errors never block 'all' announcements.
     */
    private function getEligibleProgramIds($resident): array
    {
        try {
            $programs = AyudaProgram::where('is_active', true)
                ->with('eligibilityCriteria')
                ->get();

            $eligibleIds = [];
            foreach ($programs as $program) {
                if ($resident->isEligibleFor($program)) {
                    $eligibleIds[] = $program->id;
                }
            }

            return $eligibleIds;
        } catch (\Exception $e) {
            Log::warning('Failed to compute eligible programs for resident ' . $resident->id . ': ' . $e->getMessage());
            return [];
        }
    }
}
