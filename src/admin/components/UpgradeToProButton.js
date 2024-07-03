import { __ } from '@wordpress/i18n';

const UpgradeToProButton = ( { className } ) => {
	return (
		<button onClick={
			() => {
				window.open( 'https://sureforms.com/pricing' );
			}
		} className={ className }>
			{ __( 'Upgrade to Pro', 'sureforms' ) }
		</button>
	);
};

export default UpgradeToProButton;
