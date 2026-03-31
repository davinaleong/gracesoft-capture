# GraceSoft Capture Implementation Progress

Last Updated: 2026-03-31 (latest pass)

## Completed In Current Continuation

- Minimized PII exposure in enquiry notification emails:
  - Updated `NewEnquiryNotificationMail` subject to a generic value.
  - Masked sender name/email in template output.
  - Rendered bounded subject/message previews instead of full raw values.
- Added mail unit coverage:
  - `NewEnquiryNotificationMailTest` verifies masked/minimized rendering and no raw email/name leakage.
  - Re-verified `SendEnquiryNotificationJobTest`.
- Synchronized checklist for least-sensitive email templates and submission PII-minimization status.

## Completed In Current Continuation

- Added explicit security rendering checks:
  - `SecurityRenderingTest` verifies CSRF hidden token is present on public form submission UI.
  - `SecurityRenderingTest` verifies inbox detail escapes potentially unsafe enquiry `subject` and `message` content.
- Verified security rendering suite is green (`2` tests, `7` assertions).

## Completed In Current Continuation

- Implemented remaining admin monitoring capabilities in compliance dashboard:
  - Added `Global Platform Metrics` (accounts, enquiries, open enquiries, pending DSR).
  - Added `Tenant Health Monitoring` table with per-account activity indicators.
  - Added `Abuse Detection Queue` table for repeated submission patterns by account/email.
- Added feature coverage for new admin monitoring sections in `AdminComplianceTest`.
- Adjusted dashboard guest-access expectation in tests to match deny-by-default route behavior.
- Verified admin compliance suite is green (`25` tests, `69` assertions).
- Synchronized checklist schema-field and admin monitoring status markers with implemented behavior.

## Completed In Current Continuation

- Implemented data minimization controls across forms, analytics, and logs:
  - Added `CAPTURE_STORE_SUBMISSION_REQUEST_METADATA` (default off) to avoid persisting request IP/user-agent on submissions unless explicitly enabled.
  - Added recursive sensitive-key redaction for audit and data-access log metadata via `capture.features.audit_metadata_redact_keys`.
  - Added unit tests validating metadata redaction behavior in both audit log stores.
- Extended form submission coverage for minimization behavior:
  - default mode stores no request metadata
  - opt-in mode stores request metadata as expected
- Added data inventory and classification reference:
  - `_internal-docs/55-data-inventory.md`
- Verified minimization suites are green (`14` tests, `50` assertions):
  - `PublicFormSubmissionTest`
  - `AuditLoggerTest`

## Completed In Current Continuation

- Added explicit privilege-escalation coverage for collaborator invites:
  - `CollaboratorsModuleTest` now asserts members cannot issue `owner` role invitations.
- Verified collaborator suite remains green (`14` tests, `54` assertions).
- Synchronized delivery checklist parent/status items with implemented behavior:
  - modules scaffold status
  - form create/store + public route validation parent markers
  - enquiry notification job
  - HQ/subscription/phase status markers
  - advanced phase notes/insights status markers

## Completed In Current Continuation

- Implemented optional post-submit redirect for public forms:
  - Added feature flag: `CAPTURE_ENABLE_FORM_SUCCESS_REDIRECT`.
  - Added support for per-form `settings.success_redirect_url` on successful submission.
  - Added URL safety validation (only `http/https` schemes allowed).
  - Falls back to default in-form success redirect when URL is absent/invalid.
- Added feature coverage for success redirect behavior:
  - redirects to configured success URL when enabled
  - ignores invalid redirect URL and falls back safely
- Verified public form suite is green (`10` tests, `37` assertions).

## Completed In Current Continuation

- Implemented optional form domain validation via request headers:
  - Added feature flag: `CAPTURE_ENFORCE_FORM_DOMAIN_VALIDATION`.
  - Enforced Origin/Referer host validation against per-form `settings.allowed_domains` on public form view and submit.
  - Supports exact and subdomain matches for configured domains.
