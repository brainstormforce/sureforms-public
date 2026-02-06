# SureForms: AI Agent Development Guide

> **Target Audience:** AI agents (Claude Code, GitHub Copilot, etc.)
> **Purpose:** Enable autonomous development with architectural understanding and safety constraints
> **Last Updated:** 2026-02-06
> **Plugin Version:** 2.5.0

## Quick Reference

**Essential Constants & Paths:**
```php
SRFM_FILE     // Main plugin file path
SRFM_DIR      // Plugin directory path
SRFM_URL      // Plugin URL
SRFM_VER      // Version: 2.5.0
SRFM_FORMS_POST_TYPE  // 'sureforms_form'
```

**Entry Points:**
- **PHP:** [sureforms.php](sureforms.php) → [plugin-loader.php](plugin-loader.php) → Plugin_Loader class
- **JavaScript:** [src/blocks/index.js](src/blocks/index.js) (Gutenberg blocks), [src/admin/](src/admin/) (admin pages)

**Key Architecture:**
- **Namespace:** `SRFM\` (maps to file paths)
- **Autoloader:** Custom SPL autoloader (SRFM\Inc\Class_Name → inc/class-name.php)
- **Pattern:** Singleton with Get_Instance trait
- **Init Hooks:** plugins_loaded (priority 99), init, admin_init

**Database:**
- **Custom Tables:** wp_srfm_entries, wp_srfm_payments
- **CPT:** sureforms_form (post_type)

**REST API:**
- **Namespace:** /wp-json/sureforms/v1/
- **Submit:** POST /wp-json/sureforms/v1/submit-form

**Requirements:**
- WordPress 6.4+, PHP 7.4+, React 18, Node 18.15

---

## Critical Architectural Constraints

### ⛔ DO NOT MODIFY WITHOUT EXPLICIT APPROVAL

#### 1. Database Schema ([inc/database/tables/*.php](inc/database/tables/))
**Why:** Schema changes require migration scripts and backwards compatibility testing with production data.

**Rules:**
- Never delete columns (mark as deprecated instead)
- Always provide default values for new columns
- Increment `$table_version` property for schema changes
- Test migrations on staging with real data first

**Impact:** Breaking changes affect all stored form submissions and payment data.

#### 2. Plugin Initialization ([plugin-loader.php](plugin-loader.php), [sureforms.php](sureforms.php))
**Why:** Boot sequence is carefully orchestrated for dependency loading order.

**Rules:**
- Singleton pattern is intentional - do not refactor to dependency injection
- Hook priorities are load-order dependent (plugins_loaded:99 before init)
- Action Scheduler must load BEFORE plugins_loaded hook
- Do not replace custom autoloader with Composer autoloader

**Impact:** Changes break plugin activation and WordPress.org compatibility.

#### 3. Custom Autoloader ([plugin-loader.php:79-140](plugin-loader.php))
**Why:** WordPress.org requires plugins to work without Composer dependencies.

**Rules:**
- Namespace pattern: `SRFM\Inc\Class_Name` → `inc/class-name.php`
- Case-insensitive file lookup for compatibility
- Directory traversal protection must remain
- Do not use Composer PSR-4 autoloader

**Impact:** WordPress.org deployment will fail if Composer autoloader is used.

#### 4. Gutenberg Block Attributes ([src/blocks/*/block.json](src/blocks/))
**Why:** Attribute schema changes break saved forms in database.

**Rules:**
- Never remove or rename attributes
- Deprecate old attributes, add new ones alongside
- Provide migration path in block deprecation system
- Test with forms created in previous plugin versions

**Impact:** Users lose form content when editing after plugin update.

---

### ✅ SAFE AREAS FOR MODIFICATION

These areas can be modified with standard testing procedures:

1. **New REST API Endpoints** ([inc/rest-api.php](inc/rest-api.php))
   - Add new routes following existing patterns
   - Always include permission_callback
   - Verify nonce in permissions check

2. **New Form Field Types** ([inc/blocks/](inc/blocks/), [src/blocks/](src/blocks/))
   - Extend `SRFM\Inc\Blocks\Base` class
   - Follow existing block structure (edit.js, save.js, block.json)
   - Register in [inc/blocks/register.php](inc/blocks/register.php)

3. **Email Templates** ([inc/email/](inc/email/))
   - Modify templates using filters
   - Add smart tag support
   - Customize notification logic

4. **Frontend Styling** ([sass/](sass/), [src/styles/](src/styles/))
   - Add new CSS classes with `.srfm-` prefix
   - Use TailwindCSS utilities
   - Maintain BEM methodology

5. **Admin UI Enhancements** ([admin/](admin/), [src/admin/](src/admin/))
   - Add new admin pages
   - Enhance existing dashboards
   - Add settings tabs

6. **Test Files** ([tests/](tests/))
   - Add unit tests
   - Add E2E tests
   - Expand test coverage

---

### ⚠️ HIGH-RISK AREAS (Proceed with Caution)

#### Form Submission Pipeline ([inc/form-submit.php](inc/form-submit.php))
**Risk:** Affects all form submissions across all forms.

**Guidelines:**
- Use hooks instead of direct modification: `srfm_before_submission`, `srfm_after_submission`
- Test with spam protection enabled (reCAPTCHA, honeypot)
- Test with GDPR compliance active (auto-delete, consent)
- Verify nonce verification remains intact
- Check file upload handling

**Testing Checklist:**
- [ ] Submit form as guest user
- [ ] Submit form as logged-in user
- [ ] Submit with file uploads
- [ ] Submit with payment
- [ ] Submit with spam protection
- [ ] Verify email notifications
- [ ] Check database entry created

#### Database Query Logic ([inc/database/](inc/database/))
**Risk:** Data corruption or injection vulnerabilities.

**Guidelines:**
- Always use `$wpdb->prepare()` with placeholders
- Never use string concatenation for queries
- Specify columns instead of SELECT *
- Use indexed columns in WHERE clauses
- Test with large datasets (10,000+ entries)

**Security Requirements:**
- [ ] All queries use $wpdb->prepare()
- [ ] No user input in raw queries
- [ ] SQL injection tested
- [ ] Performance tested with pagination

#### Block Registration ([inc/blocks/register.php](inc/blocks/register.php))
**Risk:** Editor crashes if blocks fail to register.

**Guidelines:**
- Verify block.json schema is valid
- Ensure PHP class extends Base correctly
- Check render_callback exists and returns HTML
- Test in block editor immediately after changes

**Testing Checklist:**
- [ ] Block appears in inserter
- [ ] Block can be added to form
- [ ] Settings panel works
- [ ] Frontend renders correctly
- [ ] No console errors

#### Payment Processing ([inc/payments/stripe/](inc/payments/stripe/))
**Risk:** Financial data mishandling, PCI compliance issues.

**Guidelines:**
- Never store credit card numbers (Stripe handles this)
- Only store transaction IDs and metadata
- Test webhook handling thoroughly
- Verify Stripe API version compatibility
- Check error handling for failed payments

**Testing Checklist:**
- [ ] Test mode payment succeeds
- [ ] Live mode payment succeeds
- [ ] Failed payment handled gracefully
- [ ] Webhook processes correctly
- [ ] Entry linked to payment record

#### Field Validation ([inc/field-validation.php](inc/field-validation.php))
**Risk:** Security vulnerabilities from insufficient validation.

**Guidelines:**
- Always sanitize before validation
- Return WP_Error for invalid data
- Provide user-friendly error messages
- Test with malicious input (XSS, SQL injection attempts)

**Security Testing:**
- [ ] XSS attempts blocked
- [ ] SQL injection attempts blocked
- [ ] File upload validation works
- [ ] Email validation accurate
- [ ] Phone number validation accurate

---

## Implicit Rules (Not Obvious from Code)

### Naming Conventions

**PHP:**
```php
// Classes: PascalCase with underscores
class Form_Submit {}
class Plugin_Loader {}
class Admin_Ajax {}

