/**
 * SureForms Stripe Payment Integration
 *
 * @since x.x.x
 */
/* global Stripe, srfm_ajax */
class StripePayment {
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

		// listen the form submit event
		// this.form.addEventListener( 'submit', ( event ) => {
		// 	event.preventDefault();
		// 	console.log("form submitted");
		// } );
	}

	// Store Stripe instances
	static stripeInstances = {};
	static paymentElements = {};
	static paymentIntents = {};

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

					console.log("class vars", {
						stripeInstances: StripePayment.stripeInstances,
						paymentElements: StripePayment.paymentElements,
						paymentIntents: StripePayment.paymentIntents,
					})
				} );

				paymentElement.on( 'change', ( event ) => {
					const statusElement = document.getElementById(
						`srfm-payment-status-${ blockId }`
					);
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
}

document.addEventListener( 'srfm_form_after_initialization', ( event ) => {

	const form = event?.detail?.form;
	if ( form ) {
		new StripePayment( form );
	}
} );
