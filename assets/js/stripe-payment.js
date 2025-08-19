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

				const getPaymentItemWrapper =
					getPaymentItemBlockWrapper.querySelector(
						'.srfm-payment-items-wrapper'
					);
				const getPaymentItemWrapperHTML =
					getPaymentItemBlockWrapper.querySelector(
						'.srfm-payment-value'
					);

				// Generate HTML for payment items and calculate total in single loop
				let paymentItemsHTML = '';
				let totalAmount = 0;

				if ( paymentDetails.length > 0 ) {
					paymentItemsHTML = `
						<table class="srfm-payment-items-table" style="width: 100%; border-collapse: collapse; margin: 10px 0;">
							<thead>
								<tr>
									<th style="text-align: left; padding: 8px; border-bottom: 1px solid #ddd; font-weight: 600;">Payment Item</th>
									<th style="text-align: right; padding: 8px; border-bottom: 1px solid #ddd; font-weight: 600;">Amount</th>
								</tr>
							</thead>
							<tbody>
					`;

					paymentDetails.forEach( ( item ) => {
						const itemAmount = parseFloat( item.amount || 0 );
						totalAmount += itemAmount;

						paymentItemsHTML += `
							<tr>
								<td style="padding: 8px; border-bottom: 1px solid #eee;">${ item.title }</td>
								<td style="text-align: right; padding: 8px; border-bottom: 1px solid #eee;">$${ itemAmount.toFixed(
		2
	) }</td>
							</tr>
						`;
					} );

					paymentItemsHTML += `
							</tbody>
							<tfoot>
								<tr>
									<td style="padding: 8px; border-top: 2px solid #ddd; font-weight: 600;">Total:</td>
									<td style="text-align: right; padding: 8px; border-top: 2px solid #ddd; font-weight: 600;">$${ totalAmount.toFixed(
		2
	) }</td>
								</tr>
							</tfoot>
						</table>
					`;
				}

				// Update the payment items wrapper with the generated HTML
				if ( getPaymentItemWrapper ) {
					getPaymentItemWrapper.innerHTML = paymentItemsHTML;
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
		// Debounce need to add here.
		this.createOrUpdatePaymentIntent(
			blockId,
			newAmount,
			paymentHiddenInput
		);
	}

	async createOrUpdatePaymentIntent(
		blockId,
		newAmount,
		paymentHiddenInput
	) {
		// Get the payment intent ID for this block
		const paymentIntentId = StripePayment.paymentIntents[ blockId ];

		if ( ! paymentIntentId ) {
			// create the payment intent
			this.createStripeInstance( blockId, paymentHiddenInput, newAmount );
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

			console.log( 'update intent detail responseData->', responseData );

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
	}

	createStripeInstance( blockId, paymentInput, amount = 0 ) {
		const stripeKey = paymentInput.dataset.stripeKey;

		if ( ! stripeKey ) {
			throw new Error(
				'createStripeInstance: Stripe key is required in payment intent creation.'
			);
		}

		if ( amount <= 0 ) {
			throw new Error(
				'createStripeInstance: Amount is required in payment intent creation. Currently the amount is ' +
					amount
			);
		}

		const elementContainer = paymentInput
			.closest( '.srfm-block' )
			.querySelector( '.srfm-stripe-payment-element' );

		// Initialize Stripe
		if ( ! StripePayment.stripeInstances[ blockId ] ) {
			StripePayment.stripeInstances[ blockId ] = Stripe( stripeKey );
		}

		const stripe = StripePayment.stripeInstances[ blockId ];

		// Create payment intent
		this.createPaymentIntent( blockId, paymentInput, amount )
			.then( ( clientSecret ) => {
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
					console.log( 'paymentElement on change event->', event );
				} );
			} )
			.catch( ( error ) => {
				console.error(
					'SureForms: Error initializing payment for block',
					blockId,
					error
				);
			} );
	}

	async createPaymentIntent( blockId, paymentInput, amount = 0 ) {
		if ( amount <= 0 ) {
			throw new Error(
				'createPaymentIntent: Amount is required in payment intent creation.'
			);
		}

		const currency = paymentInput.dataset.currency;
		const description = paymentInput.dataset.description;

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
