import EmailNotification from './email-settings/EmailNotification';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

const SingleFormSetting = ( props ) => {
	const { sureformsKeys } = props;
    const emailNotificationData = sureformsKeys._srfm_email_notification || [];
	const [ selectedTab, setSelectedTab ] = useState( 'email_notification' );
	return (
		<div className="srfm-setting-modal-container">
			<div className="srfm-modal-sidebar">
				<div className="srfm-modal-tab" onClick={ () => setSelectedTab( 'email_notification' ) }>
					<span className="srfm-modal-tab-icon">
						<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="none">
							<path d="M18.125 5.625V14.375C18.125 15.4105 17.2855 16.25 16.25 16.25H3.75C2.71447 16.25 1.875 15.4105 1.875 14.375V5.625M18.125 5.625C18.125 4.58947 17.2855 3.75 16.25 3.75H3.75C2.71447 3.75 1.875 4.58947 1.875 5.625M18.125 5.625V5.82726C18.125 6.47837 17.7872 7.08287 17.2327 7.42412L10.9827 11.2703C10.38 11.6411 9.61996 11.6411 9.01732 11.2703L2.76732 7.42412C2.21279 7.08287 1.875 6.47837 1.875 5.82726V5.625" stroke="#475569" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round" />
						</svg>
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
