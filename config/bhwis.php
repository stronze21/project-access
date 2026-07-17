<?php

return [
    'consent_versions' => [
        'terms' => env('TERMS_VERSION', '2026-07-16'),
        'privacy' => env('PRIVACY_NOTICE_VERSION', '2026-07-16'),
        'bhwis' => env('BHWIS_CONSENT_VERSION', '2026-07-16'),
    ],
    'activation_rate_limit' => (int) env('BHWIS_ACTIVATION_RATE_LIMIT', 5),
    'activation_decay_seconds' => (int) env('BHWIS_ACTIVATION_DECAY_SECONDS', 60),
];
