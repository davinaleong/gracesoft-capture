# 🟩 1. Foundation Setup

Progress Log: `_internal-docs/54-implementation-progress.md`

## Access Model and Trust Boundaries

* [x] Define actor types and permissions:
  * [x] `user`: only access data inside their own account/workspace
  * [x] `collaborator`: invited user with scoped access to the same account/workspace data
  * [x] `administrator`: platform operator stored in a dedicated `administrators` table
* [x] Enforce tenant boundary by default (`account_id` required on all business queries)
* [x] Document security model (auth, authorization, audit, retention, lawful access)

---

## Project Setup

* [x] Create Capture app (Laravel)
* [x] Configure separate Capture DB
* [x] Setup env:
  * [x] `HQ_API_URL`
  * [x] `HQ_API_KEY`
  * [x] `APP_DATA_RETENTION_DAYS`
  * [x] `INVITE_TOKEN_TTL_HOURS`
  * [x] `ADMIN_AUDIT_LOG_ENABLED=true`

---

## Base Structure

* [x] Create modules:
  * [x] Forms
  * [x] Enquiries
  * [x] Inbox
  * [x] Notifications
  * [x] Collaborators
  * [x] Insights
  * [x] Integration
  * [x] Compliance
  * [x] Admin Monitoring

---

# 🗄️ 2. Database (Core Models)

## Forms

* [x] Create `forms` table
  * [x] id (BIGINT)
  * [x] uuid (UUID)
  * [x] account_id
  * [x] application_id
  * [x] public_token (`frm_xxx`)
  * [x] name
  * [x] settings (JSON)
  * [x] is_active

---

## Enquiries

* [x] Create `enquiries` table
  * [x] id (BIGINT)
  * [x] uuid (UUID)
  * [x] form_id
  * [x] account_id
  * [x] application_id
  * [x] name
  * [x] email
  * [x] subject
  * [x] message
  * [x] status (`new/contacted/closed`)
  * [x] contacted_at (nullable)
  * [x] closed_at (nullable)
  * [x] metadata (JSON)

---

## Notes (Pro)

* [x] Create `notes` table
  * [x] id (BIGINT)
  * [x] uuid (UUID)
  * [x] enquiry_id
  * [x] user_id
  * [x] content

---

## Collaborators and Access Control

* [x] Create `account_memberships` table
  * [x] id
  * [x] account_id
  * [x] user_id
  * [x] role (`owner/member/viewer`)
  * [x] invited_by_user_id
  * [x] joined_at
  * [x] removed_at
* [x] Create `account_invitations` table
  * [x] id
  * [x] account_id
  * [x] email
  * [x] role
  * [x] invite_token (hashed)
  * [x] expires_at
  * [x] accepted_at
  * [x] revoked_at

* [x] Create `administrators` table (platform operators)
  * [x] id (BIGINT)
  * [x] uuid (UUID)
  * [x] email (unique)
  * [x] display_name
  * [x] status (`active/suspended`)
  * [x] mfa_enabled
  * [x] last_login_at

---

## Compliance and Monitoring

* [x] Create `audit_logs` table
  * [x] actor_type (`user/administrator/system`)
  * [x] actor_id
  * [x] actor_source_table (`users/administrators/system`)
  * [x] account_id (nullable for global admin actions)
  * [x] action
  * [x] target_type
  * [x] target_id
  * [x] access_reason (nullable)
  * [x] ip_address
  * [x] user_agent
  * [x] metadata (JSON, redacted)
  * [x] created_at
* [x] Create `data_access_logs` table for sensitive read operations
* [x] Create `consents` table (policy_version + accepted_at)
* [x] Create `data_subject_requests` table (export/delete/restrict)

---

# 🔐 3. Authentication and Authorization

## Identity Flows

* [x] Support secure signup/login for users
* [x] Add invite acceptance flow for collaborators:
  * [x] invitation email
  * [x] token verification
  * [x] signup/login required before acceptance
  * [x] one-time token invalidation
  * [x] email verification required (feature-flag rollout available)

---

## Authorization Rules

* [x] Implement Laravel Policies for Forms, Enquiries, Notes, Replies, Insights
* [x] Enforce account-scoped access in query layer (global scope or repository layer)
* [x] Deny-by-default for all protected routes
* [x] Add gate checks for account-scoped data access
* [x] Add role checks for collaborator capabilities
* [x] Add dedicated admin guard/provider backed by `administrators` table
* [x] Keep admin auth/session separate from user auth/session

