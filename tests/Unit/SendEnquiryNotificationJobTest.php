<?php

use App\Jobs\SendEnquiryNotificationJob;
use App\Mail\NewEnquiryNotificationMail;
use App\Models\Enquiry;
use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('notification job sends new enquiry mail to recipient', function () {
    Mail::fake();

    $form = Form::factory()->create([
        'name' => 'Support Form',
    ]);

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'subject' => 'Test mail',
    ]);

    $job = new SendEnquiryNotificationJob($enquiry->id, 'owner@example.com');
    $job->handle();

    Mail::assertSent(NewEnquiryNotificationMail::class, function (NewEnquiryNotificationMail $mail) use ($enquiry) {
        return $mail->hasTo('owner@example.com')
            && $mail->enquiry->is($enquiry);
    });
});
