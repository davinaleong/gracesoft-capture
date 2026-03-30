# GraceSoft Capture Implementation Progress

Last Updated: 2026-03-30 (latest pass)

## Completed Previously

- Access model groundwork implemented in code:
  - Tenant-aware access context middleware (`auth.any`, `access.context`).
  - Separate administrator guard/provider via `administrators` table model.
- Core schema implemented:
  - `account_memberships`
  - `account_invitations`
  - `administrators`
  - `audit_logs`
- Administrator override support implemented:
  - Override detection (`admin_override` or `X-Admin-Override` header)
  - Sensitive read audit entries for enquiry/form views.
- Collaborator management implemented:
  - Invite collaborator
  - Resend invite
  - Revoke invite
  - Accept invite (signed + expiring URL, hashed token validation, email match)
- Collaborator UI and invitation email implemented.
- Full test suite was passing after each integration step.

## Completed In This Iteration

- Collaborator membership removal implemented (owner-only, non-owner targets only).
- Added compliance schema tables:
  - `data_access_logs`
  - `consents`
  - `data_subject_requests`
- Added models:
  - `DataAccessLog`
  - `Consent`
  - `DataSubjectRequest`
- Enforced administrator override access reason requirement on sensitive reads.
- Added role-based authorization for writes under enforced mode:
  - `owner/member` can perform write actions.
  - `viewer` is read-only.
- Added tests for role authorization behavior under enforced mode.

## Completed In Latest Continuation

- Added admin compliance monitoring module:
  - `GET /admin/compliance` dashboard for audit logs, data access logs, and DSR list.
  - DSR status update action (`pending/in_progress/completed/rejected`).
- Added administrator-only controller gate for compliance pages.
- Fixed access-context middleware to allow authenticated admin sessions without requiring override mode.
- Added public form consent UX and backend wiring:
  - Optional consent checkbox in form UI.
  - Consent recording in `consents` table on accepted submit.
  - Configurable required consent mode (`CAPTURE_REQUIRE_FORM_CONSENT`).
- Added new factories/tests:
  - `AdministratorFactory`
  - `AdminComplianceTest`
  - Extended `PublicFormSubmissionTest` with consent tests.

## Completed In Current Continuation

- Implemented executable DSR processing workflows in `DataSubjectRequestProcessor`:
  - `export`: builds auditable export evidence (matched count, status breakdown, sample enquiry UUIDs).
  - `delete`: anonymizes matched enquiry PII fields and records processing markers.
  - `restrict`: marks matched enquiries with restriction metadata for downstream enforcement.
- Added admin processing endpoint and route:
  - `POST /admin/compliance/dsr/{dataSubjectRequest}/process`
- Extended admin compliance UI with per-request process action.
- Added feature tests covering export/delete/restrict processing behavior and evidence persistence.

## Current Security Behavior

- With `CAPTURE_ENFORCE_ACCESS_CONTEXT=true`:
  - Non-admin users are constrained to active account membership.
  - `viewer` cannot perform write actions for forms, status updates, or notes.
  - Admin override reads require `access_reason`.
- With `CAPTURE_ENFORCE_ACCESS_CONTEXT=false`:
  - Backward-compatible behavior remains available during rollout.

## Remaining High-Priority Work

- Authentication flows for users/admins in UI (login/logout and admin session boundary UX).
- Retention/anonymization jobs and schedules.
- Plan-gated admin views and least-privilege admin role matrix.

## Validation Snapshot

- Current status: tests passing (`php artisan test`).
- Current passing total: 50 tests.
- Added feature tests for collaborators, role-based authorization, admin compliance, and consent capture.
