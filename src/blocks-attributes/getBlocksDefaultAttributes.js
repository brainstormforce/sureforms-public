import inputAttributes from '@IncBlocks/input/block.json';
import emailAttributes from '@IncBlocks/email/block.json';
import phoneAttributes from '@IncBlocks/phone/block.json';
import textareaAttributes from '@IncBlocks/textarea/block.json';
import urlAttributes from '@IncBlocks/url/block.json';
import numberAttributes from '@IncBlocks/number/block.json';
import checkboxAttributes from '@IncBlocks/checkbox/block.json';
import addressAttributes from '@IncBlocks/address/block.json';
import multiChoiceAttributes from '@IncBlocks/multichoice/block.json';
import dropdownAttributes from '@IncBlocks/dropdown/block.json';
import gdprAttributes from '@IncBlocks/gdpr/block.json';
import inlineButton from '@IncBlocks/inlinebutton/block.json';
import { getDefaultMessage } from '@Blocks/util';
import { __ } from '@wordpress/i18n';

const defaultKeys = {
	// General Tab
	// Submit button
	_srfm_submit_button_text: { default: __( 'Submit', 'sureforms' ) },
	// Page Break
	first_page_label: { default: __( 'Page Break Label', 'sureforms' ) },
	next_button_text: { default: __( 'Next', 'sureforms' ) },
	back_button_text: { default: __( 'Back', 'sureforms' ) },
	// Style Tab
	// Form Container
	primary_color: { default: '#111C44' },
	text_color: { default: '#1E1E1E' },
	text_color_on_primary: { default: '#FFFFFF' },
	form_container_width: { default: 620 },
	cover_color: { default: '#111C44' },
	bg_color: { default: '#ffffff' },
	gradient_type: { default: 'basic' },
	bg_gradient_type: { default: 'linear' },
	bg_gradient_angle: { default: 90 },
	bg_gradient_color_1: { default: '#FFC9B2' },
	bg_gradient_color_2: { default: '#C7CBFF' },
	bg_gradient_location_1: { default: 0 },
	bg_gradient_location_2: { default: 100 },
	bg_overlay_custom_size: { default: 100 },
	bg_overlay_custom_size_unit: { default: '%' },
	bg_image_size_custom: { default: 100 },
	bg_image_size_custom_unit: { default: '%' },
	bg_overlay_opacity: { default: 1 },
	bg_image_overlay_color: { default: '#FFFFFF75' },
	overlay_gradient_type: { default: 'basic' },
	bg_overlay_gradient_type: { default: 'linear' },
	bg_overlay_gradient_angle: { default: 90 },
	bg_overlay_gradient_color_1: { default: '#FFC9B2' },
	bg_overlay_gradient_color_2: { default: '#C7CBFF' },
	bg_overlay_gradient_location_1: { default: 0 },
	bg_overlay_gradient_location_2: { default: 100 },
};

export const blocksAttributes = {
	input: {
		...inputAttributes.attributes,
		errorMsg: getDefaultMessage( 'srfm_input_block_required_text' ),
		duplicateMsg: getDefaultMessage( 'srfm_input_block_unique_text' ),
	},
	email: {
		...emailAttributes.attributes,
		errorMsg: getDefaultMessage( 'srfm_email_block_required_text' ),
		duplicateMsg: getDefaultMessage( 'srfm_email_block_unique_text' ),
	},
	checkbox: {
		...checkboxAttributes.attributes,
		errorMsg: getDefaultMessage( 'srfm_checkbox_block_required_text' ),
	},
	address: {
		...addressAttributes.attributes,
	},
	'multi-choice': {
		...multiChoiceAttributes.attributes,
		errorMsg: getDefaultMessage( 'srfm_multi_choice_block_required_text' ),
	},
	dropdown: {
		...dropdownAttributes.attributes,
		errorMsg: getDefaultMessage( 'srfm_dropdown_block_required_text' ),
	},
	phone: {
		...phoneAttributes.attributes,
		errorMsg: getDefaultMessage( 'srfm_phone_block_required_text' ),
		duplicateMsg: getDefaultMessage( 'srfm_phone_block_unique_text' ),
	},
	textarea: {
		...textareaAttributes.attributes,
		errorMsg: getDefaultMessage( 'srfm_textarea_block_required_text' ),
	},
	url: {
		...urlAttributes.attributes,
		errorMsg: getDefaultMessage( 'srfm_url_block_required_text' ),
	},
	number: {
		...numberAttributes.attributes,
		errorMsg: getDefaultMessage( 'srfm_number_block_required_text' ),
	},
	gdpr: {
		...gdprAttributes.attributes,
		errorMsg: getDefaultMessage( 'srfm_gdpr_block_required_text' ),
	},
	'inline-button': {
		...inlineButton.attributes,
	},
	form_specific: defaultKeys,
};
