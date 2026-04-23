<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\CitizenServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ServiceTrackingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $resident = $request->user();

        $query = CitizenServiceRequest::where('resident_id', $resident->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        $requests = $query->orderByDesc('status_updated_at')
            ->orderByDesc('submitted_at')
            ->paginate($request->integer('per_page', 10));

        $summary = [
            'total' => CitizenServiceRequest::where('resident_id', $resident->id)->count(),
            'active' => CitizenServiceRequest::where('resident_id', $resident->id)
                ->whereNotIn('status', ['completed', 'released', 'cancelled', 'rejected'])
                ->count(),
            'completed' => CitizenServiceRequest::where('resident_id', $resident->id)
                ->whereIn('status', ['completed', 'released'])
                ->count(),
        ];

        return response()->json([
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
            'summary' => $summary,
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $serviceRequest = CitizenServiceRequest::where('resident_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json(['data' => $serviceRequest]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'service_type' => 'required|string|max:100',
            'service_name' => 'required|string|max:255',
            'current_step' => 'nullable|string|max:150',
            'expected_completion_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $serviceRequest = CitizenServiceRequest::create([
            'resident_id' => $request->user()->id,
            'service_type' => $request->service_type,
            'service_name' => $request->service_name,
            'status' => 'submitted',
            'current_step' => $request->current_step ?? 'Application received',
            'expected_completion_at' => $request->expected_completion_at,
            'notes' => $request->notes,
            'metadata' => $request->metadata,
        ]);

        return response()->json([
            'message' => 'Service request submitted successfully.',
            'data' => $serviceRequest,
        ], 201);
    }
}
