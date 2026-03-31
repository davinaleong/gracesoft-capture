<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sso login creates and authenticates user with valid signature', function () {
    config([
        'capture.features.sso_enabled' => true,
        'capture.features.sso_shared_secret' => 'super-secret',
        'capture.features.sso_signature_ttl_seconds' => 300,
    ]);

    $timestamp = now()->timestamp;
    $email = 'sso-user@example.com';
    $payload = strtolower($email) . '|' . $timestamp;
    $signature = hash_hmac('sha256', $payload, 'super-secret');

    $this->post(route('sso.login'), [
        'email' => $email,
        'name' => 'SSO User',
        'timestamp' => $timestamp,
        'signature' => $signature,
    ])->assertRedirect(route('manage.forms.index'));

    expect(User::query()->where('email', $email)->exists())->toBeTrue();
    expect(auth('web')->check())->toBeTrue();
});

test('sso login rejects invalid signature', function () {
    config([
        'capture.features.sso_enabled' => true,
        'capture.features.sso_shared_secret' => 'super-secret',
    ]);

    $this->post(route('sso.login'), [
        'email' => 'invalid-signature@example.com',
        'timestamp' => now()->timestamp,
        'signature' => 'not-valid',
    ])->assertStatus(403);
});
