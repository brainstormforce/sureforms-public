# Frontend Assets

SureForms manages assets across three contexts: frontend (form display), admin (dashboard), and block editor.

## Asset Loading Strategy

### Frontend (Form Display)

`inc/frontend-assets.php` (`Frontend_Assets` class) handles loading CSS/JS on pages containing SureForms forms:

- Detects form blocks in page content
- Enqueues only necessary field-specific CSS/JS
- Loads Stripe JS SDK only when payment fields are present
- Enqueues `intl-tel-input` only when phone fields exist

### Admin Dashboard

Admin assets are enqueued only on SureForms admin pages:

- React app bundle from `assets/build/`
- Tailwind CSS base styles from `src/admin/tw-base.scss`
- Localized data via `wp_localize_script()`

### Block Editor

`inc/gutenberg-hooks.php` (`Gutenberg_Hooks` class) loads editor assets:

- Block editor JS/CSS from `assets/build/`
- Editor-specific stylesheets
- Block preview styles

## Build Pipeline

### wp-scripts (Webpack)

Primary JS build tool. Configuration extends `@wordpress/scripts` defaults:

```bash
npm start          # Development with hot reload
npm run build:script  # Production build
```

**Entry Points:**
- `src/blocks/blocks.js` -- Gutenberg block registration
- `src/admin/editor-scripts.js` -- Admin dashboard React app
- Additional entries per block type

**Output:** `assets/build/` directory

**Webpack Features:**
- Code splitting per entry point
- WordPress dependency extraction (`@wordpress/*` packages)
- Source maps in development
- Asset hashing for cache busting

### Grunt (SASS / Minification)

```bash
npm run build:sass  # Compile SASS only
npm run build       # Full build (JS + SASS + minify)
```

The `Gruntfile.js` tasks include:

| Task | Description |
|------|-------------|
| `sass` | Compile SASS files from `sass/` to `assets/css/` |
| `postcss` | Run PostCSS with Autoprefixer |
| `cssmin` | Minify CSS to `assets/css/minified/` |
| `uglify` | Minify JS to `assets/js/minified/` |
| `copy` | Copy assets to distribution directories |

### SASS Directory Structure

```
sass/
|-- frontend block styles
|-- form field styles
|-- layout and grid styles
```

Compiled output: `assets/css/` (unminified) and `assets/css/minified/`

### Tailwind CSS

Used exclusively for admin dashboard pages:

**Configuration (`tailwind.config.js`):**
- Content paths: `src/admin/**/*.{js,jsx}`
- Plugins: `@tailwindcss/forms`
- Custom theme extensions for Force UI compatibility
- `tailwind-merge` used for dynamic class composition

**Base styles:** `src/admin/tw-base.scss`

## CSS Naming Conventions

| Context | Prefix | Example |
|---------|--------|---------|
| Frontend form styles | `srfm-` | `srfm-input-field`, `srfm-form-container` |
| Admin dashboard | Tailwind utilities | `flex items-center gap-2` |
| Block editor | `srfm-editor-` | `srfm-editor-preview` |

## Asset Versioning

All enqueued assets use `SRFM_VER` (currently `2.5.1`) for cache busting:

```php
wp_enqueue_style(
    'srfm-frontend',
    SRFM_URL . 'assets/css/minified/frontend.min.css',
    [],
    SRFM_VER
);
```

## Related Pages

- [Admin Dashboard](Admin-Dashboard) -- React admin asset details
- [Gutenberg Blocks](Gutenberg-Blocks) -- Block editor assets
- [Environment Configuration](Environment-Configuration) -- Build tool setup
- [Contributing Guide](Contributing-Guide) -- CSS/JS conventions
