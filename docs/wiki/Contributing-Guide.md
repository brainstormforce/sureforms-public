# Contributing Guide

Thank you for your interest in contributing to SureForms! This guide covers the development workflow, coding standards, and submission process.

## Prerequisites

- **PHP** 7.4+
- **Node.js** 18.15.0 (managed via [Volta](https://volta.sh/))
- **Composer** for PHP dependencies
- **Git** for version control
- **Docker** (optional, for wp-env test environment)

## Getting Started

1. Fork the repository on GitHub
2. Clone your fork:
   ```bash
   git clone https://github.com/YOUR_USERNAME/sureforms.git
   cd sureforms
   ```
3. Install dependencies:
   ```bash
   composer install
   npm install
   ```
4. Create a feature branch from `next-release`:
   ```bash
   git checkout next-release
   git pull origin next-release
   git checkout -b feature/your-feature-name
   ```

## Git Workflow

```
feature/branch  -->  next-release  -->  master  -->  tag/release
                     (development)     (production)
```

- **Feature branches** are created from `next-release`
- **Pull requests** target `next-release`
- **Hotfixes** may target `master` directly
- **Release merges** go from `next-release` into `master`

## Development Workflow

### Running the Dev Server

```bash
npm start        # Start wp-scripts dev server (hot reload)
```

### Building

```bash
npm run build          # Full production build (JS + SASS + minify)
npm run build:script   # Build JS only
npm run build:sass     # Build SASS only
```

### Linting

Run linters before submitting a PR:

```bash
# JavaScript
npm run lint-js        # ESLint check
npm run lint-js:fix    # Auto-fix JS issues

# CSS
npm run lint-css       # Stylelint check
npm run lint-css:fix   # Auto-fix CSS issues

# Formatting
npm run pretty         # Prettier check
npm run pretty:fix     # Auto-format

# PHP
vendor/bin/phpcs       # PHP CodeSniffer (WPCS)
vendor/bin/phpcbf      # Auto-fix PHPCS issues
```

### Testing

All tests should pass before submitting a PR:

```bash
# PHP unit tests
vendor/bin/phpunit

# JavaScript unit tests
npm run test:unit

# E2E tests (requires wp-env)
npm run play:up        # Start environment
npm run play:run       # Run Playwright tests
```

See [Testing Guide](Testing-Guide) for detailed test setup.

## Coding Standards

### PHP Standards

SureForms follows **WordPress Coding Standards (WPCS)** enforced via `phpcs.xml`:

- **Escaping**: Use `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()` for all output
- **Nonces**: Use `wp_nonce_field()` / `check_ajax_referer()` for form submissions and AJAX
- **Capabilities**: Always check `current_user_can()` before privileged operations
- **Sanitization**: Use `sanitize_text_field()`, `absint()`, `wp_kses()` for all input
- **Prefixes**: `SRFM_` for constants, `srfm_` for function names and handles
- **Text domain**: `sureforms` for all translatable strings
- **PHP compatibility**: 7.4+ (enforced via `PHPCompatibilityWP`)

### JavaScript Standards

- Follow `@wordpress/scripts` ESLint configuration
- Use `@wordpress/data` store for state management
- Use `@wordpress/api-fetch` for REST API calls
- Import WordPress dependencies from `@wordpress/*` packages
- Use Force UI (`@bsf/force-ui`) components in admin dashboard pages

### CSS Standards

- **Admin dashboard**: Tailwind CSS 3.x utility classes
- **Block frontend**: SASS files in `sass/` directory
- **Class prefix**: `srfm-` for all frontend CSS classes
- Do not edit generated CSS in `assets/css/` -- edit SASS source files instead

## Pull Request Process

### PR Checklist

Before submitting your PR, verify:

- [ ] Code follows the coding standards (PHP, JS, CSS)
- [ ] All linters pass without errors
- [ ] Tests pass (PHPUnit, Jest)
- [ ] New features include tests
- [ ] Translatable strings use the `sureforms` text domain
- [ ] No debug code, `console.log`, or `var_dump` statements
- [ ] No secrets or credentials committed
- [ ] Build completes without errors (`npm run build`)

### PR Title Format

PR titles are validated by CI. Use a clear, descriptive title that summarizes the change.

### CI Checks

Pull requests automatically run:

1. **PHPUnit** -- PHP unit tests
2. **PHPStan** -- Static analysis
3. **PHP Insights** -- Code quality
4. **PR Title Validation** -- Title format check
5. **E2E Tests** -- When the PR is labeled with `e2e`

All checks must pass before merge.

### Code Review

- PRs require at least one approving review
- Address all review comments
- Keep PRs focused on a single feature or fix
- Large changes should be discussed in an issue first

## Directory Guide for Contributors

| Directory | Purpose | Edit? |
|-----------|---------|-------|
| `src/` | JS/React source code | Yes |
| `sass/` | SASS stylesheets | Yes |
| `inc/` | PHP classes and logic | Yes |
| `tests/` | Test files | Yes |
| `build/` | Compiled JS output | No (generated) |
| `assets/css/` | Compiled CSS output | No (generated) |
| `assets/js/minified/` | Minified JS | No (generated) |
| `inc/lib/` | Bundled third-party libraries | No |
| `node_modules/` | NPM packages | No |
| `vendor/` | Composer packages | No |

## Adding a New Gutenberg Block

See [Gutenberg Blocks](Gutenberg-Blocks) for the full guide on adding blocks, including:

- Block registration (server-side and client-side)
- `block.json` schema
- Editor and frontend rendering

## Adding a New REST Endpoint

See [REST API Reference](REST-API-Reference) for endpoint patterns and the registration system.

## Reporting Issues

- Use [GitHub Issues](https://github.com/brainstormforce/sureforms/issues) for bug reports and feature requests
- Security vulnerabilities should be reported through the [Bug Bounty Program](https://brainstormforce.com/bug-bounty-program/)

## Related Pages

- [Environment Configuration](Environment-Configuration) -- Development setup details
- [Architecture Overview](Architecture-Overview) -- Codebase structure
- [Testing Guide](Testing-Guide) -- Test framework details
- [Deployment Guide](Deployment-Guide) -- Release process
