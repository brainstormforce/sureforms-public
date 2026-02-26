import { __ } from '@wordpress/i18n';
const { select, dispatch } = wp.data;

export const defaultKeys = {
	// General Tab
	_srfm_use_label_as_placeholder: false,
	_srfm_single_page_form_title: true,
	_srfm_instant_form: false,
	_srfm_is_inline_button: false,
	// Submit Button
	_srfm_submit_button_text: __( 'Submit', 'sureforms' ),
	// Page Break
	_srfm_is_page_break: false,
	_srfm_first_page_label: __( 'Page break', 'sureforms' ),
	_srfm_page_break_progress_indicator: 'connector',
	_srfm_page_break_toggle_label: false,
	_srfm_previous_button_text: __( 'Previous', 'sureforms' ),
	_srfm_next_button_text: __( 'Next', 'sureforms' ),
	// Style Tab
	// Form Container
	_srfm_form_container_width: 650,
	_srfm_bg_type: 'image',
	_srfm_bg_image: '',
	_srfm_cover_image: '',
	_srfm_bg_color: '#ffffff',
	// Submit Button
	_srfm_inherit_theme_button: false,
	_srfm_submit_alignment: 'left',
	_srfm_submit_width: '',
	_srfm_submit_alignment_backend: '100%',
	_srfm_submit_width_backend: 'max-content',
	_srfm_additional_classes: '',
	// Page Break Button
	_srfm_page_break_inherit_theme_button: false,
	_srfm_page_break_button_bg_color: '#D54407',
	_srfm_page_break_button_text_color: '#ffffff',
	_srfm_page_break_button_border_color: '#ffffff',
	_srfm_page_break_button_border_width: 0,
	_srfm_page_break_button_border_radius: 4,
	_srfm_page_break_button_bg_type: 'filled',

	// Advanced Tab
	// Success Message
	_srfm_submit_type: 'message',
	_srfm_thankyou_message_title: __( 'Thank you', 'sureforms' ),
	_srfm_thankyou_message: __( 'Form submitted successfully!', 'sureforms' ),
	_srfm_submit_url: '',
	_srfm_form_recaptcha: 'none',
};

//force panel open
export const forcePanel = () => {
	//force sidebar open
	if ( ! select( 'core/edit-post' ).isEditorSidebarOpened() ) {
		dispatch( 'core/edit-post' ).openGeneralSidebar( 'edit-post/document' );
	}
	//force panel open
	if (
		! select( 'core/editor' ).isEditorPanelEnabled(
			'srfm-form-specific-settings/srfm-sidebar'
		)
	) {
		dispatch( 'core/edit-post' ).toggleEditorPanelEnabled(
			'srfm-form-specific-settings/srfm-sidebar'
		);
	}
	if (
		! select( 'core/editor' ).isEditorPanelOpened(
			'srfm-form-specific-settings/srfm-sidebar'
		)
	) {
		dispatch( 'core/edit-post' ).toggleEditorPanelOpened(
			'srfm-form-specific-settings/srfm-sidebar'
		);
	}
};

export const validateClassName = ( className ) => {
	// Regular expression to validate a Unicode-aware CSS class name
	const classNameRegex = /^[^\d\-_][\w\p{L}\p{N}\-_]*$/u;

	// Check if the className matches the pattern
	return classNameRegex.test( className );
};
