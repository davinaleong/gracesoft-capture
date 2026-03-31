# 🧭 1. 🟩 Capture — Blade UI Components Checklist

## 🧱 Layout and Structure

* [x] `layouts.app`
* [ ] `layouts.auth`
* [ ] `layouts.embed` *(for iframe form — minimal)*
* [ ] `components.layout.account-context-switcher` *(active workspace/account context)*

---

## 📥 Inbox (Core UI)

### List Page `/inbox`

* [x] `components.inbox.table`
* [x] `components.inbox.row`
* [x] `components.inbox.filters`

  * status filter
  * [x] search input

* [x] `components.inbox.status-badge`
* [x] `components.inbox.account-context-badge`
* [x] `components.inbox.empty-state`

---

### Detail Page `/enquiries/{uuid}`

* [x] `components.enquiry.header`

  * name
  * email
  * subject
  * status

* [x] `components.enquiry.timeline` ⭐
* [x] `components.enquiry.message-card`
* [x] `components.enquiry.access-denied-state` *(for non-member/non-owner)*

---

## 💬 Replies (Growth)

* [x] `components.replies.list`
* [x] `components.replies.item`

  * sender
  * timestamp
  * content

* [x] `components.replies.sender-badge`

  * user
  * collaborator
  * administrator
  * system

* [x] `components.replies.form`

  * textarea
  * send button
  * loading state

* [x] `components.replies.empty-state`

---

## 👥 Collaborators `/settings/collaborators`

* [x] `components.collaborators.members-table`
* [x] `components.collaborators.member-row`
* [x] `components.collaborators.invites-table`
* [x] `components.collaborators.invite-form`

  * email input
  * role select (`owner/member/viewer`)
  * invite button

* [x] `components.collaborators.role-badge`
* [x] `components.collaborators.invite-status-badge`
* [x] `components.collaborators.revoke-button`
* [x] `components.collaborators.resend-button`
* [x] `components.collaborators.owner-only-banner` *(owner permission hint)*

---

## 🧠 Notes (Pro)

* [ ] `components.notes.list`
* [ ] `components.notes.item`

  * author
  * timestamp
  * tags
  * pinned badge

* [ ] `components.notes.form`

  * textarea
  * tag input
  * reminder date

* [ ] `components.notes.visibility-badge`

  * internal
  * external

* [ ] `components.notes.pinned-section`
* [ ] `components.notes.upgrade-banner` 🔒

---

## 📊 Insights (Pro)

* [x] `components.insights.card`
* [x] `components.insights.metric`

  * total enquiries
  * conversion rate

* [x] `components.insights.chart-line`
* [x] `components.insights.chart-funnel`
* [x] `components.insights.upgrade-banner`

---

## 🔌 Integration Page `/integrations`

* [x] `components.integration.card`
* [x] `components.integration.embed-code`
* [x] `components.integration.copy-button`
* [x] `components.integration.test-button`

---

## 🧾 Form Embed UI `/form/{token}`

* [x] `components.form.wrapper`
* [x] `components.form.input`
* [x] `components.form.textarea`
* [x] `components.form.button`
* [x] `components.form.success-state`
* [x] `components.form.error-state`
* [x] `components.form.consent-notice` *(GDPR/PDPA)*

---

## 💬 Feedback (to HQ)

* [ ] `components.feedback.button`
* [ ] `components.feedback.modal`
* [ ] `components.feedback.form`

---

## 🔒 Plan Gating and Security States

* [ ] `components.upgrade.banner`
* [ ] `components.upgrade.modal`
* [ ] `components.upgrade.badge`
* [ ] `components.security.access-denied`
* [ ] `components.security.cross-tenant-warning`

---

# 🟦 2. HQ Admin Panel — Blade UI Components

This is your internal control system.

---

## 🧱 Layout

* [ ] `layouts.admin`
* [ ] `components.admin.sidebar`
* [ ] `components.admin.navbar`
* [x] `components.admin.session-badge` *(shows administrator session)*

---

## 🛡️ Administrators (Separate Identity Table)

* [ ] `components.administrators.table`
* [ ] `components.administrators.detail`
* [ ] `components.administrators.status-badge`
* [ ] `components.administrators.mfa-badge`
* [x] `components.administrators.break-glass-alert`

