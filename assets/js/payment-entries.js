if ( window?.sureformsRefundData && jQuery( '#srfm-refund-button' ).length ) {
	jQuery( '#srfm-refund-button' ).on( 'click', function () {
		// Show confirmation dialog
		if ( ! confirm( sureformsRefundData.strings.confirm_message ) ) {
			return;
		}

		const button = jQuery( this );
		const paymentId = button.data( 'payment-id' );
		const transactionId = button.data( 'transaction-id' );
		const amount = button.data( 'amount' );

		// Disable button and show loading state
		button
			.prop( 'disabled', true )
			.text( sureformsRefundData.strings.processing );

		// Prepare AJAX data
		const ajaxData = {
			action: 'srfm_refund_payment',
			nonce: sureformsRefundData.nonce,
			payment_id: paymentId,
			transaction_id: transactionId,
			refund_amount: amount, // Full refund by default
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
					// Re-enable button
					button
						.prop( 'disabled', false )
						.text( sureformsRefundData.strings.issue_refund );
				}
			},
			error() {
				alert( sureformsRefundData.strings.network_error );
				// Re-enable button
				button
					.prop( 'disabled', false )
					.text( sureformsRefundData.strings.issue_refund );
			},
		} );
	} );
}
