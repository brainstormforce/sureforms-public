import Integrations from './integrations';
import Suretriggers from './integrations/suretriggers';
import Compliance from './Compliance';
import FormCustomCssPanel from './FormCustomCssPanel';
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import EmailNotification from './email-settings/EmailNotification';
import {
	MdSecurity,
	MdOutlineMailOutline,
	MdOutlineCheckCircleOutline,
	MdOutlineCode,
	MdOutlineDashboardCustomize,
} from 'react-icons/md';
import { select } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import FormConfirmSetting from './form-confirm-setting';
import {
	setFormSpecificSmartTags,
	SRFMToaster,
	getServerGeneratedBlockSlugs,
} from '@Utils/Helpers';
import toast from 'react-hot-toast';

const SingleFormSettingsPopup = ( props ) => {
	const { sureformsKeys, targetTab, setHasValidationErrors } = props;
	const emailNotificationData = sureformsKeys._srfm_email_notification || [];
	const complianceData = sureformsKeys._srfm_compliance || [];
	const formCustomCssData = sureformsKeys._srfm_form_custom_css || [];
	const [ selectedTab, setSelectedTab ] = useState(
		targetTab ?? 'email_notification'
	);

	const [ parentTab, setParentTab ] = useState( null );
	const [ action, setAction ] = useState();
	const [ CTA, setCTA ] = useState();
	const [ pluginConnected, setPluginConnected ] = useState( null );

	const [ blockSlugs, setBlockSlugs ] = useState( {} );

	const tabs = applyFilters(
		'srfm.formSettings.tabs',
		[
			/*parent tabs linked to nav*/
			{
				id: 'form_confirmation',
				title: __( 'Form Confirmation', 'sureforms' ),
				icon: <MdOutlineCheckCircleOutline size={ 20 } />,
				component: (
					<FormConfirmSetting
						setHasValidationErrors={ setHasValidationErrors }
						toast={ toast }
					/>
				),
			},
			{
				id: 'email_notification',
				title: __( 'Email Notification', 'sureforms' ),
				icon: <MdOutlineMailOutline size={ 20 } />,
				component: (
					<EmailNotification
						{ ...{ setHasValidationErrors, emailNotificationData } }
					/>
				),
			},
			{
				id: 'compliance_settings',
				title: __( 'Compliance Settings', 'sureforms' ),
				icon: <MdSecurity size={ 20 } />,
				component: <Compliance { ...{ complianceData } } />,
			},
			{
				id: 'integrations',
				title: __( 'Integrations', 'sureforms' ),
				icon: <MdOutlineDashboardCustomize size={ 20 } />,
				component: (
					<Integrations
						{ ...{
							setSelectedTab,
							action,
							setAction,
							CTA,
							setCTA,
							pluginConnected,
							setPluginConnected,
						} }
					/>
				),
			},
			{
				id: 'form_custom_css',
				title: __( 'Custom CSS', 'sureforms' ),
				icon: <MdOutlineCode size={ 20 } />,
				component: <FormCustomCssPanel { ...{ formCustomCssData } } />,
			},
			{
				id: 'suretriggers',
				parent: 'integrations',
				title: __( 'SureTriggers', 'sureforms' ),
				icon: {},
				component: <Suretriggers { ...{ setSelectedTab } } />,
			},
			/* can contain child tabs not linked to nav */
			/* add parent nav id for child tabs */
		],
		{
			// Other arguments that can be consumed from hooks.
			setSelectedTab,
			setHasValidationErrors,
		}
	);

	const { getBlocks, getCurrentPostId, getEditedPostContent } =
		select( editorStore );

	setFormSpecificSmartTags( getBlocks(), blockSlugs );

	useEffect( () => {
		const activeTabObject = tabs.find( ( tab ) => tab.id === selectedTab );
		if ( activeTabObject?.parent ) {
			setParentTab( activeTabObject.parent );
		} else {
			setParentTab( null );
		}

		if ( ! Object.keys( blockSlugs ).length ) {
			// Process the blocks using fetch one time per Modal open ( Or if data is not set already in blockSlugs state. )
			getServerGeneratedBlockSlugs(
				getCurrentPostId(),
				getEditedPostContent()
			)
				.then( ( response ) => {
					if ( true !== response?.success ) {
						return console.error(
							'Unable to fetch saved blocks: ',
							response?.data
						);
					}
					setBlockSlugs( response.data );
				} )
				.catch( ( err ) => {
					console.error( 'Unable to fetch saved blocks: ', err );
				} );
		}
	}, [ selectedTab ] );

	return (
		<div className="srfm-setting-modal-container">
			<div className="srfm-modal-sidebar">
				{ tabs.map(
					( tabItem, tabIndex ) =>
						tabItem.parent === undefined && (
							<div
								key={ tabIndex }
								className={ `srfm-modal-tab ${
									tabItem.id === selectedTab ||
									( null !== parentTab &&
										tabItem.id === parentTab )
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
						)
				) }
			</div>
			<SRFMToaster
				containerClassName="srfm-single-form-toast-ctn"
				containerStyle={ {
					marginTop: '35px',
				} }
			/>
			{ /* Modal Content */ }
			<div className="srfm-modal-main">
				{ tabs.find( ( { id } ) => id === selectedTab ).component }
			</div>
		</div>
	);
};

export default SingleFormSettingsPopup;
