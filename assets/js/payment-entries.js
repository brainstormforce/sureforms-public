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

		if ( ! this.refundButton ) {
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
}

// Initialize PaymentEntries when DOM is ready and required data is available
document.addEventListener( 'DOMContentLoaded', () => {
	if (
		window?.sureformsRefundData &&
		document.querySelector( '#srfm-refund-button' )
	) {
		new PaymentEntries();
	}
} );
