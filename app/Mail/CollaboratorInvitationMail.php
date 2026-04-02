<?php

namespace App\Mail;

use App\Models\AccountInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CollaboratorInvitationMail extends Mailable
{
    use Queueable;

    public function __construct(
        public AccountInvitation $invitation,
        public string $acceptUrl,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'You are invited to collaborate on GraceSoft Capture',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.collaborators.invitation',
            with: [
                'roleLabel' => ucfirst((string) $this->invitation->role),
                'expiresAtLabel' => optional($this->invitation->expires_at)->toDayDateTimeString(),
            ],
        );
    }
}
