<?php

use App\Jobs\RunDataRetentionCleanupJob;
use App\Services\DataRetentionService;
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

Schedule::command('capture:retention:cleanup')
    ->dailyAt('02:10')
    ->withoutOverlapping();
