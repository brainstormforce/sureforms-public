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

const default_keys = {
	_srfm_color1: { default: '#0184C7' },
	_srfm_textcolor1: { default: '' },
	_srfm_color2: { default: '' },
	_srfm_email: { default: sfBlockData.admin_email },
	_srfm_submit_url: { default: '' },
	_srfm_fontsize: { default: 20 },
	_srfm_thankyou_message: { default: 'Form submitted successfully!' },
	_srfm_form_container_width: { default: 650 },
	_srfm_thankyou_message_title: { default: 'Thank you' },
	_srfm_submit_button_text: { default: 'SUBMIT' },
	_srfm_label_color: { default: '#1f2937' },
	_srfm_help_color: { default: '#6b7280' },
	_srfm_input_text_color: { default: '#4B5563' },
	_srfm_input_placeholder_color: { default: '#9CA3AF' },
	_srfm_input_bg_color: { default: '#ffffff' },
	_srfm_input_border_color: { default: '#D0D5DD' },
	_srfm_input_border_width: { default: 1 },
	_srfm_input_border_radius: { default: 0 },
	_srfm_field_error_color: { default: '#DC2626' },
	_srfm_field_error_surface_color: { default: '#EF4444' },
	_srfm_field_error_bg_color: { default: '#FEF2F2' },
	_srfm_button_text_color: { default: '#ffffff' },
	_srfm_button_color: { default: '#000000' },
	_srfm_button_bg_color: { default: '#0184C7' },
	_srfm_button_border_color: { default: '#ffffff' },
	_srfm_button_border_width: { default: 1 },
	_srfm_button_border_radius: { default: 6 },
	_srfm_first_page_label: { default: 'Page break' },
	_srfm_previous_button_text: { default: 'Previous' },
	_srfm_next_button_text: { default: 'Next' },
	_srfm_bg_color: { default: '#ffffff' },
};

export const blocksAttributes = {
	input: inputAttributes.attributes,
	email: emailAttributes.attributes,
	checkbox: checkboxAttributes.attributes,
	address: addressAttributes.attributes,
	'multi-choice': multiChoiceAttributes.attributes,
	dropdown: dropdownAttributes.attributes,
	phone: phoneAttributes.attributes,
	textarea: textareaAttributes.attributes,
	url: urlAttributes.attributes,
	number: numberAttributes.attributes,
	form_specific: default_keys,
};
