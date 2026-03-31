<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CollaboratorOwnerNotificationMail extends Mailable
{
    use Queueable;

    public function __construct(
        public string $eventType,
        public string $invitationEmail,
        public string $invitationRole,
        public string $accountId,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = $this->eventType === 'accepted'
            ? 'Collaborator invitation accepted'
            : 'Collaborator invitation revoked';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.collaborators.owner-notification',
        );
    }
}
