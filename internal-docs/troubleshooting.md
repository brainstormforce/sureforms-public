# Troubleshooting Guide

**Version:** 2.5.0

---

## Quick Diagnostic

**Before diving deep, check these common issues:**

```bash
# 1. WordPress & PHP versions
wp core version
php -v

# 2. Plugin status
wp plugin list | grep sureforms

# 3. Theme compatibility
wp theme list --status=active

# 4. Recent errors
tail -50 wp-content/debug.log | grep -i "sureforms\|fatal\|error"

# 5. Database tables exist
wp db query "SHOW TABLES LIKE '%sureforms%';"
```

---

## Installation & Activation Issues

### Plugin Won't Activate

**Symptom:** "Plugin activation failed" or white screen

**Common Causes:**

**1. PHP Version Too Old**
```bash
php -v
# Required: PHP 7.4+
```

**Fix:**
```bash
# Update PHP (contact host if shared hosting)
# Or add to wp-config.php temporarily to see error:
define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', true);
```

**2. WordPress Version Too Old**
```bash
wp core version
# Required: WordPress 6.4+
```

**Fix:**
```bash
wp core update
```

**3. Conflicting Plugin**

**Check for conflicts:**
```bash
# Deactivate all other plugins
wp plugin deactivate --all --exclude=sureforms,sureforms-pro

# Try activating SureForms
wp plugin activate sureforms

# Reactivate plugins one by one
wp plugin activate plugin-name
```

**Common conflicts:**
- Old caching plugins (W3 Total Cache < 2.0)
- Security plugins with aggressive rules
- Other form builders (namespace collisions)

**4. Memory Limit Too Low**

**Check current limit:**
```bash
wp eval 'echo WP_MEMORY_LIMIT;'
```

**Fix (wp-config.php):**
```php
define('WP_MEMORY_LIMIT', '256M');
```

---

### Pro Plugin Shows "Base Plugin Required"

**Symptom:** SureForms Pro won't activate

**Diagnosis:**
```bash
wp plugin list | grep sureforms
# Ensure both sureforms AND sureforms-pro are installed
```

**Fix:**
1. Install SureForms Free first
2. Activate SureForms Free
3. Then activate SureForms Pro

**Check version compatibility:**
```bash
# Free and Pro versions should match
# Both should be 2.5.0 (or same major.minor)
```

---

### Database Tables Not Created

**Symptom:** Form submissions fail, entries not showing

**Check tables exist:**
```bash
wp db query "SHOW TABLES LIKE '%sureforms%';"
```

**Expected output:**
```
wp_sureforms_entries
wp_sureforms_payments
wp_sureforms_integrations
wp_sureforms_save_resume
```

**Fix: Force database creation**
```bash
# Deactivate plugin
wp plugin deactivate sureforms sureforms-pro

# Delete plugin (backup first!)
wp plugin delete sureforms

# Reinstall
wp plugin install sureforms --activate

# Tables should be created on activation
```

**Manual creation (last resort):**

Read schema from:
- `inc/database/tables/entries.php`
- `inc/database/tables/payments.php`

Run CREATE TABLE statements manually.

---

## Form Builder (Editor) Issues

### Forms Won't Load in Editor

**Symptom:** Blank screen or infinite loading spinner

**Diagnosis:**

**1. Check browser console:**
```
Open DevTools → Console
Look for JavaScript errors
```

**Common errors:**

**a) "wp.blockEditor is undefined"**
- **Cause:** Outdated WordPress version
- **Fix:** `wp core update`

**b) "React version mismatch"**
- **Cause:** Another plugin using old React version
- **Fix:** Deactivate other plugins one by one to find conflict

**c) "Uncaught SyntaxError"**
- **Cause:** JavaScript file corrupted or not minified correctly
- **Fix:** Rebuild assets: `npm run build` in plugin directory

**2. Check network tab:**
```
DevTools → Network
Filter: JS
Look for failed requests (red, 404, 500)
```

**Common failures:**
- `form-editor.js` (404) → Rebuild assets
- `chunk-vendors.js` (500) → Server misconfiguration

**3. Enable SCRIPT_DEBUG:**

In `wp-config.php`:
```php
define('SCRIPT_DEBUG', true);
```

This loads unminified JS, easier to debug.

---

