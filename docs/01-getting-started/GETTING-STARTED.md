# Getting Started with SureForms Development

> **Goal:** Get productive in less than 30 minutes
> **Audience:** New developers, contractors, open-source contributors
> **Prerequisites:** Basic WordPress, PHP, and React knowledge

## Prerequisites

Before you begin, ensure you have:

- **PHP 7.4+** with extensions: mysqli, mbstring, curl, json
- **Composer 2.3+** for PHP dependencies
- **Node.js 18.15+** (check with `node --version`, use nvm: `nvm use`)
- **npm** (comes with Node.js)
- **Git** for version control
- **Code editor** (VS Code recommended with extensions: PHP Intelephense, ESLint, Prettier)
- **Docker** (optional, for wp-env local environment)

---

## Quick Start (5 Minutes)

### 1. Clone Repository
```bash
git clone https://github.com/brainstormforce/sureforms.git
cd sureforms
```

### 2. Install Dependencies
```bash
# PHP dependencies
composer install

# JavaScript dependencies
npm install
```

This installs:
- **PHP:** Action Scheduler, NPS Survey, Analytics libraries
- **JavaScript:** React 18, WordPress scripts, TailwindCSS, Webpack, testing tools

### 3. Build Assets
```bash
npm run build
```

This compiles:
- JavaScript bundles (Webpack → assets/build/)
- CSS from SASS (sass/ → assets/css/)
- Minified production files

**Time:** ~2-3 minutes

### 4. Start Local Environment

**Option A: Using wp-env (Recommended)**
```bash
npm run play:up
```

This creates a Docker-based WordPress installation:
- **URL:** http://localhost:8888
- **Admin:** http://localhost:8888/wp-admin
- **Username:** admin
- **Password:** password

**Option B: Manual WordPress Setup**
1. Install WordPress locally (MAMP, Local by Flywheel, or XAMPP)
2. Symlink or copy plugin to `wp-content/plugins/sureforms`
3. Activate plugin in WordPress admin

### 5. Activate Plugin

Visit http://localhost:8888/wp-admin/plugins.php and activate "SureForms".

**You're now ready to develop!** 🎉

---

## Project Structure Overview

```
sureforms/
├── sureforms.php              # Main plugin file (entry point)
├── plugin-loader.php          # Bootstrap and autoloader
│
├── inc/                       # PHP backend logic (176+ files)
│   ├── form-submit.php        # Form submission handler
│   ├── post-types.php         # Custom post type registration
│   ├── rest-api.php           # REST API endpoints
│   ├── helper.php             # Utility functions
│   ├── blocks/                # Gutenberg block PHP classes
│   │   ├── base.php           # Abstract base for blocks
│   │   ├── register.php       # Block registration
│   │   ├── input/             # Text input block
│   │   └── [14+ more blocks]
│   ├── database/              # Custom database layer
│   │   ├── base.php           # Abstract CRUD operations
│   │   ├── register.php       # Table initialization
│   │   └── tables/
│   │       ├── entries.php    # Form submissions table
│   │       └── payments.php   # Payment transactions table
│   └── [many more directories]
│
├── src/                       # JavaScript/React source
│   ├── blocks/                # Gutenberg block components
│   │   ├── blocks.js          # Block registration entry
│   │   ├── input/             # Input block React component
│   │   │   ├── edit.js        # Editor interface
│   │   │   ├── save.js        # Frontend save (usually null)
│   │   │   └── block.json     # Block metadata
│   │   └── [14+ more blocks]
│   ├── admin/                 # Admin dashboard React apps
│   │   ├── dashboard/         # Forms listing
│   │   ├── settings/          # Settings page
│   │   ├── entries/           # Entry management
│   │   └── single-form-settings/  # Form editor
│   ├── components/            # Reusable React components
│   ├── store/                 # WordPress Data Store (Redux)
│   └── utils/                 # Helper functions
│
├── assets/                    # Compiled build output
│   ├── build/                 # Webpack bundles
│   ├── css/                   # Compiled CSS
│   └── js/                    # Compiled JavaScript
│
├── sass/                      # SCSS source files
├── tests/                     # PHPUnit and Playwright tests
├── webpack.config.js          # Build configuration
├── package.json               # npm dependencies and scripts
└── composer.json              # PHP dependencies
```

### Key Entry Points

