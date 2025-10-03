/**
 * URL Utilities for Payment List
 * Handles URL parameter management without React Router
 *
 * @package sureforms
 * @since 1.0.0
 */

/**
 * Get all URL parameters as an object
 *
 * @return {Object} Object containing all URL parameters
 */
export const getUrlParams = () => {
	const searchParams = new URLSearchParams( window.location.search );
	const params = {};

	for ( const [ key, value ] of searchParams.entries() ) {
		params[ key ] = value;
	}

	return params;
};

/**
 * Update URL parameters without page reload
 *
 * @param {Object}  newParams - Key-value pairs to update
 * @param {boolean} replace   - Use replaceState instead of pushState (default: false)
 */
export const updateUrlParams = ( newParams, replace = false ) => {
	const searchParams = new URLSearchParams( window.location.search );

	// Update or remove parameters
	Object.entries( newParams ).forEach( ( [ key, value ] ) => {
		if ( value === undefined || value === null || value === '' ) {
			searchParams.delete( key );
		} else {
			searchParams.set( key, value );
		}
	} );

	const newUrl = `${ window.location.pathname }?${ searchParams.toString() }`;

	if ( replace ) {
		window.history.replaceState( {}, '', newUrl );
	} else {
		window.history.pushState( {}, '', newUrl );
	}
};

/**
 * Get a single URL parameter value
 *
 * @param {string} key          - Parameter name
 * @param {*}      defaultValue - Default value if not found
 * @return {string|null} Parameter value or default
 */
export const getUrlParam = ( key, defaultValue = null ) => {
	const searchParams = new URLSearchParams( window.location.search );
	return searchParams.get( key ) || defaultValue;
};

/**
 * Clear specific URL parameters
 *
 * @param {Array} keys - Array of parameter names to remove
 */
export const clearUrlParams = ( keys ) => {
	const params = {};
	keys.forEach( ( key ) => ( params[ key ] = undefined ) );
	updateUrlParams( params, true ); // Use replace to avoid history pollution
};
