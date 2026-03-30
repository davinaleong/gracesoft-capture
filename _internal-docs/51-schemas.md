# 🟩 GraceSoft Capture — Database Reference

## 🎯 Design Principles

* **Hybrid IDs**

  * `id` (BIGINT) → internal joins
  * `uuid` (UUID) → external/API usage

* **Separation of concerns**

  * Capture DB → product data only
  * HQ DB → accounts, billing, analytics

* **Multi-tenancy**

  * All core tables include `account_id` (UUID from HQ)

---

# 🧱 Core Tables

---

## 📄 `forms`

**Purpose:** Embeddable iframe forms

```sql
id (BIGINT, PK)
uuid (UUID, unique)

account_id (UUID, indexed)
application_id (UUID, indexed)

name (string)
public_token (string, unique)

is_active (boolean)
settings (json, nullable)

created_at
updated_at
```

### Notes

* `public_token` → used in iframe (`/form/{token}`)
* `uuid` → internal API reference

---

---

## 📥 `enquiries`

**Purpose:** Captured form submissions

```sql
id (BIGINT, PK)
uuid (UUID, unique)

form_id (BIGINT, indexed)
account_id (UUID, indexed)
application_id (UUID, indexed)

name (string)
email (string, indexed)
subject (string)
message (text)

status (enum: new, contacted, closed)

contacted_at (timestamp, nullable)
closed_at (timestamp, nullable)

metadata (json, nullable)

created_at (indexed)
updated_at
```

### Relationships

* `form_id → forms.id`

### Lifecycle

```plaintext
created_at → contacted_at → closed_at
```

---

---

## 📝 `notes` (Pro)

**Purpose:** Internal notes per enquiry

```sql
id (BIGINT, PK)
uuid (UUID, unique)

enquiry_id (BIGINT, indexed)
user_id (UUID)  -- from HQ

content (text)

created_at
updated_at
```

### Relationships

* `enquiry_id → enquiries.id`

---

---

## 👥 `account_memberships`

**Purpose:** Tenant-scoped access control for account owners and collaborators

```sql
id (BIGINT, PK)

account_id (UUID, indexed)
user_id (UUID, indexed)

role (enum: owner, member, viewer)

invited_by_user_id (UUID, nullable)
joined_at (timestamp, nullable)
removed_at (timestamp, nullable)

created_at
updated_at
```

### Notes

* Enforces that users can access only their account data
* Unique key recommended: `(account_id, user_id)`

---

---

## ✉️ `account_invitations`

**Purpose:** Security-first collaborator invitation flow

```sql
id (BIGINT, PK)

account_id (UUID, indexed)
email (string, indexed)

role (enum: owner, member, viewer)
invite_token (string, unique)   -- store hash only

expires_at (timestamp)
accepted_at (timestamp, nullable)
revoked_at (timestamp, nullable)

created_at
updated_at
```

### Notes

* Invite tokens must be signed, expiring, and single-use
* Only `owner` should invite by default (policy-level control)

---

---

## 🛡️ `administrators`

**Purpose:** Platform operators for monitoring/support, stored separately from user/collaborator identities

```sql
id (BIGINT, PK)
uuid (UUID, unique)

email (string, unique)
display_name (string)

status (enum: active, suspended)
mfa_enabled (boolean)

last_login_at (timestamp, nullable)

created_at
updated_at
```

### Notes

* This table is separate from account users/collaborators
* Admin access must be audited with reason capture for sensitive reads

---

---

## 🧾 `audit_logs`

**Purpose:** Immutable-style audit trail for security and compliance (GDPR/PDPA)

```sql
id (BIGINT, PK)

actor_type (enum: user, administrator, system)
actor_id (UUID, nullable)
actor_source_table (enum: users, administrators, system)

account_id (UUID, nullable)

action (string)
target_type (string)
target_id (string)

access_reason (string, nullable)
metadata (json, nullable)   -- redacted

ip_address (string, nullable)
user_agent (string, nullable)

created_at
```

### Notes

* Required for admin monitoring and access recertification
* Sensitive read access should include `access_reason`

---

---

## 🧪 `form_submissions` (Optional)

**Purpose:** Track submission attempts / debugging

```sql
id (BIGINT, PK)
uuid (UUID, unique)

form_id (BIGINT, indexed)

success (boolean)

ip_address (string)
user_agent (string)

error (string, nullable)

created_at
```

---

# 🔗 Relationships Summary

```plaintext
forms.id        → enquiries.form_id
enquiries.id    → notes.enquiry_id
accounts/users  → account_memberships
account_memberships.account_id → forms/enquiries/account_id
administrators  → audit_logs (when actor_type = administrator)
```

---

# 🔐 External vs Internal IDs

| Usage        | Field          |
| ------------ | -------------- |
| Joins        | `id`           |
| API / URLs   | `uuid`         |
| Public embed | `public_token` |

---

# ⚡ Indexing Strategy

## forms

* `uuid` (unique)
* `public_token` (unique)
* `account_id`

---

## enquiries

* `uuid` (unique)
* `form_id`
* `account_id`
* `email`
* `created_at`

---

## notes

* `uuid` (unique)
* `enquiry_id`

---

## account_memberships

* `account_id`
* `user_id`
* unique (`account_id`, `user_id`)

---

## account_invitations

* `account_id`
* `email`
* `invite_token` (unique)
* `expires_at`

---

## administrators

* `uuid` (unique)
* `email` (unique)

---

## audit_logs

* `actor_type`
* `actor_id`
* `account_id`
* `created_at`

---

# 🧠 Key Rules

* ❌ Never expose `id` externally
* ✅ Always use `uuid` in APIs/routes
* ✅ Always filter by `account_id`
* ❌ Do NOT store:

  * account user credentials/profiles
  * collaborator credentials/profiles
  * subscriptions
  * API keys
* ✅ Store platform administrators in a dedicated `administrators` table

---

# 🔄 Data Flow (Quick Reference)

```plaintext
iframe form
   ↓
POST /form/{token}/submit
   ↓
resolve → form → account_id
   ↓
store → enquiries
   ↓
notify + analytics (HQ)
```

---

# ✨ Summary

* Clean separation (Capture vs HQ)
* Fast joins (BIGINT IDs)
* Secure exposure (UUID + tokens)
* Multi-tenant ready
