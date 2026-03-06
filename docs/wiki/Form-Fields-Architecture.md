# Form Fields Architecture

SureForms uses a layered architecture for form fields: Gutenberg blocks define the editor experience, field markup classes generate the frontend HTML, and the validation system ensures data integrity on submission.

## Field Type Hierarchy

```
Fields\Base (inc/fields/base.php)
  |-- Input_Markup       (text, hidden)
  |-- Email_Markup       (email with validation)
  |-- Textarea_Markup    (multi-line text)
  |-- Number_Markup      (numeric with min/max)
  |-- Phone_Markup       (with country selector)
  |-- URL_Markup         (URL with validation)
  |-- Dropdown_Markup    (select options)
  |-- Checkbox_Markup    (single/multiple)
  |-- Multichoice_Markup (radio/checkbox groups)
  |-- Address_Markup     (composite address field)
  |-- GDPR_Markup        (consent checkbox)
  |-- Payment_Markup     (Stripe payment field)
  |-- Inlinebutton_Markup (inline buttons)
```

## Base Field Class

`inc/fields/base.php` provides the foundation:

- Common HTML wrapper generation
- Label rendering with required indicator
- Help text / description output
- Error message container
- CSS class assembly with `srfm-` prefix
- Conditional logic attribute output
- Accessibility attributes (aria-label, aria-required)

## Markup Generation Pattern

Each field markup class follows a consistent pattern:

1. Receive block attributes from the Gutenberg block
2. Process and sanitize attribute values
3. Generate field-specific HTML using WordPress escaping functions
4. Wrap in the standard field container with label and error placeholder
5. Return the complete HTML string

## Available Field Types

### Input (input-markup.php)
Standard text input supporting `text`, `hidden`, and other HTML5 input types. Supports placeholder, default value, min/max length.

### Email (email-markup.php)
Email input with built-in email format validation. Supports confirmation email field option.

### Textarea (textarea-markup.php)
Multi-line text area with configurable rows and max length.

### Number (number-markup.php)
Numeric input with min/max value constraints, step size, and format validation.

### Phone (phone-markup.php)
Phone number input integrated with `intl-tel-input` library for country code selection. Uses `countries.json` for country data.

### URL (url-markup.php)
URL input with format validation.

### Dropdown (dropdown-markup.php)
Select dropdown with configurable options. Supports default selection and placeholder text.

### Checkbox (checkbox-markup.php)
Single or multiple checkbox inputs with label positioning options.

### Multi Choice (multichoice-markup.php)
Radio button or checkbox groups for multiple choice questions. Supports image choices and custom layouts.

### Address (address-markup.php)
Composite field with subfields: street address, city, state/province, zip/postal code, and country. Each subfield is independently configurable.

### GDPR (gdpr-markup.php)
GDPR consent checkbox with customizable compliance text. Integrates with form-level compliance settings.

### Payment (payment-markup.php)
Payment collection field for Stripe integration. Renders the payment form elements and connects to the payment processing flow.

## Validation System

### Server-Side Validation (field-validation.php)

`inc/field-validation.php` handles validation during form submission:

- **Required field check** -- Ensures required fields have values
- **Type validation** -- Validates data types (email format, URL format, number ranges)
- **Unique value check** -- Optional unique validation against existing entries
- **Custom validation** -- Extensible via filters

Common error messages are defined in `Helper::get_common_err_msg()`:
- `required`: "This field is required."
- `unique`: "Value needs to be unique."

### Client-Side Validation

Frontend JavaScript provides instant validation feedback before submission, matching server-side rules.

## Field Rendering Pipeline

```
1. Block Editor (Gutenberg)
   -> Block attributes saved in post_content

2. Frontend Request
   -> Generate_Form_Markup::get_form_markup()
   -> Parses post_content blocks
   -> For each block, calls its render callback

3. Block Render Callback
   -> Block class (inc/blocks/{type}/block.php)
   -> Instantiates field markup class
   -> Passes block attributes

4. Field Markup Generation
   -> Field markup class (inc/fields/{type}-markup.php)
   -> Base class wraps with container, label, error placeholder
   -> Field-specific HTML generated
   -> Returns escaped HTML string

5. Form Assembly
   -> All field HTML concatenated inside <form> wrapper
   -> Submit button appended
   -> Nonce field added
   -> Final form HTML returned
```

## Related Pages

- [Gutenberg Blocks](Gutenberg-Blocks) -- Block registration and editor components
- [Form Submission Flow](Form-Submission-Flow) -- How field data is validated and processed
- [WordPress Hooks Reference](WordPress-Hooks-Reference) -- Field-related filters
- [Payment Integration](Payment-Integration) -- Payment field details
