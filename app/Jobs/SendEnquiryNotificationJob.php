<?php

namespace App\Jobs;

use App\Mail\NewEnquiryNotificationMail;
use App\Models\Enquiry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendEnquiryNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $enquiryId,
        public string $recipientEmail,
    ) {
    }

    public function handle(): void
    {
        $enquiry = Enquiry::query()->with('form')->find($this->enquiryId);

        if (! $enquiry) {
            return;
        }

        Mail::to($this->recipientEmail)->send(new NewEnquiryNotificationMail($enquiry));
    }
}
