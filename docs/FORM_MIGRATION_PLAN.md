# SureForms — Form Migration Implementation Plan

## 1. Goal

Build a one-click migrator that imports forms (and optionally entries) from the 5 major WordPress form plugins into SureForms, modelled on Fluent Forms' production-tested migrator architecture (~5,400 LOC across 5 sources, deployed on 700K+ sites).

**Sources to support in v1**: Contact Form 7, WPForms (Lite + Pro), Gravity Forms, Ninja Forms, Caldera Forms.

## 2. Why Fluent Forms' pattern

Fluent's full migrator source was read end-to-end (`fluentform/app/Services/Migrator/`). Their architecture is:

- **One abstract base class** with the import loop, idempotency map, and a ~1,200-line canonical field dictionary.
- **Thin per-source subclasses** (500–900 LOC each) that only do (a) `source_type → fluent_id` mapping and (b) source-specific args formatting.
- **4 AJAX endpoints** for: list sources, list forms in source, import forms, import entries.
- **Template Method pattern** — base class drives the flow, subclasses fill in 5 abstract methods (`exist`, `get_source_forms`, `get_source_form_id`, `get_source_form_name`, `build_form_content`, `get_form_metas`).

It's clean, proven, and easy to extend. SureForms will adopt the same shape, adapted for:

1. **REST instead of AJAX** — SureForms already uses `/sureforms/v1/` namespace (`inc/rest-api.php`); AJAX would be inconsistent.
2. **Block markup output instead of JSON** — SureForms forms are Gutenberg block strings in `post_content` of the `sureforms_form` CPT, not JSON in a custom table. So the importer's final stage is to serialize block markup, not assemble a flat field array.
3. **15 SureForms blocks as targets** — instead of Fluent's ~25 field types.

## 3. Sources to import — confirmed field type maps

These are lifted directly from Fluent's `fieldTypes()` methods in each migrator class. Total source field types we need to handle: **110** (with overlap).

### 3.1 Contact Form 7 (13 form-tag types → SureForms blocks)
Source: `wpcf7_contact_form` post type, `_form` meta (shortcode template).

| CF7 form-tag | SureForms block | Notes |
|---|---|---|
| `text`, `text*` | `srfm/input` | type=text |
| `email`, `email*` | `srfm/email` | |
| `url`, `url*` | `srfm/url` | |
| `tel`, `tel*` | `srfm/phone` | |
| `number`, `number*` | `srfm/number` | |
| `range` | `srfm/number` | slider mode |
| `date` | `srfm/input` | type=date (no native date block) |
| `textarea`, `textarea*` | `srfm/textarea` | |
| `select`, `select*` | `srfm/dropdown` | `multiple` → multi-select |
| `checkbox`, `checkbox*` | `srfm/checkbox` | |
| `radio` | `srfm/multi-choice` | |
| `acceptance` | `srfm/gdpr` | tnc_html → content |
| `file`, `file*` | unsupported in v1 | no native `srfm/file` — flag |
| `quiz`, `captchar`, `hidden` | skipped | legacy / not in v1 scope |

Detection: `defined('WPCF7_PLUGIN')`. Entries: requires Flamingo plugin.

### 3.2 WPForms (22 field types → SureForms blocks)
Source: CPT `wpforms`, field data as JSON in `post_content`.

| WPForms type | SureForms block | Notes |
|---|---|---|
| `text` | `srfm/input` | |
| `email` | `srfm/email` | |
| `url` | `srfm/url` | |
| `phone` | `srfm/phone` | |
| `number`, `number-slider` | `srfm/number` | |
| `textarea`, `richtext` | `srfm/textarea` | rich → fallback |
| `select`, `multi_select` | `srfm/dropdown` | |
| `radio` | `srfm/multi-choice` | |
| `checkbox` | `srfm/checkbox` | |
| `name` | `srfm/input × 3` | split first/middle/last |
| `address` | `srfm/address` | structured fields |
| `date-time` | `srfm/input` | type=date until native block |
| `password` | `srfm/input` | type=password |
| `hidden` | `srfm/input` | type=hidden |
| `html`, `divider` | structural — core/html or section break | |
| `pagebreak` | unsupported in v1 | no native multi-step yet |
| `file-upload`, `rating`, `layout` | unsupported in v1 | flag |