### Blocks Not Appearing in Inserter

**Symptom:** SureForms blocks missing from block library

**Diagnosis:**
```bash
# Check if blocks are registered
wp eval "print_r(get_option('srfm_blocks_registration'));"
```

**Fixes:**

**1. Re-register blocks:**
```bash
wp plugin deactivate sureforms
wp plugin activate sureforms
```

**2. Clear block cache:**
```bash
wp cache flush
wp transient delete --all
```

**3. Check block.json files exist:**
```bash
find wp-content/plugins/sureforms/inc/blocks -name "block.json"
# Should return multiple files
```

---

### Form Preview Shows "Invalid Block"

**Symptom:** Form renders as "This block contains unexpected or invalid content"

**Cause:** Block HTML structure changed, saved form has old structure

**Fix:**

**Option 1: Update block (preserves data):**
1. Click "Attempt Block Recovery"
2. Verify content looks correct
3. Update form

**Option 2: Clear and rebuild:**
1. Delete the invalid block
2. Add fresh block
3. Reconfigure settings

**Prevention:**
- Always test after plugin updates
- Keep staging environment for testing

---

## Form Submission Issues

### Form Submissions Not Saving

**Symptom:** User submits form, success message shows, but no entry in database

**Diagnosis:**

**1. Check debug log:**
```bash
tail -f wp-content/debug.log
# Submit form while watching log
```

**Common errors:**
- "Database insert failed" → Check table permissions
- "Nonce verification failed" → Caching issue (see below)
- "Call to undefined method" → PHP version or missing dependency

**2. Check database directly:**
```bash
# Get latest entry
wp db query "SELECT * FROM wp_sureforms_entries ORDER BY id DESC LIMIT 1;"
```

**3. Test with minimal form:**
Create form with ONLY:
- Email field
- Submit button

If this works, issue is with specific field type.

**Fixes:**

**Database Permissions:**
```bash
# Check MySQL user has INSERT privilege
wp db query "SHOW GRANTS;"
```

**Fix permissions (MySQL):**
```sql
GRANT INSERT, UPDATE, DELETE ON database_name.* TO 'wp_user'@'localhost';
FLUSH PRIVILEGES;
```

**Caching Interference:**

Caching plugins cache nonce values, causing verification failures.

**Fix:**
1. Exclude `/wp-admin/admin-ajax.php` from cache
2. Exclude REST API `/wp-json/` from cache
3. Or disable caching temporarily to test

---

### Email Notifications Not Sending

**Symptom:** Form submitted successfully, but no email received

**Diagnosis:**

**1. Check if emails are being sent at all:**
```bash
# Install WP Mail SMTP or similar
wp plugin install wp-mail-smtp --activate

# Or test with simple command:
wp eval "wp_mail('your@email.com', 'Test', 'Testing SureForms');"
```

**2. Check SureForms email settings:**
```bash
# View form meta
wp post meta list <form_id> | grep email
```

**3. Check spam folder**
- WordPress default `from` address: `wordpress@yourdomain.com`
- Often flagged as spam

**Fixes:**

**Configure SMTP:**

Install WP Mail SMTP plugin:
```bash
wp plugin install wp-mail-smtp --activate
```

Configure with:
- Gmail
- SendGrid
- Mailgun
- Amazon SES

**Check email template:**

In form settings → Email Notification:
- Verify "To" address is correct
- Check "From" address is valid domain
- Test with simple subject/message

**Server mail() function:**

Some hosts disable PHP `mail()` function.

Test:
```bash
php -r "mail('test@example.com', 'Test', 'Test message');"
```

If no email received, `mail()` is disabled. Use SMTP.

---

### Form Validation Not Working

**Symptom:** Form submits even with empty required fields

**Causes:**

**1. JavaScript disabled** (frontend validation skipped)
**2. Theme CSS hiding error messages**
**3. Custom JavaScript conflict**

**Diagnosis:**

**Check browser console:**
```
Look for JavaScript errors
Check if srfm-validation.js loaded
```

**Test with default theme:**
```bash
wp theme activate twentytwentythree
# Submit form again
```

If works with default theme → Theme conflict.

**Fixes:**

**Theme conflict:**

Add to theme's `functions.php`:
```php
add_action('wp_enqueue_scripts', function() {
  // Ensure SureForms scripts load
  wp_enqueue_script('srfm-frontend');
}, 20);
```

