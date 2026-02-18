# WordPress Hooks Reference

SureForms exposes custom actions and filters for extensibility, and hooks into WordPress core lifecycle events.

## Custom Actions

| Action | Parameters | Description |
|--------|-----------|-------------|
| `srfm_core_loaded` | -- | Fired after Plugin_Loader singleton is created |
| `srfm_before_submission` | `$form_data`, `$form_id` | Before form submission is processed |
| `srfm_form_submit` | `$entry_id`, `$form_id`, `$form_data` | After form submission is complete |
| `srfm_after_submission_process` | `$entry_id`, `$form_id` | After all post-submission processing |
| `srfm_before_delete_entry` | `$entry_id` | Before an entry is permanently deleted |

## Custom Filters

### REST API

| Filter | Parameters | Description |
|--------|-----------|-------------|
| `srfm_rest_api_endpoints` | `$endpoints` | Add/modify REST API endpoints |

### Form Data

| Filter | Parameters | Description |
|--------|-----------|-------------|
| `srfm_form_submit_data` | `$form_data`, `$form_id` | Modify form data before processing |
| `srfm_entry_value` | `$value`, `$args` | Transform entry field values in responses |
| `srfm_normalize_csv_field_value` | `$value`, `$field` | Custom CSV export value formatting |
| `srfm_form_confirmation_params` | `$params`, `$form_id` | Modify confirmation settings |

### Post Meta

| Filter | Parameters | Description |
|--------|-----------|-------------|
| `srfm_register_post_meta` | `$meta_keys` | Register additional form post meta keys |

### Blocks

| Filter | Parameters | Description |
|--------|-----------|-------------|
| `srfm_is_special_block` | `$is_special`, `$block_name` | Declare a block as special (nested) |
| `srfm_handle_special_block` | `$result`, `$block` | Custom processing for special blocks |
| `srfm_extract_form_fields_field_name` | `$field_name`, `$block` | Modify generated field names |

### Settings

| Filter | Parameters | Description |
|--------|-----------|-------------|
| `srfm_global_settings_data` | `$settings` | Extend global settings data |
| `srfm_disable_nps_survey` | `$disable` | Disable NPS survey module |
| `srfm_enable_redirect_activation` | `$enable` | Control activation redirect behavior |

## WordPress Core Hooks Used

### plugins_loaded

| Priority | Callback | Class |
|----------|----------|-------|
| default | `load_textdomain()` | Plugin_Loader |
| 99 | `load_plugin()` | Plugin_Loader |

### init

| Priority | Callback | Purpose |
|----------|----------|---------|
| 10 | `load_classes()` | Block registration, admin, payments |

### rest_api_init

| Callback | Class | Purpose |
|----------|-------|---------|
| `register_endpoints()` | Rest_Api | Register all admin REST endpoints |
| `register_routes()` | Form_Submit | Register public submission endpoints |
| `register_rest_routes()` | Payments_Settings | Register payment REST routes |

### admin_init

| Callback | Purpose |
|----------|---------|
| `activation_redirect()` | Redirect to onboarding after activation |
| `intercept_stripe_callback()` | Handle Stripe OAuth callback |

### wp_enqueue_scripts

| Callback | Class | Purpose |
|----------|-------|---------|
| Asset enqueue | Frontend_Assets | Load form CSS/JS on pages with forms |

### enqueue_block_editor_assets

| Callback | Class | Purpose |
|----------|-------|---------|
| Editor asset enqueue | Gutenberg_Hooks | Load block editor JS/CSS |

## Hook Execution Order (Form Submission)

```
1. REST API receives POST /submit-form
2. Form_Submit::submit_form_permissions_check()
   -> Nonce verification
3. Form_Submit::handle_form_submission()
   -> apply_filters('srfm_form_submit_data', $data, $form_id)
   -> Field validation
   -> CAPTCHA verification
   -> Honeypot check
4. do_action('srfm_before_submission', $data, $form_id)
5. Email notification sent
6. Entry stored in database
7. do_action('srfm_form_submit', $entry_id, $form_id, $data)
8. do_action('srfm_after_submission_process', $entry_id, $form_id)
9. Confirmation response returned
```

## Usage Examples

### Adding a Custom REST Endpoint

```php
add_filter( 'srfm_rest_api_endpoints', function( $endpoints ) {
    $endpoints['my-custom/data'] = [
        'methods'             => 'GET',
        'callback'            => 'my_custom_callback',
        'permission_callback' => [ \SRFM\Inc\Helper::class, 'get_items_permissions_check' ],
    ];
    return $endpoints;
} );
```

### Modifying Form Data Before Processing

```php
add_filter( 'srfm_form_submit_data', function( $data, $form_id ) {
    // Add custom processing
    $data['custom_field'] = 'custom_value';
    return $data;
}, 10, 2 );
```

### Running Code After Submission

```php
add_action( 'srfm_form_submit', function( $entry_id, $form_id, $form_data ) {
    // Send to external service, trigger automation, etc.
}, 10, 3 );
```

## Related Pages

- [Architecture Overview](Architecture-Overview) -- Plugin bootstrap and hook registration
- [REST API Reference](REST-API-Reference) -- Full endpoint documentation
- [Form Submission Flow](Form-Submission-Flow) -- Submission hook execution order
