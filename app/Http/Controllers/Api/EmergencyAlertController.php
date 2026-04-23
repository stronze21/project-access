<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmergencyAlert;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmergencyAlertController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => EmergencyAlert::latest()->paginate(20),
        ]);
    }

    public function store(Request $request, PushNotificationService $pushNotificationService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'severity' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'alert_type' => 'nullable|string|max:100',
            'send_push_notification' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $alert = EmergencyAlert::create([
            'created_by' => $request->user()->id,
            'title' => $request->title,
            'message' => $request->message,
            'severity' => $request->severity ?? 'medium',
            'status' => $request->status ?? 'active',
            'alert_type' => $request->alert_type ?? 'general',
            'send_push_notification' => $request->boolean('send_push_notification'),
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'metadata' => $request->metadata,
        ]);

        if ($alert->send_push_notification && $alert->status === 'active') {
            $pushNotificationService->broadcastResidentNotification(
                $alert->title,
                $alert->message,
                'emergency',
                [
                    'emergency_alert_id' => (string) $alert->id,
                    'severity' => (string) $alert->severity,
                    'alert_type' => (string) $alert->alert_type,
                ]
            );
        }

        return response()->json(['data' => $alert], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $alert = EmergencyAlert::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'message' => 'sometimes|required|string',
            'severity' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'alert_type' => 'nullable|string|max:100',
            'send_push_notification' => 'nullable|boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $alert->update($request->only([
            'title',
            'message',
            'severity',
            'status',
            'alert_type',
            'send_push_notification',
            'starts_at',
            'ends_at',
            'metadata',
        ]));

        return response()->json(['data' => $alert->fresh()]);
    }
}