**CSS hiding errors:**

Check if theme has:
```css
.srfm-error { display: none !important; }
```

Remove or override.

---

## Payment Processing Issues

### Stripe Payments Failing

**Symptom:** "Payment failed" error after entering card details

**Diagnosis:**

**1. Check Stripe API keys:**
```bash
# In WordPress admin: SureForms → Settings → Payments → Stripe
# Verify:
# - Using correct keys (test vs live)
# - Keys match Stripe dashboard
```

**2. Check Stripe webhook:**
```bash
# In Stripe Dashboard → Developers → Webhooks
# Verify webhook URL is:
https://yoursite.com/wp-json/sureforms/webhook_test

# Check recent webhook deliveries for errors
```

**3. Check browser console:**
```
DevTools → Console
Look for Stripe.js errors
```

**Common Errors:**

**"Invalid API Key"**
- Using test key in live mode (or vice versa)
- API key revoked in Stripe dashboard
- Fix: Copy fresh keys from Stripe

**"Payment Intent creation failed"**
- Amount is $0 or negative
- Currency mismatch
- Fix: Check form configuration, amount field

**"Webhook signature verification failed"**
- Webhook secret incorrect
- Middleware issue
- Fix: Copy webhook signing secret from Stripe, update settings

**Fixes:**

**Test mode checklist:**
```bash
# 1. Use test API keys (starts with pk_test_ / sk_test_)
# 2. Test card: 4242 4242 4242 4242
# 3. Any future expiry date
# 4. Any 3-digit CVC
```

**Live mode checklist:**
```bash
# 1. Use live API keys (starts with pk_live_ / sk_live_)
# 2. SSL certificate valid (https://)
# 3. Webhook verified in Stripe dashboard
# 4. Test with real card (refund immediately)
```

---

### PayPal Payments Failing (Pro)

**Symptom:** Redirected to PayPal but payment doesn't process

**Diagnosis:**

**Check PayPal credentials:**
```bash
# SureForms → Settings → Payments → PayPal
# Verify:
# - Client ID matches PayPal dashboard
# - Secret matches
# - Using sandbox for testing, live for production
```

**Check webhook endpoint:**
```bash
# PayPal Dashboard → Apps & Credentials → Webhooks
# Webhook URL should be:
https://yoursite.com/wp-json/sureforms-pro/paypal-live-webhook
```

**Fixes:**

**Test in sandbox mode first:**
1. Create PayPal sandbox account: https://developer.paypal.com/
2. Use sandbox credentials in SureForms
3. Test with sandbox buyer account

**Common issues:**
- **Wrong environment:** Using sandbox credentials in live mode
- **Webhook not subscribed:** Must subscribe to payment events in PayPal dashboard
- **SSL certificate:** PayPal requires valid HTTPS

---

## Integration Issues (Pro)

### Native Integration Not Connecting

**Symptom:** "Connection failed" when adding integration (Mailchimp, HubSpot, etc.)

**Diagnosis:**

**1. Check OAuth redirect URL:**

For OAuth integrations (Mailchimp, HubSpot, Salesforce):
```
Redirect URL must be:
https://yoursite.com/wp-json/sureforms-pro/v1/oauth/callback
```

**2. Test API credentials:**

For API key integrations (Brevo, etc.):
```bash
# Test API key directly
curl -X GET "https://api.brevo.com/v3/account" \
  -H "api-key: YOUR_API_KEY"
```

Should return account info, not error.

**3. Check error logs:**
```bash
tail -f wp-content/debug.log | grep -i "integration\|oauth"
```

**Fixes:**

**OAuth issues:**
- Ensure site uses HTTPS (required for OAuth)
- Whitelist redirect URL in service's developer console
- Check OAuth app has correct permissions/scopes

**API key issues:**
- Regenerate key in service dashboard
- Copy entire key (no spaces, no quotes)
- Check key has required permissions (read/write)

---

### Webhook Not Firing

**Symptom:** Form submitted, but data not sent to integrated service

**Diagnosis:**

**Check webhook logs:**

Install Query Monitor plugin:
```bash
wp plugin install query-monitor --activate
```

Submit form, check "HTTP API Calls" panel for webhook requests.

