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
	static slugForPayment = [];
	static debounceTimers = {};

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
		console.log(
			'listen_the_form_input_changes->',
			StripePayment.slugForPayment
		);

		// if the slug is in the slugForPayment array, then listen the input change
		if ( StripePayment.slugForPayment.length > 0 ) {
			StripePayment.slugForPayment.forEach( ( slug ) => {
				this.form
					.querySelectorAll(
						`.srfm-slug-${ slug } .srfm-input-number`
					)
					.forEach( ( input ) => {
						input.addEventListener( 'input', ( event ) => {
							console.log(
								'form input changed',
								event.target.value
							);

							// // Update payment intent when value changes
							this.updatePaymentIntent( {
								input: event.target,
								slug,
							} );
						} );
					} );
			} );
		}
	}

	updatePaymentIntent( { input, slug } ) {
		console.log( 'updatePaymentIntent->', { input, slug } );

		const getPaymentWrapper = this.form.querySelectorAll(
			'.srfm-payment-input[data-payment-items]'
		);

		for ( const paymentWrapper of getPaymentWrapper ) {
			const getTheSlug = this.getSlugForPayment( paymentWrapper );

			// If in the slug array, then update the payment intent
			if ( getTheSlug.includes( slug ) ) {
				// Block wrapper
				const blockWrapper = paymentWrapper.closest( '.srfm-block' );
				// get Wrapper block id
				const blockId = blockWrapper.getAttribute( 'data-block-id' );

				// new amount
				const newAmount = input.value;

				// Set amount in the ".srfm-payment-value"
				const paymentValue = blockWrapper.querySelector(
					'.srfm-payment-value'
				);
				paymentValue.textContent = `$${ newAmount }`;

				this.updatePaymentIntentAmount( blockId, newAmount );
			}
		}
	}

	debounce( func, delay, key ) {
		return function ( ...args ) {
			clearTimeout( StripePayment.debounceTimers[ key ] );
			StripePayment.debounceTimers[ key ] = setTimeout(
				() => func.apply( this, args ),
				delay
			);
		};
	}

	updatePaymentIntentAmount( blockId, newAmount ) {
		console.log( 'updatePaymentIntentAmount->', { blockId, newAmount } );

		// Create a unique key for this block
		const debounceKey = `payment_update_${ blockId }`;

		// Create debounced function if it doesn't exist
		if ( ! this[ `debouncedUpdate_${ blockId }` ] ) {
			this[ `debouncedUpdate_${ blockId }` ] = this.debounce(
				this.performUpdatePaymentIntent.bind( this ),
				500,
				debounceKey
			);
		}

		// Call the debounced function
		this[ `debouncedUpdate_${ blockId }` ]( blockId, newAmount );
	}

	async performUpdatePaymentIntent( blockId, newAmount ) {
		console.log( 'performUpdatePaymentIntent->', { blockId, newAmount } );

		// Get the payment intent ID for this block
		const paymentIntentId = StripePayment.paymentIntents[ blockId ];

		if ( ! paymentIntentId ) {
			console.error( `No payment intent found for block ${ blockId }` );
			return;
		}

		// Prepare data for API call
		const data = new FormData();
		data.append( 'action', 'srfm_update_payment_intent_amount' );
		data.append( 'nonce', srfm_ajax.nonce );
		data.append( 'payment_intent_id', paymentIntentId );
		data.append( 'new_amount', parseInt( newAmount * 100 ) ); // Convert to cents
		data.append( 'block_id', blockId );

		try {
			const response = await fetch( srfm_ajax.ajax_url, {
				method: 'POST',
				body: data,
			} );

			const responseData = await response.json();

			if ( responseData.success ) {
				console.log(
					`Payment intent updated successfully for block ${ blockId }`
				);
			} else {
				console.error(
					`Failed to update payment intent for block ${ blockId }:`,
					responseData.data
				);
			}
		} catch ( error ) {
			console.error(
				`Error updating payment intent for block ${ blockId }:`,
				error
			);
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

		console.log( 'processPayment ddd->', { paymentInput, blockId } );

		if ( ! paymentInput ) {
			console.error(
				'SureForms: Payment input not found for block',
				blockId
			);
			return;
		}

		const stripeKey = paymentInput.dataset.stripeKey;
		const elementContainer = field.querySelector(
			'.srfm-stripe-payment-element'
		);
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
			...StripePayment.slugForPayment,
			...slugForPayment,
		];

		if ( ! stripeKey || ! elementContainer ) {
			console.error(
				'SureForms: Missing Stripe key or element container for block',
				blockId
			);
			return;
		}

		// Initialize Stripe
		if ( ! StripePayment.stripeInstances[ blockId ] ) {
			StripePayment.stripeInstances[ blockId ] = Stripe( stripeKey );
		}

		const stripe = StripePayment.stripeInstances[ blockId ];

		// Create payment intent
		this.createPaymentIntent( blockId, paymentInput )
			.then( ( clientSecret ) => {
				console.log( 'clientSecret cl->', clientSecret );

				if ( ! clientSecret ) {
					throw new Error( 'Failed to create payment intent' );
				}

				// Initialize Elements
				const elements = stripe.elements( {
					clientSecret,
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

				// Store references
				StripePayment.paymentElements[ blockId ] = {
					stripe,
					elements,
					paymentElement,
					clientSecret,
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
					const statusElement = document.getElementById(
						`srfm-payment-status-${ blockId }`
					);

					console.log( 'paymentElement on change event->', event );

					// if ( event.error ) {
					// 	StripePayment.showPaymentError( blockId, event.error.message );
					// } else if ( statusElement ) {
					// 	statusElement.style.display = 'none';
					// }
				} );
			} )
			.catch( ( error ) => {
				console.error(
					'SureForms: Error initializing payment for block',
					blockId,
					error
				);
				showPaymentError(
					blockId,
					'Failed to initialize payment. Please try again.'
				);
			} );
	}

	async createPaymentIntent( blockId, paymentInput ) {
		const amount = parseInt( paymentInput.dataset.paymentAmount );
		const currency = paymentInput.dataset.currency;
		const description = paymentInput.dataset.description;

		const data = new FormData();
		data.append( 'action', 'srfm_create_payment_intent' );
		data.append( 'nonce', srfm_ajax.nonce );
		data.append( 'amount', amount );
		data.append( 'currency', currency );
		data.append( 'description', description );
		data.append( 'block_id', blockId );

		try {
			const response = await fetch( srfm_ajax.ajax_url, {
				method: 'POST',
				body: data,
			} );

			const responseData = await response.json();

			if ( responseData.success ) {
				StripePayment.paymentIntents[ blockId ] =
					responseData.data.payment_intent_id;
				return responseData.data.client_secret;
			}
			throw new Error(
				responseData.data || 'Failed to create payment intent'
			);
		} catch ( error ) {
			console.error( 'Error creating payment intent:', error );
			throw error;
		}
	}
}

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
