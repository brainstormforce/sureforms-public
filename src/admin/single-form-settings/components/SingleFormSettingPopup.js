import Integration from './integrations';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import parse from 'html-react-parser';
import svgIcons from '@Image/single-form-logo.json';
import EmailNotification from './email-settings/EmailNotification';

const SingleFormSettingsPopup = ( props ) => {
	const { sureformsKeys, targetTab } = props;
	const emailIcon = parse( svgIcons.email );
	const integrationIcon = parse( svgIcons.integration );
	const emailNotificationData = sureformsKeys._srfm_email_notification || [];
	const [ selectedTab, setSelectedTab ] = useState( targetTab ?? 'email_notification' );

	const tabs = applyFilters(
		'srfm.form_settings.tabs',
		[
			/*parent tabs linked to nav*/
			{
				id: 'email_notification',
				title: __( 'Email Notification', 'sureforms' ),
				icon: emailIcon,
				component: <EmailNotification
					emailNotificationData={ emailNotificationData }
				/>,
			},
			{
				id: 'integration',
				title: __( 'Integration', 'sureforms' ),
				icon: integrationIcon,
				component: <Integration
					setSelectedTab={ setSelectedTab } />,
			},
			/* can contain child tabs not linked to nav */
			/* add parent nav id for child tabs */
		]
	);

	return (
		<div className="srfm-setting-modal-container">
			<div className="srfm-modal-sidebar">
				{
					tabs.map( ( tabItem, tabIndex ) => (
						tabItem.parent === undefined && (
							<div
								key={ tabIndex }
								className={ `srfm-modal-tab ${ tabItem.id === selectedTab ? 'srfm-modal-tab-active' : '' }` }
								onClick={ () => setSelectedTab( tabItem.id ) }
							>
								<span className="srfm-modal-tab-icon">{ tabItem.icon }</span>
								<span className="srfm-modal-tab-text">
									<p>{ tabItem.title }</p>
								</span>
							</div> )
					) )
				}
			</div>
			{ /* Modal Content */ }
			<div className="srfm-modal-main">
				{
					tabs.find( ( { id } ) => id === selectedTab ).component
				}
			</div>
		</div>
	);
};

export default SingleFormSettingsPopup;
