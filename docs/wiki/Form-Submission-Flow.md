# Form Submission Flow

This page documents the end-to-end form submission process from frontend to entry storage.

## Flow Diagram

```
+------------------+     +-------------------+     +------------------+
|  User fills form |---->| JavaScript fetch() |---->| REST API         |
|  in browser      |     | POST /submit-form  |     | Form_Submit      |
+------------------+     +-------------------+     +--------+---------+
                                                           |
                          +--------------------------------+
                          |
               +----------v-----------+
               | Permission Check     |
               | - X-WP-Submit-Nonce  |
               | - Form data exists   |
               +----------+-----------+
                          |
               +----------v-----------+
               | Form Restrictions    |
               | - Max entries check  |
               | - Date scheduling   |
               | - Login required     |
               +----------+-----------+
                          |
               +----------v-----------+
               | Field Validation     |
               | - Required fields   |
               | - Type validation   |
               | - Unique values     |
               +----------+-----------+
                          |
               +----------v-----------+
               | Security Checks     |
               | - CAPTCHA verify    |
               | - Honeypot check    |
               +----------+-----------+
                          |
               +----------v-----------+
               | Data Processing     |
               | - Sanitize fields   |
               | - Smart tag resolve |
               | - Collect metadata  |
               +----------+-----------+
                          |
         +----------------+----------------+
         |                                 |
+--------v--------+              +--------v--------+
| Email Notify    |              | Entry Storage   |
| Email_Template  |              | wp_srfm_entries |
+-----------------+              +--------+--------+
                                          |
                                 +--------v--------+
                                 | Payment Process |
                                 | (if applicable) |
                                 | wp_srfm_payments|
                                 +--------+--------+
                                          |
                                 +--------v--------+
                                 | Confirmation    |
                                 | Response        |
                                 +-----------------+
```

## Step-by-Step Process

### 1. Frontend Submission

The form is submitted via JavaScript `fetch()` to `POST /wp-json/sureforms/v1/submit-form` with:

- Form field data as POST body
- `X-WP-Submit-Nonce` header for security (does not require logged-in user)
- Form ID to identify which form was submitted

Nonces can be refreshed via `GET /refresh-nonces` (public, no auth required).

### 2. Permission Check

`Form_Submit::submit_form_permissions_check()`:

- Verifies the `X-WP-Submit-Nonce` header
- Validates that form data and form ID are present
- Returns `WP_Error` on failure

### 3. Form Restrictions

`Form_Restriction` class checks:

- **Maximum entries** -- Has the form reached its submission limit?
- **Date scheduling** -- Is the form within its active date range?
- **Login required** -- Does the form require a logged-in user?
- **Entry caps** -- Per-user submission limits

### 4. Field Validation

`Field_Validation` (`inc/field-validation.php`):

- **Required fields** -- Ensures all required fields have non-empty values
- **Type validation** -- Email format, URL format, number ranges, phone format
- **Unique values** -- Checks existing entries for uniqueness if configured
- Returns validation errors that prevent submission

### 5. Security Verification

- **CAPTCHA** -- Validates reCAPTCHA v2/v3, hCaptcha, or Cloudflare Turnstile token
- **Honeypot** -- Checks the hidden honeypot field is empty (bots fill it)

### 6. Data Processing

- `apply_filters('srfm_form_submit_data', $data, $form_id)` -- allows modification
- Sanitizes each field value
- Resolves smart tags for email templates
- Collects submission metadata (IP, browser via Browser class, device info)
- `do_action('srfm_before_submission', $data, $form_id)`

### 7. Email Notifications

`Email_Template` sends configured email notifications:

- Processes smart tags in subject and body
- Sends to configured recipients
- Handles CC/BCC
- See [Email Notifications](Email-Notifications) for details

### 8. Entry Storage

Checks GDPR compliance (`do_not_store_entries` setting). If storage is allowed:

```php
Entries::add([
    'form_id'         => $form_id,
    'user_id'         => get_current_user_id(),
    'form_data'       => $sanitized_data,     // JSON-encoded
    'submission_info' => $metadata,            // JSON-encoded
    'logs'            => $activity_logs,       // JSON-encoded
    'status'          => 'unread',
    'type'            => $form_type,           // 'standard', 'quiz', etc.
]);
```

### 9. Payment Processing

If the form includes a payment block:

- Stripe payment is processed
- Payment record created in `wp_srfm_payments`
- Payment ID linked to the entry
- See [Payment Integration](Payment-Integration) for details

### 10. Post-Submission

- `do_action('srfm_form_submit', $entry_id, $form_id, $form_data)`
- `do_action('srfm_after_submission_process', $entry_id, $form_id)`
- Returns confirmation response:
  - **Message** -- Display a success message
  - **Redirect** -- Redirect to a specified URL

## Form Confirmation Types

Configured via `_srfm_form_confirmation` post meta:

| Type | Behavior |
|------|----------|
| Message | Display a success message on the same page |
| Redirect | Redirect to a specified URL after submission |

Confirmation parameters are filterable via `srfm_form_confirmation_params`.

## Error Handling

Errors at any stage return a JSON error response to the frontend:

```json
{
  "success": false,
  "data": "Validation error message"
}
```

The frontend JavaScript displays errors inline next to the relevant fields.

## Related Pages

- [REST API Reference](REST-API-Reference) -- Submit endpoint details
- [Database Schema](Database-Schema) -- Entry and payment table structure
- [Email Notifications](Email-Notifications) -- Email dispatch details
- [Payment Integration](Payment-Integration) -- Payment processing
- [WordPress Hooks Reference](WordPress-Hooks-Reference) -- Submission hooks
- [Form Fields Architecture](Form-Fields-Architecture) -- Field validation
