<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AccountDeletionRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requests = AccountDeletionRequest::where('resident_id', $request->user()->id)
            ->latest('submitted_at')
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'data' => $requests->items(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:2000',
            'requested_action' => 'nullable|in:delete-account-and-data,delete-app-data-only',
            'resident_id' => 'nullable|string|max:100',
            'resident_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'contact_number' => 'nullable|string|max:30',
            'platform' => 'nullable|string|max:100',
            'device_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resident = $request->user();

        $deletionRequest = AccountDeletionRequest::create([
            'resident_id' => $resident?->id,
            'resident_identifier' => $request->input('resident_id', $resident?->resident_id),
            'resident_name' => $request->input('resident_name', $resident?->full_name),
            'email' => $request->input('email', $resident?->email),
            'contact_number' => $request->input('contact_number', $resident?->contact_number),
            'reason' => $request->input('reason'),
            'requested_action' => $request->input('requested_action', 'delete-account-and-data'),
            'retention_acknowledged' => true,
            'source' => 'mobile-api',
            'platform' => $request->input('platform'),
            'device_name' => $request->input('device_name'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Account deletion request received.',
            'data' => [
                'id' => $deletionRequest->id,
                'reference_number' => $deletionRequest->reference_number,
                'status' => $deletionRequest->status,
                'submitted_at' => $deletionRequest->submitted_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $deletionRequest = AccountDeletionRequest::where('resident_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json(['data' => $deletionRequest]);
    }
}
