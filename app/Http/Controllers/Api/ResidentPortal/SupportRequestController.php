<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\SupportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $requests = SupportRequest::where('resident_id', $request->user()->id)
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
            'category' => 'nullable|in:account,privacy,technical,service-request,emergency,other',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:4000',
            'platform' => 'nullable|string|max:100',
            'device_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resident = $request->user();

        $supportRequest = SupportRequest::create([
            'resident_id' => $resident?->id,
            'resident_identifier' => $resident?->resident_id,
            'resident_name' => $resident?->full_name,
            'email' => $resident?->email,
            'contact_number' => $resident?->contact_number,
            'category' => $request->input('category', 'general'),
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'source' => 'mobile-api',
            'platform' => $request->input('platform'),
            'device_name' => $request->input('device_name'),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'Support request received.',
            'data' => [
                'id' => $supportRequest->id,
                'reference_number' => $supportRequest->reference_number,
                'status' => $supportRequest->status,
                'submitted_at' => $supportRequest->submitted_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $supportRequest = SupportRequest::where('resident_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json(['data' => $supportRequest]);
    }
}
