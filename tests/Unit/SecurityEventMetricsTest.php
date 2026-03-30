<?php

use App\Models\SecurityEventSnapshot;
use App\Support\SecurityEventMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('verification blocked summary returns incremented counters', function () {
    Carbon::setTestNow('2026-03-31 09:00:00');

    $metrics = app(SecurityEventMetrics::class);
    $metrics->incrementVerificationBlocked('web', 'collaborator_acceptance');
    $metrics->incrementVerificationBlocked('web', 'collaborator_acceptance');
    $metrics->incrementVerificationBlocked('admin', 'sensitive_admin_operation');

    $summary = $metrics->verificationBlockedSummary();

    expect($summary['total'])->toBe(3);
    expect(data_get($summary, 'breakdown.web:collaborator_acceptance'))->toBe(2);
    expect(data_get($summary, 'breakdown.admin:sensitive_admin_operation'))->toBe(1);

    Carbon::setTestNow();
});

test('persist verification blocked snapshot stores rows for date', function () {
    Carbon::setTestNow('2026-03-31 10:00:00');

    $metrics = app(SecurityEventMetrics::class);
    $metrics->incrementVerificationBlocked('web', 'collaborator_acceptance');
    $metrics->incrementVerificationBlocked('admin', 'sensitive_admin_operation');

    $result = $metrics->persistVerificationBlockedSnapshot();

    expect($result['date'])->toBe('2026-03-31');
    expect($result['rows_written'])->toBe(2);
    expect($result['total'])->toBe(2);

    expect(SecurityEventSnapshot::query()->whereDate('snapshot_date', '2026-03-31')->count())->toBe(2);
    expect(SecurityEventSnapshot::query()
        ->where('metric_key', 'verification_blocked:web:collaborator_acceptance')
        ->value('metric_value'))->toBe(1);

    Carbon::setTestNow();
});
