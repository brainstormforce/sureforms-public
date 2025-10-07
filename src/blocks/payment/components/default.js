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
				<p>
					{ __(
						'This is a placeholder for the Stripe Payment block. The actual payment fields will only appear when you preview or publish the form.',
						'sureforms'
					) }
				</p>
			</div>

			{ help && <div className="srfm-help-text">{ help }</div> }
		</div>
	);
};
