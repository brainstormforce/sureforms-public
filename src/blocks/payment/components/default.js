/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';
import { applyFilters } from '@wordpress/hooks';
import HelpText from '@Components/misc/HelpText';
import { decodeHtmlEntities } from '@Blocks/util';
import { Fragment } from '@wordpress/element';

/**
 * Payment Component
 *
 * @param {Object} props Component props
 * @return {JSX.Element} Payment component
 */
export const PaymentComponent = ( props ) => {
	const { attributes, setAttributes, availableFormFields } = props;
	const { label, help = '', block_id } = attributes;

	// Get global payment settings
	const paymentSettings = window?.srfm_admin?.payments || {};
	const stripeConnected = paymentSettings.stripe_connected || false;
	const stripeConnectUrl = paymentSettings.stripe_connect_url || '';

	/**
	 * Filter: srfm_payment_gateway_configured
	 *
	 * Check if any payment gateway is configured (Stripe, PayPal, etc.)
	 * This filter allows payment gateways to extend the configuration check.
	 *
	 * @param {boolean} isConfigured    - Whether any payment gateway is configured
	 * @param {Object}  paymentSettings - Payment settings from window.srfm_admin.payments
	 *
	 * @return {boolean} True if any payment gateway is configured, false otherwise
	 */
	const isPaymentConfigured = applyFilters(
		'srfm_payment_gateway_configured',
		stripeConnected,
		paymentSettings
	);

	// Handle connect to Stripe
	const handleConnectStripe = () => {
		if ( stripeConnectUrl ) {
			window.open( stripeConnectUrl, '_blank', 'noopener,noreferrer' );
		}
	};

	// Verify if the field is valid (check name, email, and variableAmount fields)
	const verifyFieldIsValid = ( fieldSlug, fieldType = 'default' ) => {
		if ( '' === fieldSlug ) {
			return false;
		}

		// For variable amount fields, check in variableAmountFields array
		if ( fieldType === 'variableAmount' ) {
			const variableAmountFieldExists =
				availableFormFields?.variableAmountFields?.some( ( field ) => {
					return field.slug === fieldSlug;
				} );
			return variableAmountFieldExists;
		}

		// Check in both nameFields and emailsFields arrays
		const nameFieldExists = availableFormFields?.nameFields?.some(
			( field ) => {
				return field.slug === fieldSlug;
			}
		);

		const emailFieldExists = availableFormFields?.emailsFields?.some(
			( field ) => {
				return field.slug === fieldSlug;
			}
		);

		return nameFieldExists || emailFieldExists;
	};

	// Check payment type (subscription or one-time)
	const paymentType = attributes.paymentType || 'one-time';
	const isSubscription = paymentType === 'subscription';

	// Check amount type
	const amountType = attributes.amountType || 'fixed';
	const isVariableAmount = amountType === 'variable';

	// Check if payment requires name and email fields
	const customerName = attributes.customerNameField || '';
	const customerEmail = attributes.customerEmailField || '';
	const variableAmountField = attributes.variableAmountField || '';

	// Name field validation: required only for subscriptions
	const missingNameField =
		isSubscription &&
		( ! customerName || ! verifyFieldIsValid( customerName ) );

	// Email field validation: required for all payment types
	const missingEmailField =
		! customerEmail || ! verifyFieldIsValid( customerEmail );

	// Variable amount field validation: required when amount type is variable
	const missingVariableAmountField =
		isVariableAmount &&
		( ! variableAmountField ||
			! verifyFieldIsValid( variableAmountField, 'variableAmount' ) );

	const hasCustomerFieldsError =
		missingNameField || missingEmailField || missingVariableAmountField;

	let paymentConfigurationComponent = [];

	// If missing required fields, show validation error
	if ( isPaymentConfigured && hasCustomerFieldsError ) {
		let errorMessage = '';

		// Build error message based on missing fields
		const missingFields = [];

		if ( missingNameField ) {
			missingFields.push( 'Name' );
		}
		if ( missingEmailField ) {
			missingFields.push( 'Email' );
		}
		if ( missingVariableAmountField ) {
			missingFields.push( 'Variable Amount' );
		}

		if ( missingFields.length > 0 ) {
			const fieldsList = missingFields.join( ' and ' );
			if ( isSubscription && missingFields.length > 1 ) {
				errorMessage = sprintf(
					/* translators: %1$s: a comma-separated list of missing field names */
					__(
						'%1$s fields are required. Please configure these fields in the block settings.',
						'sureforms'
					),
					fieldsList
				);
			} else {
				errorMessage = sprintf(
					/* translators: %1$s: the missing field name */
					__(
						'%1$s field is required. Please configure this field in the block settings.',
						'sureforms'
					),
					fieldsList
				);
			}
		}

		paymentConfigurationComponent = [
			<>
				<p className="srfm-stripe-payment-error-text">
					{ errorMessage }
				</p>
			</>,
		];
	}

	// If no payment gateway is configured, show connect message.
	if ( ! isPaymentConfigured ) {
		paymentConfigurationComponent = [
			<>
				<p className="srfm-stripe-payment-error-text">
					{ __(
						'You need to configure a payment account to collect payments from this form. Please configure your payment provider to proceed.',
						'sureforms'
					) }
				</p>
				<button
					type="button"
					className="srfm-stripe-connect-button"
					onClick={ handleConnectStripe }
				>
					{ __( 'Configure Payment Account', 'sureforms' ) }
				</button>
			</>,
		];
	}

	const filteredPaymentWarning = applyFilters(
		'srfm.payment.warning',
		paymentConfigurationComponent,
		{
			props,
		}
	);

	if ( filteredPaymentWarning.length > 0 ) {
		paymentConfigurationComponent = filteredPaymentWarning;
	}

	return (
		<div className="srfm-block-single srfm-payment-block">
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => {
					setAttributes( { label: decodeHtmlEntities( value ) } );
				} }
				className={ `srfm-block-label` }
				multiline={ false }
				allowedFormats={ [] }
			/>
			<HelpText
				help={ help }
				setAttributes={ setAttributes }
				block_id={ block_id }
			/>
			<div className="srfm-payment-field-wrapper">
				<p
					style={ {
						marginTop: '12px',
						fontSize: '13px',
						color: '#757575',
					} }
				>
					{ __(
						'This is a placeholder for the Payment block. The actual payment fields for your configured payment provider(s) will only appear when you preview or publish the form.',
						'sureforms'
					) }
				</p>
				{ paymentConfigurationComponent.map( ( component, index ) => {
					return <Fragment key={ index }>{ component }</Fragment>;
				} ) }
			</div>
		</div>
	);
};