Detection: `function_exists('wpforms')`. Entries: WPForms Pro only.

### 3.3 Gravity Forms (22 field types → SureForms blocks)
Source: custom tables `gf_form` + `gf_form_meta` (serialized PHP array).

| Gravity type | SureForms block | Notes |
|---|---|---|
| `email` | `srfm/email` | |
| `text` | `srfm/input` | |
| `name` | `srfm/input × 3` | first/middle/last sub-inputs |
| `hidden` | `srfm/input` (type=hidden) | |
| `textarea` | `srfm/textarea` | |
| `website` | `srfm/url` | |
| `phone` | `srfm/phone` | |
| `select`, `multiselect` | `srfm/dropdown` | |
| `checkbox` | `srfm/checkbox` | |
| `radio` | `srfm/multi-choice` | |
| `date`, `time` | `srfm/input` (type=date/time) | |
| `number` | `srfm/number` | |
| `address` | `srfm/address` | |
| `consent` | `srfm/gdpr` | |
| `captcha` | unsupported | external service |
| `html` | core/html (or skip) | |
| `section` | section break / divider | |
| `page` | unsupported in v1 | multi-step |
| `list` | unsupported in v1 | repeater not in srfm |
| `fileupload` | unsupported in v1 | no srfm/file |

Detection: `class_exists('GFForms')`. Entries: `gf_entry`, `gf_entry_meta` tables.

### 3.4 Ninja Forms (27 field type aliases → SureForms blocks)
Source: custom tables `nf3_forms`, `nf3_fields`, `nf3_field_meta` (JOIN-based).

| Ninja type | SureForms block | Notes |
|---|---|---|
| `email` | `srfm/email` | |
| `textbox`, `firstname`, `lastname`, `address`, `city`, `zip` | `srfm/input` | (Ninja splits address into individual text fields) |
| `liststate`, `listcountry` | `srfm/dropdown` | |
| `textarea` | `srfm/textarea` | |
| `phone` | `srfm/phone` | |
| `select`, `listselect` | `srfm/dropdown` | |
| `listmultiselect` | `srfm/dropdown` (multi) | |
| `radio`, `listradio`, `listimage`, `toggle_switch` | `srfm/multi-choice` | |
| `checkbox`, `listcheckbox` | `srfm/checkbox` | |
| `date` | `srfm/input` (type=date) | |
| `number` | `srfm/number` | |
| `hidden` | `srfm/input` (type=hidden) | |
| `html` | core/html | |
| `hr` | section divider | |
| `starrating` | unsupported in v1 | no srfm/rating |
| `repeater` | unsupported in v1 | |
| `recaptcha` | spam protection — emit as form-level setting | |
| `submit` | handled by `srfm/inline-button` | |

Detection: `defined('NF_SERVER_URL')`. Entries: `nf_sub` CPT.

### 3.5 Caldera Forms (26 field types → SureForms blocks)
Source: `Caldera_Forms_Forms::get_forms()` static API. Caldera is unmaintained — migration is a one-shot rescue.

