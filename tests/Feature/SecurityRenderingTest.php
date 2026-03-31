<?php

use App\Models\Enquiry;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public form includes csrf token field', function () {
    $form = Form::factory()->create([
        'is_active' => true,
    ]);

    $this->get(route('forms.show', $form->public_token))
        ->assertOk()
        ->assertSee('name="_token"', false);
});

test('inbox detail escapes potentially unsafe enquiry content', function () {
    $user = User::factory()->create();

    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'subject' => '<script>alert("xss")</script>',
        'message' => '<img src=x onerror=alert("xss") />',
    ]);

    $this->actingAs($user)
        ->get(route('inbox.show', $enquiry))
        ->assertOk()
        ->assertDontSee('<script>alert("xss")</script>', false)
        ->assertDontSee('<img src=x onerror=alert("xss") />', false)
        ->assertSee('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', false)
        ->assertSee('&lt;img src=x onerror=alert(&quot;xss&quot;) /&gt;', false);
});
