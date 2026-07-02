<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FcmPushService
{
    public const TOPIC_PUBLIC_COMPLAINTS = 'public_complaints';
    public const TOPIC_POLLS = 'polls';
    public const TOPIC_SENTIMENTS = 'sentiments';

    private const TOKEN_AUDIENCE = 'https://oauth2.googleapis.com/token';
    private const TOKEN_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    public function __construct(private CacheRepository $cache)
    {
    }

    public function sendTopicNotification(string $topic, string $title, string $body, array $data = []): bool
    {
        if (!config('fcm.enabled')) {
            return false;
        }

        $serviceAccount = $this->serviceAccount();
        if ($serviceAccount === null) {
            return false;
        }

        $projectId = $this->projectId($serviceAccount);
        if ($projectId === null) {
            return false;
        }

        $accessToken = $this->accessToken($serviceAccount);
        if ($accessToken === null) {
            return false;
        }

        $payload = [
            'message' => [
                'topic' => $topic,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $this->normalizeData($data),
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'channel_id' => (string) config('fcm.android_channel_id', 'bosesmoto_updates'),
                        'sound' => 'default',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::connectTimeout((float) config('fcm.connect_timeout', 3))
                ->timeout((float) config('fcm.timeout', 8))
                ->withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

            if ($response->successful()) {
                return true;
            }

            Log::warning('FCM topic push failed.', [
                'topic' => $topic,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('FCM topic push exception.', [
                'topic' => $topic,
                'error' => $exception->getMessage(),
            ]);
        }

        return false;
    }

    private function normalizeData(array $data): array
    {
        $normalized = [];

        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            $normalized[(string) $key] = is_scalar($value)
                ? (string) $value
                : (json_encode($value) ?: '');
        }

        return $normalized;
    }

    private function accessToken(array $serviceAccount): ?string
    {
        $cacheKey = 'fcm.oauth.'.sha1((string) ($serviceAccount['client_email'] ?? 'default'));
        $cached = $this->cache->get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $assertion = $this->jwtAssertion($serviceAccount);
        if ($assertion === null) {
            return null;
        }

        try {
            $response = Http::asForm()
                ->connectTimeout((float) config('fcm.connect_timeout', 3))
                ->timeout((float) config('fcm.timeout', 8))
                ->post(self::TOKEN_AUDIENCE, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $assertion,
                ]);

            if (!$response->successful()) {
                Log::warning('FCM OAuth token request failed.', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);

                return null;
            }

            $token = (string) $response->json('access_token');
            $expiresIn = max(60, ((int) $response->json('expires_in', 3600)) - 120);

            if ($token === '') {
                return null;
            }

            $this->cache->put($cacheKey, $token, now()->addSeconds($expiresIn));

            return $token;
        } catch (Throwable $exception) {
            Log::warning('FCM OAuth token exception.', [
                'error' => $exception->getMessage(),
            ]);
        }

        return null;
    }

    private function jwtAssertion(array $serviceAccount): ?string
    {
        $clientEmail = (string) ($serviceAccount['client_email'] ?? '');
        $privateKey = (string) ($serviceAccount['private_key'] ?? '');

        if ($clientEmail === '' || $privateKey === '') {
            return null;
        }

        $issuedAt = time();
        $expiresAt = $issuedAt + 3600;

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]) ?: '');

        $claims = $this->base64UrlEncode(json_encode([
            'iss' => $clientEmail,
            'scope' => self::TOKEN_SCOPE,
            'aud' => self::TOKEN_AUDIENCE,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
        ]) ?: '');

        if ($header === '' || $claims === '') {
            return null;
        }

        $unsignedToken = $header.'.'.$claims;
        $signature = '';
        $signed = openssl_sign($unsignedToken, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (!$signed) {
            return null;
        }

        return $unsignedToken.'.'.$this->base64UrlEncode($signature);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function projectId(array $serviceAccount): ?string
    {
        $projectId = trim((string) config('fcm.project_id', ''));
        if ($projectId !== '') {
            return $projectId;
        }

        $fromAccount = trim((string) ($serviceAccount['project_id'] ?? ''));

        return $fromAccount !== '' ? $fromAccount : null;
    }

    private function serviceAccount(): ?array
    {
        $rawJson = trim((string) config('fcm.service_account_json', ''));
        if ($rawJson !== '') {
            $decoded = json_decode($rawJson, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $path = trim((string) config('fcm.service_account_path', ''));
        if ($path === '') {
            return null;
        }

        $resolvedPath = str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:[\/\\\\]/', $path) === 1
            ? $path
            : base_path($path);

        if (!is_file($resolvedPath)) {
            return null;
        }

        $contents = file_get_contents($resolvedPath);
        if ($contents === false) {
            return null;
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : null;
    }
}
