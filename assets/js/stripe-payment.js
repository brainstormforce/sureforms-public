/**
 * SureForms Stripe Payment Integration
 *
 * @since x.x.x
 */
/* global Stripe, srfm_ajax */
class StripePayment {
	// Store Stripe instances
	static stripeInstances = {};
	static paymentElements = {};
	static paymentIntents = {};
	static subscriptionIntents = {};
	static slugForPayment = [];

	// Initialize on page load
	static {
		window.srfmPaymentElements = this.paymentElements;
	}

	/**
	 * Constructor for the Calculations class.
	 * @param {HTMLElement} form - The form element containing calculation fields.
	 */
	constructor( form ) {
		this.form = form;

		// Find all payment blocks within the form.
		const getPaymentFields = this.form.querySelectorAll(
			'.srfm-block.srfm-payment-block'
		);

		// Initialize Stripe payment for each payment field.
		getPaymentFields.forEach( ( field ) => {
			this.processPayment( field );
		} );

		// Listen the form input changes.
		this.listen_the_form_input_changes();
	}

	listen_the_form_input_changes() {
		// if the slug is in the slugForPayment array, then listen the input change
		if ( StripePayment.slugForPayment.length > 0 ) {
			StripePayment.slugForPayment.forEach( ( slug ) => {
				this.form
					.querySelectorAll(
						`.srfm-slug-${ slug } .srfm-input-number`
					)
					.forEach( ( input ) => {
						input.addEventListener( 'input', () => {
							// Update payment intent when value changes
							this.updatePaymentIntent( { slug } );
						} );

						// update payment intent without input change.
						this.updatePaymentIntent( { slug } );
					} );
			} );
		}
	}

	/**
	 * Refine the amount by removing format characters based on format type.
	 * Converts formatted numbers to clean decimal strings for calculation.
	 *
	 * @param {HTMLElement} input - The input element with format-type attribute.
	 * @return {string} Clean number string (e.g., "1234.56").
	 */
	refineTheAmount( input ) {
		const rawValue = input?.value || '0';
		const formatType = input?.getAttribute( 'format-type' ) || 'us-style';

		// Handle empty or invalid input
		if ( ! rawValue || rawValue.trim() === '' ) {
			return '0';
		}

		let cleanedValue = rawValue.trim();

		if ( 'eu-style' === formatType ) {
			// EU-style: 1.234,56
			// Remove dots (thousands separator): 1234,56
			// Replace comma (decimal separator) with dot: 1234.56
			cleanedValue = cleanedValue
				.replace( /\./g, '' ) // Remove all dots
				.replace( ',', '.' ); // Replace comma with dot
		} else {
			// US-style: 1,234.56 (default)
			// Remove commas (thousands separator): 1234.56
			// Keep dot (decimal separator): 1234.56
			cleanedValue = cleanedValue.replace( /,/g, '' ); // Remove all commas
		}

		// Validate the result is a valid number
		const numericValue = parseFloat( cleanedValue );
		if ( isNaN( numericValue ) ) {
			console.warn(
				'SureForms: Invalid number value after refinement:',
				rawValue
			);
			return '0';
		}

		return cleanedValue;
	}

	updatePaymentIntent( args ) {
		const fieldSlug = args.slug;
		const getPaymentHiddenInputs = this.form.querySelectorAll(
			'.srfm-payment-input[data-payment-items]'
		);

		for ( const paymentHiddenInput of getPaymentHiddenInputs ) {
			const getTheSlug = this.getSlugForPayment( paymentHiddenInput );

			const paymentDetails = [];

			// If in the slug array, then update the payment intent
			if ( getTheSlug.includes( fieldSlug ) ) {
				// paymentHiddenInput is the payment configuration wrapper in which we need to add all the payment details.
				for ( const slug of getTheSlug ) {
					const getThePaymentItem = this.form.querySelector(
						`.srfm-slug-${ slug } .srfm-input-number`
					);

					if ( ! getThePaymentItem ) {
						continue;
					}

					// Get and refine the amount based on format type
					const getTheAmount = this.refineTheAmount( getThePaymentItem );
					const getTheTitle = getThePaymentItem
						.closest( '.srfm-block' )
						.querySelector( '.srfm-block-label' ).textContent;

					paymentDetails.push( {
						title: getTheTitle,
						amount: getTheAmount,
					} );
				}

				// new amount
				const getPaymentItemBlockWrapper =
					paymentHiddenInput.closest( '.srfm-block' );
				const blockId =
					getPaymentItemBlockWrapper.getAttribute( 'data-block-id' );
				const getPaymentItemWrapperHTML =
					getPaymentItemBlockWrapper.querySelector(
						'.srfm-payment-value'
					);

				// Generate HTML for payment items and calculate total in single loop
				let totalAmount = 0;
				if ( paymentDetails.length > 0 ) {
					paymentDetails.forEach( ( item ) => {
						const itemAmount = parseFloat( item.amount || 0 );
						totalAmount += itemAmount;
					} );
				}

				// Update the main payment value display
				if ( getPaymentItemWrapperHTML ) {
					getPaymentItemWrapperHTML.textContent = `$${ totalAmount.toFixed(
						2
					) }`;
				}

				this.updatePaymentIntentAmount(
					blockId,
					totalAmount,
					paymentHiddenInput
				);
			}
		}
	}

