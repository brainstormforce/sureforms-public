/**
 * SureForms Stripe Payment Integration
 *
 * @since 2.0.0
 */
/* global Stripe, srfm_ajax */

/**
 * Get composite key for a payment block within a specific form instance.
 * Supports multiple embeds of the same form on one page.
 *
 * @param {HTMLElement} form    - The form element.
 * @param {string}      blockId - The block ID from data-block-id attribute.
 * @return {string} Composite key in format "instanceId-blockId", or plain blockId as fallback.
 */
function getPaymentKey( form, blockId ) {
	const instanceId = form.getAttribute( 'data-srfm-instance' );
	return instanceId ? `${ instanceId }-${ blockId }` : blockId;
}
window.srfmGetPaymentKey = getPaymentKey;

class StripePayment {
	// Store Stripe instances
	static stripeInstances = {};
	static paymentElements = {};
	static paymentIntents = {};
	static subscriptionIntents = {};
	// BOTH MODE: per-block guard so rapid type-flips don't race two
	// Stripe.elements() instances on the same DOM container. Keyed by
	// compositeKey, cleared in the 'ready' handler of the new element.
	static reinitInProgress = new Set();

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
	}

	/**
	 * Create payment or subscription intent during form submission.
	 * Unified function that handles both payment types based on the paymentType parameter.
	 *
	 * @param {string}      blockId      - Block ID (original, for server API).
	 * @param {string}      compositeKey - Composite key (instanceId-blockId, for client-side maps).
	 * @param {number}      amount       - The amount for the payment/subscription.
	 * @param {HTMLElement} paymentInput - The payment input element.
	 * @param {string}      paymentType  - Payment type: 'one-time' or 'subscription'.
	 * @return {Promise<Object>} Resolves with payment/subscription data.
	 */
	async createPaymentIntentOnSubmission(
		blockId,
		compositeKey,
		amount,
		paymentInput,
		paymentType = 'one-time'
	) {
		// Setup
		const isSubscription = paymentType === 'subscription';
		const customerData = this.extractCustomerData( paymentInput );

		// Extract common data
		const currency = paymentInput.dataset.currency || 'usd';
		const description =
			paymentInput.dataset.description ||
			( isSubscription
				? PAYMENT_UTILITY.getStripeStrings(
					'sureforms_subscription',
					'SureForms Subscription'
				  )
				: PAYMENT_UTILITY.getStripeStrings(
					'sureforms_payment',
					'SureForms Payment'
				  ) );

		// Build FormData
		const data = new FormData();
		data.append(
			'action',
			isSubscription
				? 'srfm_create_subscription_intent'
				: 'srfm_create_payment_intent'
		);
		// Read submit token from form element for server-side verification.
		const formElement = paymentInput.closest( 'form' );
		data.append( 'token', formElement?.getAttribute( 'data-submit-token' ) || '' );
		// Handle zero-decimal currencies (JPY, KRW, etc.) - don't multiply by 100
		const formattedAmount =
			window?.srfmStripe?.zeroDecimalCurrencies?.includes(
				currency.toUpperCase()
			)
				? parseInt( amount )
				: parseInt( amount * 100 );
		data.append( 'amount', formattedAmount );
		data.append( 'currency', currency );
		data.append( 'description', description );
		data.append( 'block_id', blockId );
		data.append( 'customer_email', customerData.email );
		data.append( 'customer_name', customerData.name );
		const formIdInput = formElement?.querySelector(
			'input[name="form-id"]'
		);
		if ( formIdInput?.value ) {
			data.append( 'form_id', formIdInput.value );
		}

		// Add subscription-specific data
		if ( isSubscription ) {
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

			// Handle success
			if ( responseData.success ) {
				const clientSecret = responseData.data.client_secret;
				const paymentIntentId = responseData.data.payment_intent_id;
				const customerId = responseData?.data?.customer_id || null;

				// Store payment/subscription data
				if ( isSubscription ) {
					const subscriptionId = responseData.data.subscription_id;

					StripePayment.subscriptionIntents[ compositeKey ] = {
						subscriptionId,
						customerId: customerId || null,
						paymentIntentId,
						amount,
						interval: customerData.interval,
					};
				} else {
					StripePayment.paymentIntents[ compositeKey ] = {
						paymentIntentId,
						customerId: customerId || null,
					};
				}

				// Update elements with client secret
				const elementData =
					StripePayment.paymentElements[ compositeKey ];
				if ( elementData ) {
					// CRITICAL: Store client secret WITHOUT calling elements.update()
					// This preserves user-entered card data
					elementData.clientSecret = clientSecret;
				}

				return { valid: true };
			}

			// Handle failure
			return {
				valid: false,
				message:
					responseData.data?.message ||
					responseData.data ||
					PAYMENT_UTILITY.getStripeStrings(
						'payment_unavailable',
						'Payment is currently unavailable. Please contact the site administrator.'
					),
			};
		} catch ( error ) {
			return {
				valid: false,
				message:
					error.message ||
					PAYMENT_UTILITY.getStripeStrings(
						'payment_unavailable',
						'Payment is currently unavailable. Please contact the site administrator.'
					),
			};
		}
	}

	processPayment( field ) {
		const paymentInput = field.querySelector( 'input.srfm-payment-input' );

		if ( ! paymentInput ) {
			return;
		}

		const blockId = field.getAttribute( 'data-block-id' );
		const compositeKey = getPaymentKey( this.form, blockId );
		// Check payment type from data attribute
		const paymentType =
			paymentInput.getAttribute( 'data-payment-type' ) || 'one-time';

		// Initialize Stripe elements using unified function
		this.initializePaymentElements(
			compositeKey,
			paymentInput,
			paymentType
		);
	}

	/**
	 * Initialize Stripe elements for one-time payments or subscriptions.
	 * Unified function that handles both payment types based on the paymentType parameter.
	 *
	 * @param {string}      compositeKey - Composite key (instanceId-blockId) for client-side maps.
	 * @param {HTMLElement} paymentInput - The payment input element.
	 * @param {string}      paymentType  - Payment type: 'one-time' or 'subscription'.
	 * @return {void} This function does not return a value.
	 */
	initializePaymentElements(
		compositeKey,
		paymentInput,
		paymentType = 'one-time'
	) {
		// CRITICAL: Check if elements already exist to prevent re-initialization
		// Re-mounting elements destroys user-entered card data
		if ( StripePayment.paymentElements[ compositeKey ] ) {
			return;
		}

		const stripeKey = paymentInput.dataset.stripeKey;

		if ( ! stripeKey ) {
			return;
		}

		const elementContainer = paymentInput
			.closest( '.srfm-block' )
			.querySelector( '.srfm-stripe-payment-element' );

		if ( ! elementContainer ) {
			return;
		}

		// Initialize Stripe
		if ( ! StripePayment.stripeInstances[ compositeKey ] ) {
			StripePayment.stripeInstances[ compositeKey ] = Stripe( stripeKey );
		}

		const stripe = StripePayment.stripeInstances[ compositeKey ];

		// Build elements configuration based on payment type
		const elementsConfig = {
			mode: paymentType === 'subscription' ? 'subscription' : 'payment',
			currency: paymentInput.dataset.currency || 'usd',
			amount: 12000,
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
		if ( paymentType === 'one-time' ) {
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
			paymentType,
		};

		StripePayment.paymentElements[ compositeKey ] = storedData;

		// Update window object
		window.srfmPaymentElements = StripePayment.paymentElements;

		// Setup event handlers
		this.setupPaymentElementEvents( paymentElement, compositeKey );
	}

	/**
	 * Setup event handlers for payment element.
	 *
	 * @param {Object} paymentElement - The Stripe payment element.
	 * @param {string} compositeKey   - Composite key (instanceId-blockId) used to clear the reinit-in-flight flag.
	 * @return {void} This function does not return a value.
	 */
	setupPaymentElementEvents( paymentElement, compositeKey ) {
		// Ready event — clears the reinit-in-flight flag so subsequent type
		// flips can proceed. Without this, a stale flag would block all future
		// reinits for this block.
		paymentElement.on( 'ready', () => {
			if ( compositeKey ) {
				StripePayment.reinitInProgress.delete( compositeKey );
			}
		} );
	}

	/**
	 * Get payment amount based on amount type (fixed or user-defined).
	 *
	 * @param {HTMLElement} paymentInput - The payment input element.
	 * @return {number|false} The payment amount in dollars, or false if invalid.
	 */
	static getPaymentAmount( paymentInput ) {
		const amountType = paymentInput.dataset.amountType || 'fixed';
		let amount = 0;

		if ( amountType === 'fixed' ) {
			// Get fixed amount from data attribute
			amount = parseFloat( paymentInput.dataset.fixedAmount || 0 );
		} else {
			// Get the format type for dynamic amounts
			const formatType =
				paymentInput.getAttribute(
					'data-dynamic-amount-format-type'
				) || 'us-style';
			const rawAmount = paymentInput.dataset.currentAmount || 0;

			// Normalize the amount based on format type
			amount = StripePayment.normalizeAmount( rawAmount, formatType );
		}

		// Validate the amount - must be valid, not negative, and greater than 0
		if ( isNaN( amount ) ) {
			return false;
		}

		if ( amount < 0 ) {
			return false;
		}

		if ( amount <= 0 ) {
			return false;
		}

		// Additional validation using existing method
		if ( ! StripePayment.validatePaymentAmount( amount ) ) {
			return false;
		}

		return amount;
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

		// Get subscription plan data from input attributes
		const planName =
			paymentInput.dataset.subscriptionPlanName ||
			PAYMENT_UTILITY.getStripeStrings(
				'subscription_plan',
				'Subscription Plan'
			);
		const interval = paymentInput.dataset.subscriptionInterval || 'month';

		// Use static methods to extract customer data from mapped form fields
		const customerName =
			StripePayment.extractBillingName( form, block ) ||
			PAYMENT_UTILITY.getStripeStrings(
				'sureforms_customer',
				'SureForms Customer'
			);
		const customerEmail =
			StripePayment.extractBillingEmail( form, block ) ||
			PAYMENT_UTILITY.getStripeStrings(
				'customer_example_email',
				'customer@example.com'
			);

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
		const paymentInput = paymentBlock.querySelector(
			'input.srfm-payment-input'
		);
		const paymentType =
			paymentInput.getAttribute( 'data-payment-type' ) || 'one-time';

		if ( ! paymentInput ) {
			return {
				valid: false,
				message: PAYMENT_UTILITY.getStripeStrings(
					'payment_unavailable',
					'Payment is currently unavailable. Please contact the site administrator.'
				),
			};
		}

		// Get payment amount using helper method
		const amount = StripePayment.getPaymentAmount( paymentInput );

		if ( false === amount ) {
			return {
				valid: false,
				message: PAYMENT_UTILITY.getStripeStrings(
					'payment_amount_not_configured',
					'Payment is currently unavailable. Please contact the site administrator to configure the payment amount.'
				),
			};
		} else if ( amount <= 0 ) {
			return {
				valid: false,
				message: PAYMENT_UTILITY.getStripeStrings(
					'payment_amount_not_configured',
					'Payment is currently unavailable. Please contact the site administrator to configure the payment amount.'
				),
			};
		}

		try {
			// Create a temporary instance to call the method
			const tempInstance = new StripePayment( form );
			const blockId = paymentBlock.getAttribute( 'data-block-id' );
			const compositeKey = getPaymentKey( form, blockId );

			// Use unified function for both payment types
			const result = await tempInstance.createPaymentIntentOnSubmission(
				blockId,
				compositeKey,
				amount,
				paymentInput,
				paymentType
			);

			return {
				blockId,
				compositeKey,
				paymentType,
				valid: true,
				...result,
			};
		} catch ( error ) {
			return {
				valid: false,
				message:
					error.message ||
					PAYMENT_UTILITY.getStripeStrings(
						'payment_unavailable',
						'Payment is currently unavailable. Please contact the site administrator.'
					),
			};
		}
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
			return false;
		}
		if ( amount > maxAmount ) {
			return false;
		}
		return true;
	}

	/**
	 * Normalize amount based on number format type (EU-style or US-style)
	 * @param {string|number} amount     - The amount to normalize.
	 * @param {string}        formatType - The format type: 'eu-style' or 'us-style'.
	 * @return {number} The normalized amount as a number.
	 */
	static normalizeAmount( amount, formatType = 'us-style' ) {
		// If already a number, return it
		if ( typeof amount === 'number' ) {
			return amount;
		}

		// Convert to string and trim
		const amountStr = String( amount ).trim();

		if ( formatType === 'eu-style' ) {
			// EU-style: 1.234,56 (period = thousands, comma = decimal)
			// Remove periods (thousands separator) and replace comma with period (decimal)
			return parseFloat(
				amountStr.replace( /\./g, '' ).replace( ',', '.' )
			);
		}

		// US-style (default): 1,234.56 (comma = thousands, period = decimal)
		// Remove commas (thousands separator)
		return parseFloat( amountStr.replace( /,/g, '' ) );
	}

	/**
	 * Extract billing name from form fields
	 * @param {HTMLElement} form         - The form element.
	 * @param {HTMLElement} paymentBlock - The payment block wrapper element.
	 * @return {string} The extracted billing name or a default value.
	 */
	static extractBillingName( form, paymentBlock ) {
		// Get the customer name field slug from payment input data attribute
		const paymentInput = paymentBlock.querySelector(
			'input.srfm-payment-input'
		);
		const customerNameFieldSlug = paymentInput
			? paymentInput.getAttribute( 'data-customer-name-field' )
			: null;

		if ( ! customerNameFieldSlug || customerNameFieldSlug.trim() === '' ) {
			return '';
		}

		// Find the actual name input field in the form using the slug
		const nameInput = form.querySelector(
			`.srfm-input-block.srfm-slug-${ customerNameFieldSlug } .srfm-input-common`
		);

		if ( ! nameInput ) {
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
		// Get the customer email field slug from payment input data attribute
		const paymentInput = paymentBlock.querySelector(
			'input.srfm-payment-input'
		);
		const customerEmailFieldSlug = paymentInput
			? paymentInput.getAttribute( 'data-customer-email-field' )
			: null;

		if (
			! customerEmailFieldSlug ||
			customerEmailFieldSlug.trim() === ''
		) {
			return '';
		}

		// Find the actual email input field in the form using the slug
		const emailInput = form.querySelector(
			`.srfm-email-block.srfm-slug-${ customerEmailFieldSlug } .srfm-input-common`
		);

		if ( ! emailInput ) {
			return '';
		}

		// Return the trimmed value
		return emailInput.value.trim() || '';
	}

	/**
	 * Confirm payment for a specific block
	 * @param {string}      compositeKey - Composite key (instanceId-blockId) for client-side maps.
	 * @param {Object}      paymentData  - The payment data.
	 * @param {HTMLElement} form         - The form element.
	 * @return {Promise<string>} The payment intent or setup intent ID if successful.
	 */
	static async srfmConfirmPayment( compositeKey, paymentData, form ) {
		const { elements } = paymentData;

		// Validate card details AFTER payment intent is created but BEFORE confirmation
		// This is the correct timing to avoid card data loss
		const { error: submitError } = await elements.submit();

		if ( submitError ) {
			return {
				valid: false,
				error: submitError.message,
				message: submitError.message,
			};
		}

		// Handle payment confirmation via unified handler
		try {
			return await StripePayment.confirmStripePayment(
				compositeKey,
				paymentData,
				form
			);
		} catch ( error ) {
			// Catch any errors thrown by confirmStripePayment and return consistent structure
			return {
				valid: false,
				error: error.message || error,
				message:
					error.message ||
					PAYMENT_UTILITY.getStripeStrings(
						'payment_failed',
						'Payment failed'
					),
			};
		}
	}

	static async confirmStripePayment( compositeKey, paymentData, form ) {
		const { stripe, elements, clientSecret } = paymentData;

		// Extract original blockId from compositeKey for DOM queries
		// compositeKey format is always "numericInstanceId-blockId"
		const separatorIndex = compositeKey.indexOf( '-' );
		const blockId =
			separatorIndex > -1
				? compositeKey.substring( separatorIndex + 1 )
				: compositeKey;

		// Get the payment block element
		const paymentBlock = form.querySelector(
			`[data-block-id="${ blockId }"]`
		);
		// Update form input with subscription data for backend processing
		const paymentInput = paymentBlock.querySelector(
			'.srfm-payment-input'
		);

		// BOTH MODE: read paymentType fresh from the live data attribute rather
		// than the cached paymentData. reinitForBlock() rebuilds the cache on
		// type flip, and a confirm captured before the flip would otherwise
		// branch on a stale type. If paymentType and clientSecret ever truly
		// diverge, Stripe itself rejects the wrong confirmSetup/confirmPayment
		// call — no need for a redundant client-side guard here.
		const paymentType =
			paymentInput.getAttribute( 'data-payment-type' ) ||
			paymentData.paymentType ||
			'one-time';

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
			console.warn( { 'Payment Confirmation Error': paymentResult } );

			const getErrorCode =
				paymentResult?.error?.decline_code ||
				paymentResult?.error?.code;
			// Get the user-friendly message for the decline code
			const errorMessage =
				PAYMENT_UTILITY.getStripeStrings( getErrorCode );

			return {
				valid: false,
				error: paymentResult.error,
				message: errorMessage,
				...paymentResult,
			};
		}

		if (
			'one-time' === paymentType &&
			! [ 'succeeded', 'requires_capture' ].includes(
				paymentResult?.paymentIntent?.status
			)
		) {
			const errorMessage = PAYMENT_UTILITY.getStripeStrings(
				'payment_could_not_be_completed',
				'Payment could not be completed. Please try again or contact the site administrator.'
			);

			return {
				valid: false,
				error: errorMessage,
				message: errorMessage,
				paymentResult,
			};
		}

		const amount = StripePayment.getPaymentAmount( paymentInput );

		const resultArgs = {
			paymentResult,
			compositeKey,
			blockId,
			paymentType,
			amountType,
			amount,
			paymentInput,
			billingDetails,
		};

		StripePayment.prepareInputValueData( resultArgs );

		return { valid: true };
	}

	/**
	 * Prepares and sets the payment input value data as a JSON string.
	 *
	 * @param {Object}           args               - The configuration arguments.
	 * @param {string}           args.blockId       - The payment block ID.
	 * @param {string}           args.paymentType   - The type of payment ('subscription' or 'one-time').
	 * @param {string}           args.amountType    - The type of amount ('fixed' or 'user-defined').
	 * @param {number}           args.amount        - The payment amount.
	 * @param {HTMLInputElement} args.paymentInput  - The input field to store payment data.
	 * @param {Object}           args.paymentResult - The result object from Stripe payment confirmation.
	 */
	static prepareInputValueData( args ) {
		const {
			compositeKey,
			blockId,
			paymentType,
			amountType,
			amount,
			paymentInput,
			paymentResult,
			billingDetails,
		} = args;

		const value = {
			blockId,
			amountType,
			amount,
			...( billingDetails || {} ),
		};

		if ( 'subscription' === paymentType ) {
			const subscriptionData =
				StripePayment.subscriptionIntents[ compositeKey ];
			const getSubscriptionName = paymentInput.getAttribute(
				'data-subscription-plan-name'
			);
			const getSubscriptionBillingCycles = paymentInput.getAttribute(
				'data-subscription-billing-cycles'
			);
			const getSubscriptionInterval = paymentInput.getAttribute(
				'data-subscription-interval'
			);
			value.subscriptionPlanName = getSubscriptionName;
			value.subscriptionBillingCycles = getSubscriptionBillingCycles;
			value.subscriptionInterval = getSubscriptionInterval;

			value.paymentId = paymentResult?.setupIntent?.payment_method;
			value.setupIntent = paymentResult?.setupIntent?.id;
			value.subscriptionId = subscriptionData?.subscriptionId;
			value.customerId = subscriptionData?.customerId;
			value.paymentType = 'stripe-subscription';
			value.status = 'succeeded';
		} else {
			const paymentData = StripePayment.paymentIntents[ compositeKey ];
			const customerId = paymentData?.customerId || null;
			value.paymentId = paymentResult?.paymentIntent?.id;
			value.paymentType = 'stripe';
			value.customerId = customerId || null;
		}

		paymentInput.value = JSON.stringify( value );
	}
}

