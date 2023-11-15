/**
 * Returns Dynamic Generated CSS
 */

import generateCSS from '@Controls/generateCSS';
import generateCSSUnit from '@Controls/generateCSSUnit';
import generateBorderCSS from '@Controls/generateBorderCSS';

function styling( props ) {
	const {
		align,
		talign,
		malign,
		titletextTransform,
		subtitletextTransform,
		textAlignment,
		//Padding
		paddingTypeDesktop,
		paddingTypeTablet,
		paddingTypeMobile,
		//Border
		// borderStyle,
		// borderWidth,
		// borderRadius,
		// borderColor,
		// borderHoverColor,
		btnBorderHColor,
		// Text Color
		textColor,
		textHoverColor,
		// Button Color
		buttonHoverColor,
		// Title
		titleFontFamily,
		titleFontWeight,
		titleFontSize,
		titleFontSizeType,
		titleFontSizeMobile,
		titleFontSizeTablet,
		titleLineHeight,
		titleLineHeightType,
		titleLineHeightMobile,
		titleLineHeightTablet,
		// Sub Title
		subTitleFontFamily,
		subTitleFontWeight,
		subTitleFontSize,
		subTitleFontSizeType,
		subTitleFontSizeMobile,
		subTitleFontSizeTablet,
		subTitleLineHeight,
		subTitleLineHeightType,
		subTitleLineHeightMobile,
		subTitleLineHeightTablet,
		// Title Bottom Margin
		titleBottomSpacing,
		// Icon
		iconPosition,
		iconColor,
		iconHoverColor,
		iconSize,
		iconSpacing,

		backgroundType,
		backgroundImageColor,
		backgroundOpacity,
		backgroundColor,
		gradientColor1,
		gradientColor2,
		gradientLocation1,
		gradientLocation2,
		gradientType,
		gradientAngle,
		gradientPosition,
		backgroundPosition,
		backgroundSize,
		backgroundAttachment,
		backgroundImage,
		backgroundRepeat,
		gradientValue,
		paddingBtnTop,
		paddingBtnBottom,
		paddingBtnLeft,
		paddingBtnRight,
		paddingBtnTopTablet,
		paddingBtnRightTablet,
		paddingBtnBottomTablet,
		paddingBtnLeftTablet,
		paddingBtnTopMobile,
		paddingBtnRightMobile,
		paddingBtnBottomMobile,
		paddingBtnLeftMobile,
		titleFontStyle,
		subTitleFontStyle,
		titleletterSpacing,
		titleLetterSpacingTablet,
		titleLetterSpacingMobile,
		titleLetterSpacingType,
		subtitleletterSpacing,
		subtitleLetterSpacingTablet,
		subtitleLetterSpacingMobile,
		subtitleLetterSpacingType,
	} = props.attributes;
	const position = backgroundPosition.replace( '-', ' ' );
	let selectors = {};
	const tablet_selectors = {};
	const mobile_selectors = {};
	const borderCSS = generateBorderCSS( props.attributes, 'btn' );
	const borderCSSTablet = generateBorderCSS(
		props.attributes,
		'btn',
		'tablet'
	);
	const borderCSSMobile = generateBorderCSS(
		props.attributes,
		'btn',
		'mobile'
	);
	selectors = {
		' .wpcf__next-step-button-wrap': {
			'text-align': align,
		},
		' .wpcf__next-step-button-link:hover': {
			'background-color': buttonHoverColor,
			color: textHoverColor,
			'border-color': btnBorderHColor,
		},
		' .wpcf__next-step-button-link .wpcf__next-step-button-content-wrap .wpcf__next-step-button-title-wrap':
			{
				'font-family': titleFontFamily,
				'font-weight': titleFontWeight,
				'font-style': titleFontStyle,
				'font-size': generateCSSUnit(
					titleFontSize,
					titleFontSizeType
				),
				'line-height': generateCSSUnit(
					titleLineHeight,
					titleLineHeightType
				),
				'text-transform': titletextTransform,
				'letter-spacing': generateCSSUnit(
					titleletterSpacing,
					titleLetterSpacingType
				),
			},
		' .wpcf__next-step-button-link .wpcf__next-step-button-content-wrap .wpcf__next-step-button-sub-title':
			{
				'font-family': subTitleFontFamily,
				'font-weight': subTitleFontWeight,
				'font-style': subTitleFontStyle,
				'font-size': generateCSSUnit(
					subTitleFontSize,
					subTitleFontSizeType
				),
				'line-height': generateCSSUnit(
					subTitleLineHeight,
					subTitleLineHeightType
				),
				'margin-top': generateCSSUnit( titleBottomSpacing, 'px' ),
				'text-transform': subtitletextTransform,
				'letter-spacing': generateCSSUnit(
					subtitleletterSpacing,
					subtitleLetterSpacingType
				),
			},
		' .wpcf__next-step-button-icon svg': {
			width: generateCSSUnit( iconSize, 'px' ),
			height: generateCSSUnit( iconSize, 'px' ),
			fill: iconColor,
		},
		' .wpcf__next-step-button-link:hover .wpcf__next-step-button-icon svg':
			{
				fill: iconHoverColor,
			},
	};

	selectors[ ' .wpcf__next-step-button-link' ] = {};
	// selectors[ ' .wpcf__next-step-button-link' ] = borderCSS;
	if ( 'gradient' === backgroundType ) {
		selectors[ ' .wpcf__next-step-button-link' ] = {
			// 'border-style': borderStyle,
			// 'border-color': borderColor,
			// 'border-width': generateCSSUnit( borderWidth, 'px' ),
			// 'border-radius': generateCSSUnit( borderRadius, 'px' ),
			'padding-top': generateCSSUnit( paddingBtnTop, paddingTypeDesktop ),
			'padding-bottom': generateCSSUnit(
				paddingBtnBottom,
				paddingTypeDesktop
			),
			'padding-left': generateCSSUnit(
				paddingBtnLeft,
				paddingTypeDesktop
			),
			'padding-right': generateCSSUnit(
				paddingBtnRight,
				paddingTypeDesktop
			),
			color: textColor,
			'text-align': textAlignment,
			...borderCSS,
		};
	}

	if ( 'image' === backgroundType ) {
		selectors[ ' .wpcf__next-step-button-link' ] = {
			opacity:
				typeof backgroundOpacity !== 'undefined'
					? backgroundOpacity / 100
					: '',
			'background-color': backgroundImageColor,
			// 'border-style': borderStyle,
			// 'border-color': borderColor,
			// 'border-width': generateCSSUnit( borderWidth, 'px' ),
			// 'border-radius': generateCSSUnit( borderRadius, 'px' ),
			'padding-top': generateCSSUnit( paddingBtnTop, paddingTypeDesktop ),
			'padding-bottom': generateCSSUnit(
				paddingBtnBottom,
				paddingTypeDesktop
			),
			'padding-left': generateCSSUnit(
				paddingBtnLeft,
				paddingTypeDesktop
			),
			'padding-right': generateCSSUnit(
				paddingBtnRight,
				paddingTypeDesktop
			),
			color: textColor,
			'background-image': backgroundImage
				? `url(${ backgroundImage.url })`
				: null,
			'background-position': position,
			'background-attachment': backgroundAttachment,
			'background-repeat': backgroundRepeat,
			'background-size': backgroundSize,
			'text-align': textAlignment,
			...borderCSS,
		};
	} else if ( 'color' === backgroundType ) {
		selectors[ ' .wpcf__next-step-button-link' ] = {
			opacity:
				typeof backgroundOpacity !== 'undefined'
					? backgroundOpacity / 100
					: '',
			'background-color': backgroundColor,
			// 'border-style': borderStyle,
			// 'border-color': borderColor,
			// 'border-width': generateCSSUnit( borderWidth, 'px' ),
			// 'border-radius': generateCSSUnit( borderRadius, 'px' ),
			'padding-top': generateCSSUnit( paddingBtnTop, paddingTypeDesktop ),
			'padding-bottom': generateCSSUnit(
				paddingBtnBottom,
				paddingTypeDesktop
			),
			'padding-left': generateCSSUnit(
				paddingBtnLeft,
				paddingTypeDesktop
			),
			'padding-right': generateCSSUnit(
				paddingBtnRight,
				paddingTypeDesktop
			),
			color: textColor,
			'text-align': textAlignment,
			...borderCSS,
		};
	} else if ( 'gradient' === backgroundType ) {
		selectors[ ' .wpcf__next-step-button-link' ][ 'background-color' ] =
			'transparent';
		selectors[ ' .wpcf__next-step-button-link' ].opacity =
			typeof backgroundOpacity !== 'undefined'
				? backgroundOpacity / 100
				: '';
		if ( gradientValue ) {
			selectors[ ' .wpcf__next-step-button-link' ][ 'background-image' ] =
				gradientValue;
		} else if ( 'linear' === gradientType ) {
			selectors[ ' .wpcf__next-step-button-link' ][
				'background-image'
			] = `linear-gradient(${ gradientAngle }deg, ${ gradientColor1 } ${ gradientLocation1 }%, ${ gradientColor2 } ${ gradientLocation2 }%)`;
		} else {
			selectors[ ' .wpcf__next-step-button-link' ][
				'background-image'
			] = `radial-gradient( at ${ gradientPosition }, ${ gradientColor1 } ${ gradientLocation1 }%, ${ gradientColor2 } ${ gradientLocation2 }%)`;
		}
	}

	if ( align === 'full' ) {
		selectors[ ' a.wpcf__next-step-button-link' ] = {
			width: '100%',
			'justify-content': 'center',
		};
	}

	const margin_type =
		'after_title' === iconPosition ||
		'after_title_sub_title' === iconPosition
			? 'margin-left'
			: 'margin-right';
	selectors[ ' .wpcf__next-step-button-icon svg' ][ margin_type ] =
		generateCSSUnit( iconSpacing, 'px' );

	tablet_selectors[ ' .wpcf__next-step-button-wrap' ] = {
		'text-align': talign,
	};
	tablet_selectors[ ' .wpcf__next-step-button-link' ] = {
		'padding-top': generateCSSUnit(
			paddingBtnTopTablet,
			paddingTypeTablet
		),
		'padding-bottom': generateCSSUnit(
			paddingBtnBottomTablet,
			paddingTypeTablet
		),
		'padding-left': generateCSSUnit(
			paddingBtnLeftTablet,
			paddingTypeTablet
		),
		'padding-right': generateCSSUnit(
			paddingBtnRightTablet,
			paddingTypeTablet
		),
		...borderCSSTablet,
	};
	tablet_selectors[
		' .wpcf__next-step-button-link .wpcf__next-step-button-content-wrap .wpcf__next-step-button-title-wrap'
	] = {
		'font-size': generateCSSUnit( titleFontSizeTablet, titleFontSizeType ),
		'line-height': generateCSSUnit(
			titleLineHeightTablet,
			titleLineHeightType
		),
		'letter-spacing': generateCSSUnit(
			titleLetterSpacingTablet,
			titleLetterSpacingType
		),
	};
	tablet_selectors[
		' .wpcf__next-step-button-link .wpcf__next-step-button-content-wrap .wpcf__next-step-button-sub-title'
	] = {
		'font-size': generateCSSUnit(
			subTitleFontSizeTablet,
			titleFontSizeType
		),
		'line-height': generateCSSUnit(
			subTitleLineHeightTablet,
			titleLineHeightType
		),
		'letter-spacing': generateCSSUnit(
			subtitleLetterSpacingTablet,
			subtitleLetterSpacingType
		),
	};

	if ( talign === 'full' ) {
		tablet_selectors[ ' a.wpcf__next-step-button-link' ] = {
			width: '100%',
			'justify-content': 'center',
		};
	}
	mobile_selectors[ ' .wpcf__next-step-button-wrap' ] = {
		'text-align': malign,
	};
	mobile_selectors[ ' .wpcf__next-step-button-link' ] = {
		'padding-top': generateCSSUnit(
			paddingBtnTopMobile,
			paddingTypeMobile
		),
		'padding-bottom': generateCSSUnit(
			paddingBtnBottomMobile,
			paddingTypeMobile
		),
		'padding-left': generateCSSUnit(
			paddingBtnLeftMobile,
			paddingTypeMobile
		),
		'padding-right': generateCSSUnit(
			paddingBtnRightMobile,
			paddingTypeMobile
		),
		...borderCSSMobile,
	};
	mobile_selectors[
		' .wpcf__next-step-button-link .wpcf__next-step-button-content-wrap .wpcf__next-step-button-title-wrap'
	] = {
		'font-size': generateCSSUnit( titleFontSizeMobile, titleFontSizeType ),
		'line-height': generateCSSUnit(
			titleLineHeightMobile,
			titleLineHeightType
		),
		'letter-spacing': generateCSSUnit(
			titleLetterSpacingMobile,
			titleLetterSpacingType
		),
	};
	mobile_selectors[
		' .wpcf__next-step-button-link .wpcf__next-step-button-content-wrap .wpcf__next-step-button-sub-title'
	] = {
		'font-size': generateCSSUnit(
			subTitleFontSizeMobile,
			titleFontSizeType
		),
		'line-height': generateCSSUnit(
			subTitleLineHeightMobile,
			titleLineHeightType
		),
		'letter-spacing': generateCSSUnit(
			subtitleLetterSpacingMobile,
			subtitleLetterSpacingType
		),
	};

	if ( malign === 'full' ) {
		mobile_selectors[ ' a.wpcf__next-step-button-link' ] = {
			width: '100%',
			'justify-content': 'center',
		};
	}

	const base_selector = `.editor-styles-wrapper .cf-block-${ props.clientId.substr(
		0,
		8
	) }`;

	let styling_css = generateCSS( selectors, base_selector );

	styling_css += generateCSS(
		tablet_selectors,
		`${ base_selector }.cf-editor-preview-mode-tablet`,
		true,
		'tablet'
	);

	styling_css += generateCSS(
		mobile_selectors,
		`${ base_selector }.cf-editor-preview-mode-mobile`,
		true,
		'mobile'
	);

	return styling_css;
}

export default styling;
