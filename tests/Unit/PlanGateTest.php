<?php

use App\Models\Account;
use App\Models\Plan;
use App\Support\PlanGate;
use App\Models\Form;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();

    config()->set('capture.features.notes_force_enabled', false);
    config()->set('capture.features.default_plan', 'growth');
    config()->set('capture.features.plan_cache_ttl_seconds', 300);
});

test('notes are enabled when local subscription plan is pro', function () {
    $account = Account::factory()->create();
    $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

    Subscription::factory()->create([
        'account_id' => $account->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $gate = app(PlanGate::class);

    expect($gate->notesEnabled($account->id))->toBeTrue();
});

test('notes are disabled when local subscription plan is starter', function () {
    $account = Account::factory()->create();
    $plan = Plan::factory()->create(['slug' => 'starter']);

    Subscription::factory()->create([
        'account_id' => $account->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $gate = app(PlanGate::class);

    expect($gate->notesEnabled($account->id))->toBeFalse();
});

test('falls back to default plan when no local subscription exists', function () {
    $account = Account::factory()->create();

    $gate = app(PlanGate::class);

    expect($gate->notesEnabled($account->id))->toBeFalse();
});

test('uses cached plan after first local resolution', function () {
    $account = Account::factory()->create();
    $plan = Plan::query()->where('slug', 'pro')->firstOrFail();

    Subscription::factory()->create([
        'account_id' => $account->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $gate = app(PlanGate::class);

    expect($gate->notesEnabled($account->id))->toBeTrue();
    expect($gate->notesEnabled($account->id))->toBeTrue();

    expect(Cache::has('capture:plan:' . $account->id))->toBeTrue();
});

test('force enabled notes bypasses plan lookup', function () {
    config()->set('capture.features.notes_force_enabled', true);

    $gate = app(PlanGate::class);

    expect($gate->notesEnabled('b5a8a06a-b355-4b80-a7dd-b87d67eb85f8'))->toBeTrue();
});

test('compliance views are enabled when plan gating is disabled', function () {
    config()->set('capture.features.admin_compliance_plan_gate_enabled', false);

    $gate = app(PlanGate::class);

    expect($gate->complianceViewsEnabled('b5a8a06a-b355-4b80-a7dd-b87d67eb85f8'))->toBeTrue();
});

test('compliance views are disabled for non allowed plans when gating is enabled', function () {
    config()->set('capture.features.admin_compliance_plan_gate_enabled', true);
    config()->set('capture.features.admin_compliance_allowed_plans', ['pro']);

    $account = Account::factory()->create();
    $plan = Plan::query()->where('slug', 'growth')->firstOrFail();

    Subscription::factory()->create([
        'account_id' => $account->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $gate = app(PlanGate::class);

    expect($gate->complianceViewsEnabled($account->id))->toBeFalse();
});

test('starter plan form creation is blocked when limit is reached', function () {
    config()->set('capture.features.plan_enforcement_enabled', true);
    config()->set('capture.features.starter_form_limit', 1);

    $account = Account::factory()->create(['id' => (string) Str::uuid()]);
    $plan = Plan::factory()->create(['slug' => 'starter']);

    Subscription::factory()->create([
        'account_id' => $account->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    Form::factory()->create([
        'account_id' => $account->id,
    ]);

    $gate = app(PlanGate::class);

    expect($gate->formCreationAllowed($account->id))->toBeFalse();
});

test('growth plan restricts collaborator owner invites', function () {
    config()->set('capture.features.plan_enforcement_enabled', true);

    $account = Account::factory()->create();
    $plan = Plan::query()->where('slug', 'growth')->firstOrFail();

    Subscription::factory()->create([
        'account_id' => $account->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $gate = app(PlanGate::class);

    expect($gate->collaboratorInviteRoleAllowed($account->id, 'owner'))->toBeFalse();
    expect($gate->collaboratorInviteRoleAllowed($account->id, 'member'))->toBeTrue();
});
