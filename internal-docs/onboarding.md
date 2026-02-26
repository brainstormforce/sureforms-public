# Developer Onboarding

**Version:** 2.5.0

---

## Welcome to SureForms

This guide helps you become productive quickly, whether you have 1 hour, 1 day, or 1 week.

**What you're working on:**
- **SureForms Free:** AI-powered WordPress form builder (219 PHP files, 50+ blocks)
- **SureForms Pro:** Premium extension (206 PHP files, payments, integrations, advanced features)

**Architecture:** WordPress + Gutenberg blocks + React 18 + Custom database

---

## Prerequisites

Before starting:
- [ ] WordPress 6.4+ installed locally
- [ ] Node.js 18+ and npm 8+
- [ ] PHP 7.4+ with Composer
- [ ] Git configured
- [ ] IDE with PHP/JavaScript support
- [ ] Both plugins cloned from GitHub

**Test environment:**
```bash
# If using Local by Flywheel or similar
http://localhost:10003/wp-admin/
Username: admin
Password: admin
```

---

## 1-Hour Quick Start

**Goal:** Make your first successful change

### Step 1: Get Code Running (15 min)

```bash
# Clone repositories (if not already)
cd /path/to/wp-content/plugins/
git clone https://github.com/brainstormforce/sureforms.git
git clone https://github.com/brainstormforce/sureforms-pro.git

# Install dependencies - SureForms Free
cd sureforms/
npm install
composer install

# Build assets
npm run build

# Install dependencies - SureForms Pro
cd ../sureforms-pro/
npm install
composer install
npm run build
```

**Verify installation:**
```bash
# In WordPress admin
wp plugin activate sureforms sureforms-pro
wp plugin list | grep sureforms
```

### Step 2: Create Your First Form (15 min)

**Via WordPress admin:**
1. Go to **SureForms → Add New**
2. Click **Create with AI** or **Start from Blank**
3. Add fields: Email, Name, Message
4. Click **Publish**
5. Add to a page with the SureForms block

**Via WP-CLI:**
```bash
# Create form programmatically
wp post create \
  --post_type=sureforms_form \
  --post_title="Test Contact Form" \
  --post_status=publish \
  --post_content='<!-- wp:sureforms/form -->'
```

### Step 3: Make a Simple Change (20 min)

**Task:** Change the submit button text

**Files to modify:**
```
src/blocks/form/edit.js          ← React component
inc/blocks/form/block.php        ← Server-side rendering
```

**Change 1: Edit the React component**

Open `src/blocks/form/edit.js`:

```javascript
// Find around line 45-50
const { submitButtonText } = attributes;

// Change default value
const defaultSubmitText = __('Send Message', 'sureforms'); // ← Change this
```

**Change 2: Rebuild**

```bash
npm run build
```

**Verify:** Create new form, check if default button text changed.

### Step 4: Debug Your Change (10 min)

**Enable debugging:**

Edit `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true); // Loads unminified JS
```

**Check debug log:**
```bash
tail -f wp-content/debug.log
```

**Browser console:**
- Open DevTools → Console
- Look for JavaScript errors
- Check Network tab for AJAX failures

---

## 1-Day Deep Dive

**Goal:** Understand architecture and make meaningful contributions

### Morning: Core Concepts (4 hours)

#### Hour 1: Architecture Overview

**Read these docs first:**
1. [README.md](README.md) - Project overview
2. [architecture.md](architecture.md) - System design
3. [codebase-map.md](codebase-map.md) - File structure

**Key concepts to understand:**
- **Gutenberg blocks:** React components that render forms
- **Custom post type:** `sureforms_form` stores form configurations
- **Custom database tables:** `wp_sureforms_entries`, `wp_sureforms_payments`
- **REST API:** Handles form submissions, AI generation
- **AJAX handlers:** Payment processing, file uploads

**Test your understanding:**
- Where is form submission data saved? (Answer: `wp_sureforms_entries` table)
- How does AI form generation work? (Answer: REST endpoint → external middleware → GPT API)
- What's the difference between Free and Pro? (Answer: Pro adds payments, integrations, conditional logic)

#### Hour 2: Data Flow

**Trace a form submission from start to finish:**

