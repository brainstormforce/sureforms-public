import inputAttributes from '@Blocks/input/block.json';
import emailAttributes from '@Blocks/email/block.json';
import phoneAttributes from '@Blocks/phone/block.json';
import textareaAttributes from '@Blocks/textarea/block.json';
import urlAttributes from '@Blocks/url/block.json';
import numberAttributes from '@Blocks/number/block.json';
import checkboxAttributes from '@Blocks/checkbox/block.json';
import addressAttributes from '@Blocks/address/block.json';
import multiChoiceAttributes from '@Blocks/multi-choice/block.json';
import dropdownAttributes from '@Blocks/dropdown/block.json';
import gdprAttributes from '@Blocks/gdpr/block.json';
import inlineButton from '@Blocks/inline-button/block.json';
import { getDefaultMessage } from '@Blocks/util';

const defaultKeys = {
	// General Tab
	// Submit button
	_srfm_submit_button_text: { default: 'Submit' },
	// Page Break
	_srfm_first_page_label: { default: 'Page break' },
	_srfm_previous_button_text: { default: 'Previous' },
	_srfm_next_button_text: { default: 'Next' },
	// Style Tab
	// Form Container
	primary_color: { default: '#0C78FB' },
	text_color: { default: '#1E1E1E' },
	text_color_on_primary: { default: '#FFFFFF' },
	form_container_width: { default: 650 },
	cover_color: { default: '#0C78FB' },
	bg_color: { default: '#ffffff' },
	// Page Break
	_srfm_page_break_button_text_color: { default: '#ffffff' },
	_srfm_page_break_button_bg_color: { default: '#D54407' },
	_srfm_page_break_button_border_color: { default: '#ffffff' },
	_srfm_page_break_button_border_width: { default: 0 },
	_srfm_page_break_button_border_radius: { default: 4 },
	// Advanced Tab
	_srfm_thankyou_message_title: { default: 'Thank you' },
	_srfm_thankyou_message: { default: 'Form submitted successfully!' },
	_srfm_submit_url: { default: '' },
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
