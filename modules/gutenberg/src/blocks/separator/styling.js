/**
 * Returns Dynamic Generated CSS
 */

import generateCSS from '@Controls/generateCSS';
import generateCSSUnit from '@Controls/generateCSSUnit';
import { getFallbackNumber } from '@Controls/getAttributeFallback';
const alignmentCSS = ( align ) => {
	let alignCSS = {};
	switch ( align ) {
		case 'left':
			alignCSS = {
				'margin-left': 0,
				'margin-right': 'auto',
			};
			break;
		case 'center':
			alignCSS = {
				'margin-left': 'auto',
				'margin-right': 'auto',
			};
			break;
		case 'right':
			alignCSS = {
				'margin-right': 0,
				'margin-left': 'auto',
			};
			break;
	}
	return alignCSS;
};

function styling( attributes, clientId, name, deviceType ) {
	const blockName = name.replace( 'srfm/', '' );
	const previewType = deviceType.toLowerCase();
	const {
		block_id,
		//separator
		separatorAlign,
		separatorAlignTablet,
		separatorAlignMobile,
		separatorStyle,
		separatorColor,
		separatorWidth,
		separatorWidthTablet,
		separatorWidthMobile,
		separatorWidthType,
		separatorBorderHeight,
		separatorBorderHeightMobile,
		separatorBorderHeightTablet,
		separatorBorderHeightUnit,
		separatorSize,
		separatorSizeTablet,
		separatorSizeMobile,
		separatorSizeType,
		//element
		elementType,
		elementPosition,
		elementSpacing,
		elementSpacingTablet,
		elementSpacingMobile,
		elementSpacingUnit,
		elementTextFontFamily,
		elementTextFontWeight,
		elementTextFontSize,
		elementTextFontSizeType,
		elementTextFontSizeTablet,
		elementTextFontSizeMobile,
		elementTextLineHeightType,
		elementTextLineHeight,
		elementTextLineHeightTablet,
		elementTextLineHeightMobile,
		elementTextFontStyle,
		elementTextLetterSpacing,
		elementTextLetterSpacingTablet,
		elementTextLetterSpacingMobile,
		elementTextLetterSpacingType,
		elementTextDecoration,
		elementTextTransform,
		elementColor,
		elementIconWidth,
		elementIconWidthTablet,
		elementIconWidthMobile,
		elementIconWidthType,

		// padding
		blockTopPadding,
		blockRightPadding,
		blockLeftPadding,
		blockBottomPadding,
		blockTopPaddingTablet,
		blockRightPaddingTablet,
		blockLeftPaddingTablet,
		blockBottomPaddingTablet,
		blockTopPaddingMobile,
		blockRightPaddingMobile,
		blockLeftPaddingMobile,
		blockBottomPaddingMobile,
		blockPaddingUnit,
		blockPaddingUnitTablet,
		blockPaddingUnitMobile,
		// margin
		blockTopMargin,
		blockRightMargin,
		blockLeftMargin,
		blockBottomMargin,
		blockTopMarginTablet,
		blockRightMarginTablet,
		blockLeftMarginTablet,
		blockBottomMarginTablet,
		blockTopMarginMobile,
		blockRightMarginMobile,
		blockLeftMarginMobile,
		blockBottomMarginMobile,
		blockMarginUnit,
		blockMarginUnitTablet,
		blockMarginUnitMobile,
	} = attributes;

	// Responsive Slider
	const separatorWidthFallback = getFallbackNumber(
		separatorWidth,
		'separatorWidth',
		blockName
	);
	const separatorWidthFallbackTablet = getFallbackNumber(
		separatorWidthTablet,
		'separatorWidthTablet',
		blockName
	);
	const separatorWidthFallbackMobile = getFallbackNumber(
		separatorWidthMobile,
		'separatorWidthMobile',
		blockName
	);
	// Separator Height
	const separatorBorderHeightFallback = getFallbackNumber(
		separatorBorderHeight,
		'separatorBorderHeight',
		blockName
	);
	const separatorBorderHeightFallbackMobile = getFallbackNumber(
		separatorBorderHeightMobile,
		'separatorBorderHeightMobile',
		blockName
	);
	const separatorBorderHeightFallbackTablet = getFallbackNumber(
		separatorBorderHeightTablet,
		'separatorBorderHeightTablet',
		blockName
	);
	//Size
	const separatorSizeFallback = getFallbackNumber(
		separatorSize,
		'separatorSize',
		blockName
	);
	const separatorSizeFallbackTablet = getFallbackNumber(
		separatorSizeTablet,
		'separatorSizeTablet',
		blockName
	);
	const separatorSizeFallbackMobile = getFallbackNumber(
		separatorSizeMobile,
		'separatorSizeMobile',
		blockName
	);

	const borderCSS = {
		'border-top-width': generateCSSUnit(
			separatorBorderHeightFallback,
			separatorBorderHeightUnit
		),
		'-webkit-mask-size': `${ generateCSSUnit(
			separatorSizeFallback,
			separatorSizeType
		) } 100%`,
		width: generateCSSUnit( separatorWidthFallback, separatorWidthType ),
		'border-top-color': separatorColor,
		'border-top-style': separatorStyle,
	};

	let borderStyle = {};
	const iconSpacingStyle = {};
	if ( elementType === 'none' ) {
		borderStyle = {
			'.wp-block-uagb-separator .wp-block-uagb-separator__inner': {
				'margin-top': '5px',
				'margin-bottom': '5px',
				...borderCSS,
			},
		};
	} else {
		const alignCSS = alignmentCSS( separatorAlign );
		borderStyle = {
			'.wp-block-uagb-separator .wp-block-uagb-separator__inner': {
				width: generateCSSUnit(
					separatorWidthFallback,
					separatorWidthType
				),
				...alignCSS,
			},
			'.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::before':
				borderCSS,
			'.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::before':
				borderCSS,
			'.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::after':
				borderCSS,
			'.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::after':
				borderCSS,
		};
		if ( elementPosition === 'left' ) {
			iconSpacingStyle[
				'.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'
			] = {
				'margin-right': generateCSSUnit(
					elementSpacing,
					elementSpacingUnit
				),
			};
			borderStyle[
				'.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::before'
			] = {
				display: 'none',
			};
			borderStyle[
				'.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::before'
			] = {
				display: 'none',
			};
		}
		if ( elementPosition === 'right' ) {
			iconSpacingStyle[
				'.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'
			] = {
				'margin-left': generateCSSUnit(
					elementSpacing,
					elementSpacingUnit
				),
			};
			borderStyle[
				'.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::after'
			] = {
				display: 'none',
			};
			borderStyle[
				'.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::after'
			] = {
				display: 'none',
			};
		}
		if ( elementPosition === 'center' ) {
			iconSpacingStyle[
				'.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'
			] = {
				'margin-right': generateCSSUnit(
					elementSpacing,
					elementSpacingUnit
				),
				'margin-left': generateCSSUnit(
					elementSpacing,
					elementSpacingUnit
				),
			};
		}
	}

	const selectors = {
		...borderStyle,
		...iconSpacingStyle,
		'.wp-block-uagb-separator--text .wp-block-uagb-separator-element .uagb-html-tag':
			{
				'font-family': elementTextFontFamily,
				'font-style': elementTextFontStyle,
				'text-decoration': elementTextDecoration,
				'text-transform': elementTextTransform,
				'font-weight': elementTextFontWeight,
				'font-size': generateCSSUnit(
					elementTextFontSize,
					elementTextFontSizeType
				),
				'line-height': generateCSSUnit(
					elementTextLineHeight,
					elementTextLineHeightType
				),
				'letter-spacing': generateCSSUnit(
					elementTextLetterSpacing,
					elementTextLetterSpacingType
				),
				color: elementColor,
				margin: 0,
			},
		'.wp-block-uagb-separator--icon .wp-block-uagb-separator-element svg': {
			'font-size': generateCSSUnit(
				elementIconWidth,
				elementIconWidthType
			),
			width: generateCSSUnit( elementIconWidth, elementIconWidthType ),
			height: generateCSSUnit( elementIconWidth, elementIconWidthType ),
			'line-height': generateCSSUnit(
				elementIconWidth,
				elementIconWidthType
			),
			color: elementColor,
			fill: elementColor,
		},
		'.wp-block-srfm-separator': {
			'margin-top': generateCSSUnit(
				blockTopMargin,
				blockMarginUnit || 'px'
			),
			'margin-right': generateCSSUnit(
				blockRightMargin,
				blockMarginUnit || 'px'
			),
			'margin-bottom': generateCSSUnit(
				blockBottomMargin,
				blockMarginUnit || 'px'
			),
			'margin-left': generateCSSUnit(
				blockLeftMargin,
				blockMarginUnit || 'px'
			),
		},
		'.wp-block-uagb-separator': {
			'padding-top': generateCSSUnit(
				blockTopPadding,
				blockPaddingUnit || 'px'
			),
			'padding-right': generateCSSUnit(
				blockRightPadding,
				blockPaddingUnit || 'px'
			),
			'padding-bottom': generateCSSUnit(
				blockBottomPadding,
				blockPaddingUnit || 'px'
			),
			'padding-left': generateCSSUnit(
				blockLeftPadding,
				blockPaddingUnit || 'px'
			),
			'text-align': separatorAlign,
		},
	};

	const borderCSSTablet = {
		'border-top-width': generateCSSUnit(
			separatorBorderHeightFallbackTablet,
			separatorBorderHeightUnit
		),
		'-webkit-mask-size': `${ generateCSSUnit(
			separatorSizeFallbackTablet,
			separatorSizeType
		) } 100%`,
		width: generateCSSUnit(
			separatorWidthFallbackTablet,
			separatorWidthType
		),
		'border-top-color': separatorColor,
		'border-top-style': separatorStyle,
	};

	let borderStyleTablet = {};
	const iconSpacingStyleTablet = {};
	if ( elementType === 'none' ) {
		borderStyleTablet = {
			'.wp-block-uagb-separator .wp-block-uagb-separator__inner': {
				'margin-top': '5px',
				'margin-bottom': '5px',
				...borderCSSTablet,
			},
		};
	} else {
		const alignCSS = alignmentCSS( separatorAlignTablet );
		borderStyleTablet = {
			'.wp-block-uagb-separator .wp-block-uagb-separator__inner': {
				width: generateCSSUnit(
					separatorWidthFallbackTablet,
					separatorWidthType
				),
				...alignCSS,
			},
			'.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::before':
				borderCSSTablet,
			'.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::before':
				borderCSSTablet,
			'.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::after':
				borderCSSTablet,
			'.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::after':
				borderCSSTablet,
		};
		if ( elementPosition === 'left' ) {
			iconSpacingStyleTablet[
				'.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'
			] = {
				'margin-right': generateCSSUnit(
					elementSpacingTablet,
					elementSpacingUnit
				),
			};
			borderStyleTablet[
				'.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::before'
			] = {
				display: 'none',
			};
			borderStyleTablet[
				'.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::before'
			] = {
				display: 'none',
			};
		}
		if ( elementPosition === 'center' ) {
			iconSpacingStyleTablet[
				'.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'
			] = {
				'margin-left': generateCSSUnit(
					elementSpacingTablet,
					elementSpacingUnit
				),
				'margin-right': generateCSSUnit(
					elementSpacingTablet,
					elementSpacingUnit
				),
			};
		}
		if ( elementPosition === 'right' ) {
			iconSpacingStyleTablet[
				'.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'
			] = {
				'margin-left': generateCSSUnit(
					elementSpacingTablet,
					elementSpacingUnit
				),
			};
			borderStyleTablet[
				'.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::after'
			] = {
				display: 'none',
			};
			borderStyleTablet[
				'.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::after'
			] = {
				display: 'none',
			};
		}
	}

	const tablet_selectors = {
		...borderStyleTablet,
		...iconSpacingStyleTablet,
		'.wp-block-uagb-separator--text .wp-block-uagb-separator-element .uagb-html-tag':
			{
				'font-family': elementTextFontFamily,
				'font-style': elementTextFontStyle,
				'text-decoration': elementTextDecoration,
				'text-transform': elementTextTransform,
				'font-weight': elementTextFontWeight,
				'font-size': generateCSSUnit(
					elementTextFontSizeTablet,
					elementTextFontSizeType
				),
				'line-height': generateCSSUnit(
					elementTextLineHeightTablet,
					elementTextLineHeightType
				),
				'letter-spacing': generateCSSUnit(
					elementTextLetterSpacingTablet,
					elementTextLetterSpacingType
				),
				color: elementColor,
				margin: 0,
			},
		'.wp-block-uagb-separator--icon .wp-block-uagb-separator-element svg': {
			'font-size': generateCSSUnit(
				elementIconWidthTablet,
				elementIconWidthType
			),
			width: generateCSSUnit(
				elementIconWidthTablet,
				elementIconWidthType
			),
			height: generateCSSUnit(
				elementIconWidthTablet,
				elementIconWidthType
			),
			'line-height': generateCSSUnit(
				elementIconWidthTablet,
				elementIconWidthType
			),
			color: elementColor,
			fill: elementColor,
		},
		'.wp-block-srfm-separator': {
			'margin-top': generateCSSUnit(
				blockTopMarginTablet,
				blockMarginUnitTablet || 'px'
			),
			'margin-right': generateCSSUnit(
				blockRightMarginTablet,
				blockMarginUnitTablet || 'px'
			),
			'margin-bottom': generateCSSUnit(
				blockBottomMarginTablet,
				blockMarginUnitTablet || 'px'
			),
			'margin-left': generateCSSUnit(
				blockLeftMarginTablet,
				blockMarginUnitTablet || 'px'
			),
		},
		'.wp-block-uagb-separator': {
			'padding-top': generateCSSUnit(
				blockTopPaddingTablet,
				blockPaddingUnitTablet || 'px'
			),
			'padding-right': generateCSSUnit(
				blockRightPaddingTablet,
				blockPaddingUnitTablet || 'px'
			),
			'padding-bottom': generateCSSUnit(
				blockBottomPaddingTablet,
				blockPaddingUnitTablet || 'px'
			),
			'padding-left': generateCSSUnit(
				blockLeftPaddingTablet,
				blockPaddingUnitTablet || 'px'
			),
			'text-align': separatorAlignTablet,
		},
	};

	const borderCSSMobile = {
		'border-top-width': generateCSSUnit(
			separatorBorderHeightFallbackMobile,
			separatorBorderHeightUnit
		),
		'-webkit-mask-size': `${ generateCSSUnit(
			separatorSizeFallbackMobile,
			separatorSizeType
		) } 100%`,
		width: generateCSSUnit(
			separatorWidthFallbackMobile,
			separatorWidthType
		),
		'border-top-color': separatorColor,
		'border-top-style': separatorStyle,
	};

	let borderStyleMobile = {};
	const iconSpacingStyleMobile = {};
	if ( elementType === 'none' ) {
		borderStyleMobile = {
			'.wp-block-uagb-separator .wp-block-uagb-separator__inner': {
				'margin-top': '5px',
				'margin-bottom': '5px',
				...borderCSSMobile,
			},
		};
	} else {
		const alignCSS = alignmentCSS( separatorAlignMobile );
		borderStyleMobile = {
			'.wp-block-uagb-separator .wp-block-uagb-separator__inner': {
				width: generateCSSUnit(
					separatorWidthFallbackMobile,
					separatorWidthType
				),
				...alignCSS,
			},
			'.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::before':
				borderCSSMobile,
			'.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::before':
				borderCSSMobile,
			'.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::after':
				borderCSSMobile,
			'.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::after':
				borderCSSMobile,
		};
		if ( elementPosition === 'left' ) {
			iconSpacingStyleMobile[
				'.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'
			] = {
				'margin-right': generateCSSUnit(
					elementSpacingMobile,
					elementSpacingUnit
				),
			};
			borderStyleMobile[
				'.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::before'
			] = {
				display: 'none',
			};
			borderStyleMobile[
				'.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::before'
			] = {
				display: 'none',
			};
		}
		if ( elementPosition === 'center' ) {
			iconSpacingStyleMobile[
				'.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'
			] = {
				'margin-left': generateCSSUnit(
					elementSpacingMobile,
					elementSpacingUnit
				),
				'margin-right': generateCSSUnit(
					elementSpacingMobile,
					elementSpacingUnit
				),
			};
		}
		if ( elementPosition === 'right' ) {
			iconSpacingStyleMobile[
				'.wp-block-uagb-separator .wp-block-uagb-separator__inner .wp-block-uagb-separator-element'
			] = {
				'margin-left': generateCSSUnit(
					elementSpacingMobile,
					elementSpacingUnit
				),
			};
			borderStyleMobile[
				'.wp-block-uagb-separator--text .wp-block-uagb-separator__inner::after'
			] = {
				display: 'none',
			};
			borderStyleMobile[
				'.wp-block-uagb-separator--icon .wp-block-uagb-separator__inner::after'
			] = {
				display: 'none',
			};
		}
	}
	const mobile_selectors = {
		...borderStyleMobile,
		...iconSpacingStyleMobile,
		'.wp-block-uagb-separator--text .wp-block-uagb-separator-element .uagb-html-tag':
			{
				'font-family': elementTextFontFamily,
				'font-style': elementTextFontStyle,
				'text-decoration': elementTextDecoration,
				'text-transform': elementTextTransform,
				'font-weight': elementTextFontWeight,
				'font-size': generateCSSUnit(
					elementTextFontSizeMobile,
					elementTextFontSizeType
				),
				'line-height': generateCSSUnit(
					elementTextLineHeightMobile,
					elementTextLineHeightType
				),
				'letter-spacing': generateCSSUnit(
					elementTextLetterSpacingMobile,
					elementTextLetterSpacingType
				),
				color: elementColor,
				margin: 0,
			},
		'.wp-block-uagb-separator--icon .wp-block-uagb-separator-element svg': {
			'font-size': generateCSSUnit(
				elementIconWidthMobile,
				elementIconWidthType
			),
			width: generateCSSUnit(
				elementIconWidthMobile,
				elementIconWidthType
			),
			height: generateCSSUnit(
				elementIconWidthMobile,
				elementIconWidthType
			),
			'line-height': generateCSSUnit(
				elementIconWidthMobile,
				elementIconWidthType
			),
			color: elementColor,
			fill: elementColor,
		},
		'.wp-block-srfm-separator': {
			'margin-top': generateCSSUnit(
				blockTopMarginMobile,
				blockMarginUnitMobile || 'px'
			),
			'margin-right': generateCSSUnit(
				blockRightMarginMobile,
				blockMarginUnitMobile || 'px'
			),
			'margin-bottom': generateCSSUnit(
				blockBottomMarginMobile,
				blockMarginUnitMobile || 'px'
			),
			'margin-left': generateCSSUnit(
				blockLeftMarginMobile,
				blockMarginUnitMobile || 'px'
			),
		},
		'.wp-block-uagb-separator': {
			'padding-top': generateCSSUnit(
				blockTopPaddingMobile,
				blockPaddingUnitMobile || 'px'
			),
			'padding-right': generateCSSUnit(
				blockRightPaddingMobile,
				blockPaddingUnitMobile || 'px'
			),
			'padding-bottom': generateCSSUnit(
				blockBottomPaddingMobile,
				blockPaddingUnitMobile || 'px'
			),
			'padding-left': generateCSSUnit(
				blockLeftPaddingMobile,
				blockPaddingUnitMobile || 'px'
			),
			'text-align': separatorAlignMobile,
		},
	};

	const base_selector = `.editor-styles-wrapper .uagb-block-${ block_id }`;

	let styling_css = generateCSS( selectors, base_selector );

	if ( 'tablet' === previewType || 'mobile' === previewType ) {
		styling_css += generateCSS(
			tablet_selectors,
			`${ base_selector }`,
			true,
			'tablet'
		);

		if ( 'mobile' === previewType ) {
			styling_css += generateCSS(
				mobile_selectors,
				`${ base_selector }`,
				true,
				'mobile'
			);
		}
	}
	return styling_css;
}

export default styling;
