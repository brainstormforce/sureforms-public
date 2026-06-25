# Data Export & Import

SureForms supports exporting and importing forms and entries for backup, migration, and data analysis.

## Form Export

### REST Endpoint

`POST /wp-json/sureforms/v1/forms/export`

**Parameters:**
- `form_ids` (required) -- Array of form IDs to export

**Response:** JSON file containing form data including:
- Form post data (title, content, status)
- All form post meta (`_srfm_*` keys)
- Block content (Gutenberg blocks)

### Export Format

Forms are exported as a JSON structure containing all data needed to recreate the form on another site.

## Form Import

### REST Endpoint

`POST /wp-json/sureforms/v1/forms/import`

**Parameters:**
- File upload containing exported JSON data

**Process:**
1. Validates the uploaded JSON structure
2. Creates new `sureforms_form` posts
3. Restores all post meta keys
4. Assigns new post IDs (no ID conflicts)
5. Returns count of imported forms

## Form Duplication

### REST Endpoint

`POST /wp-json/sureforms/v1/forms/duplicate`

**Parameters:**
- `form_id` (required) -- ID of the form to duplicate

**Process:**

Handled by `Duplicate_Form` class (`inc/duplicate-form.php`):

1. Reads the original form post and all meta
2. Creates a new post with "(Copy)" appended to the title
3. Copies all `_srfm_*` post meta to the new form
4. Returns the new form ID

## Entry Export

### REST Endpoint

`POST /wp-json/sureforms/v1/entries/export`

**Parameters:**
- `entry_ids` -- Specific entry IDs to export (optional)
- `form_id` -- Export all entries for a specific form
- `status` -- Filter by status (all, read, unread)
- `search` -- Search filter
- `date_from` / `date_to` -- Date range filter

**Response:** CSV file (or ZIP for large exports)

### Export Format

The `Export` class (`inc/export.php`) generates CSV with:
- One row per entry
- Columns for each form field
- Submission metadata (date, status, IP)
- Field values normalized via `srfm_normalize_csv_field_value` filter

### CSV Field Value Normalization

The `srfm_normalize_csv_field_value` filter allows customizing how field values appear in CSV exports:

```php
add_filter( 'srfm_normalize_csv_field_value', function( $value, $field ) {
    // Custom formatting for specific field types
    return $value;
}, 10, 2 );
```

## Form Lifecycle Management

### REST Endpoint

`POST /wp-json/sureforms/v1/forms/manage`

**Parameters:**
- `form_ids` (required) -- Array of form IDs
- `action` (required) -- `trash`, `restore`, or `delete`

| Action | Behavior |
|--------|----------|
| `trash` | Move forms to trash (recoverable) |
| `restore` | Restore trashed forms |
| `delete` | Permanently delete forms |

## Create New Form

The `Create_New_Form` class (`inc/create-new-form.php`) handles new form creation with default meta values:

- Sets default styling options
- Configures default submit button text
- Initializes email notification defaults
- Applies default form confirmation settings

## Related Pages

- [REST API Reference](REST-API-Reference) -- All export/import endpoints
- [Database Schema](Database-Schema) -- Entry and form data structure
- [Admin Dashboard](Admin-Dashboard) -- UI for export/import operations