	updatePaymentIntentAmount( blockId, newAmount, paymentHiddenInput ) {
		console.log( `Amount updated for block ${ blockId }: $${ newAmount }` );

		// In deferred mode, we update the Elements configuration instead of payment intent
		const elementData = StripePayment.paymentElements[ blockId ];
		if ( ! elementData ) {
			return;
		}

		// Don't update if amount hasn't changed significantly
		const previousAmount = elementData.currentAmount || 0;
		if ( Math.abs( newAmount - previousAmount ) < 0.01 ) {
			return;
		}

		try {
			// Update elements based on payment type (subscription vs one-time)
			const currency = paymentHiddenInput.dataset.currency || 'usd';
			const paymentType = elementData.paymentType || 'one-time';

			if ( paymentType === 'subscription' ) {
				// For subscriptions: use mode 'subscription', NO captureMethod
				elementData.elements.update( {
					mode: 'subscription',
					amount: Math.round( newAmount * 100 ),
					currency,
				} );
			} else {
				// For one-time payments: use mode 'payment' with captureMethod 'manual'
				elementData.elements.update( {
					mode: 'payment',
					amount: Math.round( newAmount * 100 ),
					currency,
					captureMethod: 'manual',
				} );
			}

			// Store new amount for later use
			elementData.currentAmount = newAmount;

			console.log(
				`SureForms: Elements updated with new amount $${ newAmount } for block ${ blockId } (type: ${ paymentType })`
			);
		} catch ( error ) {
			console.warn(
				`SureForms: Failed to update amount for block ${ blockId }:`,
				error
			);
			// Continue without throwing - non-critical failure
		}
	}

	/**
	 * Determine if validation error should be displayed to user.
	 * Filters out common typing-related incomplete warnings.
	 *
	 * @param {Object} error - The error object returned from Stripe or validation.
	 * @return {boolean} True if the error should be displayed to the user, false otherwise.
	 */
	shouldDisplayValidationError( error ) {
		// Don't show incomplete errors while user is still typing
		const incompleteErrors = [
			'incomplete_number',
			'incomplete_cvc',
			'incomplete_expiry',
			'incomplete_zip',
		];

		// Don't show these common incomplete errors that occur during typing
		if ( incompleteErrors.includes( error.code ) ) {
			return false;
		}

		// Show invalid format errors and other validation errors
		return (
			error.type === 'validation_error' &&
			( error.code === 'invalid_number' ||
				error.code === 'invalid_expiry_month' ||
				error.code === 'invalid_expiry_year' ||
				error.code === 'invalid_cvc' ||
				error.code === 'card_declined' )
		);
	}

