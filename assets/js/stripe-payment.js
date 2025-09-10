/**
 * SureForms Payment Entries Management
 *
 * @since x.x.x
 */
/* global sureformsRefundData */
class PaymentEntries {
	/**
	 * Constructor for the PaymentEntries class.
	 * Initializes refund form elements and sets up event listeners.
	 */
	constructor() {
		this.refundButton = document.querySelector( '#srfm-refund-button' );
		this.refundTypeSelect = document.querySelector( '#srfm-refund-type' );
		this.refundAmountInput = document.querySelector(
			'#srfm-refund-amount'
		);
		this.partialAmountContainer = document.querySelector(
			'#srfm-partial-amount-container'
		);

		// Subscription-specific elements (following WPForms pattern)
		this.subscriptionRefundButton = document.querySelector(
			'#srfm-subscription-refund-button'
		);
		this.cancelSubscriptionButton = document.querySelector(
			'#srfm-cancel-subscription-button'
		);

		if ( ! this.refundButton && ! this.subscriptionRefundButton ) {
			return;
		}

		this.setupEventListeners();
	}

	/**
	 * Set up all event listeners for refund functionality.
	 */
	setupEventListeners() {
		// Handle refund type dropdown change
		if ( this.refundTypeSelect ) {
			this.refundTypeSelect.addEventListener( 'change', () => {
				this.handleRefundTypeChange();
			} );
		}

		// Handle refund amount input validation
		if ( this.refundAmountInput ) {
			this.refundAmountInput.addEventListener( 'input', () => {
				this.validateRefundAmountInput();
			} );

			this.refundAmountInput.addEventListener( 'blur', () => {
				this.validateRefundAmountInput();
			} );
		}

		// Handle refund button click
		if ( this.refundButton ) {
			this.refundButton.addEventListener( 'click', () => {
				this.processRefund();
			} );
		}

		// Handle subscription refund button click (following WPForms pattern)
		if ( this.subscriptionRefundButton ) {
			this.subscriptionRefundButton.addEventListener( 'click', () => {
				this.processSubscriptionRefund();
			} );
		}

		// Handle subscription cancellation button click (following WPForms pattern)
		if ( this.cancelSubscriptionButton ) {
			this.cancelSubscriptionButton.addEventListener( 'click', () => {
				this.processSubscriptionCancellation();
			} );
		}
	}

	/**
	 * Handle refund type dropdown change event.
	 */
	handleRefundTypeChange() {
		const refundType = this.refundTypeSelect.value;

		if ( refundType === 'partial' ) {
			this.partialAmountContainer.style.display = 'block';
			this.refundAmountInput.focus();
		} else {
			this.partialAmountContainer.style.display = 'none';
			this.refundAmountInput.value = '';
		}
	}

	/**
	 * Validate refund amount input and provide visual feedback.
	 */
	validateRefundAmountInput() {
		const amount = parseFloat( this.refundAmountInput.value );
		const maxAmount = parseFloat(
			this.refundAmountInput.getAttribute( 'max' )
		);

		// Remove existing validation styles
		this.refundAmountInput.style.borderColor = '';

		if ( this.refundAmountInput.value !== '' ) {
			if ( isNaN( amount ) || amount <= 0 ) {
				this.refundAmountInput.style.borderColor = '#d63384';
			} else if ( amount > maxAmount ) {
				this.refundAmountInput.style.borderColor = '#d63384';
			} else {
				this.refundAmountInput.style.borderColor = '#198754';
			}
		}
	}

