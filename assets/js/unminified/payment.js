/**
 * Get currency symbol from currency code
 * @param {string} currency - Currency code (e.g., 'usd', 'eur')
 * @return {string} - Currency symbol
 */
function getCurrencySymbol( currency ) {
	const currencySymbols = {
		usd: '$',
		eur: '€',
		gbp: '£',
		jpy: '¥',
		aud: 'A$',
		cad: 'C$',
		chf: 'CHF',
		cny: '¥',
		sek: 'kr',
		nzd: 'NZ$',
		mxn: 'MX$',
		sgd: 'S$',
		hkd: 'HK$',
		nok: 'kr',
		krw: '₩',
		try: '₺',
		rub: '₽',
		inr: '₹',
		brl: 'R$',
		zar: 'R',
		aed: 'د.إ',
	};

	return currencySymbols[ currency.toLowerCase() ] || currency.toUpperCase() + ' ';
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
		console.warn( 'Payment block or payment input not found' );

		return {
			valid: true,
			slug: 'no-payment-block',
			message: '',
		};
	}

	const paymentInput = paymentBlock.querySelector( '.srfm-payment-input' );

	// Validate amount based on amount type (fixed or user-defined)
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
				message: 'Payment amount must be configured properly.',
			};
		}
	} else if ( amountType === 'user-defined' ) {
		// Validate user entered an amount
		const userAmountInput = paymentBlock.querySelector(
			'.srfm-user-amount-field'
		);

		if ( ! userAmountInput ) {
			return {
				valid: false,
				slug: 'user-amount-field-not-found',
				message: 'Payment amount input field not found.',
			};
		}

		const userAmount = parseFloat( userAmountInput.value || 0 );

		if ( ! userAmount || isNaN( userAmount ) || userAmount <= 0 ) {
			return {
				valid: false,
				slug: 'invalid-user-amount',
				message:
					'Please enter a valid payment amount greater than zero.',
			};
		}

		// Validate minimum amount
		const minimumAmount = parseFloat(
			userAmountInput.getAttribute( 'data-minimum-amount' ) || 0
		);

		if ( minimumAmount > 0 && userAmount < minimumAmount ) {
			// Get currency symbol for better error message
			const currency =
				userAmountInput.getAttribute( 'data-currency' ) || 'usd';
			const currencySymbol = getCurrencySymbol( currency );

			return {
				valid: false,
				slug: 'amount-below-minimum',
				message: `Payment amount must be at least ${ currencySymbol }${ minimumAmount.toFixed(
					2
				) }.`,
			};
		}
	}

	// Get customer field mappings
	const customerNameFieldSlug = paymentBlock.getAttribute(
		'data-customer-name-field'
	);

	// Get payment type (subscription or one-time)
	const paymentType =
		paymentBlock.getAttribute( 'data-payment-type' ) || 'one-time';
	const isSubscription = paymentType === 'subscription';

	// Validate that name field is mapped (required only for subscriptions)
	if (
		isSubscription &&
		( ! customerNameFieldSlug || customerNameFieldSlug.trim() === '' )
	) {
		return {
			valid: false,
			slug: 'payment-name-not-mapped',
			message:
				'Customer name field is required for subscriptions. Please configure it in the payment block settings.',
		};
	}

	const customerEmailFieldSlug = paymentBlock.getAttribute(
		'data-customer-email-field'
	);

	// Validate that email field is mapped (required for all payment types)
	if ( ! customerEmailFieldSlug || customerEmailFieldSlug.trim() === '' ) {
		return {
			valid: false,
			slug: 'payment-email-not-mapped',
			message:
				'Customer email field is required for payments. Please configure it in the payment block settings.',
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
				message: 'Please enter your name.',
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
			message: 'Please enter your email.',
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
		console.error( 'Payment processing failed:', error );
		return {
			valid: false,
			message: 'Payment failed',
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
		const paymentType = paymentData?.paymentType || 'one-time';

		if ( paymentData && paymentData.clientSecret ) {
			const paymentResult = await window.StripePayment.srfmConfirmPayment(
				blockId,
				paymentData,
				form
			).catch( ( error ) => {
				// Enhanced error reporting
				const errorMessage = `${
					paymentType.charAt( 0 ).toUpperCase() +
					paymentType.slice( 1 )
				} payment failed for block ${ blockId }: ${ error.message }`;
				console.error( 'error from confirmPayment:', errorMessage );
				return null;
			} );

			console.log( 'Payment completed via new method:', paymentResult );

			// Return true if payment succeeded (result is truthy and not empty string)
			return paymentResult
				? { valid: true, message: 'Payment successful', paymentResult }
				: {
					valid: false,
					message: 'Payment failed',
					paymentResult: null,
				  };
		}

		console.warn(
			`Skipping block ${ blockId } - missing payment data or client secret`
		);
		return false;
	} catch ( error ) {
		console.error( 'Payment processing failed:', error );
		return false;
	}
}