**PHP Initialization:**
```
sureforms.php (defines constants)
  → plugin-loader.php (Plugin_Loader class)
    → Autoloader registration
    → WordPress hooks: plugins_loaded, init, admin_init
```

**JavaScript Entry:**
- **Blocks:** [src/blocks/index.js](../../src/blocks/index.js)
- **Admin Pages:** [src/admin/{page}/index.js](../../src/admin/)

---

## Development Workflow

### Daily Development

**1. Start Development Server**
```bash
npm start
```

This starts Webpack in watch mode:
- Automatically rebuilds on file changes
- Hot reload for React components
- Source maps for debugging

**2. Make Changes**
- Edit PHP files in `inc/` or `admin/`
- Edit React components in `src/`
- Edit styles in `sass/`

**3. See Changes**
- **Admin:** Refresh browser (Cmd/Ctrl + R)
- **Frontend:** Hard refresh (Cmd/Ctrl + Shift + R) to clear cache

### Before Committing

**CRITICAL: Always run these commands before committing**

```bash
# 1. Fix JavaScript linting issues
npm run lint-js:fix

# 2. Fix CSS linting issues
npm run lint-css:fix

# 3. Production build (REQUIRED)
npm run build

# 4. Check PHP coding standards
composer phpcs

# 5. Run static analysis
composer phpstan

# 6. Run tests (optional but recommended)
composer test
npm run test:e2e
```

**If all pass, commit:**
```bash
git add .
git commit -m "feat: your change description"
git push
```

### Commit Message Format

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add date picker field block
fix: resolve form submission nonce error
docs: update API endpoint documentation
style: format code with PHPCS
refactor: simplify validation logic
test: add unit tests for Helper class
chore: update dependencies
```

---

## Common Development Tasks

### Task 1: Create a Test Form

**Time:** 5 minutes

1. Navigate to **SureForms → Add New** in WordPress admin
2. Block editor opens with empty form
3. Add fields by clicking "+" button:
   - Add "Input" block → Configure as "Name"
   - Add "Email" block → Configure as "Email Address"
   - Add "Textarea" block → Configure as "Message"
4. Click "Publish"
5. View form on frontend or use shortcode `[sureforms id="{ID}"]`

### Task 2: Submit a Test Form

**Time:** 2 minutes

1. Visit form on frontend
2. Fill out fields
3. Click "Submit"
4. Check **SureForms → Entries** to see submission

### Task 3: Add a New Setting to Existing Block

**Time:** 30-45 minutes
**Difficulty:** Easy
**Files Modified:** 2

**Example: Add "Max Length" setting to Input block**

**Step 1:** Update [src/blocks/input/edit.js](../../src/blocks/input/edit.js)

Find the `<InspectorControls>` section and add:
```javascript
import { TextControl } from '@wordpress/components';

// Inside InspectorControls > PanelBody
<TextControl
    label={__('Max Length', 'sureforms')}
    type="number"
    value={attributes.maxLength || ''}
    onChange={(value) => setAttributes({ maxLength: parseInt(value) })}
    help={__('Maximum number of characters allowed', 'sureforms')}
/>
```

**Step 2:** Update [src/blocks/input/block.json](../../src/blocks/input/block.json)

Add to `attributes` object:
```json
"maxLength": {
    "type": "number",
    "default": 0
}
```

**Step 3:** Build and test
```bash
npm run build
# Refresh block editor
# Test setting in Inspector panel
```

**Step 4:** Update PHP rendering (if needed)

Edit [inc/blocks/input/class-input.php](../../inc/blocks/input/class-input.php) to use the attribute:
```php
$max_length = $attributes['maxLength'] ?? 0;

// In HTML output
<input
    type="text"
    <?php if ($max_length > 0): ?>
        maxlength="<?php echo esc_attr($max_length); ?>"
    <?php endif; ?>
/>
```

### Task 4: Debug a Form Submission Issue

**Time:** 10-20 minutes

**Symptoms:** Form submission fails silently, no error message

**Debug Steps:**

1. **Check Browser Console** (F12 → Console tab)
   - Look for JavaScript errors
   - Check Network tab for failed AJAX requests
   - Look for 403 (nonce error) or 500 (server error)

2. **Check PHP Error Logs**
```bash
# If using wp-env
npm run play:logs

# Or tail WordPress debug log
tail -f wp-content/debug.log
```

3. **Enable WordPress Debug Mode** (wp-config.php)
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
```

