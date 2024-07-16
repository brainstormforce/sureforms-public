import { __ } from '@wordpress/i18n';

const UpgradeToProButton = ( props ) => {
	const handleClick = () => {
		const url = srfm_admin?.sureforms_website;
		window.open( url );
	};
	return (
		<button onClick={ handleClick } { ...props }>
			{ __( 'Upgrade to Pro', 'sureforms' ) }
		</button>
	);
};

export default UpgradeToProButton;
