<?php

use App\Jobs\RunDataRetentionCleanupJob;
use App\Services\StripeBillingService;
use App\Services\StripeCatalogSyncService;
use App\Services\DataRetentionService;
use App\Support\SecurityEventMetrics;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('capture:retention:cleanup', function (DataRetentionService $dataRetentionService) {
    $stats = $dataRetentionService->cleanup();

    $this->info('Data retention cleanup completed.');
    $this->table(
        ['Metric', 'Value'],
        [
            ['deleted_audit_logs', (string) ($stats['deleted_audit_logs'] ?? 0)],
            ['deleted_data_access_logs', (string) ($stats['deleted_data_access_logs'] ?? 0)],
            ['deleted_consents', (string) ($stats['deleted_consents'] ?? 0)],
            ['deleted_resolved_dsr', (string) ($stats['deleted_resolved_dsr'] ?? 0)],
            ['anonymized_enquiries', (string) ($stats['anonymized_enquiries'] ?? 0)],
        ]
    );
})->purpose('Apply retention policy cleanup and anonymization rules.');

Artisan::command('capture:retention:queue', function () {
    Bus::dispatch(new RunDataRetentionCleanupJob());

    $this->info('Queued data retention cleanup job.');
})->purpose('Queue retention cleanup job.');

Artisan::command('capture:security-metrics:snapshot {--date=}', function (SecurityEventMetrics $securityEventMetrics) {
    $dateOption = $this->option('date');
    $date = is_string($dateOption) && $dateOption !== '' ? Carbon::parse($dateOption) : now()->subDay();

    $result = $securityEventMetrics->persistVerificationBlockedSnapshot($date);

    $this->info('Security metrics snapshot persisted.');
    $this->line('Date: ' . $result['date']);
    $this->line('Rows written: ' . $result['rows_written']);
    $this->line('Total blocked events: ' . $result['total']);
})->purpose('Persist daily security telemetry snapshot to the database.');

Artisan::command('capture:mail:health-check {--to=}', function () {
    $recipient = (string) ($this->option('to') ?: config('mail.from.address'));

    if ($recipient === '') {
        $this->error('No recipient available for mail health check.');

        return 1;
    }

    Mail::raw('GraceSoft Capture mail health check', function ($message) use ($recipient): void {
        $message->to($recipient)->subject('GraceSoft Capture Mail Health Check');
    });

    $this->info('Mail health-check message dispatched to ' . $recipient);

    return 0;
})->purpose('Send a mail health-check message to verify SMTP/Postmark connectivity.');

Artisan::command('capture:secrets:rotation:check', function () {
    $intervalDays = max((int) config('capture.features.secret_rotation_interval_days', 90), 1);
    $lastRotationAt = (string) config('capture.features.last_secret_rotation_at', '');

    if ($lastRotationAt === '') {
        $this->warn('No last secret rotation timestamp configured (CAPTURE_LAST_SECRET_ROTATION_AT).');

        return 0;
    }

    $lastRotation = Carbon::parse($lastRotationAt);
    $ageDays = $lastRotation->diffInDays(now());

    if ($ageDays >= $intervalDays) {
        $message = sprintf('Secret rotation is overdue by %d days.', $ageDays - $intervalDays);
        Log::warning($message, [
            'last_rotation_at' => $lastRotation->toIso8601String(),
            'interval_days' => $intervalDays,
        ]);
        $this->warn($message);

        return 0;
    }

    $this->info('Secret rotation is within policy window.');

    return 0;
})->purpose('Check whether secret rotation is within configured policy interval.');

Artisan::command('capture:stripe:catalog:sync', function (StripeCatalogSyncService $stripeCatalogSyncService, StripeBillingService $stripeBillingService) {
    $result = $stripeCatalogSyncService->syncFromStripe($stripeBillingService);

    $this->info('Stripe catalog sync completed.');
    $this->table(
        ['Metric', 'Value'],
        [
            ['total_prices', (string) ($result['total'] ?? 0)],
            ['synced_plans', (string) ($result['synced'] ?? 0)],
            ['skipped_prices', (string) ($result['skipped'] ?? 0)],
        ]
    );

    return 0;
})->purpose('Sync Stripe recurring prices/products into local paid plan mappings.');

Schedule::command('capture:retention:cleanup')
    ->dailyAt('02:10')
    ->withoutOverlapping();

Schedule::command('capture:security-metrics:snapshot')
    ->dailyAt('00:20')
    ->withoutOverlapping();

Schedule::command('capture:secrets:rotation:check')
    ->weeklyOn(1, '03:15')
    ->withoutOverlapping();