4. **Test REST API Directly**
```bash
# Get nonce from browser (inspect form, look for data-nonce attribute)
curl -X POST "http://localhost:8888/wp-json/sureforms/v1/submit-form" \
  -H "X-WP-Nonce: YOUR_NONCE_HERE" \
  -H "Content-Type: application/json" \
  -d '{"form_id":123,"field_data":{"email":{"value":"test@example.com"}}}'
```

5. **Common Fixes:**
   - **Nonce expired:** Refresh page
   - **Spam protection:** Disable reCAPTCHA temporarily
   - **PHP error:** Check debug.log for syntax errors
   - **REST API disabled:** Check .htaccess or security plugins

---

## Testing Your Changes

### Unit Tests (PHP)

```bash
# Run all PHPUnit tests
composer test

# Run specific test file
./vendor/bin/phpunit tests/unit/test-helper.php

# Generate coverage report
composer test:coverage
```

### E2E Tests (Playwright)

```bash
# Run all E2E tests
npm run test:e2e

# Run specific test
npx playwright test tests/play/specs/basic-form-test-submit-text-field.spec.js

# Run in headed mode (see browser)
npx playwright test --headed

# Debug mode
npx playwright test --debug
```

### Manual Testing Checklist

After making changes, test:
- [ ] Form appears in block editor
- [ ] Settings panel works
- [ ] Form renders on frontend
- [ ] Form submission works
- [ ] Entry appears in admin
- [ ] Email notification sent (if configured)
- [ ] No console errors
- [ ] No PHP errors in debug.log

---

## Debugging Tips

### Enable Debugging

**WordPress Debug Mode** (wp-config.php):
```php
define('WP_DEBUG', true);           // Enable debug mode
define('WP_DEBUG_LOG', true);       // Log to wp-content/debug.log
define('WP_DEBUG_DISPLAY', false);  // Don't display errors on page
define('SCRIPT_DEBUG', true);       // Load unminified JS/CSS
define('SAVEQUERIES', true);        // Log database queries
```

**Plugin Debug Mode** (wp-config.php):
```php
define('SRFM_DEBUG', true);         // Load unminified assets
```

### View Logs

**PHP Errors:**
```bash
tail -f wp-content/debug.log
```

**JavaScript Console:**
- Open browser DevTools (F12)
- Console tab shows errors and logs
- Network tab shows AJAX requests

### Common Issues

#### Issue: "Class not found" Error
**Cause:** Namespace doesn't match file path
**Fix:** Ensure `SRFM\Inc\Blocks\Input` maps to `inc/blocks/input/class-input.php`

#### Issue: Blocks Don't Appear in Editor
**Cause:** JavaScript not rebuilt
**Fix:**
```bash
npm run build
# Hard refresh browser (Cmd/Ctrl + Shift + R)
```

#### Issue: Form Submission Fails
**Cause:** Nonce expired or invalid
**Fix:** Refresh page to get new nonce

#### Issue: Styling Not Applied
**Cause:** CSS not compiled or cached
**Fix:**
```bash
npm run build:sass
# Hard refresh browser
```

#### Issue: PHP Syntax Error After Edit
**Cause:** Syntax mistake in PHP file
**Fix:** Check debug.log, fix syntax, refresh

---

## Next Steps

Now that you're set up, here's how to continue learning:

### 1. Read Core Documentation (2-3 hours)

**Essential:**
- [CLAUDE.md](../../CLAUDE.md) - AI agent guide with constraints and patterns
- [ARCHITECTURE.md](../02-architecture/ARCHITECTURE.md) - System design overview

**Important:**
- [CODING-STANDARDS.md](../03-development/CODING-STANDARDS.md) - Code style rules
- [DATABASE-SCHEMA.md](../04-backend/DATABASE-SCHEMA.md) - Data model
- [FRONTEND-GUIDE.md](../05-frontend/FRONTEND-GUIDE.md) - React/Gutenberg patterns

### 2. Explore the Codebase (2-3 hours)

**Follow the Request Flow:**

**Admin Page Load:**
```
1. WordPress loads: admin.php?page=sureforms_forms
2. admin/admin.php → render_forms_page()
3. Outputs: <div id="srfm-forms-root"></div>
4. React app mounts from assets/build/forms.js
5. Fetches data from REST API
```

**Form Submission:**
```
1. User fills form → formSubmit.js validates
2. AJAX POST → /wp-json/sureforms/v1/submit-form
3. inc/form-submit.php → handle_form_submission()
4. Validates → Saves to wp_srfm_entries table
5. Sends email notifications
6. Returns success response
```

