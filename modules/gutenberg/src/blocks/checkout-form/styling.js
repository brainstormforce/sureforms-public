import generateCSS from '@Controls/generateCSS';
import generateCSSUnit from '@Controls/generateCSSUnit';
import generateBorderCSS from '@Controls/generateBorderCSS';

function styling( props ) {
	const {
		headBgColor,
		headFontSize,
		headFontSizeType,
		headFontSizeTablet,
		headFontSizeMobile,
		headFontFamily,
		headFontWeight,
		headLineHeightType,
		headLineHeight,
		headLineHeightTablet,
		headLineHeightMobile,
		headTransform,
		headLetterSpacing,
		headLetterSpacingTablet,
		headLetterSpacingMobile,
		headLetterSpacingType,
		buttonFontSize,
		buttonFontSizeType,
		buttonFontSizeTablet,
		buttonFontSizeMobile,
		buttonFontFamily,
		buttonFontWeight,
		buttonLineHeightType,
		buttonLineHeight,
		buttonLineHeightTablet,
		buttonLineHeightMobile,
		buttonTextColor,
		buttonTransform,
		buttonLetterSpacing,
		buttonLetterSpacingTablet,
		buttonLetterSpacingMobile,
		buttonLetterSpacingType,
		// buttonBgColor,
		buttonTextHoverColor,
		buttonBgHoverColor,
		// buttonBorderColor,
		// buttonBorderHoverColor,
		// buttonBorderStyle,
		// buttonBorderWidth,
		// buttonBorderRadius,
		paymentdescriptionColor,
		paymenttitleColor,
		sectionbgColor,
		informationbgColor,
		sectionBorderRadius,
		fieldLabelColor,
		fieldBgColor,
		fieldInputColor,
		// fieldBorderStyle,
		// fieldBorderWidth,
		// fieldBorderRadius,
		// fieldBorderColor,
		inputFontSize,
		inputFontSizeType,
		inputFontSizeTablet,
		inputFontSizeMobile,
		inputFontFamily,
		inputFontWeight,
		inputLineHeightType,
		inputLineHeight,
		inputLineHeightTablet,
		inputLineHeightMobile,
		inputTransform,
		inputLetterSpacing,
		inputLetterSpacingTablet,
		inputLetterSpacingMobile,
		inputLetterSpacingType,
		globaltextColor,
		globalbgColor,
		globalFontSize,
		globalFontSizeType,
		globalFontSizeTablet,
		globalFontSizeMobile,
		globalFontFamily,
		globalFontWeight,
		globalLineHeightType,
		globalLineHeight,
		globalLineHeightTablet,
		globalLineHeightMobile,
		globalTransform,
		globalLetterSpacing,
		globalLetterSpacingTablet,
		globalLetterSpacingMobile,
		globalLetterSpacingType,
		backgroundType,
		backgroundImageColor,
		backgroundOpacity,
		backgroundColor,
		backgroundHoverColor,
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
		boxShadowColor,
		boxShadowHOffset,
		boxShadowVOffset,
		boxShadowBlur,
		boxShadowSpread,
		boxShadowPosition,
		orderReviewColumnColor,
		orderReviewColumnTextColor,
		// fieldBorderHoverColor,

		paymentSectionpaddingTop,
		paymentSectionpaddingBottom,
		paymentSectionpaddingLeft,
		paymentSectionpaddingRight,
		paymentSectionpaddingTopTablet,
		paymentSectionpaddingRightTablet,
		paymentSectionpaddingBottomTablet,
		paymentSectionpaddingLeftTablet,
		paymentSectionpaddingTopMobile,
		paymentSectionpaddingRightMobile,
		paymentSectionpaddingBottomMobile,
		paymentSectionpaddingLeftMobile,

		paymentSectionpaddingTypeDesktop,
		paymentSectionpaddingTypeTablet,
		paymentSectionpaddingTypeMobile,

		paymentSectionMarginTop,
		paymentSectionMarginBottom,
		paymentSectionMarginLeft,
		paymentSectionMarginRight,
		paymentSectionMarginTopTablet,
		paymentSectionMarginRightTablet,
		paymentSectionMarginBottomTablet,
		paymentSectionMarginLeftTablet,
		paymentSectionMarginTopMobile,
		paymentSectionMarginRightMobile,
		paymentSectionMarginBottomMobile,
		paymentSectionMarginLeftMobile,

		paymentSectionMarginTypeDesktop,
		paymentSectionMarginTypeTablet,
		paymentSectionMarginTypeMobile,
		fieldBorderHColor,
		buttonBorderHColor,

		buttonFontStyle,
		inputFontStyle,
		globalFontStyle,
		headFontStyle,
	} = props.attributes;
	const position = backgroundPosition.replace( '-', ' ' );
	let boxShadowPositionCSS = boxShadowPosition;

	if ( 'outset' === boxShadowPosition ) {
		boxShadowPositionCSS = '';
	}

	const fieldborderCSS = generateBorderCSS( props.attributes, 'field' );
	const fieldborderCSSTablet = generateBorderCSS(
		props.attributes,
		'field',
		'tablet'
	);
	const fieldborderCSSMobile = generateBorderCSS(
		props.attributes,
		'field',
		'mobile'
	);

	const buttonborderCSS = generateBorderCSS( props.attributes, 'button' );
	const buttonborderCSSTablet = generateBorderCSS(
		props.attributes,
		'button',
		'tablet'
	);
	const buttonborderCSSMobile = generateBorderCSS(
		props.attributes,
		'button',
		'mobile'
	);

	const selectors = {
		' .wcf-embed-checkout-form .woocommerce, .wcf-embed-checkout-form .woocommerce p':
			{
				'font-size': generateCSSUnit(
					globalFontSize,
					globalFontSizeType
				),
				'font-weight': globalFontWeight,
				'font-family': globalFontFamily,
				'font-style': globalFontStyle,
				'line-height': generateCSSUnit(
					globalLineHeight,
					globalLineHeightType
				),
				'text-transform': globalTransform,
				'letter-spacing': generateCSSUnit(
					globalLetterSpacing,
					globalLetterSpacingType
				),
			},

		' .wcf-embed-checkout-form .woocommerce h3, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout h3, .wcf-embed-checkout-form .woocommerce-checkout #order_review_heading, .wcf-embed-checkout-form .woocommerce h3, .wcf-embed-checkout-form .woocommerce h3 span':
			{
				color: headBgColor,
				'font-size': generateCSSUnit( headFontSize, headFontSizeType ),
				'font-weight': headFontWeight,
				'font-family': headFontFamily,
				'font-style': headFontStyle,
				'line-height': generateCSSUnit(
					headLineHeight,
					headLineHeightType
				),
				'text-transform': headTransform,
				'letter-spacing': generateCSSUnit(
					headLetterSpacing,
					headLetterSpacingType
				),
			},

		' .wcf-embed-checkout-form .woocommerce #payment input[type=radio]:checked:before, .wcf-embed-checkout-form .woocommerce #payment button, .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form .woocommerce form.woocommerce-form-login .form-row button, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon:hover, .wcf-embed-checkout-form .woocommerce-checkout form.woocommerce-form-login .button, .wcf-embed-checkout-form .woocommerce-checkout form.checkout_coupon .button, .wcf-embed-checkout-form .woocommerce-checkout form.login .button:hover, .wcf-embed-checkout-form .woocommerce-checkout form.checkout_coupon .button:hover, .wcf-embed-checkout-form .woocommerce #payment #place_order:hover, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small:hover, .wcf-embed-checkout-form-two-step .wcf-embed-checkout-form-steps .step-one.wcf-current:before, .wcf-embed-checkout-form-two-step .wcf-embed-checkout-form-steps .step-two.wcf-current:before, .wcf-embed-checkout-form-two-step .wcf-embed-checkout-form-steps .steps.wcf-current:before, .wcf-embed-checkout-form-two-step .wcf-embed-checkout-form-note, body .wcf-pre-checkout-offer-wrapper .wcf-nav-bar-step.active .wcf-progress-nav-step, body .wcf-pre-checkout-offer-wrapper .wcf-nav-bar-step.active .wcf-nav-bar-step-line:before, body .wcf-pre-checkout-offer-wrapper .wcf-nav-bar-step.active .wcf-nav-bar-step-line:after':
			{
				'background-color': globalbgColor,
			},
		' .wcf-embed-checkout-form-two-step .wcf-embed-checkout-form-note:before':
			{
				'border-top-color': globalbgColor,
			},
		' .wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns .wcf-next-button, .wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns a.wcf-next-button, .wcf-embed-checkout-form form.checkout_coupon .button, body .wcf-pre-checkout-offer-wrapper #wcf-pre-checkout-offer-content button.wcf-pre-checkout-offer-btn':
			{
				'background-color': globalbgColor,
			},
		' .wcf-embed-checkout-form, .wcf-embed-checkout-form .woocommerce a, .wcf-embed-checkout-form #payment .woocommerce-privacy-policy-text p, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout #payment .woocommerce-privacy-policy-text p':
			{
				color: globaltextColor,
			},
		" .wcf-embed-checkout-form .woocommerce .woocommerce-checkout .product-name .remove:hover, .wcf-embed-checkout-form .woocommerce #payment input[type=checkbox]:checked:before, .wcf-embed-checkout-form .woocommerce .woocommerce-shipping-fields [type='checkbox']:checked:before, .wcf-embed-checkout-form .woocommerce-info::before, .wcf-embed-checkout-form .woocommerce-message::before, .wcf-embed-checkout-form .woocommerce a, .wcf-embed-checkout-form-two-step .wcf-embed-checkout-form-steps .wcf-current .step-name, body .wcf-pre-checkout-offer-wrapper .wcf-content-main-head .wcf-content-modal-title .wcf_first_name":
			{
				color: globalbgColor,
			},
		' .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form .woocommerce #payment button, .wcf-embed-checkout-form .woocommerce form.woocommerce-form-login .form-row button, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form .woocommerce-checkout form.woocommerce-form-login .button, .wcf-embed-checkout-form .woocommerce-checkout form.checkout_coupon .button, .wcf-embed-checkout-form form.checkout_coupon .button, .wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns .wcf-next-button, body .wcf-pre-checkout-offer-wrapper #wcf-pre-checkout-offer-content button.wcf-pre-checkout-offer-btn, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout.wcf-modern-skin-multistep .woocommerce form .wcf-multistep-nav-btn-group .wcf-multistep-nav-next-btn':
			{
				'background-color': globalbgColor,
				'border-color': globalbgColor,
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
			},
		' .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form .woocommerce #payment button, .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form.wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns .wcf-next-button, .wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns a.wcf-next-button, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form .woocommerce-checkout form.woocommerce-form-login .button, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout.wcf-modern-skin-multistep .woocommerce form .wcf-multistep-nav-btn-group .wcf-multistep-nav-next-btn, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .woocommerce #payment button':
			{
				color: buttonTextColor,

				'font-size': generateCSSUnit(
					buttonFontSize,
					buttonFontSizeType
				),
				'font-weight': buttonFontWeight,
				'font-family': buttonFontFamily,
				'font-style': buttonFontStyle,
				'line-height': generateCSSUnit(
					buttonLineHeight,
					buttonLineHeightType
				),
				'text-transform': buttonTransform,
				'letter-spacing': generateCSSUnit(
					buttonLetterSpacing,
					buttonLetterSpacingType
				),
				'background-color': backgroundColor,
				...buttonborderCSS,
			},
		// '.wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .woocommerce-shipping-fields label.checkbox': {
		// 	'letter-spacing': generateCSSUnit(
		// 		buttonLetterSpacing,
		// 		buttonLetterSpacingType
		// 	),
		// },
		' .wcf-embed-checkout-form .woocommerce #payment #place_order:hover, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small:hover, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon:hover, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout.wcf-modern-skin-multistep .woocommerce form .wcf-multistep-nav-btn-group .wcf-multistep-nav-next-btn:hover':
			{
				color: buttonTextHoverColor,
				'border-color': buttonBorderHColor
					? buttonBorderHColor
					: globalbgColor,
				'background-color': buttonBgHoverColor,
			},
		' .wcf-embed-checkout-form .woocommerce-checkout #payment ul.payment_methods':
			{
				'background-color': sectionbgColor,
				padding:
					paymentSectionpaddingTop +
					paymentSectionpaddingTypeDesktop +
					' ' +
					paymentSectionpaddingRight +
					paymentSectionpaddingTypeDesktop +
					' ' +
					paymentSectionpaddingBottom +
					paymentSectionpaddingTypeDesktop +
					' ' +
					paymentSectionpaddingLeft +
					paymentSectionpaddingTypeDesktop,
				margin:
					paymentSectionMarginTop +
					paymentSectionMarginTypeDesktop +
					' ' +
					paymentSectionMarginRight +
					paymentSectionMarginTypeDesktop +
					' ' +
					paymentSectionMarginBottom +
					paymentSectionMarginTypeDesktop +
					' ' +
					paymentSectionMarginLeft +
					paymentSectionMarginTypeDesktop,
				'border-radius': generateCSSUnit( sectionBorderRadius, 'px' ),
			},
		' .wcf-embed-checkout-form .woocommerce-checkout #payment label a, .wcf-embed-checkout-form .woocommerce-checkout #payment label, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .woocommerce #payment label':
			{
				color: paymenttitleColor,
			},
		' .wcf-embed-checkout-form #payment .woocommerce-privacy-policy-text p':
			{
				color: paymentdescriptionColor,
			},
		' .wcf-embed-checkout-form .woocommerce-checkout #payment div.payment_box':
			{
				'background-color': informationbgColor,
				color: paymentdescriptionColor,
			},
		' .wcf-embed-checkout-form .woocommerce form p.form-row label': {
			'font-size': generateCSSUnit( inputFontSize, inputFontSizeType ),
			'font-weight': inputFontWeight,
			'font-family': inputFontFamily,
			'font-style': inputFontStyle,
			'line-height': generateCSSUnit(
				inputLineHeight,
				inputLineHeightType
			),
			'text-transform': inputTransform,
			'letter-spacing': generateCSSUnit(
				inputLetterSpacing,
				inputLetterSpacingType
			),
			color: fieldLabelColor,
		},
		' .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout table.shop_table':
			{
				'background-color': orderReviewColumnColor,
			},
		' .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout table.shop_table th, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout table.shop_table td':
			{
				color: orderReviewColumnTextColor,
			},
		' .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .woocommerce form .form-row input.input-text:focus, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .woocommerce form .form-row textarea:focus, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .woocommerce #order_review .wcf-custom-coupon-field input.input-text:focus':
			{
				'border-color': globalbgColor,
				'box-shadow': '0 0 0 1px ' + globalbgColor,
			},
		" .wcf-embed-checkout-form .woocommerce form .form-row input.input-text, .wcf-embed-checkout-form .woocommerce form .form-row select, .wcf-embed-checkout-form .woocommerce form .form-row textarea, .wcf-embed-checkout-form #order_review .wcf-custom-coupon-field input[type='text'], .wcf-embed-checkout-form .woocommerce form .form-row select#billing_country, .wcf-embed-checkout-form .woocommerce form .form-row select, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .woocommerce form .form-row select, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .woocommerce form .form-row textarea":
			{
				'background-color': fieldBgColor,
				// 'border-radius': generateCSSUnit( fieldBorderRadius, 'px' ),
				// 'border-color': fieldBorderColor,
				// 'border-style': fieldBorderStyle,
				// 'border-width': generateCSSUnit( fieldBorderWidth, 'px' ),
				color: fieldInputColor,
				'letter-spacing': generateCSSUnit(
					inputLetterSpacing,
					inputLetterSpacingType
				),
				...fieldborderCSS,
			},

		" .wcf-embed-checkout-form .woocommerce form .form-row input.input-text:hover, .wcf-embed-checkout-form .woocommerce form .form-row select:hover, .wcf-embed-checkout-form .woocommerce form .form-row textarea:hover, .wcf-embed-checkout-form #order_review .wcf-custom-coupon-field input[type='text']:hover, .wcf-embed-checkout-form .woocommerce form .form-row select#billing_country:hover, .wcf-embed-checkout-form .woocommerce form .form-row select:hover, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .woocommerce form .form-row select:hover":
			{
				'border-color': fieldBorderHColor,
			},
		" .wcf-embed-checkout-form .woocommerce form .form-row input.input-text, .wcf-embed-checkout-form .woocommerce form .form-row textarea, .wcf-embed-checkout-form .woocommerce form .form-row select#billing_country, .wcf-embed-checkout-form .woocommerce form .form-row select#billing_state, span#select2-billing_country-container, .wcf-embed-checkout-form .select2-container--default .select2-selection--single .select2-selection__rendered, .wcf-embed-checkout-form #order_review .wcf-custom-coupon-field input[type='text'], .wcf-embed-checkout-form .woocommerce form .form-row input.input-text, .wcf-embed-checkout-form .woocommerce form .form-row textarea, .wcf-embed-checkout-form .select2-container--default .select2-selection--single, .wcf-embed-checkout-form .woocommerce form .form-row select, .wcf-embed-checkout-form .woocommerce form .form-row select, .wcf-embed-checkout-form ::placeholder, .wcf-embed-checkout-form ::-webkit-input-placeholder":
			{
				'font-size': generateCSSUnit(
					inputFontSize,
					inputFontSizeType
				),
				'font-weight': inputFontWeight,
				'font-family': inputFontFamily,
				'font-style': inputFontStyle,
				'line-height': generateCSSUnit(
					inputLineHeight,
					inputLineHeightType
				),
				'text-transform': inputTransform,
				'letter-spacing': generateCSSUnit(
					inputLetterSpacing,
					inputLetterSpacingType
				),
				color: fieldInputColor,
			},
		" .wcf-embed-checkout-form .woocommerce #payment [type='radio']:checked + label, .wcf-embed-checkout-form .woocommerce #payment [type='radio']:not(:checked) + label":
			{
				'font-size': generateCSSUnit(
					inputFontSize,
					inputFontSizeType
				),
				'font-weight': inputFontWeight,
				'font-family': inputFontFamily,
				'font-style': inputFontStyle,
				'line-height': generateCSSUnit(
					inputLineHeight,
					inputLineHeightType
				),
				'text-transform': inputTransform,
				'letter-spacing': generateCSSUnit(
					inputLetterSpacing,
					inputLetterSpacingType
				),
			},
		' .wcf-embed-checkout-form .woocommerce-checkout #payment div.payment_box::before ':
			{
				'border-bottom-color': informationbgColor,
			},
		' .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout.wcf-modern-skin-multistep .wcf-multistep-checkout-breadcrumb a.wcf-current-step':
			{
				color: globalbgColor,
			},
	};

	if ( 'gradient' === backgroundType ) {
		selectors[
			' .wcf-embed-checkout-form .woocommerce #payment button, .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form .woocommerce form.woocommerce-form-login .form-row button, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form .woocommerce-checkout form.woocommerce-form-login .button, .wcf-embed-checkout-form .woocommerce-checkout form.checkout_coupon .button, .wcf-embed-checkout-form form.checkout_coupon .button, .wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns a.wcf-next-button, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout.wcf-modern-skin-multistep .woocommerce form .wcf-multistep-nav-btn-group .wcf-multistep-nav-next-btn'
		] = {
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
		};
	}

	if ( 'image' === backgroundType ) {
		selectors[
			' .wcf-embed-checkout-form .woocommerce #payment button, .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form .woocommerce form.woocommerce-form-login .form-row button, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form .woocommerce-checkout form.woocommerce-form-login .button, .wcf-embed-checkout-form .woocommerce-checkout form.checkout_coupon .button, .wcf-embed-checkout-form form.checkout_coupon .button, .wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns a.wcf-next-button, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout.wcf-modern-skin-multistep .woocommerce form .wcf-multistep-nav-btn-group .wcf-multistep-nav-next-btn'
		] = {
			opacity:
				typeof backgroundOpacity !== 'undefined'
					? backgroundOpacity / 100
					: '',
			'background-color': backgroundImageColor,
			'background-image': backgroundImage
				? `url(${ backgroundImage.url })`
				: null,
			'background-position': position,
			'background-attachment': backgroundAttachment,
			'background-repeat': backgroundRepeat,
			'background-size': backgroundSize,
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
		};
	} else if ( 'color' === backgroundType ) {
		selectors[
			' .wcf-embed-checkout-form .woocommerce #payment button, .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form .woocommerce form.woocommerce-form-login .form-row button, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form .woocommerce-checkout form.woocommerce-form-login .button, .wcf-embed-checkout-form .woocommerce-checkout form.checkout_coupon .button, .wcf-embed-checkout-form form.checkout_coupon .button, .wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns a.wcf-next-button, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout.wcf-modern-skin-multistep .woocommerce form .wcf-multistep-nav-btn-group span.wcf-multistep-nav-next-btn'
		] = {
			opacity:
				typeof backgroundOpacity !== 'undefined'
					? backgroundOpacity / 100
					: '',
			'background-color': backgroundColor,
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
		};
		selectors[
			' .wcf-embed-checkout-form .woocommerce #order_review button:hover, .wcf-embed-checkout-form .woocommerce form.woocommerce-form-login .form-row button:hover, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small:hover, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon:hover, .wcf-embed-checkout-form .woocommerce-checkout form.woocommerce-form-login .button:hover, .wcf-embed-checkout-form .woocommerce-checkout form.checkout_coupon .button:hover, .wcf-embed-checkout-form form.checkout_coupon .button:hover, .wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns a.wcf-next-button:hover, .wcf-embed-checkout-form .woocommerce #payment #place_order:hover, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout.wcf-modern-skin-multistep .woocommerce form .wcf-multistep-nav-btn-group span.wcf-multistep-nav-next-btn:hover'
		] = {
			'background-color': backgroundHoverColor,
		};
	} else if ( 'gradient' === backgroundType ) {
		selectors[
			' .wcf-embed-checkout-form .woocommerce #payment button, .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form .woocommerce form.woocommerce-form-login .form-row button, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form .woocommerce-checkout form.woocommerce-form-login .button, .wcf-embed-checkout-form .woocommerce-checkout form.checkout_coupon .button, .wcf-embed-checkout-form form.checkout_coupon .button, .wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns a.wcf-next-button, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout.wcf-modern-skin-multistep .woocommerce form .wcf-multistep-nav-btn-group .wcf-multistep-nav-next-btn'
		][ 'background-color' ] = 'transparent';
		selectors[
			' .wcf-embed-checkout-form .woocommerce #payment button, .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form .woocommerce form.woocommerce-form-login .form-row button, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form .woocommerce-checkout form.woocommerce-form-login .button, .wcf-embed-checkout-form .woocommerce-checkout form.checkout_coupon .button, .wcf-embed-checkout-form form.checkout_coupon .button, .wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns a.wcf-next-button, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout.wcf-modern-skin-multistep .woocommerce form .wcf-multistep-nav-btn-group .wcf-multistep-nav-next-btn'
		].opacity =
			typeof backgroundOpacity !== 'undefined'
				? backgroundOpacity / 100
				: '';
		if ( gradientValue ) {
			selectors[
				' .wcf-embed-checkout-form .woocommerce #payment button, .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form .woocommerce form.woocommerce-form-login .form-row button, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form .woocommerce-checkout form.woocommerce-form-login .button, .wcf-embed-checkout-form .woocommerce-checkout form.checkout_coupon .button, .wcf-embed-checkout-form form.checkout_coupon .button, .wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns a.wcf-next-button, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout.wcf-modern-skin-multistep .woocommerce form .wcf-multistep-nav-btn-group .wcf-multistep-nav-next-btn'
			][ 'background-image' ] = gradientValue;
		} else if ( 'linear' === gradientType ) {
			selectors[
				' .wcf-embed-checkout-form .woocommerce #payment button, .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form .woocommerce form.woocommerce-form-login .form-row button, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form .woocommerce-checkout form.woocommerce-form-login .button, .wcf-embed-checkout-form .woocommerce-checkout form.checkout_coupon .button, .wcf-embed-checkout-form form.checkout_coupon .button, .wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns a.wcf-next-button, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout.wcf-modern-skin-multistep .woocommerce form .wcf-multistep-nav-btn-group .wcf-multistep-nav-next-btn'
			][
				'background-image'
			] = `linear-gradient(${ gradientAngle }deg, ${ gradientColor1 } ${ gradientLocation1 }%, ${ gradientColor2 } ${ gradientLocation2 }%)`;
		} else {
			selectors[
				' .wcf-embed-checkout-form .woocommerce #payment button, .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form .woocommerce form.woocommerce-form-login .form-row button, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form .woocommerce-checkout form.woocommerce-form-login .button, .wcf-embed-checkout-form .woocommerce-checkout form.checkout_coupon .button, .wcf-embed-checkout-form form.checkout_coupon .button, .wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns a.wcf-next-button, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout.wcf-modern-skin-multistep .woocommerce form .wcf-multistep-nav-btn-group .wcf-multistep-nav-next-btn'
			][
				'background-image'
			] = `radial-gradient( at ${ gradientPosition }, ${ gradientColor1 } ${ gradientLocation1 }%, ${ gradientColor2 } ${ gradientLocation2 }%)`;
		}
	}

	const tablet_selectors = {
		' .wcf-embed-checkout-form .woocommerce': {
			'font-size': generateCSSUnit(
				globalFontSizeTablet,
				globalFontSizeType
			),
			'line-height': generateCSSUnit(
				globalLineHeightTablet,
				globalLineHeightType
			),
			'letter-spacing': generateCSSUnit(
				globalLetterSpacingTablet,
				globalLetterSpacingType
			),
		},
		' .wcf-embed-checkout-form .woocommerce h3, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout h3, .wcf-embed-checkout-form .woocommerce-checkout .woocommerce-shipping-fields h3#ship-to-different-address, .wcf-embed-checkout-form.wcf-embed-checkout-form-two-step .wcf-embed-checkout-form-steps .wcf-current .step-name':
			{
				'font-size': generateCSSUnit(
					headFontSizeTablet,
					headFontSizeType
				),
				'line-height': generateCSSUnit(
					headLineHeightTablet,
					headLineHeightType
				),
				'letter-spacing': generateCSSUnit(
					headLetterSpacingTablet,
					headLetterSpacingType
				),
			},
		' .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form .woocommerce #payment button, .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form.wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns .wcf-next-button, .wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns a.wcf-next-button, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form .woocommerce-checkout form.woocommerce-form-login .button, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout.wcf-modern-skin-multistep .woocommerce form .wcf-multistep-nav-btn-group .wcf-multistep-nav-next-btn, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .woocommerce #payment button':
			{
				'font-size': generateCSSUnit(
					buttonFontSizeTablet,
					buttonFontSizeType
				),
				'line-height': generateCSSUnit(
					buttonLineHeightTablet,
					buttonLineHeightType
				),
				'letter-spacing': generateCSSUnit(
					buttonLetterSpacingTablet,
					buttonLetterSpacingType
				),
				...buttonborderCSSTablet,
			},
		' .wcf-embed-checkout-form .woocommerce form p.form-row label': {
			'font-size': generateCSSUnit(
				inputFontSizeTablet,
				inputFontSizeType
			),
			'line-height': generateCSSUnit(
				inputLineHeightTablet,
				inputLineHeightType
			),
		},
		" .wcf-embed-checkout-form .woocommerce form .form-row input.input-text, .wcf-embed-checkout-form .woocommerce form .form-row textarea, .wcf-embed-checkout-form .woocommerce form .form-row select#billing_country, .wcf-embed-checkout-form .woocommerce form .form-row select#billing_state, span#select2-billing_country-container, .wcf-embed-checkout-form .select2-container--default .select2-selection--single .select2-selection__rendered, .wcf-embed-checkout-form #order_review .wcf-custom-coupon-field input[type='text'], .wcf-embed-checkout-form .woocommerce form .form-row input.input-text, .wcf-embed-checkout-form .woocommerce form .form-row textarea, .wcf-embed-checkout-form .select2-container--default .select2-selection--single, .wcf-embed-checkout-form .woocommerce form .form-row select, .wcf-embed-checkout-form .woocommerce form .form-row select, .wcf-embed-checkout-form ::placeholder, .wcf-embed-checkout-form ::-webkit-input-placeholder":
			{
				'font-size': generateCSSUnit(
					inputFontSizeTablet,
					inputFontSizeType
				),
				'line-height': generateCSSUnit(
					inputLineHeightTablet,
					inputLineHeightType
				),
				'letter-spacing': generateCSSUnit(
					inputLetterSpacingTablet,
					inputLetterSpacingType
				),
				...fieldborderCSSTablet,
			},
		" .wcf-embed-checkout-form .woocommerce #payment [type='radio']:checked + label, .wcf-embed-checkout-form .woocommerce #payment [type='radio']:not(:checked) + label":
			{
				'font-size': generateCSSUnit(
					inputFontSizeTablet,
					inputFontSizeType
				),
				'line-height': generateCSSUnit(
					inputLineHeightTablet,
					inputLineHeightType
				),
			},
		' .wcf-embed-checkout-form .woocommerce-checkout #payment ul.payment_methods':
			{
				'background-color': sectionbgColor,
				padding:
					paymentSectionpaddingTopTablet +
					paymentSectionpaddingTypeTablet +
					' ' +
					paymentSectionpaddingRightTablet +
					paymentSectionpaddingTypeTablet +
					' ' +
					paymentSectionpaddingBottomTablet +
					paymentSectionpaddingTypeTablet +
					' ' +
					paymentSectionpaddingLeftTablet +
					paymentSectionpaddingTypeTablet,
				margin:
					paymentSectionMarginTopTablet +
					paymentSectionMarginTypeTablet +
					' ' +
					paymentSectionMarginRightTablet +
					paymentSectionMarginTypeTablet +
					' ' +
					paymentSectionMarginBottomTablet +
					paymentSectionMarginTypeTablet +
					' ' +
					paymentSectionMarginLeftTablet +
					paymentSectionMarginTypeTablet,
				'border-radius': generateCSSUnit( sectionBorderRadius, 'px' ),
			},
	};

	const mobile_selectors = {
		' .wcf-embed-checkout-form .woocommerce': {
			'font-size': generateCSSUnit(
				globalFontSizeMobile,
				globalFontSizeType
			),
			'line-height': generateCSSUnit(
				globalLineHeightMobile,
				globalLineHeightType
			),
			'letter-spacing': generateCSSUnit(
				globalLetterSpacingMobile,
				globalLetterSpacingType
			),
		},
		' .wcf-embed-checkout-form .woocommerce h3, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout h3, .wcf-embed-checkout-form .woocommerce-checkout .woocommerce-shipping-fields h3#ship-to-different-address, .wcf-embed-checkout-form.wcf-embed-checkout-form-two-step .wcf-embed-checkout-form-steps .wcf-current .step-name':
			{
				'font-size': generateCSSUnit(
					headFontSizeMobile,
					headFontSizeType
				),
				'line-height': generateCSSUnit(
					headLineHeightMobile,
					headLineHeightType
				),
				'letter-spacing': generateCSSUnit(
					headLetterSpacingMobile,
					headLetterSpacingType
				),
			},
		' .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form .woocommerce #payment button, .wcf-embed-checkout-form .woocommerce #order_review button, .wcf-embed-checkout-form.wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns .wcf-next-button, .wcf-embed-checkout-form-two-step .woocommerce .wcf-embed-checkout-form-nav-btns a.wcf-next-button, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form .woocommerce-checkout form.woocommerce-form-login .button, .wcf-embed-checkout-form .woocommerce #order_review button.wcf-btn-small, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .wcf-custom-coupon-field button.wcf-submit-coupon, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout.wcf-modern-skin-multistep .woocommerce form .wcf-multistep-nav-btn-group .wcf-multistep-nav-next-btn, .wcf-embed-checkout-form.wcf-embed-checkout-form-modern-checkout .woocommerce #payment button':
			{
				'font-size': generateCSSUnit(
					buttonFontSizeMobile,
					buttonFontSizeType
				),
				'line-height': generateCSSUnit(
					buttonLineHeightMobile,
					buttonLineHeightType
				),
				'letter-spacing': generateCSSUnit(
					buttonLetterSpacingMobile,
					buttonLetterSpacingType
				),
				...buttonborderCSSMobile,
			},
		' .wcf-embed-checkout-form .woocommerce form p.form-row label': {
			'font-size': generateCSSUnit(
				inputFontSizeMobile,
				inputFontSizeType
			),
			'line-height': generateCSSUnit(
				inputLineHeightMobile,
				inputLineHeightType
			),
			'letter-spacing': generateCSSUnit(
				inputLetterSpacingMobile,
				inputLetterSpacingType
			),
		},
		" .wcf-embed-checkout-form .woocommerce form .form-row input.input-text, .wcf-embed-checkout-form .woocommerce form .form-row textarea, .wcf-embed-checkout-form .woocommerce form .form-row select#billing_country, .wcf-embed-checkout-form .woocommerce form .form-row select#billing_state, span#select2-billing_country-container, .wcf-embed-checkout-form .select2-container--default .select2-selection--single .select2-selection__rendered, .wcf-embed-checkout-form #order_review .wcf-custom-coupon-field input[type='text'], .wcf-embed-checkout-form .woocommerce form .form-row input.input-text, .wcf-embed-checkout-form .woocommerce form .form-row textarea, .wcf-embed-checkout-form .select2-container--default .select2-selection--single, .wcf-embed-checkout-form .woocommerce form .form-row select, .wcf-embed-checkout-form .woocommerce form .form-row select, .wcf-embed-checkout-form ::placeholder, .wcf-embed-checkout-form ::-webkit-input-placeholder":
			{
				'font-size': generateCSSUnit(
					inputFontSizeMobile,
					inputFontSizeType
				),
				'line-height': generateCSSUnit(
					inputLineHeightMobile,
					inputLineHeightType
				),
				...fieldborderCSSMobile,
			},
		" .wcf-embed-checkout-form .woocommerce #payment [type='radio']:checked + label, .wcf-embed-checkout-form .woocommerce #payment [type='radio']:not(:checked) + label":
			{
				'font-size': generateCSSUnit(
					inputFontSizeMobile,
					inputFontSizeType
				),
				'line-height': generateCSSUnit(
					inputLineHeightMobile,
					inputLineHeightType
				),
			},
		' .wcf-embed-checkout-form .woocommerce-checkout #payment ul.payment_methods':
			{
				'background-color': sectionbgColor,
				padding:
					paymentSectionpaddingTopMobile +
					paymentSectionpaddingTypeMobile +
					' ' +
					paymentSectionpaddingRightMobile +
					paymentSectionpaddingTypeMobile +
					' ' +
					paymentSectionpaddingBottomMobile +
					paymentSectionpaddingTypeMobile +
					' ' +
					paymentSectionpaddingLeftMobile +
					paymentSectionpaddingTypeMobile,
				margin:
					paymentSectionMarginTopMobile +
					paymentSectionMarginTypeMobile +
					' ' +
					paymentSectionMarginRightMobile +
					paymentSectionMarginTypeMobile +
					' ' +
					paymentSectionMarginBottomMobile +
					paymentSectionMarginTypeMobile +
					' ' +
					paymentSectionMarginLeftMobile +
					paymentSectionMarginTypeMobile,
				'border-radius': generateCSSUnit( sectionBorderRadius, 'px' ),
			},
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