// Files: Lowercase with hyphens, class- prefix
// inc/class-form-submit.php
// inc/class-plugin-loader.php

// Methods: snake_case
public function get_instance() {}
public function handle_submission() {}
private function validate_data() {}

// Constants: UPPERCASE with SRFM_ prefix
define('SRFM_VERSION', '2.5.0');
define('SRFM_DIR', __DIR__);

// Variables: snake_case, descriptive
$form_id = 123;
$is_valid = true;
$has_errors = false;
```

**JavaScript/React:**
```javascript
// Components: PascalCase
function FormBuilder() {}
function FieldSettings() {}

// Files: PascalCase matching component
// FormBuilder.js
// FieldSettings.js

// Props/State: camelCase
const [formData, setFormData] = useState({});
const [isLoading, setIsLoading] = useState(false);

// Event Handlers: handle prefix
const handleSubmit = () => {};
const handleFieldChange = () => {};

// Hooks: use prefix, camelCase
function useFormData() {}
function useValidation() {}
```

**CSS:**
```css
/* BEM methodology with .srfm- prefix */
.srfm-form {}
.srfm-form__header {}
.srfm-form__header--sticky {}
.srfm-field__input--error {}
```

### Code Organization Patterns

#### 1. Singleton Pattern (Every Core Class)
```php
namespace SRFM\Inc;

