# 🧠 Mental Model (keep this in your head)

* **User** → login identity
* **Account (Workspace)** → owns the subscription
* **Plan** → your internal representation of Stripe price
* **Subscription** → mirrors Stripe subscription

👉 Stripe is source of truth for billing
👉 Your DB is source of truth for access control

---

# 🧱 Minimal Schema (Stripe-friendly)

## 1. `users`

```sql
id (uuid) PK
email (string) UNIQUE
password (string)
created_at
updated_at
```

---

## 2. `accounts` (aka workspaces)

```sql
id (uuid) PK
name (string)

owner_user_id (uuid) FK -> users.id

stripe_customer_id (string) NULL

created_at
updated_at
```

👉 One Stripe customer per account

---

## 3. `account_users` (collaborators)

```sql
id (uuid) PK

account_id (uuid) FK -> accounts.id
user_id (uuid) FK -> users.id

role (enum: 'owner', 'member')

created_at
```

👉 This controls:

* 1 user (Free)
* 5 users (Growth)
* 20 users (Pro)

---

## 4. `plans`

👉 Your internal mapping layer (VERY important)

```sql
id (uuid) PK

name (string) -- Free, Growth, Pro
slug (string) UNIQUE -- free, growth, pro

stripe_price_id (string) NULL
stripe_product_id (string) NULL

max_users (int)
max_items (int) NULL
max_replies (int) NULL

created_at
```

💡 Notes:

* Free plan → `stripe_price_id = NULL`
* Paid plans → must match Stripe Price ID

---

## 5. `subscriptions`

👉 Mirrors Stripe subscription

```sql
id (uuid) PK

account_id (uuid) FK -> accounts.id
plan_id (uuid) FK -> plans.id

stripe_subscription_id (string) UNIQUE

status (string) 
-- 'active', 'trialing', 'past_due', 'canceled', etc.

current_period_end (timestamp) NULL

created_at
updated_at
```

---

# 🔁 How It Connects

```
User
  ↓
Account (workspace)
  ↓
Subscription
  ↓
Plan
```

---

# ⚡ Stripe Mapping (Important)

| Your DB       | Stripe            |
| ------------- | ----------------- |
| accounts      | customers         |
| plans         | products + prices |
| subscriptions | subscriptions     |

---

# 🔄 Typical Flow (MVP)

### 1. User signs up

* create `user`
* create `account`
* attach **Free plan subscription (no Stripe yet)**

---

### 2. User upgrades

* create Stripe **Customer** (if not exists)
* create Stripe **Subscription**
* store:

  * `stripe_customer_id`
  * `stripe_subscription_id`
* update `subscriptions.plan_id`

---

### 3. Webhook (IMPORTANT)

Handle:

* `invoice.paid`
* `customer.subscription.updated`
* `customer.subscription.deleted`

👉 Update:

* `subscriptions.status`
* `current_period_end`

---

# 🧪 MVP Rules (Keep it simple)

* Always trust **Stripe for billing state**
* Always trust **your DB for feature limits**
* Don’t calculate billing yourself

---

# 🚫 What NOT to build yet

Avoid:

* invoices table
* usage tracking tables
* proration logic
* multi-plan subscriptions

👉 Stripe already solves these

---

# 💡 Optional (but useful)

Add to `accounts`:

```sql
current_plan_id (uuid)
```

👉 Speeds up access checks (no joins)

---

# 🔥 Final Thought

This setup lets you:

* start **payment testing immediately**
* support upgrades/downgrades
* enforce limits (users/items/replies)
* scale later without migration pain