	/**
	 * Process refund request with validation and confirmation.
	 */
	processRefund() {
		const paymentId = this.refundButton.dataset.paymentId;
		const transactionId = this.refundButton.dataset.transactionId;
		const refundType = this.refundTypeSelect.value;

		let refundAmount;
		let confirmMessage;

		// Validate and get refund amount
		if ( refundType === 'full' ) {
			refundAmount = sureformsRefundData.payment.refundable_amount;
			confirmMessage = sureformsRefundData.strings.confirm_full_refund;
		} else if ( refundType === 'partial' ) {
			const inputAmount = this.refundAmountInput.value;

			// Validate partial refund amount
			const validation = this.validateRefundAmount( inputAmount );
			if ( ! validation.isValid ) {
				alert( validation.message );
				this.refundAmountInput.focus();
				this.refundAmountInput.style.borderColor = '#d63384';
				return;
			}

			refundAmount = parseFloat( inputAmount );
			const formattedAmount =
				sureformsRefundData.payment.currency_symbol +
				refundAmount.toFixed( 2 );
			confirmMessage =
				sureformsRefundData.strings.confirm_partial_refund.replace(
					'%amount%',
					formattedAmount
				);
		} else {
			alert( sureformsRefundData.strings.select_refund_type );
			return;
		}

		// Show confirmation dialog
		if ( ! confirm( confirmMessage ) ) {
			return;
		}

		// Convert amount to cents for backend
		const refundAmountInCents = Math.round( refundAmount * 100 );

		this.makeRefundRequest(
			paymentId,
			transactionId,
			refundAmountInCents,
			refundType
		);
	}

	/**
	 * Make AJAX request to process refund.
	 *
	 * @param {string} paymentId     - The payment ID.
	 * @param {string} transactionId - The transaction ID.
	 * @param {number} refundAmount  - The refund amount in cents.
	 * @param {string} refundType    - The refund type (full/partial).
	 */
	async makeRefundRequest(
		paymentId,
		transactionId,
		refundAmount,
		refundType
	) {
		// Disable button and show loading state
		this.setLoadingState( true );

		// Prepare AJAX data
		const data = new FormData();
		data.append( 'action', 'srfm_refund_payment' );
		data.append( 'nonce', sureformsRefundData.nonce );
		data.append( 'payment_id', paymentId );
		data.append( 'transaction_id', transactionId );
		data.append( 'refund_amount', refundAmount );
		data.append( 'refund_type', refundType );

		try {
			const response = await fetch( sureformsRefundData.ajaxurl, {
				method: 'POST',
				body: data,
			} );

			const responseData = await response.json();

			if ( responseData.success ) {
				// Show success message
				alert( sureformsRefundData.strings.success_message );
				// Reload page to show updated status
				location.reload();
			} else {
				alert(
					sureformsRefundData.strings.error_prefix +
						( responseData.data ||
							sureformsRefundData.strings.error_fallback )
				);
				// Re-enable controls
				this.setLoadingState( false );
			}
		} catch ( error ) {
			alert( sureformsRefundData.strings.network_error );
			// Re-enable controls
			this.setLoadingState( false );
		}
	}

	/**
	 * Validate refund amount input.
	 *
	 * @param {string} inputAmount - The input amount to validate.
	 * @return {Object} Validation result with isValid flag and message.
	 */
	validateRefundAmount( inputAmount ) {
		const amount = parseFloat( inputAmount );
		const maxAmount = sureformsRefundData.payment.refundable_amount;

		if ( ! inputAmount || inputAmount.trim() === '' ) {
			return {
				isValid: false,
				message: sureformsRefundData.strings.amount_required,
			};
		}

		if ( isNaN( amount ) ) {
			return {
				isValid: false,
				message: sureformsRefundData.strings.amount_invalid,
			};
		}

		if ( amount < 0.01 ) {
			return {
				isValid: false,
				message: sureformsRefundData.strings.amount_too_low,
			};
		}

		if ( amount > maxAmount ) {
			return {
				isValid: false,
				message: sureformsRefundData.strings.amount_too_high,
			};
		}

		return { isValid: true };
	}

