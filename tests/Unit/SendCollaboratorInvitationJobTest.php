<?php

use App\Jobs\SendCollaboratorInvitationJob;
use App\Mail\CollaboratorInvitationMail;
use App\Models\AccountInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('invitation job sends collaborator invitation email', function () {
    Mail::fake();

    $invitation = AccountInvitation::query()->create([
        'account_id' => '7f0de96a-cf79-4e6e-b4d8-a5e2bfde4a1f',
        'email' => 'invitee@example.com',
        'role' => 'member',
        'invite_token' => hash('sha256', 'token'),
        'expires_at' => now()->addDay(),
    ]);

    $job = new SendCollaboratorInvitationJob($invitation->id, 'https://example.test/accept');
    $job->handle();

    Mail::assertSent(CollaboratorInvitationMail::class, function (CollaboratorInvitationMail $mail) {
        return $mail->hasTo('invitee@example.com');
    });
});
