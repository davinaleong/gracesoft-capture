<?php

use App\Models\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('form model generates cryptographically random public token format', function () {
    $form = Form::factory()->create([
        'public_token' => null,
    ]);

    expect($form->public_token)->toMatch('/^frm_[a-f0-9]{32}$/');
    expect(strlen((string) $form->public_token))->toBe(36);
});

test('generated form public tokens are unique across multiple records', function () {
    $tokens = Form::factory()->count(25)->create([
        'public_token' => null,
    ])->pluck('public_token');

    expect($tokens->unique()->count())->toBe($tokens->count());
});
