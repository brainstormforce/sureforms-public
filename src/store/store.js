/**
 * WordPress dependencies
 */
import { createReduxStore, register, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import reducer from './reducer';
import * as actions from './actions';
import * as selectors from './selectors';
import { STORE_NAME as storeName } from './constants';
// import setInitialState from './setInitialState';

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

// Guard against the "Store 'sureforms' is already registered" warning when
// both free and pro bundles each ship the store module and reach the same
// `wp.data` registry. The probe returns falsy only on first registration.
if ( ! select( storeName ) ) {
	register( store );
}
// setInitialState();
