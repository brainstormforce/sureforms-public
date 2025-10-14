/**
 * API service for entries
 * This file contains functions to fetch entries data from the WordPress REST API
 */

import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

/**
 * Get the REST API nonce from the global srfm_admin object
 *
 * @return {string} The nonce for REST API requests
 */
const getNonce = () => {
	return srfm_admin?.global_settings_nonce || '';
};

/**
 * Fetch forms list
 * Returns an object mapping form IDs to form titles
 *
 * @return {Promise<Object>} Promise resolving to forms map { "123": "Form Title" }
 */
export const fetchFormsList = () => {
	return apiFetch( {
		path: '/sureforms/v1/form-data',
		method: 'GET',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': getNonce(),
		},
	} );
};

/**
 * Fetch entries list with filters and pagination
 *
 * @param {Object} params           - Query parameters
 * @param {number} params.form_id   - Form ID to filter (0 for all forms)
 * @param {string} params.status    - Entry status: 'all', 'read', 'unread', 'trash'
 * @param {string} params.search    - Search term for entry ID
 * @param {string} params.date_from - Start date filter in YYYY-MM-DD format
 * @param {string} params.date_to   - End date filter in YYYY-MM-DD format
 * @param {string} params.orderby   - Column to order by
 * @param {string} params.order     - Sort direction: 'ASC' or 'DESC'
 * @param {number} params.per_page  - Number of entries per page
 * @param {number} params.page      - Current page number
 * @return {Promise<Object>} Promise resolving to entries data
 */
export const fetchEntriesList = ( params = {} ) => {
	const queryParams = {
		form_id: params.form_id || 0,
		status: params.status || 'all',
		search: params.search || '',
		date_from: params.date_from || '',
		date_to: params.date_to || '',
		orderby: params.orderby || 'created_at',
		order: params.order || 'DESC',
		per_page: params.per_page || 20,
		page: params.page || 1,
	};

	return apiFetch( {
		path: addQueryArgs( '/sureforms/v1/entries/list', queryParams ),
		method: 'GET',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': getNonce(),
		},
	} );
};

/**
 * Fetch single entry details
 *
 * @param {number} entryId - Entry ID to fetch
 * @return {Promise<Object>} Promise resolving to entry data
 */
export const fetchEntryDetail = ( entryId ) => {
	return apiFetch( {
		path: `/sureforms/v1/entries/${ entryId }`,
		method: 'GET',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': getNonce(),
		},
	} );
};

/**
 * Update entries read status (mark as read/unread)
 *
 * @param {Object}   params           - Request parameters
 * @param {number[]} params.entry_ids - Array of entry IDs
 * @param {string}   params.action    - Action: 'read' or 'unread'
 * @return {Promise<Object>} Promise resolving to operation result
 */
export const updateEntriesReadStatus = ( { entry_ids, action } ) => {
	return apiFetch( {
		path: '/sureforms/v1/entries/read-status',
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': getNonce(),
		},
		data: {
			entry_ids,
			action,
		},
	} );
};

/**
 * Update entries trash status (trash/restore)
 *
 * @param {Object}   params           - Request parameters
 * @param {number[]} params.entry_ids - Array of entry IDs
 * @param {string}   params.action    - Action: 'trash' or 'restore'
 * @return {Promise<Object>} Promise resolving to operation result
 */
export const trashEntries = ( { entry_ids, action } ) => {
	return apiFetch( {
		path: '/sureforms/v1/entries/trash',
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': getNonce(),
		},
		data: {
			entry_ids,
			action,
		},
	} );
};

/**
 * Permanently delete entries
 *
 * @param {Object}   params           - Request parameters
 * @param {number[]} params.entry_ids - Array of entry IDs to delete
 * @return {Promise<Object>} Promise resolving to deletion result
 */
export const deleteEntries = ( { entry_ids } ) => {
	return apiFetch( {
		path: '/sureforms/v1/entries/delete',
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': getNonce(),
		},
		data: {
			entry_ids,
		},
	} );
};

/**
 * Export entries to CSV or ZIP
 *
 * @param {Object}   params           - Export parameters
 * @param {number[]} params.entry_ids - Specific entry IDs to export (optional)
 * @param {number}   params.form_id   - Form ID to export from (optional)
 * @param {string}   params.status    - Entry status filter (optional)
 * @param {string}   params.search    - Search term filter (optional)
 * @param {string}   params.date_from - Start date filter in YYYY-MM-DD format (optional)
 * @param {string}   params.date_to   - End date filter in YYYY-MM-DD format (optional)
 * @return {Promise<Object>} Promise resolving to export file information
 */
export const exportEntries = ( params = {} ) => {
	return apiFetch( {
		path: '/sureforms/v1/entries/export',
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			'X-WP-Nonce': getNonce(),
		},
		data: {
			entry_ids: params.entry_ids || [],
			form_id: params.form_id || 0,
			status: params.status || 'all',
			search: params.search || '',
			date_from: params.date_from || '',
			date_to: params.date_to || '',
		},
	} );
};
