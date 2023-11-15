/**
 * Returns Dynamic Generated CSS
 */

import generateCSS from '@Controls/generateCSS';
import generateCSSUnit from '@Controls/generateCSSUnit';
import generateBorderCSS from '@Controls/generateBorderCSS';

function styling( props ) {
	const {
		// General.
		generalPrimaryColor,
		generalFontFamily,
		generalFontWeight,
		generalFontSize,
		generalFontSizeType,
		generalFontSizeTablet,
		generalFontSizeMobile,
		generalLineHeightType,
		generalLineHeight,
		generalLineHeightTablet,
		generalLineHeightMobile,
		generalTransform,
		generalLetterSpacing,
		generalLetterSpacingTablet,
		generalLetterSpacingMobile,
		generalLetterSpacingType,
		// Input Fields.
		inputFieldFontFamily,
		inputFieldFontWeight,
		inputFieldFontSize,
		inputFieldFontSizeType,
		inputFieldFontSizeTablet,
		inputFieldFontSizeMobile,
		inputFieldLineHeightType,
		inputFieldLineHeight,
		inputFieldLineHeightTablet,
		inputFieldLineHeightMobile,
		inputFieldLabelColor,
		inputFieldBackgroundColor,
		inputFieldTextPlaceholderColor,
		inputFieldTransform,
		inputFieldLetterSpacing,
		inputFieldLetterSpacingTablet,
		inputFieldLetterSpacingMobile,
		inputFieldLetterSpacingType,
		// inputFieldBorderStyle,
		// inputFieldBorderWidth,
		// inputFieldBorderRadius,
		// inputFieldBorderColor,
		// Submit Button.
		submitButtonFontFamily,
		submitButtonFontWeight,
		submitButtonFontSize,
		submitButtonFontSizeType,
		submitButtonFontSizeTablet,
		submitButtonFontSizeMobile,
		submitButtonLineHeightType,
		submitButtonLineHeight,
		submitButtonLineHeightTablet,
		submitButtonLineHeightMobile,
		submitButtonTextColor,
		submitButtonBackgroundColor,
		submitButtonTextHoverColor,
		submitButtonBackgroundHoverColor,
		submitButtonTransform,
		submitButtonLetterSpacing,
		submitButtonLetterSpacingTablet,
		submitButtonLetterSpacingMobile,
		submitButtonLetterSpacingType,
		// submitButtonBorderStyle,
		// submitButtonBorderWidth,
		// submitButtonBorderRadius,
		// submitButtonBorderColor,
		// submitButtonBorderHoverColor,
		boxShadowColor,
		boxShadowHOffset,
		boxShadowVOffset,
		boxShadowBlur,
		boxShadowSpread,
		boxShadowPosition,

		//New
		// inputFieldBorderHoverColor,
		inputFieldBorderHColor,
		submitButtonBorderHColor,

		generalFontStyle,
		submitButtonFontStyle,
		inputFieldFontStyle,
	} = props.attributes;

	let selectors = {};
	const tablet_selectors = {};
	const mobile_selectors = {};

	const fieldborderCSS = generateBorderCSS( props.attributes, 'inputField' );
	const fieldborderCSSTablet = generateBorderCSS(
		props.attributes,
		'inputField',
		'tablet'
	);
	const fieldborderCSSMobile = generateBorderCSS(
		props.attributes,
		'binputFieldtn',
		'mobile'
	);

	const btnborderCSS = generateBorderCSS( props.attributes, 'submitButton' );
	const btnborderCSSTablet = generateBorderCSS(
		props.attributes,
		'submitButton',
		'tablet'
	);
	const btnborderCSSMobile = generateBorderCSS(
		props.attributes,
		'submitButton',
		'mobile'
	);

	let boxShadowPositionCSS = boxShadowPosition;

	if ( 'outset' === boxShadowPosition ) {
		boxShadowPositionCSS = '';
	}

	selectors = {
		// General.
		' .wcf-optin-form .checkout.woocommerce-checkout #order_review .woocommerce-checkout-payment button#place_order':
			{
				'background-color': generalPrimaryColor,
				'border-color': generalPrimaryColor,
			},
		' .wcf-optin-form .checkout.woocommerce-checkout label, .wcf-optin-form .checkout.woocommerce-checkout span input.input-text, .wcf-optin-form .checkout.woocommerce-checkout .wcf-order-wrap #order_review .woocommerce-checkout-payment button#place_order':
			{
				'font-family': generalFontFamily,
				'font-weight': generalFontWeight,
				'font-style': generalFontStyle,
				'font-size': generateCSSUnit(
					generalFontSize,
					generalFontSizeType
				),
				'line-height': generateCSSUnit(
					generalLineHeight,
					generalLineHeightType
				),
				'text-transform': generalTransform,
				'letter-spacing': generateCSSUnit(
					generalLetterSpacing,
					generalLetterSpacingType
				),
			},

		// Input Fields.
		' .wcf-optin-form .checkout.woocommerce-checkout label': {
			color: inputFieldLabelColor,
		},
		' .wcf-optin-form .checkout.woocommerce-checkout span input.input-text':
			{
				color: inputFieldTextPlaceholderColor,
				'background-color': inputFieldBackgroundColor,
				// 'border-style': inputFieldBorderStyle,
				// 'border-width': generateCSSUnit( inputFieldBorderWidth, 'px' ),
				// 'border-radius': generateCSSUnit( inputFieldBorderRadius, 'px' ),
				// 'border-color': inputFieldBorderColor,
				...fieldborderCSS,
			},
		' .wcf-optin-form .checkout.woocommerce-checkout span input.input-text:hover':
			{
				'border-color': inputFieldBorderHColor,
			},
		' .wcf-optin-form .checkout.woocommerce-checkout label, .wcf-optin-form .checkout.woocommerce-checkout span input.input-text':
			{
				'font-family': inputFieldFontFamily,
				'font-weight': inputFieldFontWeight,
				'font-style': inputFieldFontStyle,
				'font-size': generateCSSUnit(
					inputFieldFontSize,
					inputFieldFontSizeType
				),
				'line-height': generateCSSUnit(
					inputFieldLineHeight,
					inputFieldLineHeightType
				),
				'text-transform': inputFieldTransform,
				'letter-spacing': generateCSSUnit(
					inputFieldLetterSpacing,
					inputFieldLetterSpacingType
				),
			},

		// Submit Button.
		' .wcf-optin-form .checkout.woocommerce-checkout .wcf-order-wrap #order_review .woocommerce-checkout-payment button#place_order':
			{
				color: submitButtonTextColor,
				'background-color': submitButtonBackgroundColor,
				// 'border-style': submitButtonBorderStyle,
				// 'border-width': generateCSSUnit( submitButtonBorderWidth, 'px' ),
				// 'border-radius': generateCSSUnit( submitButtonBorderRadius, 'px' ),
				// 'border-color': submitButtonBorderColor,
				'font-family': submitButtonFontFamily,
				'font-weight': submitButtonFontWeight,
				'font-style': submitButtonFontStyle,
				'font-size': generateCSSUnit(
					submitButtonFontSize,
					submitButtonFontSizeType
				),
				'line-height': generateCSSUnit(
					submitButtonLineHeight,
					submitButtonLineHeightType
				),
				'text-transform': submitButtonTransform,
				'letter-spacing': generateCSSUnit(
					submitButtonLetterSpacing,
					submitButtonLetterSpacingType
				),
				'box-shadow':
					generateCSSUnit( boxShadowHOffset, 'px' ) +
					' ' +
					generateCSSUnit( boxShadowVOffset, 'px' ) +
					' ' +
					generateCSSUnit( boxShadowBlur, 'px' ) +
					' ' +
					generateCSSUnit( boxShadowSpread, 'px' ) +
					' ' +
					boxShadowColor +
					' ' +
					boxShadowPositionCSS,
				...btnborderCSS,
			},
		' .wcf-optin-form .checkout.woocommerce-checkout .wcf-order-wrap #order_review .woocommerce-checkout-payment button#place_order:hover':
			{
				color: submitButtonTextHoverColor,
				'background-color': submitButtonBackgroundHoverColor,
				'border-color': submitButtonBorderHColor,
			},
	};

	// General.
	tablet_selectors[
		' .wcf-optin-form .checkout.woocommerce-checkout label, .wcf-optin-form .checkout.woocommerce-checkout span input.input-text, .wcf-optin-form .checkout.woocommerce-checkout .wcf-order-wrap #order_review .woocommerce-checkout-payment button#place_order'
	] = {
		'font-size': generateCSSUnit(
			generalFontSizeTablet,
			generalFontSizeType
		),
		'line-height': generateCSSUnit(
			generalLineHeightTablet,
			generalLineHeightType
		),
		'letter-spacing': generateCSSUnit(
			generalLetterSpacingTablet,
			generalLetterSpacingType
		),
	};
	// Input Fields.
	tablet_selectors[
		' .wcf-optin-form .checkout.woocommerce-checkout label, .wcf-optin-form .checkout.woocommerce-checkout span input.input-text'
	] = {
		'font-size': generateCSSUnit(
			inputFieldFontSizeTablet,
			inputFieldFontSizeType
		),
		'line-height': generateCSSUnit(
			inputFieldLineHeightTablet,
			inputFieldLineHeightType
		),
		'letter-spacing': generateCSSUnit(
			inputFieldLetterSpacingTablet,
			inputFieldLetterSpacingType
		),
		...fieldborderCSSTablet,
	};
	// Submit Button.
	tablet_selectors[
		' .wcf-optin-form .checkout.woocommerce-checkout .wcf-order-wrap #order_review .woocommerce-checkout-payment button#place_order'
	] = {
		'font-size': generateCSSUnit(
			submitButtonFontSizeTablet,
			submitButtonFontSizeType
		),
		'line-height': generateCSSUnit(
			submitButtonLineHeightTablet,
			submitButtonLineHeightType
		),
		'letter-spacing': generateCSSUnit(
			submitButtonLetterSpacingTablet,
			submitButtonLetterSpacingType
		),
		...btnborderCSSTablet,
	};

	// General.
	mobile_selectors[
		' .wcf-optin-form .checkout.woocommerce-checkout label, .wcf-optin-form .checkout.woocommerce-checkout span input.input-text, .wcf-optin-form .checkout.woocommerce-checkout .wcf-order-wrap #order_review .woocommerce-checkout-payment button#place_order'
	] = {
		'font-size': generateCSSUnit(
			generalFontSizeMobile,
			generalFontSizeType
		),
		'line-height': generateCSSUnit(
			generalLineHeightMobile,
			generalLineHeightType
		),
		'letter-spacing': generateCSSUnit(
			generalLetterSpacingMobile,
			generalLetterSpacingType
		),
	};
	// Input Fields.
	mobile_selectors[
		' .wcf-optin-form .checkout.woocommerce-checkout label, .wcf-optin-form .checkout.woocommerce-checkout span input.input-text'
	] = {
		'font-size': generateCSSUnit(
			inputFieldFontSizeMobile,
			inputFieldFontSizeType
		),
		'line-height': generateCSSUnit(
			inputFieldLineHeightMobile,
			inputFieldLineHeightType
		),
		'letter-spacing': generateCSSUnit(
			inputFieldLetterSpacingMobile,
			inputFieldLetterSpacingType
		),
		...fieldborderCSSMobile,
	};
	// Submit Button.
	mobile_selectors[
		' .wcf-optin-form .checkout.woocommerce-checkout .wcf-order-wrap #order_review .woocommerce-checkout-payment button#place_order'
	] = {
		'font-size': generateCSSUnit(
			submitButtonFontSizeMobile,
			submitButtonFontSizeType
		),
		'line-height': generateCSSUnit(
			submitButtonLineHeightMobile,
			submitButtonLineHeightType
		),
		'letter-spacing': generateCSSUnit(
			submitButtonLetterSpacingMobile,
			submitButtonLetterSpacingType
		),
		...btnborderCSSMobile,
	};

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
