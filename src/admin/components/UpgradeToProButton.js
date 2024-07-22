import { __ } from '@wordpress/i18n';

// Renders a button that links to the upgrade page for SureForms Pro.
const UpgradeToProButton = ( props ) => {
	const handleClick = () => {
		const url = srfm_admin?.sureforms_pricing_page || 'https://sureforms.com/pricing/';
		window.open( url );
	};
	return (
		<button onClick={ handleClick } { ...props }>
			{ __( 'Upgrade to Pro', 'sureforms' ) }
		</button>
	);
};

export default UpgradeToProButton;
