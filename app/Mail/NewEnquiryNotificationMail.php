<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class NewEnquiryNotificationMail extends Mailable
{
    use Queueable;

    public function __construct(public Enquiry $enquiry)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New enquiry: ' . $this->enquiry->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.enquiries.new',
        );
    }
}
