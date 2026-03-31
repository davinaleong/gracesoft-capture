<?php

use App\Mail\NewEnquiryNotificationMail;
use App\Models\Enquiry;
use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('new enquiry mail renders minimized sensitive fields', function () {
    $form = Form::factory()->create([
        'name' => 'Support Form',
    ]);

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'name' => 'Alice Example',
        'email' => 'alice@example.com',
        'subject' => 'Sensitive Subject Value',
        'message' => 'Sensitive message body content that should not be delivered in full to minimize unnecessary exposure.',
    ]);

    $mail = new NewEnquiryNotificationMail($enquiry);
    $html = $mail->render();

    expect($mail->envelope()->subject)->toBe('New enquiry received');
    expect($html)->toContain('A***');
    expect($html)->toContain('a***@example.com');
    expect($html)->toContain('Sensitive Subject Value');
    expect($html)->not->toContain('Alice Example');
    expect($html)->not->toContain('alice@example.com');
});
