<?php

use App\Services\HQService;
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