	/**
	 * Create payment intent only during form submission (deferred pattern).
	 * This method creates payment intent and updates elements with client secret.
	 *
	 * @param {string}      blockId      - Block ID.
	 * @param {number}      amount       - The amount to create the payment intent for.
	 * @param {HTMLElement} paymentInput - The payment input element.
	 * @return {Promise<Object>} Resolves with an object containing clientSecret and paymentIntentId.
	 */
	async createPaymentIntentOnSubmission( blockId, amount, paymentInput ) {
		if ( amount <= 0 ) {
			throw new Error(
				'createPaymentIntentOnSubmission: Amount must be greater than 0'
			);
		}

		// In deferred mode, we always create a new payment intent during submission
		console.log(
			`SureForms: Creating payment intent for block ${ blockId } during form submission`
		);
		this.set_block_loading( blockId, true );

		const currency = paymentInput.dataset.currency || 'usd';
		const description =
			paymentInput.dataset.description || 'SureForms Payment';

		const data = new FormData();
		data.append( 'action', 'srfm_create_payment_intent' );
		data.append( 'nonce', srfm_ajax.nonce );
		data.append( 'amount', parseInt( amount * 100 ) );
		data.append( 'currency', currency );
		data.append( 'description', description );
		data.append( 'block_id', blockId );

		try {
			const response = await fetch( srfm_ajax.ajax_url, {
				method: 'POST',
				body: data,
			} );

			const responseData = await response.json();

			this.set_block_loading( blockId, false );

			console.log( 'SureForms: Response data:', responseData );

			if ( responseData.success ) {
				const clientSecret = responseData.data.client_secret;
				const paymentIntentId = responseData.data.payment_intent_id;

				// Store payment intent ID
				StripePayment.paymentIntents[ blockId ] = paymentIntentId;

				// Update the existing elements with the client secret
				const elementData = StripePayment.paymentElements[ blockId ];
				if ( elementData ) {
					// CRITICAL FIX: Store client secret WITHOUT calling elements.update()
					// Calling elements.update() with clientSecret causes Stripe to re-initialize
					// the payment element, which clears all user-entered card data
					// The clientSecret will be used directly in stripe.confirmPayment() instead
					elementData.clientSecret = clientSecret;

					console.log(
						`SureForms: Client secret stored for block ${ blockId } (card data preserved) clientSecret : ${ clientSecret }`
					);
				}

				return { clientSecret, paymentIntentId };
			}
			throw new Error(
				responseData.data || 'Failed to create payment intent'
			);
		} catch ( error ) {
			this.set_block_loading( blockId, false );
			console.error( 'Error creating payment intent:', error );
			throw error;
		}
	}

	getSlugForPayment( input ) {
		try {
			const slugForPayment = input.dataset.paymentItems;
			if ( ! slugForPayment ) {
				return null;
			}

			const paymentItems = JSON.parse( slugForPayment );

			return paymentItems?.paymentItems || null;
		} catch ( error ) {
			return null;
		}
	}

	processPayment( field ) {
		const paymentInput = field.querySelector( 'input.srfm-payment-input' );
		const blockId = field.getAttribute( 'data-block-id' );

		if ( ! paymentInput ) {
			console.error(
				'SureForms: Payment input not found for block',
				blockId
			);
			return;
		}

		const slugForPayment = this.getSlugForPayment( paymentInput );

		if ( ! slugForPayment ) {
			console.error(
				'SureForms: Payment items like data-payment-items not found for block' +
					blockId,
				blockId
			);
			return;
		}

		// push the slugs in the variables
		StripePayment.slugForPayment = [
			...new Set( [
				...StripePayment.slugForPayment,
				...slugForPayment,
			] ),
		];

		// Check payment type from data attribute
		const paymentType =
			field.getAttribute( 'data-payment-type' ) || 'one-time';

		console.log(
			`SureForms: Initializing payment for block ${ blockId }, type: ${ paymentType }`
		);

		// Initialize Stripe elements based on payment type
		if ( paymentType === 'subscription' ) {
			this.initializeSubscriptionElements( blockId, paymentInput );
		} else {
			this.initializeStripeElements( blockId, paymentInput );
		}
	}

