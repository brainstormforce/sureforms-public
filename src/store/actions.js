// import { pick } from 'lodash';
// import { select } from '@wordpress/data';
// import { STORE_NAME } from './constants';
// import * as actionTypes from './action-types';
/**
 * Returns an action object used in signalling that viewport queries have been
 * updated. Values are specified as an object of breakpoint query keys where
 * value represents whether query matches.
 * Ignored from documentation as it is for internal use only.
 *
 * @param {string} value Value to update.
 */
export function updateA( value ) {

	console.log('updateA action', value);
	return {
		type: 'UPDATE_A',
		value,
	};
}

// export function updateMetaboxState( value ) {
// 	return {
// 		type: 'UPDATE_METABOX_STATE',
// 		value,
// 	};
// }

// export function updateModalState( value ) {
// 	return {
// 		type: 'UPDATE_MODAL_STATE',
// 		value,
// 	};
// }

// // To create content dynamically.
// export function updatePostDynamicData( value ) {
// 	return {
// 		type: 'UPDATE_DYNAMIC_DATA',
// 		value,
// 	};
// }

// export function updatePostMetaData( value ) {
// 	return {
// 		type: 'UPDATE_META_DATA',
// 		value,
// 	};
// }

// export function updateInitialState( value ) {
// 	return {
// 		type: 'UPDATE_INITIAL_STATE',
// 		value,
// 	};
// }

// export const updateGlobalDefaults = ( payload ) => ( {
// 	type: actionTypes.UPDATE_GLOBAL_DEFAULTS,
// 	payload,
// } );