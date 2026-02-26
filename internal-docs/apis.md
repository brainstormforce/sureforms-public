# APIs

Complete API reference for SureForms Free & Pro.

---

## REST API Endpoints

### Free (`/sureforms/v1/`)

**Form Management:**
```http
POST   /sureforms/v1/generate-form
GET    /sureforms/v1/forms
GET    /sureforms/v1/forms/{id}
DELETE /sureforms/v1/forms/{id}
```

**Form Submission:**
```http
POST   /sureforms/v1/submit-form
```
**Body:** `{ form_id, nonce, field_data... }`
**Response:** `{ success, message, entry_id }`

**Entries:**
```http
GET    /sureforms/v1/entries/list
GET    /sureforms/v1/entries/{id}
DELETE /sureforms/v1/entries/{id}
POST   /sureforms/v1/entries/export
```

**Payments:**
```http
GET    /sureforms/v1/payments/list
GET    /sureforms/v1/payments/{id}
```

**Stripe Webhooks:**
```http
POST   /sureforms/webhook_test        # Stripe webhooks
POST   /sureforms/webhook_live
```

**Settings:**
```http
GET    /sureforms/v1/settings
POST   /sureforms/v1/settings/update
```

### Pro (`/sureforms-pro/v1/`)

**OAuth:**
```http
POST   /sureforms-pro/v1/oauth/authorize
POST   /sureforms-pro/v1/oauth/callback
```

**PayPal Webhooks:**
```http
POST   /sureforms-pro/paypal-test-webhook
POST   /sureforms-pro/paypal-live-webhook
```

**User Registration:**
```http
POST   /sureforms-pro/v1/login
POST   /sureforms-pro/v1/lost-password
POST   /sureforms-pro/v1/reset-password
```

---

## AJAX Handlers

### Free (30 handlers)

**Admin:**
```php
'srfm_admin_action'                  # Generic admin handler
'srfm_get_forms'                     # Fetch forms list
'srfm_delete_form'                   # Delete form
'srfm_duplicate_form'                # Duplicate form
'srfm_export_entries'                # Export CSV
```

**Payments (Public):**
```php
'wp_ajax_srfm_create_payment_intent'         # Stripe payment
'wp_ajax_nopriv_srfm_create_payment_intent'
'wp_ajax_srfm_create_subscription_intent'    # Stripe subscription
'wp_ajax_nopriv_srfm_create_subscription_intent'
```

**Admin Stripe:**
```php
'srfm_stripe_cancel_subscription'    # Cancel subscription
'srfm_stripe_pause_subscription'     # Pause subscription
```

**Admin Payments:**
```php
'srfm_create_payment_refund'         # Create refund
```

**Form Validation (Public):**
```php
'wp_ajax_srfm_field_unique_validation'       # Check unique fields
'wp_ajax_nopriv_srfm_field_unique_validation'
```

### Pro (22 handlers)

**PayPal (Public):**
```php
'wp_ajax_srfm_pro_create_paypal_order'
'wp_ajax_nopriv_srfm_pro_create_paypal_order'
'wp_ajax_srfm_pro_create_paypal_subscription'
'wp_ajax_nopriv_srfm_pro_create_paypal_subscription'
```

**PayPal Admin:**
```php
'srfm_pro_paypal_cancel_subscription'
'srfm_pro_paypal_pause_subscription'
```

**PDF:**
```php
'srfm_pro_generate_pdf'              # Generate PDF from entry
'srfm_pro_download_pdf'              # Download PDF
```

**Licensing:**
```php
'srfm_pro_activate_license'
'srfm_pro_deactivate_license'
```

**Entries:**
```php
'sureforms_pro_entry_delete_file'    # Delete uploaded file
```

---

## WordPress Hooks

### Actions (Form Submission)

```php
// Before entry save
do_action('sureforms_before_entry_save', $form_id, $entry_data);

// After entry save
do_action('sureforms_after_entry_save', $entry_id, $form_id, $entry_data);

// After payment completed
do_action('sureforms_payment_completed', $payment_id, $entry_id, $gateway);

// After integration success
do_action('sureforms_integration_success', $integration_type, $response);

// Before email send
do_action('sureforms_before_email_send', $to, $subject, $message);

// After form submission (success)
do_action('sureforms_form_submitted', $entry_id, $form_id);
```

### Filters (Data Modification)

```php
// Modify form data before save
$entry_data = apply_filters('sureforms_submit_form_data', $entry_data, $form_id);

// Modify payment amount
$amount = apply_filters('sureforms_payment_amount', $amount, $form_id, $entry_data);

// Modify email template
$html = apply_filters('sureforms_email_template', $html, $entry_id, $form_id);

// Modify email subject
$subject = apply_filters('sureforms_email_subject', $subject, $form_id);

// Modify email recipients
$to = apply_filters('sureforms_email_recipients', $to, $entry_id, $form_id);

// Modify confirmation message
$message = apply_filters('sureforms_confirmation_message', $message, $entry_id);

// Modify field validation
$is_valid = apply_filters('sureforms_validate_field', $is_valid, $field, $value);

// Modify sanitized value
$value = apply_filters("sureforms_sanitize_{$field_type}", $value, $field);
```

