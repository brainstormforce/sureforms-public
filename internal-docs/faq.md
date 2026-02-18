# Frequently Asked Questions

**Version:** 2.5.0

---

## General Questions

### What is SureForms?

SureForms is an AI-powered WordPress form builder that uses Gutenberg blocks.

**Key features:**
- AI form generation (describe in plain language, get complete form)
- Built-in payments (Stripe in Free, PayPal in Pro)
- Mobile-first design
- Native WordPress (no proprietary builder)
- Custom database tables (not post meta)

**Target users:** Website owners, designers, developers who need forms without complexity.

---

### What's the difference between Free and Pro?

**Free (sureforms):**
- Unlimited forms & submissions
- All 15+ field types
- Stripe payments (one-time & subscriptions)
- Email notifications
- Spam protection (reCAPTCHA, Honeypot)
- Form analytics
- CSV export

**Pro (sureforms-pro):**
- **All Free features, plus:**
- PayPal payments
- 24+ native integrations (Mailchimp, HubSpot, Salesforce, etc.)
- Conditional logic
- Multi-step & conversational forms
- User registration & login
- PDF generation
- File upload fields
- Priority support (< 24hr response)

**Pricing:**
- Free: $0 (WordPress.org)
- Pro: $99-$299/year (3 sites to unlimited)

---

### Can I use SureForms on multiple sites?

**Free:** Yes, unlimited sites.

**Pro:** Depends on license:
- Essential ($99/year): 3 sites
- Plus ($199/year): 20 sites
- Agency ($299/year): Unlimited sites

**License activation:**
- Enter license key in SureForms → Settings → License
- Deactivate from one site to move to another
- All sites must be owned by you (not for client sites unless Agency plan)

---

### Is SureForms GDPR compliant?

**Yes.** SureForms provides tools for GDPR compliance:

**Features:**
- GDPR checkbox field (explicit consent)
- Data export (users can request their data)
- Data deletion (users can request deletion)
- No third-party data sharing (unless you enable integrations)
- Privacy policy link support

**What you must do:**
1. Add GDPR checkbox to forms collecting personal data
2. Link to your privacy policy
3. Honor data export/deletion requests
4. Configure data retention policies

