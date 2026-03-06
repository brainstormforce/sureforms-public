# Impact Analyzer Memory

## Project Architecture: Abilities System

- **Base class**: `inc/abilities/abstract-ability.php` -- All 16 abilities extend this
- **Registrar**: `inc/abilities/abilities-registrar.php` -- Singleton, registers via `wp_abilities_api_init` hook
- **Filter**: `srfm_register_abilities` allows third-party ability injection (Pro does NOT use this)
- **Hooks**: `srfm_before_ability_execute` / `srfm_after_ability_execute` fire around execution
- **PHPUnit config**: `phpunit.xml.dist` uses recursive `tests/unit/` discovery -- no path-specific entries

## Pro Plugin Integration

- Pro extends core abilities via **filters only** (field types, descriptions, properties)
- Pro does NOT extend `Abstract_Ability` or register abilities via `srfm_register_abilities`
- Pro files: `sureforms-pro/inc/abilities/pro-abilities-registrar.php`, `extensions/create-form-extension.php`
- Pro hooks: `srfm_ability_form_field_types`, `srfm_ability_create_form_description`, `srfm_ability_form_field_properties`

## Key Option Names

- `srfm_abilities_api_edit` -- Gates write abilities (create, update, duplicate forms, update entries, update settings)
- `srfm_abilities_api_delete` -- Gates delete abilities (delete form, delete entry)
- `srfm_mcp_server` -- Enables MCP server registration
- `srfm_ai_settings_options` -- Grouped option for settings UI fetch

## High-Impact Files

- `inc/abilities/abstract-ability.php` -- Changes here affect ALL 16 abilities + any third-party
- `inc/abilities/abilities-registrar.php` -- Central registration; test expects count of 16
- `inc/global-settings/global-settings.php` -- REST endpoint for all settings; large surface area
- `src/admin/settings/Component.js` -- Fetches all settings; must match option names

## Test Coverage Gaps (as of 2026-03-06)

- `permission_callback()` gating logic has NO test coverage
- `register_mcp_server()` has NO test coverage
- `srfm_save_ai_settings()` has NO test coverage
- No CI config files found in repo (.github, .circleci, .gitlab-ci)

## Cross-Layer Dependency Chains

- PHP option (`srfm_abilities_api_edit`) -> Abstract_Ability::permission_callback() -> wp_register_ability() -> MCP/AI clients
- JS (`AI.js` toggle) -> REST API (`srfm-global-settings`) -> `Global_Settings::srfm_save_ai_settings()` -> `update_option()`
- Registrar filter count (16) is asserted in `test-abilities-registrar.php:88`

## File References (absolute paths)
- Abilities: `/Users/vanshkapoor/Local Sites/test/app/public/wp-content/plugins/sureforms/inc/abilities/`
- Tests: found via Glob under `tests/unit/inc/abilities/` (not directly under `tests/unit/inc/abilities/` -- they are in subdirectories)
- Pro abilities: `/Users/vanshkapoor/Local Sites/test/app/public/wp-content/plugins/sureforms-pro/inc/abilities/`
