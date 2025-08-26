if ( window?.sureformsRefundData && jQuery( '#srfm-refund-button' ).length ) {
	// Handle refund type dropdown change
	jQuery( '#srfm-refund-type' ).on( 'change', function () {
		const refundType = jQuery( this ).val();
		const partialContainer = jQuery( '#srfm-partial-amount-container' );

		if ( refundType === 'partial' ) {
			partialContainer.show();
			jQuery( '#srfm-refund-amount' ).focus();
		} else {
			partialContainer.hide();
			jQuery( '#srfm-refund-amount' ).val( '' );
		}
	} );

	// Handle refund amount input validation
	jQuery( '#srfm-refund-amount' ).on( 'input blur', function () {
		const amount = parseFloat( jQuery( this ).val() );
		const maxAmount = parseFloat( jQuery( this ).attr( 'max' ) );
		const input = jQuery( this );

		// Remove existing validation styles
		input.css( 'border-color', '' );

		if ( jQuery( this ).val() !== '' ) {
			if ( isNaN( amount ) || amount <= 0 ) {
				input.css( 'border-color', '#d63384' );
			} else if ( amount > maxAmount ) {
				input.css( 'border-color', '#d63384' );
			} else {
				input.css( 'border-color', '#198754' );
			}
		}
	} );

	// Enhanced refund button click handler
	jQuery( '#srfm-refund-button' ).on( 'click', function () {
		const button = jQuery( this );
		const paymentId = button.data( 'payment-id' );
		const transactionId = button.data( 'transaction-id' );
		const refundType = jQuery( '#srfm-refund-type' ).val();

		let refundAmount;
		let confirmMessage;

		// Validate and get refund amount
		if ( refundType === 'full' ) {
			refundAmount = sureformsRefundData.payment.refundable_amount;
			confirmMessage = sureformsRefundData.strings.confirm_full_refund;
		} else if ( refundType === 'partial' ) {
			const inputAmount = jQuery( '#srfm-refund-amount' ).val();

			// Validate partial refund amount
			const validation = validateRefundAmount( inputAmount );
			if ( ! validation.isValid ) {
				alert( validation.message );
				jQuery( '#srfm-refund-amount' )
					.focus()
					.css( 'border-color', '#d63384' );
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

		// Disable button and show loading state
		button
			.prop( 'disabled', true )
			.text( sureformsRefundData.strings.processing );

		// Disable form controls during processing
		jQuery( '#srfm-refund-type, #srfm-refund-amount' ).prop(
			'disabled',
			true
		);

		// Prepare AJAX data
		const ajaxData = {
			action: 'srfm_refund_payment',
			nonce: sureformsRefundData.nonce,
			payment_id: paymentId,
			transaction_id: transactionId,
			refund_amount: refundAmountInCents,
			refund_type: refundType,
		};

		// Make AJAX request
		jQuery.ajax( {
			url: sureformsRefundData.ajaxurl,
			type: 'POST',
			data: ajaxData,
			success( response ) {
				if ( response.success ) {
					// Show success message
					alert( sureformsRefundData.strings.success_message );
					// Reload page to show updated status
					location.reload();
				} else {
					alert(
						sureformsRefundData.strings.error_prefix +
							( response.data ||
								sureformsRefundData.strings.error_fallback )
					);
					// Re-enable controls
					resetRefundForm( button );
				}
			},
			error() {
				alert( sureformsRefundData.strings.network_error );
				// Re-enable controls
				resetRefundForm( button );
			},
		} );
	} );

	// Validation function
	function validateRefundAmount( inputAmount ) {
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

	// Reset form function
	function resetRefundForm( button ) {
		button
			.prop( 'disabled', false )
			.text( sureformsRefundData.strings.issue_refund );

		jQuery( '#srfm-refund-type, #srfm-refund-amount' ).prop(
			'disabled',
			false
		);
		jQuery( '#srfm-refund-amount' ).css( 'border-color', '' );
	}
}
