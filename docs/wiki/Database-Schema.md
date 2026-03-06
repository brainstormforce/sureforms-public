# Database Schema

SureForms uses two custom database tables alongside the WordPress `sureforms_form` custom post type. All custom tables use the `wp_srfm_` prefix.

## Table Relationships

```
+-------------------+          +-------------------+
|   wp_posts        |          |  wp_srfm_entries  |
| (sureforms_form)  |<---------| form_id           |
|  ID (PK)          |     |    | ID (PK)           |
|  post_content     |     |    | user_id            |
|  post_meta (_srfm)|     |    | form_data (JSON)   |
+-------------------+     |    | status             |
                          |    +--------+-----------+
                          |             |
                          |    +--------v-----------+
                          |    |  wp_srfm_payments  |
                          +----| form_id            |
                               | id (PK)            |
                               | entry_id (FK)      |
                               | transaction_id     |
                               | total_amount       |
                               +--------------------+
```

## Custom Tables

### wp_srfm_entries

Stores all form submissions. Table version: 1.

| Column | Type | Default | Description |
|--------|------|---------|-------------|
| `ID` | BIGINT(20) UNSIGNED | AUTO_INCREMENT | Primary key |
| `form_id` | BIGINT(20) UNSIGNED | -- | Reference to sureforms_form post ID |
| `user_id` | BIGINT(20) UNSIGNED | 0 | WordPress user ID (0 = anonymous) |
| `status` | VARCHAR(10) | `unread` | Entry status: `unread`, `read`, `trash` |
| `type` | VARCHAR(20) | -- | Form type: `quiz`, `standard`, etc. |
| `form_data` | LONGTEXT | `[]` | JSON-encoded submitted field values |
| `submission_info` | LONGTEXT | `[]` | JSON-encoded browser/device/IP metadata |
| `notes` | LONGTEXT | `[]` | JSON-encoded admin notes |
| `logs` | LONGTEXT | `[]` | JSON-encoded activity log entries |
| `extras` | LONGTEXT | `[]` | JSON-encoded misc additional data |
| `created_at` | TIMESTAMP | CURRENT_TIMESTAMP | Submission timestamp |
| `updated_at` | TIMESTAMP | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |

**Indexes:**
- `PRIMARY KEY (ID)`
- `INDEX idx_form_id (form_id)`
- `INDEX idx_user_id (user_id)`
- `INDEX idx_form_id_created_at_status (form_id, created_at, status)` -- composite index for list queries

**Log Entry Structure:**
```json
{
  "title": "Form Submitted",
  "messages": ["Email notification sent", "Entry stored"],
  "timestamp": "2024-01-15 10:30:00"
}
```

### wp_srfm_payments

Stores payment transaction records linked to form entries. Table version: 1. Added in v2.0.0.

| Column | Type | Default | Description |
|--------|------|---------|-------------|
| `id` | BIGINT(20) UNSIGNED | AUTO_INCREMENT | Primary key |
| `form_id` | BIGINT(20) UNSIGNED | -- | Reference to sureforms_form post |
| `block_id` | VARCHAR(255) | `''` | Payment block identifier |
| `status` | VARCHAR(50) | `pending` | Payment status (see below) |
| `total_amount` | DECIMAL(26,8) | `0.00000000` | Total amount after discount |
| `refunded_amount` | DECIMAL(26,8) | `0.00000000` | Total refunded amount |
| `currency` | VARCHAR(10) | `''` | ISO 4217 currency code |
| `entry_id` | BIGINT(20) UNSIGNED | 0 | Reference to entries table |
| `gateway` | VARCHAR(20) | `''` | Payment gateway: `stripe` |
| `type` | VARCHAR(30) | `''` | Payment type: `payment`, `subscription`, `renewal` |
| `mode` | VARCHAR(20) | `''` | Mode: `test` or `live` |
| `transaction_id` | VARCHAR(50) | `''` | Gateway transaction ID |
| `customer_id` | VARCHAR(50) | `''` | Gateway customer ID |
| `subscription_id` | VARCHAR(50) | `''` | Subscription ID (if recurring) |
| `subscription_status` | VARCHAR(20) | `''` | Subscription status |
| `parent_subscription_id` | BIGINT(20) UNSIGNED | 0 | Parent subscription payment ID |
| `payment_data` | LONGTEXT | `[]` | JSON-encoded payment details (includes refunds) |
| `extra` | LONGTEXT | `[]` | JSON-encoded additional data |
| `log` | LONGTEXT | `[]` | JSON-encoded payment log |
| `created_at` | TIMESTAMP | CURRENT_TIMESTAMP | Creation timestamp |
| `updated_at` | TIMESTAMP | CURRENT_TIMESTAMP ON UPDATE | Last update timestamp |
| `srfm_txn_id` | VARCHAR(100) | `''` | Custom transaction ID format |
| `customer_email` | VARCHAR(255) | `''` | Customer email |
| `customer_name` | VARCHAR(255) | `''` | Customer name |

