# REST API Reference

All endpoints are registered under the namespace `sureforms/v1` and accessible at `/wp-json/sureforms/v1/`.

## Authentication

### Admin Endpoints
All admin endpoints require the `manage_options` capability. Authentication is via WordPress REST API nonce (`X-WP-Nonce` header) or cookie-based auth.

Permission check: `Helper::get_items_permissions_check()` verifies `current_user_can('manage_options')`.

### Public Endpoints
The form submission endpoint uses a custom nonce (`X-WP-Submit-Nonce` header) that does not require a logged-in user.

## Endpoints

### AI Form Builder

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/generate-form` | Generate a form using AI |
| POST | `/map-fields` | Map AI response to SureForms Gutenberg blocks |
| GET | `/initiate-auth` | Get auth URL for AI billing portal |
| POST | `/handle-access-key` | Decrypt and save AI access key |

### Dashboard

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/entries-chart-data` | Get entries chart data for analytics |
| GET | `/form-data` | Get all forms data for dashboard |
| GET | `/plugin-status` | Check integration plugin install status |

### Onboarding

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/onboarding/set-status` | Mark onboarding as complete |
| GET | `/onboarding/get-status` | Get current onboarding status |

### Entries

| Method | Endpoint | Description | Parameters |
|--------|----------|-------------|------------|
| GET | `/entries/list` | Paginated entries listing | `form_id`, `status`, `search`, `date_from`, `date_to`, `orderby`, `order`, `per_page`, `page` |
| POST | `/entries/read-status` | Bulk mark entries read/unread | `entry_ids` (required), `action` (required: read/unread) |
| POST | `/entries/trash` | Bulk trash/restore entries | `entry_ids` (required), `action` (required: trash/restore) |
| POST | `/entries/delete` | Permanently delete entries | `entry_ids` (required) |
| POST | `/entries/export` | Export entries to CSV/ZIP | `entry_ids`, `form_id`, `status`, `search`, `date_from`, `date_to` |
| GET | `/entry/{id}/details` | Single entry with form data | `id` (required) |
| GET | `/entry/{id}/logs` | Entry activity logs | `id` (required), `page`, `per_page` |

### Forms

| Method | Endpoint | Description | Parameters |
|--------|----------|-------------|------------|
| GET | `/forms` | Paginated forms listing | `page`, `per_page`, `search`, `status`, `orderby`, `order`, `date_from`, `date_to` |
| POST | `/forms/export` | Export forms as JSON | `form_ids` (required) |
| POST | `/forms/import` | Import forms from JSON | File upload |
| POST | `/forms/manage` | Manage form lifecycle | `form_ids` (required), `action` (trash/restore/delete) |
| POST | `/forms/duplicate` | Duplicate a form | `form_id` (required) |

### Form Submission (Public)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/submit-form` | Custom nonce | Public form submission |
| GET | `/refresh-nonces` | None | Refresh frontend nonces |

## Extending the API

Endpoints are extensible via the `srfm_rest_api_endpoints` filter:

```php
add_filter( 'srfm_rest_api_endpoints', function( $endpoints ) {
    $endpoints['custom/endpoint'] = [
        'methods'             => 'GET',
        'callback'            => [ $this, 'handle_custom' ],
        'permission_callback' => [ Helper::class, 'get_items_permissions_check' ],
    ];
    return $endpoints;
} );
```

## Error Responses

Errors are returned via `wp_send_json_error()`:

```json
{
  "success": false,
  "data": "Error message string"
}
```

## Pagination

List endpoints support pagination with consistent parameters:

| Parameter | Default | Description |
|-----------|---------|-------------|
| `page` | 1 | Current page number |
| `per_page` | 20 | Items per page |
| `orderby` | `created_at` | Sort column |
| `order` | `DESC` | Sort direction (ASC/DESC) |

Response includes total counts for pagination UI.

## Related Pages

- [Architecture Overview](Architecture-Overview) -- API registration flow
- [Form Submission Flow](Form-Submission-Flow) -- Submit endpoint details
- [Data Export Import](Data-Export-Import) -- Export/import endpoints
- [Payment Integration](Payment-Integration) -- Payment API endpoints