### Integration Hooks (Pro)

```php
// Before integration request
do_action('sureforms_pro_before_integration', $integration, $data);

// After integration request
do_action('sureforms_pro_after_integration', $integration, $response);

// Integration error
do_action('sureforms_pro_integration_error', $integration, $error);
```

---

## JavaScript APIs

### Global Objects

```javascript
// Form settings
window.sureforms = {
  ajaxUrl: '/wp-admin/admin-ajax.php',
  nonce: 'xxx',
  formId: 123,
  settings: {...}
};

// Block editor
window.sureformsBlocks = {
  registerBlock: (name, settings) => {...},
  getBlock: (name) => {...}
};
```

### Custom Events

```javascript
// Form submitted
document.addEventListener('sureforms-form-submitted', (e) => {
  console.log('Entry ID:', e.detail.entryId);
});

// Payment completed
document.addEventListener('sureforms-payment-completed', (e) => {
  console.log('Payment ID:', e.detail.paymentId);
});

// Validation error
document.addEventListener('sureforms-validation-error', (e) => {
  console.log('Field:', e.detail.field, 'Error:', e.detail.message);
});
```

---

## Database APIs

### Entry Operations

```php
use SRFM\Inc\Database\Tables\Entries;

// Get all entries for a form
$entries = Entries::get_all([
    'where' => [
        ['key' => 'form_id', 'value' => 123, 'compare' => '=']
    ],
    'orderby' => 'created_at',
    'order' => 'DESC',
    'limit' => 20,
    'offset' => 0
]);

// Get single entry
$entry = Entries::get_by_id(456);

// Insert entry
$entry_id = Entries::insert([
    'form_id' => 123,
    'entry_data' => json_encode($data),
    'status' => 'published'
]);

// Update entry
Entries::update(456, ['status' => 'trash']);

// Delete entry
Entries::delete(456);
```

### Payment Operations

```php
use SRFM\Inc\Database\Tables\Payments;

// Get payments
$payments = Payments::get_all_main_payments([
    'where' => [
        ['key' => 'status', 'value' => 'completed', 'compare' => '=']
    ]
]);

// Create payment
$payment_id = Payments::insert([
    'entry_id' => 456,
    'transaction_id' => 'ch_xxx',
    'gateway' => 'stripe',
    'status' => 'pending',
    'total_amount' => 99.99,
    'currency' => 'USD'
]);

// Update payment status
Payments::update($payment_id, ['status' => 'completed']);
```

---

## Helper APIs

### Sanitization

```php
use SRFM\Inc\Helper;

// Sanitize by field type
$clean = Helper::sanitize_by_field_type('email', $value);
$clean = Helper::sanitize_by_field_type('textarea', $value);
$clean = Helper::sanitize_by_field_type('number', $value);

// Email-specific
$email = Helper::sanitize_email_header($raw_email);

// Recursive sanitization
$clean_array = Helper::sanitize_array_recursively($array);
```

### Validation

```php
use SRFM\Inc\Field_Validation;

// Validate field
$result = Field_Validation::validate_field($field_config, $value);
// Returns: ['valid' => true/false, 'message' => 'Error message']

// Validate entire form
$result = Field_Validation::validate_form_data($form_id, $data);
```

### Payment Helpers

```php
use SRFM\Inc\Payments\Payment_Helper;

// Validate amount against form config
$result = Payment_Helper::validate_payment_amount($amount, $currency, $form_id, $block_id);

// Get currency
$currency = Payment_Helper::get_currency();
```

---

## Encryption API (Pro)

```php
use SRFM_PRO\Inc\Pro\Native_Integrations\Encryption;

$enc = new Encryption();

// Encrypt sensitive data
$encrypted = $enc->encrypt('oauth_token_12345');

// Decrypt
$decrypted = $enc->decrypt($encrypted);

// Storage: wp_sureforms_integrations.data column
```

**Algorithm:** AES-256-CTR
**Key:** `SRFM_ENCRYPTION_KEY` constant or `LOGGED_IN_KEY`

---

## Integration Workflow API (Pro)

```php
use SRFM_PRO\Inc\Pro\Native_Integrations\Services\Workflow_Processor;

// Execute integration workflow
$processor = new Workflow_Processor();
$result = $processor->execute_workflow($integration_config, $entry_data);

// Returns: ['success' => true/false, 'response' => API response]
```

---

## Rate Limiting

**Note:** No built-in rate limiting. Implementations should add:

```php
// Example pattern (not in core)
$key = 'form_submit_' . $user_ip;
$attempts = get_transient($key) ?: 0;

if ($attempts >= 5) {
    wp_send_json_error('Too many submissions', 429);
}

set_transient($key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
```

---

**Next:** [Coding Standards](coding-standards.md) - PHP/JS conventions