**Manually trigger webhook:**
```bash
# Find integration ID
wp db query "SELECT * FROM wp_sureforms_integrations;"

# Trigger webhook manually (developer test)
wp eval "do_action('sureforms_after_entry_save', 123, 456, []);"
```

**Fixes:**

**Webhook URL validation:**

In `inc/pro/integrations/webhooks.php`, ensure URL is valid:
- HTTPS only (no HTTP)
- No localhost (unless testing)
- Responds with 200 OK

**Timeout issues:**

Increase timeout in `wp-config.php`:
```php
define('WP_HTTP_BLOCK_EXTERNAL', false);
define('WP_ACCESSIBLE_HOSTS', 'api.mailchimp.com,api.hubspot.com');
```

---

## Performance Issues

### Form Editor Slow to Load

**Symptom:** Takes 10+ seconds to load form in editor

**Diagnosis:**

**1. Check database size:**
```bash
wp db query "SELECT COUNT(*) FROM wp_sureforms_entries;"
# If > 100,000 entries, database may be slow
```

**2. Check server resources:**
```bash
# Memory usage
free -h

# CPU usage
top

# Disk I/O
iostat
```

**3. Profile with Query Monitor:**
```bash
wp plugin install query-monitor --activate
# Open form editor, check QM panel for slow queries
```

**Fixes:**

**Optimize database:**
```bash
# Clean old entries (backup first!)
wp db query "DELETE FROM wp_sureforms_entries WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);"

# Optimize tables
wp db optimize
```

**Increase PHP limits:**

In `php.ini` or `.htaccess`:
```
max_execution_time = 300
memory_limit = 256M
```

**Enable object caching:**

Install Redis or Memcached:
```bash
wp plugin install redis-cache --activate
wp redis enable
```

---

### Frontend Form Loads Slowly

**Symptom:** Form takes 5+ seconds to appear on page

**Diagnosis:**

**Check asset loading:**
```
DevTools → Network → Slow 3G simulation
Watch which assets are slow
```

**Common bottlenecks:**
- Google Fonts loading slowly
- Large CSS/JS files
- Unoptimized images in form

**Fixes:**

**Lazy load non-critical assets:**

In `functions.php`:
```php
add_filter('script_loader_tag', function($tag, $handle) {
  if ($handle === 'srfm-frontend') {
    return str_replace(' src', ' defer src', $tag);
  }
  return $tag;
}, 10, 2);
```

**Use CDN for Google Fonts:**

In form settings → Design → Typography:
- Limit to 1-2 font families
- Use system fonts for faster load (e.g., -apple-system)

**Minify and combine assets:**
```bash
npm run build  # Ensures assets are minified
```

**Enable caching:**

Install caching plugin:
```bash
wp plugin install wp-super-cache --activate
```

Configure to cache pages with forms.

---

## Block Compatibility Issues

### Form Breaks After Theme Update

**Symptom:** Form displays incorrectly or not at all after theme update

**Diagnosis:**

**Compare theme CSS:**

Check if new theme has conflicting styles:
```css
/* Common conflicts */
.srfm-form input { /* Theme override */ }
.srfm-field { /* Theme override */ }
```

**Test with default theme:**
```bash
wp theme activate twentytwentythree
```

If works → Theme issue.

**Fixes:**

**Add theme compatibility CSS:**

In child theme `style.css`:
```css
/* Reset SureForms blocks */
.srfm-form,
.srfm-field,
.srfm-form input,
.srfm-form textarea {
  all: revert;
}

/* Then apply minimal SureForms styles */
```

**Use !important (last resort):**

In SureForms settings → Custom CSS:
```css
.srfm-form input {
  border: 1px solid #ccc !important;
  padding: 10px !important;
}
```

---

### Conflicts with Page Builders

**Symptom:** Forms don't work inside Elementor/Divi/Beaver Builder

**Common Issues:**

**1. JavaScript conflicts:**
- Page builder loads own jQuery version
- Conflicts with SureForms scripts

**Fix:**
```php
// In functions.php
add_action('wp_enqueue_scripts', function() {
  if (class_exists('Elementor\Plugin')) {
    wp_dequeue_script('jquery');
    wp_enqueue_script('jquery');
  }
}, 100);
```

**2. CSS specificity:**
- Page builder CSS overrides SureForms