---

## Security-First Collaboration

* [x] Limit who can invite collaborators (`owner` only by default)
* [x] Prevent privilege escalation (member cannot grant owner role)
* [x] Require expiration and revocation support for invitations
* [x] Notify owner when invites are accepted/revoked
* [x] Detect and block cross-account access attempts

---

# 🔗 4. HQ Integration Layer

## API Client Service

* [x] Create `HQService` class

---

## Endpoints to implement

* [x] Validate application
  * `POST /hq/api/validate-application`
* [x] Get subscription
  * `GET /hq/api/subscription`
* [x] Send analytics
  * `POST /hq/api/events`
* [x] Send feedback
  * `POST /hq/api/feedback`

---

## Caching

* [x] Cache application validation (short TTL)
* [x] Cache subscription plan

---

# 🔌 5. Forms Module (Embed System)

## Create Form

* [x] UI: Create Form
* [x] Call HQ to create application
* [x] Store `application_id` and `account_id`
* [x] Restrict create/edit/delete form actions by membership role

---

## Token Generation

* [x] Generate secure public token (`frm_xxx`)
* [x] Ensure tokens are unguessable and non-sequential

---

## Public Form Route

* [x] `GET /form/{token}`
* [x] Validate:
  * [x] form exists
  * [x] is_active

---

## Form UI (iframe)

* [x] Fields:
  * [x] name
  * [x] email
  * [x] subject
  * [x] message
* [x] Add honeypot field
* [x] Add privacy notice and consent checkbox (where required)

---

# 📨 6. Submission Handling (Critical)

## Endpoint

* [x] `POST /form/{token}/submit`

---

## Logic

* [x] Validate input
* [x] Honeypot check
* [x] Rate limit (per token/IP)
* [x] Record consent where policy requires

---

## Resolve Ownership

* [x] `form -> application_id -> account_id`

---

## Store Enquiry

* [x] Insert into `enquiries`
* [x] Store minimal PII required for purpose (data minimization)

---

## Trigger Side Effects

* [x] Send notification email
* [x] Send analytics event to HQ (exclude raw PII unless strictly required)

---

## Response

* [x] Success message (JSON / HTML)

---

# 🔔 7. Notifications

* [x] Setup email service (Postmark/SMTP)
* [x] Create notification job for new enquiry
* [x] Create invitation email job for collaborator invites
* [x] Email templates contain least-sensitive data necessary

---

# 📬 8. Inbox Module (Dashboard)

## List View

* [x] Route: `/inbox`
* [x] Show:
  * [x] name
  * [x] email
  * [x] subject
  * [x] status
  * [x] date
* [x] Restrict rows to active account context

---

## Detail View

* [x] Full enquiry content
* [x] Status update buttons (`new -> contacted -> closed`)
* [x] Restrict visibility by role and account scope

---

## Status Logic

* [x] On `contacted`, set `contacted_at`
* [x] On `closed`, set `closed_at`

---

# 👥 9. Collaborators Module

## Manage Collaborators

* [x] Route: `/settings/collaborators`
* [x] List active members and pending invites
* [x] Invite collaborator by email and role
* [x] Resend/revoke invitation
* [x] Remove collaborator from account

---

## Security Controls

* [x] Invite links are signed, expiring, and single-use
* [x] Invitation tokens are hashed at rest
* [x] Audit all collaborator lifecycle actions
* [x] Alert on repeated invalid token or cross-tenant access attempts

---

# 📝 10. Notes Module (Pro)

* [x] Add note to enquiry
* [x] Display notes in detail view
* [x] Restrict access based on plan and collaborator role

---

# 📊 11. Insights Module (Capture-Owned)

## Queries

* [x] Total enquiries
* [x] Enquiries per day
* [x] Conversion rate
* [x] Avg response time

---

## Service Layer

* [x] Create `InsightsService`

---

## API / Controller

* [x] `GET /insights`
* [x] Enforce account-level isolation for all metrics

---

## UI

* [x] Charts:
  * [x] volume over time
  * [x] funnel
  * [x] response time

---

## Plan Gating

* [x] Only show for Pro

---

# 🔌 12. Integration Page (Install UX)

* [x] Route: `/integrations`
* [x] Show:
  * [x] form name
  * [x] domain
  * [x] embed code

---

## Embed Code

```html
<iframe
  src="https://capture.gracesoft.dev/form/frm_xxx"
  width="100%"
  height="500">
</iframe>
```

---

