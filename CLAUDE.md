# SureForms - CLAUDE.md

## Project Overview
SureForms is a WordPress form builder plugin (v2.5.1) by Brainstorm Force. It provides a modern, AI-powered drag-and-drop form building experience using the Gutenberg block editor.

- **Plugin slug:** `sureforms`
- **Text domain:** `sureforms`
- **Prefix:** `SRFM_` (constants), `srfm` (slug), `sureforms_` (post types/DB)
- **Minimum PHP:** 7.4 | **Minimum WP:** 6.4
- **License:** GPL-2.0+

## Tech Stack
- **Backend:** PHP 7.4+ / WordPress Plugin API / Custom REST endpoints
- **Frontend:** React 18 + WordPress Gutenberg blocks
- **Styling:** TailwindCSS 3.4 + SASS + PostCSS
- **Build:** WordPress Scripts (Webpack 5) + Grunt
- **Testing:** PHPUnit 9 (unit) + Playwright (E2E)
- **Static Analysis:** PHPStan level 9 + PHPCS (WordPress-Extra)
- **Node:** 18.15.0 (Volta)
- **UI Library:** @bsf/force-ui (internal BSF admin UI)

## Commands

### Build
```bash
npm run start              # Dev server with watch mode
npm run build              # Full production build (webpack + sass + grunt)
npm run build:script       # Webpack build only
npm run build:sass         # SASS compilation only
```

### Lint & Format
```bash
npm run lint-js            # ESLint via wp-scripts
npm run lint-js:fix        # Auto-fix JS lint issues
npm run lint-css           # Stylelint via wp-scripts
npm run pretty:fix         # Prettier format
composer lint              # PHPCS (WordPress coding standards)
composer format            # PHPCBF auto-fix
composer phpstan           # PHPStan level 9 analysis
composer insights          # PHP Insights
```

### Test
```bash
composer test              # PHPUnit
composer test:coverage     # PHPUnit with coverage
npm run test:unit          # JS unit tests
npm run play:up            # Start WP test environment + setup
npm run play:run           # Run Playwright E2E tests
npm run play:stop          # Stop WP test environment
```

### i18n
```bash
npm run makepot            # Generate .pot file
npm run i18n:po            # Update .po from .pot
npm run i18n:mo            # Compile .mo files
npm run i18n:json          # Generate JSON translations
```

## Architecture

### Directory Structure
```
sureforms/
├── sureforms.php          # Plugin entry point, constants
├── plugin-loader.php      # Bootstraps the plugin
├── inc/                   # PHP backend
│   ├── helper.php         # Core helper class (large, central utility)
│   ├── rest-api.php       # REST API endpoints
│   ├── form-submit.php    # Form submission handler
│   ├── entries.php        # Entry management
│   ├── post-types.php     # CPT registration
│   ├── blocks/            # PHP block rendering
│   ├── fields/            # Field type handlers
│   ├── database/          # Custom DB tables
│   ├── payments/          # Stripe payment integration
│   ├── ai-form-builder/   # AI form generation
│   ├── global-settings/   # Plugin settings
│   ├── page-builders/     # Elementor/Bricks compat
│   └── lib/               # Third-party (action-scheduler, analytics)
├── src/                   # React/JS source
│   ├── admin/             # Admin UI (dashboard, settings, entries)
│   ├── blocks/            # Gutenberg block JS
│   ├── components/        # Shared React components
│   ├── store/             # WordPress data store
│   ├── utils/             # JS utilities
│   └── srfm-controls/     # Custom block controls
├── assets/                # Compiled output (build/, css/, js/)
├── sass/                  # SASS source files
├── modules/               # Feature modules (quick-action-sidebar)
├── templates/             # PHP templates
├── tests/                 # Test suites
│   ├── unit/              # PHPUnit tests
│   ├── play/              # Playwright E2E tests
│   └── docker/            # Docker setup for testing
└── languages/             # Translation files
```

### Webpack Aliases
```
@Admin      → src/admin/
@Blocks     → src/blocks/
@Controls   → src/srfm-controls/
@Components → src/components/
@Utils      → src/utils/
@Svg        → assets/svg/
@Attributes → src/blocks-attributes/
@Image      → images/
@IncBlocks  → inc/blocks/
```

### Key Data Structures
- **Post Type:** `sureforms_form` — Forms stored as CPT with block content
- **Custom Tables:** `sureforms_entries`, `sureforms_payments` — Performance-optimized
- **REST Namespace:** Custom endpoints in `inc/rest-api.php`

## Code Standards

### PHP
- Follow WordPress Coding Standards (WPCS)
- Use `sureforms` text domain for all translatable strings
- All new functions/classes require `@since x.x.x` tag (updated in release PRs)
- Escape all output: `esc_html()`, `esc_attr()`, `wp_kses_post()`
- Sanitize all input: `sanitize_text_field()`, `absint()`, etc.
- Use nonce verification for all form handlers and AJAX
- Capability checks on all privileged operations

### JavaScript/React
- Follow WordPress Scripts ESLint config
- Use WordPress data stores for state management
- Import from `@wordpress/*` packages for WP integration
- Use TailwindCSS utility classes; avoid inline styles
- Leverage `@bsf/force-ui` for admin UI components

### General
- NEVER create files unless absolutely necessary — prefer editing existing files
- NEVER proactively create documentation files unless explicitly requested
- All user-facing strings must be translatable via `__()` or `_e()`
- Run `npm run lint-js:fix` and `composer format` before committing
- Run `npm run build:sass` after any SASS changes
- Keep `helper.php` changes minimal — it's already large

## Gotchas
- **Custom DB tables** were introduced in v0.0.13 — migration is irreversible
- **PHPStan level 9** is strict; check baseline before adding new ignores
- **Grunt is still used** alongside Webpack for CSS minification and release packaging
- **`inc/lib/`** contains third-party code — do not lint or modify directly
- **Node 18.15.0** is pinned via Volta — other versions may cause build issues
- **Build output** goes to `assets/build/` — this directory is gitignored

## Current Focus
<!-- Update this section with current sprint/milestone priorities -->
