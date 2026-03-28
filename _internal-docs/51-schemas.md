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

# 🧠 Key Rules

* ❌ Never expose `id` externally
* ✅ Always use `uuid` in APIs/routes
* ✅ Always filter by `account_id`
* ❌ Do NOT store:

  * users
  * subscriptions
  * API keys

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