// BOTH MODE: start — helper to re-mount Stripe Elements when the user flips the
// payment-type chooser between one-time and subscription. Stripe's elements.update()
// does not reliably support switching `mode`, so we unmount, drop the cached
// element, and let the existing initializer pathway recreate everything.
StripePayment.reinitForBlock = function ( form, paymentBlock ) {
	const paymentInput = paymentBlock.querySelector( 'input.srfm-payment-input' );
	if ( ! paymentInput ) {
		return;
	}

	const blockId = paymentBlock.getAttribute( 'data-block-id' );
	const compositeKey = getPaymentKey( form, blockId );

	// Bail if a reinit is already in flight for this block. Stripe's iframe
	// mount is async, and a second pass before 'ready' fires would race two
	// elements.create('payment') instances on the same DOM container. The
	// caller (radio change handler) reverts the radio UI when this guard
	// trips so the visible state stays consistent.
	if ( StripePayment.reinitInProgress.has( compositeKey ) ) {
		return;
	}

	const existing = StripePayment.paymentElements[ compositeKey ];
	if ( existing && existing.paymentElement ) {
		try {
			existing.paymentElement.unmount();
		} catch ( err ) {
			// Already unmounted — safe to ignore.
		}
	}
	delete StripePayment.paymentElements[ compositeKey ];
	delete StripePayment.paymentIntents[ compositeKey ];
	delete StripePayment.subscriptionIntents[ compositeKey ];
	window.srfmPaymentElements = StripePayment.paymentElements;

	StripePayment.reinitInProgress.add( compositeKey );

	// Re-init only the target block. Previously this used `new StripePayment(form)`
	// which walked every payment block in the form and re-ran processPayment on
	// each — wasted work on multi-block forms. Object.create gives us a stub
	// bound to the form (the only `this` field processPayment touches indirectly
	// via initializePaymentElements) without triggering the constructor walk.
	const stub = Object.create( StripePayment.prototype );
	stub.form = form;
	stub.processPayment( paymentBlock );
};
// BOTH MODE: end

