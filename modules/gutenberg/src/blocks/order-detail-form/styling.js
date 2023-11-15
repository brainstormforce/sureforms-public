import generateCSS from '@Controls/generateCSS';
import generateCSSUnit from '@Controls/generateCSSUnit';

function styling( props ) {
	const {
		// Genaral
		orderOverview,
		orderDetails,
		billingAddress,
		shippingAddress,
		// Spacing
		headingBottomSpacing,
		sectionSpacing,
		// Heading
		headingAlignment,
		headingColor,
		headingFontFamily,
		headingFontWeight,
		headingFontSizeType,
		headingLineHeightType,
		headingFontSize,
		headingFontSizeTablet,
		headingFontSizeMobile,
		headingLineHeight,
		headingLineHeightTablet,
		headingLineHeightMobile,
		// Sections
		sectionHeadingColor,
		sectionHeadingFontFamily,
		sectionHeadingFontWeight,
		sectionHeadingFontSizeType,
		sectionHeadingLineHeightType,
		sectionHeadingFontSize,
		sectionHeadingFontSizeTablet,
		sectionHeadingFontSizeMobile,
		sectionHeadingLineHeight,
		sectionHeadingLineHeightTablet,
		sectionHeadingLineHeightMobile,
		sectionContentColor,
		sectionContentFontFamily,
		sectionContentFontWeight,
		sectionContentFontSizeType,
		sectionContentLineHeightType,
		sectionContentFontSize,
		sectionContentFontSizeTablet,
		sectionContentFontSizeMobile,
		sectionContentLineHeight,
		sectionContentLineHeightTablet,
		sectionContentLineHeightMobile,
		sectionBackgroundColor,
		// Order Overview
		orderOverviewTextColor,
		orderOverviewBackgroundColor,
		orderOverviewFontFamily,
		orderOverviewFontWeight,
		orderOverviewFontSizeType,
		orderOverviewLineHeightType,
		orderOverviewFontSize,
		orderOverviewFontSizeTablet,
		orderOverviewFontSizeMobile,
		orderOverviewLineHeight,
		orderOverviewLineHeightTablet,
		orderOverviewLineHeightMobile,
		// Downloads
		downloadHeadingColor,
		downloadHeadingFontFamily,
		downloadHeadingFontWeight,
		downloadHeadingFontSizeType,
		downloadHeadingLineHeightType,
		downloadHeadingFontSize,
		downloadHeadingFontSizeTablet,
		downloadHeadingFontSizeMobile,
		downloadHeadingLineHeight,
		downloadHeadingLineHeightTablet,
		downloadHeadingLineHeightMobile,
		downloadContentColor,
		downloadContentFontFamily,
		downloadContentFontWeight,
		downloadContentFontSizeType,
		downloadContentLineHeightType,
		downloadContentFontSize,
		downloadContentFontSizeTablet,
		downloadContentFontSizeMobile,
		downloadContentLineHeight,
		downloadContentLineHeightTablet,
		downloadContentLineHeightMobile,
		downloadBackgroundColor,
		// Order Details
		orderDetailHeadingColor,
		orderDetailHeadingFontFamily,
		orderDetailHeadingFontWeight,
		orderDetailHeadingFontSizeType,
		orderDetailHeadingLineHeightType,
		orderDetailHeadingFontSize,
		orderDetailHeadingFontSizeTablet,
		orderDetailHeadingFontSizeMobile,
		orderDetailHeadingLineHeight,
		orderDetailHeadingLineHeightTablet,
		orderDetailHeadingLineHeightMobile,
		orderDetailContentColor,
		orderDetailContentFontFamily,
		orderDetailContentFontWeight,
		orderDetailContentFontSizeType,
		orderDetailContentLineHeightType,
		orderDetailContentFontSize,
		orderDetailContentFontSizeTablet,
		orderDetailContentFontSizeMobile,
		orderDetailContentLineHeight,
		orderDetailContentLineHeightTablet,
		orderDetailContentLineHeightMobile,
		orderDetailBackgroundColor,
		// Customer Details
		customerDetailHeadingColor,
		customerDetailHeadingFontFamily,
		customerDetailHeadingFontWeight,
		customerDetailHeadingFontSizeType,
		customerDetailHeadingLineHeightType,
		customerDetailHeadingFontSize,
		customerDetailHeadingFontSizeTablet,
		customerDetailHeadingFontSizeMobile,
		customerDetailHeadingLineHeight,
		customerDetailHeadingLineHeightTablet,
		customerDetailHeadingLineHeightMobile,
		customerDetailContentColor,
		customerDetailContentFontFamily,
		customerDetailContentFontWeight,
		customerDetailContentFontSizeType,
		customerDetailContentLineHeightType,
		customerDetailContentFontSize,
		customerDetailContentFontSizeTablet,
		customerDetailContentFontSizeMobile,
		customerDetailContentLineHeight,
		customerDetailContentLineHeightTablet,
		customerDetailContentLineHeightMobile,
		customerDetailBackgroundColor,
		backgroundType,
		backgroundImageColor,
		backgroundOpacity,
		backgroundColor,
		backgroundPosition,
		backgroundSize,
		backgroundAttachment,
		backgroundImage,
		backgroundRepeat,
		odbackgroundType,
		odbackgroundImageColor,
		odbackgroundImage,
		odbackgroundColor,
		odbackgroundPosition,
		odbackgroundAttachment,
		odbackgroundRepeat,
		odbackgroundSize,
		odbackgroundOpacity,
		dbackgroundType,
		dbackgroundImageColor,
		dbackgroundImage,
		dbackgroundColor,
		dbackgroundPosition,
		dbackgroundAttachment,
		dbackgroundRepeat,
		dbackgroundSize,
		dbackgroundOpacity,
		odetailbackgroundType,
		odetailbackgroundImageColor,
		odetailbackgroundImage,
		odetailbackgroundColor,
		odetailbackgroundPosition,
		odetailbackgroundAttachment,
		odetailbackgroundRepeat,
		odetailbackgroundSize,
		odetailbackgroundOpacity,
		cdetailbackgroundType,
		cdetailbackgroundImageColor,
		cdetailbackgroundImage,
		cdetailbackgroundColor,
		cdetailbackgroundPosition,
		cdetailbackgroundAttachment,
		cdetailbackgroundRepeat,
		cdetailbackgroundSize,
		cdetailbackgroundOpacity,
		gradientValue,

		//new attr
		odgradientValue,
		dgradientValue,
		cdetailgradientValue,
		odetailgradientValue,

		gradientColor1,
		gradientColor2,
		gradientLocation1,
		gradientLocation2,
		gradientType,
		gradientAngle,
		gradientPosition,

		orderOverviewFontStyle,
		orderDetailHeadingFontStyle,
		downloadHeadingFontStyle,
		sectionHeadingFontStyle,
		customerDetailHeadingFontStyle,
		headingFontStyle,
		orderDetailContentFontStyle,
		sectionContentFontStyle,
		downloadContentFontStyle,
		customerDetailContentFontStyle,
	} = props.attributes;
	const position = backgroundPosition.replace( '-', ' ' );
	const odposition = odbackgroundPosition.replace( '-', ' ' );
	const dposition = dbackgroundPosition.replace( '-', ' ' );
	const odetailposition = odetailbackgroundPosition.replace( '-', ' ' );
	const cdetailposition = cdetailbackgroundPosition.replace( '-', ' ' );
	let selectors = {};
	const tablet_selectors = {};
	const mobile_selectors = {};

	const order_overview = orderOverview ? 'block' : 'none';
	const order_details = orderDetails ? 'block' : 'none';
	const billing_address = billingAddress ? 'block' : 'none';
	const shipping_address = shippingAddress ? 'block' : 'none';
	const shipping_address_position = billingAddress ? 'right' : 'left';
	const customer_details =
		billingAddress || shippingAddress ? 'block' : 'none';

	selectors = {
		// Genaral
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order ul.order_details':
			{
				display: order_overview,
			},
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order section.woocommerce-order-details':
			{
				display: order_details,
			},
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details .woocommerce-column--billing-address':
			{
				display: billing_address,
			},
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details .woocommerce-column--shipping-address':
			{
				display: shipping_address,
				float: shipping_address_position,
			},
		// Spacing
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order p.woocommerce-thankyou-order-received':
			{
				'margin-bottom': generateCSSUnit( headingBottomSpacing, 'px' ),
			},
		' .wpcf__order-detail-form .woocommerce-order ul.order_details, .wpcf__order-detail-form .woocommerce-order .woocommerce-customer-details, .wpcf__order-detail-form .woocommerce-order .woocommerce-order-details, .wpcf__order-detail-form .woocommerce-order .woocommerce-order-downloads, .wpcf__order-detail-form .woocommerce-order .woocommerce-bacs-bank-details, .wpcf__order-detail-form .woocommerce-order-details.mollie-instructions':
			{
				'margin-bottom': generateCSSUnit( sectionSpacing, 'px' ),
			},
		// Heading
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-thankyou-order-received':
			{
				'text-align': headingAlignment,
				color: headingColor,
				'font-family': headingFontFamily,
				'font-weight': headingFontWeight,
				'font-style': headingFontStyle,
				'font-size': generateCSSUnit(
					headingFontSize,
					headingFontSizeType
				),
				'line-height': generateCSSUnit(
					headingLineHeight,
					headingLineHeightType
				),
			},
		// Sections
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order h2': {
			color: sectionHeadingColor,
			'font-family': sectionHeadingFontFamily,
			'font-weight': sectionHeadingFontWeight,
			'font-style': sectionHeadingFontStyle,
			'font-size': generateCSSUnit(
				sectionHeadingFontSize,
				sectionHeadingFontSizeType
			),
			'line-height': generateCSSUnit(
				sectionHeadingLineHeight,
				sectionHeadingLineHeightType
			),
		},
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order, .wpcf__order-detail-form .woocommerce-order-downloads table.shop_table':
			{
				color: sectionContentColor,
			},
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details li, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details .woocommerce-table, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details address, .wpcf__order-detail-form .woocommerce-order-downloads table.shop_table':
			{
				'font-family': sectionContentFontFamily,
				'font-weight': sectionContentFontWeight,
				'font-style': sectionContentFontStyle,
				'font-size': generateCSSUnit(
					sectionContentFontSize,
					sectionContentFontSizeType
				),
				'line-height': generateCSSUnit(
					sectionContentLineHeight,
					sectionContentLineHeightType
				),
			},
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details, .wpcf__order-detail-form .woocommerce-order-downloads':
			{
				'background-color': sectionBackgroundColor,
			},
		// Order Overview
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details':
			{
				color: orderOverviewTextColor,
				'background-color': orderOverviewBackgroundColor,
			},

		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details li':
			{
				'font-family': orderOverviewFontFamily,
				'font-weight': orderOverviewFontWeight,
				'font-style': orderOverviewFontStyle,
				'font-size': generateCSSUnit(
					orderOverviewFontSize,
					orderOverviewFontSizeType
				),
				'line-height': generateCSSUnit(
					orderOverviewLineHeight,
					orderOverviewLineHeightType
				),
				color: orderOverviewTextColor,
			},
		// Downloads
		' .wpcf__order-detail-form .woocommerce-order h2.woocommerce-order-downloads__title, .wpcf__order-detail-form .woocommerce-order .woocommerce-order-downloads h2.woocommerce-order-downloads__title':
			{
				color: downloadHeadingColor,
				'font-family': downloadHeadingFontFamily,
				'font-weight': downloadHeadingFontWeight,
				'font-style': downloadHeadingFontStyle,
				'font-size': generateCSSUnit(
					downloadHeadingFontSize,
					downloadHeadingFontSizeType
				),
				'line-height': generateCSSUnit(
					downloadHeadingLineHeight,
					downloadHeadingLineHeightType
				),
			},
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-downloads table.shop_table':
			{
				color: downloadContentColor,
				'font-family': downloadContentFontFamily,
				'font-weight': downloadContentFontWeight,
				'font-style': downloadContentFontStyle,
				'font-size': generateCSSUnit(
					downloadContentFontSize,
					downloadContentFontSizeType
				),
				'line-height': generateCSSUnit(
					downloadContentLineHeight,
					downloadContentLineHeightType
				),
			},
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-downloads':
			{
				'background-color': downloadBackgroundColor,
			},
		// Order Details
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details .woocommerce-order-details__title':
			{
				color: orderDetailHeadingColor,
				'font-family': orderDetailHeadingFontFamily,
				'font-weight': orderDetailHeadingFontWeight,
				'font-style': orderDetailHeadingFontStyle,
				'font-size': generateCSSUnit(
					orderDetailHeadingFontSize,
					orderDetailHeadingFontSizeType
				),
				'line-height': generateCSSUnit(
					orderDetailHeadingLineHeight,
					orderDetailHeadingLineHeightType
				),
			},
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details .woocommerce-table':
			{
				color: orderDetailContentColor,
				'font-family': orderDetailContentFontFamily,
				'font-weight': orderDetailContentFontWeight,
				'font-style': orderDetailContentFontStyle,
				'font-size': generateCSSUnit(
					orderDetailContentFontSize,
					orderDetailContentFontSizeType
				),
				'line-height': generateCSSUnit(
					orderDetailContentLineHeight,
					orderDetailContentLineHeightType
				),
			},
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details':
			{
				'background-color': orderDetailBackgroundColor,
			},
		// Customer Details
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details .woocommerce-column__title':
			{
				color: customerDetailHeadingColor,
				'font-family': customerDetailHeadingFontFamily,
				'font-weight': customerDetailHeadingFontWeight,
				'font-style': customerDetailHeadingFontStyle,
				'font-size': generateCSSUnit(
					customerDetailHeadingFontSize,
					customerDetailHeadingFontSizeType
				),
				'line-height': generateCSSUnit(
					customerDetailHeadingLineHeight,
					customerDetailHeadingLineHeightType
				),
			},
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details address, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details address p':
			{
				color: customerDetailContentColor,
				'font-family': customerDetailContentFontFamily,
				'font-weight': customerDetailContentFontWeight,
				'font-style': customerDetailContentFontStyle,
				'font-size': generateCSSUnit(
					customerDetailContentFontSize,
					customerDetailContentFontSizeType
				),
				'line-height': generateCSSUnit(
					customerDetailContentLineHeight,
					customerDetailContentLineHeightType
				),
			},
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order section.woocommerce-customer-details':
			{
				'background-color': customerDetailBackgroundColor,
				display: customer_details,
			},
	};

	// selectors[" .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details, .wpcf__order-detail-form .woocommerce-order-downloads"] = {
	//     'background-color': backgroundColor,
	// }

	if ( 'image' === backgroundType ) {
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details, .wpcf__order-detail-form .woocommerce-order-downloads'
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
		};
	}

	if ( 'color' === backgroundType ) {
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details, .wpcf__order-detail-form .woocommerce-order-downloads'
		] = {
			opacity:
				typeof backgroundOpacity !== 'undefined'
					? backgroundOpacity / 100
					: '',
			'background-color': backgroundColor,
		};
	}

	if ( 'gradient' === backgroundType ) {
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details, .wpcf__order-detail-form .woocommerce-order-downloads'
		][ 'background-color' ] = 'transparent';
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details, .wpcf__order-detail-form .woocommerce-order-downloads'
		].opacity =
			typeof backgroundOpacity !== 'undefined'
				? backgroundOpacity / 100
				: '';
		if ( gradientValue ) {
			selectors[
				' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details, .wpcf__order-detail-form .woocommerce-order-downloads'
			][ 'background-image' ] = gradientValue;
		} else if ( 'linear' === gradientType ) {
			selectors[
				' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details, .wpcf__order-detail-form .woocommerce-order-downloads'
			][
				'background-image'
			] = `linear-gradient(${ gradientAngle }deg, ${ gradientColor1 } ${ gradientLocation1 }%, ${ gradientColor2 } ${ gradientLocation2 }%)`;
		} else {
			selectors[
				' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details, .wpcf__order-detail-form .woocommerce-order-downloads'
			][
				'background-image'
			] = `radial-gradient( at ${ gradientPosition }, ${ gradientColor1 } ${ gradientLocation1 }%, ${ gradientColor2 } ${ gradientLocation2 }%)`;
		}
	}

	//Order review
	selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details'
	] = {
		'background-color': odbackgroundColor,
		color: orderOverviewTextColor,
	};

	if ( 'image' === odbackgroundType ) {
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details'
		] = {
			opacity:
				typeof odbackgroundOpacity !== 'undefined'
					? odbackgroundOpacity / 100
					: '',
			'background-color': odbackgroundImageColor,
			'background-image': odbackgroundImage
				? `url(${ odbackgroundImage.url })`
				: null,
			'background-position': odposition,
			'background-attachment': odbackgroundAttachment,
			'background-repeat': odbackgroundRepeat,
			'background-size': odbackgroundSize,
			color: orderOverviewTextColor,
		};
	} else if ( 'gradient' === odbackgroundType ) {
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details'
		][ 'background-color' ] = 'transparent';
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details'
		].opacity =
			typeof backgroundOpacity !== 'undefined'
				? backgroundOpacity / 100
				: '';
		if ( odgradientValue ) {
			selectors[
				' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details'
			][ 'background-image' ] = odgradientValue;
		} else if ( 'linear' === gradientType ) {
			selectors[
				' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details'
			][
				'background-image'
			] = `linear-gradient(${ gradientAngle }deg, ${ gradientColor1 } ${ gradientLocation1 }%, ${ gradientColor2 } ${ gradientLocation2 }%)`;
		} else {
			selectors[
				' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details'
			][
				'background-image'
			] = `radial-gradient( at ${ gradientPosition }, ${ gradientColor1 } ${ gradientLocation1 }%, ${ gradientColor2 } ${ gradientLocation2 }%)`;
		}
	} else if ( 'color' === odbackgroundType ) {
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details'
		] = {
			opacity:
				typeof odbackgroundOpacity !== 'undefined'
					? odbackgroundOpacity / 100
					: '',
			'background-color': odbackgroundColor,
			color: orderOverviewTextColor,
		};
	}

	//Downloads
	selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-downloads'
	] = {
		'background-color': dbackgroundColor,
	};

	if ( 'image' === dbackgroundType ) {
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-downloads'
		] = {
			opacity:
				typeof dbackgroundOpacity !== 'undefined'
					? dbackgroundOpacity / 100
					: '',
			'background-color': dbackgroundImageColor,
			'background-image': dbackgroundImage
				? `url(${ dbackgroundImage.url })`
				: null,
			'background-position': dposition,
			'background-attachment': dbackgroundAttachment,
			'background-repeat': dbackgroundRepeat,
			'background-size': dbackgroundSize,
		};
	} else if ( 'gradient' === dbackgroundType ) {
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-downloads'
		][ 'background-color' ] = 'transparent';
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-downloads'
		].opacity =
			typeof backgroundOpacity !== 'undefined'
				? backgroundOpacity / 100
				: '';
		if ( dgradientValue ) {
			selectors[
				' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-downloads'
			][ 'background-image' ] = dgradientValue;
		} else if ( 'linear' === gradientType ) {
			selectors[
				' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-downloads'
			][
				'background-image'
			] = `linear-gradient(${ gradientAngle }deg, ${ gradientColor1 } ${ gradientLocation1 }%, ${ gradientColor2 } ${ gradientLocation2 }%)`;
		} else {
			selectors[
				' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-downloads'
			][
				'background-image'
			] = `radial-gradient( at ${ gradientPosition }, ${ gradientColor1 } ${ gradientLocation1 }%, ${ gradientColor2 } ${ gradientLocation2 }%)`;
		}
	} else if ( 'color' === dbackgroundType ) {
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-downloads'
		] = {
			opacity:
				typeof dbackgroundOpacity !== 'undefined'
					? dbackgroundOpacity / 100
					: '',
			'background-color': dbackgroundColor,
		};
	}

	//Order details
	selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details'
	] = {
		'background-color': odetailbackgroundColor,
	};

	if ( 'image' === odetailbackgroundType ) {
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details'
		] = {
			opacity:
				typeof odetailbackgroundOpacity !== 'undefined'
					? odetailbackgroundOpacity / 100
					: '',
			'background-color': odetailbackgroundImageColor,
			'background-image': odetailbackgroundImage
				? `url(${ odetailbackgroundImage.url })`
				: null,
			'background-position': odetailposition,
			'background-attachment': odetailbackgroundAttachment,
			'background-repeat': odetailbackgroundRepeat,
			'background-size': odetailbackgroundSize,
		};
	} else if ( 'gradient' === odetailbackgroundType ) {
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details'
		][ 'background-color' ] = 'transparent';
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details'
		].opacity =
			typeof backgroundOpacity !== 'undefined'
				? backgroundOpacity / 100
				: '';
		if ( odetailgradientValue ) {
			selectors[
				' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details'
			][ 'background-image' ] = odetailgradientValue;
		} else if ( 'linear' === gradientType ) {
			selectors[
				' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details'
			][
				'background-image'
			] = `linear-gradient(${ gradientAngle }deg, ${ gradientColor1 } ${ gradientLocation1 }%, ${ gradientColor2 } ${ gradientLocation2 }%)`;
		} else {
			selectors[
				' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details'
			][
				'background-image'
			] = `radial-gradient( at ${ gradientPosition }, ${ gradientColor1 } ${ gradientLocation1 }%, ${ gradientColor2 } ${ gradientLocation2 }%)`;
		}
	} else if ( 'color' === odetailbackgroundType ) {
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details'
		] = {
			opacity:
				typeof odetailbackgroundOpacity !== 'undefined'
					? odetailbackgroundOpacity / 100
					: '',
			'background-color': odetailbackgroundColor,
		};
	}

	//customer details
	selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details'
	] = {
		'background-color': cdetailbackgroundColor,
	};

	if ( 'image' === cdetailbackgroundType ) {
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details'
		] = {
			opacity:
				typeof cdetailbackgroundOpacity !== 'undefined'
					? cdetailbackgroundOpacity / 100
					: '',
			'background-color': cdetailbackgroundImageColor,
			'background-image': cdetailbackgroundImage
				? `url(${ cdetailbackgroundImage.url })`
				: null,
			'background-position': cdetailposition,
			'background-attachment': cdetailbackgroundAttachment,
			'background-repeat': cdetailbackgroundRepeat,
			'background-size': cdetailbackgroundSize,
		};
	} else if ( 'gradient' === cdetailbackgroundType ) {
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details'
		][ 'background-color' ] = 'transparent';
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details'
		].opacity =
			typeof backgroundOpacity !== 'undefined'
				? backgroundOpacity / 100
				: '';
		if ( cdetailgradientValue ) {
			selectors[
				' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details'
			][ 'background-image' ] = cdetailgradientValue;
		} else if ( 'linear' === gradientType ) {
			selectors[
				' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details'
			][
				'background-image'
			] = `linear-gradient(${ gradientAngle }deg, ${ gradientColor1 } ${ gradientLocation1 }%, ${ gradientColor2 } ${ gradientLocation2 }%)`;
		} else {
			selectors[
				' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details'
			][
				'background-image'
			] = `radial-gradient( at ${ gradientPosition }, ${ gradientColor1 } ${ gradientLocation1 }%, ${ gradientColor2 } ${ gradientLocation2 }%)`;
		}
	} else if ( 'color' === cdetailbackgroundType ) {
		selectors[
			' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details'
		] = {
			opacity:
				typeof cdetailbackgroundOpacity !== 'undefined'
					? cdetailbackgroundOpacity / 100
					: '',
			'background-color': cdetailbackgroundColor,
		};
	}

	// Heading
	tablet_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-thankyou-order-received'
	] = {
		'font-size': generateCSSUnit(
			headingFontSizeTablet,
			headingFontSizeType
		),
		'line-height': generateCSSUnit(
			headingLineHeightTablet,
			headingLineHeightType
		),
	};
	// Sections
	tablet_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order h2'
	] = {
		'font-size': generateCSSUnit(
			sectionHeadingFontSizeTablet,
			sectionHeadingFontSizeType
		),
		'line-height': generateCSSUnit(
			sectionHeadingLineHeightTablet,
			sectionHeadingLineHeightType
		),
	};
	tablet_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details li, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details .woocommerce-table, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details address, .wpcf__order-detail-form .woocommerce-order-downloads table.shop_table'
	] = {
		'font-size': generateCSSUnit(
			sectionContentFontSizeTablet,
			headingFontSizeType
		),
		'line-height': generateCSSUnit(
			sectionContentLineHeightTablet,
			headingLineHeightType
		),
	};
	// Order Overview
	tablet_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details li'
	] = {
		'font-size': generateCSSUnit(
			orderOverviewFontSizeTablet,
			orderOverviewFontSizeType
		),
		'line-height': generateCSSUnit(
			orderOverviewLineHeightTablet,
			orderOverviewLineHeightType
		),
	};
	// Downloads
	tablet_selectors[
		' .wpcf__order-detail-form .woocommerce-order h2.woocommerce-order-downloads__title, .wpcf__order-detail-form .woocommerce-order .woocommerce-order-downloads h2.woocommerce-order-downloads__title'
	] = {
		'font-size': generateCSSUnit(
			downloadHeadingFontSizeTablet,
			downloadHeadingFontSizeType
		),
		'line-height': generateCSSUnit(
			downloadHeadingLineHeightTablet,
			downloadHeadingLineHeightType
		),
	};
	tablet_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-downloads table.shop_table'
	] = {
		'font-size': generateCSSUnit(
			downloadContentFontSizeTablet,
			downloadContentFontSizeType
		),
		'line-height': generateCSSUnit(
			downloadContentLineHeightTablet,
			downloadContentLineHeightType
		),
	};
	// Order Details
	tablet_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details .woocommerce-order-details__title'
	] = {
		'font-size': generateCSSUnit(
			orderDetailHeadingFontSizeTablet,
			orderDetailHeadingFontSizeType
		),
		'line-height': generateCSSUnit(
			orderDetailHeadingLineHeightTablet,
			orderDetailHeadingLineHeightType
		),
	};
	tablet_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details .woocommerce-table'
	] = {
		'font-size': generateCSSUnit(
			orderDetailContentFontSizeTablet,
			orderDetailContentFontSizeType
		),
		'line-height': generateCSSUnit(
			orderDetailContentLineHeightTablet,
			orderDetailContentLineHeightType
		),
	};
	// Customer Details
	tablet_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details .woocommerce-column__title'
	] = {
		'font-size': generateCSSUnit(
			customerDetailHeadingFontSizeTablet,
			customerDetailHeadingFontSizeType
		),
		'line-height': generateCSSUnit(
			customerDetailHeadingLineHeightTablet,
			customerDetailHeadingLineHeightType
		),
	};
	tablet_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details address'
	] = {
		'font-size': generateCSSUnit(
			customerDetailContentFontSizeTablet,
			customerDetailContentFontSizeType
		),
		'line-height': generateCSSUnit(
			customerDetailContentLineHeightTablet,
			customerDetailContentLineHeightType
		),
	};

	// Heading
	mobile_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-thankyou-order-received'
	] = {
		'font-size': generateCSSUnit(
			headingFontSizeMobile,
			headingFontSizeType
		),
		'line-height': generateCSSUnit(
			headingLineHeightMobile,
			headingLineHeightType
		),
	};
	// Sections
	mobile_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order h2'
	] = {
		'font-size': generateCSSUnit(
			sectionHeadingFontSizeMobile,
			sectionHeadingFontSizeType
		),
		'line-height': generateCSSUnit(
			sectionHeadingLineHeightMobile,
			sectionHeadingLineHeightType
		),
	};
	mobile_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details li, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details .woocommerce-table, .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details address, .wpcf__order-detail-form .woocommerce-order-downloads table.shop_table'
	] = {
		'font-size': generateCSSUnit(
			sectionContentFontSizeMobile,
			sectionContentFontSizeType
		),
		'line-height': generateCSSUnit(
			sectionContentLineHeightMobile,
			sectionContentLineHeightType
		),
	};
	// Order Overview
	mobile_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-overview.woocommerce-thankyou-order-details.order_details li'
	] = {
		'font-size': generateCSSUnit(
			orderOverviewFontSizeMobile,
			orderOverviewFontSizeType
		),
		'line-height': generateCSSUnit(
			orderOverviewLineHeightMobile,
			orderOverviewLineHeightType
		),
	};
	// Downloads
	mobile_selectors[
		' .wpcf__order-detail-form .woocommerce-order h2.woocommerce-order-downloads__title, .wpcf__order-detail-form .woocommerce-order .woocommerce-order-downloads h2.woocommerce-order-downloads__title'
	] = {
		'font-size': generateCSSUnit(
			downloadHeadingFontSizeMobile,
			downloadHeadingFontSizeType
		),
		'line-height': generateCSSUnit(
			downloadHeadingLineHeightMobile,
			downloadHeadingLineHeightType
		),
	};
	mobile_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-downloads table.shop_table'
	] = {
		'font-size': generateCSSUnit(
			downloadContentFontSizeMobile,
			downloadContentFontSizeType
		),
		'line-height': generateCSSUnit(
			downloadContentLineHeightMobile,
			downloadContentLineHeightType
		),
	};
	// Order Details
	mobile_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details .woocommerce-order-details__title'
	] = {
		'font-size': generateCSSUnit(
			orderDetailHeadingFontSizeMobile,
			orderDetailHeadingFontSizeType
		),
		'line-height': generateCSSUnit(
			orderDetailHeadingLineHeightMobile,
			orderDetailHeadingLineHeightType
		),
	};
	mobile_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-order-details .woocommerce-table'
	] = {
		'font-size': generateCSSUnit(
			orderDetailContentFontSizeMobile,
			orderDetailContentFontSizeType
		),
		'line-height': generateCSSUnit(
			orderDetailContentLineHeightMobile,
			orderDetailContentLineHeightType
		),
	};
	// Customer Details
	mobile_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details .woocommerce-column__title'
	] = {
		'font-size': generateCSSUnit(
			customerDetailHeadingFontSizeMobile,
			customerDetailHeadingFontSizeType
		),
		'line-height': generateCSSUnit(
			customerDetailHeadingLineHeightMobile,
			customerDetailHeadingLineHeightType
		),
	};
	mobile_selectors[
		' .wpcf__order-detail-form .wcf-thankyou-wrap .woocommerce-order .woocommerce-customer-details address'
	] = {
		'font-size': generateCSSUnit(
			customerDetailContentFontSizeMobile,
			customerDetailContentFontSizeType
		),
		'line-height': generateCSSUnit(
			customerDetailContentLineHeightMobile,
			customerDetailContentLineHeightType
		),
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
