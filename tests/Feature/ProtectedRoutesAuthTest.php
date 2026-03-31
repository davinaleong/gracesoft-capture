<?php

use App\Models\Administrator;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('protected auth.any routes redirect guests to login by default', function () {
    config()->set('capture.features.enforce_access_context', false);

    $form = Form::factory()->create();

    $this->get(route('manage.forms.index'))->assertRedirect('/login');
    $this->get(route('inbox.index'))->assertRedirect('/login');
    $this->get(route('insights.index'))->assertRedirect('/login');
    $this->get(route('integrations.index'))->assertRedirect('/login');
    $this->get(route('manage.forms.edit', $form))->assertRedirect('/login');
});

test('protected auth.any routes allow authenticated users', function () {
    config()->set('capture.features.enforce_access_context', false);

    $this->actingAs(User::factory()->create());

    $this->get(route('manage.forms.index'))->assertOk();
});

test('protected admin compliance routes allow authenticated administrators', function () {
    config()->set('capture.features.enforce_access_context', false);

    $admin = Administrator::factory()->create([
        'status' => 'active',
        'role' => 'compliance_admin',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.compliance.index'))
        ->assertOk();
});
