# Gutenberg Blocks

SureForms provides 14+ Gutenberg block types for building forms in the WordPress block editor.

## Block Types

### Field Blocks

| Block | Slug | Description |
|-------|------|-------------|
| Input | `srfm/input` | Text input (text, hidden, etc.) |
| Email | `srfm/email` | Email address input with validation |
| Textarea | `srfm/textarea` | Multi-line text area |
| Number | `srfm/number` | Numeric input with min/max |
| Phone | `srfm/phone` | Phone number with country selector (intl-tel-input) |
| URL | `srfm/url` | URL input with validation |
| Dropdown | `srfm/dropdown` | Select dropdown with options |
| Checkbox | `srfm/checkbox` | Single/multiple checkboxes |
| Multi Choice | `srfm/multichoice` | Radio/checkbox group |
| Address | `srfm/address` | Address field with subfields (street, city, state, zip, country) |
| GDPR | `srfm/gdpr` | GDPR consent checkbox |
| Payment | `srfm/payment` | Payment collection (Stripe integration) |
| Inline Button | `srfm/inlinebutton` | Inline form button |

### Container Block

| Block | Slug | Description |
|-------|------|-------------|
| SForm | `srfm/sform` | Main form wrapper block (required container for all field blocks) |

## Block Registration

### Server-Side (PHP)

Blocks are registered in `inc/blocks/register.php` via the `Blocks\Register` class, initialized on the `init` hook:

```php
// inc/blocks/register.php
register_block_type_from_metadata(
    SRFM_DIR . 'inc/blocks/{block-type}/',
    [
        'render_callback' => [ $block_instance, 'render' ],
    ]
);
```

Each block type has:
- `block.json` -- Block metadata, attributes, and category
- `block.php` -- Server-side rendering class extending `Blocks\Base`

### Client-Side (JavaScript)

Block registration happens in `src/blocks/blocks.js`:

```javascript
// src/blocks/blocks.js
import { registerBlockType } from '@wordpress/blocks';
// Block imports and registrations...
```

The `src/blocks/register-block.js` utility handles individual block registration.

## Block Architecture

### Base Class: `Blocks\Base`

All block PHP classes extend `SRFM\Inc\Blocks\Base`, which provides:

- `render()` -- Main render callback invoked by WordPress
- Access to block attributes from `block.json`
- Common rendering logic shared across field types
- Integration with field markup classes

### Block to Field Mapping

Each block delegates HTML generation to its corresponding field markup class in `inc/fields/`:

```
Block (inc/blocks/input/block.php)
  -> Field Markup (inc/fields/input-markup.php)
    -> Base Field (inc/fields/base.php)
      -> HTML output
```

### block.json Structure

Each block directory contains a `block.json` file defining:

```json
{
  "apiVersion": 3,
  "name": "srfm/input",
  "title": "Input",
  "category": "sureforms",
  "attributes": {
    "label": { "type": "string", "default": "Input" },
    "required": { "type": "boolean", "default": false },
    "placeholder": { "type": "string", "default": "" }
  },
  "supports": { "html": false }
}
```

## Adding a New Block Type

1. **Create block directory:** `inc/blocks/{new-type}/`
2. **Add `block.json`** with attributes, name, and category
3. **Create `block.php`** extending `Blocks\Base` with a `render()` method
4. **Create field markup:** `inc/fields/{new-type}-markup.php` extending `Fields\Base`
5. **Register in `Blocks\Register`** -- add to the blocks array
6. **Create editor component:** `src/blocks/{new-type}/` with `edit.js` and `index.js`
7. **Add to `src/blocks/blocks.js`** -- import and register the new block

## The sform Container Block

The `srfm/sform` block is the required wrapper for all form fields. It:

- Renders the `<form>` HTML element
- Handles form-level settings (styling, confirmation, CAPTCHA)
- Manages submission nonce generation
- Controls form layout and submit button
- Integrates with frontend JavaScript for submission handling

## Related Pages

- [Form Fields Architecture](Form-Fields-Architecture) -- Field markup and validation
- [Block Editor Controls](Block-Editor-Controls) -- Custom inspector controls
- [Architecture Overview](Architecture-Overview) -- Block registration in bootstrap
- [Frontend Assets](Frontend-Assets) -- Block editor asset loading