```
User fills form → Frontend validation → AJAX request
  ↓
inc/form-submit.php (Line 88-119: Nonce verification)
  ↓
Field validation (inc/field-validation.php)
  ↓
Sanitization (inc/helper.php::sanitize_by_field_type())
  ↓
Database insert (inc/database/tables/entries.php::insert())
  ↓
Email notification (inc/email/email-handler.php)
  ↓
Confirmation message (or redirect)
```

**Hands-on exercise:**
1. Enable MySQL query logging
2. Submit a test form
3. Check `wp_sureforms_entries` table
4. Verify entry data matches submission

```bash
# View recent entries
wp db query "SELECT * FROM wp_sureforms_entries ORDER BY id DESC LIMIT 5;"
```

#### Hour 3: Payment Processing

**Understand Stripe payment flow:**

```
User clicks "Pay" → Frontend creates payment intent
  ↓
AJAX: wp_ajax_nopriv_srfm_create_payment_intent
  ↓
inc/payments/front-end.php (Line 77-83: Create Stripe PaymentIntent)
  ↓
Stripe API processes payment
  ↓
Webhook received: sureforms/webhook_test
  ↓
inc/payments/stripe/stripe-webhook.php (Line 220-637: Handle events)
  ↓
Update wp_sureforms_payments table
  ↓
Send confirmation email
```

**Hands-on exercise:**
1. Set up Stripe test keys
2. Create a payment form
3. Use Stripe test card: `4242 4242 4242 4242`
4. Check `wp_sureforms_payments` table for payment record

#### Hour 4: Pro Features

**Explore key Pro additions:**

**1. User Registration:**
- File: `inc/business/user-registration/processor.php`
- Creates WordPress user accounts from form submissions
- Handles login/password reset

**2. Native Integrations:**
- Folder: `inc/pro/native-integrations/integrations/`
- 24+ services: Mailchimp, Brevo, HubSpot, Salesforce, etc.
- OAuth authentication with encrypted token storage

**3. Conditional Logic:**
- Frontend: `src/pro/conditional-logic/`
- Show/hide fields based on user selections
- Real-time UI updates

**Hands-on exercise:**
1. Create form with registration block
2. Submit form, verify user created: `wp user list`
3. Enable Mailchimp integration (test mode)
4. Submit form, check integration logs

### Afternoon: Build Your First Feature (4 hours)

**Challenge:** Add a new field type

**Example: "Star Rating" field**

#### Step 1: Plan (30 min)

**Requirements:**
- Display 5 stars (clickable)
- Save selected rating (1-5)
- Validate: Required if configured
- Display in entries table

**Files to create/modify:**
```
src/blocks/star-rating/           ← New React component
inc/blocks/star-rating/block.php  ← Server-side rendering
inc/helper.php                     ← Add sanitization
inc/field-validation.php           ← Add validation
```

#### Step 2: Create Block Structure (1 hour)

**Create block files:**

```bash
# Create directories
mkdir -p src/blocks/star-rating
mkdir -p inc/blocks/star-rating

# Copy from existing field (Phone is a good template)
cp -r src/blocks/phone/* src/blocks/star-rating/
cp inc/blocks/phone/block.php inc/blocks/star-rating/
```

**Edit `src/blocks/star-rating/block.json`:**

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "sureforms/star-rating",
  "title": "Star Rating",
  "category": "sureforms",
  "icon": "star-filled",
  "description": "Display a star rating input",
  "attributes": {
    "label": {
      "type": "string",
      "default": "Rate your experience"
    },
    "required": {
      "type": "boolean",
      "default": false
    },
    "maxRating": {
      "type": "number",
      "default": 5
    }
  },
  "supports": {
    "html": false
  }
}
```

**Edit `src/blocks/star-rating/edit.js`:**

```javascript
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { TextControl, ToggleControl, RangeControl } from '@wordpress/components';

export default function Edit({ attributes, setAttributes }) {
  const { label, required, maxRating } = attributes;

  return (
    <div {...useBlockProps()}>
      <TextControl
        label={__('Label', 'sureforms')}
        value={label}
        onChange={(value) => setAttributes({ label: value })}
      />
      <ToggleControl
        label={__('Required', 'sureforms')}
        checked={required}
        onChange={(value) => setAttributes({ required: value })}
      />
      <RangeControl
        label={__('Max Stars', 'sureforms')}
        value={maxRating}
        onChange={(value) => setAttributes({ maxRating: value })}
        min={1}
        max={10}
      />
      <div className="srfm-star-rating-preview">
        {[...Array(maxRating)].map((_, i) => (
          <span key={i} className="dashicons dashicons-star-filled"></span>
        ))}
      </div>
    </div>
  );
}
```

#### Step 3: Server-Side Rendering (45 min)

**Edit `inc/blocks/star-rating/block.php`:**

```php
<?php
namespace SRFM\Inc\Blocks\Star_Rating;