	/**
	 * Set loading state for refund form elements.
	 *
	 * @param {boolean} loading - Whether to show loading state.
	 */
	setLoadingState( loading = true ) {
		if ( loading ) {
			this.refundButton.disabled = true;
			this.refundButton.textContent =
				sureformsRefundData.strings.processing;

			// Disable form controls during processing
			if ( this.refundTypeSelect ) {
				this.refundTypeSelect.disabled = true;
			}
			if ( this.refundAmountInput ) {
				this.refundAmountInput.disabled = true;
			}
		} else {
			this.refundButton.disabled = false;
			this.refundButton.textContent =
				sureformsRefundData.strings.issue_refund;

			// Re-enable form controls
			if ( this.refundTypeSelect ) {
				this.refundTypeSelect.disabled = false;
			}
			if ( this.refundAmountInput ) {
				this.refundAmountInput.disabled = false;
				this.refundAmountInput.style.borderColor = '';
			}
		}
	}

	/**
	 * Process subscription payment refund (following WPForms pattern)
	 */
	processSubscriptionRefund() {
		const paymentId = this.subscriptionRefundButton.dataset.paymentId;
		const transactionId =
			this.subscriptionRefundButton.dataset.transactionId;
		const refundType = this.refundTypeSelect
			? this.refundTypeSelect.value
			: 'full';

		let refundAmount;
		let confirmMessage;

		// Validate and get refund amount
		if ( refundType === 'full' ) {
			refundAmount = sureformsRefundData.payment.refundable_amount;
			confirmMessage =
				sureformsRefundData.strings.confirm_subscription_refund ||
				'Are you sure you want to refund this subscription payment?';
		} else if ( refundType === 'partial' ) {
			const inputAmount = this.refundAmountInput.value;

			// Validate partial refund amount
			const validation = this.validateRefundAmount( inputAmount );
			if ( ! validation.isValid ) {
				alert( validation.message );
				this.refundAmountInput.focus();
				this.refundAmountInput.style.borderColor = '#d63384';
				return;
			}

			refundAmount = parseFloat( inputAmount );
			const formattedAmount =
				sureformsRefundData.payment.currency_symbol +
				refundAmount.toFixed( 2 );
			confirmMessage = (
				sureformsRefundData.strings
					.confirm_partial_subscription_refund ||
				'Are you sure you want to refund %amount% from this subscription payment?'
			).replace( '%amount%', formattedAmount );
		} else {
			alert( sureformsRefundData.strings.select_refund_type );
			return;
		}

		// Show confirmation dialog
		if ( ! confirm( confirmMessage ) ) {
			return;
		}

		// Convert amount to cents for backend
		const refundAmountInCents = Math.round( refundAmount * 100 );

		this.makeSubscriptionRefundRequest(
			paymentId,
			transactionId,
			refundAmountInCents,
			refundType
		);
	}

	/**
	 * Process subscription cancellation (following WPForms pattern)
	 */
	processSubscriptionCancellation() {
		const paymentId = this.cancelSubscriptionButton.dataset.paymentId;
		const subscriptionId =
			this.cancelSubscriptionButton.dataset.subscriptionId;

		const confirmMessage =
			sureformsRefundData.strings.confirm_subscription_cancel ||
			'Are you sure you want to cancel this subscription? This action cannot be undone.';

		// Show confirmation dialog
		if ( ! confirm( confirmMessage ) ) {
			return;
		}

		this.makeSubscriptionCancelRequest( paymentId, subscriptionId );
	}

