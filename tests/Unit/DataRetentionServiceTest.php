<?php

use App\Models\AuditLog;
use App\Models\Consent;
use App\Models\DataAccessLog;
use App\Models\DataSubjectRequest;
use App\Models\Enquiry;
use App\Models\Form;
use App\Services\DataRetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('cleanup removes expired compliance records and anonymizes expired closed enquiries', function () {
    Carbon::setTestNow('2026-03-30 12:00:00');

    config(['capture.features.data_retention_days' => 365]);

    $form = Form::factory()->create([
        'account_id' => '8877b0f8-b5f4-4e87-886d-39cf7b8ccd6b',
        'application_id' => '18178bb3-9ebf-4084-98ab-19dfd88c1a97',
    ]);

    AuditLog::query()->create([
        'actor_type' => 'system',
        'actor_id' => null,
        'actor_source_table' => 'system',
        'account_id' => $form->account_id,
        'action' => 'retention.test.old',
        'target_type' => 'enquiry',
        'target_id' => '1',
        'created_at' => now()->subDays(500),
    ]);

    AuditLog::query()->create([
        'actor_type' => 'system',
        'actor_id' => null,
        'actor_source_table' => 'system',
        'account_id' => $form->account_id,
        'action' => 'retention.test.recent',
        'target_type' => 'enquiry',
        'target_id' => '2',
        'created_at' => now()->subDays(10),
    ]);

    DataAccessLog::query()->create([
        'actor_type' => 'administrator',
        'actor_id' => 'admin-1',
        'actor_source_table' => 'administrators',
        'account_id' => $form->account_id,
        'target_type' => 'enquiry',
        'target_id' => '1',
        'created_at' => now()->subDays(500),
    ]);

    Consent::query()->create([
        'account_id' => $form->account_id,
        'policy_type' => 'public_form_submission',
        'policy_version' => 'v1',
        'accepted_at' => now()->subDays(500),
    ]);

    DataSubjectRequest::query()->create([
        'account_id' => $form->account_id,
        'subject_email' => 'old-subject@example.com',
        'request_type' => 'delete',
        'status' => 'completed',
        'requested_at' => now()->subDays(520),
        'resolved_at' => now()->subDays(500),
    ]);

    DataSubjectRequest::query()->create([
        'account_id' => $form->account_id,
        'subject_email' => 'active-subject@example.com',
        'request_type' => 'delete',
        'status' => 'pending',
        'requested_at' => now()->subDays(1),
    ]);

    $expiredClosedEnquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'name' => 'Old Person',
        'email' => 'old.person@example.com',
        'subject' => 'Old subject',
        'message' => 'Old message',
        'status' => 'closed',
        'closed_at' => now()->subDays(450),
    ]);

    $recentClosedEnquiry = Enquiry::factory()->create([
        'form_id' => $form->id,
        'account_id' => $form->account_id,
        'application_id' => $form->application_id,
        'name' => 'Recent Person',
        'email' => 'recent.person@example.com',
        'subject' => 'Recent subject',
        'message' => 'Recent message',
        'status' => 'closed',
        'closed_at' => now()->subDays(20),
    ]);

    $stats = app(DataRetentionService::class)->cleanup();

    expect($stats['deleted_audit_logs'])->toBe(1);
    expect($stats['deleted_data_access_logs'])->toBe(1);
    expect($stats['deleted_consents'])->toBe(1);
    expect($stats['deleted_resolved_dsr'])->toBe(1);
    expect($stats['anonymized_enquiries'])->toBe(1);

    expect($expiredClosedEnquiry->fresh()->name)->toBe('Retained Subject');
    expect($expiredClosedEnquiry->fresh()->message)->toBe('[REDACTED BY RETENTION POLICY]');
    expect($expiredClosedEnquiry->fresh()->email)->toStartWith('retained+');
    expect(data_get($expiredClosedEnquiry->fresh()->metadata, 'retention.anonymized'))->toBeTrue();

    expect($recentClosedEnquiry->fresh()->name)->toBe('Recent Person');
    expect($recentClosedEnquiry->fresh()->message)->toBe('Recent message');

    expect(AuditLog::query()->count())->toBe(1);
    expect(DataAccessLog::query()->count())->toBe(0);
    expect(Consent::query()->count())->toBe(0);
    expect(DataSubjectRequest::query()->count())->toBe(1);

    Carbon::setTestNow();
});
