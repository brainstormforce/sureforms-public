import { getBorderAttributes } from '@Controls/generateAttributes';

const inputFieldborderAttributes = getBorderAttributes( 'inputField' );
const btnborderAttributes = getBorderAttributes( 'submitButton' );

const attributes = {
	...btnborderAttributes,
	...inputFieldborderAttributes,
	block_id: {
		type: 'string',
	},
	classMigrate: {
		type: 'boolean',
		default: false,
	},
	className: {
		type: 'string',
	},
	// General.
	generalPrimaryColor: {
		type: 'string',
		default: '',
	},
	// general font family.
	generalLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	generalFontFamily: {
		type: 'string',
	},
	generalFontWeight: {
		type: 'string',
	},
	generalFontSubset: {
		type: 'string',
	},
	// general font size.
	generalFontSize: {
		type: 'number',
	},
	generalFontSizeType: {
		type: 'string',
		default: 'px',
	},
	generalFontSizeTablet: {
		type: 'number',
	},
	generalFontSizeMobile: {
		type: 'number',
	},
	// general line height.
	generalLineHeightType: {
		type: 'string',
		default: 'em',
	},
	generalLineHeight: {
		type: 'number',
	},
	generalLineHeightTablet: {
		type: 'number',
	},
	generalLineHeightMobile: {
		type: 'number',
	},
	generalTransform: {
		type: 'string',
		default: 'none',
	},
	generalLetterSpacing: {
		type: 'number',
		default: '',
	},
	generalLetterSpacingTablet: {
		type: 'number',
		default: '',
	},
	generalLetterSpacingMobile: {
		type: 'number',
		default: '',
	},
	generalLetterSpacingType: {
		type: 'string',
		default: 'px',
	},
	// Input Fields.
	// input field font family.
	inputFieldLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	inputFieldFontFamily: {
		type: 'string',
	},
	inputFieldFontWeight: {
		type: 'string',
	},
	inputFieldFontSubset: {
		type: 'string',
	},
	// input field font size.
	inputFieldFontSize: {
		type: 'number',
	},
	inputFieldFontSizeType: {
		type: 'string',
		default: 'px',
	},
	inputFieldFontSizeTablet: {
		type: 'number',
	},
	inputFieldFontSizeMobile: {
		type: 'number',
	},
	// input field line height.
	inputFieldLineHeightType: {
		type: 'string',
		default: 'em',
	},
	inputFieldLineHeight: {
		type: 'number',
	},
	inputFieldLineHeightTablet: {
		type: 'number',
	},
	inputFieldLineHeightMobile: {
		type: 'number',
	},
	inputFieldLabelColor: {
		type: 'string',
		default: '',
	},
	inputFieldBackgroundColor: {
		type: 'string',
		default: '',
	},
	inputFieldTextPlaceholderColor: {
		type: 'string',
		default: '',
	},
	// border.
	inputFieldBorderStyle: {
		type: 'string',
		default: 'solid',
	},
	inputFieldBorderWidth: {
		type: 'number',
		default: '',
	},
	inputFieldBorderRadius: {
		type: 'number',
		default: '',
	},
	inputFieldBorderColor: {
		type: 'string',
		default: '',
	},
	inputFieldBorderHoverColor: {
		type: 'string',
		default: '',
	},

	// Submit Button.
	// submit button font family.

	submitButtonLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	submitButtonFontFamily: {
		type: 'string',
	},
	submitButtonFontWeight: {
		type: 'string',
	},
	submitButtonFontSubset: {
		type: 'string',
	},
	// submit button font size.
	submitButtonFontSize: {
		type: 'number',
	},
	submitButtonFontSizeType: {
		type: 'string',
		default: 'px',
	},
	submitButtonFontSizeTablet: {
		type: 'number',
	},
	submitButtonFontSizeMobile: {
		type: 'number',
	},
	// submit button line height.
	submitButtonLineHeightType: {
		type: 'string',
		default: 'em',
	},
	submitButtonLineHeight: {
		type: 'number',
	},
	submitButtonLineHeightTablet: {
		type: 'number',
	},
	submitButtonLineHeightMobile: {
		type: 'number',
	},
	submitButtonTextColor: {
		type: 'string',
		default: '',
	},
	submitButtonBackgroundColor: {
		type: 'string',
		default: '',
	},
	submitButtonTextHoverColor: {
		type: 'string',
		default: '',
	},
	submitButtonBackgroundHoverColor: {
		type: 'string',
		default: '',
	},
	// border.
	submitButtonBorderStyle: {
		type: 'string',
		default: '',
	},
	submitButtonBorderWidth: {
		type: 'number',
		default: '',
	},
	submitButtonBorderRadius: {
		type: 'number',
		default: '',
	},
	submitButtonBorderColor: {
		type: 'string',
		default: '',
	},
	submitButtonBorderHoverColor: {
		type: 'string',
		default: '',
	},

	boxShadowColor: {
		type: 'string',
		default: '',
	},
	boxShadowHOffset: {
		type: 'number',
	},
	boxShadowVOffset: {
		type: 'number',
	},
	boxShadowBlur: {
		type: 'number',
	},
	boxShadowSpread: {
		type: 'number',
	},
	boxShadowPosition: {
		type: 'string',
		default: 'outset',
	},
	input_skins: {
		type: 'string',
		default: '',
	},
	deviceType: {
		type: 'string',
		default: 'Desktop',
	},

	generalFontStyle: {
		type: 'string',
		default: '',
	},
	submitButtonFontStyle: {
		type: 'string',
		default: '',
	},
	inputFieldFontStyle: {
		type: 'string',
		default: '',
	},
	submitButtonTransform: {
		type: 'string',
		default: '',
	},
	submitButtonLetterSpacing: {
		type: 'number',
		default: '',
	},
	submitButtonLetterSpacingTablet: {
		type: 'number',
		default: '',
	},
	submitButtonLetterSpacingMobile: {
		type: 'number',
		default: '',
	},
	submitButtonLetterSpacingType: {
		type: 'string',
		default: 'px',
	},
	inputFieldTransform: {
		type: 'string',
		default: '',
	},
	inputFieldLetterSpacing: {
		type: 'number',
		default: '',
	},
	inputFieldLetterSpacingTablet: {
		type: 'number',
		default: '',
	},
	inputFieldLetterSpacingMobile: {
		type: 'number',
		default: '',
	},
	inputFieldLetterSpacingType: {
		type: 'string',
		default: 'px',
	},
};
export default attributes;
