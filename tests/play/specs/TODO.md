# E2E Test Plan — SureForms Free Plugin

Track progress for all Playwright test cases.
**Update this file every time a test is added or a test ID changes.**

Last updated: 2026-03-16
Branch convention: `test/e2e-p{N}-test-cases`

---

## How to update this file

- When you **add a test**, tick its checkbox and fill in the `Spec file` column.
- When you **add a new test ID** that wasn't listed here, add a row in the correct section.
- Fix the `Last updated` date above.

---

## Coverage status key

| Symbol | Meaning |
|---|---|
| ✅ | Implemented and passing |
| ⏭️ | Implemented but skipped (requires external keys / infra) |
| ❌ | Not yet implemented |
| 🔢 | Implemented but test ID needs renumbering |

---

## Group 1 — Field types (basic submit)

All tests live in `field-types.spec.js`.
Single-line input and email are covered in `form-creation-submission.spec.js` (not repeated here).

| ID   | Test case | Status | Spec file |
|------|-----------|--------|-----------|
| 1.1  | Textarea — submit multi-line text | ✅ | field-types.spec.js |
| 1.2  | Number — submit a valid number | ✅ | field-types.spec.js |
| 1.3  | Phone — submit a valid phone number | ✅ | field-types.spec.js |
| 1.4  | URL — submit a valid URL | ✅ | field-types.spec.js |
| 1.5  | Checkbox — check and submit | ✅ | field-types.spec.js |
| 1.6  | Multi-choice (radio) — pick one option and submit | ✅ | field-types.spec.js |
| 1.7  | Multi-choice (multi-select) — pick multiple options and submit | ✅ | field-types.spec.js |
| 1.8  | Dropdown — select one option and submit | ✅ | field-types.spec.js |
| 1.9  | *(reserved — inline-button / advanced-heading behaviour TBD)* | ❌ | — |
| 1.10 | GDPR — check consent and submit | ✅ | field-types.spec.js |

---

## Group 2 — Field validation

All tests live in `field-validation.spec.js`.

| ID   | Test case | Status | Spec file |
|------|-----------|--------|-----------|
| 2.1  | Email — invalid format shows error | ✅ | field-validation.spec.js |
| 2.2  | Email confirmation — mismatch shows error | ✅ | field-validation.spec.js |
| 2.3  | Email confirmation — matching emails submit successfully | ✅ | field-validation.spec.js |
| 2.4  | URL — invalid format shows error | ✅ | field-validation.spec.js |
| 2.5  | Number — value below minimum shows error | ✅ | field-validation.spec.js |
| 2.6  | Number — value above maximum shows error | ✅ | field-validation.spec.js |
| 2.7  | Required single-line input — submit empty shows error | ✅ | field-validation.spec.js |
| 2.8  | Required email — submit empty shows error | ✅ | field-validation.spec.js |
| 2.9  | Required phone — submit empty shows error | ✅ | field-validation.spec.js |
| 2.10 | Required textarea — submit empty shows error | ✅ | field-validation.spec.js |
| 2.11 | Required checkbox — submit unchecked shows error | ✅ | field-validation.spec.js |
| 2.12 | Required GDPR — submit without consent shows error | ✅ | field-validation.spec.js |
| 2.13 | Required dropdown — submit without selection shows error | ✅ | field-validation.spec.js |
| 2.14 | Required multi-choice — submit without selection shows error | ✅ | field-validation.spec.js |
| 2.15 | Character limit — input/textarea rejects text over max-chars | ❌ | — |

---

## Group 3 — Form settings (confirmation behaviour)

All tests live in `form-settings.spec.js`.

| ID  | Test case | Status | Spec file |
|-----|-----------|--------|-----------|
| 3.1 | Redirect on submission — user lands on configured URL | ✅ | form-settings.spec.js |
| 3.2 | Custom success message — configured text shown after submit | ✅ | form-settings.spec.js |
| 3.3 | Store entries disabled — submission succeeds but no entry stored | ✅ | form-settings.spec.js |
| 3.4 | Page confirmation type — redirect to a configured WordPress page | ✅ | form-settings.spec.js |
| 3.5 | Store entries enabled (default) — entry appears in admin after submit | ✅ | form-settings.spec.js |
| 3.6 | Per-form GDPR compliance: do-not-store-entries enabled → no entry created | ✅ | form-settings.spec.js (covered by 3.3) |
| 3.7 | Per-form compliance: auto-delete setting saves and is visible in editor | ❌ | — |

---

## Group 4 — Entries management

All tests live in `entries.spec.js`.

| ID  | Test case | Status | Spec file |
|-----|-----------|--------|-----------|
| 4.1 | Submitted entry appears in the admin entries list | ✅ | entries.spec.js |
| 4.2 | Entry detail contains the correct submitted field values | ✅ | entries.spec.js |
| 4.3 | Bulk delete removes selected entries | ✅ | entries.spec.js |
| 4.4 | CSV export downloads a file | ✅ | entries.spec.js |
| 4.5 | Mark entry as read / unread — status updates in the admin list | ✅ | entries.spec.js |

---

## Group 5 — Form embedding

All tests live in `form-embedding.spec.js`.

| ID  | Test case | Status | Spec file |
|-----|-----------|--------|-----------|
| 5.1 | Shortcode-embedded form renders on a page | ✅ | form-embedding.spec.js |
| 5.2 | Shortcode-embedded form submits successfully | ✅ | form-embedding.spec.js |
| 5.3 | Gutenberg block-embedded form renders on a page | ✅ | form-embedding.spec.js |
| 5.4 | Gutenberg block-embedded form submits successfully | ✅ | form-embedding.spec.js |

