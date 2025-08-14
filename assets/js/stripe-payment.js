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

	/**
	 * Constructor for the Calculations class.
	 * @param {HTMLElement} form - The form element containing calculation fields.
	 */
	constructor( form ) {
		this.form = form;

		// Find all payment blocks within the form.
		const getPaymentFields = this.form.querySelectorAll( '.srfm-block.srfm-payment-block' );

		// Initialize Stripe payment for each payment field.
		getPaymentFields.forEach( ( field ) => {
			this.processPayment( field );
		} );

		// Listen the form input changes.
		this.listen_the_form_input_changes();

		// listen the form submit event
		this.listen_the_form_submit_event();
	}

	listen_the_form_input_changes() {
		console.log("listen_the_form_input_changes->", StripePayment.slugForPayment);

		// if the slug is in the slugForPayment array, then listen the input change
		if ( StripePayment.slugForPayment.length > 0 ) {
			StripePayment.slugForPayment.forEach( ( slug ) => {
				this.form.querySelectorAll( `.srfm-slug-${slug} .srfm-input-number` ).forEach( ( input ) => {
					input.addEventListener( 'input', ( event ) => {
						console.log("form input changed", event.target.value);
						
						// // Update payment intent when value changes
						this.updatePaymentIntent( {input: event.target, slug: slug} );
					} );
				} );
			} );
		}
	}

	updatePaymentIntent( {input, slug} ) {
		console.log("updatePaymentIntent->", {input, slug});

		const getPaymentWrapper = this.form.querySelectorAll( '.srfm-payment-input[data-payment-items]' );

		for ( const paymentWrapper of getPaymentWrapper ) {
			const getTheSlug = this.getSlugForPayment( paymentWrapper );
			console.log("getTheSlug->", {getTheSlug, slug});
			// If in the slug array, then update the payment intent
			if ( getTheSlug.includes( slug ) ) {
				// get Wrapper block id
				const blockId = paymentWrapper.closest('.srfm-block').getAttribute('data-block-id');
					console.log("blockId->", blockId);

				// new amount
				const newAmount = input.value;
				console.log("newAmount->", newAmount);

				this.updatePaymentIntentAmount( blockId, newAmount );
			}
		}
	}

	updatePaymentIntentAmount( blockId, newAmount ) {
		console.log("updatePaymentIntentAmount->", {blockId, newAmount});
	}

	listen_the_form_submit_event() {
		document.addEventListener( 'srfm_on_trigger_form_submission', async ( event ) => {
			event.preventDefault();
			console.log("form submitted - processing payment first");

			const { form } = event.detail;
			
			// Check if form has payment blocks
			const paymentBlocks = form.querySelectorAll( '.srfm-block.srfm-payment-block' );
			
			if ( paymentBlocks.length === 0 ) {
				return;
			}

			// Process payment for all payment blocks
			try {
				await this.processAllPayments( paymentBlocks );
				// Payment successful, continue with form submission
			} catch ( error ) {
				console.error( 'Payment failed 1122:', error );
				// Show error and don't submit form
				const { loader } = event.detail;
				if ( loader ) {
					loader.classList.remove( 'srfm-active' );
				}
			}
		} );
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
		const paymentInput = field.querySelector('input.srfm-payment-input');
		const blockId = field.getAttribute('data-block-id');

		if ( ! paymentInput ) {
			console.error(
				'SureForms: Payment input not found for block',
				blockId
			);
			return;
		}

		const stripeKey = paymentInput.dataset.stripeKey;
		const elementContainer = field.querySelector('.srfm-stripe-payment-element');
		const slugForPayment = this.getSlugForPayment( paymentInput );

		if ( ! slugForPayment ) {
			console.error(
				'SureForms: Payment items not found for block' + blockId,
				blockId
			);
			return;
		}

		// push the slugs in the variables
		StripePayment.slugForPayment = [...StripePayment.slugForPayment, ...slugForPayment];

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

				console.log("clientSecret cl->", clientSecret);

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

					console.log("paymentElement on change event->", event);

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
		const applicationFee = parseFloat(
			paymentInput.dataset.applicationFee
		);

		const data = new FormData();
		data.append( 'action', 'srfm_create_payment_intent' );
		data.append( 'nonce', srfm_ajax.nonce );
		data.append( 'amount', amount );
		data.append( 'currency', currency );
		data.append( 'description', description );
		data.append( 'application_fee', applicationFee );
		data.append( 'block_id', blockId );

		try {
			const response = await fetch( srfm_ajax.ajax_url, {
				method: 'POST',
				body: data,
			} );

			const responseData = await response.json();
			
			if ( responseData.success ) {
				StripePayment.paymentIntents[ blockId ] = responseData.data.payment_intent_id;
				return responseData.data.client_secret;
			} else {
				throw new Error(
					responseData.data || 'Failed to create payment intent'
				);
			}
		} catch ( error ) {
			console.error( 'Error creating payment intent:', error );
			throw error;
		}
	}

	async processAllPayments( paymentBlocks ) {
		const paymentPromises = [];
		
		paymentBlocks.forEach( ( block ) => {
			const blockId = block.getAttribute( 'data-block-id' );

			const paymentData = StripePayment.paymentElements[ blockId ];

			console.log("paymentData->", {paymentBlocks, block,paymentData, blockId});
			
			if ( paymentData ) {
				paymentPromises.push( this.processForConfirmPayment( blockId, paymentData ) );
			}
		} );

		console.log("paymentPromises->", paymentPromises);
		
		return Promise.all( paymentPromises );
	}

	async processForConfirmPayment( blockId, paymentData ) {
		const { stripe, elements, clientSecret } = paymentData;

		console.log("confirmPayment->", {stripe, elements, clientSecret});
		
		// First submit the elements
		const { error: submitError } = await elements.submit();
		
		if ( submitError ) {
			console.error( `Elements submit failed for block ${blockId}:`, submitError );
			throw new Error( submitError.message );
		}
		
		// Then confirm the payment
		const confirmationObject = await stripe.confirmPayment( {
			elements,
			clientSecret,
			confirmParams: {
				return_url: window.location.href,
			},
			redirect: 'if_required'
		} );
		const { error, paymentIntent } = confirmationObject;

		console.log("paymentIntent->", {error, paymentIntent, confirmationObject});
		
		if ( error ) {
			console.error( `Payment failed for block ${blockId}:`, error );
			throw new Error( error.message );
		}
		
		if ( paymentIntent.status === 'succeeded' || paymentIntent.status === 'requires_capture' ) {
			console.log( `Payment succeeded for block ${blockId}` );
			return paymentIntent;
		} else {
			throw new Error( `Payment not completed for block ${blockId}` );
		}
	}
}

document.addEventListener( 'srfm_form_after_initialization', ( event ) => {

	const form = event?.detail?.form;
	if ( form ) {
		// Check if form has payment blocks before initializing
		const paymentBlocks = form.querySelectorAll( '.srfm-block.srfm-payment-block' );
		if ( paymentBlocks.length > 0 ) {
			new StripePayment( form );
		}
	}
} );
