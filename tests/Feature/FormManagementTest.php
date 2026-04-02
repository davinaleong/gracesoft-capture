<?php

use App\Models\Account;
use App\Models\AccountMembership;
use App\Models\Form;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\HQService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

test('forms index displays created forms', function () {
    Form::factory()->create(['name' => 'Website Contact']);

    $this->get(route('manage.forms.index'))
        ->assertOk()
        ->assertSee('Website Contact');
});

test('setup sidebar advances to integration after first form exists for active account', function () {
    config()->set('capture.features.enforce_access_context', true);

    $user = User::factory()->create();
    $this->actingAs($user);

    $account = Account::factory()->create([
        'owner_user_id' => $user->id,
    ]);

    AccountMembership::query()->create([
        'account_id' => $account->id,
        'user_id' => $user->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    Form::factory()->create([
        'account_id' => $account->id,
        'name' => 'First Form',
        'is_active' => true,
    ]);

    $this->get(route('manage.forms.index', ['account_id' => $account->id]))
        ->assertOk()
        ->assertSee('Setup progress')
        ->assertSee('Completed')
        ->assertSee('Publish the embed snippet')
        ->assertSee('Open Integrations');
});

test('setup sidebar stays synced when no explicit account query is provided', function () {
    config()->set('capture.features.enforce_access_context', false);

    $user = User::factory()->create();
    $this->actingAs($user);

    $account = Account::factory()->create([
        'owner_user_id' => $user->id,
    ]);

    AccountMembership::query()->create([
        'account_id' => $account->id,
        'user_id' => $user->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    Form::factory()->create([
        'account_id' => $account->id,
        'name' => 'Synced Form',
        'is_active' => true,
    ]);

    $this->get(route('manage.forms.index'))
        ->assertOk()
        ->assertSee('Setup progress')
        ->assertSee('Completed')
        ->assertSee('Publish the embed snippet')
        ->assertSee('Open Integrations');
});

test('setup sidebar uses global scope in non-enforced mode', function () {
    config()->set('capture.features.enforce_access_context', false);

    $user = User::factory()->create();
    $this->actingAs($user);

    AccountMembership::query()->create([
        'account_id' => '11111111-1111-1111-1111-111111111111',
        'user_id' => $user->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    // Form belongs to a different account than the user's membership context.
    Form::factory()->create([
        'account_id' => '22222222-2222-2222-2222-222222222222',
        'is_active' => true,
    ]);

    $this->get(route('manage.forms.index'))
        ->assertOk()
        ->assertSee('Setup progress')
        ->assertSee('Completed')
        ->assertSee('Publish the embed snippet')
        ->assertSee('Open Integrations');
});

    test('forms index empty state shows guided setup tour', function () {
        $this->get(route('manage.forms.index'))
        ->assertOk()
        ->assertSee('Setup progress')
        ->assertSee('Create your first form')
        ->assertSee('Review trends in Insights');
    });

test('dashboard shows plan switching controls for workspace owners', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = Account::factory()->create([
        'owner_user_id' => $user->id,
    ]);

    AccountMembership::query()->create([
        'account_id' => $account->id,
        'user_id' => $user->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $growth = Plan::query()->where('slug', 'growth')->first();
    $pro = Plan::query()->where('slug', 'pro')->first();

    expect($growth)->not->toBeNull();
    expect($pro)->not->toBeNull();

    Subscription::factory()->create([
        'account_id' => $account->id,
        'plan_id' => $growth->id,
        'status' => 'active',
    ]);

    $this->get(route('manage.forms.index'))
        ->assertOk()
        ->assertSee('Subscription')
        ->assertSee('Current plan:')
        ->assertSee('Growth')
        ->assertSee('Upgrade to Pro');
});

test('dashboard hides plan switching controls for non owners', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $account = Account::factory()->create();

    AccountMembership::query()->create([
        'account_id' => $account->id,
        'user_id' => $user->id,
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $this->get(route('manage.forms.index'))
        ->assertOk()
        ->assertSee('Only workspace owners can change subscription plans from the dashboard.')
        ->assertDontSee('Switch to Pro');
});

test('form can be created from management module', function () {
    $payload = [
        'name' => 'Sales Enquiries',
        'account_id' => '5dd2cd80-f2a9-4495-94be-f8a74c7cc64f',
        'application_id' => '979774a0-2e57-4d5b-a557-c3f477ce7d09',
        'notification_email' => 'owner@example.com',
    ];

    $response = $this->post(route('manage.forms.store'), $payload);

    $form = Form::query()->firstOrFail();

    $response
        ->assertRedirect(route('manage.forms.edit', $form))
        ->assertSessionHas('status');

    expect(data_get($form->settings, 'notification_email'))->toBe('owner@example.com');
});

test('form can be updated from management module', function () {
    $form = Form::factory()->create([
        'name' => 'Old Name',
        'account_id' => '7ce8126b-76af-45cb-9e20-f5b33fd2d1a4',
        'application_id' => '4c5e91c7-d9fb-45e9-8f20-2f9540bfca30',
        'settings' => ['notification_email' => 'old@example.com'],
    ]);

    $this->put(route('manage.forms.update', $form), [
        'name' => 'New Name',
        'account_id' => '7ce8126b-76af-45cb-9e20-f5b33fd2d1a4',
        'application_id' => '4c5e91c7-d9fb-45e9-8f20-2f9540bfca30',
        'notification_email' => 'new@example.com',
    ])->assertRedirect(route('manage.forms.edit', $form));

    $form->refresh();

    expect($form->name)->toBe('New Name');
    expect(data_get($form->settings, 'notification_email'))->toBe('new@example.com');
});

test('form active flag can be toggled', function () {
    $form = Form::factory()->create(['is_active' => true]);

    $this->post(route('manage.forms.toggle-active', $form))
        ->assertRedirect(route('manage.forms.index'));

    expect($form->fresh()->is_active)->toBeFalse();

    $this->post(route('manage.forms.toggle-active', $form))
        ->assertRedirect(route('manage.forms.index'));

    expect($form->fresh()->is_active)->toBeTrue();
});

test('starter plan form limit is enforced when creating forms', function () {
    config()->set('capture.features.default_plan', 'starter');
    config()->set('capture.features.starter_form_limit', 1);
    config()->set('capture.features.plan_enforcement_enabled', true);

    Form::factory()->create([
        'account_id' => 'f53abf5e-0f3b-44fa-85f0-4e88967f8ef5',
    ]);

    $this->from(route('manage.forms.create'))
        ->post(route('manage.forms.store'), [
            'name' => 'Second Starter Form',
            'account_id' => 'f53abf5e-0f3b-44fa-85f0-4e88967f8ef5',
            'application_id' => '5d6c5c75-1a3c-4ed5-bb4f-161245ad6a44',
        ])
        ->assertRedirect(route('manage.forms.create'))
        ->assertSessionHasErrors('plan');

    expect(Form::query()->where('account_id', 'f53abf5e-0f3b-44fa-85f0-4e88967f8ef5')->count())->toBe(1);
});

test('form creation does not depend on hq application validation', function () {
    $service = \Mockery::mock(HQService::class);
    $service->shouldNotReceive('validateApplication');
    $service->shouldNotReceive('createApplication');
    app()->instance(HQService::class, $service);

    $response = $this->from(route('manage.forms.create'))
        ->post(route('manage.forms.store'), [
            'name' => 'Local First Form',
            'account_id' => 'e7269832-c31b-4afd-af8e-8d58e4f9586b',
        ]);

    $form = Form::query()->firstOrFail();

    $response
        ->assertRedirect(route('manage.forms.edit', $form))
        ->assertSessionHasNoErrors();

    expect(Str::isUuid((string) $form->application_id))->toBeTrue();
});

test('form update is blocked when hq application validation fails', function () {
    $service = \Mockery::mock(HQService::class);
    $service->shouldReceive('validateApplication')
        ->once()
        ->andReturn(false);
    app()->instance(HQService::class, $service);

    $form = Form::factory()->create([
        'name' => 'Existing Name',
    ]);

    $this->from(route('manage.forms.edit', $form))
        ->put(route('manage.forms.update', $form), [
            'name' => 'Should Not Persist',
            'account_id' => $form->account_id,
            'application_id' => $form->application_id,
        ])
        ->assertRedirect(route('manage.forms.edit', $form))
        ->assertSessionHasErrors('application_id');

    expect($form->fresh()->name)->toBe('Existing Name');
});

test('form creation auto-generates local application id when omitted', function () {
    $service = \Mockery::mock(HQService::class);
    $service->shouldNotReceive('createApplication');
    $service->shouldNotReceive('validateApplication');
    app()->instance(HQService::class, $service);

    $response = $this->post(route('manage.forms.store'), [
        'name' => 'Auto Create App Form',
        'account_id' => 'f31b9a08-8e44-4188-9d04-b7d17a6f319f',
        'notification_email' => 'owner@example.com',
    ]);

    $form = Form::query()->firstOrFail();

    $response->assertRedirect(route('manage.forms.edit', $form));
    expect(Str::isUuid((string) $form->application_id))->toBeTrue();
});
