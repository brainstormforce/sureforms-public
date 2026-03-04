# SureForms Abilities Audit — Phase 0 Discovery

**Plugin:** SureForms v2.5.1
**Date:** 2026-03-04
**Branch:** feature/wp-abilities-api-integration

## Existing Abilities (12)

| # | Ability ID | Subdirectory | Annotations |
|---|-----------|-------------|-------------|
| 1 | `sureforms/list-forms` | forms/ | readonly, idempotent |
| 2 | `sureforms/create-form` | forms/ | write |
| 3 | `sureforms/get-form` | forms/ | readonly, idempotent |
| 4 | `sureforms/delete-form` | forms/ | destructive |
| 5 | `sureforms/duplicate-form` | forms/ | write |
| 6 | `sureforms/update-form` | forms/ | write, idempotent |
| 7 | `sureforms/get-form-stats` | forms/ | readonly, idempotent |
| 8 | `sureforms/list-entries` | entries/ | readonly, idempotent |
| 9 | `sureforms/get-entry` | entries/ | readonly, idempotent |
| 10 | `sureforms/update-entry-status` | entries/ | write, idempotent |
| 11 | `sureforms/delete-entry` | entries/ | destructive |
| 12 | `sureforms/get-shortcode` | embedding/ | readonly, idempotent |

## Module Map

### Module 1: Payments (P0 — Critical)
**Priority:** Highest. Stripe payments is a core revenue feature with zero ability coverage.

**Key Files:**
- `inc/payments/payments.php` — Orchestrator
- `inc/payments/front-end.php` (59KB) — Payment intent creation & verification
- `inc/payments/payment-helper.php` (46KB) — Utilities
- `inc/payments/stripe/stripe-webhook.php` (48KB) — Webhook handler
- `inc/payments/stripe/payments-settings.php` (32KB) — Settings REST API
- `inc/database/tables/payments.php` — Payments table

**Estimated abilities:** 5-7

### Module 2: Global Settings (P1 — High)
**Priority:** High. Plugin-wide config (reCAPTCHA, defaults, security) not exposed.

**Key Files:**
- `inc/global-settings/global-settings.php` (14.5KB)
- `inc/global-settings/email-summary.php` (20KB)

**REST:** `GET/POST /sureforms/v1/srfm-global-settings`

**Estimated abilities:** 2-3

### Module 3: Analytics (P1 — High)
**Priority:** High. Dashboard chart data available via REST but not as abilities.

**Key Files:**
- `admin/analytics.php`
- REST: `GET /sureforms/v1/entries-chart-data`, `GET /sureforms/v1/form-data`

**Estimated abilities:** 1-2

### Module 4: Import/Export (P1 — High)
**Priority:** High. Form import/export and entry export not exposed.

**Key Files:**
- `inc/export.php` (8KB)
- REST: `POST /sureforms/v1/forms/export`, `POST /sureforms/v1/forms/import`
- `inc/entries.php` — `export_entries()` method

**Estimated abilities:** 2-3

### Module 5: Email Notifications (P2 — Medium)
**Priority:** Medium. Per-form email config is partially covered via update-form metadata, but dedicated abilities would be clearer.

**Key Files:**
- `inc/email/email-template.php`
- Post meta: `_srfm_email_notification`

**Estimated abilities:** 1-2 (may overlap with update-form)

### Module 6: Integrations & Webhooks (P2 — Medium)
**Priority:** Medium. Webhook config stored in post meta `_srfm_integrations_webhooks`.

**Key Files:**
- Post meta based, referenced in `inc/form-submit.php`
- SureTriggers integration

**Estimated abilities:** 1-2

### Module 7: Smart Tags (P2 — Medium)
**Priority:** Medium. List available tags for AI to use in form creation.

**Key Files:**
- `inc/smart-tags.php`

**Estimated abilities:** 1

## Coverage Summary

| Module | Status | Abilities Gap |
|--------|--------|--------------|
| Forms CRUD | ✅ Complete | 0 |
| Entries CRUD | ✅ Complete | 0 |
| Embedding | ✅ Complete | 0 |
| Payments | ❌ None | 5-7 |
| Global Settings | ❌ None | 2-3 |
| Analytics | ❌ None | 1-2 |
| Import/Export | ❌ None | 2-3 |
| Email Config | ⚠️ Partial (via metadata) | 1-2 |
| Webhooks | ❌ None | 1-2 |
| Smart Tags | ❌ None | 1 |

**Total gap:** ~15-22 additional abilities possible
