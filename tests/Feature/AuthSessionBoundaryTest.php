<?php

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('root route shows landing page for guests', function () {
    $this->get('/')
    ->assertOk()
    ->assertSee('Capture every support request before it slips through');
});

test('root route is accessible to authenticated users for landing checkout flow', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'web')
        ->get('/')
    ->assertOk()
    ->assertSee('Pricing plans')
    ->assertSee('Upgrade to Growth');
});

test('root route redirects authenticated admins to compliance dashboard', function () {
    $admin = Administrator::factory()->create([
        'status' => 'active',
    ]);

    $this->actingAs($admin, 'admin')
        ->get('/')
        ->assertRedirect(route('admin.compliance.index'));
});

test('user can register and starts authenticated session', function () {
    $this->post(route('register.store'), [
        'name' => 'New User',
        'email' => 'new.user@example.com',
        'password' => 'strong-pass-123',
        'password_confirmation' => 'strong-pass-123',
    ])->assertRedirect(route('verification.notice'));

    $this->assertAuthenticated('web');
    $this->assertDatabaseHas('users', [
        'email' => 'new.user@example.com',
        'name' => 'New User',
    ]);
});

test('user can log in from user login page', function () {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('manage.forms.index'));

    $this->assertAuthenticated('web');
    $this->assertGuest('admin');
});

test('administrator can log in from admin login page', function () {
    $admin = Administrator::factory()->create([
        'password' => 'password',
        'status' => 'active',
    ]);

    $this->post(route('admin.login.store'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.compliance.index'));

    $this->assertAuthenticated('admin');
    $this->assertGuest('web');
});

test('admin login signs out existing user session', function () {
    $user = User::factory()->create();
    $admin = Administrator::factory()->create([
        'password' => 'password',
        'status' => 'active',
    ]);

    $this->actingAs($user, 'web')
        ->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('admin.compliance.index'));

    $this->assertAuthenticated('admin');
    $this->assertGuest('web');
});

test('user login signs out existing admin session', function () {
    $admin = Administrator::factory()->create([
        'status' => 'active',
    ]);
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('manage.forms.index'));

    $this->assertAuthenticated('web');
    $this->assertGuest('admin');
});

test('suspended administrator cannot log in', function () {
    $admin = Administrator::factory()->create([
        'password' => 'password',
        'status' => 'suspended',
    ]);

    $this->from(route('admin.login'))
        ->post(route('admin.login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])
        ->assertRedirect(route('admin.login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest('admin');
});

test('admin login link is hidden on public auth pages by default', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('Admin Login');
});

test('admin login link can be shown on public auth pages when enabled', function () {
    config()->set('capture.features.show_admin_login_links', true);

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Admin Login');
});
