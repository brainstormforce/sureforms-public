# Block Editor Controls

SureForms provides custom inspector controls and utilities for the Gutenberg block editor experience.

## Directory Structure

```
src/srfm-controls/
|-- addCommonData.js           # Inject common data into blocks
|-- generateCSS.js             # Dynamic CSS generation for blocks
|-- block-icons.js             # Block icon definitions
|-- fonts.js                   # Font management utilities
|-- maybeGetColorForVariable.js # CSS variable color resolution
|-- parseIcon.js               # SVG icon parser
|-- renderIcon.js              # Icon rendering component

src/blocks-attributes/
|-- getMetakeys.js             # Form meta key retrieval
|-- getFormId.js               # Get current form ID
|-- getBlocksDefaultAttributes.js # Block attribute defaults
```

## CSS Generation System

`generateCSS.js` dynamically generates CSS for block styling:

- Converts block attributes to CSS properties
- Supports responsive breakpoints (desktop, tablet, mobile)
- Generates inline styles injected into the page
- Handles CSS variable references

This system allows each block instance to have unique styling without class conflicts.

## Block Icons

`block-icons.js` provides SVG icon components for all SureForms blocks, used in:

- Block inserter panel
- Block toolbar
- Block settings sidebar

Additional icon sets from Spectra are available via `spectra-icons-v*.php` files.

Icon utilities:
- `parseIcon.js` -- Parses SVG string into React-renderable format
- `renderIcon.js` -- React component for rendering parsed icons

## Common Data Injection

`addCommonData.js` injects shared data into block components:

- Form-level settings accessible to all child blocks
- Common attributes and defaults
- Shared configuration values

## Meta Key Integration

`getMetakeys.js` retrieves form post meta keys for use in block controls:

- Provides meta key names for form settings
- Used by inspector controls to read/write form meta
- Integrates with `@wordpress/data` store

## Block Attribute Defaults

`getBlocksDefaultAttributes.js` provides default attribute values for all block types:

- Ensures consistent defaults across block instances
- Used during block registration
- Prevents undefined attribute errors

## Form ID Resolution

`getFormId.js` resolves the current form's post ID:

- Used by blocks to identify their parent form
- Required for meta key operations
- Supports nested block contexts

## Field Preview

`src/blocks/FieldsPreview.jsx` renders a preview of form fields in the block editor:

- Shows a visual representation of the field in editor mode
- Responds to attribute changes in real-time
- Handles edit vs preview states

## Font Management

`fonts.js` manages font loading for form styling:

- Font family selection in block settings
- Google Fonts integration
- Font weight and style options

## Color Variable Resolution

`maybeGetColorForVariable.js` resolves WordPress CSS custom properties:

- Maps color slugs to CSS variable references
- Supports theme color palette integration
- Falls back to raw color values when variables are not available

## Related Pages

- [Gutenberg Blocks](Gutenberg-Blocks) -- Block types and registration
- [State Management](State-Management) -- WordPress data store
- [Frontend Assets](Frontend-Assets) -- Editor asset loading
- [Form Fields Architecture](Form-Fields-Architecture) -- Field types
