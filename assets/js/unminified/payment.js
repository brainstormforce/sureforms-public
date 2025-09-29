/**
 * Main payment handler function called from form submission
 * @param {HTMLElement} form - The form element.
 */
export async function handleFormPayment( form ) {
	try {
		// Check if form has payment blocks
		const paymentBlocks = form.querySelectorAll(
			'.srfm-block.srfm-payment-block'
		);

		if ( paymentBlocks.length === 0 ) {
			return true; // No payment blocks, continue with form submission
		}

		// Process all payments
		const paymentResult = await processAllPayments( form );

		return paymentResult;
	} catch ( error ) {
		console.error( 'Payment processing failed:', error );
		return false;
	}
}

/**
 * Process all payments when form is submitted
 * @param {HTMLElement} form - The form element.
 */
async function processAllPayments( form ) {
	const paymentBlocks = form.querySelectorAll(
		'.srfm-block.srfm-payment-block'
	);

	if ( paymentBlocks.length === 0 ) {
		return true; // No payment blocks, return success
	}

	try {
		// Step 1: Create payment intents for all payment blocks
		await window.StripePayment.createPaymentIntentsForForm( form );

		// Step 2: Confirm all payments with enhanced error handling
		const paymentPromises = [];

		paymentBlocks.forEach( ( block ) => {
			const blockId = block.getAttribute( 'data-block-id' );
			const paymentData = window.srfmPaymentElements?.[ blockId ];
			const paymentType = paymentData?.paymentType || 'one-time';

			console.log( 'blockId', { paymentData } );

			if ( paymentData && paymentData.clientSecret ) {
				// Wrap each confirmation in individual error handling
				const paymentPromise = srfmConfirmPayment(
					blockId,
					paymentData,
					form
				).catch( ( error ) => {
					// Enhanced error reporting per block
					const errorMessage = `${
						paymentType.charAt( 0 ).toUpperCase() +
						paymentType.slice( 1 )
					} payment failed for block ${ blockId }: ${
						error.message
					}`;
					console.error( 'error from confirmPayment:', errorMessage );
				} );

				paymentPromises.push( paymentPromise );
			} else {
				console.warn(
					`Skipping block ${ blockId } - missing payment data or client secret`
				);
			}
		} );

		console.log(
			`Processing ${ paymentPromises.length } payment confirmations...`
		);

		// Wait for all payments to complete
		const paymentResults = await Promise.all( paymentPromises );

		console.log( 'All payments completed successfully:', paymentResults );

		// print the paymentResults is contain the true payment then return true else return false.
		const paymentResult = paymentResults.some(
			( result ) =>
				result &&
				( result?.status === 'requires_capture' || result?.id )
		);
		return paymentResult;
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
 */
async function srfmConfirmPayment( blockId, paymentData, form ) {
	const { elements, paymentType } = paymentData;

	// First submit the elements
	const { error: submitError } = await elements.submit();

	console.log( 'srfmConfirmPayment submitError', {
		submitError,
		paymentData,
	} );

	if ( submitError ) {
		throw new Error( submitError.message );
	}

	// Handle subscription vs one-time payment confirmation
	if ( paymentType === 'subscription' ) {
		return await confirmSubscription( blockId, paymentData, form );
	}
	return await confirmOneTimePayment( blockId, paymentData, form );
}

/**
 * Confirm one-time payment
 * @param blockId
 * @param paymentData
 * @param form
 */
async function confirmOneTimePayment( blockId, paymentData, form ) {
	const { stripe, elements, clientSecret } = paymentData;

	// Confirm the payment
	const confirmPaymentResult = await stripe.confirmPayment( {
		elements,
		clientSecret,
		confirmParams: {
			return_url: window.location.href,
		},
		redirect: 'if_required',
	} );

	console.log( 'confirmPaymentResult->', confirmPaymentResult );

	const { error, paymentIntent } = confirmPaymentResult;

	if ( error ) {
		throw new Error( error );
	}

	if (
		paymentIntent.status === 'succeeded' ||
		paymentIntent.status === 'requires_capture'
	) {
		console.log( `Payment succeeded for block ${ blockId }` );

		// update the payment detail in the input value by the json stringify.
		const getPaymentBlock = form.querySelector(
			`[data-block-id="${ blockId }"]`
		);
		const getPaymentInput = getPaymentBlock.querySelector(
			'.srfm-payment-input'
		);

		const getItems = getPaymentInput.getAttribute( 'data-payment-items' );
		const jsonParseItems = JSON.parse( getItems );

		let prepareInputValueData = {
			paymentItems: jsonParseItems,
			paymentId: paymentIntent.id,
			blockId,
			paymentType: 'stripe',
		};

		prepareInputValueData = JSON.stringify( prepareInputValueData );

		getPaymentInput.value = prepareInputValueData;

		return paymentIntent;
	}
	throw new Error( `Payment not completed for block ${ blockId }` );
}

/**
 * Confirm subscription payment with proper Payment Method creation and client secret handling
 * @param blockId
 * @param paymentData
 * @param form
 */
async function confirmSubscription( blockId, paymentData, form ) {
	const { stripe, elements, clientSecret } = paymentData;
	const subscriptionData =
		window.StripePayment.subscriptionIntents[ blockId ];

	console.log(
		`SureForms: Confirming subscription for block ${ blockId } using simple-stripe-subscriptions approach`
	);

	try {
		// Use single confirmPayment approach from simple-stripe-subscriptions
		// This works for both payment intents and subscription confirmations
		const result = await stripe.confirmSetup( {
			elements,
			clientSecret,
			confirmParams: {
				return_url: window.location.href,
				payment_method_data: {
					billing_details: {
						name: extractBillingName( form, blockId ),
						email: extractBillingEmail( form, blockId ),
					},
				},
			},
			redirect: 'if_required',
		} );

		console.log(
			`confirmPaymentResult SureForms: Subscription confirmation result for block ${ blockId }:`,
			result
		);

		if ( result.error ) {
			console.error(
				`SureForms: Subscription confirmation failed for block ${ blockId }:`,
				result.error
			);
		} else {
			// Payment succeeded - subscription automatically activated by Stripe like simple-stripe-subscriptions
			// Update form input with subscription data for backend processing
			const paymentBlock = form.querySelector(
				`[data-block-id="${ blockId }"]`
			);
			const paymentInput = paymentBlock.querySelector(
				'.srfm-payment-input'
			);

			const existingItems =
				paymentInput.getAttribute( 'data-payment-items' );
			const jsonParseItems = JSON.parse( existingItems );

			const inputValueData = {
				paymentId: result.setupIntent.payment_method,
				setupIntent: result.setupIntent.id,
				subscriptionId: subscriptionData?.subscriptionId,
				customerId: subscriptionData?.customerId,
				blockId,
				paymentType: 'stripe-subscription',
				status: 'succeeded',
				paymentItems: jsonParseItems,
			};

			paymentInput.value = JSON.stringify( inputValueData );

			return result.setupIntent.id;
		}
	} catch ( error ) {
		console.error(
			`SureForms: Error confirming subscription for block ${ blockId }:`,
			error
		);
		throw new Error(
			`Subscription confirmation failed: ${ error.message }`
		);
	}
}

/**
 * Extract billing name from form fields
 * @param form
 * @param blockId
 */
function extractBillingName( form, blockId ) {
	// Try to find name fields in the form with priority order
	const nameSelectors = [
		'input[name*="first_name"], input[name*="last_name"]', // Full name fields
		'input[name*="name"]', // General name fields
		'input[name*="billing_name"]', // Billing specific
		'input[type="text"]', // Fallback to any text input
	];

	for ( const selector of nameSelectors ) {
		const nameInputs = form.querySelectorAll( selector );
		const names = [];

		for ( const input of nameInputs ) {
			if ( input.value && input.value.trim() && ! input.hidden ) {
				names.push( input.value.trim() );
			}
		}

		if ( names.length > 0 ) {
			return names.join( ' ' );
		}
	}

	console.warn(
		`SureForms: No billing name found for block ${ blockId }, using default`
	);
	return 'SureForms Customer';
}

/**
 * Extract billing email from form fields
 * @param form
 * @param blockId
 */
function extractBillingEmail( form, blockId ) {
	// Try to find email fields in the form with priority order
	const emailSelectors = [
		'input[name*="billing_email"]', // Billing specific email
		'input[type="email"]', // Email input type
		'input[name*="email"]', // General email fields
	];

	for ( const selector of emailSelectors ) {
		const emailInputs = form.querySelectorAll( selector );

		for ( const input of emailInputs ) {
			if ( input.value && input.value.trim() && ! input.hidden ) {
				const email = input.value.trim();
				// Validate email format
				const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
				if ( emailRegex.test( email ) ) {
					return email;
				}
			}
		}
	}

	console.warn(
		`SureForms: No valid billing email found for block ${ blockId }, using default`
	);
	return 'customer@example.com';
}
