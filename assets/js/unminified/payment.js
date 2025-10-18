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

	// Validate payment items.
	const paymentItemsAttr = paymentInput.getAttribute( 'data-payment-items' );

	if ( ! paymentItemsAttr || paymentItemsAttr.trim() === '' ) {
		return {
			valid: false,
			slug: 'no-payment-items',
			message: 'Payment Input is not configured.',
		};
	}

	// Parse payment items JSON
	let paymentItemsData;
	try {
		paymentItemsData = JSON.parse( paymentItemsAttr );
	} catch ( error ) {
		return {
			valid: false,
			slug: 'invalid-payment-items-json',
			message: 'Payment configuration error. Please contact support.',
		};
	}

	// Check if paymentItems array exists and has items
	if (
		! paymentItemsData.paymentItems ||
		! Array.isArray( paymentItemsData.paymentItems ) ||
		paymentItemsData.paymentItems.length === 0
	) {
		return {
			valid: false,
			slug: 'no-payment-items',
			message: 'Payment Input is not configured.',
		};
	}

	// Validate payment item amounts (check if linked fields have valid values)
	const paymentItems = paymentItemsData.paymentItems;

	// class="srfm-block-single srfm-block srfm-number-block srf-number-e77b36ba-block  srfm-block-width-100 srfm-slug-number"

	for ( const itemSlug of paymentItems ) {
		// Find the input field by slug
		// Noted: As of now we are allowing the number blocks only for the items, may be latest we add other blocks for the items.
		const itemBlock = form.querySelector(
			`.srfm-number-block.srfm-slug-${ itemSlug }`
		);
		const itemInput = itemBlock?.querySelector( 'input.srfm-input-number' );

		if ( ! itemInput ) {
			return {
				valid: false,
				slug: 'payment-item-field-not-found',
				message: `Payment item field '${ itemSlug }' not found in form.`,
			};
		}

		// Get the field value and validate it's a valid positive number
		const itemValue = parseFloat( itemInput.value.replace( /,/g, '' ) );

		// Clean up the number value.
		if ( isNaN( itemValue ) || itemValue <= 0 ) {
			return {
				valid: false,
				slug: 'invalid-payment-amount',
				message: 'Payment amount must be greater than zero.',
			};
		}
	}

	// Get payment type from block
	const paymentType = paymentBlock.getAttribute( 'data-payment-type' );

	// If subscription, validate name and email fields
	if ( paymentType === 'subscription' ) {
		// Get customer name and email field slugs from subscription plan data
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
				slug: 'subscription-name-not-mapped',
				message:
					'Name field is required for subscription payments. Please configure it in the payment block settings.',
			};
		}

		// Validate that email field is mapped
		if (
			! customerEmailFieldSlug ||
			customerEmailFieldSlug.trim() === ''
		) {
			return {
				valid: false,
				slug: 'subscription-email-not-mapped',
				message:
					'Email field is required for subscription payments. Please configure it in the payment block settings.',
			};
		}

		// Find the actual name input field in the form
		const nameInput = form.querySelector(
			`.srfm-input-block.srfm-slug-${ customerNameFieldSlug } .srfm-input-common`
		);
		const nameInputValue = nameInput.value ? nameInput.value.trim() : '';

		if ( ! nameInput || nameInputValue === '' ) {
			return {
				valid: false,
				slug: 'subscription-name-field-not-found',
				message:
					'Name field is required for subscription payments. Please configure it in the payment block settings.',
			};
		}

		// Find the actual email input field in the form
		const emailInput = form.querySelector(
			`.srfm-email-block.srfm-slug-${ customerEmailFieldSlug } .srfm-input-common`
		);

		const emailInputValue = emailInput.value ? emailInput.value.trim() : '';

		if ( ! emailInput || emailInputValue === '' ) {
			return {
				valid: false,
				slug: 'subscription-email-field-not-found',
				message:
					'Email field is required for subscription payments. Please configure it in the payment block settings.',
			};
		}
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

	// Handle subscription vs one-time payment confirmation
	if ( paymentType === 'subscription' ) {
		return await confirmSubscription( blockId, paymentData, form );
	}
	return await confirmOneTimePayment( blockId, paymentData, form );
}

/**
 * Confirm one-time payment
 * @param {string}      blockId     - Block ID.
 * @param {Object}      paymentData - The payment data.
 * @param {HTMLElement} form        - The form element.
 * @return {Promise<string>} The payment intent ID if successful.
 */
async function confirmOneTimePayment( blockId, paymentData, form ) {
	const { stripe, elements, clientSecret } = paymentData;

	// Confirm the payment
	const confirmPaymentResult = await stripe.confirmPayment( {
		elements,
		clientSecret,
		confirmParams: {
			return_url: window.location.href,
			payment_method_data: {
				billing_details: {
					// email: extractBillingEmail( form, blockId ),
					email: 'test@gmail.com',
				},
			},
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

		return paymentIntent.id;
	}
	throw new Error( `Payment not completed for block ${ blockId }` );
}

/**
 * Confirm subscription payment with proper Payment Method creation and client secret handling
 * @param {string}      blockId     - Block ID.
 * @param {Object}      paymentData - The payment data.
 * @param {HTMLElement} form        - The form element.
 * @return {Promise<string>} The setup intent ID if successful.
 */
async function confirmSubscription( blockId, paymentData, form ) {
	const { stripe, elements, clientSecret } = paymentData;
	const subscriptionData =
		window.StripePayment.subscriptionIntents[ blockId ];

	console.log(
		`SureForms: Confirming subscription for block ${ blockId } using simple-stripe-subscriptions approach`
	);

	const billingDetails = {
		name: extractBillingName( form, blockId ),
		email: extractBillingEmail( form, blockId ),
	};

	console.log( 'billingDetails:', billingDetails );

	try {
		// Use single confirmPayment approach from simple-stripe-subscriptions
		// This works for both payment intents and subscription confirmations
		const result = await stripe.confirmSetup( {
			elements,
			clientSecret,
			confirmParams: {
				return_url: window.location.href,
				payment_method_data: {
					billing_details: billingDetails,
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
 * @param {HTMLElement} form    - The form element.
 * @param {string}      blockId - Block ID.
 * @return {string} The extracted billing name or a default value.
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
 * @param {HTMLElement} form    - The form element.
 * @param {string}      blockId - Block ID.
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
