# SureForms Abilities API Reference

Complete reference for all 15 abilities registered with the WordPress Abilities API.

**Category:** `sureforms`
**Requires:** WordPress 6.9+ (Abilities API)
**Namespace:** `SRFM\Inc\Abilities`

---

## Quick Reference

| # | Ability ID | Capability | Annotations | Module |
|---|-----------|-----------|-------------|--------|
| 1 | `sureforms/list-forms` | `edit_posts` | readonly, idempotent | Forms |
| 2 | `sureforms/create-form` | `edit_posts` | write, not idempotent | Forms |
| 3 | `sureforms/get-form` | `edit_posts` | readonly, idempotent | Forms |
| 4 | `sureforms/update-form` | `edit_posts` | write, idempotent | Forms |
| 5 | `sureforms/delete-form` | `delete_posts` | destructive | Forms |
| 6 | `sureforms/duplicate-form` | `edit_posts` | write, not idempotent | Forms |
| 7 | `sureforms/get-form-stats` | `edit_posts` | readonly, idempotent | Forms |
| 8 | `sureforms/get-shortcode` | `edit_posts` | readonly, idempotent | Embedding |
| 9 | `sureforms/list-entries` | `manage_options` | readonly, idempotent | Entries |
| 10 | `sureforms/get-entry` | `manage_options` | readonly, idempotent | Entries |
| 11 | `sureforms/update-entry-status` | `manage_options` | write, idempotent | Entries |
| 12 | `sureforms/delete-entry` | `manage_options` | destructive | Entries |
| 13 | `sureforms/get-global-settings` | `manage_options` | readonly, idempotent | Settings |
| 14 | `sureforms/update-global-settings` | `manage_options` | write, idempotent | Settings |
| 15 | `sureforms/get-form-analytics` | `manage_options` | readonly, idempotent | Analytics |
---

## Forms Module

### 1. `sureforms/list-forms`

**List SureForms Forms** — Retrieve a list of forms with optional filtering by status, search query, and pagination.

**File:** `inc/abilities/forms/list-forms.php`

**Input:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `status` | string | No | `any` | Enum: `publish`, `draft`, `trash`, `any` |
| `search` | string | No | — | Search forms by title |
| `per_page` | integer | No | `10` | Results per page (1-100) |
| `page` | integer | No | `1` | Page number |

**Output:**
```json
{
  "forms": [
    {
      "id": 123,
      "title": "Contact Form",
      "status": "publish",
      "date": "2025-01-15 10:30:00",
      "entry_count": 42
    }
  ],
  "total": 5,
  "pages": 1
}
```

---

### 2. `sureforms/create-form`

**Create SureForms Form** — Create a new form with title, fields, metadata, and status. Supports all standard field types.

**File:** `inc/abilities/forms/create-form.php`

**Input:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `formTitle` | string | **Yes** | — | Form title (5-10 words) |
| `formFields` | array | **Yes** | — | Array of field definitions |
| `formMetaData` | object | No | — | Confirmation, email, compliance, instant form, styling settings |
| `formStatus` | string | No | `draft` | Enum: `publish`, `draft`, `private` |

**Field definition properties:**
- `label` (string, required) — Field label
- `fieldType` (string, required) — Enum: `input`, `email`, `url`, `textarea`, `multi-choice`, `checkbox`, `gdpr`, `number`, `phone`, `dropdown`, `address`, `inline-button`, `payment`
- `required` (boolean) — Whether field is required
- `helpText` (string) — Help text
- `defaultValue` (string) — Default value
- `fieldOptions` (array) — Options for dropdown/multi-choice: `[{optionTitle, label}]`
- `singleSelection` (boolean) — Single selection for multi-choice
- `isUnique` (boolean) — Unique value constraint
- `textLength` (integer) — Max character length

**formMetaData sub-objects:**
- `general` — `{useLabelAsPlaceholder, submitText}`
- `formConfirmation` — `{confirmationMessage}`
- `emailConfirmation` — `{name, subject, emailBody}`
- `compliance` — `{enableCompliance, neverStoreEntries, autoDeleteEntries, autoDeleteEntriesDays}`
- `instantForm` — `{instantForm, showTitle, bannerColor, useBannerColorAsBackground, formBackgroundColor, formWidth, formSlug}`
- `styling` — `{primaryColor, textColor, textColorOnPrimary, fieldSpacing, submitAlignment}`

**Output:**
```json
{
  "form_id": 456,
  "title": "Contact Form",
  "status": "draft",
  "edit_url": "https://example.com/wp-admin/post.php?post=456&action=edit",
  "shortcode": "[sureforms id=\"456\"]"
}
```

---

### 3. `sureforms/get-form`

**Get SureForms Form Details** — Retrieve detailed information about a specific form including fields, settings, and shortcode.

**File:** `inc/abilities/forms/get-form.php`

**Input:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `form_id` | integer | **Yes** | Form ID |

