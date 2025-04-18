import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import ICONS from './icons';
import Header from './Header.js';

export const AuthErrorPopup = ( { initiateAuth } ) => {
	return (
		<>
			<Header />
			<div className="srfm-popup-overlay" />
			<div className="srfm-auth-err-confirmation-container">
				<div className="srfm-popup-header">
					<span className="srfm-popup-icon">{ ICONS.warning }</span>
					<span className="srfm-popup-title">
						{ __( 'Authentication Failed', 'sureforms' ) }
					</span>
				</div>
				<span className="srfm-auth-err-confirmation-description">
					{ __(
						'Please check your username and password for the account, and try to reconnect again. Need help? ',
						'sureforms'
					) }
					<a href="https://sureforms.com/contact/" target="_blank"
						style={ {
							color: '#D54407',
							textDecoration: 'underline',
						} } rel="noreferrer"
					>{ __( 'Contact Support', 'sureforms' ) }</a>
				</span>
				<div className="srfm-auth-err-confirmation-btn-container">
					<Button
						className="srfm-auth-err-confirmation-auth-err-btn"
						onClick={
							initiateAuth
						}
					>
						{ __( 'Click Here to Retry', 'sureforms' ) }
					</Button>
					<Button
						className="srfm-auth-err-confirmation-cancel-btn"
						onClick={ () => {
							window.location.href = '/wp-admin/admin.php?page=sureforms_menu';
						} }
					>
						{ __( 'Exit to Dashboard', 'sureforms' ) }
					</Button>
				</div>
			</div>
		</>
	);
};