class Block {
  public static function register() {
    register_block_type(
      'sureforms/star-rating',
      [
        'render_callback' => [__CLASS__, 'render'],
      ]
    );
  }

  public static function render($attributes) {
    $label = esc_html($attributes['label'] ?? 'Rate your experience');
    $required = !empty($attributes['required']);
    $max_rating = absint($attributes['maxRating'] ?? 5);
    $field_name = 'srfm-star-rating-' . uniqid();

    ob_start();
    ?>
    <div class="srfm-field srfm-star-rating">
      <label>
        <?php echo $label; ?>
        <?php if ($required) : ?>
          <span class="srfm-required">*</span>
        <?php endif; ?>
      </label>
      <div class="srfm-star-rating-input" data-max="<?php echo $max_rating; ?>">
        <?php for ($i = 1; $i <= $max_rating; $i++) : ?>
          <span class="srfm-star" data-value="<?php echo $i; ?>">
            ★
          </span>
        <?php endfor; ?>
      </div>
      <input type="hidden" name="<?php echo esc_attr($field_name); ?>" value="" <?php echo $required ? 'required' : ''; ?>>
    </div>
    <?php
    return ob_get_clean();
  }
}
```

#### Step 4: Frontend JavaScript (45 min)

**Create `src/blocks/star-rating/frontend.js`:**

```javascript
document.addEventListener('DOMContentLoaded', function() {
  const ratingContainers = document.querySelectorAll('.srfm-star-rating-input');

  ratingContainers.forEach(container => {
    const stars = container.querySelectorAll('.srfm-star');
    const input = container.nextElementSibling;
    const maxRating = parseInt(container.dataset.max);

    stars.forEach(star => {
      star.addEventListener('click', function() {
        const value = parseInt(this.dataset.value);
        input.value = value;

        // Visual update
        stars.forEach((s, index) => {
          if (index < value) {
            s.classList.add('selected');
          } else {
            s.classList.remove('selected');
          }
        });
      });

      // Hover effect
      star.addEventListener('mouseenter', function() {
        const value = parseInt(this.dataset.value);
        stars.forEach((s, index) => {
          if (index < value) {
            s.classList.add('hover');
          } else {
            s.classList.remove('hover');
          }
        });
      });
    });

    container.addEventListener('mouseleave', function() {
      stars.forEach(s => s.classList.remove('hover'));
    });
  });
});
```

#### Step 5: Register Block (15 min)

**Edit `inc/blocks/register.php`:**

```php
// Add to existing blocks registration
Star_Rating\Block::register();
```

**Edit `src/blocks/star-rating/index.js`:**

```javascript
import { registerBlockType } from '@wordpress/blocks';
import edit from './edit';
import metadata from './block.json';

registerBlockType(metadata.name, {
  edit,
  save: () => null, // Server-side rendering
});
```

#### Step 6: Add Sanitization & Validation (30 min)

**Edit `inc/helper.php`:**

Find `sanitize_by_field_type()` method and add:

```php
case 'star-rating':
  $value = absint($value);
  // Ensure value is between 1 and max rating
  return ($value >= 1 && $value <= 10) ? $value : 0;
```

**Edit `inc/field-validation.php`:**

Find validation logic and add:

```php
if ($field_type === 'star-rating' && $is_required && empty($value)) {
  $errors[] = __('Please select a rating', 'sureforms');
}
```

#### Step 7: Build & Test (30 min)

```bash
# Build assets
npm run build

# Test in browser
# 1. Create new form
# 2. Add "Star Rating" block
# 3. Configure settings (label, required, max stars)
# 4. Publish form
# 5. Submit test entry
# 6. Verify data saved correctly