**Data storage:**
- Submissions stored in your WordPress database
- No data sent to SureForms servers (AI calls go through middleware but don't store data)
- Payment data stored encrypted (PCI-compliant)

---

### Does SureForms work with my theme?

**Yes.** SureForms is designed to work with any properly-coded WordPress theme.

**Tested with:**
- Astra
- GeneratePress
- Kadence
- Neve
- Blocksy
- Twenty Twenty-Three/Four
- Page builder themes (Elementor, Divi, Beaver Builder)

**If you experience styling conflicts:**
1. Check [troubleshooting guide](troubleshooting.md#form-breaks-after-theme-update)
2. Use SureForms → Settings → Custom CSS to override
3. Contact support with theme name

---

### Can I use SureForms with page builders?

**Yes.** SureForms works with:
- **Gutenberg** (native, best experience)
- **Elementor** (via shortcode or WordPress widget)
- **Divi** (via shortcode or code module)
- **Beaver Builder** (via WordPress widget)
- **Bricks** (via shortcode or WordPress element)

**How to add:**

**Elementor:**
1. Add "Shortcode" widget
2. Paste: `[sureforms id="123"]`
3. Replace 123 with your form ID

**Divi:**
1. Add "Code" module
2. Paste shortcode
3. Save

**Beaver Builder:**
1. Add "WordPress Widget" module
2. Select "SureForms" widget
3. Choose form

---

## Technical Questions

### What are the system requirements?

**Minimum:**
- WordPress 6.4+
- PHP 7.4+
- MySQL 5.6+ or MariaDB 10.1+
- HTTPS (for payments)

**Recommended:**
- WordPress 6.6+
- PHP 8.1+
- MySQL 8.0+ or MariaDB 10.6+
- 256MB PHP memory limit
- Object caching (Redis or Memcached)

**Browser support:**
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

---

### Does SureForms slow down my site?

**No.** SureForms is optimized for performance:

**Impact on page load:**
- Additional CSS: ~15KB (minified)
- Additional JS: ~25KB (minified, deferred)
- Total: ~40KB additional assets

**Database queries:**
- Form render: 2-3 queries (cached)
- Form submission: 4-5 queries (optimized with indexes)
- No N+1 query issues

**Performance features:**
- Lazy loading for non-critical assets
- Custom database tables (not post meta)
- Query optimization (indexed columns)
- Minified & concatenated assets
- CDN-friendly (versioned static files)

**Benchmarks:**
- Lighthouse score: 95+ (with proper hosting)
- Time to Interactive: < 2 seconds on 3G
- Form submission latency: < 500ms

---

### Can I export my data?

**Yes.** Multiple export options:

**Entries export:**
1. Go to SureForms → Entries
2. Select entries (or "Select All")
3. Click "Export to CSV"
4. Download file

**Format:** CSV with all fields as columns

**Form export:**
- Export form as JSON (Settings → Export)
- Import on another site (Settings → Import)

**Database export:**
```bash
# Export all SureForms data
wp db export sureforms-backup.sql --tables=wp_sureforms_entries,wp_sureforms_payments,wp_sureforms_integrations
```

---

### How do I migrate forms to another site?

**Method 1: Export/Import (Recommended)**

**On old site:**
1. Go to SureForms → Forms
2. Hover over form → "Export"
3. Download JSON file

**On new site:**
1. Install SureForms (same version)
2. Go to SureForms → Import
3. Upload JSON file
4. Map any dependencies (integrations, etc.)

**Method 2: Database migration**

```bash
# On old site
wp db export sureforms-data.sql --tables=wp_sureforms_entries,wp_sureforms_payments

# Transfer file to new site

# On new site
wp db import sureforms-data.sql

# Update URLs in entries (if domain changed)
wp search-replace 'https://oldsite.com' 'https://newsite.com' wp_sureforms_entries
```

---

### Can I use SureForms offline / localhost?

**Yes, with limitations:**

**Works offline:**
- Form creation
- Form editing
- Local submissions (saved to database)

**Requires internet:**
- AI form generation (external API)
- Payment processing (Stripe/PayPal API)
- Native integrations (Mailchimp, HubSpot, etc.)
- Google Fonts (if used)
- reCAPTCHA (if enabled)

**Development setup:**
```bash
# Use Local by Flywheel or similar
# Or manual setup:
cd ~/Sites/my-wordpress-site
wp core download
wp core config --dbname=sureforms_dev --dbuser=root
wp core install --url=http://localhost:8080 --title="Dev Site"
wp plugin install sureforms --activate
```

---

## Payment Questions

### Which payment gateways are supported?

**Free plugin:**
- Stripe (one-time payments & subscriptions)

**Pro plugin:**
- Stripe (one-time payments & subscriptions)
- PayPal (one-time payments & subscriptions)

**Coming soon:**
- Razorpay (India)
- Mollie (Europe)

**Not planned:**
- WooCommerce (use WooCommerce for full e-commerce)
- Square (low demand)
- Bitcoin/crypto (regulatory complexity)

---

### How do I test payments before going live?

**Stripe test mode:**

1. Get test API keys:
   - Go to https://dashboard.stripe.com/test/apikeys
   - Copy "Publishable key" (pk_test_...)
   - Copy "Secret key" (sk_test_...)

2. In SureForms:
   - Go to Settings → Payments → Stripe
   - Enable "Test Mode"
   - Paste test keys
   - Save

3. Test with card:
   - Card number: `4242 4242 4242 4242`
   - Expiry: Any future date (e.g., 12/28)
   - CVC: Any 3 digits (e.g., 123)

4. Verify in Stripe dashboard:
   - Go to Payments → Test Mode
   - See your test payment

**PayPal sandbox (Pro):**

1. Create sandbox account:
   - Go to https://developer.paypal.com/
   - Sign in with PayPal account
   - Create sandbox business & buyer accounts

2. Get sandbox credentials:
   - Apps & Credentials → Sandbox
   - Copy Client ID & Secret

3. In SureForms:
   - Settings → Payments → PayPal
   - Enable "Sandbox Mode"
   - Paste credentials
   - Save

4. Test with sandbox buyer account

---

### How are payment fees handled?

**SureForms fees:** $0 (we charge nothing)

**Gateway fees:**

**Stripe:**
- Standard: 2.9% + $0.30 per transaction
- International cards: +1.5%
- Currency conversion: +1%
- (Varies by country, see Stripe pricing)

**PayPal:**
- Standard: 2.9% + $0.30 per transaction
- International: +1.5%
- (Varies by country, see PayPal pricing)

**Who pays fees:**
- Fees deducted from amount received
- Example: $100 charge → You receive ~$97

**Passing fees to customer:**
- Not built-in (would require payment gateway approval)
- Workaround: Add fee as separate amount field

---

### Can I accept subscriptions?

**Yes** (both Stripe and PayPal).

**Setup:**

1. Create form with Payment block
2. Payment block settings:
   - Payment Type: ● Subscription
   - Interval: Monthly/Yearly/Weekly
   - Amount: [price]

3. Customer pays once, charged automatically recurring

**Management:**

**Stripe:**
- View subscriptions: Stripe Dashboard → Subscriptions
- Cancel: Click subscription → "Cancel subscription"
- Refund: Not automatic, manual refund in Stripe

**PayPal (Pro):**
- View subscriptions: PayPal Dashboard → Subscriptions
- Cancel: Click subscription → "Cancel"

**Customer cancellation:**
- Customers can cancel in Stripe/PayPal customer portal
- You can provide link in confirmation email

---

## AI & Form Generation Questions

### How does AI form generation work?

**Technical flow:**

```
User enters prompt ("job application form")
  ↓
SureForms sends to AI middleware
  ↓
Middleware calls GPT-4 API
  ↓
GPT-4 returns structured JSON (field types, labels, validations)
  ↓
Middleware sends back to SureForms
  ↓
SureForms creates Gutenberg blocks from JSON
  ↓
Form appears in editor, ready to customize
```

**What data is sent:**
- Your prompt ("create a contact form")
- Form context (if modifying existing form)
- No user data, no submissions, no PII

**What data is stored:**
- None. AI responses are not logged.
- Form configuration stored in WordPress database only

**Privacy:**
- AI calls routed through SureForms middleware (not direct to OpenAI)
- No data retention
- No training on your data

---

### Can I use AI without internet?

**No.** AI requires external API call to GPT-4.

**Offline alternatives:**
- Create forms manually (no AI)
- Use templates (no AI needed)
- Import pre-made forms

---

### What languages does AI support?

**Prompts:** Any language GPT-4 supports (100+ languages)

**Field labels:** Generated in same language as prompt

**Example:**
- Prompt (Spanish): "formulario de contacto"
- Result: Fields with Spanish labels ("Nombre", "Correo electrónico", etc.)

**Limitations:**
- Some languages better than others (English, Spanish, French, German excellent)
- Less common languages may have lower quality

---

## Integration Questions (Pro)

### What integrations are available?

**24+ native integrations:**

**Email Marketing:**
- Mailchimp
- Brevo (Sendinblue)
- ActiveCampaign
- ConvertKit
- MailerLite

**CRM:**
- HubSpot
- Salesforce
- Zoho CRM
- Pipedrive

**Communication:**
- Slack
- Telegram
- Discord (coming soon)

**Productivity:**
- Google Sheets
- Airtable (coming soon)
- Notion (coming soon)

**WordPress Plugins:**
- FluentCRM (native)
- OttoKit (SureTriggers)

**Webhooks:**
- Custom webhooks (POST to any URL)

**Not included:**
- Zapier (use webhooks instead)
- Make/Integromat (use webhooks)

---

### How do I connect Mailchimp?

**Setup (OAuth):**

1. Go to SureForms → Settings → Integrations
2. Click "Mailchimp" → "Connect"
3. Redirected to Mailchimp → "Allow access"
4. Redirected back, connection confirmed

**Use in form:**

1. Edit form
2. Settings → Actions → "Add Action"
3. Select "Mailchimp"
4. Choose audience (list)
5. Map fields:
   - Email field → Mailchimp EMAIL
   - Name field → Mailchimp FNAME
6. Save

**On form submission:**
- Contact added to Mailchimp list automatically
- Tags applied (if configured)
- Double opt-in email sent (if enabled in Mailchimp)

---

### Can I send form data to custom webhook?

**Yes** (Pro feature).

**Setup:**

1. Form settings → Actions → "Add Action"
2. Select "Webhook"
3. Configure:
   - URL: `https://yourapi.com/endpoint`
   - Method: POST (or GET, PUT)
   - Headers: (optional, e.g., `Authorization: Bearer token`)
4. Save

**Payload format:**

```json
{
  "form_id": 123,
  "entry_id": 456,
  "fields": {
    "email": "user@example.com",
    "name": "John Doe",
    "message": "Hello!"
  },
  "meta": {
    "submitted_at": "2026-02-12 10:30:00",
    "user_ip": "192.168.1.1",
    "user_agent": "Mozilla/5.0..."
  }
}
```

**Retry logic:**
- Failed webhooks retried 3 times
- Exponential backoff (1s, 5s, 25s)
- After 3 failures, logged as error

---

## Security & Privacy Questions

### Is SureForms secure?

**Yes.** Security is a core priority.

**Security measures:**

**Input sanitization:**
- All user input sanitized before storage
- WordPress functions: `sanitize_text_field()`, `sanitize_email()`, etc.
- Custom sanitization for complex fields

**Output escaping:**
- All output escaped before rendering
- `esc_html()`, `esc_attr()`, `esc_url()` used throughout
- Prevents XSS attacks

**SQL injection prevention:**
- All database queries use `$wpdb->prepare()`
- No direct SQL concatenation
- Custom database class with built-in protection

**CSRF protection:**
- Nonce verification on all AJAX/REST endpoints
- `wp_verify_nonce()` checked before state-changing operations

**Authorization:**
- Capability checks: `current_user_can()`
- Admin actions require `manage_options` capability
- User-specific data access controlled

**Payment security:**
- PCI DSS compliant (no card storage)
- Stripe/PayPal handle sensitive data
- API credentials encrypted at rest (AES-256)

**File uploads (Pro):**
- MIME type validation (server-side)
- File size limits enforced
- Path traversal prevention
- Disallowed file types: `.php`, `.js`, `.exe`, etc.

---

### Where is my data stored?

**Form submissions:**
- Your WordPress database
- Table: `wp_sureforms_entries`
- No data sent to SureForms servers

**Payment data:**
- Stripe: Stored in Stripe (not in WordPress)
- PayPal: Stored in PayPal (not in WordPress)
- WordPress stores: Transaction ID, amount, status only

**Integration credentials (Pro):**
- WordPress database
- Table: `wp_sureforms_integrations`
- Encrypted with AES-256
- Encryption key in `wp-config.php` (recommended)

**AI prompts:**
- Sent to AI middleware → OpenAI GPT-4
- Not stored or logged
- Not used for training

---

### Can users delete their data?

**Yes** (GDPR requirement).

**Manual deletion:**

1. User requests deletion via email
2. Admin finds submissions by email
3. Delete from SureForms → Entries

**Automatic (Pro):**
- Data retention policy: Auto-delete entries after X days
- Settings → Privacy → Data Retention
- Configure per form or globally

**What gets deleted:**
- Entry data (all fields)
- Entry metadata (IP, user agent)
- File uploads (if any)

**What's preserved:**
- Form configuration (for re-use)
- Analytics (anonymized counts only)

---

## Troubleshooting Questions

### My forms aren't showing on the frontend. Why?

**Common causes:**

**1. Form not published:**
- Check form status in SureForms → Forms
- Must be "Published" not "Draft"

**2. Form not embedded:**
- Add SureForms block to page
- Select your form from dropdown
- Or use shortcode: `[sureforms id="123"]`

**3. Theme conflict:**
- Try default theme: `wp theme activate twentytwentythree`
- If works → Theme issue, contact theme developer

**4. JavaScript disabled:**
- Check browser console for errors
- Disable other plugins to find conflict

**Debug:**
```bash
# Enable debugging
wp config set WP_DEBUG true --raw
wp config set WP_DEBUG_LOG true --raw

# Check debug log
tail -f wp-content/debug.log
```

See [troubleshooting guide](troubleshooting.md) for more.

---

### Form submissions aren't saving. What's wrong?

**Check:**

**1. Database tables exist:**
```bash
wp db query "SHOW TABLES LIKE '%sureforms%';"
```

Should show `wp_sureforms_entries` table.

**2. Database permissions:**
```bash
wp db query "SHOW GRANTS;"
```

User must have INSERT privilege.

**3. Nonce verification:**
- If using caching, exclude AJAX endpoints
- WP Super Cache: Exclude `/wp-admin/admin-ajax.php`

**4. Debug log:**
```bash
tail -f wp-content/debug.log
# Submit form, watch for errors
```

See [troubleshooting: submissions not saving](troubleshooting.md#form-submissions-not-saving)

---

### Email notifications aren't sending. Help!

**Diagnose:**

```bash
# Test WordPress mail function
wp eval "wp_mail('your@email.com', 'Test', 'Testing');"
```

If no email received → Server mail() disabled.

**Fix:**

**Install SMTP plugin:**
```bash
wp plugin install wp-mail-smtp --activate
```

**Configure with:**
- Gmail
- SendGrid
- Mailgun
- Amazon SES

**Check spam folder:**
- Default WordPress sender: `wordpress@yourdomain.com`
- Often flagged as spam
- SMTP fixes this

See [troubleshooting: emails not sending](troubleshooting.md#email-notifications-not-sending)

---

## Customization Questions

### Can I customize form styling?

**Yes.** Multiple options:

**Option 1: Settings panel (no code):**
1. Edit form
2. Settings sidebar → Design
3. Customize:
   - Colors (background, text, buttons)
   - Typography (fonts, sizes)
   - Spacing (padding, margins)
   - Border radius

**Option 2: Custom CSS:**
1. SureForms → Settings → Custom CSS
2. Add your styles:
```css
.srfm-form {
  background: #f9f9f9;
  padding: 30px;
  border-radius: 8px;
}

.srfm-form input {
  border: 2px solid #333;
}
```

**Option 3: Theme stylesheet:**

Add to `style.css`:
```css
.srfm-form { /* Your styles */ }
```

**Option 4: Page builder:**

Use page builder's CSS editor (if using Elementor/Divi).

---

### Can I add custom fields?

**Yes** (requires development).

**Process:**

1. Create block folder: `inc/blocks/my-field/`
2. Register block: `block.php`
3. Create React component: `src/blocks/my-field/edit.js`
4. Add sanitization: `inc/helper.php`
5. Add validation: `inc/field-validation.php`

**Example:** See [onboarding guide](onboarding.md#step-2-create-block-structure)

**Pre-built custom fields (Pro):**
- Star rating
- Signature
- File upload
- Date/time picker

---

### Can I change the submit button text?

**Yes.**

**Per form:**
1. Edit form
2. Click submit button block
3. Settings sidebar → Button Text
4. Change text
5. Update form

**Globally:**

Add to `functions.php`:
```php
add_filter('sureforms_submit_button_text', function($text) {
  return __('Send Message', 'sureforms');
});
```

---

## Business & Licensing Questions

### Can I use SureForms on client sites?

**Free:** Yes, unlimited sites.

**Pro:**
- **Agency license required** ($299/year, unlimited sites)
- Essential ($99/year, 3 sites) - only for your own sites
- Plus ($199/year, 20 sites) - only for your own sites

**License terms:**
- You must own the sites (not client-owned sites with Essential/Plus)
- Agency license allows client sites
- Each site needs separate activation

---

### Can I white-label SureForms?

**Not officially.**

**What you can do:**
- Remove branding from admin (CSS)
- Custom email footer (replace "Powered by SureForms")
- Custom confirmation messages

**What you can't do:**
- Remove copyright notices from code
- Rebrand and resell as your own product
- Remove attribution from free plugin

**Enterprise white-label:**
- Contact sales for custom licensing
- Available for high-volume agencies

---

### What's your refund policy?

**14-day money-back guarantee** (Pro licenses)

**Refund eligibility:**
- Purchased within last 14 days
- Tried to use but encountered issues
- Support unable to resolve

**Not eligible:**
- After 14 days
- Changed mind (not technical issue)
- Purchased wrong license (can upgrade instead)

**Process:**
1. Contact support: support@sureforms.com
2. Describe issue (we'll try to help first)
3. If unresolved, refund processed within 3-5 business days

---

### How long do I get updates?

**Free:** Lifetime updates.

**Pro:**
- Updates: For duration of license (1 year)
- Renewals: Discounted renewal price after year 1
- If license expires: Plugin continues working, no new updates

**Version compatibility:**
- Major updates: Included
- Security updates: Prioritized
- Feature updates: Based on roadmap

---

## Developer Questions

### Is the code open source?

**Free plugin:** Yes.
- License: GPL v2 or later
- GitHub: https://github.com/brainstormforce/sureforms

**Pro plugin:** No.
- Proprietary license
- Source code visible but not redistributable

---

### Can I contribute to SureForms?

**Yes!** We welcome contributions.

**How to contribute:**

1. Fork repo: https://github.com/brainstormforce/sureforms
2. Create branch: `git checkout -b feat/my-feature`
3. Make changes
4. Run tests: `composer test && npm run test:unit`
5. Submit PR

**Contribution guidelines:**
- Follow [coding standards](coding-standards.md)
- Add tests for new features
- Update docs if needed
- Sign off commits: `Co-Authored-By: Your Name <email>`

---

### Where can I find the API documentation?

**API docs:** [apis.md](apis.md)

**Key resources:**
- REST endpoints: [apis.md#rest-api](apis.md#rest-api)
- Hooks & filters: [apis.md#hooks--filters](apis.md#hooks--filters)
- JavaScript APIs: [apis.md#javascript-apis](apis.md#javascript-apis)
- Database APIs: [apis.md#database-apis](apis.md#database-apis)

**Code examples:** [ai-agent-guide.md](ai-agent-guide.md)

---

## Need More Help?

**Documentation:**
- [README.md](README.md) - Quick start
- [Troubleshooting](troubleshooting.md) - Common problems
- [Onboarding](onboarding.md) - Developer guide
- [Glossary](glossary.md) - Technical terms

**Support:**
- **Free users:** WordPress.org forum, GitHub issues
- **Pro users:** https://support.brainstormforce.com/ (< 24hr response)

**Community:**
- Facebook: https://www.facebook.com/groups/surecart
- GitHub Discussions: https://github.com/brainstormforce/sureforms/discussions

---

**Next:** [Glossary](glossary.md)