- Added feature coverage for domain validation behavior:
  - blocks access from non-allowed origin domain
  - allows submission from configured allowed domain
- Verified public form suite is green (`8` tests, `33` assertions).

## Completed In Current Continuation

- Enforced deny-by-default access on protected `auth.any` routes:
  - Updated `EnsureUserOrAdministrator` middleware to always require authenticated user/admin sessions.
  - Removed fallback pass-through behavior when access-context enforcement is disabled.
- Updated feature coverage to align with mandatory auth behavior:
  - `FormManagementTest` now authenticates requests in setup.
  - Added `ProtectedRoutesAuthTest` to assert guest redirects and authenticated access for protected routes.
- Verified targeted auth hardening suites are green (`21` tests, `81` assertions):
  - `ProtectedRoutesAuthTest`
  - `FormManagementTest`
  - `AuthSessionBoundaryTest`

## Completed In Current Continuation

- Hardened HQ analytics event verification to enforce data minimization:
  - Extended `EnquiryNotificationDispatchTest` assertions to ensure analytics payload excludes raw PII (`name`, `email`, `subject`, `message`).
  - Confirmed analytics event still carries account/application scoped telemetry fields.
- Verified analytics sync tests are green:
  - `EnquiryNotificationDispatchTest`
  - `SyncAnalyticsEventToHQJobTest`

## Completed In Current Continuation

- Hardened form public token generation:
  - Replaced ad-hoc token generation with cryptographically secure token generation (`random_bytes`).
  - Enforced collision-safe issuance with uniqueness re-check loop.
  - Standardized token format to `frm_` + 32 lowercase hex chars.
- Added token generation coverage:
  - `FormTokenGenerationTest` verifies secure token format.
  - `FormTokenGenerationTest` verifies uniqueness across multiple generated forms.
- Verified no regressions in related form flows:
  - `PublicFormSubmissionTest`
  - `FormManagementTest`

## Completed In Current Continuation

- Implemented HQ application validation integration with short TTL caching:
  - Added `HQService::validateApplication(accountId, applicationId)`.
  - Added resilient HQ payload parsing for validation flags (`valid`/`is_valid`/`allowed` variants).
  - Added per-account/application cache key with configurable TTL.
  - Added validation config surface in `config/hq.php`:
    - `CAPTURE_HQ_VALIDATE_APPLICATION_ENABLED`
    - `VALIDATE_APPLICATION_HQ_URL`
    - `HQ_VALIDATE_APPLICATION_CACHE_TTL_SECONDS`
- Enforced validation in forms management write paths:
  - Blocks form create/update when HQ validation is enabled and application validation fails.
  - Returns user-facing validation error on `application_id`.
- Added/extended automated coverage:
  - `HQServiceTest` for validation success/failure/disabled behavior + cache reuse.
  - `FormManagementTest` for create/update rejection on failed validation.
- Verified targeted suites are green (`14` tests, `48` assertions):
  - `HQServiceTest`
  - `FormManagementTest`

## Completed In Current Continuation

- Implemented Laravel policy layer for account-scoped authorization:
  - Added policies for `Form`, `Enquiry`, `Note`, `Reply`, and `Insights` account access.
  - Added shared account-authorization resolver for membership-role and admin-override checks.
- Registered policy and gate mappings in `AppServiceProvider`:
  - Model policies for forms/enquiries/notes/replies.
  - Named gate `insights.view-account`.
- Applied policy enforcement in write/read controllers:
  - Forms management (`create/view/update/toggle`).
  - Inbox enquiry view + status updates.
  - Notes and replies creation on enquiry detail.
  - Insights account access gate.
- Added request-aware authorization helper in base controller to resolve active actor across user/admin guards.
- Preserved rollout compatibility:
  - Policy enforcement is active when `CAPTURE_ENFORCE_ACCESS_CONTEXT=true`.
  - Existing non-enforced behavior remains unchanged when the flag is disabled.