# Check database
wp db query "SELECT * FROM wp_sureforms_entries ORDER BY id DESC LIMIT 1;"
```

---

## 1-Week Mastery

**Goal:** Expert-level understanding and complex feature development

### Day 1: Architecture Deep Dive

**Morning:**
- Complete 1-day onboarding
- Read all internal docs thoroughly
- Map data flow for all major features

**Afternoon:**
- Trace payment processing end-to-end
- Understand webhook verification
- Study integration architecture

### Day 2: Security & Best Practices

**Focus areas:**
1. **Input sanitization:** Understand all 15+ sanitization methods
2. **Output escaping:** When to use `esc_html()`, `esc_attr()`, `esc_url()`
3. **SQL injection prevention:** Always use `$wpdb->prepare()`
4. **CSRF protection:** Nonce verification patterns
5. **Authorization:** `current_user_can()` checks

**Hands-on exercises:**
- Review `inc/form-submit.php` (Lines 88-119: Security checks)
- Study `inc/helper.php` sanitization methods
- Find and fix intentional security bugs in test code

**Read:**
- [coding-standards.md](coding-standards.md)
- [ai-agent-guide.md](ai-agent-guide.md) - Security section

### Day 3: React & Gutenberg Blocks

**Topics:**
1. **Block architecture:** How Gutenberg blocks work
2. **Attributes:** Data storage and retrieval
3. **Inspector controls:** Settings sidebar
4. **Block variations:** Different field configurations
5. **Dynamic blocks:** Server-side rendering

**Build complex block:**
- **Multi-step form block** (if not using Pro feature)
- Add step navigation
- Save progress between steps
- Implement validation per step

**Resources:**
- Official Gutenberg docs: https://developer.wordpress.org/block-editor/
- Example: Study `src/blocks/phone/` for complex field

### Day 4: Database & REST API

**Morning: Database operations**

**Study these classes:**
- `inc/database/base.php` - Base table class
- `inc/database/tables/entries.php` - Entries CRUD
- `inc/database/tables/payments.php` - Payment records

**Practice:**
```php
// Create custom query
use SRFM\Inc\Database\Tables\Entries;

$entries = Entries::get_all([
  'where' => [
    ['key' => 'form_id', 'value' => 123, 'compare' => '='],
    ['key' => 'status', 'value' => 'published', 'compare' => '=']
  ],
  'orderby' => 'created_at',
  'order' => 'DESC',
  'limit' => 20
]);
```

**Afternoon: REST API development**

**Create custom endpoint:**

```php
// In new file: inc/rest-api-custom.php
namespace SRFM\Inc;

class Rest_API_Custom {
  public function register_routes() {
    register_rest_route('sureforms/v1', '/custom-stats', [
      'methods' => 'GET',
      'callback' => [$this, 'get_stats'],
      'permission_callback' => [$this, 'check_permissions']
    ]);
  }

  public function check_permissions() {
    return current_user_can('manage_options');
  }

  public function get_stats($request) {
    // Your logic here
    return ['success' => true, 'data' => []];
  }
}
```

### Day 5: Pro Features & Integrations

**Morning: Payment processing**

**Study both gateways:**
- Stripe: `inc/payments/stripe/`
- PayPal (Pro): `inc/business/payments/pay-pal/`

**Understand webhook handling:**
- Signature verification
- Event processing
- Database updates
- Error handling

**Afternoon: Build custom integration**

**Example: Slack integration**

1. Create integration folder:
```
inc/pro/native-integrations/integrations/slack/
├── config.json
└── actions/
    └── send-message.php
```

2. Define config:
```json
{
  "name": "Slack",
  "slug": "slack",
  "auth_method": "webhook_url",
  "actions": [
    {
      "name": "Send Message",
      "slug": "send-message",
      "endpoint": "chat.postMessage"
    }
  ]
}
```

3. Implement action:
```php
<?php
namespace SRFM_PRO\Inc\Pro\Native_Integrations\Integrations\Slack\Actions;

class Send_Message {
  public function execute($integration_data, $form_data) {
    $webhook_url = $integration_data['webhook_url'];
    $message = $this->format_message($form_data);

    $response = wp_remote_post($webhook_url, [
      'body' => json_encode(['text' => $message]),
      'headers' => ['Content-Type' => 'application/json']
    ]);

    if (is_wp_error($response)) {
      return ['success' => false, 'error' => $response->get_error_message()];
    }

    return ['success' => true];
  }

