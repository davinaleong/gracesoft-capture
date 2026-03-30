<?php

use App\Models\Administrator;
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
