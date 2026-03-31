<?php

use App\Support\PlanGate;
use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

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

test('compliance views are enabled when plan gating is disabled', function () {
    config()->set('capture.features.admin_compliance_plan_gate_enabled', false);

    $gate = app(PlanGate::class);

    expect($gate->complianceViewsEnabled('b5a8a06a-b355-4b80-a7dd-b87d67eb85f8'))->toBeTrue();
});

test('compliance views are disabled for non allowed plans when gating is enabled', function () {
    config()->set('capture.features.admin_compliance_plan_gate_enabled', true);
    config()->set('capture.features.admin_compliance_allowed_plans', ['pro']);

    Http::fake([
        'http://hq.test/api/v1/subscription*' => Http::response([
            'plan' => 'growth',
        ], 200),
    ]);

    $gate = app(PlanGate::class);

    expect($gate->complianceViewsEnabled('b5a8a06a-b355-4b80-a7dd-b87d67eb85f8'))->toBeFalse();
});

test('starter plan form creation is blocked when limit is reached', function () {
    config()->set('capture.features.plan_enforcement_enabled', true);
    config()->set('capture.features.starter_form_limit', 1);

    Http::fake([
        'http://hq.test/api/v1/subscription*' => Http::response([
            'plan' => 'starter',
        ], 200),
    ]);

    Form::factory()->create([
        'account_id' => '919f860f-6ba0-46e0-bd0b-b7ef9d09af89',
    ]);

    $gate = app(PlanGate::class);

    expect($gate->formCreationAllowed('919f860f-6ba0-46e0-bd0b-b7ef9d09af89'))->toBeFalse();
});

test('growth plan restricts collaborator owner invites', function () {
    config()->set('capture.features.plan_enforcement_enabled', true);

    Http::fake([
        'http://hq.test/api/v1/subscription*' => Http::response([
            'plan' => 'growth',
        ], 200),
    ]);

    $gate = app(PlanGate::class);

    expect($gate->collaboratorInviteRoleAllowed('dabfd07b-a784-4ff7-b48f-a729f2caeffc', 'owner'))->toBeFalse();
    expect($gate->collaboratorInviteRoleAllowed('dabfd07b-a784-4ff7-b48f-a729f2caeffc', 'member'))->toBeTrue();
});
