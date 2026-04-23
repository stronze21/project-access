<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CitizenServiceRequest;
use App\Models\GrievanceReport;
use App\Models\SosAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CitizenServicesAdminController extends Controller
{
    public function serviceRequests(Request $request): JsonResponse
    {
        $query = CitizenServiceRequest::with('resident:id,resident_id,first_name,last_name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('service_type')) {
            $query->where('service_type', $request->service_type);
        }

        return response()->json($query->latest('status_updated_at')->paginate(20));
    }

    public function updateServiceRequest(Request $request, int $id): JsonResponse
    {
        $serviceRequest = CitizenServiceRequest::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|max:50',
            'current_step' => 'nullable|string|max:150',
            'expected_completion_at' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'notes' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $payload = $request->only([
            'status',
            'current_step',
            'expected_completion_at',
            'completed_at',
            'notes',
            'metadata',
        ]);
        $payload['status_updated_at'] = now();

        $serviceRequest->update($payload);

        return response()->json(['data' => $serviceRequest->fresh()]);
    }

    public function grievances(Request $request): JsonResponse
    {
        $query = GrievanceReport::with('resident:id,resident_id,first_name,last_name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function updateGrievance(Request $request, int $id): JsonResponse
    {
        $grievance = GrievanceReport::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|max:50',
            'admin_response' => 'nullable|string',
            'resolved_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $grievance->update($request->only(['status', 'admin_response', 'resolved_at']));

        return response()->json(['data' => $grievance->fresh()]);
    }

    public function sosAlerts(Request $request): JsonResponse
    {
        $query = SosAlert::with('resident:id,resident_id,first_name,last_name');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function updateSosAlert(Request $request, int $id): JsonResponse
    {
        $sosAlert = SosAlert::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|max:50',
            'acknowledged_at' => 'nullable|date',
            'resolved_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $sosAlert->update($request->only(['status', 'acknowledged_at', 'resolved_at']));

        return response()->json(['data' => $sosAlert->fresh()]);
    }
}
