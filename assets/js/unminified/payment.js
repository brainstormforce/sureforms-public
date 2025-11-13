/**
 * Get currency symbol from currency code
 * @param {string} currency - Currency code (e.g., 'usd', 'eur')
 * @return {string} - Currency symbol
 */
function getCurrencySymbol( currency ) {
	// Use localized currency data from PHP
	const currenciesData = window.srfmStripe?.currenciesData || {};
	const upperCurrencyCode = currency?.toUpperCase();
	const currencyData = currenciesData[ upperCurrencyCode ];

	// Return symbol from localized data, or fallback to currency code
	return currencyData?.symbol || currency.toUpperCase() + ' ';
}

/**
 * Validate payment block before form submission
 * @param {HTMLElement} form - The form element
 * @return {Object} - Validation result object with { valid: boolean, slug: string, message: string }
 */
function validateThePaymentBlock( form ) {
	// Check if payment block exists & Get payment input element
	const paymentBlock = form.querySelector(
		'.srfm-block.srfm-payment-block:not(.hide-element)'
	);

	if ( ! paymentBlock ) {
		return {
			valid: true,
			slug: 'no-payment-block',
			message: '',
		};
	}

	const paymentInput = paymentBlock.querySelector( '.srfm-payment-input' );

	// Validate amount based on amount type (fixed or variable)
	const amountType =
		paymentInput.getAttribute( 'data-amount-type' ) || 'fixed';

	if ( amountType === 'fixed' ) {
		// Validate fixed amount exists and is valid
		const fixedAmount = parseFloat(
			paymentInput.getAttribute( 'data-fixed-amount' )
		);

		if ( ! fixedAmount || isNaN( fixedAmount ) || fixedAmount <= 0 ) {
			return {
				valid: false,
				slug: 'invalid-fixed-amount',
				message: window.srfmPaymentUtility?.getStripeStrings(
					'payment_amount_not_configured',
					'Payment is currently unavailable. Please contact the site administrator to configure the payment amount.'
				),
			};
		}
	} else if ( amountType === 'variable' ) {
		// For variable amounts, the amount is stored in the paymentInput.value
		// which is populated by the PAYMENT_UTILITY.updatePaymentBlockAmount function
		const variableAmount = parseFloat( paymentInput.value || 0 );

		if ( isNaN( variableAmount ) || variableAmount <= 0 ) {
			return {
				valid: false,
				slug: 'invalid-variable-amount',
				message: window.srfmPaymentUtility?.getStripeStrings(
					'invalid_variable_amount',
					'Please enter a valid payment amount greater than zero.'
				),
			};
		}

		// Validate minimum amount
		const minimumAmount = parseFloat(
			paymentInput.getAttribute( 'data-minimum-amount' ) || 0
		);

		if ( minimumAmount > 0 && variableAmount < minimumAmount ) {
			// Get currency symbol for better error message
			const currency =
				paymentInput.getAttribute( 'data-currency' ) || 'usd';
			const currencySymbol = getCurrencySymbol( currency );

			// Get localized string and replace placeholders
			const messageTemplate = window.srfmPaymentUtility?.getStripeStrings(
				'amount_below_minimum',
				'Payment amount must be at least {symbol}{amount}.'
			);
			const message = messageTemplate
				.replace( '{symbol}', currencySymbol )
				.replace( '{amount}', minimumAmount.toFixed( 2 ) );

			return {
				valid: false,
				slug: 'amount-below-minimum',
				message,
			};
		}
	}

	// Get customer field mappings
	const customerNameFieldSlug = paymentInput.getAttribute(
		'data-customer-name-field'
	);

	// Get payment type (subscription or one-time)
	const paymentType =
		paymentInput.getAttribute( 'data-payment-type' ) || 'one-time';
	const isSubscription = paymentType === 'subscription';

	// Validate that name field is mapped (required only for subscriptions)
	if (
		isSubscription &&
		( ! customerNameFieldSlug || customerNameFieldSlug.trim() === '' )
	) {
		return {
			valid: false,
			slug: 'payment-name-not-mapped',
			message: window.srfmPaymentUtility?.getStripeStrings(
				'payment_name_not_mapped',
				'Payment is currently unavailable. Please contact the site administrator to configure the customer name field.'
			),
		};
	}

	const customerEmailFieldSlug = paymentInput.getAttribute(
		'data-customer-email-field'
	);

	// Validate that email field is mapped (required for all payment types)
	if ( ! customerEmailFieldSlug || customerEmailFieldSlug.trim() === '' ) {
		return {
			valid: false,
			slug: 'payment-email-not-mapped',
			message: window.srfmPaymentUtility?.getStripeStrings(
				'payment_email_not_mapped',
				'Payment is currently unavailable. Please contact the site administrator to configure the customer email field.'
			),
		};
	}

	// Find and validate the actual name input field in the form (only for subscriptions)
	if (
		isSubscription &&
		customerNameFieldSlug &&
		customerNameFieldSlug.trim() !== ''
	) {
		const nameInput = form.querySelector(
			`.srfm-input-block.srfm-slug-${ customerNameFieldSlug } .srfm-input-common`
		);
		const nameInputValue = nameInput ? nameInput.value.trim() : '';

		if ( ! nameInput || nameInputValue === '' ) {
			return {
				valid: false,
				slug: 'payment-name-required',
				message: window.srfmPaymentUtility?.getStripeStrings(
					'payment_name_required',
					'Please enter your name.'
				),
			};
		}
	}

	// Find the actual email input field in the form
	const emailInput = form.querySelector(
		`.srfm-email-block.srfm-slug-${ customerEmailFieldSlug } .srfm-input-common`
	);
	const emailInputValue = emailInput ? emailInput.value.trim() : '';

	if ( ! emailInput || emailInputValue === '' ) {
		return {
			valid: false,
			slug: 'payment-email-required',
			message: window.srfmPaymentUtility?.getStripeStrings(
				'payment_email_required',
				'Please enter your email.'
			),
		};
	}

	// All validations passed
	return {
		valid: true,
		slug: 'payment-valid',
		message: '',
	};
}

