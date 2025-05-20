import { __ } from '@wordpress/i18n';
import { addQueryParam } from '@Utils/Helpers';
import { Button } from '@bsf/force-ui';

// Renders a button that links to the upgrade page for SureForms Pro.
const UpgradeToProButton = ( props ) => {
	const handleClick = () => {
		const url = srfm_admin?.sureforms_pricing_page || 'https://sureforms.com/pricing/';
		window.open( addQueryParam( url, props?.location || 'settings_page' ) );
	};
	return (
		<Button onClick={ handleClick } { ...props }>
			{ __( 'Upgrade', 'sureforms' ) }
		</Button>
	);
};

export default UpgradeToProButton;
