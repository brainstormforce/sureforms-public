import apiFetch from '@wordpress/api-fetch';

export const fetchPayments = async ( args ) => {
	const {
		searchTerm,
		filter,
		selectedDates,
		page,
		itemsPerPage,
		sortBy,
		formFilter,
	} = args;
	try {
		// Prepare form data as URLSearchParams for better apiFetch compatibility
		const formData = new URLSearchParams();
		formData.append( 'action', 'srfm_fetch_payments_transactions' );
		formData.append(
			'nonce',
			window.srfm_payment_admin.srfm_payment_admin_nonce || ''
		);
		formData.append( 'search', searchTerm || '' );
		formData.append( 'status', filter || '' );
		formData.append( 'form_id', formFilter || '' );
		formData.append(
			'date_from',
			selectedDates?.from
				? selectedDates.from.toISOString().split( 'T' )[ 0 ]
				: ''
		);
		formData.append(
			'date_to',
			selectedDates?.to
				? selectedDates.to.toISOString().split( 'T' )[ 0 ]
				: ''
		);
		formData.append( 'page', page || 1 );
		formData.append( 'per_page', itemsPerPage || 10 );
		formData.append( 'sort_by', sortBy || '' );

		// Use apiFetch with proper configuration for admin-ajax.php
		const data = await apiFetch( {
			url: window.srfm_payment_admin.ajax_url,
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: formData.toString(),
		} );

		console.log( 'AJAX response:', data );

		// Check WordPress AJAX response format
		if ( ! data.success ) {
			const errorMessage =
				data.data?.message || 'Failed to fetch payments';
			console.error( 'AJAX error:', errorMessage );
			throw new Error( errorMessage );
		}

		// Return the data portion of WordPress AJAX response
		return data.data;
	} catch ( error ) {
		console.error( 'Error fetching payments:', error );
		throw error;
	}
};

export const fetchSinglePayment = async ( paymentId ) => {
	try {
		// Prepare form data for single payment fetch
		const formData = new URLSearchParams();
		formData.append( 'action', 'srfm_fetch_single_payment' );
		formData.append(
			'nonce',
			window.srfm_payment_admin.srfm_payment_admin_nonce || ''
		);
		formData.append( 'payment_id', paymentId );

		// Use apiFetch with proper configuration for admin-ajax.php
		const data = await apiFetch( {
			url: window.srfm_payment_admin.ajax_url,
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: formData.toString(),
		} );

		console.log( 'Single payment AJAX response:', data );

		// Check WordPress AJAX response format
		if ( ! data.success ) {
			const errorMessage =
				data.data?.message || 'Failed to fetch payment details';
			console.error( 'Single payment AJAX error:', errorMessage );
			throw new Error( errorMessage );
		}

		// Return the data portion of WordPress AJAX response
		return data.data;
	} catch ( error ) {
		console.error( 'Error fetching single payment:', error );
		throw error;
	}
};

export const refundPayment = async ( {
	paymentId,
	transactionId,
	refundAmount,
	refundType,
} ) => {
	console.log( { paymentId, transactionId, refundAmount, refundType } );
	try {
		// Prepare form data for refund request
		const formData = new URLSearchParams();
		formData.append( 'action', 'srfm_stripe_refund_payment' );
		formData.append(
			'nonce',
			window.srfm_payment_admin.srfm_payment_admin_nonce || ''
		);
		formData.append( 'payment_id', paymentId );
		formData.append( 'transaction_id', transactionId );
		formData.append( 'refund_amount', refundAmount );
		formData.append( 'refund_type', refundType );

		// Use apiFetch with proper configuration for admin-ajax.php
		const data = await apiFetch( {
			url: window.srfm_payment_admin.ajax_url,
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: formData.toString(),
		} );

		console.log( 'Refund AJAX response:', data );

		// Check WordPress AJAX response format
		if ( ! data.success ) {
			const errorMessage =
				data.data?.message || 'Failed to process refund';
			console.error( 'Refund AJAX error:', errorMessage );
			throw new Error( errorMessage );
		}

		// Return the data portion of WordPress AJAX response
		return data.data;
	} catch ( error ) {
		console.error( 'Error processing refund:', error );
		throw error;
	}
};