	/**
	 * Initialize Stripe elements with deferred payment intent creation.
	 * Uses real amount calculation to fix "card element is incomplete" errors.
	 * Payment intent will be created only during form submission.
	 *
	 * @param {string}      blockId      - Block ID.
	 * @param {HTMLElement} paymentInput - The payment input element.
	 * @return {void} This function does not return a value.
	 */
	initializeStripeElements( blockId, paymentInput ) {
		// CRITICAL: Check if elements already exist to prevent re-initialization
		// Re-mounting elements destroys user-entered card data
		if ( StripePayment.paymentElements[ blockId ] ) {
			console.log(
				`SureForms: ONE-TIME PAYMENT elements already initialized for block ${ blockId }, skipping re-initialization (preserving card data)`
			);
			return;
		}

		console.log(
			`SureForms: Initializing ONE-TIME PAYMENT elements for block ${ blockId } with mode: payment, captureMethod: manual`
		);

		const stripeKey = paymentInput.dataset.stripeKey;

		if ( ! stripeKey ) {
			console.error(
				'SureForms: Stripe key is required for payment initialization.'
			);
			return;
		}

		const elementContainer = paymentInput
			.closest( '.srfm-block' )
			.querySelector( '.srfm-stripe-payment-element' );

		// Initialize Stripe
		if ( ! StripePayment.stripeInstances[ blockId ] ) {
			StripePayment.stripeInstances[ blockId ] = Stripe( stripeKey );
		}

		const stripe = StripePayment.stripeInstances[ blockId ];

		// Get real payment amount from form (not placeholder)
		const currentAmount = this.calculateCurrentAmount( paymentInput );

		// Initialize Elements with mode and captureMethod to match backend configuration
		// captureMethod: 'manual' tells Elements to expect manual capture PaymentIntents
		// This prevents "capture_method mismatch" errors when backend uses manual capture
		const elements = stripe.elements( {
			mode: 'payment',
			amount: Math.round( currentAmount * 100 ),
			currency: paymentInput.dataset.currency || 'usd',
			captureMethod: 'manual',
			appearance: {
				theme: 'stripe',
				variables: {
					colorPrimary: '#0073aa',
					colorBackground: '#ffffff',
					colorText: '#424242',
					colorDanger: '#df1b41',
					spacingUnit: '4px',
					borderRadius: '4px',
					fontFamily: '"Manrope", sans-serif', // ✅ Use quotes for fonts with name + fallback
				},
			},
		} );

		// Create payment element
		const paymentElement = elements.create( 'payment' );
		paymentElement.mount( elementContainer );

		// Store references without payment intent (will be created on submission)
		StripePayment.paymentElements[ blockId ] = {
			stripe,
			elements,
			paymentElement,
			clientSecret: null, // Will be set when payment intent is created
			currentAmount, // Store current amount for later comparison
		};

		// Update window object
		window.srfmPaymentElements = StripePayment.paymentElements;

		// Handle payment element events
		paymentElement.on( 'ready', () => {
			console.log(
				'SureForms: Payment element ready for block',
				blockId
			);
		} );

		paymentElement.on( 'change', ( event ) => {
			// Handle element validation with improved error filtering
			if ( event.error ) {
				// Only show meaningful validation errors, skip incomplete warnings during typing
				if ( this.shouldDisplayValidationError( event.error ) ) {
					console.warn(
						`SureForms: Card element validation for block ${ blockId }:`,
						event.error
					);
					this.displayElementError( blockId, event.error );
				}
			} else if ( event.complete ) {
				console.log(
					`SureForms: Card element completed for block ${ blockId }`
				);
				// Clear any previous error messages
				this.clearElementError( blockId );
			}
		} );
	}

	/**
	 * Calculate current payment amount from form.
	 *
	 * @param {HTMLElement} paymentInput - The payment input element.
	 * @return {number} The calculated amount in dollars.
	 */
	calculateCurrentAmount( paymentInput ) {
		// Get payment items configuration
		const slugForPayment = this.getSlugForPayment( paymentInput );

		if ( ! slugForPayment || slugForPayment.length === 0 ) {
			// No dynamic items, check for fixed amount in payment input
			const fixedAmount =
				paymentInput.dataset.amount || paymentInput.value || '10.00';
			return parseFloat( fixedAmount ) || 10.0; // Default to $10 if no amount found
		}

		// Calculate total from dynamic payment items
		let totalAmount = 0;
		for ( const slug of slugForPayment ) {
			const paymentItem = this.form.querySelector(
				`.srfm-slug-${ slug } .srfm-input-number`
			);

			if ( paymentItem ) {
				const itemAmount = parseFloat( paymentItem.value || 0 );
				totalAmount += itemAmount;
			}
		}

		// Return at least $1.00 minimum for Stripe
		const finalAmount = Math.max( totalAmount, 1.0 );

		// Validate the amount
		if ( ! StripePayment.validatePaymentAmount( finalAmount ) ) {
			console.warn(
				'SureForms: Invalid payment amount, using minimum $1.00'
			);
			return 1.0;
		}

		return finalAmount;
	}

