/**
 * Payment Manager - Minimal payment method switching
 *
 * Handles:
 * - Detecting selected payment method
 * - Toggling srfm-payment-active class
 * - Providing unified interface for payment processing
 *
 * @package
 * @since 2.4.0
 */

class PaymentManager {
	constructor( paymentBlock, form ) {
		this.paymentBlock = paymentBlock;
		this.blockId = paymentBlock.getAttribute( 'data-block-id' );
		this.paymentInput = paymentBlock.querySelector( '.srfm-payment-input' );
		this.form = form;
		this.init();
	}

	init() {
		// Set initial active method
		this.updateActiveMethod();

		// Dispatch initial payment method event
		this.dispatchPaymentMethodEvent( this.getSelectedMethod(), this.form );

		// Listen for payment method radio changes
		const radioButtons = this.paymentBlock.querySelectorAll(
			'.srfm-payment-method-radio'
		);

		if ( radioButtons.length === 0 ) {
			console.warn(
				'PaymentManager: No payment method radio buttons found in block',
				this.blockId
			);
			return;
		}

		radioButtons.forEach( ( radio ) => {
			radio.addEventListener( 'change', ( event ) => {
				this.updateActiveMethod();
				// Get the parent form from the radio button's position in the DOM
				const form = event.target.closest( 'form' );
				// Dispatch event when payment method changes, override this.form with the detected form
				this.dispatchPaymentMethodEvent(
					this.getSelectedMethod(),
					form
				);
			} );
		} );

		// Listen for accordion header clicks
		const accordionHeaders = this.paymentBlock.querySelectorAll(
			'.srfm-accordion-header'
		);

		accordionHeaders.forEach( ( header ) => {
			header.addEventListener( 'click', () => {
				this.handleAccordionHeaderClick( header );
			} );
		} );
	}

