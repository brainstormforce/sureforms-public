/**
 * WordPress dependencies
 */
import { createReduxStore, register } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import { STORE_NAME as storeName } from './constants';
import setInitialState from './setInitialState';

console.log('store.js', { reducer, actions, selectors, storeName });

let x = {
	a : 1,
	b : 2,
};

x = applyFilters( 'srfm.store.defaultState', x );

// console.log('store.js x->', x);

/**
 * Store definition for the viewport namespace.
 *
 * @see https://github.com/WordPress/gutenberg/blob/HEAD/packages/data/README.md#createReduxStore
 *
 * @type {Object}
 */
export const store = createReduxStore( storeName, {
	reducer,
	actions,
	selectors,
} );

register( store );
setInitialState();
