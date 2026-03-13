# Troubleshooting & FAQ

Common issues, debugging tips, and frequently asked questions for SureForms development.

## Common Development Issues

### Build Failures

**Problem:** `npm run build` fails with errors.

**Solutions:**
1. Ensure Node.js 18.15.0 is active (Volta manages this automatically via `package.json`):
   ```bash
   node -v   # Should output v18.15.0
   ```
2. Clear and reinstall dependencies:
   ```bash
   rm -rf node_modules
   npm install
   ```
3. If SASS compilation fails, check that `sass/` source files have valid syntax
4. If Grunt minification fails, ensure Grunt CLI is available:
   ```bash
   npx grunt minify
   ```

### wp-env Issues

**Problem:** `npm run play:up` fails to start the Docker environment.

**Solutions:**
1. Ensure Docker is installed and running
2. Clean the environment:
   ```bash
   npm run env:clean
   ```
3. Restart the environment:
   ```bash
   npm run play:stop
   npm run play:up
   ```
4. If port conflicts occur, check for other services on ports 8888/8889

### PHPCS Errors

**Problem:** `vendor/bin/phpcs` reports violations.

**Solutions:**
1. Auto-fix what's possible:
   ```bash
   vendor/bin/phpcbf
   ```
2. Common issues:
   - Missing text domain: Add `'sureforms'` to all translatable strings
   - Unescaped output: Wrap output in `esc_html()`, `esc_attr()`, etc.
   - Missing nonce verification: Add nonce checks for form/AJAX handlers
   - Short array syntax: Use `[]` instead of `array()` (enforced by config)

### PHPUnit Test Failures

**Problem:** `vendor/bin/phpunit` tests fail.

**Solutions:**
1. Ensure WordPress test environment is available (wp-env or local setup)
2. Check database connectivity for the test suite
3. Run a specific test to isolate the issue:
   ```bash
   vendor/bin/phpunit tests/unit/test-specific.php
   ```

### JavaScript Lint Errors

**Problem:** `npm run lint-js` reports errors.

**Solutions:**
1. Auto-fix most issues:
   ```bash
   npm run lint-js:fix
   ```
2. Format code with Prettier:
   ```bash
   npm run pretty:fix
   ```
3. Common issues:
   - Import ordering: WordPress dependencies should use `@wordpress/*`
   - Unused variables: Remove or prefix with underscore
   - Missing dependencies in `useEffect`: Add to dependency array

## Form Submission Issues

### Nonce Verification Fails

**Cause:** The `X-WP-Submit-Nonce` has expired (default: 24-hour lifetime).

**Debug:**
- Nonces can be refreshed via `GET /wp-json/sureforms/v1/refresh-nonces`
- Check that the nonce is being sent in the request header
- Caching plugins may cache the nonce -- exclude form pages from page cache

### CAPTCHA Validation Fails

**Cause:** CAPTCHA keys are misconfigured or the token is expired.

**Debug:**
1. Verify CAPTCHA keys in SureForms global settings
2. Check browser console for CAPTCHA loading errors
3. Ensure the CAPTCHA provider (reCAPTCHA, hCaptcha, Turnstile) is correctly configured
4. reCAPTCHA v3 can trigger multiple times on pages with multiple forms

### File Upload Errors

**Debug:**
1. Check PHP `upload_max_filesize` and `post_max_size` in `php.ini`
2. Verify allowed file types in form field settings
3. Check file size against the configured maximum
4. Ensure the `wp-content/uploads/` directory is writable

## Database Issues

### Custom Tables Missing

**Cause:** The `wp_srfm_entries` or `wp_srfm_payments` table was not created.

**Debug:**
1. Deactivate and reactivate the plugin to trigger table creation
2. Check the `srfm_db_version` option in `wp_options`
3. Verify database user has `CREATE TABLE` permissions
4. Check for errors in `debug.log` during activation

### Entry Data Not Saving

**Debug:**
1. Check the `do_not_store_entries` form setting (GDPR compliance)
2. Verify the form submission response for error messages
3. Check `debug.log` for database insert errors
4. Ensure the entries table schema is up to date

## REST API Issues

### 401 Unauthorized on Admin Endpoints

**Cause:** User is not authenticated or lacks required capabilities.

**Debug:**
1. Admin endpoints require `manage_options` capability
2. Ensure the user is logged in with a valid session
3. Check that the REST API nonce is being sent (via `X-WP-Nonce` header)
4. WordPress cookie authentication must be active

### 403 on Form Submission

**Cause:** The submit nonce is invalid or form restrictions are blocking submission.

**Debug:**
1. Check the `X-WP-Submit-Nonce` header value
2. Review form restriction settings (max entries, date scheduling, login required)
3. Check the `srfm_form_submit_permissions_check` filter

## Performance Issues

### Slow Admin Dashboard

**Causes & Solutions:**
1. **Large number of entries:** Entries are paginated -- default page size is managed by REST API
2. **Unoptimized queries:** The entries table has indexes on `form_id`, `user_id`, and a composite index on `(form_id, created_at, status)`
3. **Asset loading:** Admin assets are conditionally loaded only on SureForms admin pages

### Slow Form Rendering

**Causes & Solutions:**
1. **Too many blocks:** Complex forms with many fields may benefit from multi-step layout
2. **External scripts:** CAPTCHA and payment scripts load conditionally based on form settings
3. **CSS generation:** Each block generates dynamic CSS -- review `generateCSS.js` for optimization

## Debugging Tips

### Enable WordPress Debug Mode

Add to `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
```

### Check REST API Responses

Use the browser developer tools Network tab to inspect REST API calls:
- Filter by `sureforms/v1` to see plugin API requests
- Check response status codes and error messages

### Inspect Form Data Store

In the browser console on a form editor page:
```javascript
wp.data.select('starter-starter/starter-starter-forms-data').getFormSettings();
```

## FAQ

### What WordPress version is required?

WordPress 6.4 or higher is required.

### What PHP version is required?

PHP 7.4 or higher. The `phpcs.xml` enforces PHP 7.4+ compatibility.

### Can I modify bundled libraries?

Files in `inc/lib/` are third-party libraries and should not be modified directly. If changes are needed, they should be managed upstream.

### Where do I put new PHP classes?

New PHP classes go in the `inc/` directory following the existing namespace structure (`SRFM\Inc\*`). The autoloader in `plugin-loader.php` handles class loading.

### How do I add a new admin page?

Admin pages are rendered by the React SPA in `src/admin/`. Add new routes using `react-router-dom` and register the corresponding menu items in PHP. See [Admin Dashboard](Admin-Dashboard) for details.

### How do I add a new form field type?

See [Form Fields Architecture](Form-Fields-Architecture) for the field type hierarchy and the process for adding new field types.

## Related Pages

- [Environment Configuration](Environment-Configuration) -- Development setup
- [Testing Guide](Testing-Guide) -- Running tests
- [Architecture Overview](Architecture-Overview) -- Codebase structure
- [REST API Reference](REST-API-Reference) -- API endpoints
- [Database Schema](Database-Schema) -- Table structure