/**
 * Main payment handler function called from form submission
 * @param {HTMLElement} form - The form element.
 */
export async function handleFormPayment( form ) {
	try {
		const valiDatePaymentBlocks = validateThePaymentBlock( form );

		if (
			valiDatePaymentBlocks.valid &&
			'no-payment-block' === valiDatePaymentBlocks.slug
		) {
			return {
				valid: true,
				message: '',
			};
		}

		if ( ! valiDatePaymentBlocks.valid ) {
			return {
				valid: false,
				message: valiDatePaymentBlocks.message,
			};
		}

		const paymentBlock = form.querySelector(
			'.srfm-block.srfm-payment-block'
		);

		// Process all payments
		const paymentResultOnCreateIntent = await processAllPayments(
			form,
			paymentBlock
		);

		return paymentResultOnCreateIntent;
	} catch ( error ) {
		return {
			valid: false,
			message: window.srfmPaymentUtility?.getStripeStrings(
				'payment_failed',
				'Payment failed'
			),
			paymentResultOnCreateIntent: null,
		};
	}
}

/**
 * Process all payments when form is submitted
 * @param {HTMLElement} form         - The form element.
 * @param {HTMLElement} paymentBlock - The payment block element.
 * @return {Promise<boolean>} True if payment succeeded, false otherwise.
 */
async function processAllPayments( form, paymentBlock ) {
	try {
		// Step 1: Create payment intents for all payment blocks
		const paymentResultOnCreateIntent =
			await window.StripePayment.createPaymentIntentsForForm(
				form,
				paymentBlock
			);

		if ( ! paymentResultOnCreateIntent?.valid ) {
			return {
				valid: false,
				message: paymentResultOnCreateIntent.message,
			};
		}

		// Step 2: Confirm payment with error handling
		const blockId = paymentBlock.getAttribute( 'data-block-id' );
		const paymentData = window.srfmPaymentElements?.[ blockId ];

		if ( paymentData && paymentData.clientSecret ) {
			const paymentResult = await window.StripePayment.srfmConfirmPayment(
				blockId,
				paymentData,
				form
			).catch( () => {
				return null;
			} );

			if ( ! paymentResult?.valid ) {
				return {
					valid: false,
					message: paymentResult.message,
					paymentResult: null,
				};
			}

			// Return true if payment succeeded (result is truthy and not empty string)
			return {
				valid: true,
				message: window.srfmPaymentUtility?.getStripeStrings(
					'payment_successful',
					'Payment successful'
				),
				paymentResult,
			};
		}
		return false;
	} catch ( error ) {
		return false;
	}
}