	/**
	 * Handle accordion header click
	 * Activates the accordion item and selects corresponding radio button
	 * @param {HTMLElement} header - The clicked header element
	 */
	handleAccordionHeaderClick( header ) {
		// Find the parent accordion item
		const accordionItem = header.closest( '.srfm-accordion-item' );
		if ( ! accordionItem ) {
			return;
		}

		// Get the payment method for this accordion item
		const method = accordionItem.getAttribute( 'data-method' );
		if ( ! method ) {
			return;
		}

		// Find and check the corresponding radio button
		const radio = this.paymentBlock.querySelector(
			`.srfm-payment-method-radio[data-method="${ method }"]`
		);

		if ( radio && ! radio.checked ) {
			radio.checked = true;
			// Trigger change event to update active state
			radio.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
	}

	/**
	 * Get the currently selected payment method
	 * @return {string} Method ID ('stripe', 'paypal', etc.)
	 */
	getSelectedMethod() {
		// Check radio buttons first (user selection)
		const checkedRadio = this.paymentBlock.querySelector(
			'.srfm-payment-method-radio:checked'
		);

		if ( checkedRadio ) {
			return checkedRadio.getAttribute( 'data-method' );
		}

		// Fallback to data-selected-method attribute (default)
		return this.paymentInput.getAttribute( 'data-selected-method' );
	}

	/**
	 * Update active payment method and toggle classes
	 */
	updateActiveMethod() {
		const selectedMethod = this.getSelectedMethod();

		// Update data-selected-method attribute
		this.paymentInput.setAttribute(
			'data-selected-method',
			selectedMethod
		);

		// Toggle srfm-payment-active class on accordion items
		const allAccordionItems = this.paymentBlock.querySelectorAll(
			'.srfm-accordion-item'
		);

		allAccordionItems.forEach( ( item ) => {
			const itemMethod = item.getAttribute( 'data-method' );

			if ( itemMethod === selectedMethod ) {
				item.classList.add( 'srfm-payment-active' );
				// Update aria-expanded attribute
				const header = item.querySelector( '.srfm-accordion-header' );
				if ( header ) {
					header.setAttribute( 'aria-expanded', 'true' );
				}
			} else {
				item.classList.remove( 'srfm-payment-active' );
				// Update aria-expanded attribute
				const header = item.querySelector( '.srfm-accordion-header' );
				if ( header ) {
					header.setAttribute( 'aria-expanded', 'false' );
				}
			}
		} );
	}

	/**
	 * Dispatch payment method change event
	 * Allows other components to react to payment method changes
	 * @param {string}      paymentMethod - The selected payment method (stripe, paypal, etc.)
	 * @param {HTMLElement} form          - The form element
	 */
	dispatchPaymentMethodEvent( paymentMethod, form ) {
		const event = new CustomEvent( 'srfm_payment_method_changed', {
			detail: {
				blockId: this.blockId,
				paymentMethod,
				form,
			},
			bubbles: true,
		} );

		document.dispatchEvent( event );
	}

	/**
	 * Process payment for the selected method
	 * Routes to appropriate payment gateway
	 * @param {HTMLElement} form - The form element
	 * @return {Promise<Object>} Payment result
	 */
	async processPayment( form ) {
		const selectedMethod = this.getSelectedMethod();

		switch ( selectedMethod ) {
			case 'stripe':
				return await this.processStripePayment( form );

			case 'paypal':
				return await this.processPayPalPayment( form );

			default:
				return {
					valid: false,
					message: `Payment method "${ selectedMethod }" is not supported.`,
				};
		}
	}

	/**
	 * Process Stripe payment
	 * @param {HTMLElement} form - The form element
	 */
	async processStripePayment( form ) {
		const paymentResultOnCreateIntent =
			await window.StripePayment.createPaymentIntentsForForm(
				form,
				this.paymentBlock
			);

		if ( ! paymentResultOnCreateIntent?.valid ) {
			return {
				valid: false,
				message: paymentResultOnCreateIntent.message,
			};
		}

		const paymentData = window.srfmPaymentElements?.[ this.blockId ];

		if ( paymentData && paymentData.clientSecret ) {
			const paymentResult = await window.StripePayment.srfmConfirmPayment(
				this.blockId,
				paymentData,
				form
			).catch( () => {
				return null;
			} );

			if ( ! paymentResult?.valid ) {
				return {
					valid: false,
					message: paymentResult.message,
					paymentResult: null,
				};
			}

			return {
				valid: true,
				message: window.srfmPaymentUtility?.getStripeStrings(
					'payment_successful',
					'Payment successful'
				),
				paymentResult,
			};
		}

		return {
			valid: false,
			message: 'Payment data not found',
		};
	}

	/**
	 * Process PayPal payment
	 */
	async processPayPalPayment() {
		// PayPal uses a different flow - it's handled by PayPal buttons
		// which call the backend directly and store completion status

		// Check if PayPal payment completion data exists for this block
		const paypalPaymentData = window.srfmPayPalPayments?.[ this.blockId ];

		// Verify that PayPal payment was completed
		if ( paypalPaymentData && paypalPaymentData.completed === true ) {
			// Store the payment result in srfmPaymentElements for form submission
			if ( ! window.srfmPaymentElements ) {
				window.srfmPaymentElements = {};
			}

			window.srfmPaymentElements[ this.blockId ] = {
				paymentMethod: 'paypal',
				paypalOrderId: paypalPaymentData.orderID,
				paypalSubscriptionId: paypalPaymentData.subscriptionID,
				paypalPayerEmail: paypalPaymentData.payerEmail || '',
				paypalPayerName: paypalPaymentData.payerName || '',
				completed: true,
			};

			return {
				valid: true,
				message: window.srfmPaymentUtility?.getStripeStrings(
					'payment_successful',
					'Payment successful'
				),
				paymentResult: window.srfmPaymentElements[ this.blockId ],
			};
		}

		return {
			valid: false,
			message: window.srfmPaymentUtility?.getStripeStrings(
				'paypal_payment_incomplete',
				'Please complete your PayPal payment before submitting the form.'
			),
		};
	}
}

// Initialize payment managers for all payment blocks
function initializePaymentManagers() {
	document.addEventListener( 'srfm_form_after_initialization', ( event ) => {
		const form = event.detail.form;
		const paymentBlocks = form.querySelectorAll( '.srfm-payment-block' );

		paymentBlocks.forEach( ( paymentBlock ) => {
			const blockId = paymentBlock.getAttribute( 'data-block-id' );

			// Store payment manager instance
			if ( ! window.srfmPaymentManagers ) {
				window.srfmPaymentManagers = {};
			}

			window.srfmPaymentManagers[ blockId ] = new PaymentManager(
				paymentBlock,
				form
			);
		} );
	} );
}

initializePaymentManagers();
