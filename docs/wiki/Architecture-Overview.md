# Architecture Overview

This page describes the high-level architecture of the SureForms WordPress plugin (v2.5.1), covering the bootstrap flow, namespace conventions, directory layout, design patterns, and data flow.

## High-Level Architecture Diagram

```
+------------------------------------------------------+
|                    WordPress Core                     |
|  (hooks: plugins_loaded, init, rest_api_init, etc.)   |
+---------------------------+--------------------------+
                            |
                   +--------v---------+
                   |  sureforms.php   |
                   | (entry point,    |
                   |  constants)      |
                   +--------+---------+
                            |
                   +--------v---------+
                   | plugin-loader.php|
                   | (Plugin_Loader)  |
                   | - autoloader     |
                   | - hook setup     |
                   | - class init     |
                   +--------+---------+
                            |
         +------------------+------------------+
         |                  |                  |
   plugins_loaded(99)     init           admin_init
   load_plugin()       load_classes()   activation_redirect()
         |                  |
  +------v------+    +------v------+
  | Core Classes|    | Admin/Block |
  | Post_Types  |    | Register    |
  | Form_Submit |    | Admin       |
  | Rest_Api    |    | Payments    |
  | Helper      |    | Duplicate   |
  | ...20+ more |    +-------------+
  +------+------+
         |
  +------v------+    +-------------+    +-------------+
  | Database    |    | REST API    |    | Frontend    |
  | Register    |    | /sureforms/ |    | Assets      |
  | Entries tbl |    | v1/...      |    | Form Markup |
  | Payments tbl|    +-------------+    +-------------+
  +-------------+
```

## Plugin Bootstrap Flow

### 1. Entry Point: sureforms.php

The main plugin file defines all global constants and then requires `plugin-loader.php`:

```php
define( 'SRFM_FILE', __FILE__ );
define( 'SRFM_DIR', plugin_dir_path( SRFM_FILE ) );
define( 'SRFM_URL', plugins_url( '/', SRFM_FILE ) );
define( 'SRFM_VER', '2.5.1' );
define( 'SRFM_FORMS_POST_TYPE', 'sureforms_form' );
define( 'SRFM_ENTRIES', 'sureforms_entries' );
define( 'SRFM_PAYMENTS', 'sureforms_payments' );
require_once 'plugin-loader.php';
```

### 2. Plugin Loader: plugin-loader.php

The `SRFM\Plugin_Loader` class is the central bootstrap orchestrator:

1. Loads the Action Scheduler library from `inc/lib/action-scheduler/action-scheduler.php`
2. Registers the PSR-4-style autoloader via `spl_autoload_register()`
3. Hooks into WordPress lifecycle events:
   - `plugins_loaded` (default priority) -- `load_textdomain()`
   - `plugins_loaded` (priority 99) -- `load_plugin()` (main class initialization)
   - `init` -- `load_classes()` (block registration, admin, payments)
   - `admin_init` -- `activation_redirect()`
4. Instantiates `Analytics` singleton immediately
5. Registers activation hook (`Activator::activate()`)
6. Registers deactivation hook (clears scheduled events)

The `Plugin_Loader` fires the `srfm_core_loaded` action after first instantiation.

### 3. Class Initialization Order

**On `plugins_loaded` (priority 99) via `load_plugin()`:**