use SRFM\Inc\Traits\Get_Instance;

class My_Class {
    use Get_Instance;  // Provides singleton functionality

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // Register hooks here, NOT in constructor
        add_action('init', [$this, 'my_method']);
    }
}

// Usage:
My_Class::get_instance();  // First call creates instance
My_Class::get_instance();  // Subsequent calls return same instance
```

#### 2. Hook Registration
```php
// ✅ GOOD - Register in dedicated method
public function init_hooks() {
    add_action('init', [$this, 'register_post_types']);
    add_filter('the_content', [$this, 'modify_content']);
}

// ❌ BAD - Never in constructor
public function __construct() {
    add_action('init', [$this, 'register_post_types']); // DON'T DO THIS
}
```

#### 3. Nonce Verification
```php
// REST API - Check X-WP-Nonce header
public function submit_form_permissions_check($request) {
    $nonce = $request->get_header('X-WP-Nonce');
    if (!wp_verify_nonce($nonce, 'wp_rest')) {
        return new \WP_Error('invalid_nonce', 'Security check failed');
    }
    return true;
}

// Admin AJAX - Check $_POST nonce
if (!wp_verify_nonce($_POST['_wpnonce'], 'srfm_ajax_nonce')) {
    wp_die('Security check failed');
}
```

#### 4. Capability Checks
```php
// Always check user permissions for admin operations
if (!current_user_can('manage_options')) {
    return new \WP_Error('forbidden', 'Insufficient permissions');
}
```

#### 5. Output Escaping
```php
// Escape ALL user-generated output
echo esc_html($user_input);
echo esc_attr($attribute_value);
echo wp_kses_post($html_content);

// For SVG, use whitelist
echo wp_kses($svg_markup, Helper::$allowed_tags_svg);
```

### Build System Quirks

#### 1. Asset Generation Workflow
```bash
# CRITICAL: Always run before committing
npm run build

# This runs:
# 1. wp-scripts build (Webpack bundles)
# 2. npm run build:sass (SASS → CSS via custom script)
# 3. grunt minify (CSS/JS minification)
```

#### 2. Development vs Production Assets
```php
// In frontend-assets.php
$file_prefix = SRFM_DEBUG ? '' : '.min';
$dir_name = SRFM_DEBUG ? 'unminified' : 'minified';

// Development loads: assets/css/unminified/blocks/default/frontend.css
// Production loads: assets/css/minified/blocks/default/frontend.min.css
```

#### 3. Translation String Rules
```javascript
// ✅ GOOD - No variables in translatable strings
const message = sprintf(
    __('Form %s has been saved', 'sureforms'),
    formName
);

