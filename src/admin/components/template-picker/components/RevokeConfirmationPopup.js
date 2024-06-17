import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import ICONS from './icons';
import Header from './Header.js';

const RevokeConfirmationPopup = ( { setShowRevokeConfirmation } ) => {
	return (
		<>
			<Header />
			<div className="srfm-popup-overlay" />
			<div className="srfm-revoke-confirmation-container">
				<div className="srfm-popup-header">
					<span className="srfm-popup-icon">{ ICONS.warning }</span>
					<span className="srfm-popup-title">
						{ __( 'Disconnect Account', 'sureforms' ) }
					</span>
				</div>
				<span className="srfm-revoke-confirmation-description">
					{ __(
						'Are you sure you want to disconnect? You will need to reconnect to use AI features again.',
						'sureforms'
					) }
				</span>
				<div className="srfm-revoke-confirmation-btn-container">
					<Button
						className="srfm-revoke-confirmation-revoke-btn"
						onClick={ () => {
							window.location.assign(
								srfm_admin.zip_ai_auth_revoke_url
							);
						} }
					>
						{ __( 'Disconnect', 'sureforms' ) }
					</Button>
					<Button
						className="srfm-revoke-confirmation-cancel-btn"
						onClick={ () => {
							setShowRevokeConfirmation( false );
						} }
					>
						{ __( 'Cancel', 'sureforms' ) }
					</Button>
				</div>
			</div>
		</>
	);
};

export default RevokeConfirmationPopup;