| Caldera type | SureForms block | Notes |
|---|---|---|
| `email` | `srfm/email` | |
| `text` | `srfm/input` | |
| `hidden` | `srfm/input` (type=hidden) | |
| `textarea`, `paragraph`, `wysiwyg` | `srfm/textarea` | |
| `url` | `srfm/url` | |
| `phone`, `phone_better` | `srfm/phone` | |
| `select`, `dropdown` | `srfm/dropdown` | |
| `filtered_select2` | `srfm/dropdown` (multi) | |
| `radio`, `toggle_switch` | `srfm/multi-choice` | |
| `checkbox` | `srfm/checkbox` | |
| `date_picker`, `date` | `srfm/input` (type=date) | |
| `number`, `range`, `calculation` | `srfm/number` | |
| `range_slider` | `srfm/number` (slider) | |
| `gdpr` | `srfm/gdpr` | |
| `section_break`, `html` | structural | |
| `color_picker`, `star_rating`, `file`, `cf2_file`, `advanced_file` | unsupported in v1 | flag |
| `button` | handled by `srfm/inline-button` | |

Detection: `defined('CFCORE_VER')`. Caldera abandoned in 2022 — entries critical.

## 4. SureForms target blocks (the destination schema)

From `find sureforms/inc/blocks/*/block.json` — **15 blocks** total:

| Block | Type | Used for |
|---|---|---|
| `srfm/form` | wrapper | Outer form container |
| `srfm/input` | field | Text/email/url/phone/password/date/hidden (HTML type via attr) |
| `srfm/email` | field | Email input |
| `srfm/textarea` | field | Paragraph text |
| `srfm/url` | field | URL input |
| `srfm/number` | field | Numeric input + slider mode |
| `srfm/phone` | field | International phone |
| `srfm/dropdown` | field | Select / multi-select |
| `srfm/multi-choice` | field | Radio / multiple choice |
| `srfm/checkbox` | field | Checkbox group |
| `srfm/address` | field | Structured address (line1/2/city/state/zip/country) |
| `srfm/gdpr` | field | Consent checkbox |
| `srfm/payment` | field | Stripe payment |
| `srfm/payment-history` | display | Past payments table |
| `srfm/inline-button` | structural | Submit / action button |

**Gaps acknowledged**: no native rich-text, file upload, signature, rating, date-picker, multi-step (page break), or repeater blocks. Fields mapped to these in source plugins are flagged as `unsupported_fields[]` in the v1 response and surfaced in the import UI as warnings. They will be added in subsequent releases.

## 5. Code layout (mirrors Fluent's, adapted to SureForms conventions)

```
sureforms/inc/migrator/
├── class-migrator-bootstrap.php           # ~200 LOC — REST routes, capability checks, factory
├── class-base-migrator.php                # ~800 LOC — abstract import loop + idempotency + helpers
├── class-block-templates.php              # ~600 LOC — canonical srfm/* block markup templates
├── importers/
│   ├── class-cf7-importer.php             # ~600 LOC — shortcode regex parser
│   ├── class-wpforms-importer.php         # ~700 LOC — JSON decode
│   ├── class-gravity-importer.php         # ~800 LOC — serialized array parser
│   ├── class-ninja-importer.php           # ~600 LOC — JOIN across nf3_* tables
│   └── class-caldera-importer.php         # ~500 LOC — Caldera static API
└── views/admin-page.php                   # ~150 LOC — admin page mount point

sureforms/src/admin/migrator/              # React UI (~800 LOC)
├── MigratorApp.jsx                        # Top-level component
├── SourceList.jsx                         # Tile per detected source
├── FormSelector.jsx                       # Pick forms to import
├── ImportPreview.jsx                      # Dry-run rendered output
├── ImportResult.jsx                       # Success + unsupported warnings
└── EntryImporter.jsx                      # Per-form entry import
```

**Total estimate: ~5,750 LOC** for all 5 sources (close to Fluent's ~5,400 LOC, with the extra slack accounting for SureForms' block-markup serialization).

## 6. REST API design

Namespace: `/wp-json/sureforms/v1/migrator/` (extending the existing `/sureforms/v1/` namespace from `inc/rest-api.php`).

