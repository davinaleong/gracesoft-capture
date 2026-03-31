<?php

use App\Services\HQService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('hq.enabled', true);
    config()->set('hq.credentials.app_id', 'app-id-test');
    config()->set('hq.credentials.app_key', 'app-key-test');
    config()->set('hq.credentials.app_secret', 'app-secret-test');
    config()->set('hq.http.timeout_seconds', 2);
    config()->set('hq.http.retry_times', 1);
    config()->set('hq.http.retry_sleep_milliseconds', 1);
    config()->set('hq.sync.analytics_url', 'http://hq.test/api/v1/analytics');
    config()->set('hq.sync.feedback_url', 'http://hq.test/api/v1/feedback');
    config()->set('hq.sync.create_application_url', 'http://hq.test/api/v1/applications');
    config()->set('hq.validation.enabled', false);
    config()->set('hq.validation.url', 'http://hq.test/api/v1/validate-application');
    config()->set('hq.validation.cache_ttl_seconds', 120);
    Cache::flush();
});

test('sends analytics payload to hq successfully', function () {
    Http::fake([
        'http://hq.test/api/v1/analytics' => Http::response(['ok' => true], 200),
    ]);

    $service = app(HQService::class);

    $ok = $service->sendAnalyticsEvent([
        'event' => 'enquiry.created',
        'account_id' => 'a8f87ef1-c7b8-4f4d-bd7a-98892689fef1',
    ]);

    expect($ok)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'http://hq.test/api/v1/analytics'
            && $request->hasHeader('X-App-Id', 'app-id-test')
            && $request->hasHeader('X-App-Key', 'app-key-test')
            && $request->hasHeader('X-App-Secret', 'app-secret-test')
            && data_get($request->data(), 'event') === 'enquiry.created';
    });
});

test('returns false when analytics sync endpoint responds with failure', function () {
    Http::fake([
        'http://hq.test/api/v1/analytics' => Http::response(['error' => 'bad request'], 422),
    ]);

    $service = app(HQService::class);

    expect($service->sendAnalyticsEvent(['event' => 'enquiry.created']))->toBeFalse();
});

test('sends feedback payload to hq successfully', function () {
    Http::fake([
        'http://hq.test/api/v1/feedback' => Http::response(['ok' => true], 200),
    ]);

    $service = app(HQService::class);

    $ok = $service->sendFeedback([
        'account_id' => 'a8f87ef1-c7b8-4f4d-bd7a-98892689fef1',
        'message' => 'Need support.',
    ]);

    expect($ok)->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'http://hq.test/api/v1/feedback'
            && data_get($request->data(), 'message') === 'Need support.';
    });
});

test('returns false when hq integration is disabled', function () {
    config()->set('hq.enabled', false);

    Http::fake();

    $service = app(HQService::class);

    expect($service->sendAnalyticsEvent(['event' => 'enquiry.created']))->toBeFalse();
    expect($service->sendFeedback(['message' => 'hello']))->toBeFalse();

    Http::assertNothingSent();
});

test('validates application via hq and caches successful result', function () {
    config()->set('hq.validation.enabled', true);

    Http::fake([
        'http://hq.test/api/v1/validate-application' => Http::response([
            'valid' => true,
        ], 200),
    ]);

    $service = app(HQService::class);

    expect($service->validateApplication('acct-1', 'app-1'))->toBeTrue();
    expect($service->validateApplication('acct-1', 'app-1'))->toBeTrue();

    Http::assertSentCount(1);
});

test('returns false when hq application validation fails', function () {
    config()->set('hq.validation.enabled', true);

    Http::fake([
        'http://hq.test/api/v1/validate-application' => Http::response([
            'valid' => false,
        ], 200),
    ]);

    $service = app(HQService::class);

    expect($service->validateApplication('acct-1', 'app-1'))->toBeFalse();
});

test('skips validation call when feature is disabled', function () {
    config()->set('hq.validation.enabled', false);

    Http::fake();

    $service = app(HQService::class);

    expect($service->validateApplication('acct-1', 'app-1'))->toBeTrue();

    Http::assertNothingSent();
});

test('creates application via hq and returns application id', function () {
    Http::fake([
        'http://hq.test/api/v1/applications' => Http::response([
            'application_id' => 'f3d18d9b-a126-41b9-a877-994808ddf31e',
        ], 200),
    ]);

    $service = app(HQService::class);

    expect($service->createApplication('acct-1', 'Support Form'))
        ->toBe('f3d18d9b-a126-41b9-a877-994808ddf31e');
});

test('returns null when create application endpoint is not configured', function () {
    config()->set('hq.sync.create_application_url', null);
    Http::fake();

    $service = app(HQService::class);

    expect($service->createApplication('acct-1', 'Support Form'))->toBeNull();
    Http::assertNothingSent();
});