export const fetchSubscription = async ( subscriptionId ) => {
	try {
		// Prepare form data for subscription fetch (returns subscription + billing data)
		const formData = new URLSearchParams();
		formData.append( 'action', 'srfm_fetch_subscription' );
		formData.append(
			'nonce',
			window.srfm_payment_admin.srfm_payment_admin_nonce || ''
		);
		formData.append( 'subscription_id', subscriptionId );

		// Use apiFetch with proper configuration for admin-ajax.php
		const data = await apiFetch( {
			url: window.srfm_payment_admin.ajax_url,
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: formData.toString(),
		} );

		console.log( 'Subscription AJAX response:', data );

		// Check WordPress AJAX response format
		if ( ! data.success ) {
			const errorMessage =
				data.data?.message || 'Failed to fetch subscription details';
			console.error( 'Subscription AJAX error:', errorMessage );
			throw new Error( errorMessage );
		}

		// Return the data portion of WordPress AJAX response (contains subscription + payments)
		return data.data;
	} catch ( error ) {
		console.error( 'Error fetching subscription:', error );
		throw error;
	}
};

export const fetchForms = async () => {
	try {
		// Prepare form data for forms list fetch
		const formData = new URLSearchParams();
		formData.append( 'action', 'srfm_fetch_forms_list' );
		formData.append(
			'nonce',
			window.srfm_payment_admin.srfm_payment_admin_nonce || ''
		);

		// Use apiFetch with proper configuration for admin-ajax.php
		const data = await apiFetch( {
			url: window.srfm_payment_admin.ajax_url,
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: formData.toString(),
		} );

		console.log( 'Forms list AJAX response:', data );

		// Check WordPress AJAX response format
		if ( ! data.success ) {
			const errorMessage =
				data.data?.message || 'Failed to fetch forms list';
			console.error( 'Forms list AJAX error:', errorMessage );
			throw new Error( errorMessage );
		}

		// Return the forms array from response
		return data.data.forms;
	} catch ( error ) {
		console.error( 'Error fetching forms:', error );
		throw error;
	}
};

export const cancelSubscription = async ( paymentId ) => {
	try {
		// Prepare form data for subscription cancellation
		const formData = new URLSearchParams();
		formData.append( 'action', 'srfm_stripe_cancel_subscription' );
		formData.append(
			'nonce',
			window.srfm_payment_admin.srfm_payment_admin_nonce || ''
		);
		formData.append( 'payment_id', paymentId );

		// Use apiFetch with proper configuration for admin-ajax.php
		const data = await apiFetch( {
			url: window.srfm_payment_admin.ajax_url,
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: formData.toString(),
		} );

		console.log( 'Cancel subscription AJAX response:', data );

		// Check WordPress AJAX response format
		if ( ! data.success ) {
			const errorMessage =
				data.data?.message || 'Failed to cancel subscription';
			console.error( 'Cancel subscription AJAX error:', errorMessage );
			throw new Error( errorMessage );
		}

		// Return the data portion of WordPress AJAX response
		return data.data;
	} catch ( error ) {
		console.error( 'Error canceling subscription:', error );
		throw error;
	}
};

export const pauseSubscription = async ( paymentId ) => {
	try {
		// Prepare form data for subscription pause
		const formData = new URLSearchParams();
		formData.append( 'action', 'srfm_stripe_pause_subscription' );
		formData.append(
			'nonce',
			window.srfm_payment_admin.srfm_payment_admin_nonce || ''
		);
		formData.append( 'payment_id', paymentId );

		// Use apiFetch with proper configuration for admin-ajax.php
		const data = await apiFetch( {
			url: window.srfm_payment_admin.ajax_url,
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: formData.toString(),
		} );

		console.log( 'Pause subscription AJAX response:', data );

		// Check WordPress AJAX response format
		if ( ! data.success ) {
			const errorMessage =
				data.data?.message || 'Failed to pause subscription';
			console.error( 'Pause subscription AJAX error:', errorMessage );
			throw new Error( errorMessage );
		}

		// Return the data portion of WordPress AJAX response
		return data.data;
	} catch ( error ) {
		console.error( 'Error pausing subscription:', error );
		throw error;
	}
};

