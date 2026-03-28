# 🧭 1. 🟩 Capture — Blade UI Components Checklist

## 🧱 Layout & Structure

* [ ] `layouts.app`
* [ ] `layouts.auth`
* [ ] `layouts.embed` *(for iframe form — minimal)*

---

## 📥 Inbox (Core UI)

### List Page `/inbox`

* [ ] `components.inbox.table`
* [ ] `components.inbox.row`
* [ ] `components.inbox.filters`

  * status filter
  * search input
* [ ] `components.inbox.status-badge`

---

### Detail Page `/enquiries/{uuid}`

* [ ] `components.enquiry.header`

  * name
  * email
  * subject
  * status

* [ ] `components.enquiry.timeline` ⭐ (important)

* [ ] `components.enquiry.message-card`

---

## 💬 Replies (Growth)

* [ ] `components.replies.list`

* [ ] `components.replies.item`

  * sender
  * timestamp
  * content

* [ ] `components.replies.form`

  * textarea
  * send button
  * loading state

* [ ] `components.replies.empty-state`

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

* [ ] `components.notes.pinned-section`

* [ ] `components.notes.upgrade-banner` 🔒

---

## 📊 Insights (Pro)

* [ ] `components.insights.card`

* [ ] `components.insights.metric`

  * total enquiries
  * conversion rate

* [ ] `components.insights.chart-line`

* [ ] `components.insights.chart-funnel`

* [ ] `components.insights.upgrade-banner`

---

## 🔌 Integration Page `/integrations`

* [ ] `components.integration.card`
* [ ] `components.integration.embed-code`
* [ ] `components.integration.copy-button`
* [ ] `components.integration.test-button`

---

## 🧾 Form Embed UI `/form/{token}`

* [ ] `components.form.wrapper`
* [ ] `components.form.input`
* [ ] `components.form.textarea`
* [ ] `components.form.button`
* [ ] `components.form.success-state`
* [ ] `components.form.error-state`

---

## 💬 Feedback (to HQ)

* [ ] `components.feedback.button`
* [ ] `components.feedback.modal`
* [ ] `components.feedback.form`

---

## 🔒 Plan Gating

* [ ] `components.upgrade.banner`
* [ ] `components.upgrade.modal`
* [ ] `components.upgrade.badge`

---

---

# 🟦 2. HQ Admin Panel — Blade UI Components

This is your **internal control system**

---

## 🧱 Layout

* [ ] `layouts.admin`
* [ ] `components.admin.sidebar`
* [ ] `components.admin.navbar`

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

---

## 💬 Feedback (Support Inbox)

* [ ] `components.feedback.table`
* [ ] `components.feedback.detail`
* [ ] `components.feedback.status-badge`

---

## 🛠️ Diagnostics

* [ ] `components.diagnostics.log-table`
* [ ] `components.diagnostics.error-view`

---

---

# 🧩 3. Shared Components (Reusable Everywhere)

These save you a TON of time.

---

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

## 🧾 Feedback / States

* [ ] `components.ui.empty-state`
* [ ] `components.ui.loading`
* [ ] `components.ui.toast`
* [ ] `components.ui.alert`

---

## 🏷️ Status & Labels

* [ ] `components.ui.status-badge`
* [ ] `components.ui.tag`

---

## 📋 Utility

* [ ] `components.ui.copy-to-clipboard`
* [ ] `components.ui.date-time`
* [ ] `components.ui.avatar`

---

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
```

---

## Inbox Page

```plaintext
Filters
↓
Table
↓
Pagination
```

---

## Form Embed

```plaintext
Card
↓
Fields
↓
Submit Button
↓
Success / Error
```

---

---

# 🔥 5. Priority Build Order (Important)

## Phase 1 (MVP UI)

* [ ] Form components
* [ ] Inbox table
* [ ] Enquiry detail
* [ ] Reply form

---

## Phase 2

* [ ] Integration page
* [ ] Notifications UI

---

## Phase 3

* [ ] Notes
* [ ] Insights

---

## Phase 4

* [ ] Admin panel

---

---

# ✨ Final Insight

If you build components this way:

You get:

* Reusable system
* Clean separation (Capture vs HQ)
* Faster iteration
* Consistent UX

---

# 💬 My Honest Take

You’re now thinking in:

> **design system + product architecture**

Which is exactly what makes a SaaS feel:

> polished, scalable, and “real”
