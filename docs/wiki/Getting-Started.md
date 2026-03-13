# Getting Started

This guide walks you through setting up a SureForms development environment from scratch.

## Prerequisites

| Tool | Version | Notes |
|------|---------|-------|
| PHP | 7.4+ | Required for plugin runtime |
| WordPress | 6.4+ | Required for block editor features |
| Node.js | 18.15.0 | Managed via Volta (auto-switches) |
| Composer | 2.x | PHP dependency manager |
| Git | 2.x | Version control |
| Docker | Latest | Optional, for wp-env test environment |

### Installing Volta (Recommended)

[Volta](https://volta.sh/) ensures you use the correct Node.js version automatically:

```bash
curl https://get.volta.sh | bash
```

The `package.json` pins Node 18.15.0 via Volta config -- it activates automatically when you enter the project directory.

## Initial Setup

### 1. Clone the Repository

```bash
git clone https://github.com/brainstormforce/sureforms.git
cd sureforms
```

### 2. Install Dependencies

```bash
composer install    # PHP dependencies
npm install         # Node dependencies
```

### 3. Build the Plugin

```bash
npm run build       # Full production build (JS + SASS + minify)
```

This runs three steps:
1. `wp-scripts build` -- Bundles JS/React from `src/` to `build/`
2. SASS compilation -- Compiles `sass/` to `assets/css/`
3. `grunt minify` -- Minifies CSS and JS to `assets/css/minified/` and `assets/js/minified/`

### 4. Start Development Server

```bash
npm start           # wp-scripts dev server with hot reload
```

This watches `src/` for changes and rebuilds JS automatically. For SASS changes, run `npm run build:sass` separately.

## WordPress Environment

### Option A: Local WordPress Installation

1. Set up a local WordPress site (e.g., using Local, MAMP, or Docker)
2. Symlink or copy the plugin into `wp-content/plugins/sureforms/`
3. Activate the plugin from the WordPress admin

### Option B: wp-env (Docker-Based)

The project includes a `.wp-env.json` configuration for a Docker-based WordPress environment:

```bash
npm run play:up     # Start WordPress + MySQL containers
```

This provides:
- WordPress at `http://localhost:8888` (admin: `admin` / `password`)
- Test WordPress at `http://localhost:8889`
- Plugin auto-mounted and activated
- Consistent environment across developers

Additional commands:
```bash
npm run play:stop   # Stop containers
npm run env:clean   # Reset all data
```

## Verifying Your Setup

### Run Linters

```bash
vendor/bin/phpcs     # PHP CodeSniffer
npm run lint-js      # ESLint
npm run lint-css     # Stylelint
npm run pretty       # Prettier
```

### Run Tests

```bash
vendor/bin/phpunit   # PHP unit tests
npm run test:unit    # JavaScript unit tests
```

### Build Check

```bash
npm run build        # Should complete without errors
```

## Project Structure Overview

```
sureforms/
├── sureforms.php          # Plugin entry point
├── plugin-loader.php      # Autoloader and bootstrap
├── inc/                   # PHP classes (core logic)
├── src/                   # JS/React source (compiled by wp-scripts)
│   ├── admin/             # Admin dashboard React app
│   ├── blocks/            # Gutenberg block components
│   ├── components/        # Shared React components
│   └── store/             # WordPress data store
├── sass/                  # SASS source files
├── assets/                # Compiled assets (CSS, JS, images)
├── templates/             # PHP templates (BladeOne)
├── tests/                 # Test files
├── modules/               # Feature modules
└── admin/                 # Admin PHP + assets
```

See [Architecture Overview](Architecture-Overview) for a detailed breakdown.

## Key Concepts

- **Custom Post Type**: Forms are stored as `sureforms_form` posts with block content
- **Custom Database Tables**: Entries in `wp_srfm_entries`, payments in `wp_srfm_payments`
- **REST API**: All data operations use the `sureforms/v1` REST namespace
- **Block Editor**: Form fields are Gutenberg blocks registered via `block.json`
- **Admin SPA**: The dashboard is a React single-page application using Force UI components

## Next Steps

- [Architecture Overview](Architecture-Overview) -- Understand the codebase
- [Contributing Guide](Contributing-Guide) -- Development workflow and standards
- [Gutenberg Blocks](Gutenberg-Blocks) -- How form blocks work
- [REST API Reference](REST-API-Reference) -- Available API endpoints
- [Testing Guide](Testing-Guide) -- Running and writing tests
