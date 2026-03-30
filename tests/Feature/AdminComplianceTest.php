<?php

use App\Models\Administrator;
use App\Models\BreakGlassApproval;
use App\Models\Enquiry;
use App\Models\Form;
use App\Models\DataSubjectRequest;
use App\Models\SecurityEventSnapshot;
use App\Models\AuditLog;
use App\Support\SecurityEventMetrics;
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

test('compliance reader can view dashboard but cannot update dsr status', function () {
    $admin = Administrator::factory()->create([
        'role' => 'compliance_reader',
    ]);

    $requestItem = DataSubjectRequest::query()->create([
        'account_id' => 'f0f6efe8-c9d3-4792-a2d8-2fae22cbf152',
        'subject_email' => 'reader-test@example.com',
        'request_type' => 'export',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.compliance.index'))
        ->assertOk();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.compliance.dsr.update', $requestItem), [
            'status' => 'in_progress',
        ])
        ->assertStatus(403);
});

test('compliance operator can update status but cannot process dsr', function () {
    $admin = Administrator::factory()->create([
        'role' => 'compliance_operator',
    ]);

    $requestItem = DataSubjectRequest::query()->create([
        'account_id' => '548f5cd0-8ea7-43f9-9f20-5493fdf22a92',
        'subject_email' => 'operator-test@example.com',
        'request_type' => 'delete',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.compliance.dsr.update', $requestItem), [
            'status' => 'in_progress',
        ])
        ->assertSessionHas('status');

    $this->actingAs($admin, 'admin')
        ->post(route('admin.compliance.dsr.process', $requestItem), [
            'reason' => 'Operator cannot process',
        ])
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

test('suspended administrator cannot access compliance dashboard', function () {
    $admin = Administrator::factory()->create([
        'status' => 'suspended',
        'role' => 'compliance_admin',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.compliance.index'))
        ->assertStatus(403);
});

test('admin compliance processing is blocked for non-pro account when plan gate is enabled', function () {
    config([
        'capture.features.admin_compliance_plan_gate_enabled' => true,
        'capture.features.default_plan' => 'growth',
        'hq.enabled' => false,
    ]);

    $admin = Administrator::factory()->create([
        'role' => 'compliance_admin',
    ]);

    $requestItem = DataSubjectRequest::query()->create([
        'account_id' => '2d6431d1-cc57-4260-a506-5c5d301e1914',
        'subject_email' => 'blocked@example.com',
        'request_type' => 'export',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.compliance.dsr.process', $requestItem), [
            'reason' => 'Should be blocked by plan gate',
        ])
        ->assertStatus(403);
});

test('admin compliance access requires mfa when configured', function () {
    config([
        'capture.features.require_admin_mfa_for_compliance' => true,
    ]);

    $adminWithoutMfa = Administrator::factory()->create([
        'role' => 'compliance_admin',
        'mfa_enabled' => false,
    ]);

    $this->actingAs($adminWithoutMfa, 'admin')
        ->get(route('admin.compliance.index'))
        ->assertStatus(403);

    $adminWithMfa = Administrator::factory()->create([
        'role' => 'compliance_admin',
        'mfa_enabled' => true,
    ]);

    $this->actingAs($adminWithMfa, 'admin')
        ->get(route('admin.compliance.index'))
        ->assertOk();
});

test('sensitive dsr processing requires active break glass approval when enabled', function () {
    config([
        'capture.features.require_break_glass_for_sensitive_dsr' => true,
    ]);

    $admin = Administrator::factory()->create([
        'role' => 'compliance_admin',
    ]);

    $form = Form::factory()->create();

    $requestItem = DataSubjectRequest::query()->create([
        'account_id' => $form->account_id,
        'subject_email' => 'breakglass-required@example.com',
        'request_type' => 'delete',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.compliance.dsr.process', $requestItem), [
            'reason' => 'Should require break glass',
        ])
        ->assertStatus(403);
});

test('sensitive dsr processing works with approved break glass by different admin', function () {
    config([
        'capture.features.require_break_glass_for_sensitive_dsr' => true,
    ]);

    $requester = Administrator::factory()->create([
        'role' => 'compliance_admin',
    ]);

    $approver = Administrator::factory()->create([
        'role' => 'compliance_admin',
    ]);

    $form = Form::factory()->create([
        'account_id' => 'f772ee64-9458-4f08-a6a7-c870f4d583b6',
        'application_id' => '4355f8a2-c7ba-4d5b-95de-8f6783f98789',
    ]);

    Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'email' => 'sensitive@example.com',
    ]);

    $requestItem = DataSubjectRequest::query()->create([
        'account_id' => $form->account_id,
        'subject_email' => 'sensitive@example.com',
        'request_type' => 'delete',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($requester, 'admin')
        ->post(route('admin.compliance.break-glass.request'), [
            'account_id' => $form->account_id,
            'scope' => 'dsr_sensitive',
            'reason' => 'Need elevated compliance operation',
        ])
        ->assertSessionHas('status');

    $approval = BreakGlassApproval::query()->firstOrFail();

    $this->actingAs($approver, 'admin')
        ->post(route('admin.compliance.break-glass.approve', $approval), [
            'expires_minutes' => 45,
        ])
        ->assertSessionHas('status');

    $this->actingAs($requester, 'admin')
        ->post(route('admin.compliance.dsr.process', $requestItem), [
            'reason' => 'Break glass approved',
        ])
        ->assertSessionHas('status');

    expect($requestItem->fresh()->status)->toBe('completed');
});

test('break glass approval cannot be self-approved', function () {
    $admin = Administrator::factory()->create([
        'role' => 'compliance_admin',
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.compliance.break-glass.request'), [
            'scope' => 'dsr_sensitive',
            'reason' => 'Self approval test',
        ])
        ->assertSessionHas('status');

    $approval = BreakGlassApproval::query()->firstOrFail();

    $this->actingAs($admin, 'admin')
        ->post(route('admin.compliance.break-glass.approve', $approval), [
            'expires_minutes' => 20,
        ])
        ->assertStatus(403);
});

test('admin session is denied when idle timeout is exceeded', function () {
    config([
        'capture.features.harden_admin_sessions' => true,
        'capture.features.admin_session_idle_timeout_minutes' => 15,
    ]);

    $admin = Administrator::factory()->create([
        'role' => 'compliance_admin',
    ]);

    $this->withSession([
        'admin.last_activity_at' => now()->subMinutes(16)->timestamp,
    ])->actingAs($admin, 'admin')
        ->get(route('admin.compliance.index'))
        ->assertStatus(403);
});

test('unverified admin cannot perform sensitive compliance actions when verification enforcement is enabled', function () {
    config([
        'capture.features.require_verified_email_for_sensitive_admin_operations' => true,
    ]);

    $admin = Administrator::factory()->create([
        'role' => 'compliance_admin',
        'email_verified_at' => null,
    ]);

    $requestItem = DataSubjectRequest::query()->create([
        'account_id' => '4d9a0ee7-84a6-48e6-9d9f-64bf90509ca6',
        'subject_email' => 'sensitive-action@example.com',
        'request_type' => 'export',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->post(route('admin.compliance.dsr.update', $requestItem), [
            'status' => 'in_progress',
        ])
        ->assertRedirect(route('admin.verification.notice'));

    expect($requestItem->fresh()->status)->toBe('pending');
    expect(AuditLog::query()->where('action', 'auth.verification.blocked')->count())->toBeGreaterThan(0);
    expect(data_get(app(SecurityEventMetrics::class)->verificationBlockedSummary(), 'breakdown.admin:sensitive_admin_operation'))->toBeGreaterThan(0);
});

test('admin compliance dashboard shows persisted telemetry snapshots', function () {
    $admin = Administrator::factory()->create();

    SecurityEventSnapshot::query()->create([
        'snapshot_date' => '2026-03-30',
        'metric_key' => 'verification_blocked:web:collaborator_acceptance',
        'metric_value' => 7,
        'metadata' => ['source' => 'test'],
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.compliance.index'))
        ->assertOk()
        ->assertSee('Persisted Daily Snapshots')
        ->assertSee('verification_blocked:web:collaborator_acceptance');
});

test('admin compliance dashboard masks subject identifiers by default', function () {
    $admin = Administrator::factory()->create([
        'role' => 'compliance_admin',
    ]);

    DataSubjectRequest::query()->create([
        'account_id' => 'e353b037-f95d-4f27-a5a8-f5f7ec07f196',
        'subject_email' => 'private.subject@example.com',
        'request_type' => 'export',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.compliance.index'))
        ->assertOk()
        ->assertSee('p***@example.com')
        ->assertDontSee('private.subject@example.com');
});

test('compliance admin can reveal masked subject identifiers', function () {
    $admin = Administrator::factory()->create([
        'role' => 'compliance_admin',
    ]);

    DataSubjectRequest::query()->create([
        'account_id' => 'ec75f6f2-45bc-4972-9127-53963bde8326',
        'subject_email' => 'private.subject@example.com',
        'request_type' => 'export',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.compliance.index', ['show_sensitive' => 1]))
        ->assertOk()
        ->assertSee('private.subject@example.com')
        ->assertSee('Hide Sensitive Data');
});

test('compliance reader cannot reveal masked subject identifiers', function () {
    $admin = Administrator::factory()->create([
        'role' => 'compliance_reader',
    ]);

    DataSubjectRequest::query()->create([
        'account_id' => 'fb8c7f9f-4586-4b5a-b777-560442c5eb86',
        'subject_email' => 'private.subject@example.com',
        'request_type' => 'export',
        'status' => 'pending',
        'requested_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(route('admin.compliance.index', ['show_sensitive' => 1]))
        ->assertOk()
        ->assertSee('p***@example.com')
        ->assertDontSee('private.subject@example.com')
        ->assertDontSee('Hide Sensitive Data');
});
