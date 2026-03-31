<?php

use App\Models\Enquiry;
use App\Models\Form;
use App\Models\Note;
use App\Models\Reply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('enquiry message is encrypted at rest', function () {
    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'message' => 'Sensitive enquiry message',
    ]);

    $raw = DB::table('enquiries')->where('id', $enquiry->id)->value('message');

    expect($raw)->not->toBe('Sensitive enquiry message');
    expect($enquiry->fresh()->message)->toBe('Sensitive enquiry message');
});

test('note and reply content are encrypted at rest', function () {
    $form = Form::factory()->create();

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
    ]);

    $note = Note::query()->create([
        'enquiry_id' => $enquiry->id,
        'user_id' => '7014ec0e-5968-4328-9262-0fb486e95bbf',
        'content' => 'Private note content',
    ]);

    $reply = Reply::query()->create([
        'enquiry_id' => $enquiry->id,
        'account_id' => $form->account_id,
        'sender_type' => 'system',
        'sender_id' => null,
        'email' => null,
        'content' => 'Private reply content',
        'is_internal' => true,
    ]);

    $rawNote = DB::table('notes')->where('id', $note->id)->value('content');
    $rawReply = DB::table('replies')->where('id', $reply->id)->value('content');

    expect($rawNote)->not->toBe('Private note content');
    expect($rawReply)->not->toBe('Private reply content');
    expect($note->fresh()->content)->toBe('Private note content');
    expect($reply->fresh()->content)->toBe('Private reply content');
});
