# 🟩 `replies` Table — Schema (Final)

## 🎯 Purpose

Store replies to an enquiry (internal notes or external responses)

---

## 🧱 Schema

```sql
replies
- id (BIGINT, PK)
- uuid (UUID, unique, indexed)

- enquiry_id (BIGINT, indexed)

- account_id (UUID, indexed)

- sender_type (enum: user, system, external)
- sender_id (UUID, nullable)   -- HQ user_id (if internal)

- email (string, nullable)     -- for external sender

- content (text)

- is_internal (boolean, default false)

- metadata (json, nullable)

- created_at
- updated_at
```

---

# 🧠 Field Breakdown

## 🔗 `enquiry_id`

* FK → `enquiries.id`
* Core relationship

---

## 👤 `sender_type`

| Type       | Meaning                     |
| ---------- | --------------------------- |
| `user`     | Logged-in user (from HQ)    |
| `external` | Customer replying via email |
| `system`   | Auto-generated              |

---

## 🆔 `sender_id`

* UUID from HQ
* Only used when `sender_type = user`

---

## 📧 `email`

* Used when `sender_type = external`
* Stores customer email

---

## 💬 `content`

* The actual reply message

---

## 🔒 `is_internal`

| Value | Meaning                                 |
| ----- | --------------------------------------- |
| true  | Internal note (not visible to customer) |
| false | External reply                          |

---

## 🧾 `metadata` (future-proof)

Example:

```json
{
  "source": "email",
  "message_id": "<abc123@mail>",
  "in_reply_to": "<xyz@mail>"
}
```

👉 Enables:

* Email threading
* Reply tracking
* integrations later

---

# 🔗 Relationships

```plaintext
enquiries.id → replies.enquiry_id
```

---

# 🧱 Laravel Migration (Clean)

```php
Schema::create('replies', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();

    $table->unsignedBigInteger('enquiry_id')->index();

    $table->uuid('account_id')->index();

    $table->enum('sender_type', ['user', 'external', 'system']);
    $table->uuid('sender_id')->nullable();

    $table->string('email')->nullable();

    $table->text('content');

    $table->boolean('is_internal')->default(false);

    $table->json('metadata')->nullable();

    $table->timestamps();
});
```

---

# 🧠 How This Fits Your System

## Example Flow

### 1. New enquiry

* Stored in `enquiries`

---

### 2. User replies from dashboard

```plaintext
sender_type = user
sender_id = user UUID
is_internal = false
```

---

### 3. Internal note

```plaintext
sender_type = user
is_internal = true
```

---

### 4. Future: email reply from customer

```plaintext
sender_type = external
email = customer@email.com
```

---

# 🔥 Optional Enhancement (Later)

## Add:

```sql
parent_reply_id (BIGINT, nullable)
```

👉 Enables:

* Threaded conversations

---

# ⚠️ Important Rules

* ❌ Never join on UUID → use `enquiry_id`
* ❌ Don’t store full user data → only reference `sender_id`
* ✅ Always filter by `account_id`

---

# ✨ Final Summary

You now have:

* Enquiries → root object
* Notes → internal-only
* Replies → conversation layer

---

# 💬 My Honest Take

This is a **very smart move**.

With replies, your product evolves into:

> **a lightweight CRM / communication tool**
