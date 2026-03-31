<?php

use App\Models\AuditLog;
use App\Models\DataAccessLog;
use App\Support\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('audit logger redacts configured sensitive metadata keys', function () {
    config()->set('capture.features.admin_audit_log_enabled', true);
    config()->set('capture.features.audit_metadata_redact_keys', ['email', 'message']);

    $request = Request::create('/test', 'POST', [
        'access_reason' => 'compliance_review',
    ]);

    app(AuditLogger::class)->log(
        $request,
        'test.action',
        'enquiry',
        'target-1',
        'account-1',
        [
            'email' => 'person@example.com',
            'message' => 'sensitive freeform text',
            'safe' => 'ok',
            'nested' => [
                'email' => 'nested@example.com',
            ],
        ]
    );

    $record = AuditLog::query()->firstOrFail();

    expect(data_get($record->metadata, 'email'))->toBe('[redacted]');
    expect(data_get($record->metadata, 'message'))->toBe('[redacted]');
    expect(data_get($record->metadata, 'safe'))->toBe('ok');
    expect(data_get($record->metadata, 'nested.email'))->toBe('[redacted]');
});

test('data access logger also applies metadata redaction', function () {
    config()->set('capture.features.admin_audit_log_enabled', true);
    config()->set('capture.features.audit_metadata_redact_keys', ['content']);

    $request = Request::create('/test', 'POST', [
        'access_reason' => 'support_case',
    ]);

    app(AuditLogger::class)->logDataAccess(
        $request,
        'enquiry',
        'target-2',
        'account-2',
        [
            'content' => 'private note',
            'status' => 'viewed',
        ]
    );

    $record = DataAccessLog::query()->firstOrFail();

    expect(data_get($record->metadata, 'content'))->toBe('[redacted]');
    expect(data_get($record->metadata, 'status'))->toBe('viewed');
});
