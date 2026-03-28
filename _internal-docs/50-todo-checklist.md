# 🟩 1. Foundation Setup

## ✅ Project Setup

* [ ] Create Capture app (Laravel)
* [ ] Configure separate **Capture DB**
* [ ] Setup env:

  * [ ] `HQ_API_URL`
  * [ ] `HQ_API_KEY` (internal service auth)

---

## ✅ Base Structure

* [ ] Create modules:

  * [ ] Forms
  * [ ] Enquiries
  * [ ] Inbox
  * [ ] Notifications
  * [ ] Insights
  * [ ] Integration

---

# 🗄️ 2. Database (Core Models)

## Forms

* [ ] Create `forms` table

  * [ ] id (UUID)
  * [ ] account_id
  * [ ] application_id
  * [ ] public_token (`frm_xxx`)
  * [ ] name
  * [ ] settings (JSON)
  * [ ] is_active

---

## Enquiries

* [ ] Create `enquiries` table

  * [ ] id (UUID)
  * [ ] form_id
  * [ ] account_id
  * [ ] application_id
  * [ ] name
  * [ ] email
  * [ ] subject
  * [ ] message
  * [ ] status (`new/contacted/closed`)
  * [ ] contacted_at (nullable)
  * [ ] closed_at (nullable)
  * [ ] metadata (JSON)

---

## Notes (Pro)

* [ ] Create `notes` table

  * [ ] enquiry_id
  * [ ] user_id
  * [ ] content

---

---

# 🔐 3. HQ Integration Layer

## API Client Service

* [ ] Create `HQService` class

---

## Endpoints to implement

* [ ] Validate application

  * `POST /hq/api/validate-application`

* [ ] Get subscription

  * `GET /hq/api/subscription`

* [ ] Send analytics

  * `POST /hq/api/events`

* [ ] Send feedback

  * `POST /hq/api/feedback`

---

## Caching (important)

* [ ] Cache:

  * [ ] application validation (short TTL)
  * [ ] subscription plan

---

---

# 🔌 4. Forms Module (Embed System)

## Create Form

* [ ] UI: “Create Form”
* [ ] Call HQ → create application
* [ ] Store:

  * application_id
  * account_id

---

## Token Generation

* [ ] Generate secure `public_token` (`frm_xxx`)

---

## Public Form Route

* [ ] `GET /form/{token}`
* [ ] Validate:

  * form exists
  * is_active

---

## Form UI (iframe)

* [ ] Fields:

  * [ ] name
  * [ ] email
  * [ ] subject
  * [ ] message
* [ ] Add honeypot field

---

---

# 📨 5. Submission Handling (Critical)

## Endpoint

* [ ] `POST /form/{token}/submit`

---

## Logic

* [ ] Validate input
* [ ] Honeypot check
* [ ] Rate limit (per token/IP)

---

## Resolve Ownership

* [ ] form → application_id → account_id

---

## Store Enquiry

* [ ] Insert into `enquiries`

---

## Trigger Side Effects

* [ ] Send notification (email)
* [ ] Send analytics event → HQ

---

## Response

* [ ] Success message (JSON / HTML)

---

---

# 🔔 6. Notifications

* [ ] Setup email service (Postmark/SMTP)
* [ ] Create notification job:

  * [ ] On new enquiry
* [ ] Email content:

  * name
  * email
  * subject
  * message

---

---

# 📬 7. Inbox Module (Dashboard)

## List View

* [ ] Route: `/inbox`
* [ ] Show:

  * name
  * email
  * subject
  * status
  * date

---

## Detail View

* [ ] Full enquiry content
* [ ] Status update buttons:

  * new → contacted → closed

---

## Status Logic

* [ ] On “contacted”:

  * set `contacted_at`
* [ ] On “closed”:

  * set `closed_at`

---

---

# 📝 8. Notes Module (Pro)

* [ ] Add note to enquiry
* [ ] Display notes in detail view
* [ ] Restrict access based on plan

---

---

# 📊 9. Insights Module (Capture-Owned)

## Queries

* [ ] Total enquiries
* [ ] Enquiries per day
* [ ] Conversion rate
* [ ] Avg response time

---

## Service Layer

* [ ] Create `InsightsService`

---

## API / Controller

* [ ] `GET /insights`

---

## UI

* [ ] Charts:

  * volume over time
  * funnel
  * response time

---

## Plan Gating

* [ ] Only show for Pro

---

---

# 🔌 10. Integration Page (Install UX)

* [ ] Route: `/integrations`
* [ ] Show:

  * form name
  * domain
  * embed code

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

* [ ] “Send test enquiry” button

---

---

# 🔐 11. Security Layer

* [ ] Public token must be unguessable
* [ ] Rate limiting (global + per form)
* [ ] Honeypot validation
* [ ] Optional:

  * [ ] domain validation via headers

---

---

# 💳 12. Plan Enforcement (via HQ)

* [ ] Fetch subscription from HQ
* [ ] Cache plan

---

## Enforce

### Starter

* [ ] Limit forms (optional)
* [ ] Disable notes
* [ ] Disable insights

---

### Growth

* [ ] Enable core features

---

### Pro

* [ ] Enable:

  * notes
  * insights

---

---

# 💬 13. Feedback Integration

* [ ] Add “Contact Support” button
* [ ] Form → send to HQ `/api/feedback`

---

---

# 🧪 14. Testing Checklist

## Core Flow

* [ ] Submit enquiry → stored
* [ ] Notification sent
* [ ] Appears in inbox

---

## Security

* [ ] Honeypot blocks spam
* [ ] Rate limiting works

---

## HQ Sync

* [ ] Analytics event sent
* [ ] Subscription fetched

---

## Plans

* [ ] Starter restrictions enforced
* [ ] Pro features unlocked

---

---

# 🚀 15. Nice-to-Have (Post-MVP)

* [ ] Redirect after submit
* [ ] Custom themes
* [ ] Custom fields
* [ ] File uploads
* [ ] Webhooks

---

---

# 🧭 Final Build Order (Simplified)

## Phase 1 (Core)

* Forms
* Public form
* Submission
* Enquiry storage

---

## Phase 2

* Inbox
* Notifications

---

## Phase 3

* HQ integration
* Analytics events

---

## Phase 4

* Insights
* Notes

---

---

# ✨ Final Mental Model

```plaintext
Form (iframe)
   ↓
Capture
   ↓
Store enquiry
   ↓
Notify user
   ↓
Compute insights
   ↓
Send lightweight events → HQ
```

---

# 💬 My Honest Take

This is now:

* **Clean**
* **Secure**
* **Buildable solo**
* **Launch-ready**