| Method | Route | Body / Query | Returns |
|---|---|---|---|
| GET | `/migrator/sources` | — | `[{key, title, installed, form_count, supports_entries}]` |
| GET | `/migrator/sources/{key}/forms` | — | `[{id, name, field_count, imported_srfm_id, last_imported_at}]` |
| POST | `/migrator/sources/{key}/import` | `{form_ids: [], dry_run: bool, behavior: {}, post_status?: 'draft'\|'publish'}` | `{imported: [{srfm_id, edit_url, source_id}], failed: [], skipped: [], unsupported_fields: [], preview?: {}}` |
| POST | `/migrator/sources/{key}/forms/{id}/entries` _(planned — P3, not yet registered)_ | `{limit?: 1000}` | `{count_imported, count_skipped, count_total}` |

**Security model** (per `sureforms/CLAUDE.md`):
- Every route's `permission_callback` requires `current_user_can('manage_options')`.
- REST cookie nonce (`X-WP-Nonce`) for all routes — these are admin-only, no public access.
- Sanitize `form_ids` via `array_map('absint', ...)`, `key` via allowlist `['cf7','wpforms','gravity','ninja','caldera']`.

## 7. Canonical block template dictionary

Analogue of Fluent's 1,200-line `defaultFieldConfig()` array, but emitting Gutenberg block markup strings instead of field-config arrays.

One method per target block in `class-block-templates.php`:

```php
final class SRFM_Block_Templates {

    public static function input( array $args ): string {
        $defaults = [
            'label'        => '',
            'placeholder'  => '',
            'required'     => false,
            'field_type'   => 'text',  // text|email|number|password|date|hidden
            'help_text'    => '',
            'default_val'  => '',
            'min_length'   => '',
            'max_length'   => '',
            'css_class'    => '',
        ];
        $attrs = wp_parse_args( $args, $defaults );
        // Match the schema in src/blocks/input/block.json
        $json  = wp_json_encode( self::strip_empty( [
            'label'        => $attrs['label'],
            'placeholder'  => $attrs['placeholder'],
            'required'     => $attrs['required'],
            'fieldType'    => $attrs['field_type'],
            'help'         => $attrs['help_text'],
            'defaultValue' => $attrs['default_val'],
            'minLength'    => $attrs['min_length'],
            'maxLength'    => $attrs['max_length'],
            'className'    => $attrs['css_class'],
        ] ) );
        return "<!-- wp:srfm/input {$json} /-->\n";
    }

    public static function email( array $args ): string { /* ... */ }
    public static function dropdown( array $args ): string { /* ... */ }
    public static function checkbox( array $args ): string { /* ... */ }
    // ... one per srfm/* block

    public static function form_wrapper( string $inner_content ): string {
        return "<!-- wp:srfm/form -->\n{$inner_content}\n<!-- /wp:srfm/form -->";
    }
}
```

The base migrator's import loop concatenates the per-field strings, wraps them in `form_wrapper()`, and persists via `wp_insert_post(['post_type' => 'sureforms_form', 'post_content' => $markup])`.

## 8. Base migrator contract (abstract methods)

```php
abstract class SRFM_Base_Migrator {

    protected string $key;                  // 'cf7' | 'wpforms' | 'gravity' | 'ninja' | 'caldera'
    protected string $title;                // Display name
    protected array  $unsupported_fields = [];

    // Per-source plumbing (abstract — subclasses implement)
    abstract public function exist(): bool;
    abstract protected function get_source_forms(): array;
    abstract protected function get_source_form_id( array $form );
    abstract protected function get_source_form_name( array $form ): string;
    abstract protected function build_form_content( array $form ): string; // concatenated block markup
    abstract protected function get_form_metas( array $form ): array;       // notifications, confirmations
    // NOTE: get_entries() is planned for the entries-migration phase (P3); not shipped yet.

    // Shared (in base):
    public function list_forms(): array;
    public function count_source_forms(): int;
    public function import_forms( array $selected_ids = [], bool $dry_run = false, array $behavior = [], string $post_status = 'publish' ): array;
    protected function find_existing_srfm_id( $source_id ): int;
    protected function record_import_mapping( int $srfm_id, $source_id ): void;
}
```

