# SureForms - CLAUDE.md

## Project Overview
SureForms is a WordPress form builder plugin (v2.5.1) by Brainstorm Force. AI-powered drag-and-drop form building using the Gutenberg block editor.

- **Plugin slug:** `sureforms` | **Text domain:** `sureforms`
- **Prefix:** `SRFM_` (constants), `srfm` (slug), `sureforms_` (post types/DB)
- **Minimum PHP:** 7.4 | **Minimum WP:** 6.4

## Tech Stack
- **Backend:** PHP 7.4+ / WordPress Plugin API / Custom REST endpoints
- **Frontend:** React 18 + WordPress Gutenberg blocks
- **Styling:** TailwindCSS 3.4 + SASS + PostCSS
- **Build:** WordPress Scripts (Webpack 5) + Grunt
- **Testing:** PHPUnit 9 (unit) + Playwright (E2E)
- **Static Analysis:** PHPStan level 9 + PHPCS (WordPress-Extra)
- **Node:** 18.15.0 (Volta) | **UI Library:** @bsf/force-ui

## Commands

### Build
```bash
npm run start              # Dev server with watch
npm run build              # Full production build
npm run build:script       # Webpack only
npm run build:sass         # SASS only
```

### Lint & Format
```bash
npm run lint-js            # ESLint
npm run lint-js:fix        # ESLint auto-fix
npm run lint-css           # Stylelint
composer lint              # PHPCS
composer format            # PHPCBF auto-fix
composer phpstan           # PHPStan level 9
composer insights          # PHP Insights
```

### Test
```bash
composer test              # PHPUnit
composer test:coverage     # PHPUnit with coverage
npm run test:unit          # JS unit tests
npm run play:up            # Start WP test env
npm run play:run           # Playwright E2E
npm run play:stop          # Stop WP test env
```

### i18n
```bash
npm run makepot            # Generate .pot
npm run i18n:po            # Update .po from .pot
npm run i18n:mo            # Compile .mo
npm run i18n:json          # JSON translations
```

## Architecture

### Directory Structure
```
sureforms/
├── sureforms.php          # Entry point, constants
├── plugin-loader.php      # Bootstrap
├── inc/                   # PHP backend
│   ├── helper.php         # Central utility (large — minimize changes)
│   ├── rest-api.php       # REST endpoints
│   ├── form-submit.php    # Submission handler
│   ├── entries.php        # Entry management
│   ├── post-types.php     # CPT registration
│   ├── blocks/            # PHP block rendering
│   ├── fields/            # Field type handlers
│   ├── database/          # Custom DB tables
│   ├── payments/          # Stripe integration
│   ├── ai-form-builder/   # AI form generation
│   ├── global-settings/   # Plugin settings
│   ├── page-builders/     # Elementor/Bricks compat
│   └── lib/               # Third-party (DO NOT modify)
├── src/                   # React/JS source
│   ├── admin/             # Admin UI
│   ├── blocks/            # Gutenberg block JS
│   ├── components/        # Shared React components
│   ├── store/             # WordPress data store
│   ├── utils/             # JS utilities
│   └── srfm-controls/     # Custom block controls
├── assets/                # Compiled output (gitignored)
├── sass/                  # SASS source
├── modules/               # Feature modules
├── templates/             # PHP templates
├── tests/                 # PHPUnit + Playwright + Docker
└── languages/             # Translation files
```

### Webpack Aliases
```
@Admin → src/admin/    @Blocks → src/blocks/    @Controls → src/srfm-controls/
@Components → src/components/    @Utils → src/utils/    @Svg → assets/svg/
@Attributes → src/blocks-attributes/    @Image → images/    @IncBlocks → inc/blocks/
```

### Key Data Structures
- **Post Type:** `sureforms_form` — Forms as CPT with block content
- **Custom Tables:** `sureforms_entries`, `sureforms_payments`
- **REST Namespace:** Custom endpoints in `inc/rest-api.php`

## Code Rules

### PHP
- Follow WPCS. Use `sureforms` text domain for all translatable strings
- `@since x.x.x` on all new functions/classes (updated in release PRs)
- NEVER `echo` without escaping — use `esc_html()`, `esc_attr()`, `wp_kses_post()`
- NEVER use `$_GET/$_POST/$_REQUEST` directly — use `sanitize_text_field( wp_unslash( ... ) )`
- NEVER skip security verification on endpoints/AJAX handlers
  - **Public form submission endpoints** use HMAC tokens via `Submit_Token::verify()` (`inc/submit-token.php`) — NOT nonces. These are cache-safe and session-independent. The `// phpcs:ignore WordPress.Security.NonceVerification.Missing` comment is expected on these handlers.
  - **Admin/authenticated endpoints** still use traditional nonces (`wp_verify_nonce()` / `check_ajax_referer()`)
- NEVER skip capability checks — use `current_user_can()` before privileged operations
- NEVER use `wp_die()` in REST callbacks — use `WP_Error` with proper response codes
- NEVER hardcode table names — use `$wpdb->prefix . 'sureforms_entries'`
- ALWAYS use `wp_json_encode()`, `wp_remote_get/post()`, `gmdate()`, `wp_safe_redirect()`

### JavaScript/React
- Follow WordPress Scripts ESLint config
- Use WordPress data stores (`useSelect`/`useDispatch`) — never raw fetch in blocks
- Import from `@wordpress/*` packages — never import React directly
- Use `__()` from `@wordpress/i18n` — never hardcode user-facing strings
- Use TailwindCSS utility classes; use `@bsf/force-ui` for admin UI
- NEVER use `dangerouslySetInnerHTML` — use `RawHTML` from `@wordpress/element`

### General
- NEVER create files unless absolutely necessary — prefer editing existing files
- NEVER proactively create documentation files unless explicitly requested

## Gotchas
- **Custom DB tables** introduced in v0.0.13 — migration is irreversible
- **PHPStan level 9** is strict; check baseline before adding new ignores
- **Grunt** still used alongside Webpack for CSS minification and release packaging
- **`inc/lib/`** is third-party code — do not lint or modify
- **Node 18.15.0** pinned via Volta — other versions may cause build issues

## Verification Before Done
After code changes, verify before reporting done:

**JS:** `npm run lint-js` → `npm run build:script`
**SASS:** `npm run build:sass` (if touched)

Checklist:
- Re-read the diff for obvious issues
- No debug code left (`console.log`, `error_log`, `var_dump`)
- All new functions have `@since x.x.x`
- All user-facing strings use `__()` / `_e()`
- Security checks on new endpoints/handlers (HMAC token for public, nonce for admin)
- For significant changes, suggest `npm run play:run`

## Self-Improvement Loop
- When corrected, add a rule to **Learned Rules** so the mistake never repeats
- When a pattern causes a bug or CI failure, document it immediately
- Periodically prune outdated rules

## Learned Rules
<!-- Format: "- NEVER/ALWAYS [action] — [reason]" -->
- NEVER flag `phpcs:ignore WordPress.Security.NonceVerification.Missing` as a security issue on public form submission handlers (form submit, field validation, Stripe/PayPal payment intents) — these use HMAC token verification (`Submit_Token::verify()`) instead of nonces for page-cache compatibility. The HMAC token is embedded in the form at render time and verified server-side without any session dependency.
- ALWAYS use `Submit_Token::generate($form_id)` / `Submit_Token::verify($token, $form_id)` for public-facing form security — nonces (`wp_verify_nonce`) are only for admin/authenticated endpoints.

## Current Focus
<!-- Update with current sprint/milestone priorities -->