**Fix:**

Use SureForms settings → Custom CSS with higher specificity:
```css
.elementor-widget-container .srfm-form input {
  /* Your styles */
}
```

---

## Database & Query Issues

### "Too many connections" Error

**Symptom:** Site crashes during high form submission volume

**Diagnosis:**
```bash
wp db query "SHOW STATUS LIKE 'max_used_connections';"
wp db query "SHOW VARIABLES LIKE 'max_connections';"
```

**Fixes:**

**Increase max connections (MySQL):**

In `my.cnf`:
```
[mysqld]
max_connections = 500
```

**Use persistent connections:**

In `wp-config.php`:
```php
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_PERSISTENT);
```

**Add connection pooling:**

Use ProxySQL or PgBouncer for connection pooling.

---

### Slow Query: "SELECT * FROM wp_sureforms_entries"

**Symptom:** Admin page very slow when viewing entries

**Diagnosis:**
```bash
wp plugin install query-monitor --activate
# View entries page, check QM for slow queries
```

**Fixes:**

**Add database indexes:**
```sql
ALTER TABLE wp_sureforms_entries
ADD INDEX idx_form_id (form_id),
ADD INDEX idx_created_at (created_at),
ADD INDEX idx_status (status);
```

**Limit entries displayed:**

In admin, reduce entries per page from 100 to 20.

**Paginate large result sets:**

Ensure code uses `LIMIT` and `OFFSET`:
```php
$entries = Entries::get_all([
  'limit' => 20,
  'offset' => ($page - 1) * 20
]);
```

---

## WordPress.org Review Issues

### Plugin Rejected for Security

**Common reasons:**

**1. Direct database calls without prepare():**
```php
// ❌ Bad
$wpdb->query("DELETE FROM table WHERE id = $id");

// ✅ Good
$wpdb->query($wpdb->prepare("DELETE FROM table WHERE id = %d", $id));
```

**2. Unsanitized user input:**
```php
// ❌ Bad
echo $_POST['name'];

// ✅ Good
echo esc_html(sanitize_text_field($_POST['name'] ?? ''));
```

**3. Missing nonce verification:**
```php
// ❌ Bad
if (isset($_POST['action'])) { do_action(); }

// ✅ Good
if (isset($_POST['action']) && check_ajax_referer('my_action_nonce')) {
  do_action();
}
```

**Fix:**

Run WordPress Coding Standards checker:
```bash
composer require --dev wp-coding-standards/wpcs
vendor/bin/phpcs --standard=WordPress inc/
```

Fix all errors before resubmitting.

---

## Getting Help

### Before Asking for Help

**Gather this information:**

```bash
# 1. WordPress & PHP versions
wp core version
php -v

# 2. Plugin version
wp plugin list | grep sureforms

# 3. Active theme
wp theme list --status=active

# 4. Other active plugins
wp plugin list --status=active

# 5. Recent errors
tail -50 wp-content/debug.log

# 6. Browser/OS (if frontend issue)
# Example: Chrome 120 on macOS 14
```

### Where to Get Help

**1. Documentation** (check first)
- This troubleshooting guide
- [FAQ](faq.md)
- [Architecture](architecture.md)

**2. GitHub Issues** (bugs & feature requests)
- Search existing issues first
- Provide minimal reproduction steps
- Include system info from above

**3. Support Portal** (Pro users)
- https://support.brainstormforce.com/
- < 24hr response time for Pro users

**4. Community**
- Facebook group: https://www.facebook.com/groups/surecart
- WordPress.org forum (Free only)

---

## Still Stuck?

If none of the above solutions work:

**Create detailed bug report:**

```markdown
**Environment:**
- WordPress: 6.4.2
- PHP: 8.1
- SureForms: 2.5.0 (Free/Pro)
- Theme: Astra 4.5.0
- Browser: Chrome 120

**Steps to Reproduce:**
1. Create form with email field
2. Mark field as required
3. Submit form with empty email
4. [Describe unexpected behavior]

**Expected:** Validation error shows
**Actual:** Form submits anyway

**Debug Log:**
[Paste relevant errors from debug.log]

**Screenshots:**
[Attach if visual issue]
```

Submit to: https://github.com/brainstormforce/sureforms/issues

---

**Next:** [FAQ](faq.md)