---

## 👤 Accounts

* [ ] `components.accounts.table`
* [ ] `components.accounts.detail`
* [ ] `components.accounts.subscription-badge`

---

## 🔑 Applications

* [ ] `components.applications.table`
* [ ] `components.applications.detail`
* [ ] `components.applications.keys`

  * masked API key
  * reveal toggle

---

## 💳 Billing

* [ ] `components.billing.subscription-card`
* [ ] `components.billing.invoice-table`
* [ ] `components.billing.payment-status`

---

## 📊 Analytics (Platform)

* [ ] `components.analytics.metric`
* [ ] `components.analytics.event-table`
* [ ] `components.analytics.account-scope-pill`

---

## ⚖️ Compliance Monitoring

* [x] `components.compliance.audit-log-table`
* [x] `components.compliance.data-access-log-table`
* [x] `components.compliance.access-reason-modal`
* [x] `components.compliance.pii-mask-toggle` *(permission-gated)*
* [x] `components.compliance.dsr-request-table`
* [ ] `components.compliance.retention-job-status`

---

## 💬 Feedback (Support Inbox)

* [ ] `components.feedback.table`
* [ ] `components.feedback.detail`
* [ ] `components.feedback.status-badge`

---

## 🛠️ Diagnostics

* [ ] `components.diagnostics.log-table`
* [ ] `components.diagnostics.error-view`
* [x] `components.diagnostics.security-events-table`

---

# 🧩 3. Shared Components (Reusable Everywhere)

## 🧱 UI Basics

* [ ] `components.ui.card`
* [ ] `components.ui.button`
* [ ] `components.ui.input`
* [ ] `components.ui.textarea`
* [ ] `components.ui.select`
* [ ] `components.ui.badge`
* [ ] `components.ui.modal`
* [ ] `components.ui.dropdown`

---

## 🧾 Feedback and States

* [ ] `components.ui.empty-state`
* [ ] `components.ui.loading`
* [ ] `components.ui.toast`
* [ ] `components.ui.alert`
* [ ] `components.ui.access-denied`

---

## 🏷️ Status and Labels

* [ ] `components.ui.status-badge`
* [ ] `components.ui.tag`
* [ ] `components.ui.role-badge`
* [ ] `components.ui.consent-badge`

---

## 📋 Utility

* [ ] `components.ui.copy-to-clipboard`
* [ ] `components.ui.date-time`
* [ ] `components.ui.avatar`
* [ ] `components.ui.pii-mask`

---

# 🧠 4. Page-Level Composition (How It Comes Together)

## Enquiry Page

```plaintext
Header
↓
Customer Message
↓
Replies (Growth)
↓
Notes (Pro)
↓
Timeline (merged view)
↓
Active Account Scope Indicator
```

---

## Inbox Page

```plaintext
Filters
↓
Table
↓
Pagination
↓
Tenant Boundary Preserved
```

---

## Collaborators Page

```plaintext
Members
↓
Pending Invites
↓
Invite Collaborator
↓
Revoke / Resend Actions
```

---

## Admin Monitoring Page

```plaintext
Aggregate Metrics
↓
Audit and Data Access Logs
↓
Reason Capture for Sensitive Drill-Down
↓
Masked PII by Default
```

---

## Form Embed

```plaintext
Card
↓
Fields
↓
Consent Notice
↓
Submit Button
↓
Success / Error
```

---

# 🔥 5. Priority Build Order (Updated)

## Phase 1 (MVP UI + Security Baseline)

* [ ] Form components
* [ ] Inbox table
* [ ] Enquiry detail
* [ ] Reply form
* [ ] Access denied and account scope indicators

---

## Phase 2

* [ ] Integration page
* [ ] Notifications UI
* [ ] Collaborators UI (invite, revoke, role badges)

---

## Phase 3

* [ ] Notes
* [ ] Insights
* [ ] Consent and data request status components

---

## Phase 4

* [ ] Admin panel
* [ ] Administrators management UI
* [ ] Compliance monitoring UI

---

# ✨ Final Insight

If you build components this way, you get:

* User-scoped data UX by default
* Secure collaboration flows
* Clear separation of user vs administrator identities
* GDPR/PDPA-friendly monitoring patterns
* Reusable and consistent UI system
