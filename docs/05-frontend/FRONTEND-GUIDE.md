# SureForms Frontend Development Guide

> **Purpose:** Comprehensive guide for React/Gutenberg block development
> **Audience:** Frontend developers working with React, Gutenberg, and WordPress
> **Last Updated:** 2026-02-06
> **Plugin Version:** 2.5.0

## Overview

SureForms frontend is built with modern JavaScript technologies integrated deeply with WordPress's Gutenberg block editor:

- **React 18.2:** UI components and interactive features
- **Gutenberg Blocks:** WordPress block editor integration for form building
- **TailwindCSS 3.4:** Utility-first CSS framework
- **React Query 5.x:** Server state management and caching
- **@wordpress/scripts 26.2:** Build tooling (Webpack 5 wrapper)

---

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Directory Structure](#directory-structure)
- [Creating Gutenberg Blocks](#creating-gutenberg-blocks)
- [State Management](#state-management)
- [Component Patterns](#component-patterns)
- [Styling](#styling)
- [Form Validation](#form-validation)
- [Internationalization](#internationalization)
- [Performance Optimization](#performance-optimization)
- [Testing](#testing)

---

## Architecture Overview

### Technology Stack

```
┌─────────────────────────────────────────┐
│           Admin UI (React Apps)         │
│  Dashboard, Settings, Entries, Editor   │
└──────────────┬──────────────────────────┘
               │
    ┌──────────┴──────────┐
    │                     │
┌───▼────────┐    ┌──────▼──────┐
│ Gutenberg  │    │   Admin     │
│   Blocks   │    │   Pages     │
│  (Editor)  │    │  (Router)   │
└───┬────────┘    └──────┬──────┘
    │                    │
    └──────────┬─────────┘
               │
    ┌──────────▼──────────┐
    │  State Management   │
    │ Data Store + Hooks  │
    └──────────┬──────────┘
               │
    ┌──────────▼──────────┐
    │    REST API         │
    │ /sureforms/v1/*     │
    └─────────────────────┘
```

### Component Layers

1. **Gutenberg Block Layer** - Form builder blocks (src/blocks/)
2. **Admin UI Layer** - React SPA pages (src/admin/)
3. **Shared Components** - Reusable UI (src/components/)
4. **State Layer** - Data stores (src/store/)
5. **API Layer** - WordPress REST API

---

## Directory Structure

```
src/
├── blocks/                        # Gutenberg block components
│   ├── blocks.js                  # Block registration entry
│   ├── register-block.js          # Block utilities
│   │
│   ├── sform/                     # Form container block
│   │   ├── index.js               # Block registration
│   │   ├── edit.js                # Editor component
│   │   ├── save.js                # Frontend save (returns null)
│   │   ├── block.json             # Block metadata
│   │   ├── components/            # Block-specific components
│   │   └── style.scss             # Block styles
│   │
│   ├── input/                     # Text input field block
│   │   ├── edit.js                # Editor with InspectorControls
│   │   ├── save.js                # Server-side rendering
│   │   └── block.json             # Attributes schema
│   │
│   └── [14+ more blocks]          # Email, textarea, checkbox, etc.
│
├── admin/                         # Admin dashboard React apps
│   ├── dashboard/                 # Forms listing page
│   │   ├── index.js               # Entry point
│   │   ├── FormsTable.js          # Table component
│   │   ├── Analytics.js           # Analytics widgets
│   │   └── styles.scss
│   │
│   ├── single-form-settings/      # Form editor
│   │   ├── Editor.js              # Main editor wrapper
│   │   ├── InstantForm.js         # Live preview
│   │   ├── tabs/                  # Settings tabs
│   │   │   ├── GeneralSettings.js
│   │   │   └── StyleSettings.js
│   │   └── components/            # Editor components
│   │
│   ├── entries/                   # Entry management
│   ├── settings/                  # Global settings
│   ├── payment/                   # Payment settings
│   └── onboarding/                # Setup wizard
│
├── components/                    # Shared React components
│   ├── ui/                        # UI primitives
│   │   ├── Button/
│   │   ├── Input/
│   │   ├── Modal/
│   │   └── Tooltip/
│   │
│   ├── form/                      # Form-specific
│   │   ├── FieldWrapper/
│   │   ├── ValidationMessage/
│   │   └── FormContainer/
│   │
│   ├── inspector-tabs/            # Block editor tabs
│   ├── color-switch-control/      # Color picker
│   ├── spacing-control/           # Responsive spacing
│   └── [40+ component dirs]
│
├── srfm-controls/                 # Gutenberg block controls
│   ├── index.js                   # Control registry
│   ├── getPreviewType.js          # Responsive preview
│   └── [18+ control definitions]
│
├── store/                         # WordPress Data Store (Redux)
│   ├── store.js                   # Store creation
│   ├── actions.js                 # Action creators
│   ├── reducer.js                 # Reducer function
│   ├── selectors.js               # Selectors
│   └── constants.js               # Action types
│
├── utils/                         # Utility functions
│   ├── Helpers.js                 # Common helpers
│   ├── datePickerHelper.js
│   └── addressFieldHelper.js
│
└── styles/                        # Global styles
    ├── typography/
    └── breakpoints/
```

**Build Output:**
```
assets/build/
├── formEditor.js       # Form editor bundle (~1.9MB)
├── blocks.js           # Gutenberg blocks (~1.3MB)
├── dashboard.js        # Dashboard page (~1.3MB)
├── entries.js          # Entries page (~728KB)
├── forms.js            # Forms listing (~727KB)
├── settings.js         # Settings page (~1.1MB)
├── formSubmit.js       # Frontend submission (~60KB)
└── *.asset.php         # Webpack dependency manifests
```

---

## Creating Gutenberg Blocks

### Block Anatomy

Every form field block consists of:
1. **block.json** - Metadata and attributes schema
2. **edit.js** - Editor component (React)
3. **save.js** - Frontend rendering (usually null, PHP renders)
4. **style.scss** - Block-specific styles
5. **PHP class** - Server-side registration and rendering

### Step-by-Step: Create a New Block

#### Step 1: Create Block Directory

```bash
mkdir -p src/blocks/custom-field
cd src/blocks/custom-field
```

#### Step 2: Create block.json

**File:** [src/blocks/custom-field/block.json](../../src/blocks/custom-field/block.json)

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 2,
    "name": "sureforms/custom-field",
    "title": "Custom Field",
    "category": "sureforms",
    "icon": "text",
    "description": "A custom form field",
    "keywords": ["form", "custom", "field"],
    "supports": {
        "html": false,
        "anchor": false,
        "customClassName": false
    },
    "attributes": {
        "label": {
            "type": "string",
            "default": "Custom Field"
        },
        "slug": {
            "type": "string",
            "default": "custom_field"
        },
        "placeholder": {
            "type": "string",
            "default": ""
        },
        "required": {
            "type": "boolean",
            "default": false
        },
        "helpText": {
            "type": "string",
            "default": ""
        }
    },
    "editorScript": "file:./index.js",
    "editorStyle": "file:./editor.scss",
    "style": "file:./style.scss"
}
```

#### Step 3: Create edit.js (Editor Component)

**File:** [src/blocks/custom-field/edit.js](../../src/blocks/custom-field/edit.js)

```javascript
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl, TextareaControl } from '@wordpress/components';

export default function Edit({ attributes, setAttributes, clientId }) {
    const { label, slug, placeholder, required, helpText } = attributes;

    const blockProps = useBlockProps({
        className: 'srfm-block srfm-custom-field-block',
    });

    return (
        <>
            {/* Inspector Panel (Right Sidebar) */}
            <InspectorControls>
                <PanelBody title={__('Field Settings', 'sureforms')}>
                    <TextControl
                        label={__('Label', 'sureforms')}
                        value={label}
                        onChange={(value) => setAttributes({ label: value })}
                        help={__('Field label shown to users', 'sureforms')}
                    />

                    <TextControl
                        label={__('Field Slug', 'sureforms')}
                        value={slug}
                        onChange={(value) => setAttributes({ slug: value })}
                        help={__('Unique identifier for this field', 'sureforms')}
                    />

                    <TextControl
                        label={__('Placeholder', 'sureforms')}
                        value={placeholder}
                        onChange={(value) => setAttributes({ placeholder: value })}
                    />

                    <ToggleControl
                        label={__('Required', 'sureforms')}
                        checked={required}
                        onChange={(value) => setAttributes({ required: value })}
                    />

                    <TextareaControl
                        label={__('Help Text', 'sureforms')}
                        value={helpText}
                        onChange={(value) => setAttributes({ helpText: value })}
                        help={__('Additional instructions for users', 'sureforms')}
                    />
                </PanelBody>
            </InspectorControls>

            {/* Block Preview in Editor */}
            <div {...blockProps}>
                <div className="srfm-field-wrapper">
                    <label className="srfm-field__label">
                        {label}
                        {required && <span className="required">*</span>}
                    </label>

                    <input
                        type="text"
                        className="srfm-field__input"
                        placeholder={placeholder}
                        disabled
                    />

                    {helpText && (
                        <span className="srfm-field__help-text">
                            {helpText}
                        </span>
                    )}
                </div>
            </div>
        </>
    );
}
```

#### Step 4: Create save.js

**File:** [src/blocks/custom-field/save.js](../../src/blocks/custom-field/save.js)

```javascript
/**
 * Save function - return null for dynamic blocks (rendered server-side).
 *
 * SureForms blocks are rendered server-side via PHP for:
 * - Better security (no client-side data exposure)
 * - Consistent rendering across contexts
 * - Easier form submission handling
 */
export default function save() {
    return null;
}
```

#### Step 5: Create index.js (Registration)

**File:** [src/blocks/custom-field/index.js](../../src/blocks/custom-field/index.js)

```javascript
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import save from './save';
import metadata from './block.json';

// Register block
registerBlockType(metadata.name, {
    ...metadata,
    edit: Edit,
    save,
});
```

#### Step 6: Add to blocks.js

**File:** [src/blocks/blocks.js](../../src/blocks/blocks.js)

```javascript
// ... other imports
import './custom-field';
```

#### Step 7: Create PHP Server-Side Rendering

**File:** [inc/blocks/custom-field/class-custom-field.php](../../inc/blocks/custom-field/class-custom-field.php)

```php
<?php
namespace SRFM\Inc\Blocks\CustomField;

use SRFM\Inc\Blocks\Base;
use SRFM\Inc\Traits\Get_Instance;

class Custom_Field extends Base {
    use Get_Instance;

    protected $block_name = 'custom-field';

    /**
     * Render callback for frontend.
     *
     * @param array $attributes Block attributes.
     * @param string $content Block content.
     * @return string HTML output.
     */
    public function render_callback($attributes, $content) {
        $label = $attributes['label'] ?? '';
        $slug = $attributes['slug'] ?? 'custom_field';
        $placeholder = $attributes['placeholder'] ?? '';
        $required = $attributes['required'] ?? false;
        $help_text = $attributes['helpText'] ?? '';

        ob_start();
        ?>
        <div class="srfm-field srfm-custom-field">
            <label for="<?php echo esc_attr($slug); ?>" class="srfm-field__label">
                <?php echo esc_html($label); ?>
                <?php if ($required): ?>
                    <span class="required">*</span>
                <?php endif; ?>
            </label>

            <input
                type="text"
                id="<?php echo esc_attr($slug); ?>"
                name="<?php echo esc_attr($slug); ?>"
                class="srfm-field__input"
                placeholder="<?php echo esc_attr($placeholder); ?>"
                <?php echo $required ? 'required' : ''; ?>
                <?php echo $required ? 'aria-required="true"' : ''; ?>
            />

            <?php if ($help_text): ?>
                <span class="srfm-field__help-text">
                    <?php echo esc_html($help_text); ?>
                </span>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
```

#### Step 8: Register PHP Class

**File:** [inc/blocks/register.php](../../inc/blocks/register.php)

```php
use SRFM\Inc\Blocks\CustomField\Custom_Field;

// Register custom field block
Custom_Field::get_instance();
```

#### Step 9: Build and Test

```bash
# Build assets
npm run build

# Hard refresh browser (Cmd/Ctrl + Shift + R)

# Test in block editor:
# 1. Create/edit form
# 2. Click "+" to add block
# 3. Find "Custom Field" in inserter
# 4. Add to form
# 5. Test settings in Inspector panel
# 6. Save and preview frontend
```

---

## State Management

SureForms uses **three layers** of state management:

### 1. Local Component State (useState)

For component-specific state that doesn't need to be shared.

```javascript
import { useState } from 'react';

function FormField({ initialValue }) {
    const [value, setValue] = useState(initialValue);
    const [error, setError] = useState('');

    const handleChange = (e) => {
        setValue(e.target.value);

        // Validate
        if (e.target.value.length < 3) {
            setError('Minimum 3 characters');
        } else {
            setError('');
        }
    };

    return (
        <div>
            <input value={value} onChange={handleChange} />
            {error && <span className="error">{error}</span>}
        </div>
    );
}
```

### 2. WordPress Data Store (Redux-based)

For block editor state and form metadata.

**Define Store** ([src/store/store.js](../../src/store/store.js)):

```javascript
import { createReduxStore, register } from '@wordpress/data';
import apiFetch from '@wordpress/api-fetch';

const DEFAULT_STATE = {
    formSettings: {},
    blockAttributes: {},
    isLoading: false,
};

const actions = {
    setFormSettings(settings) {
        return {
            type: 'SET_FORM_SETTINGS',
            settings,
        };
    },

    updateBlockAttribute(blockId, attribute, value) {
        return {
            type: 'UPDATE_BLOCK_ATTRIBUTE',
            blockId,
            attribute,
            value,
        };
    },

    *fetchFormSettings(formId) {
        yield { type: 'SET_LOADING', isLoading: true };

        try {
            const settings = yield apiFetch({
                path: `/sureforms/v1/forms/${formId}/settings`,
            });

            return actions.setFormSettings(settings);
        } catch (error) {
            console.error('Failed to fetch settings:', error);
            return { type: 'FETCH_ERROR', error };
        }
    },
};

const selectors = {
    getFormSettings(state) {
        return state.formSettings;
    },

    getBlockAttribute(state, blockId, attribute) {
        return state.blockAttributes[blockId]?.[attribute];
    },

    isLoading(state) {
        return state.isLoading;
    },
};

const reducer = (state = DEFAULT_STATE, action) => {
    switch (action.type) {
        case 'SET_FORM_SETTINGS':
            return {
                ...state,
                formSettings: action.settings,
                isLoading: false,
            };

        case 'UPDATE_BLOCK_ATTRIBUTE':
            return {
                ...state,
                blockAttributes: {
                    ...state.blockAttributes,
                    [action.blockId]: {
                        ...state.blockAttributes[action.blockId],
                        [action.attribute]: action.value,
                    },
                },
            };

        case 'SET_LOADING':
            return {
                ...state,
                isLoading: action.isLoading,
            };

        default:
            return state;
    }
};

export const store = createReduxStore('srfm/forms', {
    reducer,
    actions,
    selectors,
});

register(store);
```

**Use Store in Components:**

```javascript
import { useSelect, useDispatch } from '@wordpress/data';

function FormEditor({ formId }) {
    // Select data from store
    const { formSettings, isLoading } = useSelect((select) => ({
        formSettings: select('srfm/forms').getFormSettings(),
        isLoading: select('srfm/forms').isLoading(),
    }));

    // Dispatch actions to store
    const { fetchFormSettings, setFormSettings } = useDispatch('srfm/forms');

    useEffect(() => {
        fetchFormSettings(formId);
    }, [formId]);

    if (isLoading) {
        return <Spinner />;
    }

    return (
        <div>
            <h2>{formSettings.title}</h2>
            {/* Editor UI */}
        </div>
    );
}
```

### 3. React Query (Server State)

For API responses and caching.

**Setup Query Client** ([src/admin/dashboard/index.js](../../src/admin/dashboard/index.js)):

```javascript
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 5 * 60 * 1000, // 5 minutes
            retry: 1,
        },
    },
});

function App() {
    return (
        <QueryClientProvider client={queryClient}>
            <Dashboard />
        </QueryClientProvider>
    );
}
```

**Create Custom Hooks** ([src/hooks/useFormData.js](../../src/hooks/)):

```javascript
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';

// Fetch form data
export function useFormData(formId) {
    return useQuery({
        queryKey: ['form', formId],
        queryFn: async () => {
            const response = await apiFetch({
                path: `/sureforms/v1/forms/${formId}`,
            });
            return response;
        },
        enabled: !!formId,
    });
}

// Update form data
export function useUpdateForm() {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn: async ({ formId, data }) => {
            return await apiFetch({
                path: `/sureforms/v1/forms/${formId}`,
                method: 'PUT',
                data,
            });
        },
        onSuccess: (data, variables) => {
            // Invalidate and refetch
            queryClient.invalidateQueries(['form', variables.formId]);
        },
    });
}

// Fetch entries
export function useEntries(formId, page = 1) {
    return useQuery({
        queryKey: ['entries', formId, page],
        queryFn: async () => {
            return await apiFetch({
                path: `/sureforms/v1/forms/${formId}/entries?page=${page}`,
            });
        },
        keepPreviousData: true, // For pagination
    });
}
```

**Use in Components:**

```javascript
function FormEditor({ formId }) {
    const { data: form, isLoading, error } = useFormData(formId);
    const updateForm = useUpdateForm();

    const handleSave = async () => {
        try {
            await updateForm.mutateAsync({
                formId,
                data: { title: 'Updated Title' },
            });
            // Success toast
            toast.success('Form saved!');
        } catch (error) {
            // Error toast
            toast.error('Failed to save form');
        }
    };

    if (isLoading) return <Spinner />;
    if (error) return <div>Error: {error.message}</div>;

    return (
        <div>
            <h2>{form.title}</h2>
            <button onClick={handleSave}>Save</button>
        </div>
    );
}
```

---

## Component Patterns

### Functional Components with Hooks

**Always use functional components** (not class components):

```javascript
// ✅ GOOD - Functional component
function MyComponent({ prop1, prop2 }) {
    const [state, setState] = useState(null);

    useEffect(() => {
        // Side effects
    }, []);

    return <div>{/* JSX */}</div>;
}

// ❌ BAD - Class component (deprecated pattern)
class MyComponent extends React.Component {
    // Don't use class components in SureForms
}
```

### Props Destructuring

```javascript
// ✅ GOOD - Destructure in signature
function Button({ label, onClick, disabled = false, variant = 'primary' }) {
    return (
        <button
            onClick={onClick}
            disabled={disabled}
            className={`btn btn--${variant}`}
        >
            {label}
        </button>
    );
}

// ❌ BAD - Access via props object
function Button(props) {
    return <button onClick={props.onClick}>{props.label}</button>;
}
```

### Conditional Rendering

```javascript
function FormField({ label, required, error }) {
    return (
        <div className="form-field">
            <label>
                {label}
                {/* Short-circuit evaluation */}
                {required && <span className="required">*</span>}
            </label>

            {/* Ternary for if/else */}
            {error ? (
                <span className="error">{error}</span>
            ) : (
                <span className="hint">Optional</span>
            )}

            {/* Conditional with && */}
            {!error && <span className="success">Valid</span>}
        </div>
    );
}
```

### Event Handlers

```javascript
function FormContainer() {
    // useCallback for stable reference
    const handleSubmit = useCallback((event) => {
        event.preventDefault();
        // Handle submit
    }, []);

    // Arrow function for inline with parameters
    const handleFieldChange = useCallback((fieldId, value) => {
        setFormData((prev) => ({
            ...prev,
            [fieldId]: value,
        }));
    }, []);

    return (
        <form onSubmit={handleSubmit}>
            <input
                onChange={(e) => handleFieldChange('name', e.target.value)}
            />
        </form>
    );
}
```

---

## Styling

### TailwindCSS (Preferred)

Use Tailwind utility classes for rapid development:

```javascript
function Card({ title, children }) {
    return (
        <div className="bg-white rounded-lg shadow-md p-6 mb-4">
            <h3 className="text-xl font-semibold text-gray-800 mb-4">
                {title}
            </h3>
            <div className="text-gray-600">
                {children}
            </div>
        </div>
    );
}
```

**Responsive Design:**

```javascript
<div className="w-full md:w-1/2 lg:w-1/3">
    {/* Full width mobile, half on tablet, third on desktop */}
</div>

<div className="text-sm md:text-base lg:text-lg">
    {/* Responsive text size */}
</div>
```

### Custom SCSS (When Needed)

**Use BEM methodology** with `.srfm-` prefix:

```scss
// style.scss
.srfm-custom-field {
    // Block
    &__label {
        // Element
        @apply block text-sm font-medium text-gray-700 mb-2;

        .required {
            // Nested element
            @apply text-red-500 ml-1;
        }
    }

    &__input {
        // Element
        @apply w-full px-4 py-2 border border-gray-300 rounded-md;
        @apply focus:outline-none focus:ring-2 focus:ring-blue-500;

        &:disabled {
            // Modifier via pseudo-class
            @apply bg-gray-100 cursor-not-allowed;
        }
    }

    &--error {
        // Block modifier
        .srfm-custom-field__input {
            @apply border-red-500;
        }
    }
}
```

### Conditional Classes (classnames)

```javascript
import classnames from 'classnames';

function Button({ variant, size, disabled, className }) {
    const classes = classnames(
        'srfm-button',
        `srfm-button--${variant}`,
        `srfm-button--${size}`,
        {
            'srfm-button--disabled': disabled,
        },
        className // Allow override
    );

    return <button className={classes}>Click me</button>;
}
```

---

## Form Validation

### Client-Side Validation (Frontend)

**Inline validation:**

```javascript
import { useState } from 'react';

function EmailField({ value, onChange, required }) {
    const [error, setError] = useState('');

    const validateEmail = (email) => {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    };

    const handleBlur = () => {
        if (required && !value) {
            setError(__('Email is required', 'sureforms'));
        } else if (value && !validateEmail(value)) {
            setError(__('Please enter a valid email', 'sureforms'));
        } else {
            setError('');
        }
    };

    return (
        <div className="form-field">
            <input
                type="email"
                value={value}
                onChange={onChange}
                onBlur={handleBlur}
                className={error ? 'error' : ''}
            />
            {error && <span className="error-message">{error}</span>}
        </div>
    );
}
```

**Form-level validation:**

```javascript
function FormContainer() {
    const [formData, setFormData] = useState({});
    const [errors, setErrors] = useState({});

    const validate = () => {
        const newErrors = {};

        if (!formData.name) {
            newErrors.name = 'Name is required';
        }

        if (!formData.email || !isValidEmail(formData.email)) {
            newErrors.email = 'Valid email is required';
        }

        if (formData.age && formData.age < 18) {
            newErrors.age = 'Must be 18 or older';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!validate()) {
            return;
        }

        // Submit form
        try {
            await submitForm(formData);
            toast.success('Form submitted!');
        } catch (error) {
            toast.error('Submission failed');
        }
    };

    return (
        <form onSubmit={handleSubmit}>
            {/* Form fields */}
            <button type="submit">Submit</button>
        </form>
    );
}
```

---

## Internationalization (i18n)

### Translation Functions

```javascript
import { __, _n, sprintf } from '@wordpress/i18n';

// Simple translation
const label = __('Submit Form', 'sureforms');

// Plural forms
const message = _n(
    'One field is required',
    '%d fields are required',
    count,
    'sureforms'
);

// String interpolation
const greeting = sprintf(
    __('Hello, %s!', 'sureforms'),
    userName
);
```

### Best Practices

**✅ DO:**
```javascript
// Use sprintf for variables
const msg = sprintf(__('Form %s saved', 'sureforms'), formName);

// Separate strings for different contexts
const saveButton = __('Save', 'sureforms');
const saveForm = __('Save Form', 'sureforms');
```

**❌ DON'T:**
```javascript
// Never use template literals in translations
const msg = __(`Form ${formName} saved`, 'sureforms'); // ❌ WRONG

// Never concatenate
const msg = __('Form ', 'sureforms') + formName + __(' saved', 'sureforms'); // ❌ WRONG
```

---

## Performance Optimization

### Code Splitting

```javascript
import { lazy, Suspense } from 'react';

const HeavyComponent = lazy(() => import('./HeavyComponent'));

function App() {
    return (
        <Suspense fallback={<Spinner />}>
            <HeavyComponent />
        </Suspense>
    );
}
```

### Memoization

```javascript
import { useMemo, useCallback } from 'react';

function DataTable({ data, onRowClick }) {
    // Memoize expensive calculations
    const sortedData = useMemo(() => {
        console.log('Sorting data...');
        return data.sort((a, b) => a.name.localeCompare(b.name));
    }, [data]); // Only recompute when data changes

    // Memoize callbacks
    const handleRowClick = useCallback(
        (row) => {
            onRowClick(row);
        },
        [onRowClick]
    );

    return <table>{/* Render sortedData */}</table>;
}
```

### React.memo

```javascript
import { memo } from 'react';

// Prevent re-renders if props haven't changed
const ExpensiveComponent = memo(function ExpensiveComponent({ data }) {
    console.log('Rendering...');
    return <div>{/* Complex rendering */}</div>;
});
```

---

## Testing

### Component Testing (Jest + React Testing Library)

```javascript
import { render, screen, fireEvent } from '@testing-library/react';
import '@testing-library/jest-dom';
import FormField from '../FormField';

describe('FormField', () => {
    it('renders with label', () => {
        render(<FormField label="Name" />);
        expect(screen.getByText('Name')).toBeInTheDocument();
    });

    it('shows required indicator', () => {
        render(<FormField label="Email" required />);
        expect(screen.getByText('*')).toBeInTheDocument();
    });

    it('calls onChange when value changes', () => {
        const handleChange = jest.fn();
        render(<FormField value="" onChange={handleChange} />);

        const input = screen.getByRole('textbox');
        fireEvent.change(input, { target: { value: 'test' } });

        expect(handleChange).toHaveBeenCalledWith('test');
    });

    it('displays error message', () => {
        render(<FormField error="This field is required" />);
        expect(screen.getByText('This field is required')).toBeInTheDocument();
    });
});
```

---

## Summary

**Key Takeaways:**

1. **Use React 18 functional components** with hooks
2. **Gutenberg blocks** are registered in JavaScript, rendered in PHP
3. **State management** uses three layers: local, WordPress Data Store, React Query
4. **Styling** prefers TailwindCSS utilities, custom SCSS for complex needs
5. **Validation** happens client-side (UX) and server-side (security)
6. **i18n** uses `@wordpress/i18n` functions, no template literals
7. **Performance** optimized with memoization, code splitting, virtualization

---

**Related Documentation:**
- [CLAUDE.md](../../CLAUDE.md) - AI agent guide
- [GETTING-STARTED.md](../01-getting-started/GETTING-STARTED.md) - Setup
- [CODING-STANDARDS.md](../03-development/CODING-STANDARDS.md) - Code style
- [TESTING.md](../08-testing/TESTING.md) - Testing guide
