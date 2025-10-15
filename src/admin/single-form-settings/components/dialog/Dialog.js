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
} from 'lucide-react';

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
				id: 'ottokit',
				label: __( 'OttoKit', 'sureforms' ),
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
