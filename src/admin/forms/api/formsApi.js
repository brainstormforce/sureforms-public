/**
 * API service for forms
 * This file contains functions to fetch forms data from the WordPress REST API
 */

import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Fetch forms list from the API
 *
 * @param {Object} params - Query parameters
 * @return {Promise} API response
 */
export const fetchFormsList = async ( params = {} ) => {
	const queryParams = {
		page: params.page || 1,
		per_page: params.per_page,
		status: params.status || 'any',
		orderby: params.orderby || 'date',
		order: params.order || 'desc',
		...( params.search && { search: params.search } ),
		...( params.after && { after: params.after } ),
		...( params.before && { before: params.before } ),
	};

	const url = addQueryArgs( '/sureforms/v1/forms', queryParams );
	return await apiFetch( { path: url } );
};

/**
 * Bulk action on forms
 *
 * @param {Object} data - Action data
 * @return {Promise} API response
 */
export const bulkFormsAction = async ( data ) => {
	return await apiFetch( {
		path: '/sureforms/v1/forms/manage',
		method: 'POST',
		data,
	} );
};

/**
 * Export forms
 *
 * @param {Object} data - Export data
 * @return {Promise} API response
 */
export const exportForms = async ( data ) => {
	return await apiFetch( {
		path: '/sureforms/v1/forms/export',
		method: 'POST',
		data,
	} );
};

/**
 * Import forms
 *
 * @param {Object} data - Import data
 * @return {Promise} API response
 */
export const importForms = async ( data ) => {
	return await apiFetch( {
		path: '/sureforms/v1/forms/import',
		method: 'POST',
		data,
	} );
};
