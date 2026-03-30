# GraceSoft Capture Implementation Progress

Last Updated: 2026-03-31 (latest pass)

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

## Completed In Next Continuation

- Implemented retention automation in `DataRetentionService`:
  - Deletes expired `audit_logs`, `data_access_logs`, and `consents` records.
  - Deletes expired resolved `data_subject_requests` records.
  - Anonymizes closed enquiries older than retention window with marker metadata.
- Added queueable retention job:
  - `RunDataRetentionCleanupJob`
- Added retention commands and scheduler wiring in `routes/console.php`:
  - `capture:retention:cleanup`
  - `capture:retention:queue`
  - Daily schedule at `02:10` with overlap protection.
- Added retention config:
  - `APP_DATA_RETENTION_DAYS` via `capture.features.data_retention_days`.
- Added unit coverage:
  - `DataRetentionServiceTest` validates expiry deletion and anonymization behavior.

## Completed In Latest Continuation

- Implemented least-privilege admin role matrix with capability gating:
  - Added `administrators.role` field (default `compliance_admin`).
  - Added role/capability mapping in `config/capture.php`.
  - Added capability helper methods on `Administrator` model.
- Enforced administrator status and capability checks in base controller gate:
  - Suspended admins are denied.
  - Compliance actions now require explicit capability keys.
- Applied capability boundaries in compliance module:
  - `compliance.view` for dashboard access.
  - `compliance.manage_dsr_status` for status updates.
  - `compliance.process_dsr` for DSR execution.
- Added feature tests for role matrix behavior:
  - reader can view but cannot update.
  - operator can update status but cannot process.
  - suspended admin is denied access.

## Completed In Current Continuation

- Implemented plan-gated admin compliance access using `PlanGate`:
  - Added `complianceViewsEnabled()` with feature-flag support.
  - Added reusable plan resolver for account-scoped gates.
- Added admin compliance plan-gate config:
  - `CAPTURE_ADMIN_COMPLIANCE_PLAN_GATE_ENABLED`
  - allowed plans list (default `pro`).
- Enforced Pro-plan gate in admin compliance controller:
  - Dashboard filtered by account can be blocked for non-allowed plans.
  - DSR status/process actions are blocked for non-allowed plans.
- Added tests for plan-gated compliance behavior:
  - Unit coverage for `PlanGate::complianceViewsEnabled()`.
  - Feature coverage for blocked DSR processing on non-Pro account when gate is enabled.

## Completed In Latest Continuation

- Added optional MFA enforcement for admin compliance actions:
  - `CAPTURE_REQUIRE_ADMIN_MFA_FOR_COMPLIANCE`
  - When enabled, compliance capabilities require `administrators.mfa_enabled=true`.
- Added feature coverage for MFA-gated compliance access.

## Completed In Current Continuation

- Implemented break-glass workflow with enhanced logging and two-person approval:
  - Added `break_glass_approvals` table and `BreakGlassApproval` model.
  - Added request + approve endpoints in admin compliance module.
  - Enforced self-approval prohibition for break-glass approvals.
  - Added audit events for request/approval actions.
- Enforced break-glass requirement for sensitive DSR processing (`delete`/`restrict`) when enabled:
  - `CAPTURE_REQUIRE_BREAK_GLASS_FOR_SENSITIVE_DSR`
  - Requires active, non-expired approval for `dsr_sensitive` scope.
- Added break-glass controls section to admin compliance UI.
- Implemented admin session hardening middleware:
  - Configurable idle timeout (`CAPTURE_ADMIN_SESSION_IDLE_TIMEOUT_MINUTES`).
  - Configurable enable switch (`CAPTURE_HARDEN_ADMIN_SESSIONS`).
  - Applied to admin compliance routes.
- Added feature tests for break-glass enforcement, two-person approval rule, and idle-timeout session denial.

## Completed In Latest Continuation

- Implemented explicit user/admin authentication flows with separate routes and controllers:
  - User register/login/logout routes and handlers.
  - Administrator login/logout routes and handlers.
- Enforced user/admin session boundary behavior:
  - Signing into one guard signs out the other guard.
  - Session guard context markers updated on sign-in.
- Added administrator active-status enforcement during admin login.
- Added login/register views and session-state UX in app layout:
  - User/admin login entry points when unauthenticated.
  - Guard-specific session badge and logout actions when authenticated.
- Added feature tests for auth flows and session boundary behavior:
  - user registration/login
  - administrator login
  - cross-guard session replacement
  - suspended admin login denial

## Current Security Behavior

- With `CAPTURE_ENFORCE_ACCESS_CONTEXT=true`:
  - Non-admin users are constrained to active account membership.
  - `viewer` cannot perform write actions for forms, status updates, or notes.
  - Admin override reads require `access_reason`.
- With `CAPTURE_ENFORCE_ACCESS_CONTEXT=false`:
  - Backward-compatible behavior remains available during rollout.

## Remaining High-Priority Work

- Password reset and email-verification flows for users and administrators.

## Validation Snapshot

- Current status: tests passing (`php artisan test`).
- Current passing total: 68 tests.
- Added feature tests for collaborators, role-based authorization, admin compliance, and consent capture.