## 9. Idempotent re-imports

Same pattern as Fluent: a single `wp_option('srfm_imported_forms_map')` storing:

```php
[
    123 => [ 'source_id' => 'cf7-45', 'source_key' => 'cf7',     'last_imported' => '2026-05-22 10:00:00' ],
    124 => [ 'source_id' => '8',      'source_key' => 'wpforms', 'last_imported' => '2026-05-22 10:01:00' ],
]
```

`is_already_imported()`:
1. Look up `source_key` + `source_id` in the map.
2. If found and the matching `sureforms_form` post still exists → **update** (not insert).
3. If the SureForms form was deleted → prune the stale entry, fresh insert.

## 10. Unsupported field tracking

Same pattern as Fluent:
- `$this->unsupported_fields[]` is pushed each time a source field's type isn't in the source's `field_type_map`, capturing the field's user-facing label (not internal id).
- Returned in the REST response as `unsupported_fields: []`.
- Admin UI renders these as a yellow callout: "These fields couldn't be migrated to SureForms — add them manually after import: Field A, Field B, Field C."

## 11. Entry migration

- **Form-by-form, not bulk** — same as Fluent.
- **Hard cap of 1,000 entries per form**, override via the `srfm/entry_migration_max_limit` filter.
- **Idempotent**: import deletes existing SureForms entries for that form first (via the same `resetEntries()` pattern Fluent uses) before inserting.
- **Files**: copy via `WP_Filesystem_Direct::copy()` into `wp-content/uploads/sureforms/migrated/`.

## 12. Admin UI flow

Lives at **WordPress → SureForms → Tools → Migrator** (new sub-tab next to existing export/import).

```
[ Step 1: Select source ]   Detected plugins shown as tiles with logo + install count
  ▼  click "Migrate from Contact Form 7"

[ Step 2: Select forms ]    Table of forms in source (name | fields | already-imported badge)
  ▼  bulk select + "Preview"

[ Step 3: Dry-run preview ] Server returns block markup; UI renders in a code pane
                            + warning callout for unsupported fields
  ▼  "Confirm & Import"

[ Step 4: Result ]          "Imported X forms." Links: [Edit in SureForms] [Import entries]
                            Unsupported-fields list (if any)
```

The dry-run preview is the **one improvement over Fluent's UX** — it answers the user's "is this going to break anything?" anxiety before they commit.

## 13. Implementation phasing

Four mergeable PRs, each ~7–10 working days:

| Phase | Deliverable | LOC |
|---|---|---|
| **P1: Foundation** | `Base_Migrator`, `Block_Templates`, REST routes, admin page shell, CF7 importer end-to-end | ~2,400 |
| **P2: WPForms + Gravity** | Two more importers + dry-run preview UI | ~1,700 |
| **P3: Ninja + Caldera** | Final two importers + entry import flow | ~1,300 |
| **P4: Polish** | Field-mapping overrides, entry import UI, docs, E2E tests | ~350 |

Total: ~5,750 LOC delivered across ~4–5 weeks.

## 14. Testing approach

- **Unit tests** (PHPUnit, per `composer test`): per importer, fixture-driven — store sample source-form payloads in `tests/fixtures/migrator/{source}/*.json` and assert the produced block markup string matches a golden snapshot.
- **Integration tests**: a real CF7 / WPForms / Gravity form created via their PHP APIs in a `tests/Integration/Migrator/` test, imported via `Base_Migrator::import_forms()`, then assert a `sureforms_form` post exists with the right block content.
- **Playwright E2E** (per `npm run play:run`): full admin flow — open Migrator, pick CF7, select a form, dry-run, confirm import, verify the new form renders correctly on the front-end.
- **Manual exploratory**: all 5 source plugins are already installed locally, so the test environment is real, not mocked.