**Output:**
```json
{
  "form_id": 123,
  "title": "Contact Form",
  "status": "publish",
  "fields": [
    {
      "type": "input",
      "label": "Full Name",
      "slug": "full-name",
      "required": true
    }
  ],
  "settings": {
    "submit_button_text": "Submit",
    "use_label_as_placeholder": false,
    "form_container_width": "",
    "instant_form": "",
    "submit_alignment": "left",
    "form_recaptcha": ""
  },
  "shortcode": "[sureforms id=\"123\"]"
}
```

---

### 4. `sureforms/update-form`

**Update SureForms Form** — Update form title, status, and/or metadata. Use `status: "trash"` to trash, or change from trash to restore.

**File:** `inc/abilities/forms/update-form.php`

**Input:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `form_id` | integer | **Yes** | Form ID |
| `title` | string | No | New form title |
| `status` | string | No | Enum: `publish`, `draft`, `private`, `trash` |
| `formMetaData` | object | No | Same schema as create-form |

**Output:**
```json
{
  "form_id": 123,
  "title": "Updated Form",
  "status": "publish",
  "previous_status": "draft",
  "edit_url": "https://example.com/wp-admin/post.php?post=123&action=edit",
  "updated_fields": ["title", "status"]
}
```

---

### 5. `sureforms/delete-form`

**Delete SureForms Form** — Move a form to trash, or permanently delete when `force` is true.

**File:** `inc/abilities/forms/delete-form.php`

**Input:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `form_id` | integer | **Yes** | — | Form ID |
| `force` | boolean | No | `false` | Permanently delete if true |

**Output:**
```json
{
  "form_id": 123,
  "deleted": true,
  "previous_status": "publish"
}
```

---

### 6. `sureforms/duplicate-form`

**Duplicate SureForms Form** — Duplicate an existing form with all fields, metadata, and settings. Creates as draft.

**File:** `inc/abilities/forms/duplicate-form.php`

**Input:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `form_id` | integer | **Yes** | — | Form ID to duplicate |
| `title_suffix` | string | No | `" (Copy)"` | Suffix for new form title |

**Output:**
```json
{
  "original_form_id": 123,
  "new_form_id": 789,
  "new_form_title": "Contact Form (Copy)",
  "edit_url": "https://example.com/wp-admin/post.php?post=789&action=edit",
  "shortcode": "[sureforms id=\"789\"]"
}
```

---

### 7. `sureforms/get-form-stats`

**Get Form Statistics** — Get submission statistics for a specific form or all forms.

**File:** `inc/abilities/forms/get-form-stats.php`

**Input:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `form_id` | integer | No | `0` | Form ID (0 for all forms) |

**Output:**
```json
{
  "form_id": 123,
  "form_title": "Contact Form",
  "form_status": "publish",
  "total_entries": 42,
  "unread_count": 5,
  "read_count": 35,
  "trash_count": 2
}
```

---

## Embedding Module

### 8. `sureforms/get-shortcode`

**Get Form Shortcode** — Get the shortcode and block markup for embedding a form.

**File:** `inc/abilities/embedding/get-shortcode.php`

**Input:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `form_id` | integer | **Yes** | Form ID |

**Output:**
```json
{
  "form_id": 123,
  "shortcode": "[sureforms id=\"123\"]",
  "block_markup": "<!-- wp:srfm/form {\"id\":123} /-->"
}
```

---

## Entries Module

### 9. `sureforms/list-entries`

**List Form Entries** — List form submission entries with filtering, pagination, and sorting.

**File:** `inc/abilities/entries/list-entries.php`

**Input:**
| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `form_id` | integer | No | `0` | Filter by form ID |
| `status` | string | No | `all` | Enum: `all`, `read`, `unread`, `trash` |
| `search` | string | No | — | Search by entry ID |
| `date_from` | string | No | — | Start date (YYYY-MM-DD) |
| `date_to` | string | No | — | End date (YYYY-MM-DD) |
| `per_page` | integer | No | `20` | Results per page (1-100) |
| `page` | integer | No | `1` | Page number |
| `orderby` | string | No | `created_at` | Enum: `created_at`, `ID`, `form_id`, `status` |
| `order` | string | No | `DESC` | Enum: `ASC`, `DESC` |

**Output:**
```json
{
  "entries": [
    {
      "id": 1,
      "form_id": 123,
      "form_title": "Contact Form",
      "status": "unread",
      "created_at": "2025-01-15 10:30:00"
    }
  ],
  "total": 42,
  "total_pages": 3,
  "current_page": 1,
  "per_page": 20
}
```

---

### 10. `sureforms/get-entry`

**Get Entry Details** — Retrieve detailed entry information including submitted field data with decrypted labels.

**File:** `inc/abilities/entries/get-entry.php`

**Input:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `entry_id` | integer | **Yes** | Entry ID |