- Verified targeted regression suites are green (`19` tests, `75` assertions):
  - `RoleAuthorizationTest`
  - `FormManagementTest`
  - `NotesModuleTest`
  - `RepliesModuleTest`
  - `InsightsModuleTest`

## Completed In Current Continuation

- Implemented owner notification flow for collaborator invitation lifecycle:
  - Added `SendCollaboratorOwnerNotificationJob` to notify active account owners.
  - Added `CollaboratorOwnerNotificationMail` and mail template for accepted/revoked events.
  - Dispatched owner notifications from collaborator invitation `accept` and `revoke` actions.
- Added/extended collaborator feature coverage:
  - Acceptance flow now asserts owner-notification job dispatch.
  - Revoke flow now asserts owner-notification job dispatch.
- Verified collaborator suite is green (`13` tests, `52` assertions).

## Completed In Current Continuation

- Implemented remaining shared UI primitives across states, labels, and utility helpers:
  - `components.ui.empty-state`
  - `components.ui.loading`
  - `components.ui.toast`
  - `components.ui.status-badge`
  - `components.ui.tag`
  - `components.ui.role-badge`
  - `components.ui.consent-badge`
  - `components.ui.copy-to-clipboard`
  - `components.ui.date-time`
  - `components.ui.avatar`
  - `components.ui.pii-mask`
- Synchronized component checklist status for shared UI completion in these groups:
  - Feedback and States
  - Status and Labels
  - Utility

## Completed In Current Continuation

- Added dedicated authentication layout and migrated auth screens:
  - Added `layouts.auth` with auth-focused shell and cross-auth navigation shortcuts.
  - Migrated all user/admin auth views to extend `layouts.auth`.
- Added `components.layout.account-context-switcher` and integrated it into `layouts.app` user session header.
- Verified auth and inbox flows remain green after layout/context updates:
  - `AuthSessionBoundaryTest`
  - `AuthRecoveryAndVerificationTest`
  - `InboxModuleTest`

## Completed In Current Continuation

- Implemented additional shared UI primitives for reuse across modules:
  - Added `components.ui.modal`
  - Added `components.ui.dropdown`
  - Added `components.ui.access-denied`
- Consolidated modal implementations:
  - Refactored `components.feedback.modal` to build on `x-ui.modal`.
  - Refactored `components.upgrade.modal` to build on `x-ui.modal`.
- Synchronized component checklist entries for newly added shared UI primitives.

## Completed In Current Continuation

- Implemented shared plan-gating and security state components:
  - Added `components.upgrade.banner`
  - Added `components.upgrade.modal`
  - Added `components.upgrade.badge`
  - Added `components.security.access-denied`
  - Added `components.security.cross-tenant-warning`
- Refactored existing feature components to consume shared primitives:
  - `components.insights.upgrade-banner` now uses `x-upgrade.banner`
  - `components.notes.upgrade-banner` now uses `x-upgrade.banner`
  - `components.enquiry.access-denied-state` now uses `x-security.access-denied`
- Synchronized shared UI checklist status for existing reusable primitives (`ui.card`, `ui.button`, `ui.input`, `ui.textarea`, `ui.select`, `ui.badge`, `ui.alert`).

## Completed In Current Continuation

- Implemented Feedback module componentization for support-to-HQ flow:
  - Added `components.feedback.button`
  - Added `components.feedback.modal`
  - Added `components.feedback.form`
  - Refactored `support/contact` view to use `x-feedback.*` components.
- Added support page assertion coverage for feedback modal trigger/render markers.
- Verified feedback pipeline remains green:
  - `FeedbackSupportTest`
  - `SyncFeedbackToHQJobTest`

## Completed In Current Continuation

- Implemented full Notes module componentization on enquiry detail:
  - Added `components.notes.list`
  - Added `components.notes.item`
  - Added `components.notes.form`
  - Added `components.notes.visibility-badge`
  - Added `components.notes.pinned-section`
  - Added `components.notes.upgrade-banner`
  - Refactored notes section in `inbox/show` to use `x-notes.*` components.