**Valid Payment Statuses:**
`pending`, `succeeded`, `failed`, `canceled`, `requires_action`, `requires_payment_method`, `processing`, `refunded`, `partially_refunded`

**Valid Subscription Statuses:**
`active`, `canceled`, `past_due`, `unpaid`, `trialing`, `incomplete`, `incomplete_expired`, `paused`

**Supported Currencies:**
USD, EUR, GBP, JPY, CAD, AUD, CHF, CNY, SEK, NZD, MXN, SGD, HKD, NOK, TRY, RUB, INR, BRL, ZAR, KRW

## WordPress Post Type: sureforms_form

Forms are stored as a custom post type (`sureforms_form`) in the standard `wp_posts` table. Block content is in `post_content`, and form configuration is stored as post meta with `_srfm_` prefixed keys.

### Key Post Meta Keys

| Meta Key | Type | Description |
|----------|------|-------------|
| `_srfm_forms_styling` | string | Form styling mode |
| `_srfm_color` | string | Primary form color |
| `_srfm_bg_color` | string | Background color |
| `_srfm_fontsize` | string | Font size |
| `_srfm_label_color` | string | Label text color |
| `_srfm_input_color` | string | Input text color |
| `_srfm_submit_button_text` | string | Submit button text |
| `_srfm_submit_alignment` | string | Button alignment |
| `_srfm_submit_width` | string | Button width |
| `_srfm_submit_width_backend` | string | Backend button width |
| `_srfm_is_inline_button` | boolean | Inline button mode |
| `_srfm_email_notification` | array | Email notification settings |
| `_srfm_form_confirmation` | array | Confirmation settings (message/redirect) |
| `_srfm_captcha` | string | CAPTCHA type (recaptcha_v2, v3, hcaptcha, turnstile) |
| `_srfm_honeypot` | boolean | Honeypot spam protection |
| `_srfm_is_page_break` | boolean | Multi-step form enabled |
| `_srfm_single_page_form_title` | boolean | Show title on instant form |
| `_srfm_instant_form_settings` | array | Instant form configuration |
| `_srfm_page_form_logo` | string | Form logo URL |
| `_srfm_submit_type` | string | Submission type |

Post meta is extensible via the `srfm_register_post_meta` filter.

## Database Base Class

All custom tables extend `SRFM\Inc\Database\Base`, which provides:

### Schema Versioning
- Each table has a `$table_version` integer
- Versions stored in `srfm_database_table_versions` option
- On version mismatch, `start_db_upgrade()` runs migrations (new columns, renames)

### Query Builder
```php
// Simple equality
$results = $instance->get_results(['form_id' => 123]);

// Complex conditions with operators
$results = $instance->get_results([
    [
        ['key' => 'status', 'compare' => '!=', 'value' => 'trash'],
        ['key' => 'created_at', 'compare' => '>', 'value' => '2024-01-01'],
    ]
]);

// IN operator
$results = $instance->get_results([
    [['key' => 'ID', 'compare' => 'IN', 'value' => [1, 2, 3]]]
]);
```

### Caching
Results are cached in-memory using MD5 of the SQL query as the cache key. Cache is reset on insert, update, or delete operations.

### Data Encoding
Array-type columns (defined in `get_schema()`) are automatically JSON-encoded on write and decoded on read. Number types are cast to integers.

## Related Pages

- [Architecture Overview](Architecture-Overview) -- Plugin bootstrap and class initialization
- [Payment Integration](Payment-Integration) -- Stripe payment flow
- [Form Submission Flow](Form-Submission-Flow) -- Entry creation pipeline
- [REST API Reference](REST-API-Reference) -- API endpoints for entries and payments