	/**
	 * Set or unset loading state for a payment block and its submit button.
	 *
	 * @param {string}  blockId        - The unique identifier for the payment block.
	 * @param {boolean} [loading=true] - Whether to set (true) or unset (false) the loading state.
	 * @return {void} This function does not return a value.
	 */
	set_block_loading( blockId, loading = true ) {
		const block = this.form.querySelector(
			`.srfm-block[data-block-id="${ blockId }"]`
		);

		if ( ! block ) {
			return;
		}

		const submitButton = this.form.querySelector( '.srfm-submit-button' );

		if ( loading ) {
			block.classList.add( 'srfm-loading-block' );
			submitButton.classList.add( 'srfm-loading-button' );
		} else {
			block.classList.remove( 'srfm-loading-block' );
			submitButton.classList.remove( 'srfm-loading-button' );
		}
	}

	/**
	 * Initialize Stripe elements for subscriptions.
	 *
	 * @param {string}      blockId      - Block ID.
	 * @param {HTMLElement} paymentInput - The payment input element.
	 * @return {void} This function does not return a value.
	 */
	initializeSubscriptionElements( blockId, paymentInput ) {
		// CRITICAL: Check if elements already exist to prevent re-initialization
		// Re-mounting elements destroys user-entered card data
		if ( StripePayment.paymentElements[ blockId ] ) {
			const existingMode =
				StripePayment.paymentElements[ blockId ].paymentType;
			console.log(
				`SureForms: SUBSCRIPTION elements already initialized for block ${ blockId } (existing type: ${ existingMode }), skipping re-initialization (preserving card data)`
			);
			return;
		}

		console.log(
			`SureForms: Initializing SUBSCRIPTION elements for block ${ blockId } with mode: subscription`
		);

		const stripeKey = paymentInput.dataset.stripeKey;

		if ( ! stripeKey ) {
			console.error(
				'SureForms: Stripe key is required for subscription initialization.'
			);
			return;
		}

		const elementContainer = paymentInput
			.closest( '.srfm-block' )
			.querySelector( '.srfm-stripe-payment-element' );

		// Initialize Stripe
		if ( ! StripePayment.stripeInstances[ blockId ] ) {
			StripePayment.stripeInstances[ blockId ] = Stripe( stripeKey );
		}

		const stripe = StripePayment.stripeInstances[ blockId ];

		// Initialize Elements with subscription mode for subscriptions
		// Subscription mode is used with confirmSetup() and SetupIntent
		// captureMethod is NOT applicable for subscriptions (only for one-time payments)
		const elements = stripe.elements( {
			mode: 'subscription',
			currency: paymentInput.dataset.currency || 'usd',
			amount: 1200, // Placeholder amount in cents ($12.00), will be updated by backend subscription
			appearance: {
				theme: 'stripe',
				variables: {
					colorPrimary: '#0073aa',
					colorBackground: '#ffffff',
					colorText: '#424242',
					colorDanger: '#df1b41',
					spacingUnit: '4px',
					borderRadius: '4px',
					fontFamily: '"Manrope", sans-serif', // ✅ Use quotes for fonts with name + fallback
				},
			},
		} );

		// Create payment element for subscriptions
		const paymentElement = elements.create( 'payment' );
		paymentElement.mount( elementContainer );

		// Store references without subscription intent
		StripePayment.paymentElements[ blockId ] = {
			stripe,
			elements,
			paymentElement,
			clientSecret: null, // Will be set when subscription intent is created
			paymentType: 'subscription',
		};

		// Update window object
		window.srfmPaymentElements = StripePayment.paymentElements;

		// Handle payment element events
		paymentElement.on( 'ready', () => {
			console.log(
				'SureForms: Subscription element ready for block',
				blockId
			);
		} );

		paymentElement.on( 'change', ( event ) => {
			// Handle element validation errors without disrupting payment flow
			if ( event.error ) {
				console.warn(
					`SureForms: Subscription element validation warning for block ${ blockId }:`,
					event.error
				);
				// Don't throw errors here as they're often non-fatal validation warnings
			} else if ( event.complete ) {
				console.log(
					`SureForms: Subscription element completed for block ${ blockId }`
				);
			}
		} );
	}

