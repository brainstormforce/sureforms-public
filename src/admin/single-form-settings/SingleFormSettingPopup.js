import EmailNotification from './email-settings/EmailNotification';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';

const SingleFormSetting = ( props ) => {
	const { sureformsKeys } = props;
	const emailIcon = parse( svgIcons.email );
	const emailNotificationData = sureformsKeys._srfm_email_notification || [];
	const [ selectedTab, setSelectedTab ] = useState( 'email_notification' );
	return (
		<div className="srfm-setting-modal-container">
			<div className="srfm-modal-sidebar">
				<div className="srfm-modal-tab" onClick={ () => setSelectedTab( 'email_notification' ) }>
					<span className="srfm-modal-tab-icon">
						{ emailIcon }
					</span>
					<span className="srfm-modal-tab-text">
						<p>{ __( 'Email Notifications', 'sureforms' ) }</p>
					</span>
				</div>
			</div>
			{ /* Modal Content */ }
			{ 'email_notification' === selectedTab && <EmailNotification emailNotificationData={ emailNotificationData } /> }
		</div>
	);
};

export default SingleFormSetting;