## 15. Verification (when is this done)

1. All 5 importers exist under `sureforms/inc/migrator/importers/` and pass their PHPUnit fixtures.
2. REST endpoints respond correctly: `GET /sureforms/v1/migrator/sources` lists the 5 sources with `installed: true` for whichever plugins are active.
3. Admin page at SureForms → Tools → Migrator renders the tile-based source picker.
4. Dry-run for a non-trivial CF7 form (text + email + textarea + radio + acceptance) produces valid Gutenberg block markup that renders correctly in the SureForms editor.
5. Re-importing the same source form updates the existing SureForms form (not a duplicate).
6. Entry migration imports up to 1,000 entries for a WPForms Pro form and the entries appear in SureForms' Entries admin.
7. Unsupported-field warnings appear in the UI for source fields without a SureForms target (e.g. CF7 `[file]`, WPForms `rating`).
8. PHPStan level 9 passes (`composer phpstan`) and PHPCS passes (`composer lint`).

## 16. Reference source files (Fluent Forms — production reference)

| Fluent file | Our equivalent | Why useful |
|---|---|---|
| `app/Services/Migrator/Bootstrap.php` | `class-migrator-bootstrap.php` | AJAX → REST route mapping |
| `app/Services/Migrator/Classes/BaseMigrator.php` | `class-base-migrator.php` | Import loop, idempotency, file migration helpers |
| `app/Services/Migrator/Classes/ContactForm7Migrator.php` | `class-cf7-importer.php` | Battle-tested shortcode regex parser (`formatAsFluentField()`) |
| `app/Services/Migrator/Classes/WpFormsMigrator.php` | `class-wpforms-importer.php` | JSON-decode + 22-entry field map |
| `app/Services/Migrator/Classes/GravityFormsMigrator.php` | `class-gravity-importer.php` | Serialized-array parsing + Name/Address sub-input handling |
| `app/Services/Migrator/Classes/NinjaFormsMigrator.php` | `class-ninja-importer.php` | JOIN across `nf3_*` tables via Ninja's PHP API |
| `app/Services/Migrator/Classes/CalderaMigrator.php` | `class-caldera-importer.php` | `Caldera_Forms_Forms` static API |

Fluent Forms is GPL-2.0+ — we can lift regexes and parsing patterns directly with attribution comments. The `defaultFieldConfig()` array structure should be re-written for SureForms blocks, not copied (different output format).

## 16a. Metadata migration

Form metadata (email notifications, confirmation messages, compliance flags, integrations) lives **outside** the block-markup pipeline — it's persisted as `sureforms_form` post meta. The migrator handles it via the abstract `Base_Migrator::get_form_metas()` method which returns a `meta_key => meta_value` array passed to `wp_insert_post()` / `wp_update_post()` via `meta_input`.

### What every importer must produce

A SureForms-shaped payload. The two keys every importer should populate are:

| SureForms meta key | Schema (registered in `inc/post-types.php`) |
|---|---|
| `_srfm_email_notification` | Array of objects: `{id, status, is_raw_format, name, email_to, email_reply_to, from_name, from_email, email_cc, email_bcc, subject, email_body}` |
| `_srfm_form_confirmation` | Array of objects: `{id, confirmation_type, page_url, custom_url, message, submission_action, enable_query_params, query_params}` |

Other meta keys exist (`_srfm_compliance`, `_srfm_conditional_logic`, `_srfm_forms_styling`, `_srfm_integrations_webhooks`) but are not required for a usable imported form — they default to SureForms' built-in `register_post_meta` defaults if not provided.

### What each source can give us (and what we ship in v1)

