<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('mail health check command dispatches test email', function () {
    Mail::shouldReceive('raw')->once();
    config()->set('mail.from.address', 'ops@example.com');

    Artisan::call('capture:mail:health-check');
});

test('secrets rotation command reports within policy window', function () {
    config([
        'capture.features.secret_rotation_interval_days' => 90,
        'capture.features.last_secret_rotation_at' => now()->subDays(10)->toIso8601String(),
    ]);

    Artisan::call('capture:secrets:rotation:check');

    expect(Artisan::output())->toContain('within policy window');
});
