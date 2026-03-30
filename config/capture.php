<?php

return [
    'features' => [
        'default_plan' => env('CAPTURE_DEFAULT_PLAN', 'growth'),
        'plan_cache_ttl_seconds' => (int) env('CAPTURE_PLAN_CACHE_TTL_SECONDS', 300),
        'notes_force_enabled' => (bool) env('CAPTURE_NOTES_FORCE_ENABLED', false),
        'admin_audit_log_enabled' => (bool) env('ADMIN_AUDIT_LOG_ENABLED', true),
        'enforce_access_context' => (bool) env('CAPTURE_ENFORCE_ACCESS_CONTEXT', false),
    ],
];
