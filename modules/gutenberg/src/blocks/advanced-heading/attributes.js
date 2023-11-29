import { getBorderAttributes } from '@Controls/generateAttributes';
import { __ } from '@wordpress/i18n';

const highLightBorderAttributes = getBorderAttributes( 'highLight' );

const attributes = {
	block_id: {
		type: 'string',
	},
	classMigrate: {
		type: 'boolean',
		default: false,
	},
	blockBackgroundType: {
		type: 'string',
		default: 'classic',
	},
	blockGradientBackground: {
		type: 'string',
		default:
			'linear-gradient(90deg, rgb(6, 147, 227) 0%, rgb(155, 81, 224) 100%)',
	},
	blockBackground: {
		type: 'string',
	},
	headingTitleToggle: {
		type: 'boolean',
		default: true,
	},
	headingTitle: {
		source: 'html',
		selector: '.uagb-heading-text',
		default: __( 'Your Attractive Heading', 'sureforms' ),
	},
	headingId: {
		type: 'string',
	},
	headingDescToggle: {
		type: 'boolean',
		default: false,
	},
	headingDescPosition: {
		type: 'string',
		default: 'below-heading',
	},
	headingDesc: {
		source: 'html',
		selector: '.uagb-desc-text',
	},
	headingAlign: {
		type: 'string',
		default: 'left',
	},
	headingAlignTablet: {
		type: 'string',
		default: '',
	},
	headingAlignMobile: {
		type: 'string',
		default: '',
	},
	headingColorType: {
		type: 'string',
		default: 'classic',
	},
	headingColor: {
		type: 'string',
	},
	headingGradientColor: {
		type: 'string',
		default:
			'linear-gradient(90deg, rgb(155, 81, 224) 0%, rgb(6, 147, 227) 100%)',
	},
	subHeadingColor: {
		type: 'string',
	},
	separatorColor: {
		type: 'string',
		default: '#0170b9',
	},
	headingTag: {
		type: 'string',
		default: 'h2',
	},
	level: {
		type: 'number',
		default: 2,
	},
	separatorStyle: {
		type: 'string',
		default: 'none',
	},
	separatorPosition: {
		type: 'string',
		default: 'below-heading',
	},
	separatorHeight: {
		type: 'number',
		default: 2,
	},
	separatorHeightType: {
		type: 'string',
		default: 'px',
	},
	separatorWidth: {
		type: 'number',
		default: 12,
	},
	separatorWidthTablet: {
		type: 'number',
	},
	separatorWidthMobile: {
		type: 'number',
	},
	separatorWidthType: {
		type: 'string',
		default: '%',
	},
	headSpace: {
		type: 'number',
		default: 15,
	},
	headSpaceMobile: {
		type: 'number',
		default: '',
	},
	headSpaceTablet: {
		type: 'number',
		default: '',
	},
	headSpaceType: {
		type: 'string',
		default: 'px',
	},
	subHeadSpace: {
		type: 'number',
		default: 15,
	},
	subHeadSpaceMobile: {
		type: 'number',
		default: '',
	},
	subHeadSpaceTablet: {
		type: 'number',
		default: '',
	},
	subHeadSpaceType: {
		type: 'string',
		default: 'px',
	},
	headFontFamily: {
		type: 'string',
		default: 'Default',
	},
	headFontWeight: {
		type: 'string',
	},
	headFontStyle: {
		type: 'string',
		default: 'normal',
	},
	headTransform: {
		type: 'string',
	},
	headDecoration: {
		type: 'string',
	},
	headFontSizeType: {
		type: 'string',
		default: 'px',
	},
	headFontSizeTypeMobile: {
		type: 'string',
		default: 'px',
	},
	headFontSizeTypeTablet: {
		type: 'string',
		default: 'px',
	},
	headLineHeightType: {
		type: 'string',
		default: 'em',
	},
	headFontSize: {
		type: 'number',
	},
	headFontSizeTablet: {
		type: 'number',
	},
	headFontSizeMobile: {
		type: 'number',
	},
	headLineHeight: {
		type: 'number',
	},
	headLineHeightTablet: {
		type: 'number',
	},
	headLineHeightMobile: {
		type: 'number',
	},
	headLetterSpacing: {
		type: 'number',
	},
	headLetterSpacingTablet: {
		type: 'number',
	},
	headLetterSpacingMobile: {
		type: 'number',
	},
	headLetterSpacingType: {
		type: 'string',
		default: 'px',
	},
	headShadowColor: {
		type: 'string',
		default: '',
	},
	headShadowHOffset: {
		type: 'number',
		default: 0,
	},
	headShadowVOffset: {
		type: 'number',
		default: 0,
	},
	headShadowBlur: {
		type: 'number',
		default: 10,
	},
	// sub headline.
	subHeadFontFamily: {
		type: 'string',
		default: 'Default',
	},
	subHeadFontWeight: {
		type: 'string',
	},
	subHeadFontStyle: {
		type: 'string',
		default: 'normal',
	},
	subHeadTransform: {
		type: 'string',
	},
	subHeadDecoration: {
		type: 'string',
	},
	subHeadFontSize: {
		type: 'number',
	},
	subHeadFontSizeType: {
		type: 'string',
		default: 'px',
	},
	subHeadFontSizeTypeMobile: {
		type: 'string',
		default: 'px',
	},
	subHeadFontSizeTypeTablet: {
		type: 'string',
		default: 'px',
	},
	subHeadFontSizeTablet: {
		type: 'number',
	},
	subHeadFontSizeMobile: {
		type: 'number',
	},
	subHeadLineHeight: {
		type: 'number',
	},
	subHeadLineHeightType: {
		type: 'string',
		default: 'em',
	},
	subHeadLineHeightTablet: {
		type: 'number',
	},
	subHeadLineHeightMobile: {
		type: 'number',
	},
	subHeadLetterSpacing: {
		type: 'number',
	},
	subHeadLetterSpacingTablet: {
		type: 'number',
	},
	subHeadLetterSpacingMobile: {
		type: 'number',
	},
	subHeadLetterSpacingType: {
		type: 'string',
		default: 'px',
	},
	// separator
	separatorSpace: {
		type: 'number',
		default: 15,
	},
	separatorSpaceTablet: {
		type: 'number',
		default: '',
	},
	separatorSpaceMobile: {
		type: 'number',
		default: '',
	},
	separatorSpaceType: {
		type: 'string',
		default: 'px',
	},
	headLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	subHeadLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	separatorHoverColor: {
		type: 'string',
	},
	isPreview: {
		type: 'boolean',
		default: false,
	},
	// padding
	blockTopPadding: {
		type: 'number',
	},
	blockRightPadding: {
		type: 'number',
	},
	blockLeftPadding: {
		type: 'number',
	},
	blockBottomPadding: {
		type: 'number',
	},
	blockTopPaddingTablet: {
		type: 'number',
	},
	blockRightPaddingTablet: {
		type: 'number',
	},
	blockLeftPaddingTablet: {
		type: 'number',
	},
	blockBottomPaddingTablet: {
		type: 'number',
	},
	blockTopPaddingMobile: {
		type: 'number',
	},
	blockRightPaddingMobile: {
		type: 'number',
	},
	blockLeftPaddingMobile: {
		type: 'number',
	},
	blockBottomPaddingMobile: {
		type: 'number',
	},
	blockPaddingUnit: {
		type: 'string',
		default: 'px',
	},
	blockPaddingUnitTablet: {
		type: 'string',
		default: 'px',
	},
	blockPaddingUnitMobile: {
		type: 'string',
		default: 'px',
	},
	blockPaddingLink: {
		type: 'boolean',
		default: false,
	},
	// margin
	blockTopMargin: {
		type: 'number',
	},
	blockRightMargin: {
		type: 'number',
	},
	blockLeftMargin: {
		type: 'number',
	},
	blockBottomMargin: {
		type: 'number',
	},
	blockTopMarginTablet: {
		type: 'number',
	},
	blockRightMarginTablet: {
		type: 'number',
	},
	blockLeftMarginTablet: {
		type: 'number',
	},
	blockBottomMarginTablet: {
		type: 'number',
	},
	blockTopMarginMobile: {
		type: 'number',
	},
	blockRightMarginMobile: {
		type: 'number',
	},
	blockLeftMarginMobile: {
		type: 'number',
	},
	blockBottomMarginMobile: {
		type: 'number',
	},
	blockMarginUnit: {
		type: 'string',
		default: 'px',
	},
	blockMarginUnitTablet: {
		type: 'string',
		default: 'px',
	},
	blockMarginUnitMobile: {
		type: 'string',
		default: 'px',
	},
	blockMarginLink: {
		type: 'boolean',
		default: false,
	},
	// link
	linkColor: {
		type: 'string',
	},
	linkHColor: {
		type: 'string',
	},
	// Highlight
	highLightColor: {
		type: 'string',
		default: '#fff',
	},
	highLightBackground: {
		type: 'string',
		default: '#007cba',
	},
	highLightLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	highLightFontFamily: {
		type: 'string',
		default: 'Default',
	},
	highLightFontWeight: {
		type: 'string',
		default: 'Default',
	},
	highLightFontStyle: {
		type: 'string',
		default: 'normal',
	},
	highLightTransform: {
		type: 'string',
	},
	highLightDecoration: {
		type: 'string',
	},
	highLightFontSizeType: {
		type: 'string',
		default: 'px',
	},
	highLightFontSizeTypeMobile: {
		type: 'string',
		default: 'px',
	},
	highLightFontSizeTypeTablet: {
		type: 'string',
		default: 'px',
	},
	highLightLineHeightType: {
		type: 'string',
		default: 'em',
	},
	highLightFontSize: {
		type: 'number',
	},
	highLightFontSizeTablet: {
		type: 'number',
	},
	highLightFontSizeMobile: {
		type: 'number',
	},
	highLightLineHeight: {
		type: 'number',
	},
	highLightLineHeightTablet: {
		type: 'number',
	},
	highLightLineHeightMobile: {
		type: 'number',
	},
	highLightLetterSpacing: {
		type: 'number',
	},
	highLightLetterSpacingTablet: {
		type: 'number',
	},
	highLightLetterSpacingMobile: {
		type: 'number',
	},
	highLightLetterSpacingType: {
		type: 'string',
		default: 'px',
	},
	highLightTopPadding: {
		type: 'number',
	},
	highLightRightPadding: {
		type: 'number',
	},
	highLightLeftPadding: {
		type: 'number',
	},
	highLightBottomPadding: {
		type: 'number',
	},
	highLightTopPaddingTablet: {
		type: 'number',
	},
	highLightRightPaddingTablet: {
		type: 'number',
	},
	highLightLeftPaddingTablet: {
		type: 'number',
	},
	highLightBottomPaddingTablet: {
		type: 'number',
	},
	highLightTopPaddingMobile: {
		type: 'number',
	},
	highLightRightPaddingMobile: {
		type: 'number',
	},
	highLightLeftPaddingMobile: {
		type: 'number',
	},
	highLightBottomPaddingMobile: {
		type: 'number',
	},
	highLightPaddingUnit: {
		type: 'string',
		default: 'px',
	},
	highLightPaddingUnitTablet: {
		type: 'string',
		default: 'px',
	},
	highLightPaddingUnitMobile: {
		type: 'string',
		default: 'px',
	},
	highLightPaddingLink: {
		type: 'boolean',
		default: false,
	},
	...highLightBorderAttributes,
};

export default attributes;
