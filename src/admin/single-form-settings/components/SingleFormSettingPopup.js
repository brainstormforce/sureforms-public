import EmailNotification from './email-settings/EmailNotification';
import Compliance from './email-settings/Compliance';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';

const SingleFormSettingsPopup = ( props ) => {
	const { sureformsKeys } = props;
	const emailIcon = parse( svgIcons.email );
	const emailNotificationData = sureformsKeys._srfm_email_notification || [];
	const complianceData = sureformsKeys._srfm_compliance || [];
	const [ selectedTab, setSelectedTab ] = useState( 'email_notification' );
	return (
		<div className="srfm-setting-modal-container">
			<div className="srfm-modal-sidebar">
				<div
					className={ `srfm-modal-tab ${
						selectedTab === 'email_notification'
							? 'srfm-modal-active-tab'
							: ''
					}` }
					onClick={ () => setSelectedTab( 'email_notification' ) }
				>
					<span className="srfm-modal-tab-icon">{ emailIcon }</span>
					<span className="srfm-modal-tab-text">
						<p>{ __( 'Email Notifications', 'sureforms' ) }</p>
					</span>
				</div>
				<div
					className={ `srfm-modal-tab ${
						selectedTab === 'compliance_settings'
							? 'srfm-modal-active-tab'
							: ''
					}` }
					onClick={ () => setSelectedTab( 'compliance_settings' ) }
				>
					<span className="srfm-modal-tab-icon">{ emailIcon }</span>
					<span className="srfm-modal-tab-text">
						<p>{ __( 'Compliance Settings', 'sureforms' ) }</p>
					</span>
				</div>
			</div>
			{ selectedTab === 'email_notification' && (
				<EmailNotification
					emailNotificationData={ emailNotificationData }
				/>
			) }
			{ selectedTab === 'compliance_settings' && (
				<Compliance complianceData={ complianceData } />
			) }
		</div>
	);
};

export default SingleFormSettingsPopup;
