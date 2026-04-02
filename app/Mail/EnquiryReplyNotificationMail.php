<?php

namespace App\Mail;

use App\Models\Enquiry;
use App\Models\Reply;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Str;

class EnquiryReplyNotificationMail extends Mailable
{
    use Queueable;

    public function __construct(
        public Enquiry $enquiry,
        public Reply $reply,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on your enquiry',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.enquiries.reply',
            with: [
                'formName' => trim((string) optional($this->enquiry->form)->name) !== ''
                    ? (string) $this->enquiry->form->name
                    : 'GraceSoft Capture',
                'subjectPreview' => Str::limit((string) $this->enquiry->subject, 80),
                'replyPreview' => Str::limit((string) $this->reply->content, 800),
            ],
        );
    }
}
