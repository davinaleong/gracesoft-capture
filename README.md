# GraceSoft Capture

GraceSoft Capture is a Laravel-based multi-tenant enquiry capture platform with strong tenant isolation, separate administrator identity, and compliance-first operations.

## Current Scope

Implemented modules and capabilities include:

- Public form rendering and submission (`/form/{token}`)
- Enquiry storage, inbox listing/detail, and status transitions
- Collaborator invitation lifecycle (invite, resend, revoke, accept)
- Role-based access for `owner/member/viewer`
- Separate user and administrator authentication guards/sessions
- Password reset and email verification for both users and administrators
- Admin compliance dashboard with audit logs, data access logs, DSR workflows
- Break-glass approvals, optional MFA gating, and admin idle-timeout hardening
- Data retention cleanup command and scheduler
- Verification-block telemetry counters with persisted daily snapshots

## Tech Stack

- PHP / Laravel
- Blade views
- Pest / PHPUnit for tests

## Local Setup

1. Install dependencies:

```bash
composer install
npm install
```

2. Create environment file and generate app key:

```bash
cp .env.example .env
php artisan key:generate
```

3. Configure database and run migrations:

```bash
php artisan migrate
```

4. Build frontend assets (or run dev mode):

```bash
npm run build
# or
npm run dev
```

5. Start app:

```bash
php artisan serve
```

## Important Environment Flags

These toggles control security and compliance behavior:

- `CAPTURE_ENFORCE_ACCESS_CONTEXT`
- `CAPTURE_HARDEN_ADMIN_SESSIONS`
- `CAPTURE_ADMIN_SESSION_IDLE_TIMEOUT_MINUTES`
- `CAPTURE_REQUIRE_FORM_CONSENT`
- `APP_DATA_RETENTION_DAYS`
- `CAPTURE_ADMIN_COMPLIANCE_PLAN_GATE_ENABLED`
- `CAPTURE_REQUIRE_ADMIN_MFA_FOR_COMPLIANCE`
- `CAPTURE_REQUIRE_BREAK_GLASS_FOR_SENSITIVE_DSR`
- `CAPTURE_REQUIRE_VERIFIED_EMAIL_FOR_COLLABORATOR_ACCEPTANCE`
- `CAPTURE_REQUIRE_VERIFIED_EMAIL_FOR_SENSITIVE_ADMIN_OPERATIONS`
- `CAPTURE_VERIFICATION_BLOCK_METRICS_ENABLED`

HQ integration-related settings are configured in `config/hq.php` and use `HQ_*` env values.

## Operations Commands

Run ad-hoc operational and compliance tasks:

```bash
php artisan capture:retention:cleanup
php artisan capture:retention:queue
php artisan capture:security-metrics:snapshot
php artisan capture:security-metrics:snapshot --date=2026-03-30
```

Scheduled tasks (configured in `routes/console.php`):

- `capture:security-metrics:snapshot` daily at `00:20`
- `capture:retention:cleanup` daily at `02:10`

## Testing

Run full test suite:

```bash
php artisan test
```

Current validation baseline:

- 79 passing tests
- 261 assertions

## Internal Documentation

Implementation and delivery docs live in:

- `_internal-docs/50-todo-checklist.md`
- `_internal-docs/51-schemas.md`
- `_internal-docs/52-replies-module.md`
- `_internal-docs/53-components-checklist.md`
- `_internal-docs/54-implementation-progress.md`
