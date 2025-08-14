/**
 * SureForms Stripe Payment Integration
 *
 * @since x.x.x
 */
/* global Stripe, srfm_ajax */

( function () {
	'use strict';

	console.log( 'SureForms Stripe Payment script loaded' );
	console.log( 'srfm_ajax object:', typeof srfm_ajax !== 'undefined' ? srfm_ajax : 'undefined' );

	// Store Stripe instances
	const stripeInstances = {};
	const paymentElements = {};
	const paymentIntents = {};

	/**
	 * Initialize Stripe payment for a specific block
	 *
	 * @param {string} blockId Block ID
	 */
	window.srfmInitializeStripePayment = function ( blockId ) {
		const paymentInput = document.querySelector(
			`[data-block-id="${ blockId }"]`
		);
		if ( ! paymentInput ) {
			console.error(
				'SureForms: Payment input not found for block',
				blockId
			);
			return;
		}

		const stripeKey = paymentInput.dataset.stripeKey;
		const elementContainer = document.getElementById(
			`srfm-payment-element-${ blockId }`
		);

		if ( ! stripeKey || ! elementContainer ) {
			console.error(
				'SureForms: Missing Stripe key or element container for block',
				blockId
			);
			return;
		}

		// Initialize Stripe
		if ( ! stripeInstances[ blockId ] ) {
			stripeInstances[ blockId ] = Stripe( stripeKey );
		}

		const stripe = stripeInstances[ blockId ];

		// Create payment intent
		createPaymentIntent( blockId, paymentInput )
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
				paymentElements[ blockId ] = {
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
					if ( event.error ) {
						showPaymentError( blockId, event.error.message );
					} else if ( statusElement ) {
						statusElement.style.display = 'none';
					}
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
	};

	/**
	 * Create payment intent via AJAX
	 *
	 * @param {string}      blockId      Block ID
	 * @param {HTMLElement} paymentInput Payment input element
	 * @return {Promise<string>} Client secret
	 */
	function createPaymentIntent( blockId, paymentInput ) {
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

		return fetch( srfm_ajax.ajax_url, {
			method: 'POST',
			body: data,
		} )
			.then( ( response ) => response.json() )
			.then( ( responseData ) => {
				if ( responseData.success ) {
					paymentIntents[ blockId ] = responseData.data.payment_intent_id;
					return responseData.data.client_secret;
				}
				throw new Error(
					responseData.data || 'Failed to create payment intent'
				);
			} );
	}

	/**
	 * Process payment for a specific block
	 *
	 * @param {string} blockId Block ID
	 * @return {Promise<boolean>} Payment success status
	 */
	window.srfmProcessPayment = function ( blockId ) {
		return new Promise( ( resolve, reject ) => {
			const paymentData = paymentElements[ blockId ];
			if ( ! paymentData ) {
				reject( new Error( 'Payment not initialized' ) );
				return;
			}

			const { stripe, elements } = paymentData;

			showPaymentProcessing( blockId );

			stripe
				.confirmPayment( {
					elements,
					confirmParams: {
						return_url: window.location.href,
					},
					redirect: 'if_required',
				} )
				.then( ( result ) => {
					hidePaymentProcessing( blockId );

					if ( result.error ) {
						showPaymentError( blockId, result.error.message );
						reject( result.error );
					} else if ( result.paymentIntent && result.paymentIntent.status === 'succeeded' ) {
						console.log(
							'SureForms: Payment successful for block',
							blockId,
							'Payment Intent:',
							result.paymentIntent.id
						);

						// Mark payment as completed
						const paymentInput = document.querySelector(
							`[data-block-id="${ blockId }"]`
						);
						if ( paymentInput ) {
							paymentInput.value = result.paymentIntent.id;
							paymentInput.dataset.paymentStatus = 'succeeded';
							console.log( 'SureForms: Payment input updated with intent ID:', result.paymentIntent.id );
						}

						resolve( true );
					} else {
						console.error( 'SureForms: Unexpected payment result', result );
						showPaymentError( blockId, 'Payment processing incomplete. Please try again.' );
						reject( new Error( 'Payment incomplete' ) );
					}
				} )
				.catch( ( error ) => {
					hidePaymentProcessing( blockId );
					console.error(
						'SureForms: Payment error for block',
						blockId,
						error
					);
					showPaymentError(
						blockId,
						'Payment failed. Please try again.'
					);
					reject( error );
				} );
		} );
	};

	/**
	 * Show payment processing status
	 *
	 * @param {string} blockId Block ID
	 */
	function showPaymentProcessing( blockId ) {
		const statusElement = document.getElementById(
			`srfm-payment-status-${ blockId }`
		);
		if ( statusElement ) {
			statusElement.style.display = 'block';
		}
	}

	/**
	 * Hide payment processing status
	 *
	 * @param {string} blockId Block ID
	 */
	function hidePaymentProcessing( blockId ) {
		const statusElement = document.getElementById(
			`srfm-payment-status-${ blockId }`
		);
		if ( statusElement ) {
			statusElement.style.display = 'none';
		}
	}

	/**
	 * Show payment error
	 *
	 * @param {string} blockId Block ID
	 * @param {string} message Error message
	 */
	function showPaymentError( blockId, message ) {
		const errorElement = document.querySelector(
			`.srf-payment-${ blockId }-block .srfm-error-wrap`
		);
		if ( errorElement ) {
			errorElement.innerHTML = `<div class="srfm-error-message">${ message }</div>`;
			errorElement.style.display = 'block';
		}
	}

	/**
	 * Validate payment fields before form submission
	 *
	 * @param {HTMLFormElement} form Form element
	 * @return {boolean} Validation status
	 */
	window.srfmValidatePayments = function ( form ) {
		const paymentInputs = form.querySelectorAll(
			'.srfm-payment-input[data-required="true"]'
		);

		for ( const input of paymentInputs ) {
			const blockId = input.dataset.blockId;
			const paymentStatus = input.dataset.paymentStatus;

			if ( paymentStatus !== 'succeeded' ) {
				showPaymentError(
					blockId,
					'Please complete the payment before submitting the form.'
				);
				input.scrollIntoView( { behavior: 'smooth', block: 'center' } );
				return false;
			}
		}

		return true;
	};

	/**
	 * Process all payments in form before submission
	 *
	 * @param {HTMLFormElement} form Form element
	 * @return {Promise<boolean>} Payment processing status
	 */
	window.srfmProcessAllPayments = function ( form ) {
		const paymentInputs = form.querySelectorAll( '.srfm-payment-input' );
		const paymentPromises = [];

		for ( const input of paymentInputs ) {
			const blockId = input.dataset.blockId;
			const paymentStatus = input.dataset.paymentStatus;

			// Only process payments that haven't been completed yet
			if ( paymentStatus !== 'succeeded' && paymentElements[ blockId ] ) {
				paymentPromises.push( window.srfmProcessPayment( blockId ) );
			}
		}

		if ( paymentPromises.length === 0 ) {
			return Promise.resolve( true );
		}

		return Promise.all( paymentPromises )
			.then( () => true )
			.catch( ( error ) => {
				console.error( 'SureForms: Payment processing failed', error );
				return false;
			} );
	};

	// Hook into form submission to process payments
	document.addEventListener( 'submit', function ( event ) {
		const form = event.target;
		
		// Only handle SureForms forms
		if ( ! form.classList.contains( 'srfm-form' ) ) {
			return;
		}

		const paymentInputs = form.querySelectorAll( '.srfm-payment-input' );
		
		// Skip if no payment fields or payment already processed
		if ( paymentInputs.length === 0 || form.dataset.paymentProcessing === 'true' ) {
			return;
		}

		// Check if any payment needs processing
		let needsPaymentProcessing = false;
		for ( const input of paymentInputs ) {
			if ( input.dataset.paymentStatus !== 'succeeded' ) {
				needsPaymentProcessing = true;
				break;
			}
		}

		if ( ! needsPaymentProcessing ) {
			console.log( 'SureForms: All payments already processed' );
			return;
		}

		// Prevent default submission to process payment first
		event.preventDefault();
		event.stopPropagation();

		console.log( 'SureForms: Processing payments before form submission' );

		// Mark form as processing payment
		form.dataset.paymentProcessing = 'true';

		// Find and activate loader
		const loader = form.querySelector( '.srfm-loader' );
		if ( loader ) {
			loader.classList.add( 'srfm-active' );
		}

		// Process payments
		window.srfmProcessAllPayments( form )
			.then( function ( success ) {
				if ( success ) {
					console.log( 'SureForms: Payment successful, submitting form' );
					// Remove the processing flag
					delete form.dataset.paymentProcessing;
					// Submit the form programmatically
					// Use HTMLFormElement.submit() to bypass event listeners
					form.submit();
				} else {
					console.error( 'SureForms: Payment processing failed' );
					delete form.dataset.paymentProcessing;
					if ( loader ) {
						loader.classList.remove( 'srfm-active' );
					}
				}
			} )
			.catch( function ( error ) {
				console.error( 'SureForms: Payment error:', error );
				delete form.dataset.paymentProcessing;
				if ( loader ) {
					loader.classList.remove( 'srfm-active' );
				}
			} );
	}, true ); // Use capture phase to run before other handlers

	// Initialize payments when DOM is ready
	document.addEventListener( 'DOMContentLoaded', function () {
		// Find all payment blocks and initialize them
		const paymentInputs = document.querySelectorAll(
			'.srfm-payment-input'
		);
		paymentInputs.forEach( ( input ) => {
			const blockId = input.dataset.blockId;
			if (
				blockId &&
				typeof window.srfmInitializeStripePayment === 'function'
			) {
				window.srfmInitializeStripePayment( blockId );
			}
		} );
	} );

	// Initialize payments for dynamically loaded forms
	document.addEventListener( 'srfm_form_initialize', function () {
		// Find all payment blocks and initialize them
		const paymentInputs = document.querySelectorAll(
			'.srfm-payment-input'
		);
		paymentInputs.forEach( ( input ) => {
			const blockId = input.dataset.blockId;
			if (
				blockId &&
				! paymentElements[ blockId ] &&
				typeof window.srfmInitializeStripePayment === 'function'
			) {
				window.srfmInitializeStripePayment( blockId );
			}
		} );
	} );
}() );
