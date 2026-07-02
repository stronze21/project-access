<?php

return [
    'enabled' => (bool) env('FCM_ENABLED', false),
    'project_id' => env('FCM_PROJECT_ID'),
    'service_account_path' => env('FCM_SERVICE_ACCOUNT_PATH'),
    'service_account_json' => env('FCM_SERVICE_ACCOUNT_JSON'),
    'android_channel_id' => env('FCM_ANDROID_CHANNEL_ID', 'bosesmoto_updates'),
    'connect_timeout' => (float) env('FCM_CONNECT_TIMEOUT', 3),
    'timeout' => (float) env('FCM_TIMEOUT', 8),
];
