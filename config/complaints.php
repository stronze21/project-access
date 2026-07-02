<?php

return [
    'submission_limits' => [
        'citizen_daily' => 5,
        // Anonymous limits should be enforced per browser/device fingerprint.
        // Keep the old key for backward compatibility with existing deployments.
        'anonymous_device_daily' => 5,
        'anonymous_ip_daily' => 2,
    ],

    'workflow' => [
        'statuses' => [
            'received',
            'assigned',
            'in_progress',
            'resolved',
            'closed',
        ],
        'moderation_statuses' => [
            'normal',
            'spam',
            'abusive',
            'invalid',
        ],
        'priorities' => [
            'low',
            'medium',
            'high',
            'urgent',
        ],
        'visibility' => [
            'public_named',
            'public_anonymous',
            'private',
        ],
    ],

    'sla' => [
        'acknowledge_days' => 1,
        'first_action_days' => 3,
        'resolution_days' => 7,
        'auto_close_days' => 7,
    ],

    'similarity' => [
        'max_results' => 5,
    ],

    'attachments' => [
        'max_files_per_complaint' => 5,
        'max_size_kb' => 20480,
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'video/mp4',
            'video/quicktime',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
    ],

    'virus_scan' => [
        'command' => env('CLAMAV_SCAN_COMMAND'),
    ],
];
