<?php

use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('forms index displays created forms', function () {
    Form::factory()->create(['name' => 'Website Contact']);

    $this->get(route('manage.forms.index'))
        ->assertOk()
        ->assertSee('Website Contact');
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
