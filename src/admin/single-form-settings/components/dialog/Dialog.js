import {
	useState,
	useLayoutEffect,
	createPortal,
	useEffect,
	memo,
	useContext,
} from '@wordpress/element';
import {
	Dialog as ForceUIDialog,
	Button,
	Container,
	Title,
} from '@bsf/force-ui';
import SidebarNav from './SidebarNav';
import {
	Settings,
	Code2Icon,
	CpuIcon,
	CircleCheckBig,
	ShieldCheckIcon,
	XIcon,
	UserPlus,
	FileDown,
	FilePlus,
	File,
	FileText,
} from 'lucide-react';

import Integrations from '../integrations';
import Suretriggers from '../integrations/suretriggers';
import Compliance from '../Compliance';
import FormCustomCssPanel from '../FormCustomCssPanel';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import EmailNotification from '../email-settings/EmailNotification';
import FormConfirmSetting from '../form-confirm-setting';
import { setFormSpecificSmartTags, cn } from '@Utils/Helpers';
import toast from 'react-hot-toast';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import FormRestriction from '../form-restrictions/FormRestriction';
import { FormRestrictionContext } from '../form-restrictions/context';
import FeaturePreview from '../FeaturePreview';

const Dialog = ( {
	open,
	setOpen,
	sureformsKeys,
	targetTab,
	setHasValidationErrors,
	close,
} ) => {
	const [ renderRoot, setRenderRoot ] = useState( null );
	const { updateBlockAttributes } = useDispatch( blockEditorStore );

	// Load Form restrictions setting early.
	const { editMeta } = useContext( FormRestrictionContext );
	useEffect( () => {
		editMeta();
	}, [] );

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

	const getAdminMenuWidth = () => {
		const adminMenu = document.getElementById( 'adminmenu' );
		const width = adminMenu ? adminMenu.offsetWidth : 0;
		return width + 40;
	};

	const isFullscreen = useSelect(
		( select ) =>
			select( 'core/edit-post' ).isFeatureActive( 'fullscreenMode' ),
		[]
	);

	useLayoutEffect( () => {
		const updateMargin = () => {
			window.requestAnimationFrame( () => {
				const dialogWrapper =
					document.querySelector( '.srfm-dialog-panel' );
				if ( ! dialogWrapper ) {
					return;
				}

				const width = getAdminMenuWidth();

				if ( ! isFullscreen ) {
					dialogWrapper.style.marginLeft = `${ width }px`;
					dialogWrapper.style.marginRight = '40px';
				} else {
					dialogWrapper.style.removeProperty( 'margin-left' );
					dialogWrapper.style.removeProperty( 'margin-right' );
				}
			} );
		};
		updateMargin();
		window.addEventListener( 'resize', updateMargin );
		return () => window.removeEventListener( 'resize', updateMargin );
	}, [ open, isFullscreen ] );

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

	const tabs = applyFilters(
		'srfm.formSettings.tabs',
		[
			/*parent tabs linked to nav*/
			{
				id: 'form_confirmation',
				label: __( 'Form Confirmation', 'sureforms' ),
				icon: <CircleCheckBig />,
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
				id: 'advanced-settings',
				label: __( 'Advanced Settings', 'sureforms' ),
				icon: <Settings />,
				component: (
					<>
						<FormRestriction />
						<Compliance { ...{ complianceData } } />
					</>
				),
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
				id: 'pdf-generation-preview',
				label: __( 'PDF Generation', 'sureforms' ),
				icon: <File />,
				component: (
					<FeaturePreview
						featureName={ __( 'PDF Generation', 'sureforms' ) }
						icon={
							<FileDown
								className="text-orange-500"
								size={ 40 }
								strokeWidth={ 1 }
							/>
						}
						title={ __( 'Generate Submission PDFs', 'sureforms' ) }
						subtitle={ __(
							'Turn every form entry into a polished PDF file—perfect for reports, records, or sharing.',
							'sureforms'
						) }
						featureList={ [
							__(
								'Automatically create PDFs from form submissions.',
								'sureforms'
							),
							__(
								'Customize PDF templates with your branding.',
								'sureforms'
							),
							__(
								'Download or email PDFs instantly.',
								'sureforms'
							),
						] }
						utmMedium="pdf-preview-single-form-settings"
					/>
				),
			},
			{
				id: 'user-login-preview',
				label: __( 'User Registration', 'sureforms' ),
				icon: <UserPlus />,
				component: (
					<FeaturePreview
						featureName={ __( 'User Registration', 'sureforms' ) }
						icon={
							<UserPlus
								className="text-orange-500"
								size={ 40 }
								strokeWidth={ 1 }
							/>
						}
						title={ __(
							'Register Users with SureForms',
							'sureforms'
						) }
						subtitle={ __(
							'Streamline the user onboarding for your sites with form-powered registration.',
							'sureforms'
						) }
						featureList={ [
							__(
								'Register new users directly from form submissions.',
								'sureforms'
							),
							__(
								'Update existing accounts with custom data.',
								'sureforms'
							),
							__(
								'Assign roles and control access automatically.',
								'sureforms'
							),
						] }
						utmMedium="user-registration-preview-single-form-settings"
						autoSaveHelpText={ __(
							'Register new users or update existing accounts with form submissions.',
							'sureforms'
						) }
					/>
				),
			},
			{
				id: 'post-feed-preview',
				label: __( 'Post Feed', 'sureforms' ),
				icon: <FileText />,
				component: (
					<FeaturePreview
						featureName={ __( 'Post Feed', 'sureforms' ) }
						icon={
							<FilePlus
								className="text-orange-500"
								size={ 40 }
								strokeWidth={ 1 }
							/>
						}
						title={ __(
							'Automatically create WordPress posts from form submissions.',
							'sureforms'
						) }
						subtitle={ __(
							'Automatically turn form submissions into WordPress posts, pages, or custom post types. Save big on time and let your forms publish content directly.',
							'sureforms'
						) }
						featureList={ [
							__(
								'Create posts, pages, or CPTs from your form entries.',
								'sureforms'
							),
							__(
								'Map form fields to post fields easily.',
								'sureforms'
							),
							__(
								'Automate content publishing with no extra steps.',
								'sureforms'
							),
						] }
						utmMedium="post-feed-preview-single-form-settings"
						autoSaveHelpText={ __(
							'Automatically create WordPress posts from form submissions.',
							'sureforms'
						) }
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

	setFormSpecificSmartTags( updateBlockAttributes );

	useEffect( () => {
		const activeTabObject = tabs.find( ( tab ) => tab.id === selectedTab );
		if ( activeTabObject?.parent ) {
			setParentTab( activeTabObject.parent );
		} else {
			setParentTab( null );
		}
	}, [ selectedTab ] );

	useLayoutEffect( () => {
		if ( ! targetTab || ! open ) {
			return;
		}
		setSelectedTab( targetTab );
	}, [ targetTab, open ] );

	const containerClassName = cn(
		'w-full h-full mx-auto',
		selectedTab === 'suretriggers' ? 'min-w-[800px]' : 'max-w-[43.5rem]'
	);

	return (
		renderRoot &&
		createPortal(
			<ForceUIDialog
				design="simple"
				exitOnEsc
				scrollLock
				open={ open }
				setOpen={ setOpen }
				className="[&>div>div]:h-full z-99999"
			>
				<ForceUIDialog.Backdrop />
				<ForceUIDialog.Panel className="srfm-dialog-panel size-[calc(100%-80px)] m-auto">
					<Container
						direction="column"
						gap="none"
						className="w-full h-full py-3 divide-y divide-x-0 divide-solid divide-border-subtle"
					>
						<Container className="py-2 px-4" justify="between">
							<Title
								tag="h6"
								title={ __( 'Form Behavior', 'sureforms' ) }
								size="xs"
							/>
							<Button
								variant="ghost"
								size="sm"
								className="p-1"
								onClick={ close }
								icon={ <XIcon /> }
							/>
						</Container>
						<div className="w-full h-[calc(100%-3rem)] flex justify-start items-stretch">
							{ /* Sidebar Navigation */ }
							<SidebarNav
								tabs={ tabs }
								selectedTab={ selectedTab }
								setSelectedTab={ setSelectedTab }
								parentTab={ parentTab }
							/>
							{ /* Content Area */ }
							<div className="p-8 w-full h-full bg-background-secondary overflow-y-auto">
								<div className={ containerClassName }>
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