export const addPaymentNote = async ( paymentId, noteText ) => {
	try {
		// Prepare form data for adding note
		const formData = new URLSearchParams();
		formData.append( 'action', 'srfm_add_payment_note' );
		formData.append(
			'nonce',
			window.srfm_payment_admin.srfm_payment_admin_nonce || ''
		);
		formData.append( 'payment_id', paymentId );
		formData.append( 'note_text', noteText );

		// Use apiFetch with proper configuration for admin-ajax.php
		const data = await apiFetch( {
			url: window.srfm_payment_admin.ajax_url,
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: formData.toString(),
		} );

		console.log( 'Add note AJAX response:', data );

		// Check WordPress AJAX response format
		if ( ! data.success ) {
			const errorMessage = data.data?.message || 'Failed to add note';
			console.error( 'Add note AJAX error:', errorMessage );
			throw new Error( errorMessage );
		}

		// Return the notes array from response
		return data.data.notes;
	} catch ( error ) {
		console.error( 'Error adding note:', error );
		throw error;
	}
};

export const deletePaymentNote = async ( paymentId, noteIndex ) => {
	try {
		// Prepare form data for deleting note
		const formData = new URLSearchParams();
		formData.append( 'action', 'srfm_delete_payment_note' );
		formData.append(
			'nonce',
			window.srfm_payment_admin.srfm_payment_admin_nonce || ''
		);
		formData.append( 'payment_id', paymentId );
		formData.append( 'note_index', noteIndex );

		// Use apiFetch with proper configuration for admin-ajax.php
		const data = await apiFetch( {
			url: window.srfm_payment_admin.ajax_url,
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: formData.toString(),
		} );

		console.log( 'Delete note AJAX response:', data );

		// Check WordPress AJAX response format
		if ( ! data.success ) {
			const errorMessage = data.data?.message || 'Failed to delete note';
			console.error( 'Delete note AJAX error:', errorMessage );
			throw new Error( errorMessage );
		}

		// Return the updated notes array from response
		return data.data.notes;
	} catch ( error ) {
		console.error( 'Error deleting note:', error );
		throw error;
	}
};

export const deletePaymentLog = async ( paymentId, logIndex ) => {
	try {
		// Prepare form data for deleting log
		const formData = new URLSearchParams();
		formData.append( 'action', 'srfm_delete_payment_log' );
		formData.append(
			'nonce',
			window.srfm_payment_admin.srfm_payment_admin_nonce || ''
		);
		formData.append( 'payment_id', paymentId );
		formData.append( 'log_index', logIndex );

		// Use apiFetch with proper configuration for admin-ajax.php
		const data = await apiFetch( {
			url: window.srfm_payment_admin.ajax_url,
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: formData.toString(),
		} );

		console.log( 'Delete log AJAX response:', data );

		// Check WordPress AJAX response format
		if ( ! data.success ) {
			const errorMessage = data.data?.message || 'Failed to delete log';
			console.error( 'Delete log AJAX error:', errorMessage );
			throw new Error( errorMessage );
		}

		// Return the updated logs array from response
		return data.data.logs;
	} catch ( error ) {
		console.error( 'Error deleting log:', error );
		throw error;
	}
};

export const bulkDeletePayments = async ( paymentIds ) => {
	try {
		// Validate input
		if ( ! Array.isArray( paymentIds ) || paymentIds.length === 0 ) {
			const validationError = new Error( 'Validation failed' );
			validationError.isValidationError = true;
			validationError.validationType = 'empty_array';
			throw validationError;
		}

		// Prepare form data for bulk delete
		const formData = new URLSearchParams();
		formData.append( 'action', 'srfm_bulk_delete_payments' );
		formData.append(
			'nonce',
			window.srfm_payment_admin.srfm_payment_admin_nonce || ''
		);

		// Send array as JSON string
		formData.append( 'payment_ids', JSON.stringify( paymentIds ) );

		// Use apiFetch with proper configuration for admin-ajax.php
		const data = await apiFetch( {
			url: window.srfm_payment_admin.ajax_url,
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: formData.toString(),
		} );

		console.log( 'Bulk delete AJAX response:', data );
		console.log( 'Response success:', data.success );
		console.log( 'Response data:', data.data );

		// Check WordPress AJAX response format
		if ( ! data.success ) {
			// Throw error with raw backend data attached
			const apiError = new Error( 'API request failed' );
			apiError.response = data;
			apiError.data = data.data;
			apiError.isApiError = true;
			console.error( 'Bulk delete API error:', data.data );
			throw apiError;
		}

		// Return raw success data
		return data.data;
	} catch ( error ) {
		console.error( 'Error bulk deleting payments:', error );

		// Mark network errors
		if ( ! error.response && ! error.data && ! error.isValidationError ) {
			error.isNetworkError = true;
		}

		// Re-throw without modifying
		throw error;
	}
};
