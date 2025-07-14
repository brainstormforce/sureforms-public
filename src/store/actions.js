/**
 * Action to update the global state with new data.
 */

/**
 * Update the global state with new data
 *
 * @param {Object} payload - The new global data to be set
 * @return {Object} Action object to update the global state
 */
export const updateSrfmGlobalData = ( payload ) => ( {
	type: 'UPDATE_SRFM_GLOBAL_DATA',
	payload,
} );
