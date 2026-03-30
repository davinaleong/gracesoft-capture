<?php

use App\Models\Administrator;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('user can request password reset link', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), [
        'email' => $user->email,
    ])->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('administrator can request password reset link', function () {
    Notification::fake();

    $admin = Administrator::factory()->create();

    $this->post(route('admin.password.email'), [
        'email' => $admin->email,
    ])->assertSessionHas('status');

    Notification::assertSentTo($admin, ResetPassword::class);
});

test('user can reset password with valid token', function () {
    $user = User::factory()->create();

    $token = Password::broker('users')->createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertRedirect(route('login'));

    expect(auth('web')->attempt([
        'email' => $user->email,
        'password' => 'new-password-123',
    ]))->toBeTrue();
});

test('administrator can reset password with valid token', function () {
    $admin = Administrator::factory()->create();

    $token = Password::broker('administrators')->createToken($admin);

    $this->post(route('admin.password.update'), [
        'token' => $token,
        'email' => $admin->email,
        'password' => 'new-admin-pass-123',
        'password_confirmation' => 'new-admin-pass-123',
    ])->assertRedirect(route('admin.login'));

    expect(auth('admin')->attempt([
        'email' => $admin->email,
        'password' => 'new-admin-pass-123',
    ]))->toBeTrue();
});

test('user receives verification email on registration and can verify', function () {
    Notification::fake();

    $this->post(route('register.store'), [
        'name' => 'Verify User',
        'email' => 'verify.user@example.com',
        'password' => 'verify-user-pass',
        'password_confirmation' => 'verify-user-pass',
    ])->assertRedirect(route('manage.forms.index'));

    $user = User::query()->where('email', 'verify.user@example.com')->firstOrFail();

    Notification::assertSentTo($user, VerifyEmail::class);

    $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addMinutes(30), [
        'id' => $user->getKey(),
        'hash' => sha1($user->getEmailForVerification()),
    ]);

    $this->actingAs($user, 'web')
        ->get($verificationUrl)
        ->assertRedirect(route('manage.forms.index'));

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('administrator can verify email through signed verification route', function () {
    Notification::fake();

    $admin = Administrator::factory()->create([
        'email_verified_at' => null,
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.verification.send'))
        ->assertSessionHas('status');

    Notification::assertSentTo($admin, VerifyEmail::class);

    $verificationUrl = URL::temporarySignedRoute('admin.verification.verify', now()->addMinutes(30), [
        'id' => $admin->getKey(),
        'hash' => sha1($admin->getEmailForVerification()),
    ]);

    $this->actingAs($admin, 'admin')
        ->get($verificationUrl)
        ->assertRedirect(route('admin.compliance.index'));

    expect($admin->fresh()->hasVerifiedEmail())->toBeTrue();
});