  private function format_message($form_data) {
    // Format form data as Slack message
    return "New form submission:\n" . print_r($form_data, true);
  }
}
```

---

## Testing Your Changes

### Local Testing

**PHP Unit Tests:**
```bash
# Run all tests
composer test

# Run specific test file
vendor/bin/phpunit tests/unit/test-helper.php

# Run with coverage
composer test-coverage
```

**JavaScript Tests:**
```bash
# Run all JS tests
npm run test:unit

# Watch mode
npm run test:unit:watch

# Coverage
npm run test:unit:coverage
```

### E2E Testing (Playwright)

```bash
# Start test environment
npm run play:up

# Run E2E tests
npm run play:run

# Interactive mode (see browser)
npm run play:run:interactive

# Stop environment
npm run play:down
```

### Manual Testing Checklist

Before submitting PR:
- [ ] Form creation works
- [ ] Form submission saves entry
- [ ] Email notifications sent
- [ ] Payment processing works (if touched)
- [ ] No JavaScript console errors
- [ ] No PHP errors in debug.log
- [ ] Works in latest WordPress version
- [ ] Works in Firefox, Chrome, Safari
- [ ] Mobile responsive
- [ ] Accessibility: keyboard navigation works

---

## Code Review Process

### Before Creating PR

**Self-review:**
1. Run linters:
```bash
composer lint       # PHP_CodeSniffer
npm run lint-js     # ESLint
```

2. Auto-fix minor issues:
```bash
composer format         # phpcbf
npm run lint-js:fix     # ESLint --fix
```

3. Check for common issues:
- [ ] All inputs sanitized
- [ ] All outputs escaped
- [ ] SQL queries use `prepare()`
- [ ] Nonces verified for AJAX/forms
- [ ] Capabilities checked for admin actions
- [ ] No hardcoded strings (use `__()` for i18n)

### Creating PR

**Branch naming:**
```bash
git checkout -b feat/star-rating-field
git checkout -b fix/payment-webhook-error
git checkout -b refactor/database-queries
```

**Commit message format:**
```
type(scope): brief description

Longer explanation if needed.

Co-Authored-By: Your Name <email@example.com>
```

**Types:** feat, fix, docs, style, refactor, test, chore

**Example:**
```
feat(blocks): add star rating field

- Add new star-rating block
- Implement frontend JavaScript
- Add sanitization and validation
- Update docs with new field type

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>
```

### PR Checklist

- [ ] Tests pass: `composer test && npm run test:unit`
- [ ] Linting passes: `composer lint && npm run lint-js`
- [ ] No console errors
- [ ] Database migrations documented (if any)
- [ ] Breaking changes documented
- [ ] Changelog updated
- [ ] Screenshots attached (for UI changes)

---

## Common Tasks & Patterns

### Add New AJAX Handler

**Pattern:**
```php
// In controller file
public function register_ajax_actions() {
  add_action('wp_ajax_srfm_my_action', [$this, 'handle_my_action']);
  add_action('wp_ajax_nopriv_srfm_my_action', [$this, 'handle_my_action']);
}

public function handle_my_action() {
  // CRITICAL: Always verify nonce
  check_ajax_referer('srfm_my_action_nonce', 'security');

  // Check permissions if needed
  if (!current_user_can('manage_options')) {
    wp_send_json_error(['message' => 'Insufficient permissions']);
  }

  // Sanitize inputs
  $data = Helper::sanitize_array_recursively($_POST['data'] ?? []);

  // Process...

  wp_send_json_success(['result' => $result]);
}
```

### Add New REST Endpoint

**Pattern:**
```php
// In inc/rest-api.php
public function register_routes() {
  register_rest_route('sureforms/v1', '/my-endpoint', [
    'methods' => 'POST',
    'callback' => [$this, 'handle_request'],
    'permission_callback' => [$this, 'check_permissions']
  ]);
}

public function check_permissions() {
  return current_user_can('manage_options');
}

public function handle_request($request) {
  // Always verify nonce
  $nonce = $request->get_header('X-WP-Nonce');
  if (!wp_verify_nonce($nonce, 'wp_rest')) {
    return new \WP_Error('invalid_nonce', 'Invalid nonce', ['status' => 403]);
  }

  // Get and sanitize parameters
  $params = $request->get_params();
  $clean_params = Helper::sanitize_array_recursively($params);

  // Process...

  return ['success' => true, 'data' => $result];
}
```

### Add WordPress Hook

**Action hook (notify about event):**
```php
// Trigger action after entry save
do_action('sureforms_after_entry_save', $entry_id, $form_id, $entry_data);