// ❌ BAD - Variables break translation extraction
const message = __(`Form ${formName} has been saved`, 'sureforms');
```

#### 4. Text Domain
```php
// Always use 'sureforms' text domain
__('Submit Form', 'sureforms');
_e('Email Address', 'sureforms');
_n('One entry', '%d entries', $count, 'sureforms');
```

---

## File Path Reference

### Core Entry Points

| Purpose | File Path | Key Classes/Functions |
|---------|-----------|----------------------|
| Plugin Bootstrap | [sureforms.php](sureforms.php) | Constants definition, activation hooks |
| Plugin Loader | [plugin-loader.php](plugin-loader.php) | Plugin_Loader class, autoloader, init hooks |
| Autoloader Logic | plugin-loader.php:79-140 | Custom SPL autoloader function |

### Key Backend Files

| Component | File Path | Purpose |
|-----------|-----------|---------|
| Form Submission | [inc/form-submit.php](inc/form-submit.php) | REST API submission handler (Form_Submit class) |
| REST API | [inc/rest-api.php](inc/rest-api.php) | All REST endpoints (Rest_Api class) |
| Database Entries | [inc/database/tables/entries.php](inc/database/tables/entries.php) | wp_srfm_entries table schema + ORM |
| Database Payments | [inc/database/tables/payments.php](inc/database/tables/payments.php) | wp_srfm_payments table schema + ORM |
| Helper Functions | [inc/helper.php](inc/helper.php) | Static utility methods (2000+ lines) |
| Post Types | [inc/post-types.php](inc/post-types.php) | Custom post type registration |
| Field Validation | [inc/field-validation.php](inc/field-validation.php) | Server-side validation rules |
| Block Base | [inc/blocks/base.php](inc/blocks/base.php) | Abstract base class for blocks |
| Block Registration | [inc/blocks/register.php](inc/blocks/register.php) | Block orchestrator |
| Frontend Assets | [inc/frontend-assets.php](inc/frontend-assets.php) | Conditional script/style loading |
| Form Markup | [inc/generate-form-markup.php](inc/generate-form-markup.php) | HTML generation from blocks |

### Key Frontend Files

| Component | File Path | Purpose |
|-----------|-----------|---------|
| Block Entry Point | [src/blocks/index.js](src/blocks/index.js) | Registers all Gutenberg blocks |
| Form Editor | [src/admin/single-form-settings/Editor.js](src/admin/single-form-settings/Editor.js) | Form builder UI |
| Dashboard | [src/admin/dashboard/index.js](src/admin/dashboard/index.js) | Forms listing page |
| Settings | [src/admin/settings/settings.js](src/admin/settings/settings.js) | Global settings UI |
| Entries | [src/admin/entries/index.js](src/admin/entries/index.js) | Entry management UI |
| Form Submit | [assets/js/unminified/form-submit.js](assets/js/unminified/form-submit.js) | Frontend form submission |

### Configuration Files

| Purpose | File Path | Usage |
|---------|-----------|-------|
| Webpack | [webpack.config.js](webpack.config.js) | Build configuration (11 entry points) |
| TailwindCSS | [tailwind.config.js](tailwind.config.js) | Utility CSS configuration |
| PHPUnit | [phpunit.xml.dist](phpunit.xml.dist) | Unit test configuration |
| PHPCS | [phpcs.xml](phpcs.xml) | WordPress coding standards |
| ESLint | [.eslintrc.js](.eslintrc.js) | JavaScript linting rules |
| Playwright | [playwright.config.js](playwright.config.js) | E2E test configuration |
| PHPStan | [phpstan.neon](phpstan.neon) | Static analysis config |

---

## Common Development Tasks

### Task 1: Add a New Form Field Block

**Time:** 60-90 minutes
**Difficulty:** Medium
**Risk Level:** Low (if following pattern)

**Steps:**

1. **Create Backend Class** ([inc/blocks/my-field/class-my-field.php](inc/blocks/my-field/))
```php
<?php
namespace SRFM\Inc\Blocks\MyField;

use SRFM\Inc\Blocks\Base;

class My_Field extends Base {
    protected $block_name = 'my-field';

    public function render_callback($attributes, $content) {
        $label = $attributes['label'] ?? '';
        $slug = $attributes['slug'] ?? 'my_field';
        $required = $attributes['required'] ?? false;

        ob_start();
        ?>
        <div class="srfm-field srfm-my-field">
            <label for="<?php echo esc_attr($slug); ?>">
                <?php echo esc_html($label); ?>
                <?php if ($required): ?>
                    <span class="required">*</span>
                <?php endif; ?>
            </label>
            <input
                type="text"
                id="<?php echo esc_attr($slug); ?>"
                name="<?php echo esc_attr($slug); ?>"
                class="srfm-field__input"
                <?php echo $required ? 'required' : ''; ?>
            />
        </div>
        <?php
        return ob_get_clean();
    }
}
```

2. **Create React Component** ([src/blocks/my-field/edit.js](src/blocks/my-field/))
```javascript
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