| Order | Class | Responsibility |
|-------|-------|---------------|
| 1 | `Post_Types` | Registers `sureforms_form` CPT and all post meta |
| 2 | `Form_Submit` | Registers `/submit-form` and `/refresh-nonces` REST endpoints |
| 3 | `Gutenberg_Hooks` | Block editor integration hooks |
| 4 | `Frontend_Assets` | Frontend CSS/JS enqueue |
| 5 | `Helper` | Utility/helper functions |
| 6 | `Activator` | First-run setup and migrations |
| 7 | `Admin_Ajax` | WordPress AJAX handlers |
| 8 | `Forms_Data` | Form data queries and listing |
| 9 | `Export` | Import/export functionality |
| 10 | `Smart_Tags` | Template tag processing |
| 11 | `Generate_Form_Markup` | Form HTML rendering |
| 12 | `Create_New_Form` | New form creation flow with default meta |
| 13 | `Global_Settings` | Plugin-wide settings management |
| 14 | `Email_Summary` | Scheduled email digest reports |
| 15 | `Compliance_Settings` | GDPR/privacy per-form settings |
| 16 | `Events_Scheduler` | Cron-based scheduled events |
| 17 | `AI_Form_Builder` | AI-powered form generation |
| 18 | `Field_Mapping` | AI response to Gutenberg block mapping |
| 19 | `Background_Process` | Background task processing via Action Scheduler |
| 20 | `Page_Builders` | Third-party page builder integrations |
| 21 | `Rest_Api` | Admin REST endpoint registration (20+ routes) |
| 22 | `AI_Helper` | AI utility functions |
| 23 | `AI_Auth` | AI authentication and billing portal |
| 24 | `Updater` | Plugin update logic |
| 25 | `Onboarding` | First-run onboarding wizard |
| 26 | `DatabaseRegister` | Custom table creation/migration |
| 27 | `Form_Restriction` | Submission limits, scheduling, and entry caps |
| 28 | `Astra` | Astra theme compatibility layer |

**On `init` via `load_classes()`:**

| Class | Condition | Responsibility |
|-------|-----------|---------------|
| `Blocks\Register` | Always | Gutenberg block type registration |
| `Admin` | `is_admin()` only | Admin menu pages, assets, and screens |
| `Notice_Manager` | `is_admin()` only | Admin notice display and management |
| `Payments` | Always | Payment gateway setup (Stripe) |
| `Duplicate_Form` | Always | Form duplication handler |

## Namespace and Autoloading

### Namespace Structure

The root namespace is `SRFM`. All classes follow `SRFM\{directory}\{ClassName}`:

```
SRFM\                                -> plugin-loader.php
SRFM\Inc\Helper                      -> inc/helper.php
SRFM\Inc\Rest_Api                    -> inc/rest-api.php
SRFM\Inc\Form_Submit                 -> inc/form-submit.php
SRFM\Inc\Database\Base               -> inc/database/base.php
SRFM\Inc\Database\Tables\Entries     -> inc/database/tables/entries.php
SRFM\Inc\AI_Form_Builder\*           -> inc/ai-form-builder/
SRFM\Inc\Traits\Get_Instance         -> inc/traits/get-instance.php
SRFM\Admin\Admin                     -> admin/admin.php
```

### Autoloader Logic

The autoloader in `Plugin_Loader::autoload()`:

1. Strips the root `SRFM\` namespace prefix
2. Converts `CamelCase` to `kebab-case`
3. Replaces underscores with hyphens
4. Replaces namespace separators with directory separators
5. Converts to lowercase, prepends `SRFM_DIR`, appends `.php`
6. Rejects any path containing `..` (directory traversal protection)

## Directory Layout

```
sureforms/
|-- sureforms.php              # Plugin entry point, all SRFM_* constants
|-- plugin-loader.php          # Autoloader, bootstrap, hook registration
|-- inc/                       # Core PHP classes (SRFM\Inc namespace)
|   |-- helper.php             # Static utility methods
|   |-- rest-api.php           # REST API endpoint registration
|   |-- form-submit.php        # Public form submission handler
|   |-- post-types.php         # sureforms_form CPT and post meta
|   |-- entries.php            # Entry CRUD operations
|   |-- blocks/                # Gutenberg block server-side rendering
|   |-- database/              # Custom DB table management
|   |-- fields/                # Form field type definitions and validation
|   |-- payments/              # Payment gateway integrations (Stripe)
|   |-- email/                 # Email template rendering
|   |-- ai-form-builder/       # AI-powered form generation
|   |-- global-settings/       # Plugin-wide settings
|   |-- single-form-settings/  # Per-form configuration
|   |-- compatibility/         # Theme/plugin compatibility
|   |-- page-builders/         # Page builder integrations
|   |-- traits/                # Shared PHP traits (Get_Instance)
|   |-- lib/                   # Bundled third-party libraries (do not modify)
|-- src/                       # JS/React source (compiled by wp-scripts)
|   |-- admin/                 # Admin dashboard React app
|   |-- blocks/                # Gutenberg block editor components
|   |-- components/            # Shared React components
|   |-- store/                 # WordPress @wordpress/data store
|   |-- utils/                 # JS utilities
|-- modules/                   # Feature modules (Gutenberg, Quick Action Sidebar)
|-- admin/                     # Admin PHP classes and assets
|-- sass/                      # SASS source files (compiled to assets/css/)
|-- assets/                    # Compiled and static assets
|-- templates/                 # PHP templates (BladeOne engine)
|-- tests/                     # PHPUnit, Playwright, and Docker setup
```

## Design Patterns

### Singleton via Get_Instance Trait

Nearly every class uses the `Get_Instance` trait from `inc/traits/get-instance.php`:

```php
trait Get_Instance {
    private static $instance = null;
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
```

### Abstract Base Class (Database)

The database layer uses `SRFM\Inc\Database\Base` providing:

- Table creation via `dbDelta()`
- Schema versioning with integer versions stored in options
- Column migration (`maybe_add_new_columns()`, `maybe_rename_columns()`)
- CRUD wrappers (`use_insert()`, `use_update()`, `use_delete()`)
- Query builder with parameterized WHERE supporting `=`, `!=`, `>`, `<`, `LIKE`, `IN`
- In-memory result caching keyed by MD5 of SQL query
- Schema-driven JSON encoding/decoding for array columns

### Hook-Based Initialization

Classes register WordPress hooks in their constructors, invoked during singleton instantiation in `load_plugin()` or `load_classes()`.

### Filter-Driven Extensibility

| Filter | Purpose |
|--------|---------|
| `srfm_rest_api_endpoints` | Extend REST API routes |
| `srfm_register_post_meta` | Register additional form post meta |
| `srfm_form_submit_data` | Modify form data before processing |
| `srfm_entry_value` | Transform entry field values |
| `srfm_is_special_block` | Declare custom blocks as special |
| `srfm_handle_special_block` | Custom processing for special blocks |
| `srfm_form_confirmation_params` | Extend form confirmation settings |
| `srfm_normalize_csv_field_value` | Custom CSV export formatting |

## Data Flow

```
1. FORM CREATION
   Admin creates form in Gutenberg block editor
   -> Saved as sureforms_form CPT (wp_posts)
   -> Settings stored as post meta (_srfm_* keys)
   -> Block content in post_content column

2. FORM DISPLAY
   Form embedded via shortcode, Gutenberg block, or Instant Form URL
   -> Generate_Form_Markup::get_form_markup() renders HTML
   -> Frontend_Assets enqueues CSS/JS
   -> Submission nonce generated

3. FORM SUBMISSION
   User submits via JavaScript fetch()
   -> POST /wp-json/sureforms/v1/submit-form
   -> Nonce verification, field validation
   -> CAPTCHA validation (reCAPTCHA, hCaptcha, Turnstile)
   -> Honeypot spam check

4. ENTRY STORAGE
   -> JSON-encoded form data inserted into wp_srfm_entries
   -> Email notifications sent
   -> Payment processed if applicable (wp_srfm_payments)
   -> Confirmation response returned

5. POST-SUBMISSION
   -> srfm_form_submit and srfm_after_submission_process actions fired
```

## Key Constants

| Constant | Value | Purpose |
|----------|-------|---------|
| `SRFM_FILE` | `__FILE__` | Main plugin file path |
| `SRFM_DIR` | Plugin dir path | File system operations |
| `SRFM_URL` | Plugin URL | Asset URL generation |
| `SRFM_VER` | `2.5.1` | Version for cache busting |
| `SRFM_SLUG` | `srfm` | Short identifier slug |
| `SRFM_FORMS_POST_TYPE` | `sureforms_form` | Custom post type slug |
| `SRFM_ENTRIES` | `sureforms_entries` | Entries identifier |
| `SRFM_PAYMENTS` | `sureforms_payments` | Payments identifier |
| `SRFM_PRO_RECOMMENDED_VER` | `2.5.1` | Minimum Pro version |

## Related Pages

- [Database Schema](Database-Schema) -- Custom tables, post types, and meta keys
- [Environment Configuration](Environment-Configuration) -- Development setup, build tools, and linting
- [REST API Reference](REST-API-Reference) -- Full endpoint documentation
- [Form Submission Flow](Form-Submission-Flow) -- Detailed submission pipeline
- [WordPress Hooks Reference](WordPress-Hooks-Reference) -- All actions and filters
