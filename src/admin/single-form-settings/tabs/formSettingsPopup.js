import { Popover } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { dispatch, select } from '@wordpress/data';

// Lucide Icons
import {
	ShieldCheck as ShieldCheckIcon,
	CircleCheckBig,
	TriangleAlert,
	Cpu,
	File,
	Settings,
	Save,
	UserPlus,
	FileText,
	Code,
	Split,
} from 'lucide-react';
import ottoKitIcon from '@Image/suretriggers-grayscale.svg';

const FormSettingsPopup = ( {
	popoverAnchor,
	setOpenPopover,
	hidePopover,
	setHidePopover,
} ) => {
	const handleTabClick = async ( tabId ) => {
		// Check if editor sidebar is open
		const store = select( 'core/edit-post' );
		const isSidebarOpen = store?.isEditorSidebarOpened();

		// If sidebar is closed, open it
		if ( ! isSidebarOpen ) {
			await dispatch( 'core/edit-post' ).openGeneralSidebar(
				'edit-post/document'
			);
		}

		const dispatchEventForFormSettings = () => {
			const event = new CustomEvent( 'srfm-open-form-settings', {
				detail: { tabId },
			} );
			window.dispatchEvent( event );
		};

		// Deselect any selected block
		dispatch( 'core/block-editor' ).clearSelectedBlock();
		const getFormSettingTab = document.querySelector(
			`[id="edit-post:block"].editor-sidebar [data-tab-id="edit-post/document"]`
		);
		if ( getFormSettingTab ) {
			setTimeout( () => {
				dispatchEventForFormSettings();
			}, 50 );
		} else {
			// Dispatch custom event to open the dialog
			dispatchEventForFormSettings();
		}

		// Close the popover
		setOpenPopover( false );
	};

	const tabs = applyFilters(
		'srfm.formSettings.tabs',
		[
			{
				id: 'email_notification',
				label: __( 'Email Notification', 'sureforms' ),
				icon: <ShieldCheckIcon size={ 18 } />,
			},
			{
				id: 'form_confirmation',
				label: __( 'Form Confirmation', 'sureforms' ),
				icon: <CircleCheckBig size={ 18 } />,
			},
			{
				id: 'conditional-confirmations-preview',
				label: __( 'Conditional Confirmations', 'sureforms' ),
				icon: <Split size={ 18 } />,
			},
			{
				id: 'spam_protection',
				label: __( 'Spam Protection', 'sureforms' ),
				icon: <TriangleAlert size={ 18 } />,
			},
			{
				id: 'integrations-preview',
				label: __( 'Integrations', 'sureforms' ),
				icon: <Cpu size={ 18 } />,
			},
			{
				id: 'pdf-generation-preview',
				label: __( 'PDF Generation', 'sureforms' ),
				icon: <File size={ 18 } />,
			},
			{
				id: 'advanced-settings',
				label: __( 'Advanced Settings', 'sureforms' ),
				icon: <Settings size={ 18 } />,
			},
			{
				id: 'save-resume-preview',
				label: __( 'Save & Progress', 'sureforms' ),
				icon: <Save size={ 18 } />,
			},
			{
				id: 'user-login-preview',
				label: __( 'User Registration', 'sureforms' ),
				icon: <UserPlus size={ 18 } />,
			},
			{
				id: 'post-feed-preview',
				label: __( 'Post Feed', 'sureforms' ),
				icon: <FileText size={ 18 } />,
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
			},
			{
				id: 'form_custom_css',
				label: __( 'Custom CSS', 'sureforms' ),
				icon: <Code size={ 18 } />,
			},
		],
		{
			// Dummy functions required for existing filters that may consume these.
			setSelectedTab: () => {},
			setHasValidationErrors: () => {},
		}
	);

	// Filter out tabs with parent property as they are nested
	const mainTabs = tabs.filter( ( tab ) => ! tab.parent );

	return (
		<>
			<Popover
				resize
				hidden={ hidePopover }
				placement="bottom-end"
				anchor={ popoverAnchor }
				onFocusOutside={ ( event ) => {
					if (
						event.relatedTarget?.className ===
						popoverAnchor.className
					) {
						// Bail if clicked on the Form Settings button.
						return;
					}

					if (
						event.relatedTarget?.className?.includes(
							'media-modal'
						)
					) {
						// Unloading the Popover triggers error when media uploader modal is opened.
						// Don't close the Popover when media uploader is opened, instead just hide the popover.
						setHidePopover( true );
						return;
					}

					setOpenPopover( false );
				} }
				className="srfm-form-settings-popover"
			>
				<div className="srfm-form-settings-container">
					<div className="srfm-form-settings-group">
						{ mainTabs.map( ( tab ) => (
							<button
								key={ tab.id }
								className="srfm-form-settings-nav-item"
								onClick={ () => handleTabClick( tab.id ) }
							>
								{ tab.icon && (
									<span className="srfm-form-settings-nav-item-icon">
										{ tab.icon }
									</span>
								) }
								<span className="srfm-form-settings-nav-item-label">
									{ tab.label }
								</span>
							</button>
						) ) }
					</div>
				</div>
			</Popover>
		</>
	);
};

export default FormSettingsPopup;
