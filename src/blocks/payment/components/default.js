/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';
import HelpText from '@Components/misc/HelpText';
import { decodeHtmlEntities } from '@Blocks/util';

/**
 * Payment Component
 *
 * @param {Object} props Component props
 * @return {JSX.Element} Payment component
 */
export const PaymentComponent = ( props ) => {
	const { attributes, setAttributes, availableFormFields } = props;
	const { label = 'Payment Details', help = '', block_id } = attributes;

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

	// Verify if the field is valid (check both name and email fields)
	const verifyFieldIsValid = ( fieldSlug ) => {
		if ( '' === fieldSlug ) {
			return false;
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

	// Check if payment requires name and email fields
	const customerName = attributes.customerNameField || '';
	const customerEmail = attributes.customerEmailField || '';

	// Name field validation: required only for subscriptions
	const missingNameField =
		isSubscription &&
		( ! customerName || ! verifyFieldIsValid( customerName ) );

	// Email field validation: required for all payment types
	const missingEmailField =
		! customerEmail || ! verifyFieldIsValid( customerEmail );

	const hasCustomerFieldsError = missingNameField || missingEmailField;

	let stripeConnectedComponent = null;

	// If missing required fields, show validation error
	if ( stripeConnected && hasCustomerFieldsError ) {
		let errorMessage = '';

		if ( isSubscription ) {
			// For subscriptions: both name and email are required
			if ( missingNameField && missingEmailField ) {
				errorMessage = __(
					'Name and Email fields are required for subscriptions. Please map these fields in the block settings. Also, make sure the fields are mapped correctly.',
					'sureforms'
				);
			} else if ( missingNameField ) {
				errorMessage = __(
					'Name field is required for subscriptions. Please map this field in the block settings and make sure it is mapped correctly.',
					'sureforms'
				);
			} else if ( missingEmailField ) {
				errorMessage = __(
					'Email field is required for subscriptions. Please map this field in the block settings and make sure it is mapped correctly.',
					'sureforms'
				);
			}
		} else {
			// For one-time payments: only email is required
			errorMessage = __(
				'Email field is required to collect payments. Please map this field in the block settings and make sure it is mapped correctly.',
				'sureforms'
			);
		}

		stripeConnectedComponent = (
			<p className="srfm-stripe-payment-error-text">{ errorMessage }</p>
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
	// const isRequired = required ? ' srfm-required' : '';

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
						'This is a placeholder for the Stripe Payment block. The actual payment fields will only appear when you preview or publish the form.',
						'sureforms'
					) }
				</p>
				{ stripeConnectedComponent }
			</div>
		</div>
	);
};
