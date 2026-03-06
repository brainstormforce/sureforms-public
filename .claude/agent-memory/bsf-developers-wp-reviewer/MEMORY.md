# SureForms WP Reviewer Memory

## Plugin Conventions
- **Text domain:** `sureforms`
- **PHP prefix:** `SRFM_` (constants), `srfm` (slugs/options), `SRFM\Inc\` (namespace)
- **DB table prefix:** `{wpdb->prefix}srfm_` (e.g., `wp_srfm_entries`, `wp_srfm_payments`)
- **Custom tables:** entries (`srfm_entries`), payments (`srfm_payments`) via `inc/database/`
- **REST namespace:** `sureforms/v1`
- **Post type:** `sureforms_form` (constant `SRFM_FORMS_POST_TYPE`)

## Abilities Module (`inc/abilities/`)
- All abilities extend `Abstract_Ability` with `execute()`, `get_input_schema()`, `get_output_schema()`, `get_annotations()`
- Capability: all abilities use `manage_options` (enforced by `meets_capability_policy()`)
- Gated abilities: write/destructive abilities use `$this->gated` property to require admin opt-in via `get_option()`
- ID format: `sureforms/<verb>-<noun>`
- Annotations: `readonly`, `destructive`, `idempotent`, `priority`, `openWorldHint`
- Security: `permission_callback()` checks both capability and gated option
- Secret keys are masked with `********` in get-global-settings, preserved on update

## Known Acceptable Patterns
- `$wpdb->prefix . 'srfm_entries'` in `list-forms.php` direct query -- intentional batch query to avoid N+1, with proper phpcs:ignore
- `wp_send_json_error()` in `global-settings.php` REST callbacks -- pre-existing pattern (not ideal, should use WP_Error)
- `console.error` in Component.js catch blocks -- debatable, may be intentional for debugging

## PHPDoc Convention
- New abilities should have `@since x.x.x` on ALL methods including `{@inheritDoc}` overrides
- Some older abilities (list-forms, create-form, get-form, delete-form, get-shortcode) were missing `@since` on overrides -- flagged in review

## Test Patterns
- Tests use `Yoast\PHPUnitPolyfills\TestCases\TestCase`
- Pattern: test constructor, test annotations, test input/output schema, test error cases, test happy path
- Abilities API tests guard with `function_exists('wp_register_ability')` for WP < 6.9
- DB-dependent tests handle WP_Error gracefully when tables don't exist in test env
