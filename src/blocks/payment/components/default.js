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
	const {
		label = 'Payment Details',
		help = '',
		block_id,
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

	// Check if payment requires name and email fields (required for BOTH payment types)
	const customerName = attributes.customerNameField || '';
	const customerEmail = attributes.customerEmailField || '';
	const missingNameField =
		! customerName || ! verifyFieldIsValid( customerName );
	const missingEmailField =
		! customerEmail || ! verifyFieldIsValid( customerEmail );
	const hasCustomerFieldsError = missingNameField || missingEmailField;

	let stripeConnectedComponent = null;

	// If missing name/email fields, show validation error (for both one-time and subscription payments)
	if ( stripeConnected && hasCustomerFieldsError ) {
		stripeConnectedComponent = (
			<p className="srfm-stripe-payment-error-text">
				{ __(
					'Name and Email fields are required to collect payments. Please map these fields in the block settings. Also, make sure the fields are mapped correctly.',
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
