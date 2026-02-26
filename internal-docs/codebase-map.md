# Codebase Map

**Version:** 2.5.0

Complete folder-by-folder guide to both plugins.

---

## SureForms Free (`sureforms/`)

### Root Files

| File | Purpose |
|------|---------|
| `sureforms.php` | Main plugin file, defines constants, loads `plugin-loader.php` |
| `plugin-loader.php` | Bootstraps plugin, registers hooks, loads dependencies |
| `composer.json` | PHP dependencies (nps-survey, bsf-analytics, astra-notices) |
| `package.json` | JS dependencies, build scripts |
| `Gruntfile.js` | Grunt tasks (minify, compress, release) |

### `admin/` - WordPress Admin UI

| File | Purpose |
|------|---------|
| `admin.php` | Admin page registration, AJAX handlers for admin actions |
| `analytics.php` | Usage analytics, event tracking |
| `notice-manager.php` | Admin notices, dismissible alerts |

### `inc/` - Core PHP Logic

#### `inc/blocks/` - Gutenberg Block Definitions (PHP)

Each block has a `block.php` with:
- Block registration (`register_block_type`)
- Server-side rendering callback
- Attribute schema
- Enqueue scripts/styles

**Blocks (16):**
- `address/`, `checkbox/`, `dropdown/`, `email/`, `gdpr/`, `inlinebutton/`
- `input/`, `multichoice/`, `number/`, `payment/`, `phone/`, `sform/`, `textarea/`, `url/`
- `base.php` - Base block class

#### `inc/fields/` - Field Markup Generation

Mirrors `blocks/` - generates HTML markup for each field type.

**Pattern:**
```php
*-markup.php files:
- render_field() method
- Outputs escaped HTML
- Handles field attributes, validation states
```

#### `inc/payments/` - Payment Processing

**Core Files:**
| File | Purpose |
|------|---------|
| `front-end.php` | Public AJAX handlers (`srfm_create_payment_intent`, `srfm_create_subscription_intent`) |
| `payment-helper.php` | Utility functions, amount validation, currency formatting |

**`payments/stripe/`:**
| File | Purpose |
|------|---------|
| `stripe-webhook.php` | Webhook handler (charge, refund, subscription events) |
| `stripe-helper.php` | Stripe API wrapper, PaymentIntent creation |
| `admin-stripe-handler.php` | Admin AJAX for subscription management |
| `payments-settings.php` | Stripe connection settings, API keys |

**`payments/admin/`:**
| File | Purpose |
|------|---------|
| `admin-handler.php` | Admin AJAX for refunds, payment management |

#### `inc/ai-form-builder/` - AI Form Generation

| File | Purpose |
|------|---------|
| `ai-form-builder.php` | Main AI form builder class, API integration |
| `ai-auth.php` | Middleware authentication |
| `ai-helper.php` | Helper functions, prompt processing |
| `field-mapping.php` | Maps AI response to Gutenberg blocks |

**API:** `SRFM_AI_MIDDLEWARE` - `https://credits.startertemplates.com/sureforms/`

#### `inc/database/` - Custom Database Tables

| File | Purpose |
|------|---------|
| `base.php` | Abstract base class for all table classes (CRUD operations) |
| `register.php` | Table registration, schema creation |
| `tables/entries.php` | `wp_sureforms_entries` table (1,300+ lines) |
| `tables/payments.php` | `wp_sureforms_payments` table (1,200+ lines) |

**Key Methods:**
- `get_all()`, `get_by_id()`, `insert()`, `update()`, `delete()`
- `get_all_main_payments()` - Payment queries with filtering

#### Other `inc/` Files

| File | Purpose | Lines |
|------|---------|-------|
| `form-submit.php` | Form submission handler (AJAX + REST) | 1,280 |
| `helper.php` | Utility functions (sanitization, escaping, validation) | 1,500+ |
| `field-validation.php` | Server-side field validation | 800+ |
| `rest-api.php` | REST API registration (`/sureforms/v1/*`) | 800 |
| `entries.php` | Entry management (list, view, export) | 1,000+ |
| `email/email-template.php` | Email notification templates | 400 |
| `frontend-assets.php` | Enqueue public scripts/styles | 300 |

### `src/` - React Admin UI

**Structure:**
```
src/
├── admin/                 # Main admin app
│   ├── components/        # Reusable React components
│   ├── pages/             # Page-level components
│   └── index.js           # Entry point
├── blocks/                # Block editor components
│   ├── address/           # Block-specific React
│   ├── email/
│   └── ...
└── common/                # Shared utilities
    ├── api.js             # API client (wp.apiFetch)
    └── utils.js           # Helper functions
```

**Build:** webpack via `@wordpress/scripts`

---

## SureForms Pro (`sureforms-pro/`)

### Root Files

| File | Purpose |
|------|---------|
| `sureforms-pro.php` | Main plugin file, requires Free plugin |
| `plugin-loader.php` | Bootstraps Pro features |

### `inc/business/` - Pro Business Features

#### `inc/business/payments/pay-pal/` - PayPal Integration

