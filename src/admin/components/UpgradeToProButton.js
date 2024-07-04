import { __ } from '@wordpress/i18n';

const UpgradeToProButton = ( props ) => {
	const handleClick = () => {
		const url = 'https://sureforms.com/pricing';
		window.open( url );
	};
	return (
		<button onClick={ handleClick } { ...props }>
			{ __( 'Upgrade to Pro', 'sureforms' ) }
		</button>
	);
};

export default UpgradeToProButton;
