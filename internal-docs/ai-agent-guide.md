# AI Agent Guide

**Version:** 2.5.0
**For:** AI Coding Agents & LLMs

---

## Overview

This guide helps AI agents understand SureForms codebase conventions, make safe changes, and avoid common pitfalls.

---

## Core Principles

1. **Security First:** Sanitize inputs, escape outputs, use prepared statements
2. **WordPress Standards:** Follow WP coding standards, use WP functions
3. **Backward Compatibility:** Don't break existing forms/entries
4. **Performance:** Avoid N+1 queries, cache when possible
5. **Testing:** Write tests, run existing tests before committing

---

## File Modification Guidelines

### ✅ Safe to Modify

**Add features (low risk):**
- New blocks: `inc/blocks/new-block/`
- New integrations (Pro): `inc/pro/native-integrations/integrations/new-service/`
- New validation rules: Add to `inc/field-validation.php`
- New hooks: Add filters/actions (don't remove existing)

**Extend existing:**
- Add sanitization functions to `inc/helper.php`
- Add validation methods to `inc/field-validation.php`
- Add utility functions to helper classes

### ⚠️ Modify with Caution

**Database schema changes:**
- Requires migration script
- Must handle existing data
- Version increments in `inc/database/tables/*.php`

**Payment processing:**
- `inc/payments/stripe/stripe-webhook.php`
- `inc/business/payments/pay-pal/webhook-listener.php`
- **Risk:** Financial impact if broken

**Form submission pipeline:**
- `inc/form-submit.php`
- **Risk:** Could break all forms

### 🚫 High Risk - Avoid Unless Necessary

**Core WordPress integration:**
- `plugin-loader.php`
- `sureforms.php` / `sureforms-pro.php`

**Database base class:**
- `inc/database/base.php` (inherited by all tables)

**Critical security:**
- Nonce verification logic
- Capability checks
- Encryption (Pro): `inc/pro/native-integrations/encryption.php`

---

## Common Patterns

### Adding a New Field Type

1. **Create block:**
   ```
   inc/blocks/my-field/block.php
   src/blocks/my-field/
   ```

2. **Register block:**
   ```php
   // inc/blocks/my-field/block.php
   namespace SRFM\Inc\Blocks\My_Field;

   class Block extends \SRFM\Inc\Blocks\Base {
       public static function register() {
           register_block_type(
               'sureforms/my-field',
               [
                   'render_callback' => [__CLASS__, 'render'],
                   'attributes' => [/* schema */],
               ]
           );
       }

       public static function render($attributes) {
           // Return HTML
       }
   }
   ```

3. **Add to block registry:**
   ```php
   // inc/blocks/register.php
   My_Field\Block::register();
   ```

4. **Add sanitization:**
   ```php
   // inc/helper.php
   public static function sanitize_by_field_type($type, $value) {
       switch ($type) {
           case 'my-field':
               return sanitize_text_field($value);
       }
   }
   ```

### Adding a REST Endpoint

```php
// inc/rest-api.php or new file
public function register_routes() {
    register_rest_route(
        'sureforms/v1',
        '/my-endpoint',
        [
            'methods' => 'POST',
            'callback' => [$this, 'handle_request'],
            'permission_callback' => [$this, 'check_permissions'],
        ]
    );
}

public function check_permissions() {
    // CRITICAL: Always verify permissions
    return current_user_can('manage_options');
}

public function handle_request($request) {
    // CRITICAL: Always verify nonce
    $nonce = $request->get_header('X-WP-Nonce');
    if (!wp_verify_nonce($nonce, 'wp_rest')) {
        return new \WP_Error('invalid_nonce', 'Invalid nonce', ['status' => 403]);
    }

    // Sanitize inputs
    $data = $request->get_params();
    $clean_data = Helper::sanitize_array_recursively($data);

    // Process...

    return ['success' => true];
}
```

### Adding an Integration (Pro)

1. **Create integration folder:**
   ```
   inc/pro/native-integrations/integrations/myservice/
   ├── config.json
   └── actions/
       └── send-data.php
   ```

2. **config.json:**
   ```json
   {
       "name": "MyService",
       "slug": "myservice",
       "auth_method": "api_key",
       "api_endpoint": "https://api.myservice.com/v1/",
       "actions": [
           {
               "name": "Send Data",
               "slug": "send-data",
               "endpoint": "contacts"
           }
       ]
   }
   ```

3. **actions/send-data.php:**
   ```php
   namespace SRFM_PRO\Inc\Pro\Native_Integrations\Integrations\Myservice\Actions;

   class Send_Data {
       public function execute($integration_data, $form_data) {
           $api_key = $integration_data['api_key'];
           $response = wp_remote_post(
               'https://api.myservice.com/v1/contacts',
               [
                   'headers' => ['Authorization' => 'Bearer ' . $api_key],
                   'body' => json_encode($form_data),
               ]
           );

           if (is_wp_error($response)) {
               return ['success' => false, 'error' => $response->get_error_message()];
           }

           return ['success' => true, 'response' => wp_remote_retrieve_body($response)];
       }
   }
   ```

---

## Security Patterns

### Input Sanitization

```php
// Text
$name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );

// Email
$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

// Number
$id = absint( $_POST['id'] ?? 0 );

// Array (recursive)
$data = Helper::sanitize_array_recursively( $_POST['data'] ?? [] );

// Textarea
$message = Helper::sanitize_textarea( wp_unslash( $_POST['message'] ?? '' ) );
```

### Output Escaping

```php
// HTML content
echo esc_html( $user_name );

// Attributes
echo '<div class="' . esc_attr( $class ) . '">';

// URLs
echo '<a href="' . esc_url( $url ) . '">';

// Rich text (allows safe HTML)
echo wp_kses_post( $content );
```

### Database Queries

```php
global $wpdb;

// ✅ Good - Prepared statement
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sureforms_entries WHERE form_id = %d AND status = %s",
        $form_id,
        $status
    )
);

// ❌ Bad - SQL injection risk
$results = $wpdb->query("DELETE FROM {$wpdb->prefix}sureforms_entries WHERE id = $_POST[id]");
```

---

## Testing Your Changes

### Before Committing

```bash
# 1. Lint PHP
composer lint

# 2. Run PHP tests
composer test

# 3. Lint JavaScript
npm run lint-js

# 4. Run JS tests
npm run test:unit

# 5. Build assets
npm run build

# 6. Test in browser
# - Create form
# - Submit form
# - Check entries
# - Verify no console errors
```

### E2E Testing

```bash
# Start environment
npm run play:up

# Run tests
npm run play:run

# Interactive mode (with browser)
npm run play:run:interactive
```

---

## Database Queries - Common Patterns

### Get Entries

```php
use SRFM\Inc\Database\Tables\Entries;

// Get all for form
$entries = Entries::get_all([
    'where' => [
        ['key' => 'form_id', 'value' => 123, 'compare' => '=']
    ],
    'orderby' => 'created_at',
    'order' => 'DESC',
]);

// With pagination
$entries = Entries::get_all([
    'where' => [['key' => 'form_id', 'value' => 123, 'compare' => '=']],
    'limit' => 20,
    'offset' => 40  // Page 3 (20 per page)
]);

// Count entries
$count = Entries::get_count([
    'where' => [['key' => 'status', 'value' => 'published', 'compare' => '=']]
]);
```

### Get Payments

```php
use SRFM\Inc\Database\Tables\Payments;

// Get completed payments
$payments = Payments::get_all([
    'where' => [
        ['key' => 'status', 'value' => 'completed', 'compare' => '=']
    ]
]);

// Get payment by transaction ID
$payment = Payments::get_by_transaction_id('ch_xxxxx', 'stripe');
```

---

## Hooks - When to Use

### Modify Data Before Save

```php
// Filter entry data before save
add_filter('sureforms_submit_form_data', function($data, $form_id) {
    // Add custom field
    $data['custom_timestamp'] = current_time('mysql');
    return $data;
}, 10, 2);
```

### Trigger External Action

```php
// Action after entry save
add_action('sureforms_after_entry_save', function($entry_id, $form_id, $data) {
    // Send to external CRM
    send_to_crm($data);
}, 10, 3);
```

### Modify Email

```php
// Filter email template
add_filter('sureforms_email_template', function($html, $entry_id) {
    // Add custom header
    return '<div class="custom-header">...</div>' . $html;
}, 10, 2);
```

---

## Debugging Tips

### Enable Debug Mode

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check Debug Log

```bash
tail -f wp-content/debug.log
```

### JavaScript Console

```javascript
// Check for errors
// Open browser DevTools → Console

// SureForms global
console.log(window.sureforms);
```

### Database Queries

```php
// Install Query Monitor plugin
// Shows all queries, slow queries, errors
```

---

## Common Pitfalls to Avoid

1. **Don't bypass sanitization:** "It's internal, it's safe" → Still sanitize
2. **Don't skip nonce checks:** Every AJAX/REST endpoint needs verification
3. **Don't use `$_POST` directly:** Always sanitize first
4. **Don't concatenate SQL:** Use `$wpdb->prepare()`
5. **Don't echo user data raw:** Use `esc_html()`, `esc_attr()`, etc.
6. **Don't modify database schema without migration:** Breaks existing sites
7. **Don't remove existing hooks:** Other plugins may depend on them
8. **Don't hardcode `wp_` prefix:** Use `$wpdb->prefix`

---

## Getting Help

**Documentation:**
- This folder: All internal docs
- WordPress Codex: https://codex.wordpress.org/
- React Docs: https://react.dev/

**Code Search:**
- Use Glob/Grep tools to find patterns
- Example: `grep -r "sanitize_text_field" inc/` → See how sanitization is used

**Ask Before:**
- Breaking changes
- Database schema changes
- Removing existing functionality

---

**Next:** [Troubleshooting](troubleshooting.md) - Common problems and solutions
