<?php

use App\Support\PlanGate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();

    config()->set('capture.features.notes_force_enabled', false);
    config()->set('capture.features.default_plan', 'growth');
    config()->set('capture.features.plan_cache_ttl_seconds', 300);
    config()->set('hq.enabled', true);
    config()->set('hq.sync.subscription_url', 'http://hq.test/api/v1/subscription');
    config()->set('hq.http.retry_times', 1);
    config()->set('hq.http.retry_sleep_milliseconds', 1);
    config()->set('hq.http.timeout_seconds', 2);
});

test('notes are enabled when HQ returns pro plan', function () {
    Http::fake([
        'http://hq.test/api/v1/subscription*' => Http::response([
            'data' => ['plan' => 'pro'],
        ], 200),
    ]);

    $gate = app(PlanGate::class);

    expect($gate->notesEnabled('b5a8a06a-b355-4b80-a7dd-b87d67eb85f8'))->toBeTrue();
});

test('notes are disabled when HQ returns non pro plan', function () {
    Http::fake([
        'http://hq.test/api/v1/subscription*' => Http::response([
            'plan' => 'starter',
        ], 200),
    ]);

    $gate = app(PlanGate::class);

    expect($gate->notesEnabled('b5a8a06a-b355-4b80-a7dd-b87d67eb85f8'))->toBeFalse();
});

test('falls back to default plan when HQ request fails', function () {
    Http::fake([
        'http://hq.test/api/v1/subscription*' => Http::response([], 500),
    ]);

    $gate = app(PlanGate::class);

    expect($gate->notesEnabled('b5a8a06a-b355-4b80-a7dd-b87d67eb85f8'))->toBeFalse();
});

test('uses cached plan after first HQ call', function () {
    Http::fake([
        'http://hq.test/api/v1/subscription*' => Http::response([
            'subscription' => ['plan' => 'pro'],
        ], 200),
    ]);

    $gate = app(PlanGate::class);

    expect($gate->notesEnabled('b5a8a06a-b355-4b80-a7dd-b87d67eb85f8'))->toBeTrue();
    expect($gate->notesEnabled('b5a8a06a-b355-4b80-a7dd-b87d67eb85f8'))->toBeTrue();

    Http::assertSentCount(1);
});

test('force enabled notes bypasses HQ', function () {
    config()->set('capture.features.notes_force_enabled', true);

    Http::fake();

    $gate = app(PlanGate::class);

    expect($gate->notesEnabled('b5a8a06a-b355-4b80-a7dd-b87d67eb85f8'))->toBeTrue();

    Http::assertNothingSent();
});
