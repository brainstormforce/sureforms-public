import { __ } from '@wordpress/i18n';
import { Label, RadioButton, Select } from '@bsf/force-ui';
import ContentSection from '@Admin/settings/components/ContentSection';
import { currencies } from '../../components/utils';

/**
 * General payment settings component
 * Contains settings that apply to all payment gateways (currency, payment mode)
  /**
 * @param {Object} props - General settings props object
 */
const GeneralSettings = ( props ) => {
	const { paymentsSettings, onSettingChange, loading } = props;
	// Get current currency label
	const getCurrentCurrencyLabel = () => {
		const currency = currencies.find(
			( c ) => c.value === paymentsSettings.currency
		);
		return currency ? currency.label : currencies[ 0 ].label;
	};

	const currencySelection = (
		<div className="flex flex-col gap-2">
			<Label size="sm" variant="neutral">
				{ __( 'Currency', 'sureforms' ) }
			</Label>
			<Select
				value={ paymentsSettings.currency }
				onChange={ ( value ) => onSettingChange( 'currency', value ) }
			>
				<Select.Button
					type="button"
					label={ __( 'Select Currency', 'sureforms' ) }
					size="md"
				>
					{ getCurrentCurrencyLabel() }
				</Select.Button>
				<Select.Portal id="srfm-settings-container">
					<Select.Options>
						{ currencies.map( ( currency ) => (
							<Select.Option
								key={ currency.value }
								value={ currency.value }
							>
								{ currency.label }
							</Select.Option>
						) ) }
					</Select.Options>
				</Select.Portal>
			</Select>
			<p className="text-sm text-field-helper">
				{ __(
					'Select the default currency for payment forms.',
					'sureforms'
				) }
			</p>
		</div>
	);

	const paymentMode = (
		<div className="flex flex-col gap-2">
			<Label size="sm" variant="neutral">
				{ __( 'Payment Mode', 'sureforms' ) }
			</Label>
			<RadioButton.Group
				columns={ 2 }
				value={ paymentsSettings.payment_mode }
				onChange={ ( value ) =>
					onSettingChange( 'payment_mode', value )
				}
				size="sm"
				className="w-fit"
			>
				<RadioButton.Button
					borderOn
					label={ {
						heading: __( 'Live Mode', 'sureforms' ),
					} }
					value="live"
				/>
				<RadioButton.Button
					borderOn
					label={ {
						heading: __( 'Test Mode', 'sureforms' ),
					} }
					value="test"
				/>
			</RadioButton.Group>
			<p className="text-sm text-field-helper">
				{ __(
					'Test mode allows you to process payments without real charges. Switch to Live mode for actual transactions.',
					'sureforms'
				) }
			</p>
		</div>
	);

	const content = (
		<div className="flex flex-col gap-6 px-2">
			{ currencySelection }
			{ paymentMode }
		</div>
	);

	return (
		<ContentSection
			loading={ loading }
			title={ __( 'General Payment Settings', 'sureforms' ) }
			content={ content }
			description={ __(
				'These settings apply to all payment gateways.',
				'sureforms'
			) }
		/>
	);
};

export default GeneralSettings;