- Expanded Notes data model and persistence for richer context:
  - Added notes metadata columns: `visibility`, `is_pinned`, `tags`, `reminder_at`.
  - Added optional note-input handling in `EnquiryNoteController@store` for metadata fields.
  - Added Note model casts/fillable updates for metadata fields.
- Added notes metadata feature coverage:
  - `NotesModuleTest`: verifies metadata persistence and rendering (pinned section, visibility badge, tags, reminder date).
- Verified targeted notes/replies suites are green (`9` tests, `42` assertions).

## Completed In Current Continuation

- Implemented concrete Starter/Growth plan enforcement controls:
  - Added `PlanGate::formCreationAllowed()` for Starter form-cap limits.
  - Added `PlanGate::collaboratorInviteRoleAllowed()` for plan-scoped collaborator invite role restrictions.
  - Added plan-enforcement config surface:
    - `CAPTURE_PLAN_ENFORCEMENT_ENABLED`
    - `CAPTURE_STARTER_FORM_LIMIT`
    - `plan_invite_roles` map (`starter/growth/pro`).
- Applied plan gates in write paths:
  - Form creation blocked when Starter limit is reached.
  - Collaborator invite role blocked when current plan disallows requested role.
- Added coverage for plan enforcement behavior:
  - `PlanGateTest`: starter limit + growth invite-role restriction checks.
  - `FormManagementTest`: starter form creation limit enforcement.
  - `CollaboratorsModuleTest`: growth cannot invite owner, pro can invite owner.
- Verified targeted plan enforcement suites are green.

## Completed In Current Continuation

- Implemented public form embed layout and componentization:
  - Added `layouts.embed` for iframe/public-form rendering context.
  - Added reusable form embed components:
    - `components.form.wrapper`
    - `components.form.input`
    - `components.form.textarea`
    - `components.form.button`
    - `components.form.success-state`
    - `components.form.error-state`
    - `components.form.consent-notice`
  - Refactored `form` view to use `layouts.embed` and `components.form.*`.
- Verified no behavior regressions via `PublicFormSubmissionTest`.

## Completed In Current Continuation

- Implemented insights componentization and completed funnel chart coverage:
  - Added reusable insights components:
    - `components.insights.card`
    - `components.insights.metric`
    - `components.insights.chart-line`
    - `components.insights.chart-funnel`
    - `components.insights.upgrade-banner`
  - Refactored insights page to use the new insights component set.
  - Extended `InsightsService` summary payload with funnel stages (`total`, `contacted`, `closed`).
  - Added `Conversion funnel` visualization to the insights dashboard.
- Added feature assertion coverage for funnel rendering in `InsightsModuleTest`.

## Completed In Current Continuation

- Implemented enquiry detail access-denied state for read-only collaborators:
  - Added `components.enquiry.access-denied-state` reusable component.
  - Integrated denied-state rendering in replies form for read-only roles.
  - Added denied-state rendering in notes section for read-only roles when notes are plan-enabled.
  - Extended inbox detail permissions payload with `canManageNotes`.
- Added feature coverage proving denied-state visibility for viewer role in enquiry detail.

## Completed In Current Continuation

- Implemented replies module backend and enquiry-detail UI integration:
  - Added `replies` schema (`create_replies_table` migration) and `Reply` model.
  - Added `EnquiryReplyController@store` with account/role enforcement and audit logging.
  - Added inbox reply route: `POST /inbox/{enquiry}/replies`.
  - Added `enquiry->replies` relation and eager-loading in inbox detail flow.
  - Added replies UI components:
    - `components.replies.list`
    - `components.replies.item`
    - `components.replies.sender-badge`
    - `components.replies.form`
    - `components.replies.empty-state`
  - Integrated replies section into enquiry detail page.
- Added feature coverage for replies behavior:
  - owner can add reply
  - viewer is denied under enforced access context
  - replies render in enquiry detail page
- Verified regressions remain green via `RepliesModuleTest` and `InboxModuleTest`.

## Completed In Current Continuation

