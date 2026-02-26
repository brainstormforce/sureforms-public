# Architecture

**Version:** 2.5.0

---

## System Overview

SureForms is a WordPress form builder with a **Free + Pro** plugin architecture:

```
WordPress Site
├── SureForms Free (Required)
│   ├── Core form functionality
│   ├── Stripe payments
│   ├── AI form builder
│   └── Entry management
└── SureForms Pro (Optional Extension)
    ├── Advanced features (conditional logic, multi-step)
    ├── PayPal payments
    ├── User registration/login
    └── 24+ native integrations
```

---

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    WordPress Admin                       │
│  ┌──────────────────────────────────────────────────┐  │
│  │  React Admin UI (src/)                           │  │
│  │  - Form Builder (Gutenberg Blocks)               │  │
│  │  - Entry Dashboard                                │  │
│  │  - Settings Panels                                │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
                          ↕
┌─────────────────────────────────────────────────────────┐
│                    REST API Layer                        │
│  - /sureforms/v1/* (Free)                               │
│  - /sureforms-pro/v1/* (Pro)                            │
│  - AJAX handlers (wp_ajax_*)                            │
└─────────────────────────────────────────────────────────┘
                          ↕
┌─────────────────────────────────────────────────────────┐
│                  Business Logic (inc/)                   │
│  ┌──────────┬──────────┬──────────┬──────────────────┐ │
│  │ Forms    │ Payments │ AI       │ Integrations     │ │
│  │ Submit   │ Stripe   │ Builder  │ (Pro: 24+)       │ │
│  │ Validate │ PayPal   │ Field    │ Mailchimp, etc   │ │
│  │ Store    │ Webhooks │ Mapping  │                  │ │
│  └──────────┴──────────┴──────────┴──────────────────┘ │
└─────────────────────────────────────────────────────────┘
                          ↕
┌─────────────────────────────────────────────────────────┐
│                  Data Layer                              │
│  ┌──────────────┬────────────────┬──────────────────┐  │
│  │ WP Options   │ Custom Tables  │ Encrypted Data   │  │
│  │ Form configs │ Entries        │ OAuth tokens     │  │
│  │ Settings     │ Payments       │ API keys         │  │
│  └──────────────┴────────────────┴──────────────────┘  │
└─────────────────────────────────────────────────────────┘
                          ↕
┌─────────────────────────────────────────────────────────┐
│                Frontend Rendering                        │
│  - Gutenberg Blocks (PHP + JS)                          │
│  - Form submission (AJAX)                                │
│  - reCAPTCHA/Turnstile                                  │
│  - Real-time validation                                  │
└─────────────────────────────────────────────────────────┘
```

---

## Component Breakdown

### 1. Block System (Gutenberg)

**Free Blocks (16):**
- Input types: Text, Email, Number, Phone, URL, Textarea, Dropdown
- Special: Address, Checkbox, Multiple Choice, GDPR, Payment
- Layout: Separator, Heading, Image, Icon, Custom Button

**Pro Blocks (8):**
- Input: Date Picker, Time Picker, Rating, Slider, Hidden, Password
- Advanced: File Upload, HTML, Page Break (multi-step)
- User Registration: Login, Register, Lost Password, Reset Password

**Location:** `inc/blocks/*/block.php` and `src/blocks/*/`

### 2. Form Submission Pipeline

```
User Submits Form
    ↓
1. Nonce Verification (inc/form-submit.php:91-95)
    ↓
2. Field Validation (inc/field-validation.php)
   - Type validation (email, URL, number)
   - Required fields
   - Min/max constraints
   - Custom regex
    ↓
3. Sanitization (inc/helper.php:258-274)
   - Per-field type sanitization
   - XSS prevention (wp_kses_post)
   - SQL injection prevention
    ↓
4. CAPTCHA Validation (inc/form-submit.php:317-386)
   - reCAPTCHA v2/v3
   - Cloudflare Turnstile
   - hCaptcha
    ↓
5. Business Logic (Pro: Conditional Logic)
    ↓
6. Payment Processing (if enabled)
   - Stripe: Create PaymentIntent
   - PayPal: Create Order
    ↓
7. Entry Storage (inc/database/tables/entries.php)
    ↓
8. Integrations (Pro: Send to external services)
    ↓
9. Email Notifications (inc/email/email-template.php)
    ↓
10. Confirmation/Redirect
```

### 3. Payment Processing

**Stripe (Free):**
- Location: `inc/payments/stripe/`
- Flow:
  1. Frontend creates PaymentIntent (AJAX)
  2. User completes payment on Stripe
  3. Webhook updates payment status
  4. Entry marked as paid

**PayPal (Pro):**
- Location: `inc/business/payments/pay-pal/`
- Flow:
  1. Frontend creates Order via API
  2. User redirected to PayPal
  3. Webhook confirms payment
  4. Entry status updated

**Database:** `wp_sureforms_payments` table

### 4. AI Form Builder

**Location:** `inc/ai-form-builder/`

**Flow:**
```
User Prompt ("Create a contact form")
    ↓
AI Middleware (SRFM_AI_MIDDLEWARE)
    ↓
Field Mapping (field-mapping.php)
    ↓
Gutenberg Block Generation
    ↓
Form Preview
```

**API:** `https://credits.startertemplates.com/sureforms/`

### 5. Native Integrations (Pro)

**Location:** `inc/pro/native-integrations/`

**Supported Services (24+):**
- Email: Mailchimp, Brevo, Constant Contact
- CRM: HubSpot, Salesforce, Zoho
- Messaging: Telegram, Slack, Discord
- Marketing: FluentCRM, MailerPress
- Booking: LatePoint
- Automation: Zapier, OttoKit

**Architecture:**
```
Generic Provider Base Class
    ↓
Integration-Specific Classes
(inc/pro/native-integrations/integrations/*/actions/)
    ↓
