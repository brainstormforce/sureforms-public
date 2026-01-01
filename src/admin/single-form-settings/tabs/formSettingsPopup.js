import { Popover } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { dispatch } from '@wordpress/data';

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
} from 'lucide-react';

const FormSettingsPopup = ( {
	popoverAnchor,
	setOpenPopover,
	hidePopover,
	setHidePopover,
} ) => {
	const handleTabClick = ( tabId ) => {
		// Deselect any selected block
		dispatch( 'core/block-editor' ).clearSelectedBlock();
		const getFormSettingTab = document.querySelector(
			`[id="edit-post:block"].editor-sidebar [data-tab-id="edit-post/document"]`
		);
		if ( getFormSettingTab ) {
			setTimeout( () => {
				// Dispatch custom event to open the dialog
				const event = new CustomEvent( 'srfm-open-form-settings', {
					detail: { tabId },
				} );
				window.dispatchEvent( event );
			}, 50 );
		} else {
			// Dispatch custom event to open the dialog
			const event = new CustomEvent( 'srfm-open-form-settings', {
				detail: { tabId },
			} );
			window.dispatchEvent( event );
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
