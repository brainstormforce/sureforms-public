# Glossary

**Version:** 2.5.0

---

## A

### AJAX (Asynchronous JavaScript and XML)
Technique for updating parts of a page without full reload. Used in SureForms for form submissions, payment processing, and file uploads.

**Example:**
```javascript
// AJAX request to submit form
wp.ajax.post('srfm_submit_form', { data: formData });
```

### API (Application Programming Interface)
Set of functions and methods for interacting with software. SureForms provides REST API, database API, and JavaScript API.

**Types in SureForms:**
- **REST API:** HTTP endpoints for external access
- **Database API:** Methods to query entries/payments
- **JavaScript API:** Frontend form manipulation

### Attribute (Gutenberg)
Data stored with a block. In SureForms, attributes define field configuration (label, required, placeholder, etc.).

**Example:**
```json
{
  "label": "Email",
  "required": true,
  "placeholder": "name@example.com"
}
```

---

## B

### Block (Gutenberg)
Reusable component in WordPress editor. SureForms forms are composed of blocks (Email block, Textarea block, Submit button block, etc.).

**Types:**
- **Static blocks:** Rendered client-side (React)
- **Dynamic blocks:** Rendered server-side (PHP)

### Block Editor
WordPress's editing interface (formerly called "Gutenberg"). Native to WordPress 5.0+.

**Why SureForms uses it:**
- Users already familiar
- No proprietary builder to learn
- Future-proof (WordPress core)

---

## C

### Capability
WordPress permission level. SureForms checks capabilities before allowing admin actions.

**Common capabilities:**
- `manage_options` - Admin settings access
- `edit_posts` - Create forms (default)
- `read` - View forms (logged-in users)

**Example:**
```php
if (current_user_can('manage_options')) {
  // Allow admin action
}
```

### Conditional Logic (Pro)
Show or hide form fields based on user answers. Example: Show "Dietary restrictions" only if user selects "Yes" to "Will you attend?"

**Types:**
- **Show when:** Display field if condition matches
- **Hide when:** Hide field if condition matches
- **AND/OR logic:** Multiple conditions combined

### Conversational Form (Pro)
Form that displays one question at a time, like a chat conversation. Increases engagement and completion rates.

**Benefits:**
- Less overwhelming than long forms
- Higher completion rate (3x vs single-page)
- Better mobile experience

### CSRF (Cross-Site Request Forgery)
Attack where malicious site tricks user into submitting request to your site. SureForms prevents with nonce verification.

**Example attack (prevented):**
```html
<!-- Malicious site -->
<form action="https://yoursite.com/wp-admin/admin-ajax.php" method="POST">
  <input name="action" value="srfm_delete_form">
  <input name="form_id" value="123">
</form>
<script>document.forms[0].submit();</script>
```

### CSV (Comma-Separated Values)
File format for spreadsheet data. SureForms can export form entries as CSV.

**Example:**
```
Name,Email,Message
John Doe,john@example.com,Hello!
Jane Smith,jane@example.com,Question about...
```

---

## D

### Database Table
MySQL table storing structured data. SureForms uses 4 custom tables:
- `wp_sureforms_entries` - Form submissions
- `wp_sureforms_payments` - Payment records
- `wp_sureforms_integrations` - OAuth credentials
- `wp_sureforms_save_resume` - Draft submissions (Pro)

**Why custom tables?**
- Faster queries (indexed columns)
- More efficient than post meta
- Easier to export

### Dynamic Block
Gutenberg block rendered server-side (PHP). Most SureForms field blocks are dynamic for security (server-side validation).

**Example:**
```php
register_block_type('sureforms/email', [
  'render_callback' => 'render_email_field'
]);
```

---

## E

### Encryption
Converting data to unreadable format. SureForms encrypts OAuth credentials and API keys.

**Algorithm:** AES-256-CTR

**Example:**
```php
$encrypted = Encryption::encrypt($api_key);
// Stored in database: "base64encodedIV.encryptedData"
```

### Entry
Single form submission. Stored in `wp_sureforms_entries` table.

**Contains:**
- Form ID
- User ID (if logged in)
- Field values (JSON)
- Metadata (IP, user agent, timestamp)
- Status (published, spam, trash)

### Escaping (Output Escaping)
Converting special characters to prevent XSS. SureForms escapes all user data before display.

