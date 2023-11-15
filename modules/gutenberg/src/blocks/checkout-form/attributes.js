import { getBorderAttributes } from '@Controls/generateAttributes';

const btnborderAttributes = getBorderAttributes( 'button' );
const fieldborderAttributes = getBorderAttributes( 'field' );

const attributes = {
	...btnborderAttributes,
	...fieldborderAttributes,
	block_id: {
		type: 'string',
	},
	className: {
		type: 'string',
	},
	boxShadowColor: {
		type: 'string',
		default: '',
	},
	boxShadowHOffset: {
		type: 'number',
		default: 0,
	},
	boxShadowVOffset: {
		type: 'number',
		default: 0,
	},
	boxShadowBlur: {
		type: 'number',
		default: 0,
	},
	boxShadowSpread: {
		type: 'number',
		default: 0,
	},
	boxShadowPosition: {
		type: 'string',
		default: 'outset',
	},
	isHtml: {
		type: 'boolean',
	},
	showprecheckoutoffer: {
		type: 'boolean',
		default: false,
	},
	formJson: {
		type: 'object',
		default: null,
	},
	fieldVrPadding: {
		type: 'number',
		default: 10,
	},
	fieldHrPadding: {
		type: 'number',
		default: 10,
	},
	headBgColor: {
		type: 'string',
		default: '',
	},
	fieldBgColor: {
		type: 'string',
		default: '',
	},
	fieldLabelColor: {
		type: 'string',
		default: '',
	},
	fieldInputColor: {
		type: 'string',
		default: '',
	},
	fieldBorderStyle: {
		type: 'string',
		default: 'solid',
	},
	fieldBorderWidth: {
		type: 'number',
		default: '',
	},
	fieldBorderRadius: {
		type: 'number',
		default: '',
	},
	fieldBorderColor: {
		type: 'string',
		default: '',
	},
	fieldBorderHoverColor: {
		type: 'string',
		default: '',
	},
	buttonAlignment: {
		type: 'string',
		default: 'left',
	},
	buttonVrPadding: {
		type: 'number',
		default: 10,
	},
	buttonHrPadding: {
		type: 'number',
		default: 25,
	},
	buttonBorderStyle: {
		type: 'string',
		default: '',
	},
	buttonBorderWidth: {
		type: 'number',
		default: '',
	},
	buttonBorderRadius: {
		type: 'number',
		default: '',
	},
	buttonBorderColor: {
		type: 'string',
		default: '',
	},
	buttonBorderHoverColor: {
		type: 'string',
		default: '',
	},
	buttonTextColor: {
		type: 'string',
		default: '',
	},
	buttonBgColor: {
		type: 'string',
		default: '',
	},
	buttonTextHoverColor: {
		type: 'string',
		default: '',
	},
	buttonBgHoverColor: {
		type: 'string',
		default: '',
	},
	fieldSpacing: {
		type: 'number',
		default: '',
	},
	fieldLabelSpacing: {
		type: 'number',
		default: '',
	},
	inputFontSize: {
		type: 'number',
		default: '',
	},
	inputFontSizeType: {
		type: 'string',
		default: 'px',
	},
	inputFontSizeTablet: {
		type: 'number',
	},
	inputFontSizeMobile: {
		type: 'number',
	},
	inputFontFamily: {
		type: 'string',
		default: 'Default',
	},
	inputFontWeight: {
		type: 'string',
	},
	inputFontSubset: {
		type: 'string',
	},
	inputLineHeightType: {
		type: 'string',
		default: 'em',
	},
	inputLineHeight: {
		type: 'number',
	},
	inputLineHeightTablet: {
		type: 'number',
	},
	inputLineHeightMobile: {
		type: 'number',
	},
	inputLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	buttonFontSize: {
		type: 'number',
		default: '',
	},
	buttonFontSizeType: {
		type: 'string',
		default: 'px',
	},
	buttonFontSizeTablet: {
		type: 'number',
	},
	buttonFontSizeMobile: {
		type: 'number',
	},
	buttonFontFamily: {
		type: 'string',
		default: 'Default',
	},
	buttonFontWeight: {
		type: 'string',
	},
	buttonFontSubset: {
		type: 'string',
	},
	buttonLineHeightType: {
		type: 'string',
		default: 'em',
	},
	buttonLineHeight: {
		type: 'number',
	},
	buttonLineHeightTablet: {
		type: 'number',
	},
	buttonLineHeightMobile: {
		type: 'number',
	},
	buttonLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	errorMsgColor: {
		type: 'string',
		default: '',
	},
	errorMsgBgColor: {
		type: 'string',
		default: '',
	},
	errorMsgBorderColor: {
		type: 'string',
		default: '',
	},
	msgBorderSize: {
		type: 'number',
		default: '',
	},
	msgBorderRadius: {
		type: 'number',
		default: '',
	},
	msgVrPadding: {
		type: 'number',
		default: '',
	},
	msgHrPadding: {
		type: 'number',
		default: '',
	},
	msgBorderRadiusType: {
		type: 'string',
		default: 'px',
	},
	fieldBorderRadiusType: {
		type: 'string',
		default: 'px',
	},
	buttonBorderRadiusType: {
		type: 'string',
		default: 'px',
	},
	paymentdescriptionColor: {
		type: 'string',
		default: '',
	},
	paymenttitleColor: {
		type: 'string',
		default: '',
	},
	sectionbgColor: {
		type: 'string',
		default: '',
	},
	informationbgColor: {
		type: 'string',
		default: 'px',
	},
	sectionBorderRadius: {
		type: 'number',
		default: '',
	},
	sectionhrPadding: {
		type: 'string',
		default: '',
	},
	sectionvrPadding: {
		type: 'string',
		default: '',
	},
	sectionhrMargin: {
		type: 'string',
		default: '',
	},
	sectionvrMargin: {
		type: 'string',
		default: '',
	},
	headFontSize: {
		type: 'string',
		default: '',
	},
	headFontSizeType: {
		type: 'string',
		default: 'px',
	},
	headFontSizeTablet: {
		type: 'string',
		default: '',
	},
	headFontSizeMobile: {
		type: 'string',
		default: '',
	},
	headFontFamily: {
		type: 'string',
		default: '',
	},
	headFontWeight: {
		type: 'string',
		default: '',
	},
	headFontSubset: {
		type: 'string',
		default: '',
	},
	headLineHeightType: {
		type: 'string',
		default: '',
	},
	headLineHeight: {
		type: 'string',
		default: '',
	},
	headLineHeightTablet: {
		type: 'string',
		default: '',
	},
	headLineHeightMobile: {
		type: 'string',
		default: '',
	},
	headLoadGoogleFonts: {
		type: 'string',
		default: '',
	},
	globaltextColor: {
		type: 'string',
		default: '',
	},
	globalbgColor: {
		type: 'string',
		default: '',
	},
	globalFontSize: {
		type: 'number',
		default: '',
	},
	globalFontSizeType: {
		type: 'string',
		default: 'px',
	},
	globalFontSizeTablet: {
		type: 'number',
	},
	globalFontSizeMobile: {
		type: 'number',
	},
	globalFontFamily: {
		type: 'string',
		default: 'Default',
	},
	globalFontWeight: {
		type: 'string',
	},
	globalFontSubset: {
		type: 'string',
	},
	globalLineHeightType: {
		type: 'string',
		default: 'em',
	},
	globalLineHeight: {
		type: 'number',
	},
	globalLineHeightTablet: {
		type: 'number',
	},
	globalLineHeightMobile: {
		type: 'number',
	},
	globalLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	backgroundType: {
		type: 'string',
		default: 'color',
	},
	backgroundImage: {
		type: 'object',
	},
	backgroundPosition: {
		type: 'string',
		default: 'center-center',
	},
	backgroundSize: {
		type: 'string',
		default: 'cover',
	},
	backgroundRepeat: {
		type: 'string',
		default: 'no-repeat',
	},
	backgroundAttachment: {
		type: 'string',
		default: 'scroll',
	},
	backgroundOpacity: {
		type: 'number',
	},
	backgroundImageColor: {
		type: 'string',
		default: '',
	},
	backgroundColor: {
		type: 'string',
		default: '',
	},
	backgroundHoverColor: {
		type: 'string',
		default: '',
	},
	gradientColor1: {
		type: 'string',
		default: '#f16334',
	},
	gradientColor2: {
		type: 'string',
		default: '#f16334',
	},
	gradientType: {
		type: 'string',
		default: 'linear',
	},
	gradientLocation1: {
		type: 'number',
		default: 0,
	},
	gradientLocation2: {
		type: 'number',
		default: 100,
	},
	gradientAngle: {
		type: 'number',
		default: 0,
	},
	gradientPosition: {
		type: 'string',
		default: 'center center',
	},
	gradientValue: {
		type: 'string',
		default: '',
	},
	errorLabelColor: {
		type: 'string',
		default: '',
	},
	orderReviewColumnColor: {
		type: 'string',
		default: '#ffffff',
	},
	orderReviewColumnTextColor: {
		type: 'string',
		default: '#555555',
	},
	errorFieldBorderColor: {
		type: 'string',
		default: '',
	},

	inputSkins: {
		type: 'string',
		default: 'modern-label',
	},
	layout: {
		type: 'string',
		default: 'modern-checkout',
	},
	deviceType: {
		type: 'string',
		default: 'Desktop',
	},

	//New attrs.
	paymentSectionpaddingTop: {
		type: 'string',
		default: '',
	},
	paymentSectionpaddingBottom: {
		type: 'string',
		default: '',
	},
	paymentSectionpaddingLeft: {
		type: 'string',
		default: '',
	},
	paymentSectionpaddingRight: {
		type: 'string',
		default: '',
	},
	paymentSectionpaddingTopTablet: {
		type: 'string',
		default: '',
	},
	paymentSectionpaddingRightTablet: {
		type: 'string',
		default: '',
	},
	paymentSectionpaddingBottomTablet: {
		type: 'string',
		default: '',
	},
	paymentSectionpaddingLeftTablet: {
		type: 'string',
		default: '',
	},
	paymentSectionpaddingTopMobile: {
		type: 'string',
		default: '',
	},
	paymentSectionpaddingRightMobile: {
		type: 'string',
		default: '',
	},
	paymentSectionpaddingBottomMobile: {
		type: 'string',
		default: '',
	},
	paymentSectionpaddingLeftMobile: {
		type: 'string',
		default: '',
	},

	paymentSectionpaddingType: {
		type: 'string',
		default: 'px',
	},
	paymentSectionpaddingTypeTablet: {
		type: 'string',
		default: 'px',
	},
	paymentSectionpaddingTypeMobile: {
		type: 'string',
		default: 'px',
	},

	paymentSectionMarginTop: {
		type: 'string',
		default: '',
	},
	paymentSectionMarginBottom: {
		type: 'string',
		default: '',
	},
	paymentSectionMarginLeft: {
		type: 'string',
		default: '',
	},
	paymentSectionMarginRight: {
		type: 'string',
		default: '',
	},
	paymentSectionMarginTopTablet: {
		type: 'string',
		default: '',
	},
	paymentSectionMarginRightTablet: {
		type: 'string',
		default: '',
	},
	paymentSectionMarginBottomTablet: {
		type: 'string',
		default: '',
	},
	paymentSectionMarginLeftTablet: {
		type: 'string',
		default: '',
	},
	paymentSectionMarginTopMobile: {
		type: 'string',
		default: '',
	},
	paymentSectionMarginRightMobile: {
		type: 'string',
		default: '',
	},
	paymentSectionMarginBottomMobile: {
		type: 'string',
		default: '',
	},
	paymentSectionMarginLeftMobile: {
		type: 'string',
		default: '',
	},

	paymentSectionMarginType: {
		type: 'string',
		default: 'px',
	},
	paymentSectionMarginTypeTablet: {
		type: 'string',
		default: 'px',
	},
	paymentSectionMarginTypeMobile: {
		type: 'string',
		default: 'px',
	},
	spacingLink: {
		type: 'string',
		default: '',
	},

	paymentSectionpaddingTypeDesktop: {
		type: 'string',
		default: 'px',
	},

	paymentSectionMarginTypeDesktop: {
		type: 'string',
		default: 'px',
	},

	buttonFontStyle: {
		type: 'string',
		default: '',
	},
	inputFontStyle: {
		type: 'string',
		default: '',
	},
	globalFontStyle: {
		type: 'string',
		default: '',
	},
	headFontStyle: {
		type: 'string',
		default: '',
	},
	buttonTransform: {
		type: 'string',
		default: '',
	},
	buttonLetterSpacing: {
		type: 'number',
		default: '',
	},
	buttonLetterSpacingTablet: {
		type: 'number',
		default: '',
	},
	buttonLetterSpacingMobile: {
		type: 'number',
		default: '',
	},
	buttonLetterSpacingType: {
		type: 'string',
		default: 'px',
	},

	inputTransform: {
		type: 'string',
		default: '',
	},
	inputLetterSpacing: {
		type: 'number',
		default: '',
	},
	inputLetterSpacingTablet: {
		type: 'number',
		default: '',
	},
	inputLetterSpacingMobile: {
		type: 'number',
		default: '',
	},
	inputLetterSpacingType: {
		type: 'string',
		default: 'px',
	},
	headTransform: {
		type: 'string',
		default: '',
	},
	headLetterSpacing: {
		type: 'number',
		default: '',
	},
	headLetterSpacingTablet: {
		type: 'number',
		default: '',
	},
	headLetterSpacingMobile: {
		type: 'number',
		default: '',
	},
	headLetterSpacingType: {
		type: 'string',
		default: 'px',
	},
	globalTransform: {
		type: 'string',
		default: 'none',
	},
	globalLetterSpacing: {
		type: 'number',
		default: '',
	},
	globalLetterSpacingTablet: {
		type: 'number',
		default: '',
	},
	globalLetterSpacingMobile: {
		type: 'number',
		default: '',
	},
	globalLetterSpacingType: {
		type: 'string',
		default: 'px',
	},
};
export default attributes;