	/**
	 * Create subscription intent using simplified approach like simple-stripe-subscriptions.
	 *
	 * @param {string}      blockId      - Block ID.
	 * @param {number}      amount       - The amount for the subscription.
	 * @param {HTMLElement} paymentInput - The payment input element.
	 * @return {Promise<Object>} Resolves with an object containing clientSecret, subscriptionId, customerId, and paymentIntentId.
	 */
	async createSubscriptionIntentOnSubmission(
		blockId,
		amount,
		paymentInput
	) {
		if ( amount <= 0 ) {
			throw new Error(
				'createSubscriptionIntentOnSubmission: Amount must be greater than 0'
			);
		}

		this.set_block_loading( blockId, true );

		const currency = paymentInput.dataset.currency || 'usd';
		const description =
			paymentInput.dataset.description || 'SureForms Subscription';

		// Extract customer data from form
		const customerData = this.extractCustomerData( paymentInput );

		// console.log("SureForms: Creating subscription with data:", customerData);

		const data = new FormData();
		data.append( 'action', 'srfm_create_subscription_intent' );
		data.append( 'nonce', srfm_ajax.nonce );
		data.append( 'amount', parseInt( amount * 100 ) );
		data.append( 'currency', currency );
		data.append( 'description', description );
		data.append( 'block_id', blockId );
		data.append( 'interval', customerData.interval );
		data.append( 'plan_name', customerData.planName );

		try {
			const response = await fetch( srfm_ajax.ajax_url, {
				method: 'POST',
				body: data,
			} );

			const responseData = await response.json();

			console.log( 'SureForms: Subscription response:', responseData );

			this.set_block_loading( blockId, false );

			if ( responseData.success ) {
				const clientSecret = responseData.data.client_secret;
				const subscriptionId = responseData.data.subscription_id;
				const customerId = responseData.data.customer_id;
				const paymentIntentId = responseData.data.payment_intent_id;

				// Store subscription data for form submission
				StripePayment.subscriptionIntents[ blockId ] = {
					subscriptionId,
					customerId,
					paymentIntentId,
					amount,
					interval: customerData.interval,
				};

				// Update elements with client secret
				const elementData = StripePayment.paymentElements[ blockId ];
				if ( elementData ) {
					elementData.clientSecret = clientSecret;

					// CRITICAL FIX: DON NOT call elements.update() for subscriptions
					// This would change the mode and break confirmSetup()
					console.log(
						`SureForms: Client secret stored for subscription block ${ blockId } (preserving subscription mode)`
					);
				}

				return {
					clientSecret,
					subscriptionId,
					customerId,
					paymentIntentId,
					status: true
				};
			} else {
				return {
					status: false,
					message: responseData.data?.message || responseData.data || 'Failed to create subscription',
				}
			}
		} catch ( error ) {
			this.set_block_loading( blockId, false );
			// console.error( 'SureForms: Error creating subscription:', error );
			// throw error;
			return {
				status: false,
				message: error.message || 'Failed to create subscription',
			}
		}
	}

	/**
	 * Extract customer data from form fields or use dummy data.
	 *
	 * @param {HTMLElement} paymentInput - The payment input element.
	 * @return {Object} An object containing name, email, interval, and planName.
	 */
	extractCustomerData( paymentInput ) {
		const form = paymentInput.closest( 'form' );
		const block = paymentInput.closest( '.srfm-block' );

		// Get subscription plan data from block attributes
		const planName =
			block.dataset.subscriptionPlanName || 'Subscription Plan';
		const interval = block.dataset.subscriptionInterval || 'month';
		const customerNameField = block.dataset.customerNameField;
		const customerEmailField = block.dataset.customerEmailField;

		// Try to extract customer data from mapped form fields
		let customerName = 'SureForms Customer';
		let customerEmail = 'customer@example.com';

		if ( customerNameField && form ) {
			const nameInput = form.querySelector(
				`.srfm-input-block.srfm-slug-${ customerNameField } .srfm-input-common`
			);
			if ( nameInput && nameInput.value.trim() ) {
				customerName = nameInput.value.trim();
			}
		}

		if ( customerEmailField && form ) {
			const emailInput = form.querySelector(
				`.srfm-input-block.srfm-slug-${ customerEmailField } .srfm-input-common`
			);
			if ( emailInput && emailInput.value.trim() ) {
				customerEmail = emailInput.value.trim();
			}
		}

		return {
			name: customerName,
			email: customerEmail,
			interval,
			planName,
		};
	}

