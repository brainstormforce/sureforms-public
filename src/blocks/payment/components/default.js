/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Payment Component
 *
 * @param {Object} props Component props
 * @return {JSX.Element} Payment component
 */
export const PaymentComponent = ( props ) => {
	const { attributes } = props;
	const {
		label = 'Payment Details',
		help = '',
		required = true,
		paymentType = 'one-time',
		subscriptionPlan = {},
	} = attributes;

	// Get global stripe settings
	const paymentSettings = window?.srfm_admin?.payments || {};
	const stripeConnected = paymentSettings.stripe_connected || false;
	const stripeConnectUrl = paymentSettings.stripe_connect_url || '';

	// Handle connect to Stripe
	const handleConnectStripe = () => {
		if ( stripeConnectUrl ) {
			window.location.href = stripeConnectUrl;
		}
	};

	// Check if subscription requires name and email fields
	const isSubscription = paymentType === 'subscription';
	const customerName = subscriptionPlan?.customer_name || '';
	const customerEmail = subscriptionPlan?.customer_email || '';
	const missingNameField = isSubscription && ! customerName;
	const missingEmailField = isSubscription && ! customerEmail;
	const hasSubscriptionError = missingNameField || missingEmailField;

	let stripeConnectedComponent = null;

	// If subscription and missing name/email fields, show validation error.
	if ( stripeConnected && hasSubscriptionError ) {
		stripeConnectedComponent = (
			<p className="srfm-stripe-payment-error-text">
				{ __(
					'Name and Email fields are required to collect payments for subscriptions. Please map these fields in the block settings.',
					'sureforms'
				) }
			</p>
		);
	}

	// Add validation for payment items.
	const paymentItems = attributes.paymentItems || [];
	const hasPaymentItems = paymentItems.length > 0;
	if ( ! hasPaymentItems ) {
		stripeConnectedComponent = (
			<p className="srfm-stripe-payment-error-text">
				{ __(
					'Payment items are required to collect payments for this form. Please map these items in the block settings.',
					'sureforms'
				) }
			</p>
		);
	}

	// If stripe is not connected, show connect message.
	if ( ! stripeConnected ) {
		stripeConnectedComponent = (
			<>
				<p className="srfm-stripe-payment-error-text">
					{ __(
						'You need to connect your Stripe account to collect payments from this form.',
						'sureforms'
					) }
				</p>
				<button
					type="button"
					className="srfm-stripe-connect-button"
					onClick={ handleConnectStripe }
				>
					{ __( 'Connect to Stripe', 'sureforms' ) }
				</button>
			</>
		);
	}

	return (
		<div className="srfm-block-single srfm-payment-block">
			{ label && (
				<label className="srfm-block-label">
					{ label }
					{ required && <span className="srfm-required">*</span> }
				</label>
			) }

			<div className="srfm-payment-field-wrapper">
				<p>
					{ __(
						'This is a placeholder for the Stripe Payment block. The actual payment fields will only appear when you preview or publish the form.',
						'sureforms'
					) }
				</p>
				{ stripeConnectedComponent }
			</div>

			{ help && <div className="srfm-help-text">{ help }</div> }
		</div>
	);
};
