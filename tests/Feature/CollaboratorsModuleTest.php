<?php

use App\Jobs\SendCollaboratorInvitationJob;
use App\Models\AccountInvitation;
use App\Models\AccountMembership;
use App\Models\AuditLog;
use App\Support\SecurityEventMetrics;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('owner can invite collaborator and dispatch invitation job', function () {
    Queue::fake();

    $owner = User::factory()->create();
    $accountId = '889f8c8c-7c83-4a27-b866-0100fd4f8dc4';

    AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $owner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $this->actingAs($owner)->post(route('collaborators.store'), [
        'account_id' => $accountId,
        'email' => 'invitee@example.com',
        'role' => 'member',
    ])->assertRedirect(route('collaborators.index', ['account_id' => $accountId]));

    $invitation = AccountInvitation::query()->first();

    expect($invitation)->not->toBeNull();
    expect($invitation->email)->toBe('invitee@example.com');
    expect($invitation->invite_token)->not->toBe('invitee@example.com');
    expect(strlen($invitation->invite_token))->toBe(64);

    Queue::assertPushed(SendCollaboratorInvitationJob::class, 1);
});

test('non owner cannot invite collaborator', function () {
    Queue::fake();

    $member = User::factory()->create();
    $accountId = '4e48a52e-c81f-4e39-932f-309db1adfeff';

    AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $member->id,
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $this->actingAs($member)->post(route('collaborators.store'), [
        'account_id' => $accountId,
        'email' => 'invitee@example.com',
        'role' => 'viewer',
    ])->assertForbidden();

    expect(AccountInvitation::query()->count())->toBe(0);
});

test('invited user can accept invitation from signed link', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create([
        'email' => 'invitee@example.com',
    ]);

    $accountId = 'b9173e91-6451-4d7f-ab7c-e63163351395';
    $plainToken = 'secure-plain-token';

    AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $owner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $invitation = AccountInvitation::query()->create([
        'account_id' => $accountId,
        'email' => 'invitee@example.com',
        'role' => 'member',
        'invite_token' => hash('sha256', $plainToken),
        'invited_by_user_id' => $owner->id,
        'expires_at' => now()->addHour(),
    ]);

    $url = URL::temporarySignedRoute('collaborators.accept', now()->addHour(), [
        'invitation' => $invitation->id,
        'token' => $plainToken,
    ]);

    $this->actingAs($invitee)
        ->get($url)
        ->assertRedirect(route('collaborators.index', ['account_id' => $accountId]));

    $membership = AccountMembership::query()
        ->where('account_id', $accountId)
        ->where('user_id', $invitee->id)
        ->first();

    expect($membership)->not->toBeNull();
    expect($membership->role)->toBe('member');
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('owner can remove non-owner collaborator membership', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $accountId = '7f0c5a2c-4ec0-4a89-b23b-9ae0c04fbc31';

    AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $owner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $toRemove = AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $member->id,
        'role' => 'member',
        'joined_at' => now(),
    ]);

    $this->actingAs($owner)
        ->post(route('collaborators.remove', $toRemove))
        ->assertRedirect();

    expect($toRemove->fresh()->removed_at)->not->toBeNull();
});

test('owner cannot remove owner collaborator membership', function () {
    $owner = User::factory()->create();
    $otherOwner = User::factory()->create();
    $accountId = 'c43a0dcf-8917-49a0-9c7c-5ef19e2e761d';

    AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $owner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $ownerMembership = AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $otherOwner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $this->actingAs($owner)
        ->post(route('collaborators.remove', $ownerMembership))
        ->assertSessionHasErrors('membership');

    expect($ownerMembership->fresh()->removed_at)->toBeNull();
});

test('unverified invited user cannot accept invitation when verification enforcement is enabled', function () {
    config([
        'capture.features.require_verified_email_for_collaborator_acceptance' => true,
    ]);

    $owner = User::factory()->create();
    $invitee = User::factory()->unverified()->create([
        'email' => 'invitee-unverified@example.com',
    ]);

    $accountId = '0ebefb3b-a70f-4761-b8c5-0694f68db976';
    $plainToken = 'secure-plain-token-2';

    AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $owner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $invitation = AccountInvitation::query()->create([
        'account_id' => $accountId,
        'email' => 'invitee-unverified@example.com',
        'role' => 'member',
        'invite_token' => hash('sha256', $plainToken),
        'invited_by_user_id' => $owner->id,
        'expires_at' => now()->addHour(),
    ]);

    $url = URL::temporarySignedRoute('collaborators.accept', now()->addHour(), [
        'invitation' => $invitation->id,
        'token' => $plainToken,
    ]);

    $this->actingAs($invitee)
        ->get($url)
        ->assertRedirect(route('verification.notice'));

    expect($invitation->fresh()->accepted_at)->toBeNull();
    expect(AuditLog::query()->where('action', 'auth.verification.blocked')->count())->toBeGreaterThan(0);
    expect(data_get(app(SecurityEventMetrics::class)->verificationBlockedSummary(), 'breakdown.web:collaborator_acceptance'))->toBeGreaterThan(0);
});

test('repeated invalid invitation acceptance attempts trigger security alert', function () {
    config([
        'capture.features.collaborator_invite_alert_threshold' => 3,
        'capture.features.collaborator_invite_alert_window_minutes' => 30,
    ]);

    $owner = User::factory()->create();
    $invitee = User::factory()->create([
        'email' => 'invitee-alert@example.com',
    ]);

    $accountId = 'dba9d438-1b1c-4409-9dd9-af1a41cf4978';

    AccountMembership::query()->create([
        'account_id' => $accountId,
        'user_id' => $owner->id,
        'role' => 'owner',
        'joined_at' => now(),
    ]);

    $invitation = AccountInvitation::query()->create([
        'account_id' => $accountId,
        'email' => 'invitee-alert@example.com',
        'role' => 'member',
        'invite_token' => hash('sha256', 'correct-token'),
        'invited_by_user_id' => $owner->id,
        'expires_at' => now()->addHour(),
    ]);

    foreach (range(1, 3) as $_) {
        $url = URL::temporarySignedRoute('collaborators.accept', now()->addHour(), [
            'invitation' => $invitation->id,
            'token' => 'wrong-token',
        ]);

        $this->actingAs($invitee)
            ->get($url)
            ->assertRedirect(route('collaborators.index'));
    }

    expect(AuditLog::query()->where('action', 'collaborators.invite.accept.invalid')->count())->toBe(3);
    expect(AuditLog::query()->where('action', 'collaborators.invite.accept.alert')->count())->toBeGreaterThan(0);
});
