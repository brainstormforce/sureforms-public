/**
 * Validate payment block before form submission
 * @param {HTMLElement} form - The form element
 * @return {Object} - Validation result object with { valid: boolean, slug: string, message: string }
 */
function validateThePaymentBlock( form ) {
	// Check if payment block exists & Get payment input element
	const paymentBlock = form.querySelector( '.srfm-block.srfm-payment-block' );

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
	}

	// Get customer field mappings (required for both payment types)
	const customerNameFieldSlug = paymentBlock.getAttribute(
		'data-customer-name-field'
	);
	const customerEmailFieldSlug = paymentBlock.getAttribute(
		'data-customer-email-field'
	);

	// Validate that name field is mapped
	if ( ! customerNameFieldSlug || customerNameFieldSlug.trim() === '' ) {
		return {
			valid: false,
			slug: 'payment-name-not-mapped',
			message:
				'Customer name field is required for payments. Please configure it in the payment block settings.',
		};
	}

	// Validate that email field is mapped
	if ( ! customerEmailFieldSlug || customerEmailFieldSlug.trim() === '' ) {
		return {
			valid: false,
			slug: 'payment-email-not-mapped',
			message:
				'Customer email field is required for payments. Please configure it in the payment block settings.',
		};
	}

	// Find the actual name input field in the form
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
			const paymentResult = await srfmConfirmPayment(
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

			console.log( 'Payment completed:', paymentResult );

			// Return true if payment succeeded (result is truthy and not empty string)
			// return paymentResult && '' !== paymentResult;
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

/**
 * Confirm payment for a specific block
 * @param {string}      blockId     - The block ID.
 * @param {Object}      paymentData - The payment data.
 * @param {HTMLElement} form        - The form element.
 * @return {Promise<string>} The payment intent or setup intent ID if successful.
 */
async function srfmConfirmPayment( blockId, paymentData, form ) {
	const { elements, paymentType } = paymentData;

	// Validate card details AFTER payment intent is created but BEFORE confirmation
	// This is the correct timing to avoid card data loss
	const { error: submitError } = await elements.submit();

	if ( submitError ) {
		console.error( 'Card validation failed:', submitError );
		throw new Error( submitError.message );
	}

	console.log(
		`Card validation successful for block ${ blockId } paymentType: ${ paymentType }`
	);

	// Handle payment confirmation via unified handler
	return await confirmStripePayment( blockId, paymentData, form );
}

/**
 * Unified confirm handler for one-time payments and subscriptions
 * @param {string}      blockId     - Block ID.
 * @param {Object}      paymentData - The payment data.
 * @param {HTMLElement} form        - The form element.
 * @return {Promise<string>} The intent ID (payment or setup) if successful.
 */
async function confirmStripePayment( blockId, paymentData, form ) {
	const { stripe, elements, clientSecret, paymentType } = paymentData;

	// Get the payment block element
	const paymentBlock = form.querySelector( `[data-block-id="${ blockId }"]` );
	// Update form input with subscription data for backend processing
	const paymentInput = paymentBlock.querySelector(
			'.srfm-payment-input'
		);

	const amount = window.StripePayment.getPaymentAmount( paymentInput );
	const amountType =
		paymentInput.getAttribute( 'data-amount-type' ) || 'fixed';

	// Prepare billing details using StripePayment class methods
	const billingDetails = {
		name: window.StripePayment.extractBillingName( form, paymentBlock ),
		email: window.StripePayment.extractBillingEmail( form, paymentBlock ),
	};

	const stripeArgs = {
		elements,
		clientSecret,
		confirmParams: {
			return_url: window.location.href,
			payment_method_data: {
				billing_details: billingDetails,
			},
		},
		redirect: 'if_required',
	}

	if ( paymentType === 'subscription' ) {
		const subscriptionData =
			window.StripePayment.subscriptionIntents[ blockId ];

		const result = await stripe.confirmSetup( stripeArgs );

		if ( result.error ) {
			throw new Error( result.error.message || result.error );
		}

		const inputValueData = {
			paymentId: result.setupIntent.payment_method,
			setupIntent: result.setupIntent.id,
			subscriptionId: subscriptionData?.subscriptionId,
			customerId: subscriptionData?.customerId,
			blockId,
			paymentType: 'stripe-subscription',
			status: 'succeeded',
			amountType,
			amount,
		};

		paymentInput.value = JSON.stringify( inputValueData );

		return result.setupIntent.id;
	}

	// Handle one-time payment
	const confirmPaymentResult = await stripe.confirmPayment( stripeArgs );

	const { error, paymentIntent } = confirmPaymentResult;

	if ( error ) {
		throw new Error( error.message || error );
	}

	if (
		paymentIntent.status === 'succeeded' ||
		paymentIntent.status === 'requires_capture'
	) {
		const prepareInputValueData = {
			paymentId: paymentIntent.id,
			blockId,
			paymentType: 'stripe',
			amountType,
			amount,
		};

		paymentInput.value = JSON.stringify( prepareInputValueData );

		return paymentIntent.id;
	}
	throw new Error( `Payment not completed for block ${ blockId }` );
}
