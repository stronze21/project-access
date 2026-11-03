<?php

return [
    // Enable only after the HID SDK and physical reader have been validated.
    'enabled' => env('BIOMETRICS_ENABLED', false),
    'session_seconds' => 180,
    'service_url' => 'http://127.0.0.1:17645',
    'template_format' => 'DPUruNet-ANSI-FMD-XML-v1',
    'max_candidates' => 10000,
];
