# Testing SureForms WPML Compatibility

This branch (`claude/optimistic-jennings-8fb8b9`) implements native WPML support for SureForms. The form remains a single CPT post; the visitor's language drives which labels, placeholders, options, success messages and confirmation messages render; entries from all languages roll into the same form, tagged with the submission language.

This guide walks through end-to-end verification.

---

## Prerequisites

- **WordPress** 6.4+
- **WPML Multilingual CMS** 4.5+ (paid plugin — licence required)
- **WPML String Translation** (paid add-on — required for translatable strings)
- A SureForms install on this branch

## Activate the plugins

```bash
wp plugin activate sureforms      --path=/Users/adityajain/Valet/sureforms
wp plugin activate sitepress-multilingual-cms       --path=/Users/adityajain/Valet/sureforms
wp plugin activate wpml-string-translation          --path=/Users/adityajain/Valet/sureforms
```

If you don't yet have WPML installed, grab `sitepress-multilingual-cms.zip` and `wpml-string-translation.zip` from your WPML account, drop them into `wp-content/plugins/`, then `wp plugin activate ...`.

After activation, complete WPML setup:
- **WPML → Languages**: pick at least two languages, e.g. English (default) + German + French.
- **WPML → Translation Management → Multilingual Content Setup**: confirm `Posts` is "Translatable — use translation". (This will NOT apply to `sureforms_form`; see step 2 below.)

---

## Step 1 — Plugin loads cleanly, with or without WPML

```bash
wp eval '
echo "Manager: " . get_class( SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager::get_instance() ) . "\n";
echo "Provider: " . get_class( SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager::get_instance()->provider() ) . "\n";
echo "is_active: " . var_export( SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager::get_instance()->provider()->is_active(), true ) . "\n";
echo "current: " . SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager::get_instance()->provider()->current_language() . "\n";
echo "default: " . SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager::get_instance()->provider()->default_language() . "\n";
' --path=/Users/adityajain/Valet/sureforms
```

Expected with WPML active:
```
Manager: SRFM\Inc\Compatibility\Multilingual\Multilingual_Manager
Provider: SRFM\Inc\Compatibility\Multilingual\Providers\WPML_Provider
is_active: true
current: en
default: en
```

Expected without WPML:
```
Provider: SRFM\Inc\Compatibility\Multilingual\Providers\Null_Provider
is_active: false
current:
default:
```

---

## Step 2 — `sureforms_form` CPT is NOT translatable

Go to **WPML → Translation Management → Multilingual Content Setup → Custom Posts** (or the equivalent path in your WPML version).

The `sureforms_form` row should appear with **"Not translatable"** locked. No "Add translation" UI appears in the form editor. Why: `wpml-config.xml` declares `<custom-type translate="0">sureforms_form</custom-type>`. WPML respects this — one form, one post_id, entries unify across languages.

---

## Step 3 — Block attribute strings auto-register on save

1. Create a new SureForms form. Add a few fields:
   - Text input with label "Your Name" and placeholder "Enter your name"
   - Email input with label "Email Address"
   - Dropdown with options "Option A", "Option B", "Option C"
   - Submit button with text "Send"
2. Save the form.

Open **WPML → String Translation**. In the "Select strings within domain" dropdown, pick `sureforms`. You should see entries like:
- The block attribute strings (`label`, `placeholder`, etc.) for each field — registered by WPML's Gutenberg scanner via `wpml-config.xml`
- The form-level strings registered programmatically by SureForms — names like `form_{id}_submit_button`, `form_{id}_confirmation_0_message`, `form_{id}_notification_0_subject`, etc.

If you see strings under domain `sureforms`, registration is working.

---

## Step 4 — Translate the strings

In WPML String Translation:
1. Filter strings to your form's domain `sureforms`.
2. Find `form_{id}_submit_button` (or the block attribute for the submit button — both should be there). Click "translations" and enter German + French.
3. Translate a couple of field labels and the success/confirmation message.

Save.

---

## Step 5 — Render the form in German

1. Add the form to a page via the SureForms block or `[sureforms id="..."]` shortcode.
2. Make sure that page is translated to German (WPML → translate the host page to DE; the form shortcode/block carries over).
3. Visit `/de/your-page-slug/`.

You should see:
- ✅ Submit button text in German
- ✅ Field labels in German (for whatever you translated)
- ✅ Dropdown option labels in German
- ✅ Placeholders in German
- Field validation messages — these use core WP `__()` translations; should also be German if you have the `sureforms-de_DE.mo` file installed

If labels still render in English: open the rendered page's HTML and search for the field's label. It will tell you whether the issue is in (a) the form not being on a translated page, (b) the string not yet translated in WPML, or (c) WPML's render_block_data filter not firing on SureForms blocks (would indicate a deeper integration gap).

---

## Step 6 — Submit the form in German

1. Fill the form on the `/de/` page and submit.
2. Confirm the **success message renders in German** (because we translate `_srfm_form_confirmation` at render time via `String_Translator::translate_confirmation_message()`).
3. Inspect the database:

```sql
SELECT ID, form_id, language, created_at, JSON_EXTRACT(form_data, '$') AS form_data
FROM wp_srfm_entries
ORDER BY ID DESC
LIMIT 5;
```

The new row should have `language = 'de'`.

---

## Step 7 — Submit the form in English

1. Switch to the English version of the page (`/your-page-slug/` or via the language switcher).
2. Submit again.
3. Confirm the form renders in English, success message in English.
4. The new entry row has `language = 'en'`.
5. **There is still only one form post** (same `form_id` for both entries) — confirm via `wp post list --post_type=sureforms_form --path=...`.

---

## Step 8 — Admin entries list shows Language column

Go to the SureForms entries admin (sidebar → SureForms → Entries).

- New **Language** column between Date & Time and Actions
- DE / EN / FR uppercase for entries submitted on translated front-end pages
- `—` for older entries or non-WPML submissions
- Header is sortable — clicking it toggles ASC/DESC and re-runs the query

---

## Step 9 — Form restriction message translates

1. In the form editor → form settings → enable form restriction (e.g., "scheduled close" or "max entries"). Set a custom restriction message like "This form is closed".
2. Save the form.
3. In WPML String Translation, find `form_{id}_restriction_message`. Translate to German.
4. Trigger the restriction (set the close date to past, or hit max entries).
5. Visit the form on `/de/`. The restriction notice should render in German.

---

## Step 10 — Deactivate WPML and confirm graceful fallback

```bash
wp plugin deactivate wpml-string-translation sitepress-multilingual-cms --path=/Users/adityajain/Valet/sureforms
```

The form should still render in English with zero errors. New submissions write `language = ''` to the entry row (no provider → empty language code).

The Multilingual_Manager resolves to `Null_Provider`, which makes every translation call a pass-through.

---

## What's still NOT translated (intentional, deferred to Pro)

| Surface | Where it'll be added | Why deferred |
|---|---|---|
| **Email notification subject / body / from-name** sent to the user | `sureforms-pro/inc/multilingual/email-translator.php` (Pro Phase 6) | Needs `wpml_switch_language` to the entry's language before composing the mail body. The Pro path will hook `srfm_email_notification` filter and use the entry's `language` column. |
| **Field labels inside `{all_data}` smart tag** | Likely already correct — labels are embedded into the submitted field name at submit time, so they're in the visitor's language naturally. Pro can layer additional translation if needed via `srfm_before_processing_all_data_field`. | |
| **Admin translation-status UI** in the form editor | Pro Phase 8 | Polish UX; not blocking core functionality. |
| **Per-language entries filter dropdown** | Pro Phase 8 | Admin sorting works; filter is the next step. |
| **CSV export of entries with language column** | Pro Phase 8 | Export already includes raw entry data; this Pro work adds the column header explicitly. |
| **Polylang support** | Future v2 | Provider interface is generic enough; just needs a `Polylang_Provider` implementation. |

---

## Troubleshooting

### Strings don't appear in WPML String Translation

- Confirm WPML String Translation add-on is active (`wp plugin list --status=active --path=...`).
- Re-save the form — `String_Collector::on_form_save()` runs on `save_post_sureforms_form` priority 20.
- Filter by domain `sureforms` in the WPML String Translation UI; the dropdown is at the top.
- Check the SureForms entry log — if the provider was inactive at save time, no registration happened. Activate WPML, then re-save the form.

### Translated labels don't render on `/de/`

- Confirm the host page is translated. The form shortcode/block must live on a translated page; otherwise the language context defaults to the canonical language.
- Open the rendered HTML and search for the field's `name` attribute. Compare to the original — if the original text appears, the string isn't translated yet in WPML String Translation.
- Verify `apply_filters('wpml_current_language', null)` returns `'de'` on the rendered page (use `wp eval` from a `/de/` admin context — tricky; alternative: temporarily `error_log()` the value).

### Entry shows `language = ''` even with WPML active

- Confirm the form was submitted from a translated page (not directly via the admin or `?lang=` query string in some WPML modes).
- Check `Multilingual_Manager::get_instance()->provider()->current_language()` at request time. With WPML's "Different domains" mode, the language is set at WP load; with query-string mode (`?lang=de`), it depends on URL parsing.

### CI failures after pulling this branch

```bash
composer install
npm ci
composer run lint          # PHPCS
composer run phpstan       # PHPStan level 9
vendor/bin/phpinsights analyse --no-interaction   # Code/Architecture/Style 100/100/100
npm run lint-js            # ESLint
npm run build:script       # Webpack
```

PHPUnit needs WP test fixtures — set up via `bin/install-wp-tests.sh sureforms_test root '' localhost latest` once.

---

## Commits on this branch

```
9d8390e02  Phase 0   provider abstraction + entries.language column + wpml-config.xml
ae5776304  Phase 2+3 string collector + render-time translator (submit button, confirmation)
65c51d066  Phase 5   Language column in entries admin
<HEAD>     Phase 5+  restriction message render-time translation
```

When you're satisfied, push the branch and open a PR to `dev`.
