import GeneralSettings from './components/GeneralSettings';
import PaymentMethods from './components/PaymentMethods';

const Payments = ( props ) => {
	const {
		loading,
		paymentsSettings,
		updateGlobalSettings,
		setPaymentsSettings,
		subpage,
	} = props;

	// Handle settings change with auto-save
	const handleSettingChange = ( key, value ) => {
		// Update local state immediately for UI responsiveness
		setPaymentsSettings( {
			...paymentsSettings,
			[ key ]: value,
		} );

		// Trigger auto-save via global settings with debouncing
		if ( updateGlobalSettings ) {
			updateGlobalSettings( key, value, 'payments-settings' );
		}
	};

	// Render based on subpage
	// Default to 'general' if no subpage is specified
	const currentSubpage = subpage || 'general';

	return (
		<div className="srfm-payment-wrapper">
			{ currentSubpage === 'general' && (
				<GeneralSettings
					paymentsSettings={ paymentsSettings }
					onSettingChange={ handleSettingChange }
					loading={ loading }
				/>
			) }

			{ currentSubpage === 'payment-methods' && (
				<PaymentMethods
					paymentsSettings={ paymentsSettings }
					setPaymentsSettings={ setPaymentsSettings }
					updateGlobalSettings={ updateGlobalSettings }
					loading={ loading }
				/>
			) }
		</div>
	);
};

export default Payments;