// Make StripePayment available globally for form submission
window.StripePayment = StripePayment;

const PAYMENT_UTILITY = {
	currentForm: null,
	amountPlaceHolder: '',
	init: ( form ) => {
		PAYMENT_UTILITY.currentForm = form;
		PAYMENT_UTILITY.amountPlaceHolder = PAYMENT_UTILITY.getStripeStrings(
			'amount_placeholder',
			'Please complete the form to view the amount.'
		);
		PAYMENT_UTILITY.listenAmountChanges();
	},
	/**
	 * Format a number according to the format type (EU-style or US-style)
	 * @param {number|string} amount     - The amount to format
	 * @param {string}        formatType - The format type: 'eu-style' or 'us-style'
	 * @return {string} The formatted number string
	 */
	formatNumberByType: ( amount, formatType = 'us-style' ) => {
		// Normalize to a number first
		const normalizedAmount = StripePayment.normalizeAmount(
			amount,
			formatType
		);

		if ( isNaN( normalizedAmount ) ) {
			return '0.00';
		}

		// Format to 2 decimal places
		const fixedAmount = normalizedAmount.toFixed( 2 );

		if ( formatType === 'eu-style' ) {
			// EU-style: 1.234,56 (period = thousands, comma = decimal)
			const parts = fixedAmount.split( '.' );
			const integerPart = parts[ 0 ].replace(
				/\B(?=(\d{3})+(?!\d))/g,
				'.'
			);
			const decimalPart = parts[ 1 ];
			return integerPart + ',' + decimalPart;
		}

		// US-style (default): 1,234.56 (comma = thousands, period = decimal)
		const parts = fixedAmount.split( '.' );
		const integerPart = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, ',' );
		const decimalPart = parts[ 1 ];
		return integerPart + '.' + decimalPart;
	},

	/**
	 * Format subscription message by replacing {amount} placeholder with formatted amount
	 * @param {string} messageFormat   - The message format template (e.g., "{amount} per day for 8 payments")
	 * @param {number} amount          - The payment amount
	 * @param {string} currencySymbol  - The currency symbol (e.g., "$")
	 * @param {string} inputFormatType - The format type: 'eu-style' or 'us-style'
	 * @return {string} Formatted message
	 */
	formatSubscriptionMessage: (
		messageFormat,
		amount,
		currencySymbol,
		inputFormatType = 'us-style'
	) => {
		if ( ! messageFormat ) {
			return PAYMENT_UTILITY.amountPlaceHolder;
		}

		// Format amount with currency using the appropriate number format and position
		const formattedNumber =
			! amount || amount <= 0
				? ''
				: PAYMENT_UTILITY.formatNumberByType( amount, inputFormatType );

		const formattedAmount =
			'' !== formattedNumber
				? PAYMENT_UTILITY.formatAmountWithCurrencyPosition(
					currencySymbol,
					formattedNumber
				  )
				: '';

		// Replace {amount} placeholder with formatted amount
		return '' !== formattedAmount
			? messageFormat.replace( '{amount}', formattedAmount )
			: PAYMENT_UTILITY.amountPlaceHolder;
	},
	updatePaymentBlockAmount: (
		paymentInput,
		amount,
		inputFormatType = 'us-style'
	) => {
		// BOTH MODE: in "both" payment-type mode two .srfm-payment-value spans
		// exist (one per amount block). Target the VISIBLE one so the correct
		// type's amount updates. Falls back to the first match for non-both mode.
		const paymentBlock = paymentInput.closest( '.srfm-block' );
		const getPlaceHolderElement =
			paymentBlock.querySelector(
				'.srfm-payment-amount-block:not([hidden]) .srfm-payment-value'
			) || paymentBlock.querySelector( '.srfm-payment-value' );
		if ( getPlaceHolderElement ) {
			const getCurrencySymbol = getPlaceHolderElement.getAttribute(
				'data-currency-symbol'
			);
			const messageFormat = getPlaceHolderElement.getAttribute(
				'data-message-format'
			);

			if ( getCurrencySymbol ) {
				// Check if message format exists (for subscription messages)
				if ( messageFormat ) {
					const formattedMessage =
						PAYMENT_UTILITY.formatSubscriptionMessage(
							messageFormat,
							amount,
							getCurrencySymbol,
							inputFormatType
						);
					getPlaceHolderElement.innerHTML = formattedMessage;
				} else {
					// Fallback to simple amount display (backward compatible)
					// Format the amount according to the number format type and currency position
					const formattedNumber = PAYMENT_UTILITY.formatNumberByType(
						amount,
						inputFormatType
					);

					getPlaceHolderElement.innerHTML =
						PAYMENT_UTILITY.formatAmountWithCurrencyPosition(
							getCurrencySymbol,
							formattedNumber
						);
				}
			}
		}

		const normalizedAmount = StripePayment.normalizeAmount(
			amount,
			inputFormatType
		);

		paymentInput.setAttribute( 'data-current-amount', normalizedAmount );
		paymentInput.setAttribute(
			'data-dynamic-amount-format-type',
			inputFormatType
		);
	},
	listenAmountChanges: () => {
		const paymentInputs = PAYMENT_UTILITY.currentForm.querySelectorAll(
			'.srfm-block.srfm-payment-block input.srfm-payment-input[data-variable-amount-field]'
		);

		if ( paymentInputs.length > 0 ) {
			for ( let i = 0; i < paymentInputs.length; i++ ) {
				const paymentInput = paymentInputs[ i ];
				const getBlockMappedSlug = paymentInput.getAttribute(
					'data-variable-amount-field'
				);

				// BOTH MODE: tear down any listeners previously bound for this
				// payment input. switchActivePaymentType() calls this method again
				// each time the user flips one-time/subscription, which previously
				// piled up duplicate listeners on the source field. Round-trip
				// flips compounded the leak (field A → field B → field A → ...).
				// Each prior listener still fires on input events and can overwrite
				// the displayed amount with a stale source field's value.
				if ( paymentInput._srfmAmountListenerController ) {
					paymentInput._srfmAmountListenerController.abort();
				}
				const controller = new AbortController();
				paymentInput._srfmAmountListenerController = controller;
				const listenerOpts = { signal: controller.signal };

				if ( getBlockMappedSlug ) {
					const getMappedBlock =
						PAYMENT_UTILITY.currentForm.querySelector(
							`.srfm-block.srfm-slug-${ getBlockMappedSlug }`
						);
					if ( getMappedBlock ) {
						// Check block type.
						if (
							getMappedBlock.classList.contains(
								'srfm-number-block'
							)
						) {
							const getMappedBlockInput =
								getMappedBlock.querySelector(
									'input.srfm-input-common'
								);
							if ( getMappedBlockInput ) {
								getMappedBlockInput.addEventListener(
									'input',
									( event ) => {
										const getMappedBlockInputValue =
											event.target.value;
										// Get format type from the number input's data attribute
										const inputFormatType =
											getMappedBlockInput.getAttribute(
												'format-type'
											) || 'us-style';
										PAYMENT_UTILITY.updatePaymentBlockAmount(
											paymentInput,
											getMappedBlockInputValue,
											inputFormatType
										);
									},
									listenerOpts
								);
								// Get initial format type for the initial value
								const inputFormatType =
									getMappedBlockInput.getAttribute(
										'format-type'
									) || 'us-style';
								PAYMENT_UTILITY.updatePaymentBlockAmount(
									paymentInput,
									getMappedBlockInput.value,
									inputFormatType
								);
							}
						} else if (
							getMappedBlock.classList.contains(
								'srfm-dropdown-block'
							)
						) {
							const hiddenInput = getMappedBlock.querySelector(
								'.srfm-input-dropdown-hidden'
							);
							if ( hiddenInput ) {
								hiddenInput.addEventListener(
									'change',
									() => {
										const amount =
											PAYMENT_UTILITY.getDropdownAmount(
												getMappedBlock,
												hiddenInput
											);
										PAYMENT_UTILITY.updatePaymentBlockAmount(
											paymentInput,
											amount
										);
									},
									listenerOpts
								);
								// Set initial value
								const initialAmount =
									PAYMENT_UTILITY.getDropdownAmount(
										getMappedBlock,
										hiddenInput
									);
								PAYMENT_UTILITY.updatePaymentBlockAmount(
									paymentInput,
									initialAmount
								);
							}
						} else if (
							getMappedBlock.classList.contains(
								'srfm-multi-choice-block'
							)
						) {
							const hiddenInput = getMappedBlock.querySelector(
								'.srfm-input-multi-choice-hidden'
							);
							if ( hiddenInput ) {
								hiddenInput.addEventListener(
									'change',
									() => {
										const amount =
											PAYMENT_UTILITY.getMultiChoiceAmount(
												getMappedBlock,
												hiddenInput
											);
										PAYMENT_UTILITY.updatePaymentBlockAmount(
											paymentInput,
											amount
										);
									},
									listenerOpts
								);
								// Set initial value
								const initialAmount =
									PAYMENT_UTILITY.getMultiChoiceAmount(
										getMappedBlock,
										hiddenInput
									);
								PAYMENT_UTILITY.updatePaymentBlockAmount(
									paymentInput,
									initialAmount
								);
							}
						} else if (
							getMappedBlock.classList.contains(
								'srfm-hidden-block'
							)
						) {
							const hiddenFieldInput =
								getMappedBlock.querySelector(
									'.srfm-hidden-input'
								);
							if ( hiddenFieldInput ) {
								// Hidden inputs don't fire native input/change
								// events when set programmatically, so listen
								// for both — integrators that set the value
								// via JS should dispatch a 'change' event.
								const syncAmount = () => {
									const trimmed =
										hiddenFieldInput.value.trim();
									const rawValue =
										/^\d+(\.\d+)?$/.test( trimmed )
											? parseFloat( trimmed )
											: NaN;
									const amount =
										isNaN( rawValue ) || rawValue < 0
											? 0
											: rawValue;
									PAYMENT_UTILITY.updatePaymentBlockAmount(
										paymentInput,
										amount
									);
								};
								hiddenFieldInput.addEventListener(
									'change',
									syncAmount
								);
								hiddenFieldInput.addEventListener(
									'input',
									syncAmount
								);
								// Set initial value from defaultValue.
								syncAmount();
							}
						}
					}
				}
			}
		}
	},
	getCurrencySymbol: ( currencyCode ) => {
		// Use localized currency data from PHP
		const currenciesData = window.srfmStripe?.currenciesData || {};
		const upperCurrencyCode = currencyCode?.toUpperCase();
		const currencyData = currenciesData[ upperCurrencyCode ];

		// Return symbol from localized data, or fallback to currency code
		return currencyData?.symbol || currencyCode;
	},
	/**
	 * Get currency sign position from settings
	 * @return {string} Currency sign position ('left', 'right', 'left_space', 'right_space')
	 */
	getCurrencySignPosition: () => {
		return window.srfmStripe?.currencySignPosition || 'left';
	},
	/**
	 * Format amount with currency symbol based on position setting
	 * @param {string}        currencySymbol  - The currency symbol (e.g., "$")
	 * @param {string|number} formattedAmount - The formatted amount string
	 * @param {string}        position        - Currency sign position (optional, defaults to setting)
	 * @return {string} Formatted amount with currency symbol in correct position
	 */
	formatAmountWithCurrencyPosition: (
		currencySymbol,
		formattedAmount,
		position = null
	) => {
		const signPosition =
			position || PAYMENT_UTILITY.getCurrencySignPosition();

		switch ( signPosition ) {
			case 'right':
				return `${ formattedAmount }${ currencySymbol }`;
			case 'left_space':
				return `${ currencySymbol } ${ formattedAmount }`;
			case 'right_space':
				return `${ formattedAmount } ${ currencySymbol }`;
			case 'left':
			default:
				return `${ currencySymbol }${ formattedAmount }`;
		}
	},
	/**
	 * Get amount from dropdown block based on selected option values
	 * @param {HTMLElement} dropdownBlock - The dropdown block element
	 * @param {HTMLElement} hiddenInput   - The hidden input containing selected values
	 * @return {number} The total amount from selected options
	 */
	getDropdownAmount: ( dropdownBlock, hiddenInput ) => {
		const selectedValues = [];
		const hiddenInputValue = hiddenInput.value;

		if ( ! hiddenInputValue ) {
			return 0;
		}

		const { extractValue, normalizeDashes } =
			window.srfm?.srfmUtility || {};

		// Extract selected values from hidden input (format: "Option 1 | Option 2")
		const selectedOptions = extractValue
			? extractValue( hiddenInputValue )
			: hiddenInputValue.split( '|' ).map( ( v ) => v.trim() );

		// Get all dropdown options
		const options = dropdownBlock.querySelectorAll(
			'.srfm-dropdown-input option[option-value]'
		);

		selectedOptions.forEach( ( selectedOption ) => {
			options.forEach( ( option ) => {
				const optionText = normalizeDashes( option.innerText?.trim() );
				const selectedOptionText = normalizeDashes(
					selectedOption?.trim()
				);

				if ( optionText === selectedOptionText ) {
					const optionValue = option.getAttribute( 'option-value' );
					// Only add numeric values
					if ( ! isNaN( optionValue ) ) {
						selectedValues.push( parseFloat( optionValue ) );
					} else if ( '' === optionValue ) {
						selectedValues.push( 0 );
					}
				}
			} );
		} );

		// Sum all selected option values
		return selectedValues.length > 0
			? selectedValues.reduce( ( sum, value ) => sum + value, 0 )
			: 0;
	},
	/**
	 * Get amount from multi-choice block based on selected option values
	 * @param {HTMLElement} multiChoiceBlock - The multi-choice block element
	 * @param {HTMLElement} hiddenInput      - The hidden input containing selected values
	 * @return {number} The total amount from selected options
	 */
	getMultiChoiceAmount: ( multiChoiceBlock, hiddenInput ) => {
		const selectedValues = [];
		const hiddenInputValue = hiddenInput.value;

		if ( ! hiddenInputValue ) {
			return 0;
		}

		const { extractValue } = window.srfm?.srfmUtility || {};

		// Extract selected values from hidden input (format: "Option 1 | Option 2")
		const selectedOptions = extractValue( hiddenInputValue );

		// Get all multi-choice options
		const choices = multiChoiceBlock.querySelectorAll(
			'.srfm-multi-choice-single'
		);

		// Normalize whitespace: collapse multiple spaces to one, then trim.
		// This is necessary because browsers collapse consecutive whitespace in
		// innerText, while the stored value (from data-option-text attribute)
		// preserves the original spacing from the block attributes.
		const normalizeStr = ( str ) =>
			typeof str === 'string' ? str.trim().replace( /\s+/g, ' ' ) : '';

		selectedOptions.forEach( ( selectedOption ) => {
			choices.forEach( ( choice ) => {
				const label = choice.querySelector(
					'.srfm-option-container label'
				);
				if (
					normalizeStr( label?.innerText ) ===
					normalizeStr( selectedOption )
				) {
					const input = choice.querySelector(
						'.srfm-input-multi-choice-single'
					);
					const optionValue = input?.getAttribute( 'option-value' );
					// Only add numeric values
					if ( ! isNaN( optionValue ) ) {
						selectedValues.push( parseFloat( optionValue ) );
					} else if ( '' === optionValue ) {
						selectedValues.push( 0 );
					}
				}
			} );
		} );

		// Sum all selected option values
		return selectedValues.length > 0
			? selectedValues.reduce( ( sum, value ) => sum + value, 0 )
			: 0;
	},
	getStripeStrings: ( code, defaultMessage = '' ) => {
		// If no code provided
		if ( ! code || code === null || code === undefined ) {
			// Return default message if provided, otherwise unknown error
			return defaultMessage && defaultMessage.trim() !== ''
				? defaultMessage
				: window.srfmStripe?.strings?.unknown_error ||
						'An unknown error occurred. Please try again or contact the site administrator.';
		}

		// Check if code exists in localized strings
		const localizedMessage = window.srfmStripe?.strings?.[ code ];

		if ( localizedMessage ) {
			// Code found in localized strings, return it
			return localizedMessage;
		}

		// Code not found in localized strings
		// Return default message if provided, otherwise unknown error
		return defaultMessage && defaultMessage.trim() !== ''
			? defaultMessage
			: window.srfmStripe?.strings?.unknown_error ||
					'An unknown error occurred. Please try again or contact the site administrator.';
	},
};

