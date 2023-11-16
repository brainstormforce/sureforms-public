import inputAttributes from '@Blocks/input/block.json';
import emailAttributes from '@Blocks/email/block.json';
import numberSliderAttributes from '@Blocks/number-slider/block.json';
import phoneAttributes from '@Blocks/phone/block.json';
import switchAttributes from '@Blocks/switch/block.json';
import textareaAttributes from '@Blocks/textarea/block.json';
import urlAttributes from '@Blocks/url/block.json';
import numberAttributes from '@Blocks/number/block.json';
import passwordAttributes from '@Blocks/password/block.json';
import ratingAttributes from '@Blocks/rating/block.json';
import checkboxAttributes from '@Blocks/checkbox/block.json';
import dateTimeAttributes from '@Blocks/date-time-picker/block.json';
import addressAttributes from '@Blocks/address/block.json';
import multiChoiceAttributes from '@Blocks/multi-choice/block.json';
import dropdownAttributes from '@Blocks/dropdown/block.json';
import uploadAttributes from '@Blocks/upload/block.json';

const default_keys = {
	_srfm_color1: { default: '' },
	_srfm_textcolor1: { default: '' },
	_srfm_color2: { default: '' },
	_srfm_email: { default: sfBlockData.admin_email },
	_srfm_submit_url: { default: '' },
	_srfm_fontsize: { default: 16 },
	_srfm_thankyou_message: { default: 'Form submitted successfully!' },
	_srfm_form_container_width: { default: 650 },
	_srfm_thankyou_message_title: { default: 'Thank you' },
};

export const blocksAttributes = {
	input: inputAttributes.attributes,
	email: emailAttributes.attributes,
	rating: ratingAttributes.attributes,
	checkbox: checkboxAttributes.attributes,
	'date-time-picker': dateTimeAttributes.attributes,
	address: addressAttributes.attributes,
	'multi-choice': multiChoiceAttributes.attributes,
	dropdown: dropdownAttributes.attributes,
	upload: uploadAttributes.attributes,
	'number-slider': numberSliderAttributes.attributes,
	phone: phoneAttributes.attributes,
	switch: switchAttributes.attributes,
	textarea: textareaAttributes.attributes,
	url: urlAttributes.attributes,
	number: numberAttributes.attributes,
	password: passwordAttributes.attributes,
	form_specific: default_keys,
};
