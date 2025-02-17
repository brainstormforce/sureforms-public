import { applyFilters } from '@wordpress/hooks';

let defaultState = {
	a : 1,
	b : 2,
};

// Add filter to add default state. so we can extend the default state from the "sureforms-pro" plugin.
defaultState = applyFilters( 'srfm.store.defaultState', defaultState );

function reducer( state = defaultState, action ) {
	switch ( action.type ) {
		case 'UPDATE_A':
			return {
				...state,
				a: action.value,
			};
	}

	// Add filter to update the state. so we can extend the reducer from the "sureforms-pro" plugin.
	// state = applyFilters( 'srfm.store.reducer', state, action );
	// console.log('reducer', {state, action});

	return state;
}

export default reducer;