window.srfmPaymentUtility = PAYMENT_UTILITY;

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
			PAYMENT_UTILITY.init( form );
			// BOTH MODE: wire the one-time / subscription radio chooser, if present.
			initPaymentTypeChoosers( form );
		}
	}
} );

// BOTH MODE: start — payment-type chooser (one-time vs subscription) wiring.

/**
 * Wire the payment-type chooser radios for any payment blocks in this form
 * that were saved with paymentType === 'both'.
 *
 * @param {HTMLFormElement} form - The form element.
 */
function initPaymentTypeChoosers( form ) {
	const paymentBlocks = form.querySelectorAll(
		'.srfm-block.srfm-payment-block'
	);

	paymentBlocks.forEach( ( paymentBlock ) => {
		const paymentInput = paymentBlock.querySelector(
			'input.srfm-payment-input'
		);
		if ( ! paymentInput ) {
			return;
		}

		// Only wire when the admin chose "both" mode.
		if (
			paymentInput.getAttribute( 'data-original-payment-type' ) !==
			'both'
		) {
			return;
		}

		const radios = paymentBlock.querySelectorAll(
			'.srfm-payment-type-choice-radio'
		);
		if ( radios.length === 0 ) {
			return;
		}

		radios.forEach( ( radio ) => {
			radio.addEventListener( 'change', ( event ) => {
				if ( ! event.target.checked ) {
					return;
				}

				// BOTH MODE: block type-flip while a payment is in flight.
				// reinitForBlock() unmounts the Stripe Element and deletes the
				// cached intent the pending confirmPayment is still using —
				// flipping mid-submit produces double charges or wrong-type
				// completions. Revert the radio to whatever data-payment-type
				// currently is so the UI does not lie about the user's choice.
				const blockId =
					paymentBlock.getAttribute( 'data-block-id' );
				const compositeKey = getPaymentKey( form, blockId );
				const reinitInFlight =
					StripePayment.reinitInProgress.has( compositeKey );

				if (
					form.dataset.srfmPaymentInFlight === 'true' ||
					reinitInFlight
				) {
					const activeType =
						paymentInput.getAttribute( 'data-payment-type' ) ||
						'one-time';
					radios.forEach( ( r ) => {
						r.checked = r.value === activeType;
					} );
					return;
				}

				switchActivePaymentType(
					form,
					paymentBlock,
					paymentInput,
					event.target.value
				);
			} );
		} );
	} );
}

