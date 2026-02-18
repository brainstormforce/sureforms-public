# State Management

SureForms uses the `@wordpress/data` package to create a Redux-like data store for managing application state in the block editor.

## Store Structure

Located in `src/store/`:

```
src/store/
|-- store.js           # Store registration
|-- reducer.js         # State reducers
|-- actions.js         # Action creators
|-- selectors.js       # State selectors
|-- constants.js       # Store constants
|-- setInitialState.js # Initial state configuration
```

## Store Registration

The store is registered with `@wordpress/data` in `store.js`:

```javascript
import { createReduxStore, register } from '@wordpress/data';
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';

const store = createReduxStore('sureforms', {
    reducer,
    actions,
    selectors,
});

register(store);
```

The store namespace is `sureforms`.

## Initial State

`setInitialState.js` configures the initial state from server-side localized data (`wp_localize_script`), providing:

- Form settings and metadata
- Block defaults
- User preferences
- Plugin configuration

## Actions

Actions are defined in `actions.js` and dispatched to modify the store state. They follow the WordPress data store pattern:

```javascript
// Dispatching an action
import { dispatch } from '@wordpress/data';
dispatch('sureforms').updateFormSettings({ key: 'value' });
```

## Selectors

Selectors in `selectors.js` extract specific data from the store state:

```javascript
// Using a selector
import { select } from '@wordpress/data';
const settings = select('sureforms').getFormSettings();
```

## Component Integration

Components connect to the store using WordPress data hooks:

```javascript
import { useSelect, useDispatch } from '@wordpress/data';

function MyComponent() {
    const formData = useSelect((select) => {
        return select('sureforms').getFormSettings();
    }, []);

    const { updateFormSettings } = useDispatch('sureforms');

    return (
        <button onClick={() => updateFormSettings({ key: 'newValue' })}>
            Update
        </button>
    );
}
```

## Constants

`constants.js` defines store-wide constants used across actions, reducers, and selectors to ensure consistency.

## Usage Context

The WordPress data store is primarily used in:

- **Block editor** -- Form-level settings and block state
- **Inspector controls** -- Sidebar settings panels
- **Block attributes** -- Synchronized state between blocks

For the admin dashboard pages, `@tanstack/react-query` is used instead for server state management. See [Admin Dashboard](Admin-Dashboard) for details.

## Related Pages

- [Admin Dashboard](Admin-Dashboard) -- React admin app architecture
- [Block Editor Controls](Block-Editor-Controls) -- Inspector controls using the store
- [Gutenberg Blocks](Gutenberg-Blocks) -- Block registration and attributes
