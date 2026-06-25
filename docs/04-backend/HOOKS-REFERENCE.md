# SureForms Hooks Reference

> **Purpose:** Complete reference of all WordPress actions and filters for extending SureForms
> **Audience:** Plugin developers, theme developers, integrators
> **Last Updated:** 2026-02-06
> **Plugin Version:** 2.5.0

## Overview

SureForms provides **450+ hooks** (actions and filters) for extending functionality without modifying core files. This document catalogs the most commonly used hooks with usage examples.

**Hook Naming Convention:**
- **Prefix:** All hooks start with `srfm_`
- **Format:** `srfm_{context}_{action}` or `srfm_{data_type}_{modification}`
- **Examples:** `srfm_before_submission`, `srfm_field_validation`, `srfm_email_content`

---

## Table of Contents

- [Plugin Lifecycle](#plugin-lifecycle)
- [Form Submission](#form-submission)
- [Field Validation](#field-validation)
- [Email Notifications](#email-notifications)
- [Form Rendering](#form-rendering)
- [Data Modification](#data-modification)
- [Database Operations](#database-operations)
- [Payment Processing](#payment-processing)
- [Block Registration](#block-registration)
- [Admin Operations](#admin-operations)
- [Integration Points](#integration-points)

---

## Plugin Lifecycle

### `srfm_core_loaded`

Fires after the plugin core is fully loaded and initialized.

**Location:** [plugin-loader.php](../../plugin-loader.php)
**Since:** 0.0.1
**Parameters:** None

**Usage:**
```php
add_action('srfm_core_loaded', function() {
    // Plugin is ready, initialize custom extensions
    error_log('SureForms plugin loaded');

    // Register custom integrations
    my_custom_sureforms_integration();
});
```

### `srfm_init`

Fires during WordPress `init` hook after SureForms initialization.

**Location:** [plugin-loader.php](../../plugin-loader.php)
**Since:** 0.0.1
**Parameters:** None

**Usage:**
```php
add_action('srfm_init', function() {
    // Register custom post types, taxonomies, or other WordPress entities
    register_post_type('custom_form_data', [
        'public' => false,
        'supports' => ['title']
    ]);
});
```

---

## Form Submission

### `srfm_before_submission`

Fires immediately before form data is processed and saved to database. Use to validate or modify data before processing.

**Location:** [inc/form-submit.php:525](../../inc/form-submit.php)
**Since:** 0.0.1
**Parameters:**
- `array $form_data` - Submitted form data including form_id, fields, and metadata

**Usage:**
```php
add_action('srfm_before_submission', function($form_data) {
    $form_id = $form_data['form_id'] ?? 0;

    // Log submission attempt
    error_log(sprintf('Form %d submission started', $form_id));

    // Perform custom validation
    if ($form_id === 123) {
        $email = $form_data['email']['value'] ?? '';
        if (strpos($email, '@company.com') === false) {
            // Throw exception to halt submission
            throw new Exception('Only company emails allowed');
        }
    }
}, 10, 1);
```

### `srfm_after_submission_process`

Fires after form entry is saved to database but before email notifications are sent.

**Location:** [inc/form-submit.php:556](../../inc/form-submit.php)
**Since:** 0.0.1
**Parameters:**
- `array $form_data` - Complete form data including entry_id

**Usage:**
```php
add_action('srfm_after_submission_process', function($form_data) {
    $entry_id = $form_data['entry_id'] ?? 0;
    $form_id = $form_data['form_id'] ?? 0;

    // Trigger third-party integration
    if ($form_id === 123) {
        $email = $form_data['email']['value'] ?? '';
        $name = $form_data['name']['value'] ?? '';

        // Add to MailChimp
        mailchimp_add_subscriber($email, $name);

        // Send to CRM
        crm_create_lead([
            'email' => $email,
            'name' => $name,
            'source' => 'sureforms'
        ]);
    }
}, 10, 1);
```

### `srfm_form_submit`

Fires after complete form submission process (entry saved, emails sent).

**Location:** [inc/form-submit.php:548,644](../../inc/form-submit.php)
**Since:** 0.0.1
**Parameters:**
- `array $response` - Submission response with entry_id, success status, message

**Usage:**
```php
add_action('srfm_form_submit', function($response) {
    if ($response['success']) {
        $entry_id = $response['entry_id'];

        // Custom logging
        error_log(sprintf('Form submission %d completed', $entry_id));

        // Update custom database
        global $wpdb;
        $wpdb->insert('wp_custom_leads', [
            'sureforms_entry_id' => $entry_id,
            'created_at' => current_time('mysql')
        ]);
    }
}, 10, 1);
```

---

## Field Validation

### `srfm_field_validation`

Filters field validation result. Return `WP_Error` to fail validation.

**Location:** [inc/field-validation.php](../../inc/field-validation.php)
**Since:** 0.0.1
**Parameters:**
- `bool|WP_Error $is_valid` - Current validation status
- `mixed $field_value` - Field value to validate
- `array $field_data` - Field metadata (type, label, required, etc.)
- `int $form_id` - Form post ID

**Return:** `bool|WP_Error` - True if valid, WP_Error if invalid

**Usage:**
```php
add_filter('srfm_field_validation', function($is_valid, $field_value, $field_data, $form_id) {
    // Custom validation for specific field type
    if ($field_data['type'] === 'text' && $field_data['slug'] === 'phone') {
        // Validate phone format
        if (!preg_match('/^\+?[1-9]\d{1,14}$/', $field_value)) {
            return new WP_Error(
                'invalid_phone',
                __('Please enter a valid phone number', 'your-plugin')
            );
        }
    }

    // Custom validation for specific form
    if ($form_id === 123 && $field_data['slug'] === 'age') {
        if (intval($field_value) < 18) {
            return new WP_Error(
                'age_restriction',
                __('You must be 18 or older', 'your-plugin')
            );
        }
    }

    return $is_valid;
}, 10, 4);
```

### `srfm_validate_{field_type}`

Filters validation for specific field type.

**Location:** [inc/field-validation.php](../../inc/field-validation.php)
**Since:** Various
**Available Types:** `email`, `phone`, `url`, `number`, `text`, etc.

**Usage:**
```php
// Custom email validation
add_filter('srfm_validate_email', function($is_valid, $value, $field_data) {
    // Block disposable email domains
    $disposable_domains = ['tempmail.com', 'throwaway.email'];
    $domain = substr(strrchr($value, '@'), 1);

    if (in_array($domain, $disposable_domains)) {
        return new WP_Error(
            'disposable_email',
            __('Disposable email addresses are not allowed', 'your-plugin')
        );
    }

    return $is_valid;
}, 10, 3);
```

---

## Email Notifications

### `srfm_email_notification_should_send`

Filters whether email notification should be sent.

**Location:** [inc/form-submit.php:796](../../inc/form-submit.php)
**Since:** Various
**Parameters:**
- `array $email_notification` - Email configuration
- `array $submission_data` - Submitted form data
- `array $form_data` - Complete form metadata

**Return:** `array|false` - Email config to send, false to skip

**Usage:**
```php
add_filter('srfm_email_notification_should_send', function($email_notification, $submission_data, $form_data) {
    $form_id = $form_data['form_id'] ?? 0;

    // Only send email for specific form if user opted in
    if ($form_id === 123) {
        $opted_in = $submission_data['email_optin']['value'] ?? false;
        if (!$opted_in) {
            return false; // Skip email
        }
    }

    return $email_notification;
}, 10, 3);
```

### `srfm_email_notification`

Filters parsed email data before sending.

**Location:** [inc/form-submit.php:809](../../inc/form-submit.php)
**Since:** Various
**Parameters:**
- `array $parsed` - Parsed email data (to, subject, message, headers)
- `array $submission_data` - Submitted form data
- `array $item` - Email notification configuration
- `array $form_data` - Form metadata

**Return:** `array` - Modified email data

**Usage:**
```php
add_filter('srfm_email_notification', function($parsed, $submission_data, $item, $form_data) {
    // Add custom header
    $parsed['headers'][] = 'X-Form-ID: ' . ($form_data['form_id'] ?? 0);

    // Modify subject based on submission data
    if (isset($submission_data['priority']['value']) && $submission_data['priority']['value'] === 'high') {
        $parsed['subject'] = '[URGENT] ' . $parsed['subject'];
    }

    // Add custom footer to message
    $parsed['message'] .= "\n\n---\nSubmitted via SureForms";

    return $parsed;
}, 10, 4);
```

### `srfm_before_email_send`

Action fired immediately before email is sent via `wp_mail()`.

**Location:** [inc/form-submit.php:812](../../inc/form-submit.php)
**Since:** Various
**Parameters:**
- `array $parsed` - Email data (to, subject, message, headers)
- `array $submission_data` - Submitted form data
- `array $item` - Email notification configuration
- `array $form_data` - Form metadata

**Usage:**
```php
add_action('srfm_before_email_send', function($parsed, $submission_data, $item, $form_data) {
    // Log email send attempt
    error_log(sprintf(
        'Sending email to %s for form %d',
        $parsed['to'],
        $form_data['form_id'] ?? 0
    ));

    // Save email to custom table for tracking
    global $wpdb;
    $wpdb->insert('wp_email_log', [
        'recipient' => $parsed['to'],
        'subject' => $parsed['subject'],
        'sent_at' => current_time('mysql')
    ]);
}, 10, 4);
```

### `srfm_after_email_send`

Action fired after email is sent.

**Location:** [inc/form-submit.php:904-905](../../inc/form-submit.php)
**Since:** Various
**Parameters:**
- `array $parsed` - Email data
- `array $submission_data` - Form data
- `array $item` - Email config
- `array $form_data` - Form metadata
- `bool $sent` - Whether email was sent successfully

**Usage:**
```php
add_action('srfm_after_email_send', function($parsed, $submission_data, $item, $form_data, $sent) {
    if (!$sent) {
        // Email failed to send
        error_log(sprintf(
            'Email failed for form %d to %s',
            $form_data['form_id'] ?? 0,
            $parsed['to']
        ));

        // Send alert to admin
        wp_mail(
            get_option('admin_email'),
            'Form email notification failed',
            sprintf('Failed to send notification for form %d', $form_data['form_id'])
        );
    }
}, 10, 5);
```

---

## Data Modification

### `srfm_form_submit_data`

Filters complete form submission data before processing.

**Location:** [inc/form-submit.php:474](../../inc/form-submit.php)
**Since:** 0.0.1
**Parameters:**
- `array $form_data` - Complete form data

**Return:** `array` - Modified form data

**Usage:**
```php
add_filter('srfm_form_submit_data', function($form_data) {
    // Add custom calculated field
    if (isset($form_data['price'], $form_data['quantity'])) {
        $form_data['total'] = [
            'value' => $form_data['price']['value'] * $form_data['quantity']['value'],
            'label' => 'Total Amount',
            'type' => 'calculated'
        ];
    }

    // Add timestamp
    $form_data['submitted_timestamp'] = [
        'value' => current_time('mysql'),
        'label' => 'Submission Time',
        'type' => 'hidden'
    ];

    return $form_data;
}, 10, 1);
```

### `srfm_before_entry_data`

Filters entry data before saving to database.

**Location:** [inc/form-submit.php:605-606](../../inc/form-submit.php)
**Since:** 0.0.1
**Parameters:**
- `array $entries_data` - Entry data to be saved
- `int $form_id` - Form post ID

**Return:** `array` - Modified entry data

**Usage:**
```php
add_filter('srfm_before_entry_data', function($entries_data, $form_id) {
    // Add custom extras data
    $entries_data['extras'] = [
        'ip_country' => get_user_ip_country(),
        'utm_source' => $_GET['utm_source'] ?? '',
        'utm_campaign' => $_GET['utm_campaign'] ?? '',
        'referrer' => wp_get_referer()
    ];

    // Modify status based on conditions
    if ($form_id === 123) {
        $entries_data['status'] = 'pending_review'; // Custom status
    }

    return $entries_data;
}, 10, 2);
```

### `srfm_form_submission_response`

Filters final submission response sent to frontend.

**Location:** [inc/form-submit.php:660](../../inc/form-submit.php)
**Since:** 2.4.0
**Parameters:**
- `array $response` - Response data (success, message, entry_id)
- `array $form_data` - Form data
- `array $submission_data` - Submission data

**Return:** `array` - Modified response

**Usage:**
```php
add_filter('srfm_form_submission_response', function($response, $form_data, $submission_data) {
    // Add custom data to response
    if ($response['success']) {
        $response['custom_redirect_url'] = 'https://example.com/thank-you';
        $response['tracking_code'] = 'FB-' . $response['entry_id'];

        // Add custom message
        $response['custom_message'] = sprintf(
            'Thank you! Your reference number is %d',
            $response['entry_id']
        );
    }

    return $response;
}, 10, 3);
```

### `srfm_process_field_value`

Filters individual field value during processing.

**Location:** [inc/form-submit.php:1211-1212](../../inc/form-submit.php)
**Since:** 0.0.1
**Parameters:**
- `mixed $value` - Field value
- `array $field` - Field data
- `array $form_data` - Complete form data

**Return:** `mixed` - Modified field value

**Usage:**
```php
add_filter('srfm_process_field_value', function($value, $field, $form_data) {
    // Normalize phone numbers
    if ($field['type'] === 'phone') {
        $value = preg_replace('/[^0-9+]/', '', $value);
    }

    // Convert text to uppercase for specific field
    if ($field['slug'] === 'reference_code') {
        $value = strtoupper($value);
    }

    // Encrypt sensitive data
    if ($field['slug'] === 'ssn') {
        $value = encrypt_data($value);
    }

    return $value;
}, 10, 3);
```

---

## Form Rendering

### `srfm_form_markup`

Filters complete form HTML before rendering.

**Location:** [inc/generate-form-markup.php](../../inc/generate-form-markup.php)
**Since:** 0.0.1
**Parameters:**
- `string $markup` - Generated HTML
- `int $form_id` - Form post ID
- `array $blocks` - Gutenberg blocks array

**Return:** `string` - Modified HTML

**Usage:**
```php
add_filter('srfm_form_markup', function($markup, $form_id, $blocks) {
    // Add custom wrapper
    $markup = '<div class="custom-form-wrapper">' . $markup . '</div>';

    // Add custom analytics tracking
    $markup .= sprintf(
        '<script>trackFormView(%d);</script>',
        $form_id
    );

    return $markup;
}, 10, 3);
```

### `srfm_field_markup`

Filters individual field HTML.

**Location:** [inc/fields/base.php](../../inc/fields/base.php)
**Since:** 0.0.1
**Parameters:**
- `string $markup` - Field HTML
- `array $attributes` - Block attributes
- `string $field_type` - Field type (input, email, etc.)

**Return:** `string` - Modified HTML

**Usage:**
```php
add_filter('srfm_field_markup', function($markup, $attributes, $field_type) {
    // Add custom class to all email fields
    if ($field_type === 'email') {
        $markup = str_replace(
            'class="srfm-field',
            'class="srfm-field custom-email-field',
            $markup
        );
    }

    // Add tooltips
    if (!empty($attributes['help_text'])) {
        $markup .= sprintf(
            '<span class="tooltip">%s</span>',
            esc_html($attributes['help_text'])
        );
    }

    return $markup;
}, 10, 3);
```

### `srfm_form_class`

Filters CSS classes applied to form element.

**Location:** [inc/generate-form-markup.php](../../inc/generate-form-markup.php)
**Since:** 0.0.1
**Parameters:**
- `array $classes` - CSS class array
- `int $form_id` - Form post ID

**Return:** `array` - Modified classes

**Usage:**
```php
add_filter('srfm_form_class', function($classes, $form_id) {
    // Add custom class
    $classes[] = 'custom-form';

    // Add class based on form ID
    $classes[] = 'form-' . $form_id;

    // Add class based on form type
    $form_type = get_post_meta($form_id, '_srfm_form_type', true);
    if ($form_type === 'contact') {
        $classes[] = 'contact-form';
    }

    return $classes;
}, 10, 2);
```

---

## Database Operations

### `srfm_before_delete_entry`

Action fired before entry is permanently deleted from database.

**Location:** [inc/database/tables/entries.php:297](../../inc/database/tables/entries.php)
**Since:** 0.0.13
**Parameters:**
- `int $entry_id` - Entry ID being deleted

**Usage:**
```php
add_action('srfm_before_delete_entry', function($entry_id) {
    // Log deletion
    error_log(sprintf('Deleting entry %d', $entry_id));

    // Clean up related data
    global $wpdb;
    $wpdb->delete('wp_custom_entry_meta', ['entry_id' => $entry_id]);

    // Archive entry before deletion
    $entry = SRFM\Inc\Database\Tables\Entries::get($entry_id);
    if ($entry) {
        $wpdb->insert('wp_deleted_entries_archive', [
            'entry_id' => $entry_id,
            'form_data' => wp_json_encode($entry),
            'deleted_at' => current_time('mysql')
        ]);
    }
}, 10, 1);
```

---

## Payment Processing

### `srfm_before_payment_process`

Action fired before payment processing starts.

**Location:** [inc/payments/front-end.php](../../inc/payments/front-end.php)
**Since:** 2.0.0
**Parameters:**
- `array $payment_data` - Payment data
- `int $form_id` - Form post ID

**Usage:**
```php
add_action('srfm_before_payment_process', function($payment_data, $form_id) {
    // Log payment attempt
    error_log(sprintf(
        'Processing payment of %s %s for form %d',
        $payment_data['amount'],
        $payment_data['currency'],
        $form_id
    ));

    // Verify inventory before charging
    if ($form_id === 123) {
        $product_id = $payment_data['product_id'] ?? 0;
        if (!check_inventory($product_id)) {
            throw new Exception('Product out of stock');
        }
    }
}, 10, 2);
```

### `srfm_after_payment_success`

Action fired after successful payment.

**Location:** [inc/payments/front-end.php](../../inc/payments/front-end.php)
**Since:** 2.0.0
**Parameters:**
- `int $payment_id` - Payment record ID
- `array $payment_data` - Payment data
- `int $entry_id` - Entry ID

**Usage:**
```php
add_action('srfm_after_payment_success', function($payment_id, $payment_data, $entry_id) {
    // Send receipt
    $customer_email = $payment_data['customer_email'];
    wp_mail(
        $customer_email,
        'Payment Receipt',
        sprintf('Payment of %s received', $payment_data['total_amount'])
    );

    // Update inventory
    update_inventory($payment_data['product_id'], -1);

    // Trigger fulfillment
    trigger_order_fulfillment($payment_id);
}, 10, 3);
```

---

## Block Registration

### `srfm_block_registration_args`

Filters block registration arguments.

**Location:** [inc/blocks/register.php](../../inc/blocks/register.php)
**Since:** 0.0.1
**Parameters:**
- `array $args` - Block registration args
- `string $block_name` - Block name (sureforms/input, etc.)

**Return:** `array` - Modified args

**Usage:**
```php
add_filter('srfm_block_registration_args', function($args, $block_name) {
    // Modify render callback
    if ($block_name === 'sureforms/input') {
        $original_callback = $args['render_callback'];
        $args['render_callback'] = function($attributes, $content) use ($original_callback) {
            // Wrap output in custom div
            $output = call_user_func($original_callback, $attributes, $content);
            return '<div class="custom-wrapper">' . $output . '</div>';
        };
    }

    return $args;
}, 10, 2);
```

---

## Admin Operations

### `srfm_after_form_created`

Action fired after new form is created.

**Location:** [inc/create-new-form.php](../../inc/create-new-form.php)
**Since:** 0.0.1
**Parameters:**
- `int $form_id` - Created form post ID

**Usage:**
```php
add_action('srfm_after_form_created', function($form_id) {
    // Set default form settings
    update_post_meta($form_id, '_srfm_custom_setting', 'default_value');

    // Log form creation
    error_log(sprintf('New form created: %d', $form_id));

    // Notify admin
    wp_mail(
        get_option('admin_email'),
        'New form created',
        sprintf('Form ID %d was created', $form_id)
    );
}, 10, 1);
```

---

## Form Restrictions

### `srfm_form_restriction_message`

Filters restriction message shown to users.

**Location:** [inc/form-submit.php:267](../../inc/form-submit.php)
**Since:** Various
**Parameters:**
- `string $message` - Default restriction message
- `int $form_id` - Form post ID
- `array $form_restriction` - Restriction settings

**Return:** `string` - Modified message

**Usage:**
```php
add_filter('srfm_form_restriction_message', function($message, $form_id, $form_restriction) {
    // Custom message based on restriction type
    if ($form_restriction['type'] === 'schedule' && $form_restriction['state'] === 'not_started') {
        $message = sprintf(
            'This form will open on %s',
            date('F j, Y', strtotime($form_restriction['start_date']))
        );
    }

    return $message;
}, 10, 3);
```

### `srfm_additional_restriction_check`

Filters whether form has additional restrictions beyond scheduling.

**Location:** [inc/form-submit.php:276](../../inc/form-submit.php)
**Since:** Various
**Parameters:**
- `bool $is_restricted` - Whether form is restricted
- `int $form_id` - Form post ID
- `array $form_data` - Form data

**Return:** `bool` - True to restrict, false to allow

**Usage:**
```php
add_filter('srfm_additional_restriction_check', function($is_restricted, $form_id, $form_data) {
    // Restrict based on user role
    if ($form_id === 123 && !current_user_can('subscriber')) {
        return true; // Restrict
    }

    // Restrict based on IP
    $user_ip = $_SERVER['REMOTE_ADDR'];
    if (is_ip_blocked($user_ip)) {
        return true;
    }

    // Restrict based on previous submissions
    $user_email = $form_data['email']['value'] ?? '';
    if ($user_email && has_recent_submission($form_id, $user_email)) {
        return true; // Already submitted recently
    }

    return $is_restricted;
}, 10, 3);
```

---

## Integration Points

### Third-Party Service Integration Example

```php
/**
 * Complete integration example: Send form data to CRM after submission
 */
class SureForms_CRM_Integration {
    public function __construct() {
        add_action('srfm_after_submission_process', [$this, 'send_to_crm'], 10, 1);
        add_action('srfm_before_delete_entry', [$this, 'delete_from_crm'], 10, 1);
    }

    public function send_to_crm($form_data) {
        // Only for contact forms
        if ($form_data['form_id'] !== 123) {
            return;
        }

        $crm_data = [
            'email' => $form_data['email']['value'] ?? '',
            'name' => $form_data['name']['value'] ?? '',
            'phone' => $form_data['phone']['value'] ?? '',
            'company' => $form_data['company']['value'] ?? '',
            'source' => 'sureforms',
            'entry_id' => $form_data['entry_id'] ?? 0
        ];

        $response = wp_remote_post('https://crm.example.com/api/leads', [
            'body' => json_encode($crm_data),
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . get_option('crm_api_key')
            ]
        ]);

        if (is_wp_error($response)) {
            error_log('CRM sync failed: ' . $response->get_error_message());
        } else {
            $crm_id = json_decode(wp_remote_retrieve_body($response))->id;
            // Store CRM ID with entry
            update_post_meta($form_data['entry_id'], '_crm_lead_id', $crm_id);
        }
    }

    public function delete_from_crm($entry_id) {
        $crm_id = get_post_meta($entry_id, '_crm_lead_id', true);
        if ($crm_id) {
            wp_remote_request('https://crm.example.com/api/leads/' . $crm_id, [
                'method' => 'DELETE',
                'headers' => [
                    'Authorization' => 'Bearer ' . get_option('crm_api_key')
                ]
            ]);
        }
    }
}

new SureForms_CRM_Integration();
```

---

## Hook Priority Guidelines

**Priority Values:**
- **5:** Execute before default behavior
- **10:** Default priority (most hooks)
- **15:** Execute after default behavior
- **20+:** Late execution
- **100+:** Very late (cleanup, logging)

**Example:**
```php
// Execute before SureForms processes submission
add_action('srfm_before_submission', 'my_function', 5);

// Execute at default time
add_action('srfm_before_submission', 'my_function', 10);

// Execute after SureForms processes submission
add_action('srfm_before_submission', 'my_function', 15);
```

---

## Debugging Hooks

### Enable Hook Debugging

```php
// Add to wp-config.php
define('SRFM_DEBUG_HOOKS', true);

// Log all SureForms hooks
add_action('all', function($hook) {
    if (strpos($hook, 'srfm_') === 0) {
        error_log('Hook fired: ' . $hook);
    }
});
```

### View Hook Execution Order

```php
// Log hook priority and callback
add_action('all', function($hook) {
    if (strpos($hook, 'srfm_') === 0) {
        global $wp_filter;
        if (isset($wp_filter[$hook])) {
            foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    error_log(sprintf(
                        'Hook: %s, Priority: %d, Callback: %s',
                        $hook,
                        $priority,
                        is_array($callback['function']) ?
                            get_class($callback['function'][0]) . '::' . $callback['function'][1] :
                            $callback['function']
                    ));
                }
            }
        }
    }
});
```

---

## Best Practices

### 1. Always Check Hook Existence

```php
if (function_exists('add_action')) {
    add_action('srfm_after_submission_process', 'my_function');
}
```

### 2. Use Proper Nonces for Custom Forms

```php
add_filter('srfm_before_entry_data', function($data, $form_id) {
    // Verify custom nonce if adding custom fields
    if (isset($_POST['custom_nonce'])) {
        if (!wp_verify_nonce($_POST['custom_nonce'], 'custom_action')) {
            return new WP_Error('nonce_failed', 'Security check failed');
        }
    }
    return $data;
}, 10, 2);
```

### 3. Return Correct Data Types

```php
// Filter must return array
add_filter('srfm_form_submit_data', function($data) {
    // ✅ GOOD - returns array
    return array_merge($data, ['custom' => 'value']);

    // ❌ BAD - returns nothing
    // (void return breaks functionality)
}, 10, 1);
```

### 4. Handle Errors Gracefully

```php
add_action('srfm_after_submission_process', function($form_data) {
    try {
        // Risky operation
        send_to_external_api($form_data);
    } catch (Exception $e) {
        error_log('External API failed: ' . $e->getMessage());
        // Don't throw - allow submission to continue
    }
}, 10, 1);
```

---

## Summary

**Hook Categories:**
- **Lifecycle:** 2 hooks (core_loaded, init)
- **Submission:** 10+ hooks (before, during, after)
- **Validation:** 5+ hooks (field-specific and general)
- **Email:** 5+ hooks (before, during, after send)
- **Rendering:** 10+ hooks (markup, classes, attributes)
- **Data:** 8+ hooks (modify, filter, transform)
- **Database:** 3+ hooks (before/after operations)
- **Payment:** 6+ hooks (process, success, failure)
- **Admin:** 5+ hooks (form creation, updates)

**Total:** 450+ hooks across the plugin

**Auto-Generation Note:** This document contains the most commonly used hooks. For a complete list, run the hook extraction script:
```bash
php scripts/extract-hooks.php > docs/COMPLETE-HOOKS-LIST.md
```

---

**Related Documentation:**
- [CLAUDE.md](../../CLAUDE.md) - AI agent guide
- [ARCHITECTURE.md](../02-architecture/ARCHITECTURE.md) - System design
- [REST-API.md](REST-API.md) - REST API endpoints
- [DATABASE-SCHEMA.md](DATABASE-SCHEMA.md) - Database tables
