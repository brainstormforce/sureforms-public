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
		this.compositeKey = window.srfmGetPaymentKey
			? window.srfmGetPaymentKey( form, this.blockId )
			: this.blockId;
		this.paymentInput = paymentBlock.querySelector( '.srfm-payment-input' );
		this.form = form;
		// AbortController scopes the document-level srfm_payment_type_changed
		// listener so it is removable on form re-initialization. Pattern mirrors
		// PAYMENT_UTILITY.listenAmountChanges() in stripe-payment.js.
		this.abortController = new AbortController();
		this.init();
	}

	/**
	 * Tear down listeners owned by this instance. Called by
	 * initializePaymentManagers before replacing this manager so document-level
	 * listeners don't accumulate across form re-initializations.
	 */
	destroy() {
		if ( this.abortController ) {
			this.abortController.abort();
		}
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

		// BOTH MODE: forward the payment-type change so gateways can reinit if needed.
		// stripe-payment.js dispatches `srfm_payment_type_changed` on the document.
		// We keep a per-instance listener so multiple payment blocks on a page don't
		// step on each other. Scoped to abortController so it can be torn down on
		// form re-initialization (see destroy()).
		document.addEventListener(
			'srfm_payment_type_changed',
			( event ) => {
				if ( event?.detail?.blockId !== this.blockId ) {
					return;
				}
				if ( event?.detail?.form !== this.form ) {
					return;
				}
				// Re-broadcast a payment-method event so accordion gateways re-render
				// for the new mode (Stripe is already handled by reinitForBlock).
				this.dispatchPaymentMethodEvent(
					this.getSelectedMethod(),
					this.form
				);
			},
			{ signal: this.abortController.signal }
		);
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

		const paymentData = window.srfmPaymentElements?.[ this.compositeKey ];

		if ( paymentData && paymentData.clientSecret ) {
			const paymentResult = await window.StripePayment.srfmConfirmPayment(
				this.compositeKey,
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

		// Check if PayPal payment completion data exists for this block.
		// No blockId fallback needed here — sureforms-pro (which writes to srfmPayPalPayments)
		// is always updated alongside sureforms (core) since we enforce minimum core version
		// via SRFM_PRO_CORE_RQD_VER, so both plugins will use compositeKey simultaneously.
		const paypalPaymentData =
			window.srfmPayPalPayments?.[ this.compositeKey ];

		// Verify that PayPal payment was completed
		if ( paypalPaymentData && paypalPaymentData.completed === true ) {
			// Store the payment result in srfmPaymentElements for form submission
			if ( ! window.srfmPaymentElements ) {
				window.srfmPaymentElements = {};
			}

			window.srfmPaymentElements[ this.compositeKey ] = {
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
				paymentResult: window.srfmPaymentElements[ this.compositeKey ],
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
			const compositeKey = window.srfmGetPaymentKey
				? window.srfmGetPaymentKey( form, blockId )
				: blockId;

			// Store payment manager instance
			if ( ! window.srfmPaymentManagers ) {
				window.srfmPaymentManagers = {};
			}

			// Tear down the previous manager (if any) so its document-level
			// srfm_payment_type_changed listener is removed before we replace it.
			window.srfmPaymentManagers[ compositeKey ]?.destroy?.();

			window.srfmPaymentManagers[ compositeKey ] = new PaymentManager(
				paymentBlock,
				form
			);
		} );
	} );
}

initializePaymentManagers();
