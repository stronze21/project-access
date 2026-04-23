<?php

namespace App\Services;

use App\Models\AyudaProgram;
use App\Models\Resident;
use App\Models\ResidentDeviceToken;
use App\Models\ResidentNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    /**
     * Send push notification to a specific resident via all their registered devices.
     */
    public function sendToResident(int $residentId, string $title, string $body, array $data = []): int
    {
        $tokens = ResidentDeviceToken::where('resident_id', $residentId)
            ->where('is_active', true)
            ->pluck('device_token')
            ->toArray();

        if (empty($tokens)) {
            return 0;
        }

        $sent = 0;
        foreach ($tokens as $token) {
            if ($this->sendFcmNotification($token, $title, $body, $data)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * Send push notification to all registered devices (broadcast).
     */
    public function sendToAll(string $title, string $body, array $data = []): array
    {
        $tokens = ResidentDeviceToken::where('is_active', true)
            ->pluck('device_token')
            ->toArray();

        if (empty($tokens)) {
            return ['sent' => 0, 'failed' => 0, 'total' => 0];
        }

        $sent = 0;
        $failed = 0;

        foreach ($tokens as $token) {
            if ($this->sendFcmNotification($token, $title, $body, $data)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'total' => count($tokens)];
    }

    /**
     * Create an in-app notification for a resident and optionally send push.
     */
    public function createInAppNotificationForResident(int $residentId, string $title, string $body, string $type = 'general', array $data = [], bool $sendPush = false): ResidentNotification
    {
        $notification = ResidentNotification::create([
            'resident_id' => $residentId,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
        ]);

        if ($sendPush) {
            $this->sendToResident($residentId, $title, $body, array_merge($data, ['type' => $type]));
        }

        return $notification;
    }

    /**
     * Broadcast a generic resident notification to all active residents.
     */
    public function broadcastResidentNotification(string $title, string $body, string $type = 'general', array $data = []): array
    {
        $residentIds = Resident::where('is_active', true)->pluck('id')->toArray();

        if (!empty($residentIds)) {
            $notifications = [];
            foreach ($residentIds as $residentId) {
                $notifications[] = [
                    'resident_id' => $residentId,
                    'title' => $title,
                    'body' => $body,
                    'type' => $type,
                    'data' => json_encode($data),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            ResidentNotification::insert($notifications);
        }

        return $this->sendToAll($title, $body, array_merge($data, ['type' => $type]));
    }

    /**
     * Send push notification and also store an in-app notification record for each resident.
     */
    public function broadcastAnnouncementNotification(int $announcementId, string $title, string $body, string $type = 'announcement', string $recipientType = 'all', ?int $programId = null): array
    {
        $data = [
            'type' => $type,
            'announcement_id' => (string) $announcementId,
        ];

        // Get ALL target resident IDs for in-app notifications (regardless of device token)
        $allResidentIds = $this->getNotificationResidentIds($recipientType, $programId);

        // Create in-app notification records for all target residents
        $notifications = [];
        foreach ($allResidentIds as $residentId) {
            $notifications[] = [
                'resident_id' => $residentId,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'data' => json_encode($data),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($notifications)) {
            ResidentNotification::insert($notifications);
        }

        // Send FCM push notifications only to residents with active device tokens
        $tokens = ResidentDeviceToken::where('is_active', true)
            ->whereIn('resident_id', $allResidentIds)
            ->pluck('device_token')
            ->toArray();

        if (empty($tokens)) {
            return ['sent' => 0, 'failed' => 0, 'total' => count($allResidentIds)];
        }

        $sent = 0;
        $failed = 0;

        foreach ($tokens as $token) {
            if ($this->sendFcmNotification($token, $title, $body, $data)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'total' => count($allResidentIds)];
    }

    /**
     * Get all active resident IDs that should receive in-app notifications.
     */
    private function getNotificationResidentIds(string $recipientType, ?int $programId): array
    {
        if ($recipientType === 'program_beneficiaries' && $programId) {
            $program = AyudaProgram::with('eligibilityCriteria')->find($programId);

            if (!$program) {
                return [];
            }

            $eligibleIds = [];
            $residents = Resident::with('household')
                ->where('is_active', true)
                ->get();

            foreach ($residents as $resident) {
                if ($resident->isEligibleFor($program)) {
                    $eligibleIds[] = $resident->id;
                }
            }

            return $eligibleIds;
        }

        // Default: all active residents
        return Resident::where('is_active', true)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Send an FCM notification to a single device token.
     *
     * Uses FCM HTTP v1 API with a service account key, or the legacy API
     * depending on configuration.
     */
    private function sendFcmNotification(string $token, string $title, string $body, array $data = []): bool
    {
        $serverKey = config('services.fcm.server_key');

        if (empty($serverKey)) {
            Log::warning('FCM server key not configured. Skipping push notification.');
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type' => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', [
                'to' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                    'click_action' => 'OPEN_ANNOUNCEMENTS',
                ],
                'data' => array_merge($data, [
                    'title' => $title,
                    'body' => $body,
                ]),
                'priority' => 'high',
            ]);

            if ($response->successful()) {
                $result = $response->json();

                // Check if FCM reported the token as invalid
                if (isset($result['failure']) && $result['failure'] > 0) {
                    $this->handleFailedToken($token, $result);
                    return false;
                }

                return true;
            }

            Log::error('FCM request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('FCM notification error', [
                'message' => $e->getMessage(),
                'token' => substr($token, 0, 20) . '...',
            ]);

            return false;
        }
    }

    /**
     * Handle failed/invalid device tokens by deactivating them.
     */
    private function handleFailedToken(string $token, array $result): void
    {
        if (!isset($result['results'][0])) {
            return;
        }

        $error = $result['results'][0]['error'] ?? null;
        $invalidErrors = ['NotRegistered', 'InvalidRegistration', 'MismatchSenderId'];

        if (in_array($error, $invalidErrors)) {
            ResidentDeviceToken::where('device_token', $token)
                ->update(['is_active' => false]);

            Log::info('Deactivated invalid FCM token', [
                'error' => $error,
                'token' => substr($token, 0, 20) . '...',
            ]);
        }
    }
}