- Implemented collaborators owner-only permission hint component:
  - Added `components.collaborators.owner-only-banner` with role-aware messaging.
  - Integrated banner into collaborators management view.
  - Added feature coverage for owner vs non-owner banner visibility.
- Verified collaborator module behavior remains green via `CollaboratorsModuleTest`.

## Completed In Current Continuation

- Implemented enquiry detail componentization for inbox detail view:
  - Added `components.enquiry.header` (name, email, subject, status, form context).
  - Added `components.enquiry.message-card` for submission content display.
  - Added `components.enquiry.timeline` for received/contacted/closed lifecycle timestamps.
  - Refactored `inbox/show` to use new enquiry components.
- Verified no behavioral regressions via `InboxModuleTest`.

## Completed In Current Continuation

- Implemented inbox list componentization and search UX:
  - Added reusable list-page components:
    - `components.inbox.table`
    - `components.inbox.row`
    - `components.inbox.filters`
    - `components.inbox.status-badge`
    - `components.inbox.account-context-badge`
    - `components.inbox.empty-state`
  - Refactored `inbox/index` to use new components and account-context badge rendering.
  - Added search filtering in `InboxController@index` (`name`, `email`, `subject`).
- Added feature coverage for inbox list enhancements:
  - search query filtering behavior
  - account-context badge rendering when account context is selected

## Completed In Current Continuation

- Implemented Integration page (install UX) with account-scoped form listings:
  - Added `IntegrationController@index` and `GET /integrations` route behind `auth.any` + `access.context`.
  - Added integrations dashboard view showing form name, app domain, and iframe embed code snippet.
  - Added quick "Send Test Enquiry" action opening the public form for test submissions.
  - Added navigation link to Integrations in the main app layout.
  - Componentized the page with dedicated Blade components:
    - `components.integration.card`
    - `components.integration.embed-code`
    - `components.integration.copy-button`
    - `components.integration.test-button`
- Added feature coverage for integration behavior:
  - account-scoped form visibility
  - embed snippet rendering and test action visibility

## Completed In Current Continuation

- Implemented Insights module with account-scoped metrics and Pro-plan gating:
  - Added `InsightsService` to compute total enquiries, enquiries-per-day, conversion rate, and average first response time.
  - Added `InsightsController@index` and `GET /insights` route behind `auth.any` + `access.context`.
  - Added `PlanGate::insightsEnabled()` with config toggles (`CAPTURE_INSIGHTS_FORCE_ENABLED`, `insights_allowed_plans`).
  - Added insights dashboard view with selectable lookback windows (7/14/30 days) and daily trend bars.
- Added feature coverage for insights behavior:
  - account-scoped rendering and metric isolation
  - non-Pro access denial when gate is enforced

## Completed In Current Continuation

- Implemented collaborator invitation abuse alerting for repeated invalid acceptance attempts:
  - Added cache-window counter tracking by invitation/reason/IP.
  - Added structured audit event for invalid attempts (`collaborators.invite.accept.invalid`).
  - Added alert audit event when threshold is exceeded (`collaborators.invite.accept.alert`).
  - Covered invalid signature, inactive invitation, token mismatch, and email mismatch paths.
- Added feature coverage for repeated invalid token attempts and alert emission behavior.

## Completed In Current Continuation

- Implemented administrator access recertification workflow for periodic access review:
  - Added `administrators.compliance_recertified_at` tracking column.
  - Added compliance dashboard recertification table with due/current status.
  - Added recertification action endpoint with anti-self-approval rule.
  - Added `compliance.recertify_admin_access` capability for `compliance_admin` role.
  - Added `admin.access.recertified` audit event logging.
- Added feature coverage for recertification behavior:
  - section visibility and due-state rendering
  - authorized recertification success
  - self-recertification denial
  - role-based denial for non-privileged administrators

## Completed In Previous Continuation

- Implemented default-sensitive-data masking in admin compliance dashboard:
  - DSR subject identifiers are masked by default in list views.
  - Added privileged reveal toggle (`show_sensitive`) for authorized admins only.
  - Added `compliance.view_sensitive` capability and limited it to `compliance_admin` role.