**Functions:**
- `esc_html()` - For HTML content
- `esc_attr()` - For HTML attributes
- `esc_url()` - For URLs
- `esc_js()` - For JavaScript strings

**Example:**
```php
echo '<div>' . esc_html($user_input) . '</div>';
```

---

## F

### Field Type
Type of input field (email, text, number, etc.). SureForms has 15+ field types.

**Common types:**
- Text, Email, URL, Textarea
- Number, Phone
- Multiple Choice, Checkbox, Dropdown
- Address, Upload (Pro)

### Filter (WordPress Hook)
Function that modifies data. SureForms provides 30+ filters for customization.

**Example:**
```php
add_filter('sureforms_email_subject', function($subject, $form_id) {
  return "[Form $form_id] $subject";
}, 10, 2);
```

---

## G

### GDPR (General Data Protection Regulation)
European privacy law. SureForms provides GDPR-compliance features (consent checkbox, data export/deletion).

**Requirements:**
- Explicit consent for data collection
- Right to access data (export)
- Right to deletion
- Privacy policy link

### Gutenberg
WordPress's block editor (officially called "Block Editor"). Named after Johannes Gutenberg, inventor of printing press.

**Why "Gutenberg"?**
- Revolutionized content creation (like printing press)
- Blocks are reusable components

---

## H

### Honeypot
Anti-spam technique using hidden field. Bots fill all fields (including hidden), revealing themselves.

**How it works:**
```html
<input type="text" name="srfm-honeypot" style="display:none">
<!-- Bots fill this, real users don't -->
```

**SureForms logic:**
```php
if (!empty($_POST['srfm-honeypot'])) {
  // Spam detected
}
```

### Hook (WordPress)
Point where developers can inject custom code. Two types: actions and filters.

**SureForms hooks:**
- 50+ action hooks (e.g., `sureforms_after_entry_save`)
- 30+ filter hooks (e.g., `sureforms_email_template`)

---

## I

### Integration (Native Integration)
Connection to third-party service. SureForms Pro has 24+ native integrations (Mailchimp, HubSpot, etc.).

**Types:**
- **OAuth:** User authorizes connection (Mailchimp, Google)
- **API Key:** User provides key from service
- **Webhook:** SureForms sends POST request to URL

### Instant Form
SureForms feature allowing forms to be published with unique URL (no embedding needed).

**Example URL:**
```
https://yoursite.com/?srfm_form=abc123
```

**Use cases:**
- Email signatures
- Social media links
- QR codes

---

## J

### JSON (JavaScript Object Notation)
Data format for structured information. SureForms stores entry data and block attributes as JSON.

**Example entry data:**
```json
{
  "email": "john@example.com",
  "name": "John Doe",
  "message": "Hello!"
}
```

---

## L

### Localization (l10n)
Adapting software for specific language/region. SureForms supports translation.

**Files:**
- `languages/sureforms-{locale}.po` - Translation strings
- `languages/sureforms-{locale}.mo` - Compiled translations

**Functions:**
```php
__('Email', 'sureforms'); // Translate string
_e('Submit', 'sureforms'); // Translate and echo
```

---

## M

### Middleware
Intermediate server between client and API. SureForms uses middleware for AI form generation (client → middleware → OpenAI).

**Why middleware?**
- Hides API keys from client
- Rate limiting
- Request logging
- Caching

### Multi-Step Form (Pro)
Form split into multiple pages/steps. Improves completion rate by reducing cognitive load.

**Example:**
```
Step 1: Personal Information (name, email)
Step 2: Address Details
Step 3: Payment Information
```

---

## N

### Namespace
PHP feature to organize code and prevent name conflicts. SureForms uses namespaces.

**Example:**
```php
namespace SRFM\Inc\Database\Tables;

class Entries {
  // ...
}
```

### Native Integration
Integration built into SureForms (not requiring Zapier or external connector).

**Examples:**
- Mailchimp (OAuth)
- HubSpot (OAuth)
- Slack (Webhook URL)

### Nonce (Number Used Once)
Security token to verify request came from your site. SureForms verifies nonces on all AJAX/REST requests.

**Example:**
```php
// Generate nonce
$nonce = wp_create_nonce('srfm_submit_form');

// Verify nonce
if (!wp_verify_nonce($_POST['nonce'], 'srfm_submit_form')) {
  die('Invalid nonce');
}
```

---

## O

### OAuth (Open Authorization)
Protocol for secure authorization. Used by SureForms integrations (Mailchimp, HubSpot, etc.).

