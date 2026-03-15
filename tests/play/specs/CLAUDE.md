# Playwright E2E Specs — Writing Guide

Rules and patterns for writing reliable SureForms E2E tests. Each section is a lesson learned from real failures.

---

## Auth: Never do a full form-login inside tests

**Pattern:** `globalSetup` logs in once and saves `storageState.json`. All workers restore it.

**Why:** Multiple workers calling the WordPress login form simultaneously causes nonce collisions — WordPress redirects to `?reauth=1`, breaking every test in those workers.

**Correct:** Let `storageState` handle auth. The `loginAsAdmin` util now just navigates to `/wp-admin` and relies on the pre-loaded cookies.

```js
// DO — storageState is already loaded by playwright.config.js
test.beforeEach(async ({ page }) => {
  await loginAsAdmin(page); // navigates to /wp-admin, skips login form
});

// DON'T — never re-do a full form login inside specs
await page.goto('/wp-login.php');
await page.fill('#user_login', 'admin');
```

---

## Adding blocks: sidebar vs programmatic insertion

**Only these blocks appear in the quick-action sidebar by default:**
`input`, `email`, `textarea`, `checkbox`, `number`, `inline-button`, `advanced-heading`, `payment`

**All other blocks** (`phone`, `url`, `multi-choice`, `dropdown`, `gdpr`, `rating`, `date`, etc.) **must be inserted programmatically.**

Use `addFieldBlock(page, 'slug')` from `formHelpers.js` — it automatically routes sidebar blocks via click and all other blocks via `insertBlock`.

```js
// This handles routing automatically
await addFieldBlock(page, 'gdpr');       // programmatic
await addFieldBlock(page, 'input');      // sidebar click
await addFieldBlock(page, 'dropdown');   // programmatic
```

**Never** try to open the WP block inserter toolbar to add non-sidebar blocks — it is fragile and frequently breaks tests.

---

## Field CSS selectors

Always use the most specific class. Do not rely on element order (`.nth(N)`) unless you are certain only N elements exist.

| Field | Frontend input selector |
|---|---|
| Single line input | `input.srfm-input-input` |
| Email | `input.srfm-input-email` |
| Email confirm | `input.srfm-input-email-confirm` — **NOT** `input.srfm-input-email` |
| Phone | `input.srfm-input-phone` |
| Number | `input.srfm-input-number` |
| URL | `input.srfm-input-url` |
| Textarea | `textarea.srfm-textarea-text` |
| Checkbox | `input[type="checkbox"]` inside `.srfm-checkbox-input-wrap` |
| Submit button | `#srfm-submit-btn` |
| Success box | `.srfm-success-box` |
| Error message | `.srfm-error-message` |

---

## TomSelect dropdowns

The SureForms dropdown field uses TomSelect. The native `<select>` is hidden — use TomSelect's DOM instead.

```js
// Open the dropdown
await page.locator('.ts-control').first().click();

// Click an option
await page.locator('.ts-dropdown-content .option').filter({ hasText: 'Option A' }).click();
```

---

## GDPR field: no Required toggle

The `GDPR_Markup` PHP class hardcodes `$this->required = true`. There is no `required` attribute in `block.json` and no Required toggle in the block settings panel.

**Do not** call `enableRequiredField()` for a GDPR block — it will time out looking for a toggle that does not exist.

```js
// GDPR test — just add the block and submit without filling it
await addFieldBlock(page, 'gdpr');
const formURL = await publishFormAndGetURL(page);
// No enableRequiredField() needed
```

---

## Email confirmation block settings

The block settings toggle in the editor sidebar is labelled **"Enable Email Confirmation"**, not "confirm email".

```js
// Correct label
const toggle = panel.locator('.components-toggle-control')
  .filter({ hasText: /Enable Email Confirmation/i });

// Wrong — this will time out
const toggle = panel.locator('.components-toggle-control')
  .filter({ hasText: /confirm email/i });
```

---

## Form Confirmation dialog: redirect URL

The Form Confirmation dialog validates redirect URLs and **rejects `http://` URLs** with "Suggestion: URL should use HTTPS". This sets `hasValidationErrors = true` and skips the `updateMeta` call — the meta is never saved.

**Bypass:** call `dispatch('core/editor').editPost()` directly.

```js
await page.evaluate(() => {
  const { dispatch } = window.wp.data;
  dispatch('core/editor').editPost({
    meta: {
      _srfm_form_confirmation: [
        {
          confirmation_type: 'custom url',
          custom_url: 'http://localhost:8888/wp-admin/',
        },
      ],
    },
  });
});
```

Only use the dialog UI when you are setting a success message (no validation issues there).

---

## Quill editor debounce (Form Confirmation success message)

The Form Confirmation success message editor uses `useDebouncedCallback` with a 500ms delay. If you close the dialog immediately after typing, the editor store has not updated yet.

```js
await quillEditor.click();
await page.keyboard.press('Control+a');
await page.keyboard.type('Your submission was received!');

// Wait for the debounced auto-save to fire
await page.waitForTimeout(1000);

// Now safe to close the dialog
await page.keyboard.press('Escape');
```

This is one of the **only valid uses** of `waitForTimeout` in these tests. All other sleeps should be replaced with `expect(...).toBeVisible({ timeout })`.

---

## Opening the Form Settings dialog

