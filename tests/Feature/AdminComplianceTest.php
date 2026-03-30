<?php

use App\Models\Administrator;
use App\Models\Enquiry;
use App\Models\Form;
use App\Models\DataSubjectRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can view compliance dashboard', function () {
    $admin = Administrator::factory()->create();

    $this->actingAs($admin, 'admin')
        ->get(route('admin.compliance.index'))
        ->assertOk()
        ->assertSee('Admin Compliance Monitoring');
});

test('non admin cannot view compliance dashboard', function () {
    $this->get(route('admin.compliance.index'))
        ->assertStatus(403);
});

test('admin can update data subject request status', function () {
    $admin = Administrator::factory()->create();

    $requestItem = DataSubjectRequest::query()->create([
        'account_id' => 'b66596ec-7de8-4729-a57d-ef6de4f2df38',
        'subject_email' => 'subject@example.com',
        'request_type' => 'export',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.compliance.dsr.update', $requestItem), [
            'status' => 'completed',
            'reason' => 'Request fulfilled',
        ])
        ->assertSessionHas('status');

    expect($requestItem->fresh()->status)->toBe('completed');
    expect($requestItem->fresh()->resolved_by_administrator_uuid)->toBe($admin->uuid);
});

test('admin can process export data subject request', function () {
    $admin = Administrator::factory()->create();

    $form = Form::factory()->create([
        'account_id' => '1546f7a3-e2a0-4385-839e-a68c7d447e83',
        'application_id' => 'b23f3014-2ddf-4a88-8764-59b12e9f4fce',
    ]);

    Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'email' => 'subject@example.com',
    ]);

    Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'email' => 'subject@example.com',
        'status' => 'contacted',
    ]);

    $requestItem = DataSubjectRequest::query()->create([
        'account_id' => $form->account_id,
        'subject_email' => 'subject@example.com',
        'request_type' => 'export',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.compliance.dsr.process', $requestItem), [
            'reason' => 'Verified export request',
        ])
        ->assertSessionHas('status');

    $fresh = $requestItem->fresh();

    expect($fresh->status)->toBe('completed');
    expect(data_get($fresh->resolution_metadata, 'processed_operation'))->toBe('export');
    expect(data_get($fresh->resolution_metadata, 'evidence.matched_enquiries'))->toBe(2);
});

test('admin can process delete data subject request', function () {
    $admin = Administrator::factory()->create();

    $form = Form::factory()->create([
        'account_id' => 'b680f3ef-f3dd-46ae-8af6-b51f1ae95fce',
        'application_id' => '603e4ec2-fcdf-48a7-896e-837ecf89fb68',
    ]);

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'email' => 'subject-delete@example.com',
        'name' => 'Delete Me',
        'subject' => 'Personal request',
        'message' => 'Personal message',
    ]);

    $requestItem = DataSubjectRequest::query()->create([
        'account_id' => $form->account_id,
        'subject_email' => 'subject-delete@example.com',
        'request_type' => 'delete',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.compliance.dsr.process', $requestItem), [
            'reason' => 'Verified deletion request',
        ])
        ->assertSessionHas('status');

    expect($requestItem->fresh()->status)->toBe('completed');
    expect(data_get($requestItem->fresh()->resolution_metadata, 'processed_operation'))->toBe('delete');

    $anonymized = $enquiry->fresh();

    expect($anonymized->name)->toBe('Deleted Subject');
    expect($anonymized->message)->toBe('[REDACTED]');
    expect($anonymized->email)->toStartWith('deleted+');
});

test('admin can process restrict data subject request', function () {
    $admin = Administrator::factory()->create();

    $form = Form::factory()->create([
        'account_id' => '84eb8cfb-c59d-4cd2-8f4f-1936f6ddf420',
        'application_id' => 'bffb81fc-c91d-41b2-b41b-d184d8f86048',
    ]);

    $enquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'email' => 'subject-restrict@example.com',
    ]);

    $requestItem = DataSubjectRequest::query()->create([
        'account_id' => $form->account_id,
        'subject_email' => 'subject-restrict@example.com',
        'request_type' => 'restrict',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.compliance.dsr.process', $requestItem), [
            'reason' => 'Verified restrict request',
        ])
        ->assertSessionHas('status');

    $fresh = $requestItem->fresh();

    expect($fresh->status)->toBe('completed');
    expect(data_get($fresh->resolution_metadata, 'processed_operation'))->toBe('restrict');
    expect(data_get($enquiry->fresh()->metadata, 'dsr.restricted'))->toBeTrue();
});
