/**
 * Reducer for managing global state in SureForms.
 * This reducer handles the global state of the SureForms plugin, allowing for updates to the global data structure.
 * It is designed to be extensible, allowing other plugins like "sureforms-pro" to extend the default state.
 */

/**
 * Initial state for the SureForms store.
 * This state can be extended by other plugins like "sureforms-pro".
 * This will be used to store data that is shared across the SureForms plugin.
 *
 * @type {Object}
 * @property {Object} srfmGlobalData - The global data for SureForms.
 *                                   This will hold various data structures used across the plugin.
 */
const defaultState = {
	srfmGlobalData: {},
};

/**
 * Reducer function to handle actions and update the state.
 *
 * @param {Object} state  - The current state of the store.
 * @param {Object} action - The action to be processed.
 * @return {Object} The new state after applying the action.
 */
function reducer( state = defaultState, action ) {
	switch ( action.type ) {
		case 'UPDATE_SRFM_GLOBAL_DATA':
			return {
				...state,
				srfmGlobalData: {
					...state.srfmGlobalData,
					...action.payload,
				},
			};
		default:
			// If the action type is not recognized, return the current state unchanged.
			return state;
	}
}

export default reducer;