```js
const { openFormSettingsDialog } = require('../utils/formHelpers');

// Opens the dialog and clicks the named nav item
await openFormSettingsDialog(page, 'Form Confirmation');
```

Internally: clicks `.srfm-form-settings-button` → finds `.srfm-form-settings-nav-item-label` by text → waits for `.srfm-dialog-panel`.

The dialog has `exitOnEsc` — pressing Escape closes it reliably without hitting the WP admin bar click-intercept.

---

## Entry detail navigation

Entry rows in the admin entries table contain `<a href="#/entry/{id}">` links. Click the **link**, not the row element.

```js
// Correct
const entryLink = page.locator('a[href*="#/entry/"]').first();
await entryLink.click();

// Wrong — the row itself may not be clickable
await entryRow.click();
```

---

## No `waitForTimeout` except for debounce

`expect(...).toBeVisible({ timeout: N })` already polls. Fixed sleeps waste time and hide real timing issues.

```js
// WRONG — wasteful sleep
await page.waitForTimeout(2000);
await expect(page.locator('.srfm-success-box')).toBeVisible();

// CORRECT — just use the timeout on expect
await expect(page.locator('.srfm-success-box')).toBeVisible({ timeout: 15000 });
```

The **only exception** is the 1000ms wait after Quill editor input (debounce pattern above).

---

## Test isolation: each test creates its own form

Every test creates a fresh form via `createBlankForm(page)`. Tests are safe to run fully in parallel.

**Never** rely on a form created by a previous test — test execution order is not guaranteed.

---

## CI sharding

Tests run as 4 parallel shards in CI (`--shard=N/4`). Each shard:
- Gets its own `wp-env` instance
- Runs its own `globalSetup` (creates `storageState.json` inside the shard)
- Has 1 worker (`workers: 1` on CI)

Local runs use 4 workers with a single `globalSetup`.

---

## Forms list admin UI (`admin.php?page=sureforms_forms`)

- Table renders as standard `<table>` via `@bsf/force-ui` Table.
- Per-row action buttons are **icon-only** — no `aria-label`, no `title`. Find them by position inside the row: `row.locator('button').nth(N)`.
  - **The Edit action renders as `<a href="...">` (not `<button>`), so it is NOT counted when indexing buttons.**
  - **The Entries column renders a `<Button>` (link variant, count as text) which IS button[0] in EVERY row.**
  - Active form button order: EntriesCount(0) · View(1) · Export(2) · Duplicate(3) · Trash(4)
  - Trashed form button order: EntriesCount(0) · Restore(1) · Delete Permanently(2)
- Duplicate and Trash open a `[role="dialog"]` ConfirmationDialog. Restore fires immediately (no dialog).
- Status filter is a `@bsf/force-ui` Select. Click the button showing the current label → options appear as `[role="option"]` or `<li>` in a portal.
- Confirmation dialog confirm button text matches the action: `"Duplicate"`, `"Move to Trash"`, `"Delete Permanently"`.

## Setting post meta directly via editPost

When the UI rejects a value (e.g., HTTPS validation on redirect URL), bypass the UI and call `editPost` directly:

```js
await page.evaluate(() => {
  window.wp.data.dispatch('core/editor').editPost({
    meta: { _srfm_form_restriction: JSON.stringify({ status: true, maxEntries: 1, ... }) }
  });
});
```

This works for any meta key registered with `show_in_rest: true` on the `sureforms_form` post type:
- `_srfm_form_confirmation` — confirmation type / redirect / success message
- `_srfm_compliance` — GDPR / do-not-store entries (array type, pass as JS array)
- `_srfm_form_restriction` — entry limit + scheduling (string type, pass as `JSON.stringify({...})`)

## Form restrictions

When a form is restricted (entry limit reached, or schedule ended), the PHP renders:
```html
<div class="srfm-form-restriction-message">
  <p class="srfm-form-restriction-text">Your custom message here</p>
</div>
```
Assert with: `expect(page.locator('.srfm-form-restriction-message')).toBeVisible()`

The restriction check happens **at render time (PHP)** — not at submission time. So:
- For entry-limit tests: submit once to consume the slot, then navigate back to the form URL.
- For schedule tests: set a past end date → navigate to form → restriction shows immediately (no submission needed).

## Honeypot spam protection

Every SureForms form renders `<input name="srfm-honeypot-field">`. Filling it simulates a bot:

```js
await page.evaluate(() => {
  const f = document.querySelector('[name="srfm-honeypot-field"]');
  if (f) f.value = 'spambot';
});
```

Server returns `wp_send_json_error` → `.srfm-success-box` must NOT appear.

## Helpful utilities

All utilities live in `tests/play/utils/`.

| Utility | Purpose |
|---|---|
| `loginAsAdmin(page)` | Navigates to `/wp-admin`, restores auth from storageState |
| `createBlankForm(page)` | Creates a new blank SureForms form in the editor |
| `addFieldBlock(page, slug)` | Adds a field block (sidebar or programmatic) |
| `publishFormAndGetURL(page)` | Publishes the current form and returns its front-end URL |
| `openFormSettingsDialog(page, navItem)` | Opens Form Behavior dialog to a specific nav item |
| `setFormTitle(page, title)` | Sets the editor post title via `editPost` (avoids fragile UI title input) |
| `createWPPage(page, title, content)` | Creates a published WP page via REST API |

When a new utility is needed, add it here rather than inlining complex selectors in spec files.
