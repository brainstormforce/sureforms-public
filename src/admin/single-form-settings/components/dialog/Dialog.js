import {
	useState,
	useLayoutEffect,
	createPortal,
	useEffect,
	useRef,
	memo,
	useContext,
	useMemo,
} from '@wordpress/element';
import {
	Dialog as ForceUIDialog,
	Button,
	Container,
	Title,
	Toaster,
	toast as bsfToast,
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
	Split,
	ListTodo,
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
import { useDispatch, useSelect, select } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { STORE_NAME as SRFM_STORE_NAME } from '@Store/constants';
import FormRestriction from '../form-restrictions/FormRestriction';
import { FormRestrictionContext } from '../form-restrictions/context';
import FeaturePreview from '../FeaturePreview';
import OttoKitPage from '@Admin/settings/pages/OttoKit';
import ottoKitIcon from '@Image/ottokit.png';
import ConfirmationDialog from '@Admin/components/ConfirmationDialog';

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

	const {
		discardFormSettingsState,
		discardFormSettingValues,
		discardChanges,
		keepChanges,
		confirmBackDiscard,
		cancelBackDiscard,
	} = useDispatch( SRFM_STORE_NAME );

	// Active sub-form dirty signal — pushed by the tab via
	// `setSingleFormSettingUnsave`. Drives the close / tab-switch /
	// Esc / backdrop / beforeunload guards below.
	const isDialogDirty = useSelect(
		( s ) =>
			s( SRFM_STORE_NAME )?.selectSingleFormSettingUnsave?.() || false,
		[]
	);
	// Active sub-form in-flight Save signal — pushed by
	// `TabContentWrapper.handleSave` around its await. The guards below
	// short-circuit while this is true so a tab-switch / close during
	// an in-flight POST can't trigger a discard that would revert local
	// state for a save the server has already accepted.
	const isDialogSaving = useSelect(
		( s ) =>
			s( SRFM_STORE_NAME )?.selectSingleFormSettingSaving?.() || false,
		[]
	);

	// Copy payload for the dialog-level back-arrow discard modal. Set by
	// the active tab via `requestBackDiscard`; the tab subscribes to the
	// confirm counter to react when the user confirms.
	const pendingBackDiscard = useSelect(
		( s ) =>
			s( SRFM_STORE_NAME )?.selectPendingBackDiscard?.() || null,
		[]
	);

	// Centralized toast queue. Tabs and helpers call `notify.success/.error`
	// from `@Utils/notify`, which dispatches `requestToast` — that bumps
	// `toastCounter` and stashes the payload in `pendingToast`. The
	// listener below reads the payload and fires @bsf/force-ui's
	// imperative `toast.success/.error(...)` exactly once per bump.
	const toastCounter = useSelect(
		( s ) => s( SRFM_STORE_NAME )?.selectToastCounter?.() || 0,
		[]
	);
	const lastToastCounter = useRef( toastCounter );
	useEffect( () => {
		if ( toastCounter === lastToastCounter.current ) {
			return;
		}
		lastToastCounter.current = toastCounter;
		const payload =
			select( SRFM_STORE_NAME )?.selectPendingToast?.();
		if ( ! payload || ! payload.message ) {
			return;
		}
		// Dismiss any in-flight toast so only the latest action is on
		// screen at a time — matches the `toast.dismiss()` pattern the
		// migrated call sites used to inline.
		bsfToast.dismiss();
		const fire =
			payload.type === 'error' ? bsfToast.error : bsfToast.success;
		fire( payload.message, { duration: payload.duration ?? 5000 } );
	}, [ toastCounter ] );

	// `{ type: 'tab' | 'close', tab?: string }` while the guard modal is
	// asking the user to confirm.
	const [ pendingAction, setPendingAction ] = useState( null );

	// Native browser confirmation when the user closes the browser tab
	// or navigates away with the dialog open AND dirty.
	useEffect( () => {
		if ( ! open || ! isDialogDirty ) {
			return undefined;
		}
		const handler = ( e ) => {
			e.preventDefault();
			e.returnValue = '';
		};
		window.addEventListener( 'beforeunload', handler );
		return () => window.removeEventListener( 'beforeunload', handler );
	}, [ open, isDialogDirty ] );

	// Tear down the Redux `formSettings` slice on dialog close so the
	// next open starts from a clean slate.
	useEffect( () => {
		if ( ! open ) {
			discardFormSettingsState();
		}
	}, [ open ] );

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
		// Param renamed to `wpSelect` to avoid shadowing the imported
		// `select` from `@wordpress/data` used by the toast listener below.
		( wpSelect ) =>
			wpSelect( 'core/edit-post' ).isFeatureActive( 'fullscreenMode' ),
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

	useEffect( () => {
		const dialogWrapper = document.querySelector( '.srfm-dialog-panel' );
		if ( ! dialogWrapper ) {
			return;
		}
		if ( ! isFullscreen ) {
			dialogWrapper.style.marginTop = '40px';
		} else {
			dialogWrapper.style.removeProperty( 'margin-top' );
		}
	}, [ open, isFullscreen ] );

	const emailNotificationData = sureformsKeys._srfm_email_notification || [];
	const complianceData = sureformsKeys._srfm_compliance || [];
	const formCustomCssData = sureformsKeys._srfm_form_custom_css || [];
	const [ selectedTab, setSelectedTab ] = useState(
		targetTab ?? 'email_notification'
	);

	const performAction = ( action ) => {
		if ( ! action ) {
			return;
		}
		if ( 'tab' === action.type ) {
			setSelectedTab( action.tab );
			return;
		}
		if ( 'close' === action.type ) {
			setOpen( false );
		}
	};

	const requestTabSwitch = ( nextTab ) => {
		// Hold tab clicks while a save POST is in flight — the in-flight
		// edits will be the post-save baseline once the await resolves,
		// and switching tabs mid-await would let "Discard & continue"
		// roll back an already-accepted save.
		if ( isDialogSaving ) {
			return;
		}
		if ( ! isDialogDirty || nextTab === selectedTab ) {
			setSelectedTab( nextTab );
			return;
		}
		setPendingAction( { type: 'tab', tab: nextTab } );
	};

	const requestClose = () => {
		if ( isDialogSaving ) {
			return;
		}
		if ( ! isDialogDirty ) {
			if ( typeof close === 'function' ) {
				close();
			} else {
				setOpen( false );
			}
			return;
		}
		setPendingAction( { type: 'close' } );
	};

	// Wrap `setOpen` so Esc / backdrop close attempts also flow through
	// the guard. `ForceUIDialog` calls `setOpen(false)` on those paths.
	const guardedSetOpen = ( nextOpen ) => {
		if ( false === nextOpen && isDialogSaving ) {
			return;
		}
		if ( false === nextOpen && isDialogDirty ) {
			setPendingAction( { type: 'close' } );
			return;
		}
		setOpen( nextOpen );
	};

	const onConfirmDiscard = () => {
		discardFormSettingValues();
		// Flips the centralized dirty flag false + bumps the discard
		// counter so the active tab resets its local React state.
		discardChanges();
		const next = pendingAction;
		setPendingAction( null );
		performAction( next );
	};

	const onCancelDiscard = () => {
		// API-symmetric "stay" branch. No state change today; gives us a
		// single hook to extend later.
		keepChanges();
		setPendingAction( null );
	};

	const [ parentTab, setParentTab ] = useState( null );

	const [ pluginConnected, setPluginConnected ] = useState(
		srfm_admin?.integrations?.sure_triggers?.connected ?? null
	);
	const [ localPluginStatus, setLocalPluginStatus ] = useState(
		srfm_admin?.integrations?.sure_triggers?.status
	);

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
				id: 'conditional-confirmations-preview',
				label: __( 'Conditional Confirmations', 'sureforms' ),
				icon: <Split />,
				component: (
					<FeaturePreview
						featureName={ __(
							'Conditional Confirmations',
							'sureforms'
						) }
						featureHelpText={ __(
							'Set up the message or redirect users will see after submitting the form.',
							'sureforms'
						) }
						icon={
							<Split
								className="text-orange-500"
								size={ 40 }
								strokeWidth={ 1 }
							/>
						}
						title={ __( 'Conditional Confirmations', 'sureforms' ) }
						subtitle={ __(
							'Show the right message to the right user based on how they respond. Personalize confirmations with smart conditions and guide users to the next best step automatically.',
							'sureforms'
						) }
						featureList={ [
							__(
								'Display different confirmation messages based on form responses.',
								'sureforms'
							),
							__(
								'Redirect users to specific pages or URLs conditionally.',
								'sureforms'
							),
							__(
								'Create personalized thank-you messages without extra forms.',
								'sureforms'
							),
						] }
						utmMedium="conditional-confirmations-preview-single-form-settings"
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
						{ ...{
							isFormSettings: true,
							setSelectedTab,
							pluginConnected,
							setPluginConnected,
							localPluginStatus,
							setLocalPluginStatus,
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
				id: 'advanced-settings',
				label: __( 'Advanced Settings', 'sureforms' ),
				icon: <Settings />,
				component: (
					<>
						<FormRestriction
							setHasValidationErrors={ setHasValidationErrors }
						/>
						<Compliance { ...{ complianceData } } />
					</>
				),
			},
			{
				id: 'quiz-preview',
				label: __( 'Quizzes', 'sureforms' ),
				icon: <ListTodo size={ 18 } />,
				component: (
					<FeaturePreview
						featureName={ __( 'Quizzes', 'sureforms' ) }
						featureHelpText={ __(
							'Create interactive quizzes to engage your audience and gather insights.',
							'sureforms'
						) }
						icon={
							<ListTodo
								className="text-orange-500"
								size={ 40 }
								strokeWidth={ 1 }
							/>
						}
						title={ __( 'Quizzes', 'sureforms' ) }
						subtitle={ __(
							'Design engaging quizzes with various question types, personalized feedback, and automated scoring to captivate your audience and gain valuable insights.',
							'sureforms'
						) }
						featureList={ [
							__(
								'Create interactive quizzes with multiple question types.',
								'sureforms'
							),
							__(
								'Provide personalized feedback based on user responses.',
								'sureforms'
							),
							__(
								'Automate scoring and lead segmentation for better insights.',
								'sureforms'
							),
						] }
						utmMedium="quiz-preview-single-form-settings"
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

	useEffect( () => {
		setFormSpecificSmartTags( updateBlockAttributes );
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

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
				setOpen={ guardedSetOpen }
				className="[&>div>div]:h-full z-99999 border-radius-none"
			>
				<ForceUIDialog.Backdrop />
				<ForceUIDialog.Panel className="h-full w-full m-auto rounded-none srfm-dialog-panel">
					<Container
						direction="column"
						gap="none"
						className="w-full h-full divide-y divide-x-0 divide-solid divide-border-subtle"
					>
						<Container className="py-3 px-4" justify="between">
							<Title
								tag="h6"
								title={ __( 'Form Behavior', 'sureforms' ) }
								size="xs"
							/>
							<Button
								variant="ghost"
								size="sm"
								className="p-1"
								onClick={ requestClose }
								icon={ <XIcon /> }
							/>
						</Container>
						<div className="w-full h-[calc(100%-3rem)] flex justify-start items-stretch">
							{ /* Sidebar Navigation */ }
							<SidebarNav
								tabs={ tabs }
								selectedTab={ selectedTab }
								setSelectedTab={ requestTabSwitch }
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
					<ConfirmationDialog
						isOpen={ pendingAction !== null }
						title={ __( 'Unsaved changes', 'sureforms' ) }
						description={ __(
							'You have unsaved changes. Discard them to continue, or stay to save your changes.',
							'sureforms'
						) }
						confirmButtonText={ __(
							'Discard & continue',
							'sureforms'
						) }
						cancelButtonText={ __( 'Keep editing', 'sureforms' ) }
						onConfirm={ onConfirmDiscard }
						onCancel={ onCancelDiscard }
						destructiveConfirmButton={ true }
					/>
					{ /*
					 * Centralized back-arrow discard modal. Tabs that own
					 * a "back arrow" (e.g. EmailConfirmation editor)
					 * dispatch `requestBackDiscard({ ...copy })` with
					 * caller-supplied copy. On confirm we bump the
					 * `backDiscardConfirmCounter` so the originating tab
					 * fires its own navigation (e.g. `handleBackNotification`).
					 */ }
					<ConfirmationDialog
						isOpen={ pendingBackDiscard !== null }
						title={ pendingBackDiscard?.title || '' }
						description={ pendingBackDiscard?.description || '' }
						confirmButtonText={
							pendingBackDiscard?.confirmText ||
							__( 'Discard & go back', 'sureforms' )
						}
						cancelButtonText={
							pendingBackDiscard?.cancelText ||
							__( 'Keep editing', 'sureforms' )
						}
						onConfirm={ confirmBackDiscard }
						onCancel={ cancelBackDiscard }
						destructiveConfirmButton={ true }
					/>
					{ /*
					 * Single Toaster mount for the entire dialog. Every
					 * tab + helper goes through `notify` (in @Utils),
					 * which dispatches `requestToast`; the listener above
					 * fires @bsf/force-ui's imperative `toast.success/.error`
					 * on each bump. RTL + z-index config matches the
					 * page-level mounts elsewhere in the admin.
					 */ }
					<Toaster
						position={
							srfm_admin?.is_rtl ? 'top-left' : 'top-right'
						}
						design="stack"
						theme="light"
						autoDismiss={ true }
						dismissAfter={ 5000 }
						className={ cn(
							'z-[999999]',
							srfm_admin?.is_rtl
								? '[&>li>div>div.absolute]:right-auto [&>li>div>div.absolute]:left-[0.75rem!important]'
								: ''
						) }
					/>
				</ForceUIDialog.Panel>
			</ForceUIDialog>,
			renderRoot
		)
	);
};

export default memo( Dialog );
