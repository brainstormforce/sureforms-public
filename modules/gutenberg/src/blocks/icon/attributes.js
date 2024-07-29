/**
 * BLOCK: Icon - Attributes
 */

import { getBorderAttributes } from '@Controls/generateAttributes';
const borderAttributes = getBorderAttributes( 'icon' );

const attributes = {
	icon: {
		type: 'string',
		default: 'circle-check',
	},
	// Size
	iconSize: {
		type: 'number',
		default: 40,
	},
	iconSizeTablet: {
		type: 'number',
	},
	iconSizeMobile: {
		type: 'number',
	},
	iconSizeUnit: {
		type: 'string',
		default: 'px',
	},
	// Alignment
	align: {
		type: 'string',
		default: 'center',
	},
	alignTablet: {
		type: 'string',
		default: '',
	},
	alignMobile: {
		type: 'string',
		default: '',
	},
	// Color
	iconColor: {
		type: 'string',
		default: '#333',
	},
	iconBorderColor: {
		type: 'string',
		default: '',
	},
	iconBackgroundColorType: {
		type: 'string',
		default: 'classic',
	},
	iconBackgroundColor: {
		type: 'string',
		default: '',
	},
	iconBackgroundGradientColor: {
		type: 'string',
		default:
			'linear-gradient(90deg, rgb(155, 81, 224) 0%, rgb(6, 147, 227) 100%)',
	},
	iconHoverColor: {
		type: 'string',
		default: '',
	},
	iconHoverBackgroundColorType: {
		type: 'string',
		default: 'classic',
	},
	iconHoverBackgroundColor: {
		type: 'string',
	},
	iconHoverBackgroundGradientColor: {
		type: 'string',
		default:
			'linear-gradient(90deg, rgb(155, 81, 224) 0%, rgb(6, 147, 227) 100%)',
	},
	// Rotation
	rotation: {
		type: 'number',
		default: 0,
	},
	rotationUnit: {
		type: 'string',
		default: 'deg',
	},
	block_id: {
		type: 'string',
	},
	// Link related attributes.
	link: {
		type: 'string',
		default: '',
	},
	target: {
		type: 'boolean',
		default: false,
	},
	disableLink: {
		type: 'boolean',
		default: false,
	},
	iconAccessabilityMode: {
		type: 'string',
		default: 'svg',
	},
	iconAccessabilityDesc: {
		type: 'string',
		default: '',
	},
	// Padding.
	iconTopPadding: {
		type: 'number',
		default: 5,
	},
	iconRightPadding: {
		type: 'number',
		default: 5,
	},
	iconLeftPadding: {
		type: 'number',
		default: 5,
	},
	iconBottomPadding: {
		type: 'number',
		default: 5,
	},
	iconTopTabletPadding: {
		type: 'number',
	},
	iconRightTabletPadding: {
		type: 'number',
	},
	iconLeftTabletPadding: {
		type: 'number',
	},
	iconBottomTabletPadding: {
		type: 'number',
	},
	iconTopMobilePadding: {
		type: 'number',
	},
	iconRightMobilePadding: {
		type: 'number',
	},
	iconLeftMobilePadding: {
		type: 'number',
	},
	iconBottomMobilePadding: {
		type: 'number',
	},
	iconPaddingUnit: {
		type: 'string',
		default: 'px',
	},
	iconTabletPaddingUnit: {
		type: 'string',
		default: 'px',
	},
	iconMobilePaddingUnit: {
		type: 'string',
		default: 'px',
	},
	iconPaddingLink: {
		type: 'boolean',
		default: false,
	},
	// Margin
	iconTopMargin: {
		type: 'number',
	},
	iconRightMargin: {
		type: 'number',
	},
	iconLeftMargin: {
		type: 'number',
	},
	iconBottomMargin: {
		type: 'number',
	},
	iconTopTabletMargin: {
		type: 'number',
	},
	iconRightTabletMargin: {
		type: 'number',
	},
	iconLeftTabletMargin: {
		type: 'number',
	},
	iconBottomTabletMargin: {
		type: 'number',
	},
	iconTopMobileMargin: {
		type: 'number',
	},
	iconRightMobileMargin: {
		type: 'number',
	},
	iconLeftMobileMargin: {
		type: 'number',
	},
	iconBottomMobileMargin: {
		type: 'number',
	},
	iconMarginUnit: {
		type: 'string',
		default: 'px',
	},
	iconTabletMarginUnit: {
		type: 'string',
		default: 'px',
	},
	iconMobileMarginUnit: {
		type: 'string',
		default: 'px',
	},
	iconMarginLink: {
		type: 'boolean',
		default: false,
	},
	isPreview: {
		type: 'boolean',
		default: false,
	},
	...borderAttributes,
	iconBorderStyle: {
		type: 'string',
		default: 'default',
	},
	useSeparateBoxShadows: {
		type: 'boolean',
		default: true,
	},
	iconShadowColor: {
		type: 'string',
		default: '#00000070',
	},
	iconShadowHOffset: {
		type: 'number',
		default: 0,
	},
	iconShadowVOffset: {
		type: 'number',
		default: 0,
	},
	iconShadowBlur: {
		type: 'number',
		default: 0,
	},
	iconBoxShadowColor: {
		type: 'string',
		default: '#00000070',
	},
	iconBoxShadowHOffset: {
		type: 'number',
		default: 0,
	},
	iconBoxShadowVOffset: {
		type: 'number',
		default: 0,
	},
	iconBoxShadowBlur: {
		type: 'number',
	},
	iconBoxShadowSpread: {
		type: 'number',
	},
	iconBoxShadowPosition: {
		type: 'string',
		default: 'outset',
	},
	iconShadowColorHover: {
		type: 'string',
		default: '#00000070',
	},
	iconShadowHOffsetHover: {
		type: 'number',
		default: 0,
	},
	iconShadowVOffsetHover: {
		type: 'number',
		default: 0,
	},
	iconShadowBlurHover: {
		type: 'number',
		default: 0,
	},
	iconBoxShadowColorHover: {
		type: 'string',
	},
	iconBoxShadowHOffsetHover: {
		type: 'number',
		default: 0,
	},
	iconBoxShadowVOffsetHover: {
		type: 'number',
		default: 0,
	},
	iconBoxShadowBlurHover: {
		type: 'number',
	},
	iconBoxShadowSpreadHover: {
		type: 'number',
	},
	iconBoxShadowPositionHover: {
		type: 'string',
		default: 'outset',
	},
};

export default attributes;
