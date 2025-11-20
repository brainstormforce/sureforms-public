/**
 * API service for payments
 * Clean API layer using WordPress apiFetch
 */

import apiFetch from '@wordpress/api-fetch';

/**
 * Helper to make AJAX calls to WordPress admin-ajax.php
 *
 * @param {string} action - WordPress AJAX action name
 * @param {Object} params - Additional parameters
 * @return {Promise} API response data
 */
const makeAjaxCall = async ( action, params = {} ) => {
	const formData = new URLSearchParams();
	formData.append( 'action', action );
	formData.append(
		'nonce',
		window.srfm_payment_admin?.srfm_payment_admin_nonce || ''
	);

	// Append all additional parameters
	Object.entries( params ).forEach( ( [ key, value ] ) => {
		if ( value !== undefined && value !== null ) {
			formData.append( key, value );
		}
	} );

	const data = await apiFetch( {
		url: window.srfm_payment_admin?.ajax_url,
		method: 'POST',
		headers: {
			'Content-Type': 'application/x-www-form-urlencoded',
		},
		body: formData.toString(),
	} );

	// Check WordPress AJAX response format
	if ( ! data.success ) {
		const errorMessage = data.data?.message || 'Request failed';
		const error = new Error( errorMessage );
		error.response = data;
		error.data = data.data;
		error.isApiError = true;
		throw error;
	}

	return data.data;
};

/**
 * Fetch payments list with filters
 *
 * @param {Object} params - Query parameters
 * @return {Promise} API response
 */
export const fetchPaymentsList = async ( params = {} ) => {
	const {
		searchTerm = '',
		filter = '',
		formFilter = '',
		paymentMode = '',
		selectedDates = {},
		page = 1,
		itemsPerPage = 10,
		sortBy = '',
	} = params;

	return await makeAjaxCall( 'srfm_fetch_payments_transactions', {
		search: searchTerm,
		status: filter,
		form_id: formFilter,
		payment_mode: paymentMode,
		date_from: selectedDates?.from
			? selectedDates.from.toISOString().split( 'T' )[ 0 ]
			: '',
		date_to: selectedDates?.to
			? selectedDates.to.toISOString().split( 'T' )[ 0 ]
			: '',
		page,
		per_page: itemsPerPage,
		sort_by: sortBy,
	} );
};

/**
 * Fetch single payment details
 *
 * @param {number} paymentId - Payment ID
 * @return {Promise} API response
 */
export const fetchSinglePayment = async ( paymentId ) => {
	return await makeAjaxCall( 'srfm_fetch_single_payment', {
		payment_id: paymentId,
	} );
};

/**
 * Fetch subscription details with billing history
 *
 * @param {string|number} subscriptionId - Subscription ID
 * @return {Promise} API response
 */
export const fetchSubscription = async ( subscriptionId ) => {
	return await makeAjaxCall( 'srfm_fetch_subscription', {
		subscription_id: subscriptionId,
	} );
};

/**
 * Fetch forms list
 *
 * @return {Promise} Forms array
 */
export const fetchForms = async () => {
	const data = await makeAjaxCall( 'srfm_fetch_forms_list' );
	return data.forms || [];
};

/**
 * Bulk delete payments
 *
 * @param {Array} paymentIds - Array of payment IDs
 * @return {Promise} API response
 */
export const bulkDeletePayments = async ( paymentIds ) => {
	// Validate input
	if ( ! Array.isArray( paymentIds ) || paymentIds.length === 0 ) {
		const validationError = new Error( 'No payment IDs provided' );
		validationError.isValidationError = true;
		throw validationError;
	}

	try {
		return await makeAjaxCall( 'srfm_bulk_delete_payments', {
			payment_ids: JSON.stringify( paymentIds ),
		} );
	} catch ( error ) {
		// Mark network errors
		if ( ! error.response && ! error.data && ! error.isValidationError ) {
			error.isNetworkError = true;
		}
		throw error;
	}
};

/**
 * Refund a payment
 * @param {Object} root0               - Refund parameters
 * @param {number} root0.paymentId     - Payment ID
 * @param {string} root0.transactionId - Transaction ID
 * @param {number} root0.refundAmount  - Refund amount
 * @param {string} root0.refundType    - Refund type
 * @return {Promise} API response
 */
export const refundPayment = async ( {
	paymentId,
	transactionId,
	refundAmount,
	refundType,
} ) => {
	return await makeAjaxCall( 'srfm_stripe_refund_payment', {
		payment_id: paymentId,
		transaction_id: transactionId,
		refund_amount: refundAmount,
		refund_type: refundType,
	} );
};

/**
 * Cancel a subscription
 *
 * @param {number} paymentId - Payment ID
 * @return {Promise} API response
 */
export const cancelSubscription = async ( paymentId ) => {
	return await makeAjaxCall( 'srfm_stripe_cancel_subscription', {
		payment_id: paymentId,
	} );
};

/**
 * Pause a subscription
 *
 * @param {number} paymentId - Payment ID
 * @return {Promise} API response
 */
export const pauseSubscription = async ( paymentId ) => {
	return await makeAjaxCall( 'srfm_stripe_pause_subscription', {
		payment_id: paymentId,
	} );
};

/**
 * Add a note to a payment
 *
 * @param {number} paymentId - Payment ID
 * @param {string} noteText  - Note text
 * @return {Promise} Updated notes array
 */
export const addPaymentNote = async ( paymentId, noteText ) => {
	const data = await makeAjaxCall( 'srfm_add_payment_note', {
		payment_id: paymentId,
		note_text: noteText,
	} );
	return data.notes || [];
};

/**
 * Delete a payment note
 *
 * @param {number} paymentId - Payment ID
 * @param {number} noteIndex - Note index
 * @return {Promise} Updated notes array
 */
export const deletePaymentNote = async ( paymentId, noteIndex ) => {
	const data = await makeAjaxCall( 'srfm_delete_payment_note', {
		payment_id: paymentId,
		note_index: noteIndex,
	} );
	return data.notes || [];
};

/**
 * Delete a payment log
 *
 * @param {number} paymentId - Payment ID
 * @param {number} logIndex  - Log index
 * @return {Promise} Updated logs array
 */
export const deletePaymentLog = async ( paymentId, logIndex ) => {
	const data = await makeAjaxCall( 'srfm_delete_payment_log', {
		payment_id: paymentId,
		log_index: logIndex,
	} );
	return data.logs || [];
};
