<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\ResidentDeviceToken;
use App\Models\ResidentNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    /**
     * List the authenticated resident's notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $resident = $request->user();

        $query = ResidentNotification::where('resident_id', $resident->id);

        if ($request->has('unread_only') && $request->unread_only) {
            $query->unread();
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
            'unread_count' => ResidentNotification::where('resident_id', $resident->id)->unread()->count(),
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = ResidentNotification::where('resident_id', $request->user()->id)
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        ResidentNotification::where('resident_id', $request->user()->id)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    /**
     * Get unread notification count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = ResidentNotification::where('resident_id', $request->user()->id)
            ->unread()
            ->count();

        return response()->json(['unread_count' => $count]);
    }

    /**
     * Register a device token for push notifications.
     */
    public function registerDevice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string|max:500',
            'platform' => 'required|string|in:ios,android,web',
            'device_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resident = $request->user();

        $deviceToken = ResidentDeviceToken::updateOrCreate(
            [
                'resident_id' => $resident->id,
                'device_token' => $request->device_token,
            ],
            [
                'platform' => $request->platform,
                'device_name' => $request->device_name,
                'is_active' => true,
            ]
        );

        return response()->json([
            'message' => 'Device registered successfully',
            'data' => $deviceToken,
        ]);
    }

    /**
     * Unregister a device token.
     */
    public function unregisterDevice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        ResidentDeviceToken::where('resident_id', $request->user()->id)
            ->where('device_token', $request->device_token)
            ->delete();

        return response()->json(['message' => 'Device unregistered successfully']);
    }
}
