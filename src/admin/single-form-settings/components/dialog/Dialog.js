import {
	useState,
	useLayoutEffect,
	createPortal,
	useEffect,
	memo,
} from '@wordpress/element';
import {
	Dialog as ForceUIDialog,
	Button,
	Container,
	Title,
} from '@bsf/force-ui';
import SidebarNav from './SidebarNav';
import {
	AlertTriangleIcon,
	Code2Icon,
	CpuIcon,
	SettingsIcon,
	ShieldCheckIcon,
	XIcon,
} from 'lucide-react';

import Integrations from '../integrations';
import Suretriggers from '../integrations/suretriggers';
import Compliance from '../Compliance';
import FormCustomCssPanel from '../FormCustomCssPanel';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import EmailNotification from '../email-settings/EmailNotification';
import { select } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import FormConfirmSetting from '../form-confirm-setting';
import {
	setFormSpecificSmartTags,
	getServerGeneratedBlockSlugs,
} from '@Utils/Helpers';
import toast from 'react-hot-toast';

const Dialog = ( {
	open,
	setOpen,
	sureformsKeys,
	targetTab,
	setHasValidationErrors,
	close,
} ) => {
	const [ renderRoot, setRenderRoot ] = useState( null );

	// Create a root element for the dialog
	useLayoutEffect( () => {
		let dialogRoot = document.getElementById( 'srfm-dialog-root' );
		if ( ! dialogRoot ) {
			dialogRoot = document.createElement( 'div' );
			dialogRoot.id = 'srfm-dialog-root';
			document.body.appendChild( dialogRoot );
		}
		setRenderRoot( dialogRoot );
	}, [] );

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
				label: __( 'Form Confirmation', 'sureforms' ),
				icon: <SettingsIcon />,
				component: (
					<FormConfirmSetting
						setHasValidationErrors={ setHasValidationErrors }
						toast={ toast }
					/>
				),
			},
			{
				id: 'email_notification',
				label: __( 'Email Notification', 'sureforms' ),
				icon: <ShieldCheckIcon />,
				component: (
					<EmailNotification
						{ ...{ setHasValidationErrors, emailNotificationData } }
					/>
				),
			},
			{
				id: 'compliance_settings',
				label: __( 'Compliance Settings', 'sureforms' ),
				icon: <AlertTriangleIcon />,
				component: <Compliance { ...{ complianceData } } />,
			},
			{
				id: 'integrations',
				label: __( 'Integrations', 'sureforms' ),
				icon: <CpuIcon />,
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
				label: __( 'Custom CSS', 'sureforms' ),
				icon: <Code2Icon />,
				component: <FormCustomCssPanel { ...{ formCustomCssData } } />,
			},
			{
				id: 'suretriggers',
				parent: 'integrations',
				label: __( 'SureTriggers', 'sureforms' ),
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

	useLayoutEffect( () => {
		if ( ! targetTab || ! open ) {
			return;
		}
		setSelectedTab( targetTab );
	}, [ targetTab, open ] );

	return (
		renderRoot &&
		createPortal(
			<ForceUIDialog
				design="simple"
				exitOnEsc
				scrollLock
				open={ open }
				setOpen={ setOpen }
				trigger={ <Button className="z-999999">Open Dialog</Button> }
				className="[&>div>div]:h-full"
			>
				<ForceUIDialog.Backdrop />
				<ForceUIDialog.Panel className="size-[calc(100%-80px)] m-auto">
					<Container
						direction="column"
						gap="none"
						className="w-full h-full p-3 divide-y divide-x-0 divide-solid divide-border-subtle"
					>
						<Container className="px-2 pt-2 pb-4" justify="between">
							<Title tag="h6" title="Form Behavior" size="xs" />
							<Button
								variant="ghost"
								size="sm"
								className="p-1"
								onClick={ close }
								icon={ <XIcon /> }
							/>
						</Container>
						<div className="px-2 pb-2 pt-2 w-full h-[calc(100%-3rem)] flex justify-start items-stretch">
							{ /* Sidebar Navigation */ }
							<SidebarNav
								tabs={ tabs }
								selectedTab={ selectedTab }
								setSelectedTab={ setSelectedTab }
								parentTab={ parentTab }
							/>
							{ /* Content Area */ }
							<div className="p-8 w-full h-full bg-background-secondary overflow-y-auto">
								<div className="w-full max-w-[43.5rem] h-full mx-auto">
									{
										tabs.find(
											( { id } ) => id === selectedTab
										)?.component
									}
								</div>
							</div>
						</div>
					</Container>
				</ForceUIDialog.Panel>
			</ForceUIDialog>,
			renderRoot
		)
	);
};

export default memo( Dialog );
