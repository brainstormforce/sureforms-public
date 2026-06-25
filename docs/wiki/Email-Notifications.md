# Email Notifications

SureForms sends email notifications on form submission and supports scheduled email summary digests.

## Email System Overview

```
Form Submission
  -> Form_Submit::handle_form_submission()
    -> Email_Template (inc/email/email-template.php)
      -> Smart_Tags resolution
      -> wp_mail() dispatch
```

## Email Template

The `Email_Template` class (`inc/email/email-template.php`) handles:

- HTML email template rendering
- Dynamic content insertion via smart tags
- Multiple notification recipients
- Customizable subject lines and body content
- Reply-to address configuration

### Per-Form Email Settings

Email notifications are configured per-form via the `_srfm_email_notification` post meta key. Each form can have:

- **To** -- Recipient email addresses (supports multiple, comma-separated)
- **Subject** -- Email subject line (supports smart tags)
- **Body** -- Email content (supports smart tags and HTML)
- **Reply-To** -- Reply-to email address
- **CC/BCC** -- Additional recipients

## Smart Tags

The `Smart_Tags` class (`inc/smart-tags.php`) provides template variables that are resolved at email send time:

| Smart Tag | Description |
|-----------|-------------|
| `{admin_email}` | WordPress admin email |
| `{site_title}` | WordPress site title |
| `{site_url}` | WordPress site URL |
| `{form_title}` | Name of the submitted form |
| `{form_id}` | ID of the submitted form |
| `{entry_id}` | ID of the created entry |
| `{all_fields}` | All submitted field values formatted |
| `{field:field_name}` | Specific field value by field name |
| `{user_ip}` | Submitter's IP address |
| `{user_agent}` | Submitter's browser user agent |
| `{submission_date}` | Date of submission |

Smart tags are processed by replacing the tag placeholders with actual values from the form submission data.

## Email Summary

The `Email_Summary` class (`inc/global-settings/email-summary.php`) provides scheduled digest reports:

- Configurable frequency (daily, weekly, monthly)
- Summary of form submissions over the period
- Entry counts per form
- Sent to configured admin email addresses
- Uses WordPress cron (`Events_Scheduler`) for scheduling

## Customization

### Modifying Email Content

Email content is filtered through smart tag processing, allowing dynamic content:

```php
// In per-form settings, the email body supports:
// - Smart tags: {form_title}, {all_fields}, etc.
// - HTML formatting
// - Custom text
```

### Extending Smart Tags

Smart tags can be extended by hooking into the tag resolution process to add custom replacements.

## Related Pages

- [Form Submission Flow](Form-Submission-Flow) -- When emails are triggered
- [WordPress Hooks Reference](WordPress-Hooks-Reference) -- Email-related hooks
- [Architecture Overview](Architecture-Overview) -- Email_Template initialization
