<?php

namespace App\Mail;

use App\Models\Enquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Str;

class NewEnquiryNotificationMail extends Mailable
{
    use Queueable;

    public $theme = 'support-alert';

    public function __construct(public Enquiry $enquiry)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New enquiry received',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.enquiries.new',
            with: [
                'maskedName' => $this->maskedName(),
                'maskedEmail' => $this->maskedEmail(),
                'subjectPreview' => Str::limit((string) $this->enquiry->subject, 80),
                'messagePreview' => Str::limit((string) $this->enquiry->message, 240),
            ],
        );
    }

    private function maskedName(): string
    {
        $name = trim((string) $this->enquiry->name);

        if ($name === '') {
            return 'N/A';
        }

        return mb_substr($name, 0, 1) . '***';
    }

    private function maskedEmail(): string
    {
        $email = trim((string) $this->enquiry->email);

        if (! str_contains($email, '@')) {
            return 'N/A';
        }

        [$localPart, $domain] = explode('@', $email, 2);

        if ($localPart === '' || $domain === '') {
            return 'N/A';
        }

        return mb_substr($localPart, 0, 1) . '***@' . $domain;
    }
}