- Added feature coverage for masking behavior:
  - default masked rendering
  - privileged reveal behavior
  - non-privileged reveal denial

## Completed In Documentation Continuation

- Synchronized delivery checklist with implemented features and test-backed behavior:
  - Marked completed items for forms/enquiries/notes schema, collaborator acceptance, inbox/status logic, notes module, and public-form anti-abuse controls.
  - Marked completed items for HQ service surface (analytics/feedback/subscription + plan cache).
  - Added checklist coverage item for persisted daily verification-block telemetry snapshots.
- Replaced boilerplate framework README with project-specific documentation:
  - Setup, runtime commands, operational schedulers, feature flags, and internal docs map.
  - Updated validation snapshot reference in README to current passing totals.

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

## Completed In Current Continuation

- Implemented password reset flows for both identity types:
  - User reset request/form/submit routes and controller flow (`users` broker).
  - Administrator reset request/form/submit routes and controller flow (`administrators` broker).
- Implemented email verification flows for both identity types:
  - User verification notice/resend/verify routes and controller flow.
  - Administrator verification notice/resend/verify routes and controller flow.
- Enabled `MustVerifyEmail` contract on `User` and `Administrator` models.
- Added registration-time verification notification dispatch for users.
- Added auth recovery + verification UI pages:
  - user/admin forgot-password
  - user/admin reset-password
  - user/admin verify-email notice
- Added feature coverage for:
  - user/admin password reset link requests
  - user/admin password reset completion with broker tokens
  - user verification from signed link
  - admin verification from signed link

## Completed In Latest Continuation

- Added rollout-gated verified-email enforcement middleware for sensitive actions.
- Enforced verified email on collaborator invitation acceptance when enabled:
  - `CAPTURE_REQUIRE_VERIFIED_EMAIL_FOR_COLLABORATOR_ACCEPTANCE`
- Enforced verified email on sensitive admin compliance operations when enabled:
  - `CAPTURE_REQUIRE_VERIFIED_EMAIL_FOR_SENSITIVE_ADMIN_OPERATIONS`
- Added feature coverage for both enforcement paths:
  - unverified invited user is redirected to verification notice
  - unverified admin is blocked from sensitive compliance updates

## Completed In Current Continuation

- Implemented lightweight verification-enforcement telemetry:
  - Added `SecurityEventMetrics` cache-based counters.
  - Added `auth.verification.blocked` audit logging for blocked actions.
  - Added verification-enforcement summary panel to admin compliance dashboard.
  - Added `CAPTURE_VERIFICATION_BLOCK_METRICS_ENABLED` feature flag.
- Implemented persistent daily telemetry snapshots for verification-block events:
  - Added `security_event_snapshots` table and `SecurityEventSnapshot` model.
  - Added `persistVerificationBlockedSnapshot()` support to `SecurityEventMetrics`.
  - Added scheduled command to persist daily snapshot rows.
  - Extended admin compliance dashboard with persisted snapshot history.
  - Added unit/feature coverage for snapshot persistence and dashboard rendering.
- Added assertions proving telemetry side effects in collaborator and admin compliance feature tests.

## Current Security Behavior

- With `CAPTURE_ENFORCE_ACCESS_CONTEXT=true`:
  - Non-admin users are constrained to active account membership.
  - `viewer` cannot perform write actions for forms, status updates, or notes.
  - Admin override reads require `access_reason`.
- With `CAPTURE_ENFORCE_ACCESS_CONTEXT=false`:
  - Backward-compatible behavior remains available during rollout.

## Remaining High-Priority Work

- Consider expanding verified-email enforcement to broader write actions once rollout metrics are stable.

## Validation Snapshot

- Current status: tests passing (`php artisan test`).
- Current passing total: 87 tests.
- Current assertion total: 288 assertions.
- Added feature tests for collaborators, role-based authorization, admin compliance, and consent capture.