**Flow:**
1. User clicks "Connect to Mailchimp"
2. Redirected to Mailchimp login
3. User approves access
4. Mailchimp redirects back with token
5. SureForms stores encrypted token

---

## P

### Payment Gateway
Service that processes credit card payments. SureForms supports Stripe and PayPal.

**Comparison:**
- **Stripe:** 2.9% + $0.30, better for US/Europe
- **PayPal:** 2.9% + $0.30, more global recognition

### Payment Intent (Stripe)
Stripe object representing payment attempt. Created before customer enters card details.

**States:**
- `requires_payment_method` - Awaiting card
- `requires_confirmation` - Card entered, needs confirm
- `succeeded` - Payment successful
- `canceled` - Payment canceled

### PCI DSS (Payment Card Industry Data Security Standard)
Security standard for handling credit card data. SureForms is PCI-compliant (never stores card data).

**Compliance:**
- Card data sent directly to Stripe/PayPal
- SureForms only stores transaction ID
- No PAN (Primary Account Number) stored

### Placeholder
Example text shown in empty input field. Disappears when user types.

**Example:**
```html
<input type="email" placeholder="name@example.com">
```

**Accessibility note:** Never use placeholder as label (screen readers may miss it).

### Post Meta
WordPress method for storing additional data with posts. SureForms **doesn't** use post meta (uses custom tables instead).

**Why not post meta?**
- Slow queries (no indexes)
- Hard to search
- Not optimized for large datasets

### Post Type (Custom Post Type)
WordPress content type. SureForms forms are stored as custom post type `sureforms_form`.

**Advantages:**
- Uses WordPress admin UI
- Revision history
- Trash/restore functionality

---

## R

### reCAPTCHA
Google anti-spam service. SureForms supports reCAPTCHA v2 and v3.

**Versions:**
- **v2:** "I'm not a robot" checkbox
- **v3:** Invisible, scores user behavior (0-1)

**Setup:**
1. Get API keys from Google reCAPTCHA admin
2. Add to SureForms → Settings → Security
3. Enable on forms

### REST API
HTTP API for accessing SureForms data. Used by frontend, mobile apps, integrations.

**Base URL:** `/wp-json/sureforms/v1/`

**Example endpoints:**
- `POST /submit-form` - Submit form
- `GET /forms/{id}` - Get form data
- `POST /generate-form` - AI form generation

### RTL (Right-to-Left)
Text direction for languages like Arabic, Hebrew. SureForms supports RTL.

**CSS:**
```css
.srfm-field {
  margin-inline-start: 10px; /* Not margin-left */
}
```

---

## S

### Sanitization (Input Sanitization)
Cleaning user input to prevent attacks. SureForms sanitizes all input before storage.

**Functions:**
- `sanitize_text_field()` - Remove HTML/PHP
- `sanitize_email()` - Validate email format
- `sanitize_url()` - Validate URL format
- `absint()` - Force integer

**Example:**
```php
$email = sanitize_email($_POST['email']);
$id = absint($_POST['id']);
```

### Schema (Database Schema)
Structure of database table (columns, types, indexes).

**Example (wp_sureforms_entries):**
```sql
CREATE TABLE wp_sureforms_entries (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  form_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED,
  entry_data LONGTEXT NOT NULL,
  status VARCHAR(20) DEFAULT 'published',
  created_at DATETIME NOT NULL,
  INDEX idx_form_id (form_id),
  INDEX idx_created_at (created_at)
);
```

### Shortcode
WordPress feature for embedding content. SureForms forms can be embedded via shortcode.

**Syntax:**
```
[sureforms id="123"]
```

**Parameters:**
- `id` - Form ID (required)
- `title` - Show title (true/false)
- `description` - Show description (true/false)

### SQL Injection
Attack injecting malicious SQL into queries. SureForms prevents with `$wpdb->prepare()`.

