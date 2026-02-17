# Coding Standards

**Version:** 2.5.0

---

## PHP Standards

**WordPress Coding Standards:** https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/

### Code Style

- **PSR-12 inspired** but follows WordPress conventions
- **Tabs for indentation** (not spaces)
- **Yoda conditions:** `if ( 'value' === $variable )`
- **Braces:** Required for all control structures

```php
// ✅ Good
if ( $condition ) {
    do_something();
}

// ❌ Bad
if ($condition) do_something();
```

### Naming Conventions

**Functions:**
```php
srfm_public_function()           // Public functions
_srfm_private_function()         // Internal (leading underscore)
```

**Classes:**
```php
namespace SRFM\Inc;
class Form_Submit { }            // Underscores in class names

namespace SRFM_PRO\Inc\Business;
class PayPal_Helper { }
```

**Variables:**
```php
$snake_case_variable = 'value';  // Lowercase with underscores
```

**Constants:**
```php
define( 'SRFM_CONSTANT', 'value' );
```

### Security

**Always:**
- Sanitize input: `sanitize_text_field()`, `absint()`, `sanitize_email()`
- Validate: Check types, ranges, allowed values
- Escape output: `esc_html()`, `esc_attr()`, `esc_url()`
- Use nonces: `wp_verify_nonce()` for AJAX/forms
- Use `$wpdb->prepare()` for SQL queries
- Check capabilities: `current_user_can()`

**Example:**
```php
// Input
$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

// Database
$results = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sureforms_entries WHERE form_id = %d",
        $form_id
    )
);

// Output
echo '<div>' . esc_html( $user_name ) . '</div>';
```

### Documentation

**PHPDoc blocks:**
```php
/**
 * Short description.
 *
 * Longer description if needed.
 *
 * @since 2.5.0
 *
 * @param int    $form_id   Form ID.
 * @param array  $data      Entry data.
 * @return int|false Entry ID on success, false on failure.
 */
function srfm_create_entry( $form_id, $data ) {
    // Implementation
}
```

---

## JavaScript Standards

**WordPress JavaScript Coding Standards:** https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/

### Code Style (ESLint)

**Config:** `@wordpress/eslint-plugin`

```javascript
// ✅ Good - ES6+, const/let, arrow functions
const handleSubmit = (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    // ...
};

// ❌ Bad - var, function expressions
var handleSubmit = function(event) {
    event.preventDefault();
};
```

### React Conventions

**Functional components with hooks:**
```jsx
import { useState, useEffect } from '@wordpress/element';

function FormBuilder({ formId }) {
    const [fields, setFields] = useState([]);

    useEffect(() => {
        fetchFields(formId).then(setFields);
    }, [formId]);

    return (
        <div className="form-builder">
            {fields.map(field => <Field key={field.id} {...field} />)}
        </div>
    );
}
```

**Naming:**
- Components: `PascalCase`
- Hooks: `useCamelCase`
- Functions: `camelCase`
- Constants: `UPPER_SNAKE_CASE`

---

## CSS/SCSS Standards

**BEM-inspired naming:**
```scss
.srfm-form {
    &__field {
        // Field styles
    }

    &__field--error {
        // Error state
    }
}
```

**Prefix:** All classes start with `srfm-` to avoid conflicts.

---

## Database Standards

### Table Names

```php
$wpdb->prefix . 'sureforms_entries'
$wpdb->prefix . 'sureforms_payments'
```

**Always use `$wpdb->prefix`**, never hardcode `wp_`.

### Queries

**Always use prepared statements:**
```php
// ✅ Good
$wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sureforms_entries WHERE id = %d",
        $entry_id
    )
);

// ❌ Bad
$wpdb->query( "DELETE FROM wp_sureforms_entries WHERE id = $entry_id" );
```

---

## Git Commit Messages

**Format:**
```
type(scope): brief description

Longer explanation if needed.

Co-Authored-By: Name <email>
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation
- `style`: Formatting, no code change
- `refactor`: Code restructuring
- `test`: Adding tests
- `chore`: Build/tooling

**Example:**
```
feat(payments): add PayPal subscription support

- Implement PayPal billing agreement API
- Add webhook handler for subscription events
- Update admin UI for PayPal subscriptions

Co-Authored-By: Claude Sonnet 4.5 <noreply@anthropic.com>
```

---

## File Organization

**One class per file:**
```
inc/payments/stripe/stripe-helper.php  → class Stripe_Helper
```

**Group related functionality:**
```
inc/payments/
├── payment-helper.php       # Shared utilities
├── front-end.php            # Public AJAX handlers
├── stripe/
│   ├── stripe-helper.php
│   ├── stripe-webhook.php
│   └── admin-stripe-handler.php
└── admin/
    └── admin-handler.php
```

---

## Testing Standards

**PHPUnit:**
```php
class Test_Form_Submit extends WP_UnitTestCase {
    public function test_form_submission_saves_entry() {
        $entry_id = srfm_submit_form( $data );
        $this->assertIsInt( $entry_id );
    }
}
```

**Playwright:**
```javascript
test('form submission works', async ({ page }) => {
    await page.goto('/test-form/');
    await page.fill('[name="email"]', 'test@example.com');
    await page.click('button[type="submit"]');
    await expect(page.locator('.success-message')).toBeVisible();
});
```

---

## Code Review Checklist

Before submitting PR:

- [ ] Code follows WordPress/project standards
- [ ] All inputs sanitized
- [ ] All outputs escaped
- [ ] SQL queries use `prepare()`
- [ ] Nonces verified for AJAX/forms
- [ ] Capabilities checked for admin actions
- [ ] PHPDoc blocks added
- [ ] No PHP/JS errors in console
- [ ] Tested in latest WordPress version
- [ ] Tests pass: `composer test && npm run test:unit`
- [ ] Linting passes: `composer lint && npm run lint-js`

---

**Automated Checks:**

```bash
# PHP linting
composer lint                  # PHP_CodeSniffer

# JS linting
npm run lint-js                # ESLint

# Auto-fix
composer format                # phpcbf
npm run lint-js:fix            # ESLint --fix
```
