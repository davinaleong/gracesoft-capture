<?php

use App\Models\Enquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('free plan demo page is publicly accessible', function () {
    $this->get(route('demo.free.show'))
        ->assertOk()
        ->assertSee('Capture Demo (Free Plan)')
        ->assertSee('Demo data policy');
});

test('free plan demo submission is accepted without persisting enquiries to database', function () {
    config(['capture.features.demo_submission_ttl_minutes' => 60]);

    Cache::flush();

    $this->post(route('demo.free.submit'), [
        'name' => 'Demo User',
        'email' => 'demo.user@example.com',
        'subject' => 'Trying Capture Demo',
        'message' => 'This is a test demo submission.',
        'website' => '',
    ])
        ->assertRedirect(route('demo.free.show'))
        ->assertSessionHas('status');

    expect(Enquiry::query()->count())->toBe(0);

    $submissionId = (string) session('demo_submission_id', '');

    expect($submissionId)->not->toBe('');
    expect(Cache::has('demo_submission:' . $submissionId))->toBeTrue();
});

test('free plan demo honeypot blocks bot-style submissions', function () {
    $this->from(route('demo.free.show'))
        ->post(route('demo.free.submit'), [
            'name' => 'Bot User',
            'email' => 'bot@example.com',
            'subject' => 'Spam',
            'message' => 'Injected message',
            'website' => 'https://spam.example.com',
        ])
        ->assertRedirect(route('demo.free.show'))
        ->assertSessionHasErrors('website');

    expect(Enquiry::query()->count())->toBe(0);
});