export default function Edit({ attributes, setAttributes }) {
    const { label, required, slug } = attributes;

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Field Settings', 'sureforms')}>
                    <TextControl
                        label={__('Label', 'sureforms')}
                        value={label}
                        onChange={(value) => setAttributes({ label: value })}
                    />
                    <TextControl
                        label={__('Field Slug', 'sureforms')}
                        value={slug}
                        onChange={(value) => setAttributes({ slug: value })}
                    />
                    <ToggleControl
                        label={__('Required', 'sureforms')}
                        checked={required}
                        onChange={(value) => setAttributes({ required: value })}
                    />
                </PanelBody>
            </InspectorControls>

            <div {...useBlockProps()}>
                <label>
                    {label}
                    {required && <span className="required">*</span>}
                </label>
                <input type="text" className="srfm-field__input" disabled />
            </div>
        </>
    );
}
```

3. **Create block.json** ([src/blocks/my-field/block.json](src/blocks/my-field/))
```json
{
    "apiVersion": 2,
    "name": "sureforms/my-field",
    "title": "My Custom Field",
    "category": "sureforms",
    "icon": "text",
    "attributes": {
        "label": {
            "type": "string",
            "default": "My Field"
        },
        "slug": {
            "type": "string",
            "default": "my_field"
        },
        "required": {
            "type": "boolean",
            "default": false
        }
    }
}
```

4. **Register Block** (in [inc/blocks/register.php](inc/blocks/register.php))
```php
use SRFM\Inc\Blocks\MyField\My_Field;

My_Field::get_instance();
```

5. **Add Validation** (in [inc/field-validation.php](inc/field-validation.php))
```php
public function validate_my_field($value, $field_data) {
    $is_required = $field_data['required'] ?? false;

    if ($is_required && empty($value)) {
        return new \WP_Error(
            'required_field',
            __('This field is required', 'sureforms')
        );
    }

    return true;
}
```

6. **Build and Test**
```bash
npm run build
# Test in block editor
# Test frontend submission
# Test validation
```

### Task 2: Add a New REST API Endpoint

**Time:** 30-45 minutes
**Difficulty:** Easy
**Risk Level:** Low

**Steps:**

1. **Open** [inc/rest-api.php](inc/rest-api.php)

2. **Add Method to Rest_Api Class**
```php
/**
 * Get custom data endpoint.
 *
 * @param \WP_REST_Request $request Request object.
 * @return \WP_REST_Response|WP_Error Response or error.
 * @since 2.6.0
 */
public function get_custom_data($request) {
    // Permission check (happens in permission_callback)

    // Sanitize input
    $form_id = absint($request->get_param('form_id'));

    // Business logic
    $data = [
        'form_id' => $form_id,
        'data' => 'your data here'
    ];

    return rest_ensure_response($data);
}

/**
 * Permission check for custom data endpoint.
 *
 * @param \WP_REST_Request $request Request object.
 * @return bool|WP_Error True if allowed, error otherwise.
 */
public function custom_data_permissions($request) {
    // Verify nonce
    $nonce = $request->get_header('X-WP-Nonce');
    if (!wp_verify_nonce($nonce, 'wp_rest')) {
        return new \WP_Error('invalid_nonce', 'Security check failed');
    }

    // Check capability
    if (!current_user_can('manage_options')) {
        return new \WP_Error('forbidden', 'Insufficient permissions');
    }

    return true;
}
```

3. **Register Route** (in `register_routes()` method)
```php
register_rest_route(
    'sureforms/v1',
    '/custom-data',
    [
        'methods' => 'GET',
        'callback' => [$this, 'get_custom_data'],
        'permission_callback' => [$this, 'custom_data_permissions'],
        'args' => [
            'form_id' => [
                'required' => true,
                'validate_callback' => function($param) {
                    return is_numeric($param);
                }
            ]
        ]
    ]
);
```

4. **Test with curl**
```bash
curl -X GET "http://localhost:8888/wp-json/sureforms/v1/custom-data?form_id=123" \
  -H "X-WP-Nonce: YOUR_NONCE_HERE"
```

### Task 3: Modify Form Submission Logic (Using Hooks)

**Time:** 30-60 minutes
**Difficulty:** Medium
**Risk Level:** Medium (test thoroughly)

**Approach:** Use hooks instead of modifying core files

**Example: Add Custom Integration**
```php
// In your custom plugin or theme functions.php

/**
 * Send form data to custom CRM after submission.
 */
add_action('srfm_after_submission', function($entry_id, $form_data, $form_id) {
    // Only for specific form
    if (123 !== $form_id) {
        return;
    }

    // Extract data
    $email = $form_data['email']['value'] ?? '';
    $name = $form_data['name']['value'] ?? '';

    // Send to CRM
    wp_remote_post('https://crm.example.com/api/contacts', [
        'body' => json_encode([
            'email' => $email,
            'name' => $name,
            'source' => 'sureforms'
        ]),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer YOUR_API_KEY'
        ]
    ]);
}, 10, 3);
```

**Example: Modify Submission Data Before Saving**
```php
/**
 * Add calculated field to submission data.
 */
