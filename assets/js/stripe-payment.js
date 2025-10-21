/**
 * SureForms Stripe Payment Integration
 *
 * @since x.x.x
 */
/* global Stripe, srfm_ajax */

/**
 * Get currency symbol for a given currency code.
 *
 * @param {string} currencyCode - Currency code (e.g., 'USD', 'INR').
 * @return {string} Currency symbol.
 */
function getCurrencySymbol( currencyCode ) {
	const currencies = {
		USD: '$',
		EUR: '€',
		GBP: '£',
		JPY: '¥',
		AUD: 'A$',
		CAD: 'C$',
		CHF: 'CHF',
		CNY: '¥',
		SEK: 'kr',
		NZD: 'NZ$',
		MXN: 'MX$',
		SGD: 'S$',
		HKD: 'HK$',
		NOK: 'kr',
		KRW: '₩',
		TRY: '₺',
		RUB: '₽',
		INR: '₹',
		BRL: 'R$',
		ZAR: 'R',
		AED: 'د.إ',
	};

	const upperCurrencyCode = currencyCode?.toUpperCase();
	return currencies[ upperCurrencyCode ] || currencyCode;
}

class StripePayment {
	// Store Stripe instances
	static stripeInstances = {};
	static paymentElements = {};
	static paymentIntents = {};
	static subscriptionIntents = {};

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

		console.log( 'SureForms: StripePayment constructor' );

		// Find all payment blocks within the form.
		const getPaymentFields = this.form.querySelectorAll(
			'.srfm-block.srfm-payment-block'
		);

		// Initialize Stripe payment for each payment field.
		getPaymentFields.forEach( ( field ) => {
			this.processPayment( field );
		} );

