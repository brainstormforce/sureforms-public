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

					const getTheAmount = getThePaymentItem.value || 0;
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
		// Only update display amount, don't create payment intent yet
		// Payment intent will be created during form submission
		console.log(`Amount updated for block ${blockId}: $${newAmount}`);
	}

	/**
	 * Create payment intent only during form submission
	 * This method should be called from form submission handler
	 */
	async createPaymentIntentOnSubmission( blockId, amount, paymentInput ) {
		if ( amount <= 0 ) {
			throw new Error(
				'createPaymentIntentOnSubmission: Amount must be greater than 0'
			);
		}

		this.set_block_loading( blockId, true );

		const currency = paymentInput.dataset.currency || 'usd';
		const description = paymentInput.dataset.description || 'SureForms Payment';

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

			if ( responseData.success ) {
				const clientSecret = responseData.data.client_secret;
				const paymentIntentId = responseData.data.payment_intent_id;

				// Store payment intent ID
				StripePayment.paymentIntents[ blockId ] = paymentIntentId;

				// Update the existing elements with the client secret
				const elementData = StripePayment.paymentElements[ blockId ];
				if ( elementData ) {
					elementData.clientSecret = clientSecret;
					
					// Update elements with the new client secret only
					// Amount is automatically derived from the PaymentIntent
					elementData.elements.update({
						clientSecret: clientSecret
					});
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
		const paymentType = field.getAttribute( 'data-payment-type' ) || 'one-time';
		
		// Initialize Stripe elements based on payment type
		if ( paymentType === 'subscription' ) {
			this.initializeSubscriptionElements( blockId, paymentInput );
		} else {
			this.initializeStripeElements( blockId, paymentInput );
		}
	}

	/**
	 * Initialize Stripe elements without creating payment intent
	 * Payment intent will be created only during form submission
	 */
	initializeStripeElements( blockId, paymentInput ) {
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

		// Initialize Elements without client secret (deferred payment intent creation)
		const elements = stripe.elements( {
			mode: 'payment',
			currency: paymentInput.dataset.currency || 'usd',
			amount: 12000, // Will be updated when payment intent is created
			appearance: {
				theme: 'stripe',
				variables: {
					colorPrimary: '#0073aa',
					colorBackground: '#ffffff',
					colorText: '#424242',
					colorDanger: '#df1b41',
					fontFamily: 'inherit',
					spacingUnit: '4px',
					borderRadius: '4px',
				},
			},
		} );

		// Create payment element
		const paymentElement = elements.create( 'payment' );
		paymentElement.mount( elementContainer );

		// Store references without payment intent
		StripePayment.paymentElements[ blockId ] = {
			stripe,
			elements,
			paymentElement,
			clientSecret: null, // Will be set when payment intent is created
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
			console.log( 'paymentElement on change event->', event );
		} );
	}

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
	 * Initialize Stripe elements for subscriptions
	 */
	initializeSubscriptionElements( blockId, paymentInput ) {
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

		// Initialize Elements for subscription mode
		const elements = stripe.elements( {
			mode: 'subscription',
			currency: paymentInput.dataset.currency || 'usd',
			amount: 12000, // Will be updated when subscription intent is created
			appearance: {
				theme: 'stripe',
				variables: {
					colorPrimary: '#0073aa',
					colorBackground: '#ffffff',
					colorText: '#424242',
					colorDanger: '#df1b41',
					fontFamily: 'inherit',
					spacingUnit: '4px',
					borderRadius: '4px',
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
			console.log( 'subscriptionElement on change event->', event );
		} );
	}

	/**
	 * Create subscription intent only during form submission
	 */
	async createSubscriptionIntentOnSubmission( blockId, amount, paymentInput ) {
		if ( amount <= 0 ) {
			throw new Error(
				'createSubscriptionIntentOnSubmission: Amount must be greater than 0'
			);
		}

		this.set_block_loading( blockId, true );

		const currency = paymentInput.dataset.currency || 'usd';
		const description = paymentInput.dataset.description || 'SureForms Subscription';
		
		// Extract customer data from form or use dummy data
		const customerData = this.extractCustomerData( paymentInput );

		const data = new FormData();
		data.append( 'action', 'srfm_create_subscription_intent' );
		data.append( 'nonce', srfm_ajax.nonce );
		data.append( 'amount', parseInt( amount * 100 ) );
		data.append( 'currency', currency );
		data.append( 'description', description );
		data.append( 'block_id', blockId );
		data.append( 'customer_name', customerData.name );
		data.append( 'customer_email', customerData.email );
		data.append( 'interval', customerData.interval );
		data.append( 'plan_name', customerData.planName );

		try {
			const response = await fetch( srfm_ajax.ajax_url, {
				method: 'POST',
				body: data,
			} );

			const responseData = await response.json();

			console.log("responseData ->", responseData);

			this.set_block_loading( blockId, false );

			if ( responseData.success ) {
				const clientSecret = responseData.data.client_secret;
				const subscriptionId = responseData.data.subscription_id;
				const customerId = responseData.data.customer_id;

				// Store subscription intent ID
				StripePayment.subscriptionIntents[ blockId ] = {
					subscriptionId,
					customerId,
				};

				// Update the existing elements with the client secret
				const elementData = StripePayment.paymentElements[ blockId ];
				if ( elementData ) {
					elementData.clientSecret = clientSecret;
					
					// Update elements with the new client secret
					elementData.elements.update({
						clientSecret: clientSecret
					});
				}

				return { clientSecret, subscriptionId, customerId };
			}
			throw new Error(
				responseData.data || 'Failed to create subscription intent'
			);
		} catch ( error ) {
			this.set_block_loading( blockId, false );
			console.error( 'Error creating subscription intent:', error );
			throw error;
		}
	}

	/**
	 * Extract customer data from form fields or use dummy data
	 */
	extractCustomerData( paymentInput ) {
		const form = paymentInput.closest( 'form' );
		const block = paymentInput.closest( '.srfm-block' );
		
		// Get subscription plan data from block attributes
		const planName = block.dataset.subscriptionPlanName || 'Subscription Plan';
		const interval = block.dataset.subscriptionInterval || 'month';
		const customerNameField = block.dataset.customerNameField;
		const customerEmailField = block.dataset.customerEmailField;
		
		// Try to extract customer data from mapped form fields
		let customerName = 'SureForms Customer';
		let customerEmail = 'customer@example.com';
		
		if ( customerNameField && form ) {
			const nameInput = form.querySelector( `[name*="${customerNameField}"]` );
			if ( nameInput && nameInput.value.trim() ) {
				customerName = nameInput.value.trim();
			}
		}
		
		if ( customerEmailField && form ) {
			const emailInput = form.querySelector( `[name*="${customerEmailField}"]` );
			if ( emailInput && emailInput.value.trim() ) {
				customerEmail = emailInput.value.trim();
			}
		}
		
		return {
			name: customerName,
			email: customerEmail,
			interval: interval,
			planName: planName,
		};
	}

	/**
	 * Static method to create payment intents for all payment blocks in a form during submission
	 * This should be called from the form submission handler
	 */
	static async createPaymentIntentsForForm( form ) {
		const paymentBlocks = form.querySelectorAll('.srfm-block.srfm-payment-block');
		const results = [];

		for ( const block of paymentBlocks ) {
			const blockId = block.getAttribute('data-block-id');
			const paymentInput = block.querySelector('input.srfm-payment-input');
			const paymentType = block.getAttribute('data-payment-type') || 'one-time';
			
			if ( !paymentInput ) {
				continue;
			}

			// Calculate current amount from form
			const paymentValueElement = block.querySelector('.srfm-payment-value');
			const amountText = paymentValueElement?.textContent || '$0.00';
			const amount = parseFloat(amountText.replace(/[^0-9.]/g, '')) || 0;

			if ( amount <= 0 ) {
				console.warn(`Skipping payment block ${blockId} - amount is ${amount}`);
				continue;
			}

			try {
				// Create a temporary instance to call the method
				const tempInstance = new StripePayment(form);
				
				if ( paymentType === 'subscription' ) {
					const result = await tempInstance.createSubscriptionIntentOnSubmission(blockId, amount, paymentInput);
					results.push({ blockId, paymentType: 'subscription', ...result });
				} else {
					const result = await tempInstance.createPaymentIntentOnSubmission(blockId, amount, paymentInput);
					results.push({ blockId, paymentType: 'one-time', ...result });
				}
			} catch ( error ) {
				console.error(`Failed to create ${paymentType} intent for block ${blockId}:`, error);
				throw error;
			}
		}

		console.log("results ->", results);

		return results;
	}
}

// Make StripePayment available globally for form submission
window.StripePayment = StripePayment;

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