	/**
	 * Make AJAX request to refund subscription payment
	 * @param paymentId
	 * @param transactionId
	 * @param refundAmountInCents
	 * @param refundType
	 */
	async makeSubscriptionRefundRequest(
		paymentId,
		transactionId,
		refundAmountInCents,
		refundType
	) {
		this.setSubscriptionRefundLoadingState( true );

		try {
			const response = await fetch( ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( {
					action: 'srfm_refund_subscription_payment',
					payment_id: paymentId,
					transaction_id: transactionId,
					refund_amount: refundAmountInCents,
					refund_type: refundType,
					nonce: sureformsRefundData.nonce,
				} ),
			} );

			const result = await response.json();

			if ( result.success ) {
				alert(
					result.data.message ||
						sureformsRefundData.strings
							.subscription_refund_success ||
						'Subscription payment refunded successfully!'
				);
				// Reload the page to reflect changes
				window.location.reload();
			} else {
				alert(
					result.data.message ||
						sureformsRefundData.strings
							.subscription_refund_failed ||
						'Subscription refund failed. Please try again.'
				);
				// Re-enable controls
				this.setSubscriptionRefundLoadingState( false );
			}
		} catch ( error ) {
			alert(
				sureformsRefundData.strings.network_error ||
					'Network error occurred. Please try again.'
			);
			// Re-enable controls
			this.setSubscriptionRefundLoadingState( false );
		}
	}

	/**
	 * Make AJAX request to cancel subscription
	 * @param paymentId
	 * @param subscriptionId
	 */
	async makeSubscriptionCancelRequest( paymentId, subscriptionId ) {
		this.setSubscriptionCancelLoadingState( true );

		try {
			const response = await fetch( ajaxurl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams( {
					action: 'srfm_cancel_subscription',
					payment_id: paymentId,
					subscription_id: subscriptionId,
					nonce: sureformsRefundData.nonce,
				} ),
			} );

			const result = await response.json();

			if ( result.success ) {
				alert(
					result.data.message ||
						sureformsRefundData.strings
							.subscription_cancel_success ||
						'Subscription cancelled successfully!'
				);
				// Reload the page to reflect changes
				window.location.reload();
			} else {
				alert(
					result.data.message ||
						sureformsRefundData.strings
							.subscription_cancel_failed ||
						'Subscription cancellation failed. Please try again.'
				);
				// Re-enable controls
				this.setSubscriptionCancelLoadingState( false );
			}
		} catch ( error ) {
			alert(
				sureformsRefundData.strings.network_error ||
					'Network error occurred. Please try again.'
			);
			// Re-enable controls
			this.setSubscriptionCancelLoadingState( false );
		}
	}

	/**
	 * Set loading state for subscription refund button
	 * @param isLoading
	 */
	setSubscriptionRefundLoadingState( isLoading ) {
		if ( isLoading ) {
			this.subscriptionRefundButton.disabled = true;
			this.subscriptionRefundButton.textContent =
				sureformsRefundData.strings.processing || 'Processing...';

			// Disable form controls
			if ( this.refundTypeSelect ) {
				this.refundTypeSelect.disabled = true;
			}
			if ( this.refundAmountInput ) {
				this.refundAmountInput.disabled = true;
			}
		} else {
			this.subscriptionRefundButton.disabled = false;
			this.subscriptionRefundButton.textContent =
				sureformsRefundData.strings.refund_subscription ||
				'Refund Payment';

			// Re-enable form controls
			if ( this.refundTypeSelect ) {
				this.refundTypeSelect.disabled = false;
			}
			if ( this.refundAmountInput ) {
				this.refundAmountInput.disabled = false;
				this.refundAmountInput.style.borderColor = '';
			}
		}
	}

	/**
	 * Set loading state for subscription cancel button
	 * @param isLoading
	 */
	setSubscriptionCancelLoadingState( isLoading ) {
		if ( isLoading ) {
			this.cancelSubscriptionButton.disabled = true;
			this.cancelSubscriptionButton.textContent =
				sureformsRefundData.strings.processing || 'Processing...';
		} else {
			this.cancelSubscriptionButton.disabled = false;
			this.cancelSubscriptionButton.textContent =
				sureformsRefundData.strings.cancel_subscription ||
				'Cancel Subscription';
		}
	}
}

// Initialize PaymentEntries when DOM is ready and required data is available
document.addEventListener( 'DOMContentLoaded', () => {
	if (
		window?.sureformsRefundData &&
		( document.querySelector( '#srfm-refund-button' ) ||
			document.querySelector( '#srfm-subscription-refund-button' ) ||
			document.querySelector( '#srfm-cancel-subscription-button' ) )
	) {
		new PaymentEntries();
	}
} );