add_filter('srfm_submission_data', function($data, $form_id) {
    // Calculate total from price fields
    $total = 0;
    foreach ($data as $field) {
        if ('price' === ($field['type'] ?? '')) {
            $total += floatval($field['value']);
        }
    }

    // Add calculated total
    $data['calculated_total'] = [
        'value' => $total,
        'label' => 'Total Amount',
        'type' => 'calculated'
    ];

    return $data;
}, 10, 2);
```

**Available Hooks:**
- `srfm_before_submission` - Before processing (can throw exception to halt)
- `srfm_submission_data` - Modify data before save
- `srfm_after_submission` - After save (for integrations)
- `srfm_email_content` - Modify email content
- `srfm_field_validation` - Custom validation rules

### Task 4: Add a New Hook

**Time:** 15-20 minutes
**Difficulty:** Easy
**Risk Level:** Low

**Steps:**

1. **Identify Location** - Where the hook should fire
2. **Add Action Hook**
```php
// In relevant file (e.g., inc/form-submit.php)

/**
 * Fires before sending email notification.
 *
 * @param array $email_data Email data including to, subject, message.
 * @param int   $form_id    Form post ID.
 * @param int   $entry_id   Entry ID in database.
 * @since 2.6.0
 */
do_action('srfm_before_email_send', $email_data, $form_id, $entry_id);
```

3. **Add Filter Hook**
```php
/**
 * Filters email subject before sending.
 *
 * @param string $subject  Email subject.
 * @param int    $form_id  Form post ID.
 * @param array  $form_data Submission data.
 * @return string Modified subject.
 * @since 2.6.0
 */
$subject = apply_filters('srfm_email_subject', $subject, $form_id, $form_data);
```

4. **Document** - Add to docs/04-backend/HOOKS-REFERENCE.md
5. **Update README.md** - Add to extension points section

---

## Build & Development Commands

### Development Workflow
```bash
# Start development with hot reload
npm start

# Make changes to src/ or inc/

# Browser auto-refreshes on save
```

### Before Committing
```bash
# Fix JavaScript formatting
npm run lint-js:fix

# Fix CSS issues
npm run lint-css:fix

# Production build (CRITICAL - always run)
npm run build

# Check PHP coding standards
composer phpcs

# Run static analysis
composer phpstan

# Run unit tests
composer test

# Commit if all pass
git add .
git commit -m "feat: your change description"
```

### Testing
```bash
# JavaScript unit tests (Jest)
npm run test:unit

# E2E tests (Playwright)
npm run test:e2e

# PHP unit tests (PHPUnit)
composer test

# Code coverage
composer test:coverage
```

### Local Environment
```bash
# Start wp-env local WordPress
npm run play:up
# Access: http://localhost:8888
# Admin: admin / password

# Stop wp-env
npm run play:stop

# Clean restart
npm run play:down && npm run play:up
```

### Code Quality
```bash
# Run all linters
npm run lint-js        # ESLint
npm run lint-css       # Stylelint
composer phpcs         # PHP_CodeSniffer
composer phpstan       # Static analysis (level 8)
composer phpinsights   # Code quality metrics

