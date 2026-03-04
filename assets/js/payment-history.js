/**
 * Payment History Frontend JavaScript.
 *
 * Handles refund and subscription cancellation actions.
 *
 * @package sureforms
 * @since 2.6.0
 */

( function() {
	'use strict';

	var config = window.srfm_payment_history || {};
	var i18n = config.i18n || {};

	/**
	 * Initialize event listeners.
	 */
	function init() {
		// Refund button.
		var refundBtn = document.querySelector( '.srfm-ph-action-btn--refund' );
		if ( refundBtn ) {
			refundBtn.addEventListener( 'click', handleRefund );
		}

		// Cancel subscription button.
		var cancelBtn = document.querySelector( '.srfm-ph-action-btn--cancel' );
		if ( cancelBtn ) {
			cancelBtn.addEventListener( 'click', handleCancelSubscription );
		}
	}

	/**
	 * Handle refund button click.
	 *
	 * @param {Event} e Click event.
	 */
	function handleRefund( e ) {
		var btn = e.currentTarget;
		var amountInput = document.getElementById( 'srfm-refund-amount' );
		var notesInput = document.getElementById( 'srfm-refund-notes' );
		var messageEl = document.getElementById( 'srfm-refund-message' );

		if ( ! amountInput ) {
			return;
		}

		var amount = parseFloat( amountInput.value );
		var maxRefund = parseFloat( btn.dataset.maxRefund );
		var isZeroDecimal = btn.dataset.zeroDecimal === '1';

		// Validate amount.
		if ( isNaN( amount ) || amount <= 0 ) {
			showMessage( messageEl, i18n.invalid_amount || 'Please enter a valid refund amount.', 'error' );
			return;
		}

		if ( amount > maxRefund ) {
			showMessage( messageEl, i18n.amount_exceeds_refundable || 'Amount exceeds the refundable amount.', 'error' );
			return;
		}

		// Confirm.
		if ( ! window.confirm( i18n.confirm_refund || 'Are you sure you want to refund this payment?' ) ) {
			return;
		}

		// Convert to Stripe format (cents for non-zero-decimal currencies).
		var stripeAmount = isZeroDecimal ? Math.round( amount ) : Math.round( amount * 100 );

		// Disable button.
		btn.disabled = true;
		btn.textContent = i18n.processing || 'Processing...';
		hideMessage( messageEl );

		// Make AJAX request.
		var formData = new FormData();
		formData.append( 'action', 'srfm_frontend_refund_payment' );
		formData.append( 'nonce', config.nonce );
		formData.append( 'payment_id', btn.dataset.paymentId );
		formData.append( 'transaction_id', btn.dataset.transactionId );
		formData.append( 'refund_amount', stripeAmount );
		formData.append( 'refund_notes', notesInput ? notesInput.value : '' );

		fetch( config.ajax_url, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
		} )
			.then( function( response ) {
				return response.json();
			} )
			.then( function( data ) {
				if ( data.success ) {
					showMessage( messageEl, data.data.message || i18n.refund_success || 'Refund processed successfully.', 'success' );
					// Reload page after short delay to show updated status.
					setTimeout( function() {
						window.location.reload();
					}, 1500 );
				} else {
					showMessage( messageEl, data.data || i18n.error || 'Something went wrong.', 'error' );
					btn.disabled = false;
					btn.textContent = 'Process Refund';
				}
			} )
			.catch( function() {
				showMessage( messageEl, i18n.error || 'Something went wrong. Please try again.', 'error' );
				btn.disabled = false;
				btn.textContent = 'Process Refund';
			} );
	}

	/**
	 * Handle cancel subscription button click.
	 *
	 * @param {Event} e Click event.
	 */
	function handleCancelSubscription( e ) {
		var btn = e.currentTarget;
		var messageEl = document.getElementById( 'srfm-cancel-message' );

		if ( ! window.confirm( i18n.confirm_cancel || 'Are you sure you want to cancel this subscription? This action cannot be undone.' ) ) {
			return;
		}

		btn.disabled = true;
		btn.textContent = i18n.processing || 'Processing...';
		hideMessage( messageEl );

		var formData = new FormData();
		formData.append( 'action', 'srfm_frontend_cancel_subscription' );
		formData.append( 'nonce', config.nonce );
		formData.append( 'payment_id', btn.dataset.paymentId );

		fetch( config.ajax_url, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
		} )
			.then( function( response ) {
				return response.json();
			} )
			.then( function( data ) {
				if ( data.success ) {
					showMessage( messageEl, data.data.message || i18n.cancel_success || 'Subscription cancelled successfully.', 'success' );
					setTimeout( function() {
						window.location.reload();
					}, 1500 );
				} else {
					showMessage( messageEl, data.data || i18n.error || 'Something went wrong.', 'error' );
					btn.disabled = false;
					btn.textContent = 'Cancel Subscription';
				}
			} )
			.catch( function() {
				showMessage( messageEl, i18n.error || 'Something went wrong. Please try again.', 'error' );
				btn.disabled = false;
				btn.textContent = 'Cancel Subscription';
			} );
	}

	/**
	 * Show a message in the given element.
	 *
	 * @param {HTMLElement} el      Message element.
	 * @param {string}      message Message text.
	 * @param {string}      type    'success' or 'error'.
	 */
	function showMessage( el, message, type ) {
		if ( ! el ) {
			return;
		}
		el.textContent = message;
		el.className = 'srfm-ph-action-message srfm-ph-action-message--' + type;
		el.style.display = 'block';
	}

	/**
	 * Hide a message element.
	 *
	 * @param {HTMLElement} el Message element.
	 */
	function hideMessage( el ) {
		if ( ! el ) {
			return;
		}
		el.style.display = 'none';
	}

	// Initialize when DOM is ready.
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