		// Listen for user-defined amount changes
		this.listenUserAmountChanges();
	}

	/**
	 * Listen for changes to user-defined amount inputs
	 */
	listenUserAmountChanges() {
		const userAmountInputs = this.form.querySelectorAll(
			'.srfm-user-amount-field'
		);

		userAmountInputs.forEach( ( input ) => {
			input.addEventListener( 'input', () => {
				const paymentBlock = input.closest( '.srfm-payment-block' );
				const blockId = paymentBlock.getAttribute( 'data-block-id' );
				const amount = parseFloat( input.value || 0 );

				const paymentInput = paymentBlock.querySelector(
					'.srfm-payment-input'
				);
				this.updatePaymentIntentAmount( blockId, amount, paymentInput );
			} );
		} );
	}

	updatePaymentIntentAmount( blockId, newAmount, paymentHiddenInput ) {
		const currency = paymentHiddenInput.dataset.currency || 'usd';
		const currencySymbol = getCurrencySymbol( currency );
		console.log(
			`Amount updated for block ${ blockId }: ${ currencySymbol }${ newAmount }`
		);

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
				`SureForms: Elements updated with new amount ${ currencySymbol }${ newAmount } for block ${ blockId } (type: ${ paymentType })`
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
	 * Create payment or subscription intent during form submission.
	 * Unified function that handles both payment types based on the paymentType parameter.
	 *
	 * @param {string}      blockId      - Block ID.
	 * @param {number}      amount       - The amount for the payment/subscription.
	 * @param {HTMLElement} paymentInput - The payment input element.
	 * @param {string}      paymentType  - Payment type: 'one-time' or 'subscription'.
	 * @return {Promise<Object>} Resolves with payment/subscription data.
	 */
	async createPaymentIntentOnSubmission(
		blockId,
		amount,
		paymentInput,
		paymentType = 'one-time'
	) {
		// Setup
		const isSubscription = paymentType === 'subscription';
		const typeLabel = isSubscription ? 'subscription' : 'payment intent';

		console.log(
			`SureForms: Creating ${ typeLabel } for block ${ blockId } during form submission`
		);
		this.set_block_loading( blockId, true );

		// Extract common data
		const currency = paymentInput.dataset.currency || 'usd';
		const description =
			paymentInput.dataset.description ||
			( isSubscription ? 'SureForms Subscription' : 'SureForms Payment' );

		// Build FormData
		const data = new FormData();
		data.append(
			'action',
			isSubscription
				? 'srfm_create_subscription_intent'
				: 'srfm_create_payment_intent'
		);
		data.append( 'nonce', srfm_ajax.nonce );
		data.append( 'amount', parseInt( amount * 100 ) );
		data.append( 'currency', currency );
		data.append( 'description', description );
		data.append( 'block_id', blockId );

		// Add subscription-specific data
		let customerData = null;
		if ( isSubscription ) {
			customerData = this.extractCustomerData( paymentInput );
			data.append( 'interval', customerData.interval );
			data.append( 'plan_name', customerData.planName );
		}

		// Make API call
		try {
			const response = await fetch( srfm_ajax.ajax_url, {
				method: 'POST',
				body: data,
			} );

			const responseData = await response.json();

			this.set_block_loading( blockId, false );

			console.log(
				`SureForms: ${
					isSubscription ? 'Subscription' : 'Payment'
				} response:`,
				responseData
			);

			// Handle success
			if ( responseData.success ) {
				const clientSecret = responseData.data.client_secret;
				const paymentIntentId = responseData.data.payment_intent_id;

				// Store payment/subscription data
				if ( isSubscription ) {
					const subscriptionId = responseData.data.subscription_id;
					const customerId = responseData.data.customer_id;

					StripePayment.subscriptionIntents[ blockId ] = {
						subscriptionId,
						customerId,
						paymentIntentId,
						amount,
						interval: customerData.interval,
					};
				} else {
					StripePayment.paymentIntents[ blockId ] = paymentIntentId;
				}

				// Update elements with client secret
				const elementData = StripePayment.paymentElements[ blockId ];
				if ( elementData ) {
					// CRITICAL: Store client secret WITHOUT calling elements.update()
					// This preserves user-entered card data
					elementData.clientSecret = clientSecret;

					console.log(
						`SureForms: Client secret stored for ${
							isSubscription ? 'subscription' : 'payment'
						} block ${ blockId } (card data preserved)`
					);
				}

				// Return type-specific response
				if ( isSubscription ) {
					return {
						clientSecret,
						subscriptionId: responseData.data.subscription_id,
						customerId: responseData.data.customer_id,
						paymentIntentId,
						status: true,
					};
				}
				return { clientSecret, paymentIntentId };
			}

			// Handle failure
			return {
				valid: false,
				message:
					responseData.data?.message ||
					responseData.data ||
					`Failed to create ${ typeLabel }`,
			};
		} catch ( error ) {
			this.set_block_loading( blockId, false );
			console.error( `SureForms: Error creating ${ typeLabel }:`, error );
			return {
				valid: false,
				message: error.message || `Failed to create ${ typeLabel }`,
			};
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

		// Check payment type from data attribute
		const paymentType =
			field.getAttribute( 'data-payment-type' ) || 'one-time';

		console.log(
			`SureForms: Initializing payment for block ${ blockId }, type: ${ paymentType }`
		);

		// Initialize Stripe elements using unified function
		this.initializePaymentElements( blockId, paymentInput, paymentType );
	}

	/**
	 * Initialize Stripe elements for one-time payments or subscriptions.
	 * Unified function that handles both payment types based on the paymentType parameter.
	 *
	 * @param {string}      blockId      - Block ID.
	 * @param {HTMLElement} paymentInput - The payment input element.
	 * @param {string}      paymentType  - Payment type: 'one-time' or 'subscription'.
	 * @return {void} This function does not return a value.
	 */
	initializePaymentElements(
		blockId,
		paymentInput,
		paymentType = 'one-time'
	) {
		// CRITICAL: Check if elements already exist to prevent re-initialization
		// Re-mounting elements destroys user-entered card data
		if ( StripePayment.paymentElements[ blockId ] ) {
			const typeLabel =
				paymentType === 'subscription'
					? 'SUBSCRIPTION'
					: 'ONE-TIME PAYMENT';
			console.log(
				`SureForms: ${ typeLabel } elements already initialized for block ${ blockId }, skipping re-initialization (preserving card data)`
			);
			return;
		}

		const typeLabel =
			paymentType === 'subscription'
				? 'SUBSCRIPTION'
				: 'ONE-TIME PAYMENT';
		const modeLabel =
			paymentType === 'subscription'
				? 'subscription'
				: 'payment, captureMethod: manual';
		console.log(
			`SureForms: Initializing ${ typeLabel } elements for block ${ blockId } with mode: ${ modeLabel }`
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

		// Build elements configuration based on payment type
		const elementsConfig = {
			mode: paymentType === 'subscription' ? 'subscription' : 'payment',
			currency: paymentInput.dataset.currency || 'usd',
			appearance: {
				theme: 'stripe',
				variables: {
					colorPrimary: '#0073aa',
					colorBackground: '#ffffff',
					colorText: '#424242',
					colorDanger: '#df1b41',
					spacingUnit: '4px',
					borderRadius: '4px',
					fontFamily: '"Manrope", sans-serif',
				},
			},
			fields: {
				billingDetails: {
					email: 'auto', // ✅ Email + Link enabled
				},
			},
		};

		// Add type-specific configuration
		let currentAmount = null;
		if ( paymentType === 'subscription' ) {
			// Placeholder amount for subscriptions, will be updated by backend
			elementsConfig.amount = 1200;
		} else {
			// Get real payment amount for one-time payments
			currentAmount = StripePayment.getPaymentAmount( paymentInput );
			elementsConfig.amount = Math.round( currentAmount * 100 );
			// captureMethod: 'manual' tells Elements to expect manual capture PaymentIntents
			// This prevents "capture_method mismatch" errors when backend uses manual capture
			elementsConfig.captureMethod = 'manual';
		}

		// Create and mount payment element
		const elements = stripe.elements( elementsConfig );
		const paymentElement = elements.create( 'payment' );
		paymentElement.mount( elementContainer );

		// Store references (structure varies by payment type)
		const storedData = {
			stripe,
			elements,
			paymentElement,
			clientSecret: null, // Will be set when payment/subscription intent is created
		};

		if ( paymentType === 'subscription' ) {
			storedData.paymentType = 'subscription';
		} else {
			storedData.currentAmount = currentAmount; // Store current amount for later comparison
		}

		StripePayment.paymentElements[ blockId ] = storedData;

		// Update window object
		window.srfmPaymentElements = StripePayment.paymentElements;

		// Setup event handlers
		this.setupPaymentElementEvents( paymentElement, blockId, paymentType );
	}

	/**
	 * Setup event handlers for payment element.
	 *
	 * @param {Object} paymentElement - The Stripe payment element.
	 * @param {string} blockId        - Block ID.
	 * @param {string} paymentType    - Payment type: 'one-time' or 'subscription'.
	 * @return {void} This function does not return a value.
	 */
	setupPaymentElementEvents( paymentElement, blockId, paymentType ) {
		// Ready event
		paymentElement.on( 'ready', () => {
			const typeLabel =
				paymentType === 'subscription' ? 'Subscription' : 'Payment';
			console.log(
				`SureForms: ${ typeLabel } element ready for block ${ blockId }`
			);
		} );

		// Change event (handling differs by payment type)
		paymentElement.on( 'change', ( event ) => {
			if ( event.error ) {
				if ( paymentType === 'subscription' ) {
					// Simple warning for subscriptions (often non-fatal validation warnings)
					console.warn(
						`SureForms: Subscription element validation warning for block ${ blockId }:`,
						event.error
					);
				} else {
					// Filtered validation for one-time payments
					// Only show meaningful validation errors, skip incomplete warnings during typing
					if ( this.shouldDisplayValidationError( event.error ) ) {
						console.warn(
							`SureForms: Card element validation for block ${ blockId }:`,
							event.error
						);
						this.displayElementError( blockId, event.error );
					}
				}
			} else if ( event.complete ) {
				const typeLabel =
					paymentType === 'subscription' ? 'Subscription' : 'Card';
				console.log(
					`SureForms: ${ typeLabel } element completed for block ${ blockId }`
				);

				// Clear any previous error messages (only for one-time payments)
				if ( paymentType !== 'subscription' ) {
					this.clearElementError( blockId );
				}
			}
		} );
	}

	/**
	 * Get payment amount based on amount type (fixed or user-defined).
	 *
	 * @param {HTMLElement} paymentInput - The payment input element.
	 * @return {number} The payment amount in dollars.
	 */
	static getPaymentAmount( paymentInput ) {
		const amountType = paymentInput.dataset.amountType || 'fixed';
		let amount = 0;

		if ( amountType === 'fixed' ) {
			// Get fixed amount from data attribute
			amount = parseFloat( paymentInput.dataset.fixedAmount || 0 );
		} else {
			// Get user-defined amount from input field
			const paymentBlock = paymentInput.closest( '.srfm-payment-block' );
			const userAmountInput = paymentBlock?.querySelector(
				'.srfm-user-amount-field'
			);
			amount = parseFloat( userAmountInput?.value || 0 );
		}

		// Return at least $1.00 minimum for Stripe
		const finalAmount = Math.max( amount, 1.0 );

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

		// Use static methods to extract customer data from mapped form fields
		const customerName =
			StripePayment.extractBillingName( form, block ) ||
			'SureForms Customer';
		const customerEmail =
			StripePayment.extractBillingEmail( form, block ) ||
			'customer@example.com';

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
			return {
				valid: false,
				message: `Payment input not found for block ${ blockId }`,
			};
		}

		// Get payment amount using helper method
		const amount = StripePayment.getPaymentAmount( paymentInput );

		if ( amount <= 0 ) {
			return {
				valid: false,
				message: `Invalid payment amount ${ amount } for block ${ blockId }`,
			};
		}

		try {
			// Create a temporary instance to call the method
			const tempInstance = new StripePayment( form );

			// Use unified function for both payment types
			const result = await tempInstance.createPaymentIntentOnSubmission(
				blockId,
				amount,
				paymentInput,
				paymentType
			);

			return {
				blockId,
				paymentType,
				valid: true,
				...result,
			};
		} catch ( error ) {
			return {
				valid: false,
				message: error.message || 'Failed to create payment intent',
			};
		}
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

	/**
	 * Extract billing name from form fields
	 * @param {HTMLElement} form         - The form element.
	 * @param {HTMLElement} paymentBlock - The payment block wrapper element.
	 * @return {string} The extracted billing name or a default value.
	 */
	static extractBillingName( form, paymentBlock ) {
		// Get the customer name field slug from payment block data attribute
		const customerNameFieldSlug = paymentBlock.getAttribute(
			'data-customer-name-field'
		);

		if ( ! customerNameFieldSlug || customerNameFieldSlug.trim() === '' ) {
			console.warn( 'Customer name field slug not configured' );
			return '';
		}

		// Find the actual name input field in the form using the slug
		const nameInput = form.querySelector(
			`.srfm-input-block.srfm-slug-${ customerNameFieldSlug } .srfm-input-common`
		);

		if ( ! nameInput ) {
			console.warn(
				`Name input field not found for slug: ${ customerNameFieldSlug }`
			);
			return '';
		}

		// Return the trimmed value
		return nameInput.value.trim() || '';
	}

	/**
	 * Extract billing email from form fields
	 * @param {HTMLElement} form         - The form element.
	 * @param {HTMLElement} paymentBlock - The payment block wrapper element.
	 * @return {string} The extracted billing email or a default value.
	 */
	static extractBillingEmail( form, paymentBlock ) {
		// Get the customer email field slug from payment block data attribute
		const customerEmailFieldSlug = paymentBlock.getAttribute(
			'data-customer-email-field'
		);

		if (
			! customerEmailFieldSlug ||
			customerEmailFieldSlug.trim() === ''
		) {
			console.warn( 'Customer email field slug not configured' );
			return '';
		}

		// Find the actual email input field in the form using the slug
		const emailInput = form.querySelector(
			`.srfm-email-block.srfm-slug-${ customerEmailFieldSlug } .srfm-input-common`
		);

		if ( ! emailInput ) {
			console.warn(
				`Email input field not found for slug: ${ customerEmailFieldSlug }`
			);
			return '';
		}

		// Return the trimmed value
		return emailInput.value.trim() || '';
	}

	/**
	 * Confirm payment for a specific block
	 * @param {string}      blockId     - The block ID.
	 * @param {Object}      paymentData - The payment data.
	 * @param {HTMLElement} form        - The form element.
	 * @return {Promise<string>} The payment intent or setup intent ID if successful.
	 */
	static async srfmConfirmPayment( blockId, paymentData, form ) {
		const { elements, paymentType } = paymentData;
		console.log("start confirmPayment");

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

		// Handle payment confirmation via unified handler
		return await StripePayment.confirmStripePayment(
			blockId,
			paymentData,
			form
		);
	}

	static async confirmStripePayment( blockId, paymentData, form ) {
		const { stripe, elements, clientSecret, paymentType } = paymentData;

		// Get the payment block element
		const paymentBlock = form.querySelector(
			`[data-block-id="${ blockId }"]`
		);
		// Update form input with subscription data for backend processing
		const paymentInput = paymentBlock.querySelector(
			'.srfm-payment-input'
		);

		const amount = StripePayment.getPaymentAmount( paymentInput );
		const amountType =
			paymentInput.getAttribute( 'data-amount-type' ) || 'fixed';

		// Prepare billing details using StripePayment class methods
		const billingDetails = {
			name: StripePayment.extractBillingName( form, paymentBlock ),
			email: StripePayment.extractBillingEmail( form, paymentBlock ),
		};

		const stripeArgs = {
			elements,
			clientSecret,
			confirmParams: {
				return_url: window.location.href,
				payment_method_data: {
					billing_details: billingDetails,
				},
			},
			redirect: 'if_required',
		};

		const paymentResult = await ( paymentType === 'subscription'
			? stripe.confirmSetup( stripeArgs )
			: stripe.confirmPayment( stripeArgs ) );

		if ( paymentResult?.error ) {
			StripePayment.addErrorValueInInput( paymentInput, paymentResult?.error );
			throw new Error(
				paymentResult?.error?.message || paymentResult?.error
			);
		}

		if (
			'one-time' === paymentType &&
			! [ 'succeeded', 'requires_capture' ].includes(
				paymentResult?.paymentIntent?.status
			)
		) {
			StripePayment.addErrorValueInInput(
				paymentInput,
				new Error( `Payment not completed for block ${ blockId }` )
			);
			throw new Error( `Payment not completed for block ${ blockId }` );
		}

		const resultArgs = {
			paymentResult,
			blockId,
			paymentType,
			amountType,
			amount,
			paymentInput,
			billingDetails
		};

		StripePayment.prepareInputValueData( resultArgs );

		return true;
	}

	/**
	 * Prepares and sets the payment input value data as a JSON string.
	 *
	 * @param {Object} args - The configuration arguments.
	 * @param {string} args.blockId - The payment block ID.
	 * @param {string} args.paymentType - The type of payment ('subscription' or 'one-time').
	 * @param {string} args.amountType - The type of amount ('fixed' or 'user-defined').
	 * @param {number} args.amount - The payment amount.
	 * @param {HTMLInputElement} args.paymentInput - The input field to store payment data.
	 * @param {Object} args.paymentResult - The result object from Stripe payment confirmation.
	 */
	static prepareInputValueData( args ) {
		const {
			blockId,
			paymentType,
			amountType,
			amount,
			paymentInput,
			paymentResult,
			billingDetails
		} = args;
		
		let value = {
			blockId,
			amountType,
			amount,
			...( billingDetails || {} )
		};

		if ( 'subscription' === paymentType ) {
			const subscriptionData = StripePayment.subscriptionIntents[ blockId ];

			value.paymentId = paymentResult?.setupIntent?.payment_method;
			value.setupIntent = paymentResult?.setupIntent?.id;
			value.subscriptionId = subscriptionData?.subscriptionId;
			value.customerId = subscriptionData?.customerId;
			value.paymentType = 'stripe-subscription';
			value.status = 'succeeded';
		} else {
			value.paymentId = paymentResult?.paymentIntent?.id;
			value.paymentType = 'stripe';
		}

		paymentInput.value = JSON.stringify( value );
	}

	/**
	 * Adds an error message to the payment input field in JSON format.
	 *
	 * @param {HTMLInputElement} paymentInput - The input field to store the error.
	 * @param {Error|string} error - The error object or message to store.
	 */
	addErrorValueInInput( paymentInput, error ) {
		paymentInput.value = JSON.stringify( {
			errorType: 'stripe_payment_confirmation_error',
			error: error?.message || error,
		} );
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