/**
 * Apply a new active payment type to the block. Updates DOM visibility, syncs
 * the live data-* attributes that the rest of the JS reads, re-initializes
 * the Stripe Element in the new mode, and dispatches a gateway-agnostic event
 * so other gateways (e.g. PayPal in sureforms-pro) can react.
 *
 * @param {HTMLFormElement}  form         - The form element.
 * @param {HTMLElement}      paymentBlock - The payment block wrapper.
 * @param {HTMLInputElement} paymentInput - The hidden payment input.
 * @param {string}           newType      - 'one-time' or 'subscription'.
 */
function switchActivePaymentType( form, paymentBlock, paymentInput, newType ) {
	const safeType = newType === 'subscription' ? 'subscription' : 'one-time';

	// 1. Toggle visible amount block.
	const amountBlocks = paymentBlock.querySelectorAll(
		'.srfm-payment-amount-block'
	);
	amountBlocks.forEach( ( el ) => {
		if ( el.getAttribute( 'data-payment-type' ) === safeType ) {
			el.removeAttribute( 'hidden' );
		} else {
			el.setAttribute( 'hidden', '' );
		}
	} );

	// 2. Sync the live data attributes from the per-type configuration.
	const prefix = safeType === 'subscription' ? 'subscription' : 'one-time';
	const amountType =
		paymentInput.getAttribute( `data-${ prefix }-amount-type` ) || 'fixed';
	const fixedAmount =
		paymentInput.getAttribute( `data-${ prefix }-fixed-amount` ) || '0';
	const minimumAmount =
		paymentInput.getAttribute( `data-${ prefix }-minimum-amount` ) || '0';
	const variableField =
		paymentInput.getAttribute( `data-${ prefix }-variable-amount-field` ) ||
		'';

	paymentInput.setAttribute( 'data-payment-type', safeType );
	paymentInput.setAttribute( 'data-amount-type', amountType );
	paymentInput.setAttribute( 'data-fixed-amount', fixedAmount );

	if ( parseFloat( minimumAmount ) > 0 ) {
		paymentInput.setAttribute( 'data-minimum-amount', minimumAmount );
	} else {
		paymentInput.removeAttribute( 'data-minimum-amount' );
	}

	if ( amountType === 'variable' && variableField ) {
		paymentInput.setAttribute( 'data-variable-amount-field', variableField );
	} else {
		paymentInput.removeAttribute( 'data-variable-amount-field' );
		// Also clear any cached current-amount so stale values don't leak across choices.
		paymentInput.removeAttribute( 'data-current-amount' );
	}

	// 3. Re-initialize Stripe Element in the new mode.
	if ( typeof StripePayment.reinitForBlock === 'function' ) {
		StripePayment.reinitForBlock( form, paymentBlock );
	}

	// 3b. BOTH MODE: re-wire variable-amount listeners and trigger an immediate
	// display update for the new type. listenAmountChanges() queries
	// [data-variable-amount-field] which was just updated above, so it will
	// find and wire the correct field. The immediate update populates the
	// amount span which is empty at PHP render time for variable types.
	if ( amountType === 'variable' && variableField ) {
		PAYMENT_UTILITY.listenAmountChanges();

		// Read current value from the mapped field and update the display now.
		const mappedBlock = form.querySelector(
			`.srfm-block.srfm-slug-${ variableField }`
		);
		if ( mappedBlock ) {
			const numberInput = mappedBlock.querySelector(
				'input.srfm-input-common'
			);
			const dropdownInput = mappedBlock.querySelector(
				'.srfm-input-dropdown-hidden'
			);
			const multiChoiceInput = mappedBlock.querySelector(
				'.srfm-input-multi-choice-hidden'
			);
			const hiddenFieldInput =
				mappedBlock.querySelector( '.srfm-hidden-input' );

			let currentValue = 0;
			if ( numberInput ) {
				currentValue = numberInput.value || 0;
			} else if ( dropdownInput ) {
				currentValue =
					PAYMENT_UTILITY.getDropdownAmount(
						mappedBlock,
						dropdownInput
					) || 0;
			} else if ( multiChoiceInput ) {
				currentValue =
					PAYMENT_UTILITY.getMultiChoiceAmount(
						mappedBlock,
						multiChoiceInput
					) || 0;
			} else if ( hiddenFieldInput ) {
				// Mirror syncAmount() in listenAmountChanges — accept only numeric
				// strings, clamp negatives to 0. Without this branch, currentValue
				// stays 0 and overwrites the value syncAmount() just wrote during
				// listenAmountChanges() above, breaking initial-amount pickup on
				// every one-time/subscription flip.
				const trimmed = hiddenFieldInput.value.trim();
				const rawValue = /^\d+(\.\d+)?$/.test( trimmed )
					? parseFloat( trimmed )
					: NaN;
				currentValue =
					isNaN( rawValue ) || rawValue < 0 ? 0 : rawValue;
			}

			PAYMENT_UTILITY.updatePaymentBlockAmount(
				paymentInput,
				currentValue
			);
		} else {
			// No mapped field found — show placeholder.
			PAYMENT_UTILITY.updatePaymentBlockAmount( paymentInput, 0 );
		}
	}

	// 4. Dispatch gateway-agnostic event so other gateways (PayPal etc.) can react.
	//
	// Event contract — `srfm_payment_type_changed` (document-level, bubbles):
	//   detail: {
	//     blockId:      string            — Original payment block id (no instance prefix).
	//     form:         HTMLFormElement   — Form element the payment block belongs to.
	//     paymentType:  'one-time' | 'subscription' — The newly active type.
	//     paymentInput: HTMLInputElement  — The hidden .srfm-payment-input element.
	//   }
	// Companion event `srfm_payment_method_changed` (dispatched in payment-manager.js)
	// uses a deliberately narrower shape — { blockId, paymentMethod, form } — because
	// gateway listeners only need the method id; if you need paymentInput on that
	// path, query it from the form rather than expanding the schema (avoids drift).
	const blockId = paymentBlock.getAttribute( 'data-block-id' );
	document.dispatchEvent(
		new CustomEvent( 'srfm_payment_type_changed', {
			detail: {
				blockId,
				form,
				paymentType: safeType,
				paymentInput,
			},
			bubbles: true,
		} )
	);
}
// BOTH MODE: end
