import EmailNotification from './email-settings/EmailNotification';
import WebhookSettings from './webhooks/WebhookSettings';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';

const SingleFormSettingsPopup = ( props ) => {
	const { sureformsKeys } = props;
	const emailIcon = parse( svgIcons.email );
	const emailNotificationData = sureformsKeys._srfm_email_notification || [];
	const [ selectedTab, setSelectedTab ] = useState( 'email_notification' );
	return (
		<div className="srfm-setting-modal-container">
			<div className="srfm-modal-sidebar">
				<div
					className="srfm-modal-tab"
					onClick={ () => setSelectedTab( 'email_notification' ) }
				>
					<span className="srfm-modal-tab-icon">{ emailIcon }</span>
					<span className="srfm-modal-tab-text">
						<p>{ __( 'Email', 'sureforms' ) }</p>
					</span>
				</div>
			</div>
			<div
				className="srfm-modal-tab"
				onClick={ () => setSelectedTab( 'webhook_settings' ) }
			>
				<span className="srfm-modal-tab-icon">{ emailIcon }</span>
				<span className="srfm-modal-tab-text">
					<p>{ __( 'Webhooks Settings', 'sureforms' ) }</p>
				</span>
			</div>
			{ /* Modal Content */ }
			{ 'email_notification' === selectedTab && (
				<EmailNotification
					emailNotificationData={ emailNotificationData }
				/>
			) }
			{ 'webhook_settings' === selectedTab && (
				<WebhookSettings />
			) }
		</div>
	);
};

export default SingleFormSettingsPopup;
