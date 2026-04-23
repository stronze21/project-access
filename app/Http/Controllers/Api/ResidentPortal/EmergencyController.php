<?php

namespace App\Http\Controllers\Api\ResidentPortal;

use App\Http\Controllers\Controller;
use App\Models\EmergencyAlert;
use App\Models\SosAlert;
use App\Models\SystemSetting;
use App\Services\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EmergencyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $alerts = EmergencyAlert::active()
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'data' => $alerts->items(),
            'meta' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
            ],
            'command_center' => $this->commandCenterConfig(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json([
            'data' => EmergencyAlert::active()->findOrFail($id),
            'command_center' => $this->commandCenterConfig(),
        ]);
    }

    public function sos(Request $request, PushNotificationService $pushNotificationService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'nullable|string|max:1000',
            'contact_number' => 'nullable|string|max:30',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_label' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $resident = $request->user();

        $sosAlert = SosAlert::create([
            'resident_id' => $resident->id,
            'status' => 'open',
            'contact_number' => $request->contact_number ?? $resident->contact_number,
            'message' => $request->message,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'location_label' => $request->location_label,
        ]);

        $pushNotificationService->createInAppNotificationForResident(
            $resident->id,
            'SOS received',
            'Your emergency request has been forwarded to the city command center.',
            'sos',
            ['sos_alert_id' => (string) $sosAlert->id]
        );

        return response()->json([
            'message' => 'SOS alert sent successfully.',
            'data' => $sosAlert,
            'command_center' => $this->commandCenterConfig(),
        ], 201);
    }

    public function sosHistory(Request $request): JsonResponse
    {
        $alerts = SosAlert::where('resident_id', $request->user()->id)
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'data' => $alerts->items(),
            'meta' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
            ],
        ]);
    }

    private function commandCenterConfig(): array
    {
        return [
            'name' => SystemSetting::get('command_center_name', 'Local Command Center'),
            'hotline' => SystemSetting::get('command_center_hotline', '911'),
            'alternate_hotline' => SystemSetting::get('command_center_alternate_hotline'),
            'email' => SystemSetting::get('command_center_email'),
        ];
    }
}