	/**
	 * Static method to create payment intent for a payment block during form submission.
	 * This should be called from the form submission handler.
	 *
	 * @param {HTMLFormElement} form         - The form element.
	 * @param {HTMLElement}     paymentBlock - The payment block element.
	 * @return {Promise<Object>} Resolves with payment intent or subscription intent data.
	 */
	static async createPaymentIntentsForForm( form, paymentBlock ) {
		const blockId = paymentBlock.getAttribute( 'data-block-id' );
		const paymentInput = paymentBlock.querySelector(
			'input.srfm-payment-input'
		);
		const paymentType =
			paymentBlock.getAttribute( 'data-payment-type' ) || 'one-time';

		if ( ! paymentInput ) {
			throw new Error( `Payment input not found for block ${ blockId }` );
		}

		// Calculate current amount from form
		const paymentValueElement = paymentBlock.querySelector(
			'.srfm-payment-value'
		);
		const amountText = paymentValueElement?.textContent || '$0.00';
		const amount = parseFloat( amountText.replace( /[^0-9.]/g, '' ) ) || 0;

		if ( amount <= 0 ) {
			throw new Error(
				`Invalid payment amount ${ amount } for block ${ blockId }`
			);
		}

		try {
			// Create a temporary instance to call the method
			const tempInstance = new StripePayment( form );

			if ( paymentType === 'subscription' ) {
				const result =
					await tempInstance.createSubscriptionIntentOnSubmission(
						blockId,
						amount,
						paymentInput
					);
				return {
					blockId,
					paymentType: 'subscription',
					...result,
				};
			}

			const result = await tempInstance.createPaymentIntentOnSubmission(
				blockId,
				amount,
				paymentInput
			);
			return {
				blockId,
				paymentType: 'one-time',
				...result,
			};
		} catch ( error ) {
			// console.error(
			// 	`Failed to create ${ paymentType } intent for block ${ blockId }:`,
			// 	error
			// );
			// throw error;
			return {
				status: false,
				message: error.message || 'Failed to create payment intent',
			}
		}
	}

	/**
	 * Confirm payments using simple-stripe-subscriptions approach with enhanced error handling.
	 * This should be called after payment intents are created.
	 *
	 * @param {HTMLFormElement} form           - The form element.
	 * @param {Array<Object>}   paymentResults - The payment results.
	 * @return {Promise<Array<Object>>} Resolves with an array of confirmed payment data.
	 */
	static async confirmPaymentsForForm( form, paymentResults ) {
		const confirmationResults = [];

		// Validate inputs
		if (
			! Array.isArray( paymentResults ) ||
			paymentResults.length === 0
		) {
			console.warn( 'SureForms: No payment results to confirm' );
			return confirmationResults;
		}

		for ( const result of paymentResults ) {
			const { blockId, paymentType, clientSecret } = result;

			// Enhanced validation like simple-stripe-subscriptions
			if ( ! blockId ) {
				console.error( 'SureForms: Missing blockId in payment result' );
				continue;
			}

			if ( ! clientSecret ) {
				console.error(
					`SureForms: No client secret for block ${ blockId }`
				);
				throw new Error(
					`Payment confirmation failed: No client secret for ${ paymentType }`
				);
			}

			const elementData = StripePayment.paymentElements[ blockId ];
			if ( ! elementData ) {
				console.error(
					`SureForms: No Stripe elements found for block ${ blockId }`
				);
				throw new Error(
					`Payment confirmation failed: Stripe elements not initialized for ${ paymentType }`
				);
			}

			const { stripe, elements } = elementData;

			// Validate Stripe instances
			if ( ! stripe || ! elements ) {
				console.error(
					`SureForms: Invalid Stripe instances for block ${ blockId }`
				);
				throw new Error(
					`Payment confirmation failed: Invalid Stripe configuration for ${ paymentType }`
				);
			}

			try {
				console.log(
					`SureForms: Confirming ${ paymentType } for block ${ blockId }`
				);

				// Use single confirmPayment approach from simple-stripe-subscriptions
				const confirmationResult = await stripe.confirmPayment( {
					elements,
					clientSecret,
					confirmParams: {
						return_url: window.location.href,
						payment_method_data: {
							billing_details: {
								email:
									this.extractCustomerEmail( form ) ||
									'customer@example.com',
							},
						},
					},
					redirect: 'if_required',
				} );

				if ( confirmationResult.error ) {
					console.error(
						`SureForms: ${ paymentType } confirmation failed:`,
						confirmationResult.error
					);
				} else {
					console.log(
						`SureForms: ${ paymentType } confirmed successfully for block ${ blockId }`
					);

					// Prepare payment data for form submission like simple-stripe-subscriptions
					const paymentData = {
						blockId,
						paymentType,
						paymentId:
							paymentType === 'subscription'
								? result.subscriptionId
								: result.paymentIntentId,
						subscriptionId:
							paymentType === 'subscription'
								? result.subscriptionId
								: null,
						amount: result.amount,
						interval: result.interval || null,
						status: 'succeeded',
					};

					confirmationResults.push( paymentData );
				}
			} catch ( error ) {
				console.error(
					`SureForms: Error confirming ${ paymentType } for block ${ blockId }:`,
					error
				);
			}
		}

		console.log(
			'SureForms: Payment confirmations complete:',
			confirmationResults
		);
		return confirmationResults;
	}