# Auto-fix issues
npm run lint-js:fix
npm run lint-css:fix
composer phpcbf        # Auto-fix PHP
```

---

## Critical Dependencies

### Backend Requirements
- **WordPress:** 6.4+ (Block Editor API, REST API)
- **PHP:** 7.4+ (Namespaces, traits, type hints)
- **MySQL:** 5.6+ (Custom tables with JSON support)
- **Action Scheduler:** 3.x (Background job processing)

### Frontend Requirements
- **React:** 18.2+ (Block editor integration)
- **@wordpress/scripts:** 26.2+ (Build tooling)
- **TailwindCSS:** 3.4+ (Utility-first CSS)
- **React Query:** 5.x (Data fetching and caching)

### Build Tools
- **Node.js:** 18.15.0 (specified in .nvmrc - use `nvm use`)
- **npm:** Latest compatible with Node 18
- **Webpack:** 5.89+ (JavaScript bundling)
- **Grunt:** 1.4+ (CSS/JS minification)

---

## Known Issues & Workarounds

### Issue: Autoloader Conflicts with Composer

**Symptom:** Class not found errors when using Composer packages
**Root Cause:** Custom autoloader runs first, may conflict with Composer namespaces
**Workaround:** Avoid namespace conflicts (don't use SRFM\ namespace in Composer packages)
**Affected Files:** [plugin-loader.php:79-140](plugin-loader.php)

### Issue: Block Editor Crashes on Save

**Symptom:** Form saves fail with console errors, "Block validation failed"
**Root Cause:** Block attribute schema mismatch between block.json and saved content
**Fix:** Always validate block.json schemas match PHP render_callback expectations
**Debug:** Check browser console for specific attribute causing issue
**Affected Files:** [inc/blocks/*/block.json](inc/blocks/), [src/blocks/*/](src/blocks/)

### Issue: Form Submissions Fail Silently

**Symptom:** No error message, submission not saved, no feedback to user
**Debug Steps:**
1. Check browser console for AJAX errors
2. Verify X-WP-Nonce in REST API request headers (Network tab)
3. Check PHP error logs for validation failures (`wp-content/debug.log`)
4. Test with spam protection disabled (reCAPTCHA can fail silently)
5. Verify REST API is accessible (`/wp-json/sureforms/v1/submit-form`)

**Common Causes:**
- Expired nonce (page cached, refresh needed)
- Spam protection false positive
- JavaScript error preventing submission
- REST API disabled or blocked by security plugin

**Affected Files:** [inc/form-submit.php](inc/form-submit.php), [assets/js/unminified/form-submit.js](assets/js/unminified/form-submit.js)

### Issue: Assets Not Loading After Update

**Symptom:** Broken styling, JavaScript errors, blocks not working
**Root Cause:** Browser cache or incomplete build
**Fix:**
```bash
# Rebuild assets
npm run build

# Clear WordPress cache (if using caching plugin)
# Hard refresh browser (Cmd/Ctrl + Shift + R)
```

**Prevention:** Always run `npm run build` before committing

### Issue: Translation Strings Not Updating

**Symptom:** New strings not appearing in translation files
**Root Cause:** Translation template (.pot) not regenerated
**Fix:**
```bash
# Regenerate translation template
npm run makepot

# Output: languages/sureforms.pot
```

**Note:** Run after adding new `__()`, `_e()`, `_n()` calls

---

## Performance Considerations

### Database Query Optimization

**Best Practices:**
```php
// ✅ GOOD - Use indexes
$wpdb->get_results(
    $wpdb->prepare(
        "SELECT ID, form_data, created_at
         FROM {$wpdb->prefix}srfm_entries
         WHERE form_id = %d
         AND status = %s
         ORDER BY created_at DESC
         LIMIT %d",
        $form_id,
        'unread',
        20
    )
);

// ❌ BAD - Unindexed column, SELECT *
$wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}srfm_entries
     WHERE form_type = 'quiz'"  // No index on form_type
);
```

**Indexes Available:**
- wp_srfm_entries: form_id, user_id, status, created_at
- wp_srfm_payments: entry_id, form_id, transaction_id, status

### Asset Loading Strategy

**Conditional Loading Pattern:**
```php
// Only load scripts on pages with forms
if (has_shortcode($post->post_content, 'sureforms') || has_block('sureforms/sform')) {
    wp_enqueue_script('srfm-form-submit');
    wp_enqueue_style('srfm-frontend');
}