**Output:**
```json
{
  "id": 1,
  "form_id": 123,
  "form_name": "Contact Form",
  "status": "unread",
  "created_at": "2025-01-15 10:30:00",
  "form_data": [
    {
      "label": "Full Name",
      "value": "John Doe",
      "block_name": "input"
    }
  ],
  "submission_info": {
    "user_ip": "192.168.1.1",
    "browser_name": "Chrome",
    "device_name": "Desktop"
  },
  "user": {
    "id": 1,
    "display_name": "Admin",
    "profile_url": "https://example.com/author/admin/"
  }
}
```

---

### 11. `sureforms/update-entry-status`

**Update Entry Status** — Update the status of one or more entries.

**File:** `inc/abilities/entries/update-entry-status.php`

**Input:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `entry_ids` | array\<integer\> | **Yes** | Entry IDs to update |
| `status` | string | **Yes** | Enum: `read`, `unread`, `trash`, `restore` |

**Output:**
```json
{
  "success": true,
  "updated": 3,
  "errors": []
}
```

---

### 12. `sureforms/delete-entry`

**Delete Entry** — Permanently delete one or more entries. Cannot be undone.

**File:** `inc/abilities/entries/delete-entry.php`

**Input:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `entry_ids` | array\<integer\> | **Yes** | Entry IDs to permanently delete |

**Output:**
```json
{
  "success": true,
  "deleted": 2,
  "errors": []
}
```

---

## Settings Module

### 13. `sureforms/get-global-settings`

**Get Global Settings** — Retrieve global settings, optionally filtered by category. Secret keys in security settings are masked with `********`.

**File:** `inc/abilities/settings/get-global-settings.php`

**Input:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `categories` | array\<string\> | No | Enum items: `general`, `validation-messages`, `email-summary`, `security`. Omit for all. |

**Output:**
```json
{
  "general": {
    "srfm_ip_log": false,
    "srfm_form_analytics": false,
    "srfm_admin_notification": true,
    "srfm_bsf_analytics": false
  },
  "validation-messages": {
    "srfm_input_block_required_text": "This field is required.",
    "srfm_valid_email": "Please enter a valid email address."
  },
  "email-summary": {
    "srfm_email_summary": false,
    "srfm_email_sent_to": "admin@example.com",
    "srfm_schedule_report": "Monday"
  },
  "security": {
    "srfm_v2_checkbox_site_key": "6Lc...",
    "srfm_v2_checkbox_secret_key": "********",
    "srfm_honeypot": false
  }
}
```

---

### 14. `sureforms/update-global-settings`

**Update Global Settings** — Update settings for a specific category. When sending `********` for a security secret key, the stored value is preserved.

**File:** `inc/abilities/settings/update-global-settings.php`

**Input:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `category` | string | **Yes** | Enum: `general`, `validation-messages`, `email-summary`, `security` |
| `settings` | object | **Yes** | Key-value pairs matching the category's option keys |

**Output:**
```json
{
  "saved": true,
  "category": "general"
}
```

**Delegates to:**
- `general` → `Global_Settings::srfm_save_general_settings()`
- `validation-messages` → `Global_Settings::srfm_save_general_settings_dynamic_opt()`
- `email-summary` → `Global_Settings::srfm_save_email_summary_settings()`
- `security` → `Global_Settings::srfm_save_security_settings()` (with sentinel preservation)

---

## Analytics Module

### 15. `sureforms/get-form-analytics`

**Get Form Analytics** — Retrieve submission analytics for a date range, optionally filtered by form.

**File:** `inc/abilities/analytics/get-form-analytics.php`

**Input:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `date_from` | string | **Yes** | Start date (Y-m-d or Y-m-d H:i:s) |
| `date_to` | string | **Yes** | End date (Y-m-d or Y-m-d H:i:s) |
| `form_id` | integer | No | Filter by form ID |

**Output:**
```json
{
  "submissions": [
    { "created_at": "2025-01-15 10:30:00" },
    { "created_at": "2025-01-14 09:15:00" }
  ],
  "total_count": 2,
  "form_id": null,
  "date_from": "2025-01-01",
  "date_to": "2025-01-31"
}
```

---

## Annotations Reference

| Annotation | Meaning |
|-----------|---------|
| `readonly: true` | Does not modify any data |
| `readonly: false` | May create, update, or delete data |
| `destructive: true` | Permanently deletes data (cannot be undone) |
| `destructive: false` | No permanent data loss |
| `idempotent: true` | Same input always produces same result/effect |
| `idempotent: false` | Each call may produce different results (e.g. creates new records) |

## Extensibility

- **Filter:** `srfm_register_abilities` — Third-party plugins can add abilities (must extend `Abstract_Ability`)
- **Hooks:** `srfm_before_ability_execute` / `srfm_after_ability_execute` — Fire around every execution
- **Collision guard:** Registrar checks `wp_has_ability()` before registering to avoid duplicates
- **Field types filter:** `srfm_ability_create_form_field_types` — Pro plugins can add field types to create-form
- **Field properties filter:** `srfm_ability_create_form_field_properties` — Pro plugins can add field properties
