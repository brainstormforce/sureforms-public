import {
	useState,
	useLayoutEffect,
	createPortal,
	useEffect,
	memo,
	useContext,
	useMemo,
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
	CircleCheckBig,
	ShieldCheckIcon,
	XIcon,
	TriangleAlert,
	UserPlus,
	FileDown,
	FilePlus,
	File,
	FileText,
	Cpu,
	Link2,
	Save,
} from 'lucide-react';

import Suretriggers from '../integrations/suretriggers';
import Compliance from '../Compliance';
import FormCustomCssPanel from '../FormCustomCssPanel';
import SpamProtection from '../SpamProtection';
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
import OttoKitPage from '@Admin/settings/pages/OttoKit';
import ottoKitIcon from '@Image/suretriggers-grayscale.svg';

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

	const tabs = applyFilters(
		'srfm.formSettings.tabs',
		[
			/*parent tabs linked to nav*/
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
				id: 'spam_protection',
				label: __( 'Spam Protection', 'sureforms' ),
				icon: <TriangleAlert />,
				component: <SpamProtection />,
			},
			{
				id: 'integrations-preview',
				label: __( 'Integrations', 'sureforms' ),
				icon: <Cpu />,
				component: (
					<FeaturePreview
						featureName={ __( 'Integrations', 'sureforms' ) }
						featureHelpText={ __(
							'Connect SureForms with your favorite apps to automate tasks and sync data seamlessly.',
							'sureforms'
						) }
						icon={
							<Link2
								className="text-orange-500"
								size={ 40 }
								strokeWidth={ 1 }
							/>
						}
						title={ __(
							'Connect Native Integrations with SureForms',
							'sureforms'
						) }
						subtitle={ __(
							'Unlock powerful integrations in the Premimum plan to automate your workflows and connect SureForms directly with your favorite tools.',
							'sureforms'
						) }
						featureList={ [
							__(
								'Send form submissions straight to CRMs, email, and marketing platforms.',
								'sureforms'
							),
							__(
								'Automate repetitive tasks with seamless data syncing.',
								'sureforms'
							),
							__(
								'Access exclusive native integrations for faster workflows.',
								'sureforms'
							),
						] }
						utmMedium="integrations-preview-single-form-settings"
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
						featureHelpText={ __(
							'Generate and customize PDF copies of form submissions.',
							'sureforms'
						) }
						icon={
							<FileDown
								className="text-orange-500"
								size={ 40 }
								strokeWidth={ 1 }
							/>
						}
						title={ __( 'Generate Submission PDFs', 'sureforms' ) }
						subtitle={ __(
							'Turn every form entry into a polished PDF file, making it perfect for reports, records, or sharing.',
							'sureforms'
						) }
						featureList={ [
							__(
								'Automatically generate PDFs from your form submissions.',
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
				id: 'save-resume-preview',
				label: __( 'Save & Progress', 'sureforms' ),
				icon: <Save />,
				component: (
					<FeaturePreview
						featureName={ __( 'Save & Progress', 'sureforms' ) }
						featureHelpText={ __(
							'Allow users to save their progress and continue form completion later.',
							'sureforms'
						) }
						icon={
							<Save
								className="text-orange-500"
								size={ 40 }
								strokeWidth={ 1 }
							/>
						}
						title={ __(
							'Save & Progress in SureForms',
							'sureforms'
						) }
						subtitle={ __(
							'Give your users the flexibility to complete forms at their own pace by allowing them to save progress and return anytime.',
							'sureforms'
						) }
						featureList={ [
							__(
								'Let users pause long or multi-step forms and continue later.',
								'sureforms'
							),
							__(
								'Reduce form abandonment with convenient resume links and access their progress from anywhere.',
								'sureforms'
							),
							__(
								'Improve user experience for lengthy, complex, or multi-page forms.',
								'sureforms'
							),
						] }
						utmMedium="save-resume-preview-single-form-settings"
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
						featureHelpText={ __(
							'Onboard new users or update existing accounts through beautiful looking forms.',
							'sureforms'
						) }
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
							'Streamline the entire user onboarding process for your sites with seamless form-powered logins and registrations.',
							'sureforms'
						) }
						featureList={ [
							__(
								'Register new users directly via your form submissions.',
								'sureforms'
							),
							__(
								'Create or update existing accounts by mapping form data to user fields.',
								'sureforms'
							),
							__(
								'Assign roles and control access automatically.',
								'sureforms'
							),
						] }
						utmMedium="user-registration-preview-single-form-settings"
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
						featureHelpText={ __(
							'Transform your form submission into WordPress posts.',
							'sureforms'
						) }
						icon={
							<FilePlus
								className="text-orange-500"
								size={ 40 }
								strokeWidth={ 1 }
							/>
						}
						title={ __( 'Post Feed', 'sureforms' ) }
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
								'Map form fields to your post fields easily.',
								'sureforms'
							),
							__(
								'Automate the content publishing flow with few simple steps.',
								'sureforms'
							),
						] }
						utmMedium="post-feed-preview-single-form-settings"
					/>
				),
			},
			{
				id: 'ottokit',
				label: __( 'Automations', 'sureforms' ),
				icon: (
					<img
						src={ ottoKitIcon }
						alt={ __( 'OttoKit', 'sureforms' ) }
					/>
				),
				component: (
					<OttoKitPage
						{ ...{ isFormSettings: true, setSelectedTab } }
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
				parent: 'ottokit',
				label: __( 'SureTriggers', 'sureforms' ),
				icon: {},
				component: <Suretriggers />,
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

	// Apply filter to allow specific classes for pro tabs.
	const tabSpecificClasses = useMemo(
		() =>
			applyFilters(
				'srfm.formSettings.dialog.tabClasses',
				{
					suretriggers:
						'h-full min-w-[800px] bg-background-primary shadow-sm rounded-xl',
					ottokit:
						'min-w-[800px] bg-background-primary p-4 shadow-sm rounded-xl border-subtle',
					default: 'h-full max-w-[43.5rem]',
				},
				selectedTab
			),
		[ selectedTab ]
	);

	const containerClassName = cn(
		'w-full mx-auto',
		tabSpecificClasses[ selectedTab ] || tabSpecificClasses.default
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
				className="[&>div>div]:h-full z-99999 border-radius-none"
			>
				<ForceUIDialog.Backdrop />
				<ForceUIDialog.Panel className="h-full w-full m-auto rounded-none srfm-dialog-panel">
					<Container
						direction="column"
						gap="none"
						className="w-full h-full pt-3 divide-y divide-x-0 divide-solid divide-border-subtle"
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