// Block-specific assets
if ('phone' === $block_name) {
    wp_enqueue_script('srfm-phone-intl-input');
}
```

**Bundle Sizes (Production):**
- formEditor.js: ~1.9MB (admin only, code split)
- blocks.js: ~1.3MB (admin only, code split)
- formSubmit.js: ~60KB (frontend, minified)
- frontend.css: ~45KB (frontend, minified)

### Caching Strategy

**What to Cache:**
- Form metadata (post meta)
- API responses (React Query, 5min stale time)
- Transients for expensive queries

**What NOT to Cache:**
- Form nonces (request-specific)
- User submission data
- Admin pages

---

## Security Checklist

Before committing any code, verify:

- [ ] All user input sanitized with WordPress functions
  - `sanitize_text_field()`, `sanitize_email()`, `sanitize_textarea_field()`
- [ ] All output escaped
  - `esc_html()`, `esc_attr()`, `wp_kses()` for HTML
- [ ] Nonce verification on all form submissions and AJAX
  - REST API: Check X-WP-Nonce header
  - Admin AJAX: Check $_POST['_wpnonce']
- [ ] Capability checks on all admin operations
  - `current_user_can('manage_options')`
- [ ] SQL queries use `$wpdb->prepare()`
  - No string concatenation in queries
- [ ] File uploads validate type and size
  - Check MIME type and file extension
  - Limit file size (default 10MB)
- [ ] No sensitive data in JavaScript variables
  - API keys, database credentials in PHP only
- [ ] REST API endpoints have `permission_callback`
  - Never use `__return_true` without good reason
- [ ] GDPR compliance maintained for data storage
  - Auto-delete configured, consent tracked

---

## Version Compatibility Matrix

| SureForms | WordPress | PHP   | React | Node.js | MySQL |
|-----------|-----------|-------|-------|---------|-------|
| 2.5.x     | 6.4+      | 7.4+  | 18.2  | 18.15   | 5.6+  |
| 2.4.x     | 6.4+      | 7.4+  | 18.2  | 18.15   | 5.6+  |
| 2.3.x     | 6.3+      | 7.4+  | 18.2  | 18.15   | 5.6+  |

**Breaking Changes:**
- **2.0.0:** Moved from post meta to custom tables (migration required)
- **2.3.0:** Updated React to v18 (component lifecycle changes)
- **2.4.0:** Required WordPress 6.4+ (uses new block APIs)

---

## Cross-References to Detailed Documentation

For in-depth information, refer to:

- **Architecture:** [docs/02-architecture/ARCHITECTURE.md](docs/02-architecture/ARCHITECTURE.md)
- **Getting Started:** [docs/01-getting-started/GETTING-STARTED.md](docs/01-getting-started/GETTING-STARTED.md)
- **Coding Standards:** [docs/03-development/CODING-STANDARDS.md](docs/03-development/CODING-STANDARDS.md)
- **Database Schema:** [docs/04-backend/DATABASE-SCHEMA.md](docs/04-backend/DATABASE-SCHEMA.md)
- **Hooks Reference:** [docs/04-backend/HOOKS-REFERENCE.md](docs/04-backend/HOOKS-REFERENCE.md)
- **Frontend Guide:** [docs/05-frontend/FRONTEND-GUIDE.md](docs/05-frontend/FRONTEND-GUIDE.md)
- **REST API:** [docs/04-backend/REST-API.md](docs/04-backend/REST-API.md)
- **Security:** [docs/07-security/SECURITY.md](docs/07-security/SECURITY.md)
- **Testing:** [docs/08-testing/TESTING.md](docs/08-testing/TESTING.md)
- **Release Process:** [docs/09-deployment/RELEASE-PROCESS.md](docs/09-deployment/RELEASE-PROCESS.md)
- **Troubleshooting:** [docs/10-reference/TROUBLESHOOTING.md](docs/10-reference/TROUBLESHOOTING.md)

---

## Quick Decision Tree for AI Agents

```
Is this a NEW feature?
├─ YES
│  ├─ Does it modify database schema?
│  │  ├─ YES → ⛔ Request human approval (HIGH RISK)
│  │  └─ NO → Continue
│  │
│  ├─ Does it change block attributes?
│  │  ├─ YES → ⛔ Request human approval (BREAKS SAVED FORMS)
│  │  └─ NO → Continue
│  │
│  ├─ Does it affect form submission?
│  │  ├─ YES → ⚠️  Use hooks (srfm_before_submission), test thoroughly
│  │  └─ NO → Continue
│  │
│  └─ Is it a new block, endpoint, or UI?
│     └─ YES → ✅ Safe to proceed (follow patterns above)
│
└─ NO (Modifying existing)
   ├─ Is it in HIGH-RISK area?
   │  ├─ YES → ⚠️  Proceed with caution, extensive testing
   │  └─ NO → Continue
   │
   ├─ Does it change API contracts?
   │  ├─ YES → ⛔ Request human approval (BREAKING CHANGE)
   │  └─ NO → Continue
   │
   └─ Is it styling, tests, or docs?
      └─ YES → ✅ Safe to proceed

Always run: npm run build && composer phpcs && composer phpstan
```

---

**Last Updated:** 2026-02-06
**Maintainer:** SureForms Development Team
**Feedback:** Create issue at https://github.com/brainstormforce/sureforms/issues
