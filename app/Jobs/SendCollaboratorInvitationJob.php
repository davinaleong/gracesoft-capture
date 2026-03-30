<?php

namespace App\Jobs;

use App\Mail\CollaboratorInvitationMail;
use App\Models\AccountInvitation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendCollaboratorInvitationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $invitationId,
        public string $acceptUrl,
    ) {
    }

    public function handle(): void
    {
        $invitation = AccountInvitation::query()->find($this->invitationId);

        if (! $invitation || $invitation->revoked_at !== null || $invitation->accepted_at !== null) {
            return;
        }

        Mail::to($invitation->email)->send(new CollaboratorInvitationMail($invitation, $this->acceptUrl));
    }
}