| File | Purpose | Lines |
|------|---------|-------|
| `webhook-listener.php` | PayPal webhook handler (orders, refunds, subscriptions) | 1,173 |
| `frontend.php` | Public AJAX for PayPal orders, subscriptions | 1,100 |
| `api-payments.php` | PayPal API wrapper (create order, capture, refund) | 178 |
| `helper.php` | PayPal utilities, amount formatting | 520 |
| `settings.php` | PayPal connection settings | AJAX |

#### `inc/business/user-registration/` - Login & Registration

| File | Purpose | Lines |
|------|---------|-------|
| `init.php` | REST API registration, login/password endpoints | 600+ |
| `processor.php` | Registration processing, `wp_insert_user()` | 1,000+ |
| `login/block.php` | Login block definition | |
| `register/block.php` | Registration block | |
| `lost-password/block.php` | Lost password block | |
| `reset-password/block.php` | Reset password block | |

**⚠️ Security Note:** Registration processor creates WordPress users from form submissions.

#### `inc/business/pdf/` - PDF Generation

| File | Purpose |
|------|---------|
| `pdf.php` | PDF generation API, AJAX handlers |
| `document.php` | PDF document builder (uses TCPDF or similar) |
| `utils.php` | PDF utilities |

#### `inc/business/custom-app/` - Custom Application Builder

| File | Purpose |
|------|---------|
| `load.php` | Custom post type forms, standalone applications |
| `utils.php` | App utilities |

#### `inc/business/custom-post-type/` - Custom Post Type Integration

Allows forms to create/update custom post types.

### `inc/pro/native-integrations/` - 24+ Service Integrations

**Core:**
| File | Purpose | Lines |
|------|---------|-------|
| `native-integrations.php` | Main integration manager | |
| `encryption.php` | AES-256-CTR encryption for OAuth tokens | 200 |
| `oauth-handler.php` | OAuth authorization flow | 400+ |
| `generic-provider.php` | Base provider class | |
| `integration-provider.php` | Provider interface | |

**`inc/pro/native-integrations/integrations/`:**

Each integration has its own folder:
```
mailchimp/
├── config.json           # API endpoints, auth method
└── actions/
    ├── add-contact.php   # Action: Add subscriber
    └── remove-contact.php

hubspot/
├── config.json
└── actions/
    └── create-contact.php

...24+ integrations
```

**Supported:**
- Email: Mailchimp, Brevo, Constant Contact
- CRM: HubSpot, Salesforce, Zoho
- Messaging: Telegram, Slack, Discord
- WP: FluentCRM, MailerPress, MailPoet
- Booking: LatePoint
- Automation: Zapier, OttoKit

**Encryption:** OAuth tokens encrypted via `encryption.php` before storage in `wp_sureforms_integrations`.

### `inc/pro/database/tables/` - Pro Database Tables

| File | Purpose |
|------|---------|
| `integrations.php` | `wp_sureforms_integrations` table |
| `save-resume.php` | `wp_sureforms_save_resume` table (draft forms) |

### `inc/extensions/` - Pro Extensions

| File | Purpose |
|------|---------|
| `conditional-logic.php` | Show/hide fields based on conditions |
| `conditional-emails.php` | Conditional email notifications |
| `conditional-confirmations.php` | Conditional redirects |
| `field-validation.php` | Extended validation (file uploads, MIME types) |
| `entries-management.php` | Enhanced entry features (edit, delete files) |
| `page-break.php` | Multi-step form logic |

### `inc/blocks/` - Pro Blocks (8)

- `date-picker/`, `time-picker/`, `rating/`, `slider/`
- `upload/`, `hidden/`, `html/`, `page-break/`

---

## Key Patterns & Conventions

### Naming Conventions

**Functions:**
- Public: `srfm_function_name()`
- Internal: `_srfm_internal_function()`

**Classes:**
- Namespaced: `SRFM\Inc\ClassName`
- Pro: `SRFM_PRO\Inc\ClassName`

**Hooks:**
- Actions: `sureforms_action_name`
- Filters: `sureforms_filter_name`

**Database:**
- Tables: `wp_sureforms_table_name`
- Options: `sureforms_option_name`

### File Organization

```
feature/
├── block.php              # Block registration
├── feature-markup.php     # HTML generation
├── api-handler.php        # AJAX/REST handlers
└── helper.php             # Utilities
```

### REST API Convention

**Free:** `/sureforms/v1/endpoint`
**Pro:** `/sureforms-pro/v1/endpoint`

---

## Where to Find Things

**Need to:**
- **Add a new field type?** → `inc/blocks/` + `inc/fields/` + `src/blocks/`
- **Modify form submission?** → `inc/form-submit.php`
- **Change email template?** → `inc/email/email-template.php`
- **Add payment gateway?** → `inc/payments/` (copy Stripe/PayPal pattern)
- **Add integration?** → `inc/pro/native-integrations/integrations/new-service/`
- **Debug entries?** → `inc/database/tables/entries.php`
- **Fix validation?** → `inc/field-validation.php`
- **Customize admin UI?** → `src/admin/`

---

**Next:** [APIs](apis.md) - REST endpoints, AJAX handlers, hooks
