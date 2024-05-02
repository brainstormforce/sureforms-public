import EmailNotification from './email-settings/EmailNotification';
import Compliance from './Compliance';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	MdSecurity,
	MdOutlineMailOutline,
	MdOutlineCheckCircleOutline,
} from 'react-icons/md';
import FormConfirmSetting from './form-confirm-setting';

const SingleFormSettingsPopup = ( props ) => {
	const { sureformsKeys, targetTab } = props;
	const emailNotificationData = sureformsKeys._srfm_email_notification || [];
	const complianceData = sureformsKeys._srfm_compliance || [];
	const [ selectedTab, setSelectedTab ] = useState(
		targetTab ?? 'email_notification'
	);
	const tabs = [
		{
			id: 'email_notification',
			title: __( 'Email Notification', 'sureforms' ),
			icon: <MdOutlineMailOutline size={ 20 } />,
			component: (
				<EmailNotification
					emailNotificationData={ emailNotificationData }
				/>
			),
		},
		{
			id: 'form_confirmation',
			title: __( 'Form Confirmation', 'sureforms' ),
			icon: <MdOutlineCheckCircleOutline size={ 20 } />,
			component: <FormConfirmSetting />,
		},
		{
			id: 'compliance_settings',
			title: __( 'Compliance Settings', 'sureforms' ),
			icon: <MdSecurity size={ 20 } />,
			component: <Compliance complianceData={ complianceData } />,
		},
	];
	return (
		<div className="srfm-setting-modal-container">
			<div className="srfm-modal-sidebar">
				{ tabs.map( ( tabItem, tabIndex ) => (
					<div
						key={ tabIndex }
						className={ `srfm-modal-tab ${
							tabItem.id === selectedTab
								? 'srfm-modal-tab-active'
								: ''
						}` }
						onClick={ () => setSelectedTab( tabItem.id ) }
					>
						<span className="srfm-modal-tab-icon">
							{ tabItem.icon }
						</span>
						<span className="srfm-modal-tab-text">
							<p>{ tabItem.title }</p>
						</span>
					</div>
				) ) }
			</div>
			{ /* Modal Content */ }
			<>{ tabs.find( ( { id } ) => id === selectedTab ).component }</>
		</div>
	);
};

export default SingleFormSettingsPopup;