**Vulnerable code (DON'T DO THIS):**
```php
$id = $_GET['id'];
$wpdb->query("DELETE FROM table WHERE id = $id");
// Attack: ?id=1 OR 1=1 (deletes all records!)
```

**Safe code:**
```php
$id = absint($_GET['id']);
$wpdb->query($wpdb->prepare("DELETE FROM table WHERE id = %d", $id));
```

### Subscription
Recurring payment. SureForms supports subscriptions via Stripe and PayPal.

**Intervals:**
- Daily
- Weekly
- Monthly
- Yearly

---

## T

### Template (Form Template)
Pre-built form configuration. SureForms provides 100+ templates (contact, registration, survey, etc.).

**Structure:**
```json
{
  "name": "Contact Form",
  "fields": [
    { "type": "text", "label": "Name" },
    { "type": "email", "label": "Email" },
    { "type": "textarea", "label": "Message" }
  ]
}
```

### Transient
WordPress temporary cache. SureForms uses transients for AI rate limiting and API responses.

**Example:**
```php
// Store for 1 hour
set_transient('srfm_ai_rate_limit_' . $user_id, true, HOUR_IN_SECONDS);

// Check
if (get_transient('srfm_ai_rate_limit_' . $user_id)) {
  // Rate limited
}
```

---

## U

### User Agent
String identifying browser/device. SureForms logs user agent for spam detection.

**Example:**
```
Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36
```

---

## V

### Validation (Form Validation)
Checking if user input meets requirements. SureForms validates both client-side (JavaScript) and server-side (PHP).

**Types:**
- **Required:** Field must not be empty
- **Format:** Email/URL must be valid
- **Range:** Number within min/max
- **Length:** String length constraints

**Example:**
```php
if (empty($email) || !is_email($email)) {
  $errors[] = 'Please enter a valid email';
}
```

---

## W

### Webhook
HTTP callback sent when event occurs. SureForms can send webhooks on form submission (Pro).

**Flow:**
```
User submits form
  ↓
SureForms processes submission
  ↓
Sends POST to webhook URL
  ↓
External service receives data
```

**Example payload:**
```json
{
  "event": "form_submit",
  "form_id": 123,
  "entry_id": 456,
  "fields": { ... }
}
```

### WordPress Coding Standards (WPCS)
PHP coding style guide for WordPress. SureForms follows WPCS.

**Key rules:**
- Tabs for indentation (not spaces)
- Yoda conditions: `if ( 'value' === $variable )`
- Braces required for all control structures
- Space after control keywords: `if (` not `if(`

**Check compliance:**
```bash
composer lint
```

---

## X

### XSS (Cross-Site Scripting)
Attack injecting malicious JavaScript into pages. SureForms prevents with output escaping.

**Vulnerable code (DON'T DO THIS):**
```php
echo '<div>' . $_POST['name'] . '</div>';
// Attack: <script>steal(document.cookie)</script>
```

**Safe code:**
```php
echo '<div>' . esc_html($_POST['name']) . '</div>';
// Output: &lt;script&gt;steal(document.cookie)&lt;/script&gt;
```

---

## Z

### Zapier
Third-party automation service. SureForms doesn't have native Zapier integration but supports webhooks (which Zapier can consume).

**Alternative:** Use SureForms webhooks to trigger Zapier zaps.

---

## Acronyms Quick Reference

| Acronym | Full Term | Meaning |
|---------|-----------|---------|
| AJAX | Asynchronous JavaScript and XML | Update page without reload |
| API | Application Programming Interface | Methods to interact with software |
| CSRF | Cross-Site Request Forgery | Attack tricking users into unwanted actions |
| CSV | Comma-Separated Values | Spreadsheet file format |
| GDPR | General Data Protection Regulation | EU privacy law |
| JSON | JavaScript Object Notation | Data format |
| OAuth | Open Authorization | Secure authorization protocol |
| PCI DSS | Payment Card Industry Data Security Standard | Credit card security |
| REST | Representational State Transfer | Web API architecture |
| RTL | Right-to-Left | Text direction (Arabic, Hebrew) |
| SQL | Structured Query Language | Database query language |
| URL | Uniform Resource Locator | Web address |
| WCAG | Web Content Accessibility Guidelines | Accessibility standards |
| WPCS | WordPress Coding Standards | PHP coding style |
| XSS | Cross-Site Scripting | JavaScript injection attack |

---

## Common Abbreviations

| Abbr | Full Term |
|------|-----------|
| Pro | SureForms Pro (premium plugin) |
| CRM | Customer Relationship Management |
| UI | User Interface |
| UX | User Experience |
| CPT | Custom Post Type |
| DB | Database |
| WP | WordPress |
| PHP | PHP: Hypertext Preprocessor |
| JS | JavaScript |
| CSS | Cascading Style Sheets |

---

**Next:** [Maintenance](maintenance.md)