	/**
	 * Extract customer email from form for billing details.
	 *
	 * @param {HTMLFormElement} form - The form element.
	 * @return {string|null} The customer email if found, otherwise null.
	 */
	static extractCustomerEmail( form ) {
		// Try to find email field in form
		const emailFields = form.querySelectorAll(
			'input[type="email"], input[name*="email"]'
		);
		for ( const field of emailFields ) {
			if ( field.value && field.value.includes( '@' ) ) {
				return field.value.trim();
			}
		}
		return null;
	}

	/**
	 * Display an error message related to the Stripe payment element in the UI.
	 *
	 * @param {string} blockId - The unique identifier for the payment block.
	 * @param {Object} error   - The error object returned from Stripe or validation.
	 * @return {void} This function does not return a value.
	 */
	displayElementError( blockId, error ) {
		const block = this.form.querySelector(
			`.srfm-block[data-block-id="${ blockId }"]`
		);

		if ( ! block ) {
			return;
		}

		// Remove any existing error messages first
		this.clearElementError( blockId );

		// Only show user-friendly errors, skip technical validation warnings
		if (
			error.type === 'validation_error' ||
			error.code === 'incomplete_number' ||
			error.code === 'incomplete_cvc'
		) {
			const errorContainer = document.createElement( 'div' );
			errorContainer.className = 'srfm-stripe-error';
			errorContainer.style.cssText =
				'color: #df1b41; font-size: 14px; margin-top: 8px;';
			errorContainer.textContent =
				error.message || 'Please check your card information.';

			const paymentElement = block.querySelector(
				'.srfm-stripe-payment-element'
			);
			if ( paymentElement ) {
				paymentElement.parentNode.insertBefore(
					errorContainer,
					paymentElement.nextSibling
				);
			}
		}
	}

	/**
	 * Clear element error messages.
	 *
	 * @param {string} blockId - Block ID.
	 * @return {void} This function does not return a value.
	 */
	clearElementError( blockId ) {
		const block = this.form.querySelector(
			`.srfm-block[data-block-id="${ blockId }"]`
		);

		if ( ! block ) {
			return;
		}

		const errorElements = block.querySelectorAll( '.srfm-stripe-error' );
		errorElements.forEach( ( element ) => {
			element.remove();
		} );
	}

	/**
	 * Validate payment amount before processing
	 * @param {number} amount - The amount to validate.
	 * @return {boolean} True if the amount is valid, false otherwise.
	 */
	static validatePaymentAmount( amount ) {
		// Stripe minimum is $0.50 for most currencies
		const minAmount = 0.5;
		const maxAmount = 999999.99; // Reasonable maximum

		if ( isNaN( amount ) || amount < minAmount ) {
			console.error(
				`SureForms: Payment amount must be at least $${ minAmount }`
			);
			return false;
		}
		if ( amount > maxAmount ) {
			console.error(
				`SureForms: Payment amount cannot exceed $${ maxAmount }`
			);
			return false;
		}
		return true;
	}
}

// Make StripePayment available globally for form submission
window.StripePayment = StripePayment;

/**
 * Initializes StripePayment for forms after SureForms initialization event.
 */
document.addEventListener( 'srfm_form_after_initialization', ( event ) => {
	const form = event?.detail?.form;
	if ( form ) {
		// Check if form has payment blocks before initializing
		const paymentBlocks = form.querySelectorAll(
			'.srfm-block.srfm-payment-block'
		);
		if ( paymentBlocks.length > 0 ) {
			new StripePayment( form );
		}
	}
} );