| Source | Notifications | Confirmations | Other meta | Status in v1 |
|---|---|---|---|---|
| **CF7** | `_mail`, `_mail_2` use `[field-name]` shortcodes — incompatible with SureForms smart tags | `_messages` array (success/error text) | None | **Stub only** — admin notification to `{admin_email}` with `{all_data}` body, generic success message. Form is usable; users can refine in Single Form Settings. (Matches Fluent's CF7 approach for the same reason.) |
| **WPForms** | `settings.notifications[]` — fully structured (subject/body/recipient/CC/BCC/replyTo/fromName) | `settings.confirmations[]` — multi-confirmation with conditional logic | Webhooks | **Full migration planned in P2** |
| **Gravity** | Notifications, conditional notifications, routing | Confirmations, scheduling, login-required, entry limits | Advanced validation | **Full migration planned in P2** |
| **Ninja** | `Actions` API — type `email`, `successmessage`, `redirect`, `save` | Same Actions API | — | **Full migration planned in P3** |
| **Caldera** | `mailer` config in form settings | `successMessage` field | — | **Full migration planned in P3** |

### Why CF7 is special

CF7's email body uses `[your-name]`, `[your-email]` style shortcodes that reference the form-tag `name` attribute. Translating these to SureForms' `{slug_field_id}` smart-tag system requires either (a) maintaining a per-form name→slug mapping during import, or (b) rewriting the body string with regex substitutions that match the field tokens we emitted. Both approaches add ~400 LOC and break easily on edge cases (multi-line bodies, mixed shortcodes/text, CF7 special mail tags like `[_remote_ip]`).

Fluent Forms — with 700K+ installs and 5+ years of migrator iteration — landed on the same stub-only conclusion for CF7. v1 matches that decision; if user demand surfaces, CF7 mail-template translation is a separate scoped feature.

### Code pointers (live)

- **Abstract method**: `Base_Migrator::get_form_metas()` — `inc/migrator/base-migrator.php`
- **Wire-up**: `Base_Migrator::insert_form_post()` / `update_form_post()` both accept a `$metas` arg and pass it to `wp_insert_post()` via `meta_input`
- **CF7 implementation**: `Cf7_Importer::get_form_metas()` — `inc/migrator/importers/cf7-importer.php`
- **SureForms meta schemas** (the contract we have to match): `inc/post-types.php` lines 911-1010 (notification), 1086-1182 (confirmation)

### Verification

After importing a CF7 form via the Migration tab, open the resulting SureForms form's settings:
- **Email Notifications** tab → should show one enabled notification named "Admin Notification Email" with subject "New submission: {form title}" and body `{all_data}`.
- **Form Confirmation** tab → should show a centered "Thank you" success message, "Same page" confirmation type.

If those panels are empty, the meta payload didn't persist — check `wp_options.srfm_imported_forms_map` and `wp_postmeta` for the form id.

## 17. Out of scope for v1

- Polls / Quizzes (Forminator, Gravity surveys) — no SureForms equivalent.
- Formidable Views — front-end data display is its own feature.
- WS Form — niche, defer.
- Multi-step / page break preservation — wait for native `srfm/page-break` block.
- File-upload, signature, rating, repeater fields — wait for native blocks (flagged as unsupported until then).
- Bulk source-selection ("import everything from everywhere") — one source at a time in v1.

## 18. Sources verified live (May 2026)

All wordpress.org plugin pages were fetched live during research:

- [Contact Form 7](https://wordpress.org/plugins/contact-form-7/) — 10M+ installs, v6.1.6
- [WPForms Lite](https://wordpress.org/plugins/wpforms-lite/) — 6M+ installs, v1.10.0.5
- [Gravity Forms docs](https://docs.gravityforms.com/form-fields/) — ~655K live sites, commercial
- [Ninja Forms](https://wordpress.org/plugins/ninja-forms/) — 600K+ installs, v3.14.4
- Caldera Forms — unmaintained since 2022, ~50K legacy sites

**Combined migration pool: ~17.3M active sites** — the addressable target for v1.
