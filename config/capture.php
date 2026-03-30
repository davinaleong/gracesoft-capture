<?php

return [
    'features' => [
        'default_plan' => env('CAPTURE_DEFAULT_PLAN', 'growth'),
        'plan_cache_ttl_seconds' => (int) env('CAPTURE_PLAN_CACHE_TTL_SECONDS', 300),
        'notes_force_enabled' => (bool) env('CAPTURE_NOTES_FORCE_ENABLED', false),
        'admin_audit_log_enabled' => (bool) env('ADMIN_AUDIT_LOG_ENABLED', true),
        'enforce_access_context' => (bool) env('CAPTURE_ENFORCE_ACCESS_CONTEXT', false),
        'require_form_consent' => (bool) env('CAPTURE_REQUIRE_FORM_CONSENT', false),
        'consent_policy_version' => env('CAPTURE_CONSENT_POLICY_VERSION', 'v1'),
        'data_retention_days' => (int) env('APP_DATA_RETENTION_DAYS', 365),
        'admin_compliance_plan_gate_enabled' => (bool) env('CAPTURE_ADMIN_COMPLIANCE_PLAN_GATE_ENABLED', false),
        'admin_compliance_allowed_plans' => ['pro'],
        'require_admin_mfa_for_compliance' => (bool) env('CAPTURE_REQUIRE_ADMIN_MFA_FOR_COMPLIANCE', false),
    ],
    'admin_authorization' => [
        'default_role' => env('CAPTURE_DEFAULT_ADMIN_ROLE', 'compliance_admin'),
        'role_capabilities' => [
            'compliance_reader' => [
                'compliance.view',
            ],
            'compliance_operator' => [
                'compliance.view',
                'compliance.manage_dsr_status',
            ],
            'compliance_admin' => [
                'compliance.view',
                'compliance.manage_dsr_status',
                'compliance.process_dsr',
            ],
        ],
    ],
];
