import EmailNotification from './email-settings/EmailNotification';
import Integration from './integrations';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';

const SingleFormSettingsPopup = (props) => {
	const { sureformsKeys, targetTab } = props;
	const emailIcon = parse(svgIcons.email);
	const integrationIcon = parse(svgIcons.integration);
	const emailNotificationData = sureformsKeys._srfm_email_notification || [];
	const [selectedTab, setSelectedTab] = useState(targetTab ?? 'email_notification');
	return (
		<div className="srfm-setting-modal-container">
			<div className="srfm-modal-sidebar">
				<div
					className={`srfm-modal-tab ${'email_notification' === selectedTab ? 'srfm-modal-tab-active' : ''}`}
					onClick={() => setSelectedTab('email_notification')}
				>
					<span className="srfm-modal-tab-icon">{emailIcon}</span>
					<span className="srfm-modal-tab-text">
						<p>{__('Email Notification', 'sureforms')}</p>
					</span>
				</div>
				<div
					className={`srfm-modal-tab ${'integration' === selectedTab ? 'srfm-modal-tab-active' : ''}`}
					onClick={() => setSelectedTab('integration')}
				>
					<span className="srfm-modal-tab-icon">{integrationIcon}</span>
					<span className="srfm-modal-tab-text">
						<p>{__('Integration', 'sureforms')}</p>
					</span>
				</div>
			</div>
			{ /* Modal Content */}
			{'email_notification' === selectedTab && (
				<EmailNotification
					emailNotificationData={emailNotificationData}
				/>
			)}
			{'integration' === selectedTab && (
				<Integration />
			)}
		</div>
	);
};

export default SingleFormSettingsPopup;