// Other plugins/themes can listen:
add_action('sureforms_after_entry_save', function($entry_id, $form_id, $data) {
  // Custom logic
}, 10, 3);
```

**Filter hook (modify data):**
```php
// Allow filtering of email subject
$subject = apply_filters('sureforms_email_subject', $subject, $form_id, $entry_data);

// Other plugins/themes can modify:
add_filter('sureforms_email_subject', function($subject, $form_id, $data) {
  return "[Form $form_id] $subject";
}, 10, 3);
```

### Database Operations

**Insert entry:**
```php
use SRFM\Inc\Database\Tables\Entries;

$entry_id = Entries::insert([
  'form_id' => 123,
  'user_id' => get_current_user_id(),
  'status' => 'published',
  'entry_data' => wp_json_encode($form_data)
]);
```

**Update entry:**
```php
Entries::update($entry_id, [
  'status' => 'spam'
]);
```

**Delete entry:**
```php
Entries::delete($entry_id);
```

**Query entries:**
```php
$entries = Entries::get_all([
  'where' => [
    ['key' => 'form_id', 'value' => 123, 'compare' => '=']
  ],
  'limit' => 20,
  'offset' => 0
]);
```

---

## Debugging Tips

### Enable Debug Mode

**wp-config.php:**
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', true);
define('SAVEQUERIES', true);
```

### Check Debug Log

```bash
# Real-time log monitoring
tail -f wp-content/debug.log

# Search for errors
grep "SureForms" wp-content/debug.log
```

### JavaScript Debugging

**Browser console:**
```javascript
// Check global object
console.log(window.sureforms);

// Check block attributes
wp.data.select('core/block-editor').getBlocks();

// Check form data
document.querySelector('form').addEventListener('submit', (e) => {
  console.log('Form data:', new FormData(e.target));
});
```

### Database Queries

**Install Query Monitor plugin:**
```bash
wp plugin install query-monitor --activate
```

**Check slow queries:**
- Visit any admin page
- Click "Query Monitor" in admin bar
- View "Queries" tab
- Sort by time
- Optimize slow queries

**Manual query inspection:**
```php
// Add to code temporarily
global $wpdb;
$wpdb->show_errors();
$wpdb->print_error();
```

### AJAX Debugging

**Check network tab:**
1. Open DevTools → Network
2. Filter: XHR
3. Submit form
4. Click request
5. Check Response tab for errors

**Add debug output:**
```php
public function handle_ajax() {
  error_log('AJAX data: ' . print_r($_POST, true));
  // ... rest of code
}
```

---

## Resources

### Internal Documentation
- [README.md](README.md) - Quick start
- [architecture.md](architecture.md) - System design
- [codebase-map.md](codebase-map.md) - File structure
- [apis.md](apis.md) - API reference
- [coding-standards.md](coding-standards.md) - Code standards
- [ai-agent-guide.md](ai-agent-guide.md) - AI agent guidance
- [troubleshooting.md](troubleshooting.md) - Common problems

### External Resources
- **WordPress Codex:** https://codex.wordpress.org/
- **Block Editor Handbook:** https://developer.wordpress.org/block-editor/
- **REST API Handbook:** https://developer.wordpress.org/rest-api/
- **React Docs:** https://react.dev/
- **WP-CLI:** https://wp-cli.org/

### Community
- **GitHub Issues:** Report bugs, request features
- **Facebook Group:** https://www.facebook.com/groups/surecart
- **Support:** https://support.brainstormforce.com/

---

## Next Steps

After completing onboarding:

1. **Pick a starter issue:**
   - Look for "good first issue" label on GitHub
   - Start with documentation or small bug fixes
   - Work up to features

2. **Read code:**
   - Pick a feature you use
   - Trace it from UI to database
   - Understand every line

3. **Write tests:**
   - For code you touch
   - Increases confidence
   - Helps others understand your changes

4. **Ask questions:**
   - Don't hesitate to ask
   - Better to ask than assume
   - Document answers for others

**Welcome to the team!** 🚀
