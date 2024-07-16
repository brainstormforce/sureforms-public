import { __ } from '@wordpress/i18n';

const UpgradeToProButton = ( props ) => {
	const handleClick = () => {
		// eslint-disable-next-line no-undef
		const url = srfm_admin?.sureforms_pricing_page;
		window.open( url );
	};
	return (
		<button onClick={ handleClick } { ...props }>
			{ __( 'Upgrade to Pro', 'sureforms' ) }
		</button>
	);
};

export default UpgradeToProButton;