## Test Feature

* [x] Send test enquiry button

---

# 🔐 13. Security Layer

* [x] Public token must be unguessable
* [x] Rate limiting (global + per form)
* [x] Honeypot validation
* [x] CSRF, XSS, and output escaping checks
* [x] Encrypt sensitive fields at rest where applicable
* [x] Rotate secrets and integration keys on schedule
* [x] Optional domain validation via headers

---

# ⚖️ 14. GDPR and PDPA Compliance

## Data Governance

* [x] Build data inventory and classify personal data fields
* [x] Define lawful basis and purpose for each collected field
* [x] Enforce data minimization in forms, analytics, and logs
* [x] Define and enforce retention and deletion schedules

---

## Data Subject Rights

* [x] Export personal data on verified request
* [x] Delete or anonymize personal data on valid request
* [x] Restrict processing where required
* [x] Track request lifecycle and completion evidence

---

## Administrator Monitoring (Compliant)

* [x] Provide admin monitoring pages with aggregate-first views
* [x] Require access reason for sensitive drill-down views
* [x] Mask sensitive data by default in admin UI
* [x] Log every admin read/write of customer data
* [x] Persist daily verification-block telemetry snapshots for trend monitoring
* [x] Run periodic admin access review and recertification

---

# 💳 15. Plan Enforcement (via HQ)

* [x] Fetch subscription from HQ
* [x] Cache plan

---

## Enforce

### Starter

* [x] Limit forms (optional)
* [x] Disable notes
* [x] Disable insights

---

### Growth

* [x] Enable core features
* [x] Enable collaborator invites with role limits

---

### Pro

* [x] Enable notes
* [x] Enable insights
* [x] Enable advanced audit and compliance views

---

# 💬 16. Feedback Integration

* [x] Add Contact Support button
* [x] Send feedback to HQ `/api/feedback`

---

# 🛡️ 17. Admin Module (Platform)

## Admin Capabilities

* [x] Global metrics dashboard (without unnecessary raw personal data)
* [x] Tenant health monitoring
* [x] Abuse/spam detection queue
* [x] Compliance event dashboard (consent, DSR, deletions)

---

## Admin Safeguards

* [x] Enforce least-privilege admin roles
* [x] Require MFA for admin accounts
* [x] Harden sessions and shorten idle timeout
* [x] Store admin identities only in `administrators` table (not in user/collaborator tables)
* [x] Define break-glass flow with enhanced logging and approval

---

# 🧪 18. Testing Checklist

## Core Flow

* [x] Submit enquiry is stored
* [x] Notification is sent
* [x] Enquiry appears in inbox

---

## Access Control and Collaboration

* [x] User cannot access another account data
* [x] Collaborator invite/accept/revoke flow works
* [x] Cross-tenant IDOR attempts are blocked
* [x] Role permissions enforced for every protected action

---

## Security

* [x] Honeypot blocks spam
* [x] Rate limiting works
* [x] Signed invite token validation works
* [x] Audit logs are written for sensitive actions

---

## Compliance

* [x] Data export request flow works
* [x] Data deletion/anonymization flow works
* [x] Retention job removes expired data
* [x] Admin access logs include reason and actor

---

## HQ Sync and Plans

* [x] Analytics event sent
* [x] Subscription fetched
* [x] Starter restrictions enforced
* [x] Pro features unlocked

---

# 🚀 19. Nice-to-Have (Post-MVP)

* [x] Redirect after submit
* [x] Custom themes
* [x] Custom fields
* [x] File uploads
* [x] Webhooks
* [x] SCIM or SSO for larger teams

---

# 🧭 Final Build Order (Updated)

## Phase 1 (Security Core)

* [x] Auth and account scoping
* [x] Forms and public submission
* [x] Enquiry storage
* [x] Baseline audit logging

---

## Phase 2 (Core Product)

* [x] Inbox
* [x] Notifications
* [x] Collaboration invites and membership controls

---

## Phase 3 (Platform Integration)

* [x] HQ integration
* [x] Analytics events
* [x] Plan enforcement

---

## Phase 4 (Advanced)

* [x] Insights
* [x] Notes
* [x] Admin monitoring and compliance workflows

---

# ✨ Final Mental Model

```plaintext
Form (iframe)
   ↓
Capture
   ↓
Store enquiry under strict account boundary
   ↓
Allow only authorized users/collaborators
   ↓
Monitor with compliant administrator controls
   ↓
Send lightweight events to HQ
```
