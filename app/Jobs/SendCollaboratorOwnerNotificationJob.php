<?php

namespace App\Jobs;

use App\Mail\CollaboratorOwnerNotificationMail;
use App\Models\AccountMembership;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendCollaboratorOwnerNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $accountId,
        public string $eventType,
        public string $invitationEmail,
        public string $invitationRole,
    ) {
    }

    public function handle(): void
    {
        $ownerEmails = AccountMembership::query()
            ->where('account_id', $this->accountId)
            ->where('role', 'owner')
            ->whereNull('removed_at')
            ->with('user')
            ->get()
            ->map(fn (AccountMembership $membership) => $membership->user?->email)
            ->filter(fn (?string $email) => is_string($email) && $email !== '')
            ->unique()
            ->values();

        if ($ownerEmails->isEmpty()) {
            return;
        }

        Mail::to($ownerEmails->all())->send(new CollaboratorOwnerNotificationMail(
            $this->eventType,
            $this->invitationEmail,
            $this->invitationRole,
            $this->accountId,
        ));
    }
}
