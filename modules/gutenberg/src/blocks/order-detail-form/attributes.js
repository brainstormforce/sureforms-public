const attributes = {
	block_id: {
		type: 'string',
	},
	classMigrate: {
		type: 'boolean',
		default: false,
	},
	className: {
		type: 'string',
	},
	// Genaral.
	orderOverview: {
		type: 'boolean',
		default: true,
	},
	orderDetails: {
		type: 'boolean',
		default: true,
	},
	billingAddress: {
		type: 'boolean',
		default: true,
	},
	shippingAddress: {
		type: 'boolean',
		default: true,
	},
	// Spacing.
	headingBottomSpacing: {
		type: 'number',
	},
	sectionSpacing: {
		type: 'number',
	},
	// Heading.
	thanyouText: {
		type: 'string',
		default: '',
	},
	headingAlignment: {
		type: 'string',
		default: 'center',
	},
	headingColor: {
		type: 'string',
		default: '',
	},
	// heading font family.
	headingLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	headingFontFamily: {
		type: 'string',
	},
	headingFontWeight: {
		type: 'string',
	},
	headingFontSubset: {
		type: 'string',
	},
	// heading font size.
	headingFontSize: {
		type: 'number',
	},
	headingFontSizeType: {
		type: 'string',
		default: 'px',
	},
	headingFontSizeTablet: {
		type: 'number',
	},
	headingFontSizeMobile: {
		type: 'number',
	},
	// heading line height.
	headingLineHeightType: {
		type: 'string',
		default: 'em',
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
	// Sections.
	sectionHeadingColor: {
		type: 'string',
	},
	// section heading font family.
	sectionHeadingLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	sectionHeadingFontFamily: {
		type: 'string',
	},
	sectionHeadingFontWeight: {
		type: 'string',
	},
	sectionHeadingFontSubset: {
		type: 'string',
	},
	// section heading font size.
	sectionHeadingFontSize: {
		type: 'number',
	},
	sectionHeadingFontSizeType: {
		type: 'string',
		default: 'px',
	},
	sectionHeadingFontSizeTablet: {
		type: 'number',
	},
	sectionHeadingFontSizeMobile: {
		type: 'number',
	},
	// section heading line height.
	sectionHeadingLineHeightType: {
		type: 'string',
		default: 'em',
	},
	sectionHeadingLineHeight: {
		type: 'number',
	},
	sectionHeadingLineHeightTablet: {
		type: 'number',
	},
	sectionHeadingLineHeightMobile: {
		type: 'number',
	},
	sectionContentColor: {
		type: 'string',
	},
	// section content font family.
	sectionContentLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	sectionContentFontFamily: {
		type: 'string',
	},
	sectionContentFontWeight: {
		type: 'string',
	},
	sectionContentFontSubset: {
		type: 'string',
	},
	// section content font size.
	sectionContentFontSize: {
		type: 'number',
	},
	sectionContentFontSizeType: {
		type: 'string',
		default: 'px',
	},
	sectionContentFontSizeTablet: {
		type: 'number',
	},
	sectionContentFontSizeMobile: {
		type: 'number',
	},
	// section content line height.
	sectionContentLineHeightType: {
		type: 'string',
		default: 'em',
	},
	sectionContentLineHeight: {
		type: 'number',
	},
	sectionContentLineHeightTablet: {
		type: 'number',
	},
	sectionContentLineHeightMobile: {
		type: 'number',
	},
	sectionBackgroundColor: {
		type: 'string',
	},
	// Order Overview.
	orderOverviewTextColor: {
		type: 'string',
	},
	orderOverviewBackgroundColor: {
		type: 'string',
	},
	// order overview font family.
	orderOverviewLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	orderOverviewFontFamily: {
		type: 'string',
	},
	orderOverviewFontWeight: {
		type: 'string',
	},
	orderOverviewFontSubset: {
		type: 'string',
	},
	// order overview font size.
	orderOverviewFontSize: {
		type: 'number',
	},
	orderOverviewFontSizeType: {
		type: 'string',
		default: 'px',
	},
	orderOverviewFontSizeTablet: {
		type: 'number',
	},
	orderOverviewFontSizeMobile: {
		type: 'number',
	},
	// order overview line height.
	orderOverviewLineHeightType: {
		type: 'string',
		default: 'em',
	},
	orderOverviewLineHeight: {
		type: 'number',
	},
	orderOverviewLineHeightTablet: {
		type: 'number',
	},
	orderOverviewLineHeightMobile: {
		type: 'number',
	},
	// Downloads.
	downloadHeadingColor: {
		type: 'string',
	},
	// download heading font family.
	downloadHeadingLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	downloadHeadingFontFamily: {
		type: 'string',
	},
	downloadHeadingFontWeight: {
		type: 'string',
	},
	downloadHeadingFontSubset: {
		type: 'string',
	},
	// download heading font size.
	downloadHeadingFontSize: {
		type: 'number',
	},
	downloadHeadingFontSizeType: {
		type: 'string',
		default: 'px',
	},
	downloadHeadingFontSizeTablet: {
		type: 'number',
	},
	downloadHeadingFontSizeMobile: {
		type: 'number',
	},
	// download heading line height.
	downloadHeadingLineHeightType: {
		type: 'string',
		default: 'em',
	},
	downloadHeadingLineHeight: {
		type: 'number',
	},
	downloadHeadingLineHeightTablet: {
		type: 'number',
	},
	downloadHeadingLineHeightMobile: {
		type: 'number',
	},
	downloadContentColor: {
		type: 'string',
	},
	// download content font family.
	downloadContentLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	downloadContentFontFamily: {
		type: 'string',
	},
	downloadContentFontWeight: {
		type: 'string',
	},
	downloadContentFontSubset: {
		type: 'string',
	},
	// download content font size.
	downloadContentFontSize: {
		type: 'number',
	},
	downloadContentFontSizeType: {
		type: 'string',
		default: 'px',
	},
	downloadContentFontSizeTablet: {
		type: 'number',
	},
	downloadContentFontSizeMobile: {
		type: 'number',
	},
	// download content line height.
	downloadContentLineHeightType: {
		type: 'string',
		default: 'em',
	},
	downloadContentLineHeight: {
		type: 'number',
	},
	downloadContentLineHeightTablet: {
		type: 'number',
	},
	downloadContentLineHeightMobile: {
		type: 'number',
	},
	downloadBackgroundColor: {
		type: 'string',
	},
	// Order Details.
	orderDetailHeadingColor: {
		type: 'string',
	},
	// order details heading font family.
	orderDetailHeadingLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	orderDetailHeadingFontFamily: {
		type: 'string',
	},
	orderDetailHeadingFontWeight: {
		type: 'string',
	},
	orderDetailHeadingFontSubset: {
		type: 'string',
	},
	// order details heading font size.
	orderDetailHeadingFontSize: {
		type: 'number',
	},
	orderDetailHeadingFontSizeType: {
		type: 'string',
		default: 'px',
	},
	orderDetailHeadingFontSizeTablet: {
		type: 'number',
	},
	orderDetailHeadingFontSizeMobile: {
		type: 'number',
	},
	// order details heading line height.
	orderDetailHeadingLineHeightType: {
		type: 'string',
		default: 'em',
	},
	orderDetailHeadingLineHeight: {
		type: 'number',
	},
	orderDetailHeadingLineHeightTablet: {
		type: 'number',
	},
	orderDetailHeadingLineHeightMobile: {
		type: 'number',
	},
	orderDetailContentColor: {
		type: 'string',
	},
	// order details content font family.
	orderDetailContentLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	orderDetailContentFontFamily: {
		type: 'string',
	},
	orderDetailContentFontWeight: {
		type: 'string',
	},
	orderDetailContentFontSubset: {
		type: 'string',
	},
	// order details content font size.
	orderDetailContentFontSize: {
		type: 'number',
	},
	orderDetailContentFontSizeType: {
		type: 'string',
		default: 'px',
	},
	orderDetailContentFontSizeTablet: {
		type: 'number',
	},
	orderDetailContentFontSizeMobile: {
		type: 'number',
	},
	// order details content line height.
	orderDetailContentLineHeightType: {
		type: 'string',
		default: 'em',
	},
	orderDetailContentLineHeight: {
		type: 'number',
	},
	orderDetailContentLineHeightTablet: {
		type: 'number',
	},
	orderDetailContentLineHeightMobile: {
		type: 'number',
	},
	orderDetailBackgroundColor: {
		type: 'string',
	},
	// Customer Details.
	customerDetailHeadingColor: {
		type: 'string',
	},
	// customer details heading font family.
	customerDetailHeadingLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	customerDetailHeadingFontFamily: {
		type: 'string',
	},
	customerDetailHeadingFontWeight: {
		type: 'string',
	},
	customerDetailHeadingFontSubset: {
		type: 'string',
	},
	// customer details heading font size.
	customerDetailHeadingFontSize: {
		type: 'number',
	},
	customerDetailHeadingFontSizeType: {
		type: 'string',
		default: 'px',
	},
	customerDetailHeadingFontSizeTablet: {
		type: 'number',
	},
	customerDetailHeadingFontSizeMobile: {
		type: 'number',
	},
	// customer details heading line height.
	customerDetailHeadingLineHeightType: {
		type: 'string',
		default: 'em',
	},
	customerDetailHeadingLineHeight: {
		type: 'number',
	},
	customerDetailHeadingLineHeightTablet: {
		type: 'number',
	},
	customerDetailHeadingLineHeightMobile: {
		type: 'number',
	},
	customerDetailContentColor: {
		type: 'string',
	},
	// customer details content font family.
	customerDetailContentLoadGoogleFonts: {
		type: 'boolean',
		default: false,
	},
	customerDetailContentFontFamily: {
		type: 'string',
	},
	customerDetailContentFontWeight: {
		type: 'string',
	},
	customerDetailContentFontSubset: {
		type: 'string',
	},
	// customer details content font size.
	customerDetailContentFontSize: {
		type: 'number',
	},
	customerDetailContentFontSizeType: {
		type: 'string',
		default: 'px',
	},
	customerDetailContentFontSizeTablet: {
		type: 'number',
	},
	customerDetailContentFontSizeMobile: {
		type: 'number',
	},
	// customer details content line height.
	customerDetailContentLineHeightType: {
		type: 'string',
		default: 'em',
	},
	customerDetailContentLineHeight: {
		type: 'number',
	},
	customerDetailContentLineHeightTablet: {
		type: 'number',
	},
	customerDetailContentLineHeightMobile: {
		type: 'number',
	},
	customerDetailBackgroundColor: {
		type: 'string',
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
	odbackgroundType: {
		type: 'string',
		default: 'color',
	},
	odbackgroundImage: {
		type: 'object',
	},
	odbackgroundPosition: {
		type: 'string',
		default: 'center-center',
	},
	odbackgroundSize: {
		type: 'string',
		default: 'cover',
	},
	odbackgroundRepeat: {
		type: 'string',
		default: 'no-repeat',
	},
	odbackgroundAttachment: {
		type: 'string',
		default: 'scroll',
	},
	odbackgroundOpacity: {
		type: 'number',
	},
	odbackgroundImageColor: {
		type: 'string',
		default: '',
	},
	odbackgroundColor: {
		type: 'string',
		default: '',
	},
	dbackgroundType: {
		type: 'string',
		default: 'color',
	},
	dbackgroundImage: {
		type: 'object',
	},
	dbackgroundPosition: {
		type: 'string',
		default: 'center-center',
	},
	dbackgroundSize: {
		type: 'string',
		default: 'cover',
	},
	dbackgroundRepeat: {
		type: 'string',
		default: 'no-repeat',
	},
	dbackgroundAttachment: {
		type: 'string',
		default: 'scroll',
	},
	dbackgroundOpacity: {
		type: 'number',
	},
	dbackgroundImageColor: {
		type: 'string',
		default: '',
	},
	dbackgroundColor: {
		type: 'string',
		default: '',
	},
	odetailbackgroundType: {
		type: 'string',
		default: 'color',
	},
	odetailbackgroundImage: {
		type: 'object',
	},
	odetailbackgroundPosition: {
		type: 'string',
		default: 'center-center',
	},
	odetailbackgroundSize: {
		type: 'string',
		default: 'cover',
	},
	odetailbackgroundRepeat: {
		type: 'string',
		default: 'no-repeat',
	},
	odetailbackgroundAttachment: {
		type: 'string',
		default: 'scroll',
	},
	odetailbackgroundOpacity: {
		type: 'number',
	},
	odetailbackgroundImageColor: {
		type: 'string',
		default: '',
	},
	odetailbackgroundColor: {
		type: 'string',
		default: '',
	},
	cdetailbackgroundType: {
		type: 'string',
		default: 'color',
	},
	cdetailbackgroundImage: {
		type: 'object',
	},
	cdetailbackgroundPosition: {
		type: 'string',
		default: 'center-center',
	},
	cdetailbackgroundSize: {
		type: 'string',
		default: 'cover',
	},
	cdetailbackgroundRepeat: {
		type: 'string',
		default: 'no-repeat',
	},
	cdetailbackgroundAttachment: {
		type: 'string',
		default: 'scroll',
	},
	cdetailbackgroundOpacity: {
		type: 'number',
	},
	cdetailbackgroundImageColor: {
		type: 'string',
		default: '',
	},
	cdetailbackgroundColor: {
		type: 'string',
		default: '',
	},
	deviceType: {
		type: 'string',
		default: 'Desktop',
	},

	// Global
	gradientColor1: {
		type: 'string',
		default: '#abb8c3',
	},
	gradientColor2: {
		type: 'string',
		default: '#abb8c3',
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

	// Order Overview
	odgradientValue: {
		type: 'string',
		default: '',
	},

	// Download
	dgradientValue: {
		type: 'string',
		default: '',
	},

	// order details
	odetailgradientValue: {
		type: 'string',
		default: '',
	},

	// Customer details
	cdetailgradientValue: {
		type: 'string',
		default: '',
	},

	orderOverviewFontStyle: {
		type: 'string',
		default: '',
	},
	orderDetailHeadingFontStyle: {
		type: 'string',
		default: '',
	},
	downloadHeadingFontStyle: {
		type: 'string',
		default: '',
	},
	sectionHeadingFontStyle: {
		type: 'string',
		default: '',
	},
	customerDetailHeadingFontStyle: {
		type: 'string',
		default: '',
	},
	headingFontStyle: {
		type: 'string',
		default: '',
	},
	orderDetailContentFontStyle: {
		type: 'string',
		default: '',
	},
	sectionContentFontStyle: {
		type: 'string',
		default: '',
	},
	downloadContentFontStyle: {
		type: 'string',
		default: '',
	},
	customerDetailContentFontStyle: {
		type: 'string',
		default: '',
	},
};
export default attributes;