---

## Group 6 — Email notifications

All tests live in `email-notifications.spec.js`.
All tests are currently skipped — require MailHog wired into the wp-env Docker setup.

| ID  | Test case | Status | Spec file |
|-----|-----------|--------|-----------|
| 6.1 | Admin notification email is sent after form submission | ⏭️ | email-notifications.spec.js |
| 6.2 | Notification email body contains submitted field values | ⏭️ | email-notifications.spec.js |
| 6.3 | User confirmation email is sent to submitter's address | ❌ | — |

---

## Group 7 — CAPTCHA

All tests live in `captcha.spec.js`.

| ID  | Test case | Status | Spec file |
|-----|-----------|--------|-----------|
| 7.2 | reCAPTCHA v2 — submit without solving is blocked | ⏭️ | captcha.spec.js |
| 7.3 | Cloudflare Turnstile — submit without solving is blocked | ❌ | — |
| 7.4 | hCaptcha — submit without solving is blocked | ❌ | — |
| 7.5 | reCAPTCHA v3 — submit with low score is blocked | ❌ | — |
| 7.7 | CAPTCHA disabled — form submits normally without CAPTCHA widget | ✅ | captcha.spec.js |

> **Note:** 7.3–7.5 require external API keys; add them with `test.skip` pattern matching 7.2.

---

## Group 8 — Payments (Stripe)

All tests live in `payments.spec.js`.
All tests are currently skipped — require Stripe test keys.

| ID  | Test case | Status | Spec file |
|-----|-----------|--------|-----------|
| 8.1 | Payment field renders the Stripe card widget | ⏭️ | payments.spec.js |
| 8.2 | Valid Stripe test card — payment succeeds and success message shown | ⏭️ | payments.spec.js |
| 8.3 | Declined Stripe test card — error message shown | ⏭️ | payments.spec.js |
| 8.6 | Successful payment creates an entry in the admin entries list | ⏭️ | payments.spec.js |

---

## Group 9 — Global settings

All tests live in `global-settings.spec.js`.

| ID  | Test case | Status | Spec file |
|-----|-----------|--------|-----------|
| 9.1 | Global settings page loads without a JS error | ✅ | global-settings.spec.js |
| 9.2 | All main settings tabs are clickable and stay on settings page | ✅ | global-settings.spec.js |

---

## Group 10 — Spam protection

All tests live in `spam-protection.spec.js`.

| ID   | Test case | Status | Spec file |
|------|-----------|--------|-----------|
| 10.1 | Honeypot — bot fills hidden field → submission is blocked | ✅ | spam-protection.spec.js |
| 10.2 | Double-submit prevention — submit button disabled after first click | ✅ | spam-protection.spec.js |

---

## Group 11 — Form restrictions

All tests live in `form-restrictions.spec.js`.

| ID   | Test case | Status | Spec file |
|------|-----------|--------|-----------|
| 11.1 | Entry limit — form shows restriction message after limit reached | ✅ | form-restrictions.spec.js |
| 11.2 | Scheduling — restriction message shown when schedule has ended | ✅ | form-restrictions.spec.js |
| 11.3 | Scheduling — restriction message shown when schedule has not started yet | ✅ | form-restrictions.spec.js |

---

## Group 12 — Form lifecycle

All tests live in `form-lifecycle.spec.js`.

> **✅ Renaming done:** Comments in `form-lifecycle.spec.js` updated from `8.x` to `12.x`.

| ID   | Test case | Status | Spec file |
|------|-----------|--------|-----------|
| 12.1 | Duplicate form — copy appears in forms list with "(Copy)" suffix | ✅ | form-lifecycle.spec.js |
| 12.2 | Trash form — form disappears from All Forms view | ✅ | form-lifecycle.spec.js |
| 12.3 | Restore form — form reappears in All Forms view after restore from Trash | ✅ | form-lifecycle.spec.js |
| 12.4 | Delete permanently — form is fully removed and not restorable | ✅ | form-lifecycle.spec.js |

---

## Group 13 — AI form generation

All tests will live in `ai-form-builder.spec.js`.
Tests that require an API key should follow the `test.skip` pattern used in payments and CAPTCHA.

| ID   | Test case | Status | Spec file |
|------|-----------|--------|-----------|
| 13.1 | AI generation panel opens from the "Create Form" flow | ❌ | — |
| 13.2 | Generating a form with a prompt produces at least one block | ❌ | — |

---

## Suggested implementation order

Work these in priority order — top of each section first:

### Next up (P1 — no external dependencies)

- [x] **11.3** Scheduling not-started-yet *(done — form-restrictions.spec.js)*
- [x] **12.4** Delete permanently from trash *(done — form-lifecycle.spec.js)*
- [x] **2.7–2.10** Required validation for remaining field types *(done — field-validation.spec.js)*
- [x] **4.5** Entry read/unread status toggle *(done — entries.spec.js)*
- [x] **3.6** Per-form do-not-store-entries compliance *(already covered by test 3.3)*
- [ ] **2.15** Character limit — low E2E value (browser `maxlength` attribute, no server-side check); defer unless a custom error message is confirmed

### Later (P2 — needs infra or API keys, use skip pattern)

- [ ] **6.3** User confirmation email (needs MailHog)
- [ ] **7.3–7.5** Additional CAPTCHA types (need external keys)
- [ ] **13.1–13.2** AI form generation (needs AI API key)

### Nice to have

- [ ] **3.7** Auto-delete entries compliance setting
- [ ] **9.x** Global settings save (email From Name/From Email)
- [ ] **1.9** Inline button / advanced heading block behaviour
