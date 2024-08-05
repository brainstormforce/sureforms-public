/**
 * BLOCK: UAGB separator Attributes
 */

import { __ } from '@wordpress/i18n';

const attributes = {
	block_id: {
		type: 'string',
	},
	isPreview: {
		type: 'boolean',
		default: false,
	},
	separatorAlign: {
		type: 'string',
		default: 'center',
	},
	separatorAlignTablet: {
		type: 'string',
		default: 'center',
	},
	separatorAlignMobile: {
		type: 'string',
		default: 'center',
	},
	separatorStyle: {
		type: 'string',
		default: 'solid',
	},
	separatorWidth: {
		type: 'number',
		default: 100,
	},
	separatorWidthTablet: {
		type: 'number',
		default: 100,
	},
	separatorWidthMobile: {
		type: 'number',
		default: 100,
	},
	separatorWidthType: {
		type: 'string',
		default: '%',
	},
	separatorBorderHeight: {
		type: 'number',
		default: 3,
	},
	separatorBorderHeightMobile: {
		type: 'number',
		default: 3,
	},
	separatorBorderHeightTablet: {
		type: 'number',
		default: 3,
	},
	separatorBorderHeightUnit: {
		type: 'string',
		default: 'px',
	},
	separatorSize: {
		type: 'number',
		default: 5,
	},
	separatorSizeMobile: {
		type: 'number',
		default: 5,
	},
	separatorSizeTablet: {
		type: 'number',
		default: 5,
	},
	separatorSizeType: {
		default: 'px',
	},
	separatorColor: {
		type: 'string',
		default: '#000',
	},
	separatorHeight: {
		type: 'number',
		default: 10,
	},
	separatorHeightMobile: {
		type: 'number',
		default: 10,
	},
	separatorHeightTablet: {
		type: 'number',
		default: 10,
	},
	separatorHeightType: {
		type: 'string',
		default: 'px',
	},
	separatorBottomPadding: {
		type: 'number',
	},
	separatorPaddingTopTablet: {
		type: 'number',
	},
	separatorPaddingRightTablet: {
		type: 'number',
	},
	separatorPaddingBottomTablet: {
		type: 'number',
	},
	separatorPaddingLeftTablet: {
		type: 'number',
	},
	separatorPaddingTopMobile: {
		type: 'number',
	},
	separatorPaddingRightMobile: {
		type: 'number',
	},
	separatorPaddingBottomMobile: {
		type: 'number',
	},
	separatorPaddingLeftMobile: {
		type: 'number',
	},
	separatorPaddingUnit: {
		type: 'number',
		default: 'px',
	},
	separatorMobilePaddingUnit: {
		type: 'number',
		default: 'px',
	},
	separatorTabletPaddingUnit: {
		type: 'number',
		default: 'px',
	},
	separatorPaddingLink: {
		type: 'boolean',
		default: true,
	},
	elementType: {
		type: 'string',
		default: 'none',
	},
	separatorText: {
		type: 'string',
		default: __( 'Divider', 'sureforms' ),
	},
	separatorTextTag: {
		type: 'string',
		default: 'h4',
	},
	separatorIcon: {
		type: 'string',
		default: 'circle-check',
	},
	elementPosition: {
		type: 'string',
		default: 'center',
	},
	elementSpacing: {
		type: 'number',
		default: 15,
	},
	elementSpacingTablet: {
		type: 'number',
		default: 15,
	},
	elementSpacingMobile: {
		type: 'number',
		default: 15,
	},
	elementSpacingUnit: {
		type: 'string',
		default: 'px',
	},

	elementTextLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	elementTextFontFamily: {
		type: 'string',
		default: 'Default',
	},
	elementTextFontWeight: {
		type: 'string',
	},
	elementTextFontSize: {
		type: 'number',
	},
	elementTextFontSizeType: {
		type: 'string',
		default: 'px',
	},
	elementTextFontSizeTablet: {
		type: 'number',
	},
	elementTextFontSizeMobile: {
		type: 'number',
	},
	elementTextLineHeightType: {
		type: 'string',
		default: 'em',
	},
	elementTextLineHeight: {
		type: 'number',
		default: 1,
	},
	elementTextLineHeightTablet: {
		type: 'number',
		default: 1,
	},
	elementTextLineHeightMobile: {
		type: 'number',
		default: 1,
	},
	elementTextFontStyle: {
		type: 'string',
		default: 'normal',
	},
	elementTextLetterSpacing: {
		type: 'number',
	},
	elementTextLetterSpacingTablet: {
		type: 'number',
	},
	elementTextLetterSpacingMobile: {
		type: 'number',
	},
	elementTextLetterSpacingType: {
		type: 'string',
		default: 'px',
	},
	elementTextDecoration: {
		type: 'string',
	},
	elementTextTransform: {
		type: 'string',
	},
	elementColor: {
		type: 'string',
		default: '#000',
	},
	elementIconWidth: {
		type: 'number',
		default: 30,
	},
	elementIconWidthTablet: {
		type: 'number',
		default: 30,
	},
	elementIconWidthMobile: {
		type: 'number',
		default: 30,
	},
	elementIconWidthType: {
		type: 'string',
		default: 'px',
	},

	// padding
	blockTopPadding: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-top-padding',
		},
	},
	blockRightPadding: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-right-padding',
		},
	},
	blockLeftPadding: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-left-padding',
		},
	},
	blockBottomPadding: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-bottom-padding',
		},
	},
	blockTopPaddingTablet: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-top-padding-tablet',
		},
	},
	blockRightPaddingTablet: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-right-padding-tablet',
		},
	},
	blockLeftPaddingTablet: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-left-padding-tablet',
		},
	},
	blockBottomPaddingTablet: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-bottom-padding-tablet',
		},
	},
	blockTopPaddingMobile: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-top-padding-mobile',
		},
	},
	blockRightPaddingMobile: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-right-padding-mobile',
		},
	},
	blockLeftPaddingMobile: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-left-padding-mobile',
		},
	},
	blockBottomPaddingMobile: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-bottom-padding-mobile',
		},
	},
	blockPaddingUnit: {
		type: 'string',
		UAGCopyPaste: {
			styleType: 'block-padding-unit',
		},
	},
	blockPaddingUnitTablet: {
		type: 'string',
		UAGCopyPaste: {
			styleType: 'block-padding-unit-tablet',
		},
	},
	blockPaddingUnitMobile: {
		type: 'string',
		UAGCopyPaste: {
			styleType: 'block-padding-unit-mobile',
		},
	},
	blockPaddingLink: {
		type: 'boolean',
		default: false,
	},

	// margin
	blockTopMargin: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-top-margin',
		},
	},
	blockRightMargin: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-right-margin',
		},
	},
	blockLeftMargin: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-left-margin',
		},
	},
	blockBottomMargin: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-bottom-margin',
		},
	},
	blockTopMarginTablet: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-top-margin-tablet',
		},
	},
	blockRightMarginTablet: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-right-margin-tablet',
		},
	},
	blockLeftMarginTablet: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-left-margin-tablet',
		},
	},
	blockBottomMarginTablet: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-bottom-margin-tablet',
		},
	},
	blockTopMarginMobile: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-top-margin-mobile',
		},
	},
	blockRightMarginMobile: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-right-margin-mobile',
		},
	},
	blockLeftMarginMobile: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-left-margin-mobile',
		},
	},
	blockBottomMarginMobile: {
		type: 'number',
		isGBSStyle: true,
		UAGCopyPaste: {
			styleType: 'block-bottom-margin-mobile',
		},
	},
	blockMarginUnit: {
		type: 'string',
		default: 'px',
		UAGCopyPaste: {
			styleType: 'block-margin-unit',
		},
	},
	blockMarginUnitTablet: {
		type: 'string',
		default: 'px',
		UAGCopyPaste: {
			styleType: 'block-margin-unit-tablet',
		},
	},
	blockMarginUnitMobile: {
		type: 'string',
		default: 'px',
		UAGCopyPaste: {
			styleType: 'block-margin-unit-mobile',
		},
	},
	blockMarginLink: {
		type: 'boolean',
		default: false,
	},
};

export default attributes;
