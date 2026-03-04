# Abilities — Development Guide

## Adding a New Ability

1. Create a class extending `Abstract_Ability` in the appropriate subdirectory:
   - `forms/` — form CRUD operations
   - `entries/` — entry/submission operations
   - `embedding/` — shortcodes, block markup, rendering
2. Register it in `Abilities_Registrar::register_abilities()`

## Abstract_Ability Contract

Every ability must implement:
- **Constructor** — set `$id`, `$label`, `$description`, `$capability`
- **`get_input_schema()`** — JSON Schema for input validation
- **`get_output_schema()`** — JSON Schema for output contract
- **`execute( $input )`** — business logic, return array or `WP_Error`
- **`get_annotations()`** — override if not default (`readonly`, `destructive`, `idempotent`)

## Conventions

- ID format: `sureforms/<verb>-<noun>` (e.g. `sureforms/list-forms`)
- Namespace: `SRFM\Inc\Abilities\<Subdirectory>\<Class_Name>`
- Return `WP_Error` for failures, never throw exceptions
- Default capability: `edit_posts` — override per-ability where needed
- Destructive abilities must set `destructive: true` in annotations
- All classes need `@since x.x.x` tags
- Add PHPUnit tests in `tests/unit/inc/abilities/` for every new ability

## Architecture

```
inc/abilities/
├── abstract-ability.php        # Base class — do not modify without updating all abilities
├── abilities-registrar.php     # Singleton — registers category + all abilities
├── forms/
│   ├── list-forms.php          # readonly, idempotent
│   ├── create-form.php         # uses Field_Mapping engine
│   ├── get-form.php            # readonly, idempotent — parses Gutenberg blocks
│   ├── delete-form.php         # destructive
│   ├── duplicate-form.php      # delegates to \SRFM\Inc\Duplicate_Form
│   ├── update-form.php         # write, idempotent — title/status/metadata
│   └── get-form-stats.php      # readonly, idempotent — entry counts
├── entries/
│   ├── list-entries.php        # readonly, idempotent — paginated listing
│   ├── get-entry.php           # readonly, idempotent — decrypted form_data
│   ├── update-entry-status.php # write, idempotent — read/unread/trash/restore
│   └── delete-entry.php        # destructive — permanent delete
└── embedding/
    └── get-shortcode.php       # readonly, idempotent
```

## Extensibility

- **Filter:** `srfm_register_abilities` — third-party plugins can add abilities (must extend `Abstract_Ability`)
- **Hooks:** `srfm_before_ability_execute` / `srfm_after_ability_execute` — fire around every execution
- **Collision guard:** Registrar checks `wp_has_ability()` before registering to avoid duplicates with zipwp-mcp
- **WP < 6.9:** Registrar bails in constructor — zero overhead when Abilities API is unavailable
