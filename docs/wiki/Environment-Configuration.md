# Environment Configuration

## Prerequisites

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | 7.4+ | WordPress minimum requirement |
| WordPress | 6.4+ | Gutenberg block editor required |
| Node.js | 18.15.0 | Managed via Volta (see `package.json`) |
| Composer | 2.x | PHP dependency management |
| npm | 9.x+ | Comes with Node 18 |

## Plugin Constants

Defined in `sureforms.php`:

| Constant | Value | Purpose |
|----------|-------|---------|
| `SRFM_FILE` | `__FILE__` | Absolute path to main plugin file |
| `SRFM_BASENAME` | `sureforms/sureforms.php` | Plugin basename for hooks |
| `SRFM_DIR` | Plugin directory path | File system path operations |
| `SRFM_URL` | Plugin URL | Asset URL generation |
| `SRFM_VER` | `2.5.1` | Version for cache busting |
| `SRFM_SLUG` | `srfm` | Short identifier slug |
| `SRFM_FORMS_POST_TYPE` | `sureforms_form` | Custom post type slug |
| `SRFM_ENTRIES` | `sureforms_entries` | Entries identifier |
| `SRFM_PAYMENTS` | `sureforms_payments` | Payments identifier |
| `SRFM_WEBSITE` | `https://sureforms.com/` | Official website |
| `SRFM_AI_MIDDLEWARE` | `https://credits.startertemplates.com/sureforms/` | AI credits API |
| `SRFM_MIDDLEWARE_BASE_URL` | `https://api.sureforms.com/` | API middleware |
| `SRFM_BILLING_PORTAL` | `https://billing.sureforms.com/` | Billing portal |
| `SRFM_PRO_RECOMMENDED_VER` | `2.5.1` | Minimum recommended Pro version |
| `SRFM_SURETRIGGERS_INTEGRATION_BASE_URL` | `https://app.ottokit.com/` | OttoKit integration |

## Development Environment Setup

### Option 1: wp-env (Docker)

```bash
# Start the WordPress environment
npm run play:up

# Stop the environment
npm run play:stop

# Clean environment data
npm run env:clean
```

Configuration is in `.wp-env.json`. Requires Docker to be installed.

### Option 2: Local by Flywheel / MAMP / XAMPP

1. Set up a local WordPress installation
2. Clone the repo into `wp-content/plugins/sureforms`
3. Run `composer install` for PHP dependencies
4. Run `npm install` for JS dependencies
5. Run `npm start` for development with hot reload

## Build Tools

### wp-scripts (JavaScript)

Primary build tool for JS/React compilation via Webpack:

```bash
npm start           # Dev server with hot reload
npm run build:script # Production JS build
```

Entry points are defined in `webpack.config.js` and output to `assets/build/`.

### Grunt (SASS / Minification)

```bash
npm run build:sass   # Compile SASS to CSS
npm run build        # Full build (JS + SASS + minify)
```

The `Gruntfile.js` handles:
- SASS compilation (`sass/` -> `assets/css/`)
- CSS minification
- JS minification
- PostCSS with Autoprefixer

### Tailwind CSS

Used for admin dashboard styles. Configuration in `tailwind.config.js`:
- Content scanning paths: `src/admin/**/*.{js,jsx}`
- Plugin: `@tailwindcss/forms`
- Custom theme extensions for Force UI integration

### PostCSS

Configuration in `postcss.config.js`:
- Autoprefixer for browser compatibility
- cssnano for production minification

## NPM Scripts Reference

| Script | Command | Description |
|--------|---------|-------------|
| `start` | `wp-scripts start` | Dev server with hot reload |
| `build` | Full pipeline | JS + SASS + minify |
| `build:script` | `wp-scripts build` | Build JS only |
| `build:sass` | Grunt sass tasks | Build SASS only |
| `lint-js` | `wp-scripts lint-js` | ESLint check |
| `lint-js:fix` | `wp-scripts lint-js --fix` | Auto-fix JS lint |
| `lint-css` | `wp-scripts lint-style` | Stylelint check |
| `lint-css:fix` | `wp-scripts lint-style --fix` | Auto-fix CSS lint |
| `pretty` | `prettier --check` | Prettier check |
| `pretty:fix` | `prettier --write` | Prettier format |
| `test:unit` | `wp-scripts test-unit-js` | Jest unit tests |
| `play:up` | `wp-env start` | Start Docker env |
| `play:stop` | `wp-env stop` | Stop Docker env |
| `play:run` | `playwright test` | Run E2E tests |
| `play:run:interactive` | `playwright test --headed` | E2E tests (headed) |
| `env:clean` | `wp-env clean` | Clean Docker data |
| `makepot` | `wp i18n make-pot` | Generate .pot file |

## Composer Scripts

| Script | Description |
|--------|-------------|
| `vendor/bin/phpunit` | Run PHPUnit tests |
| `vendor/bin/phpcs` | PHP CodeSniffer (WPCS) |
| `vendor/bin/phpcbf` | Auto-fix PHPCS issues |
| `vendor/bin/phpstan` | PHPStan static analysis |

## Linting Configuration

### PHP (PHPCS)

Configured via `phpcs.xml`:
- Ruleset: WordPress Coding Standards (WPCS)
- PHPCompatibility for PHP 7.4+
- Text domain: `sureforms`
- Prefixes: `srfm`, `SRFM_`

### JavaScript (ESLint)

Uses `@wordpress/scripts` ESLint configuration which extends WordPress defaults.

### CSS (Stylelint)

WordPress Stylelint configuration via `@wordpress/scripts`.

### Prettier

Code formatting for JS/CSS files.

## Volta Configuration

Node version is pinned in `package.json`:

```json
{
  "volta": {
    "node": "18.15.0"
  }
}
```

Install Volta to automatically use the correct Node version when entering the project directory.

## Key Dependencies

### PHP (Composer)

| Package | Purpose |
|---------|---------|
| `yoast/phpunit-polyfills` | PHPUnit compatibility |
| `squizlabs/php_codesniffer` | Code style checking |
| `wp-coding-standards/wpcs` | WordPress standards |
| `phpcompatibility/php-compatibility` | PHP version checks |
| `phpstan/phpstan` | Static type analysis |
| `nunomaduro/phpinsights` | Code quality insights |

### JavaScript (npm)

| Package | Purpose |
|---------|---------|
| `@bsf/force-ui` | BSF internal UI component library |
| `@wordpress/scripts` | Build tooling |
| `@wordpress/api-fetch` | REST API client |
| `@tanstack/react-query` | Data fetching/caching |
| `react-router-dom` | Client-side routing |
| `tailwindcss` | Utility-first CSS |
| `@playwright/test` | E2E testing |
| `@wordpress/env` | Docker test environment |

## Related Pages

- [Architecture Overview](Architecture-Overview) -- Plugin structure and bootstrap
- [Testing Guide](Testing-Guide) -- Test setup and execution
- [Contributing Guide](Contributing-Guide) -- Coding standards and workflow
- [Frontend Assets](Frontend-Assets) -- Asset pipeline details
