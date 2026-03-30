# GraceSoft Capture Implementation Progress

Last Updated: 2026-03-30

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

## Current Security Behavior

- With `CAPTURE_ENFORCE_ACCESS_CONTEXT=true`:
  - Non-admin users are constrained to active account membership.
  - `viewer` cannot perform write actions for forms, status updates, or notes.
  - Admin override reads require `access_reason`.
- With `CAPTURE_ENFORCE_ACCESS_CONTEXT=false`:
  - Backward-compatible behavior remains available during rollout.

## Remaining High-Priority Work

- Authentication flows for users/admins in UI (login/logout and admin session boundary UX).
- DSR execution workflows (`data_subject_requests` processing and resolution actions).
- Consent capture wiring in form submission endpoint.
- Admin monitoring pages for audit/data access/DSR queue.
- Retention/anonymization jobs and schedules.

## Validation Snapshot

- Current status: tests passing (`php artisan test`).
- Added feature tests for collaborators and role-based authorization.
