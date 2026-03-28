<?php

return [
    'features' => [
        'default_plan' => env('CAPTURE_DEFAULT_PLAN', 'growth'),
        'plan_cache_ttl_seconds' => (int) env('CAPTURE_PLAN_CACHE_TTL_SECONDS', 300),
        'notes_force_enabled' => (bool) env('CAPTURE_NOTES_FORCE_ENABLED', false),
    ],
];