### 3. Study Example Implementations

**Reference Block:** [inc/blocks/input/](../../inc/blocks/input/)
- See complete block implementation
- PHP class extends Base
- React component structure
- Attribute schema in block.json

**Reference React App:** [src/admin/dashboard/](../../src/admin/dashboard/)
- React Query for data fetching
- React Router for navigation
- Component structure

### 4. Make Your First Contribution

**Choose from "Good First Issue" labels:**
- Add documentation
- Fix typos
- Add unit tests
- Improve error messages
- Add new field setting

**Contribution Process:**
1. Fork repository
2. Create feature branch: `git checkout -b feat/your-feature`
3. Make changes
4. Run tests and linters
5. Commit with conventional commit message
6. Push and create pull request
7. Address review feedback

---

## Getting Help

### Documentation
- [CLAUDE.md](../../CLAUDE.md) - AI agent guide
- [docs/](../) - Full documentation
- [README.md](../../README.md) - Plugin overview

### Community
- **GitHub Issues:** https://github.com/brainstormforce/sureforms/issues
- **Pull Requests:** https://github.com/brainstormforce/sureforms/pulls

### Code Questions

**When asking for help, include:**
1. What you're trying to do
2. What you've tried
3. Error messages (browser console + PHP debug.log)
4. Code snippets
5. Environment (WordPress version, PHP version, plugin version)

---

## Development Tools

### Recommended VS Code Extensions

- **PHP Intelephense** - PHP intelligence and autocomplete
- **ESLint** - JavaScript linting
- **Prettier** - Code formatting
- **EditorConfig** - Consistent editor config
- **WordPress Snippets** - WordPress function snippets
- **GitLens** - Git integration
- **Auto Rename Tag** - HTML tag renaming
- **Path Intellisense** - File path autocomplete

### Browser Extensions

- **React Developer Tools** - Inspect React components
- **Redux DevTools** - Inspect WordPress Data Store state

### Command Line Tools

```bash
# Install WP-CLI globally (optional but helpful)
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp

# Useful WP-CLI commands
wp plugin list
wp post list --post_type=sureforms_form
wp cache flush
```

---

## Keyboard Shortcuts (Block Editor)

| Action | Mac | Windows |
|--------|-----|---------|
| Save | Cmd + S | Ctrl + S |
| Add block | / | / |
| Duplicate block | Cmd + Shift + D | Ctrl + Shift + D |
| Delete block | Shift + Alt + Z | Shift + Alt + Z |
| Block settings | Cmd + Shift + , | Ctrl + Shift + , |
| Undo | Cmd + Z | Ctrl + Z |
| Redo | Cmd + Shift + Z | Ctrl + Shift + Z |

---

## Quick Reference

### Important npm Scripts

```bash
npm start                # Watch mode with hot reload
npm run build            # Production build
npm run build:script     # JavaScript only
npm run build:sass       # CSS only
npm run lint-js          # Check JavaScript
npm run lint-js:fix      # Fix JavaScript
npm run lint-css         # Check CSS
npm run lint-css:fix     # Fix CSS
npm test:unit            # JavaScript unit tests
npm run test:e2e         # Playwright E2E tests
npm run play:up          # Start wp-env
npm run play:stop        # Stop wp-env
npm run play:down        # Destroy wp-env
npm run makepot          # Generate translation template
```

### Important Composer Scripts

```bash
composer install         # Install dependencies
composer test            # Run PHPUnit tests
composer test:coverage   # Generate coverage report
composer phpcs           # Check coding standards
composer phpcbf          # Fix coding standards
composer phpstan         # Static analysis
composer phpinsights     # Code quality metrics
```

### Important File Paths

| Purpose | Path |
|---------|------|
| Main plugin file | sureforms.php |
| Plugin loader | plugin-loader.php |
| Form submission | inc/form-submit.php |
| REST API | inc/rest-api.php |
| Helper functions | inc/helper.php |
| Block base class | inc/blocks/base.php |
| Database entries | inc/database/tables/entries.php |
| Webpack config | webpack.config.js |
| TailwindCSS config | tailwind.config.js |

---

**Congratulations!** You're now ready to contribute to SureForms. Happy coding! 🚀

**Questions?** Check [CLAUDE.md](../../CLAUDE.md) or create an issue on GitHub.
