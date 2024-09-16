import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import ICONS from './icons.js';

const ErrorPopup = () => {
	return (
		<>
			<div className="srfm-popup-overlay" />
			<div className="srfm-err-popup-container">
				<div className="srfm-popup-header">
					<span className="srfm-popup-icon">{ ICONS.warning }</span>
					<span className="srfm-popup-title">
						{ __( 'Error Creating Form', 'sureforms' ) }
					</span>
				</div>
				<span className="srfm-err-popup-description">
					{ __(
						'There was an error mapping the AI response to Fields. Please try again.',
						'sureforms'
					) }
				</span>

				<Button
					className="srfm-err-popup-try-again-btn"
					onClick={ () => {
						window.location.reload();
					} }
				>
					{ __( 'Try Again!', 'sureforms' ) }
				</Button>
			</div>
		</>
	);
};

export default ErrorPopup;
