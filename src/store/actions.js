/**
 * Action to update the global state with new data.
 */

/**
 * Update the global state with new data
 *
 * @param {Object} payload                - The new global data to be set
 * @param {Object} payload.srfmGlobalData - The global data for SureForms.
 * @return {Object} Action object to update the global state
 */
export const updateSrfmGlobalData = ( payload ) => {
	// add validation for payload if necessary
	if ( ! payload || typeof payload !== 'object' ) {
		throw new Error( 'Invalid payload: must be an object.' );
	}

	return {
		type: 'UPDATE_SRFM_GLOBAL_DATA',
		payload,
	};
};
