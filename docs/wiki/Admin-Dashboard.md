# Admin Dashboard

The SureForms admin dashboard is a React single-page application (SPA) integrated into the WordPress admin area.

## Architecture

### Entry Point

The admin React app is bootstrapped in `src/admin/editor-scripts.js`, which serves as the Webpack entry point. It mounts the React app to a DOM container registered by the PHP `Admin` class.

### Tech Stack

| Technology | Purpose |
|------------|---------|
| React 18 | UI rendering |
| react-router-dom 6 | Client-side routing |
| @tanstack/react-query 5 | Server state management and data fetching |
| @wordpress/api-fetch | REST API client with nonce handling |
| @wordpress/data | WordPress data store integration |
| @bsf/force-ui | BSF component library (buttons, modals, inputs, etc.) |
| Tailwind CSS 3 | Utility-first styling |
| react-hot-toast | Toast notifications |

### Admin Pages

The React app includes the following pages/routes:

| Route | Component | Description |
|-------|-----------|-------------|
| Dashboard | `src/admin/dashboard/` | Overview with charts and stats |
| Forms | `src/admin/forms/` | Forms listing with search, filter, bulk actions |
| Entries | `src/admin/entries/` | Entry listing and management |
| Settings | `src/admin/settings/` | Global plugin settings |
| Payments | `src/admin/payment/` | Payment transactions listing |
| Single Form Settings | `src/admin/single-form-settings/` | Per-form configuration |

### Shared Components

Common admin components are in `src/admin/components/`:

- `PageHeader` -- Consistent page header with title and actions
- `TemplatePicker` -- Form template selection UI
- Loading skeletons (via `react-loading-skeleton`)
- Modal dialogs and confirmation prompts

## WordPress Integration

### Menu Registration

The PHP `Admin` class (`admin/admin.php`) registers the WordPress admin menu:

```php
add_menu_page(
    'SureForms',
    'SureForms',
    'manage_options',
    'sureforms_menu',
    [ $this, 'render_admin_page' ],
    'dashicons-feedback',
    30
);
```

### Asset Enqueuing

Admin assets are enqueued only on SureForms admin pages:

- React app bundle (from `assets/build/`)
- Tailwind CSS styles
- Localized data via `wp_localize_script()` providing:
  - REST API URL and nonce
  - Current user data
  - Plugin settings
  - Form data

## Data Fetching

### @tanstack/react-query

Used for server state management with automatic caching, refetching, and invalidation:

```javascript
const { data, isLoading } = useQuery({
    queryKey: ['entries', filters],
    queryFn: () => apiFetch({ path: '/sureforms/v1/entries/list', ... }),
});
```

### @wordpress/api-fetch

WordPress API client with automatic nonce injection:

```javascript
import apiFetch from '@wordpress/api-fetch';

apiFetch({ path: '/sureforms/v1/form-data' })
    .then(data => { /* handle response */ });
```

## Force UI Component Library

`@bsf/force-ui` is BSF's internal component library providing:

- Buttons, inputs, selects, toggles
- Modals and dialogs
- Tables and data display
- Navigation components
- Consistent design system

Components are imported directly:

```javascript
import { Button, Modal, Input } from '@bsf/force-ui';
```

## Related Pages

- [State Management](State-Management) -- WordPress data store
- [Frontend Assets](Frontend-Assets) -- Asset loading pipeline
- [REST API Reference](REST-API-Reference) -- API endpoints used by dashboard
- [Environment Configuration](Environment-Configuration) -- Build setup
