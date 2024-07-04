import { __ } from '@wordpress/i18n';

const UpgradeToProButton = ( props ) => {
	return (
		<a href="https://sureforms.com/pricing" target="_blank" rel="noreferrer">
			<button { ...props }>
				{ __( 'Upgrade to Pro', 'sureforms' ) }
			</button>
		</a>
	);
};

export default UpgradeToProButton;
