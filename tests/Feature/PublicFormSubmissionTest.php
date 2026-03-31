<?php

use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('active public form can be viewed', function () {
    $form = Form::factory()->create([
        'name' => 'Support Contact',
        'is_active' => true,
    ]);

    $response = $this->get(route('forms.show', $form->public_token));

    $response
        ->assertOk()
        ->assertSee('Support Contact');
});

test('valid submission stores an enquiry and redirects with status', function () {
    $form = Form::factory()->create([
        'account_id' => '72a3c2a3-50d8-4f92-8926-8f95f70a9f00',
        'application_id' => 'e8d2113d-01f2-4d66-9e97-c226e98b6032',
    ]);

    $payload = [
        'name' => 'Alice Doe',
        'email' => 'alice@example.com',
        'subject' => 'Need help',
        'message' => 'I want to know more about your service.',
        'website' => '',
    ];

    $response = $this->post(route('forms.submit', $form->public_token), $payload);

    $response
        ->assertRedirect(route('forms.show', $form->public_token))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('enquiries', [
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'name' => 'Alice Doe',
        'email' => 'alice@example.com',
        'subject' => 'Need help',
        'status' => 'new',
    ]);
});

test('honeypot blocks bot submission', function () {
    $form = Form::factory()->create();

    $response = $this->from(route('forms.show', $form->public_token))
        ->post(route('forms.submit', $form->public_token), [
            'name' => 'Spam Bot',
            'email' => 'spam@example.com',
            'subject' => 'Spam',
            'message' => 'Spam message.',
            'website' => 'https://bot.example.test',
        ]);

    $response
        ->assertRedirect(route('forms.show', $form->public_token))
        ->assertSessionHasErrors('website');

    $this->assertDatabaseCount('enquiries', 0);
});

test('submission endpoint is rate limited per token and ip', function () {
    $form = Form::factory()->create();

    $payload = [
        'name' => 'Rate Test',
        'email' => 'rate@example.com',
        'subject' => 'Rate limit',
        'message' => 'Checking limiter behavior.',
        'website' => '',
    ];

    foreach (range(1, 5) as $_) {
        $this->post(route('forms.submit', $form->public_token), $payload)
            ->assertRedirect(route('forms.show', $form->public_token));
    }

    $this->post(route('forms.submit', $form->public_token), $payload)
        ->assertStatus(429);
});

test('consent is recorded when consent checkbox is accepted', function () {
    $form = Form::factory()->create([
        'account_id' => 'f8bf1a18-9f92-4fce-ab53-16b4b66457a4',
    ]);

    $payload = [
        'name' => 'Consent User',
        'email' => 'consent@example.com',
        'subject' => 'Consent test',
        'message' => 'Testing consent capture.',
        'website' => '',
        'consent_accepted' => '1',
    ];

    $this->post(route('forms.submit', $form->public_token), $payload)
        ->assertRedirect(route('forms.show', $form->public_token));

    $this->assertDatabaseHas('consents', [
        'account_id' => $form->account_id,
        'policy_type' => 'public_form_submission',
    ]);
});

test('consent can be required by configuration', function () {
    config(['capture.features.require_form_consent' => true]);

    $form = Form::factory()->create();

    $payload = [
        'name' => 'Consent Required',
        'email' => 'required@example.com',
        'subject' => 'Consent required',
        'message' => 'Consent should be mandatory in this mode.',
        'website' => '',
    ];

    $this->from(route('forms.show', $form->public_token))
        ->post(route('forms.submit', $form->public_token), $payload)
        ->assertRedirect(route('forms.show', $form->public_token))
        ->assertSessionHasErrors('consent_accepted');
});

test('public form is blocked when domain validation is enabled and origin is not allowed', function () {
    config(['capture.features.enforce_form_domain_validation' => true]);

    $form = Form::factory()->create([
        'settings' => [
            'allowed_domains' => ['trusted.example.com'],
        ],
    ]);

    $this->withHeader('Origin', 'https://evil.example.com')
        ->get(route('forms.show', $form->public_token))
        ->assertForbidden();
});

test('public form submission is allowed when domain validation matches configured domain', function () {
    config(['capture.features.enforce_form_domain_validation' => true]);

    $form = Form::factory()->create([
        'settings' => [
            'allowed_domains' => ['trusted.example.com'],
        ],
    ]);

    $payload = [
        'name' => 'Allowed Domain User',
        'email' => 'allowed@example.com',
        'subject' => 'Domain check',
        'message' => 'Submitted from allowed domain.',
        'website' => '',
    ];

    $this->withHeader('Origin', 'https://app.trusted.example.com')
        ->post(route('forms.submit', $form->public_token), $payload)
        ->assertRedirect(route('forms.show', $form->public_token));

    $this->assertDatabaseHas('enquiries', [
        'form_id' => $form->id,
        'email' => 'allowed@example.com',
    ]);
});

test('public form can redirect to success url after submit when enabled', function () {
    config(['capture.features.enable_form_success_redirect' => true]);

    $form = Form::factory()->create([
        'settings' => [
            'success_redirect_url' => 'https://www.example.com/thank-you',
        ],
    ]);

    $payload = [
        'name' => 'Redirect User',
        'email' => 'redirect@example.com',
        'subject' => 'Redirect check',
        'message' => 'Please redirect me.',
        'website' => '',
    ];

    $this->post(route('forms.submit', $form->public_token), $payload)
        ->assertRedirect('https://www.example.com/thank-you');
});

test('public form ignores invalid success redirect url and falls back to form page', function () {
    config(['capture.features.enable_form_success_redirect' => true]);

    $form = Form::factory()->create([
        'settings' => [
            'success_redirect_url' => 'javascript:alert(1)',
        ],
    ]);

    $payload = [
        'name' => 'Fallback User',
        'email' => 'fallback@example.com',
        'subject' => 'Fallback check',
        'message' => 'Invalid redirect should be ignored.',
        'website' => '',
    ];

    $this->post(route('forms.submit', $form->public_token), $payload)
        ->assertRedirect(route('forms.show', $form->public_token));
});
