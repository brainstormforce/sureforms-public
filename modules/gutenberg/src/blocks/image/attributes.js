import { getBorderAttributes } from '@Controls/generateAttributes';

const overlayBorderAttributes = getBorderAttributes( 'overlay' );
const imageBorderAttributes = getBorderAttributes( 'image' );

const attributes = {
	block_id: {
		type: 'string',
	},
	isPreview: {
		type: 'boolean',
		default: false,
	},
	layout: {
		type: 'string',
		default: 'default',
	},
	url: {
		type: 'string',
		default: '',
	},
	urlTablet: {
		type: 'string',
		default: '',
	},
	urlMobile: {
		type: 'string',
		default: '',
	},
	alt: {
		type: 'string',
		default: '',
	},
	enableCaption: {
		type: 'boolean',
		default: false,
	},
	caption: {
		type: 'string',
		default: '',
	},
	align: {
		type: 'string',
		default: '',
	},
	alignTablet: {
		type: 'string',
		default: '',
	},
	alignMobile: {
		type: 'string',
		default: '',
	},
	id: {
		type: 'integer',
		default: '',
	},
	href: {
		type: 'string',
		default: '',
	},
	rel: {
		type: 'string',
		default: '',
	},
	linkClass: {
		type: 'string',
		default: '',
	},
	linkDestination: {
		type: 'string',
		default: '',
	},
	title: {
		type: 'string',
		default: '',
	},
	width: {
		type: 'integer',
		default: '',
	},
	widthTablet: {
		type: 'integer',
		default: '',
	},
	widthMobile: {
		type: 'integer',
		default: '',
	},
	height: {
		type: 'integer',
		default: '',
	},
	heightTablet: {
		type: 'integer',
		default: '',
	},
	heightMobile: {
		type: 'integer',
		default: '',
	},
	naturalWidth: {
		type: 'integer',
		default: '',
	},
	naturalHeight: {
		type: 'integer',
		default: '',
	},
	linkTarget: {
		type: 'string',
		default: '',
	},
	sizeSlug: {
		type: 'string',
		default: '',
	},
	sizeSlugTablet: {
		type: 'string',
		default: '',
	},
	sizeSlugMobile: {
		type: 'string',
		default: '',
	},
	// image.
	imageTopMargin: {
		type: 'number',
	},
	imageRightMargin: {
		type: 'number',
	},
	imageLeftMargin: {
		type: 'number',
	},
	imageBottomMargin: {
		type: 'number',
	},
	imageTopMarginTablet: {
		type: 'number',
	},
	imageRightMarginTablet: {
		type: 'number',
	},
	imageLeftMarginTablet: {
		type: 'number',
	},
	imageBottomMarginTablet: {
		type: 'number',
	},
	imageTopMarginMobile: {
		type: 'number',
	},
	imageRightMarginMobile: {
		type: 'number',
	},
	imageLeftMarginMobile: {
		type: 'number',
	},
	imageBottomMarginMobile: {
		type: 'number',
	},
	imageMarginUnit: {
		type: 'string',
		default: 'px',
	},
	imageMarginUnitTablet: {
		type: 'string',
		default: 'px',
	},
	imageMarginUnitMobile: {
		type: 'string',
		default: 'px',
	},
	imageMarginLink: {
		type: 'boolean',
		default: true,
	},
	// caption.
	captionText: {
		type: 'string',
		default: '',
	},
	captionShowOn: {
		type: 'string',
		default: 'hover',
	},
	captionLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	captionColor: {
		type: 'string',
	},
	captionAlign: {
		type: 'string',
		default: 'center',
	},
	captionFontFamily: {
		type: 'string',
		default: 'Default',
	},
	captionFontWeight: {
		type: 'string',
	},
	captionFontStyle: {
		type: 'string',
		default: 'normal',
	},
	captionTransform: {
		type: 'string',
	},
	captionDecoration: {
		type: 'string',
	},
	captionFontSizeType: {
		type: 'string',
		default: 'px',
	},
	captionFontSizeTypeTablet: {
		type: 'string',
		default: 'px',
	},
	captionFontSizeTypeMobile: {
		type: 'string',
		default: 'px',
	},
	captionLineHeightType: {
		type: 'string',
		default: 'em',
	},
	captionFontSize: {
		type: 'number',
	},
	captionFontSizeTablet: {
		type: 'number',
	},
	captionFontSizeMobile: {
		type: 'number',
	},
	captionLineHeight: {
		type: 'number',
	},
	captionLineHeightTablet: {
		type: 'number',
	},
	captionLineHeightMobile: {
		type: 'number',
	},
	captionTopMargin: {
		type: 'number',
	},
	captionRightMargin: {
		type: 'number',
	},
	captionLeftMargin: {
		type: 'number',
	},
	captionBottomMargin: {
		type: 'number',
	},
	captionTopMarginTablet: {
		type: 'number',
	},
	captionRightMarginTablet: {
		type: 'number',
	},
	captionLeftMarginTablet: {
		type: 'number',
	},
	captionBottomMarginTablet: {
		type: 'number',
	},
	captionTopMarginMobile: {
		type: 'number',
	},
	captionRightMarginMobile: {
		type: 'number',
	},
	captionLeftMarginMobile: {
		type: 'number',
	},
	captionBottomMarginMobile: {
		type: 'number',
	},
	captionMarginUnit: {
		type: 'string',
		default: 'px',
	},
	captionMarginUnitTablet: {
		type: 'string',
		default: 'px',
	},
	captionMarginUnitMobile: {
		type: 'string',
		default: 'px',
	},
	captionMarginLink: {
		type: 'boolean',
		default: false,
	},
	// heading.
	heading: {
		type: 'string',
		default: '',
	},
	headingShowOn: {
		type: 'string',
		default: 'always',
	},
	headingTag: {
		type: 'string',
		default: 'h2',
	},
	headingId: {
		type: 'string',
	},
	headingLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	headingColor: {
		type: 'string',
		default: '#fff',
	},
	headingFontFamily: {
		type: 'string',
		default: 'Default',
	},
	headingFontWeight: {
		type: 'string',
	},
	headingFontStyle: {
		type: 'string',
		default: 'normal',
	},
	headingTransform: {
		type: 'string',
	},
	headingDecoration: {
		type: 'string',
	},
	headingFontSizeType: {
		type: 'string',
		default: 'px',
	},
	headingFontSizeTypeTablet: {
		type: 'string',
		default: 'px',
	},
	headingFontSizeTypeMobile: {
		type: 'string',
		default: 'px',
	},
	headingLineHeightType: {
		type: 'string',
		default: 'em',
	},
	headingFontSize: {
		type: 'number',
	},
	headingFontSizeTablet: {
		type: 'number',
	},
	headingFontSizeMobile: {
		type: 'number',
	},
	headingLineHeight: {
		type: 'number',
	},
	headingLineHeightTablet: {
		type: 'number',
	},
	headingLineHeightMobile: {
		type: 'number',
	},
	headingTopMargin: {
		type: 'number',
	},
	headingRightMargin: {
		type: 'number',
	},
	headingLeftMargin: {
		type: 'number',
	},
	headingBottomMargin: {
		type: 'number',
	},
	headingTopMarginTablet: {
		type: 'number',
	},
	headingRightMarginTablet: {
		type: 'number',
	},
	headingLeftMarginTablet: {
		type: 'number',
	},
	headingBottomMarginTablet: {
		type: 'number',
	},
	headingTopMarginMobile: {
		type: 'number',
	},
	headingRightMarginMobile: {
		type: 'number',
	},
	headingLeftMarginMobile: {
		type: 'number',
	},
	headingBottomMarginMobile: {
		type: 'number',
	},
	headingMarginUnit: {
		type: 'string',
		default: 'px',
	},
	headingMarginUnitTablet: {
		type: 'string',
		default: 'px',
	},
	headingMarginUnitMobile: {
		type: 'string',
		default: 'px',
	},
	headingMarginLink: {
		type: 'boolean',
		default: false,
	},
	// overlay.
	overlayPositionFromEdge: {
		type: 'number',
		default: 15,
	},
	overlayPositionFromEdgeUnit: {
		type: 'string',
		default: 'px',
	},
	overlayContentPosition: {
		type: 'string',
		default: 'center center',
	},
	overlayBackground: {
		type: 'string',
		default: '',
	},
	overlayOpacity: {
		type: 'float',
		default: 0.2,
	},
	overlayHoverOpacity: {
		type: 'number',
		default: 1,
	},
	// separator.
	separatorShowOn: {
		type: 'string',
		default: 'hover',
	},
	separatorStyle: {
		type: 'string',
		default: 'none',
	},
	separatorColor: {
		type: 'string',
		default: '#fff',
	},
	separatorPosition: {
		type: 'string',
		default: 'after_title',
	},
	separatorWidth: {
		type: 'number',
		default: 30,
	},
	separatorWidthType: {
		type: 'string',
		default: '%',
	},
	separatorThickness: {
		type: 'number',
		default: 2,
	},
	separatorThicknessUnit: {
		type: 'string',
		default: 'px',
	},
	separatorTopMargin: {
		type: 'number',
	},
	separatorRightMargin: {
		type: 'number',
	},
	separatorLeftMargin: {
		type: 'number',
	},
	separatorBottomMargin: {
		type: 'number',
	},
	separatorTopMarginTablet: {
		type: 'number',
	},
	separatorRightMarginTablet: {
		type: 'number',
	},
	separatorLeftMarginTablet: {
		type: 'number',
	},
	separatorBottomMarginTablet: {
		type: 'number',
	},
	separatorTopMarginMobile: {
		type: 'number',
	},
	separatorRightMarginMobile: {
		type: 'number',
	},
	separatorLeftMarginMobile: {
		type: 'number',
	},
	separatorBottomMarginMobile: {
		type: 'number',
	},
	separatorMarginUnit: {
		type: 'string',
		default: 'px',
	},
	separatorMarginUnitTablet: {
		type: 'string',
		default: 'px',
	},
	separatorMarginUnitMobile: {
		type: 'string',
		default: 'px',
	},
	separatorMarginLink: {
		type: 'boolean',
		default: false,
	},
	// effect.
	imageHoverEffect: {
		type: 'string',
		default: 'static',
	},
	objectFit: {
		type: 'string',
	},
	objectFitTablet: {
		type: 'string',
	},
	objectFitMobile: {
		type: 'string',
	},
	useSeparateBoxShadows: {
		type: 'boolean',
		default: true,
	},
	imageBoxShadowColor: {
		type: 'string',
		default: '#00000070',
	},
	imageBoxShadowHOffset: {
		type: 'number',
		default: 0,
	},
	imageBoxShadowVOffset: {
		type: 'number',
		default: 0,
	},
	imageBoxShadowBlur: {
		type: 'number',
	},
	imageBoxShadowSpread: {
		type: 'number',
	},
	imageBoxShadowPosition: {
		type: 'string',
		default: 'outset',
	},
	imageBoxShadowColorHover: {
		type: 'string',
	},
	imageBoxShadowHOffsetHover: {
		type: 'number',
		default: 0,
	},
	imageBoxShadowVOffsetHover: {
		type: 'number',
		default: 0,
	},
	imageBoxShadowBlurHover: {
		type: 'number',
	},
	imageBoxShadowSpreadHover: {
		type: 'number',
	},
	imageBoxShadowPositionHover: {
		type: 'string',
		default: 'outset',
	},
	// mask
	maskShape: {
		type: 'string',
		default: 'none',
	},
	maskCustomShape: {
		type: 'object',
		default: {
			url: '',
			alt: 'mask shape',
		},
	},
	maskSize: {
		type: 'string',
		default: 'auto',
	},
	maskPosition: {
		type: 'string',
		default: 'center center',
	},
	maskRepeat: {
		type: 'string',
		default: 'no-repeat',
	},
	headingLetterSpacing: {
		type: 'number',
	},
	headingLetterSpacingTablet: {
		type: 'number',
	},
	headingLetterSpacingMobile: {
		type: 'number',
	},
	headingLetterSpacingType: {
		type: 'string',
		default: 'px',
	},
	captionLetterSpacing: {
		type: 'number',
	},
	captionLetterSpacingTablet: {
		type: 'number',
	},
	captionLetterSpacingMobile: {
		type: 'number',
	},
	captionLetterSpacingType: {
		type: 'string',
		default: 'px',
	},
	customHeightSetDesktop: {
		type: 'boolean',
		default: false,
	},
	customHeightSetTablet: {
		type: 'boolean',
		default: false,
	},
	customHeightSetMobile: {
		type: 'boolean',
		default: false,
	},
	...imageBorderAttributes,
	...overlayBorderAttributes,
};

export default attributes;
