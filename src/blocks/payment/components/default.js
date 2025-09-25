/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { MdOutlinePayment } from 'react-icons/md';

/**
 * Payment Component
 *
 * @param {Object} props Component props
 * @return {JSX.Element} Payment component
 */
export const PaymentComponent = ( props ) => {
	const { attributes } = props;
	const {
		amount = 10,
		currency = 'USD',
		description = 'Payment',
		label = 'Payment Details',
		help = '',
		required = true,
	} = attributes;

	// Format currency for display
	const formatCurrency = ( amt, curr ) => {
		const currencySymbols = {
			USD: '$',
			EUR: '€',
			GBP: '£',
			JPY: '¥',
			CAD: 'C$',
			AUD: 'A$',
			CHF: 'CHF',
			CNY: '¥',
			SEK: 'kr',
			NZD: 'NZ$',
		};

		const symbol = currencySymbols[ curr ] || curr + ' ';

		if ( [ 'JPY', 'KRW' ].includes( curr ) ) {
			return symbol + new Intl.NumberFormat().format( amt );
		}

		return (
			symbol +
			new Intl.NumberFormat().format( amt, {
				minimumFractionDigits: 2,
				maximumFractionDigits: 2,
			} )
		);
	};

	return (
		<div className="srfm-block-single srfm-payment-block">
			{ label && (
				<label className="srfm-block-label">
					{ label }
					{ required && <span className="srfm-required">*</span> }
				</label>
			) }

			<div className="srfm-payment-field-wrapper">
				{ /* Payment Amount Display */ }
				<div className="srfm-payment-amount">
					<div className="srfm-payment-amount-display">
						<MdOutlinePayment
							className="srfm-payment-icon"
							size={ 16 }
						/>
						<span className="srfm-payment-label">
							{ description }:
						</span>
						<span className="srfm-payment-value">
							{ formatCurrency( amount, currency ) }
						</span>
					</div>
				</div>

				{ /* Stripe Elements Placeholder */ }
				<div className="srfm-stripe-elements-preview">
					<div className="srfm-stripe-card-preview">
						<div className="srfm-stripe-field-preview">
							<span className="srfm-stripe-placeholder">
								{ __( 'Card number', 'sureforms' ) }
							</span>
						</div>
						<div className="srfm-stripe-field-row">
							<div className="srfm-stripe-field-preview srfm-stripe-field-small">
								<span className="srfm-stripe-placeholder">
									{ __( 'MM / YY', 'sureforms' ) }
								</span>
							</div>
							<div className="srfm-stripe-field-preview srfm-stripe-field-small">
								<span className="srfm-stripe-placeholder">
									{ __( 'CVC', 'sureforms' ) }
								</span>
							</div>
						</div>
					</div>
					<div className="srfm-stripe-preview-notice">
						<small>
							{ __(
								'Preview mode - Stripe Elements will appear here on the frontend',
								'sureforms'
							) }
						</small>
					</div>
				</div>
			</div>

			{ help && <div className="srfm-help-text">{ help }</div> }
		</div>
	);
};