OAuth Handler (oauth-handler.php)
    ↓
Encrypted Storage (encryption.php - AES-256-CTR)
    ↓
Workflow Processor (services/workflow-processor.php)
```

### 6. Database Schema

**Custom Tables:**

```sql
-- Entries
wp_sureforms_entries
- id (INT, PK)
- form_id (INT)
- entry_data (LONGTEXT, JSON)
- created_at (DATETIME)
- updated_at (DATETIME)
- status (VARCHAR - 'published', 'trash')
- user_agent (TEXT)
- ip_address (VARCHAR)

-- Payments
wp_sureforms_payments
- id (INT, PK)
- entry_id (INT, FK)
- transaction_id (VARCHAR)
- gateway (ENUM 'stripe', 'paypal')
- status (ENUM 'pending', 'completed', 'failed', 'refunded')
- total_amount (DECIMAL)
- refunded_amount (DECIMAL)
- currency (VARCHAR)

-- Integrations (Pro)
wp_sureforms_integrations
- id (INT, PK)
- type (VARCHAR - 'mailchimp', 'hubspot', etc.)
- data (LONGTEXT, encrypted JSON)
- status (ENUM 'active', 'inactive')

-- Save & Resume (Pro)
wp_sureforms_save_resume
- id (INT, PK)
- form_id (INT)
- token (VARCHAR, unique)
- form_data (LONGTEXT, JSON)
- expires_at (DATETIME)
```

---

## Security Architecture

**Input Validation:**
- Nonce verification on all AJAX/REST requests
- Sanitization via `sanitize_*` functions
- Type casting (absint, floatval)

**Output Escaping:**
- `esc_html()`, `esc_attr()`, `esc_url()` throughout
- `wp_kses_post()` for rich text

**SQL Security:**
- `$wpdb->prepare()` for all queries
- No direct SQL concatenation

**Payment Security:**
- Webhook signature verification (Stripe, PayPal)
- Server-side amount validation
- PCI-DSS compliant (payments handled by Stripe/PayPal)

**Encryption (Pro):**
- AES-256-CTR for OAuth tokens
- Key derivation: `SRFM_ENCRYPTION_KEY` constant or `LOGGED_IN_KEY`
- Storage: `wp_sureforms_integrations.data` (encrypted JSON)

---

## Performance Optimizations

1. **Lazy Loading:** React components loaded on-demand
2. **Asset Minification:** Grunt uglifies JS/CSS
3. **Database Indexing:** Indexes on `form_id`, `entry_id`, `status`
4. **Caching:** Transients for integration API calls
5. **Query Optimization:** `SELECT` only needed columns

---

## Extensibility

**Hooks (Filters):**
- `sureforms_submit_form_data` - Modify data before save
- `sureforms_email_template` - Customize email HTML
- `sureforms_payment_amount` - Modify payment amount

**Hooks (Actions):**
- `sureforms_after_entry_save` - Trigger after entry created
- `sureforms_payment_completed` - After successful payment
- `sureforms_integration_success` - After integration sends data

**Developer APIs:**
- REST: `/sureforms/v1/*`
- PHP: `SRFM\Inc\Helper` class
- JavaScript: `window.sureforms.*` globals

---

## Technology Stack

| Layer | Technologies |
|-------|-------------|
| Frontend (Admin) | React 18, @wordpress/components, TanStack Query |
| Frontend (Public) | Vanilla JS, DOMPurify, intl-tel-input |
| Backend | PHP 7.4+, WordPress 6.4+ |
| Build | webpack 5, Babel, Sass, Grunt |
| Testing | PHPUnit, Jest, Playwright |
| Database | MySQL 5.7+, MariaDB 10.3+ |

---

## Deployment Architecture

```
Developer Machine
    ↓
Git Commit → GitHub Actions CI
    ↓
Automated Tests (PHPUnit, Playwright)
    ↓
Build Assets (npm run build)
    ↓
Free: WordPress.org SVN
Pro: Licensing Server (ZIP)
    ↓
End User WordPress Sites (300,000+)
```

---

**Next:** [Codebase Map](codebase-map.md) for detailed folder structure
