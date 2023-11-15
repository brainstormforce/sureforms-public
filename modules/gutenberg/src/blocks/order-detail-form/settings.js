import React from 'react';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import Background from '@Components/background';
import renderSVG from '@Controls/renderIcon';

import { Icon, TextControl, ToggleControl } from '@wordpress/components';
import MultiButtonsControl from '@Components/multi-buttons-control';
import Range from '@Components/range/Range.js';

import TypographyControl from '@Components/typography';
import WebfontLoader from '@Components/typography/fontloader';

import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';

import UAGAdvancedPanelBody from '@Components/advanced-panel-body';

const Settings = ( props ) => {
	props = props.parentProps;

	const {
		attributes,
		setAttributes,
		attributes: {
			// Genaral
			orderOverview,
			orderDetails,
			billingAddress,
			shippingAddress,
			// Thank you text
			thanyouText,
			// Spacing
			headingBottomSpacing,
			sectionSpacing,
			// Heading
			headingAlignment,
			headingColor,
			headingLoadGoogleFonts,
			headingFontFamily,
			headingFontWeight,
			headingFontSubset,
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
			sectionHeadingLoadGoogleFonts,
			sectionHeadingFontFamily,
			sectionHeadingFontWeight,
			sectionHeadingFontSubset,
			sectionHeadingFontSizeType,
			sectionHeadingLineHeightType,
			sectionHeadingFontSize,
			sectionHeadingFontSizeTablet,
			sectionHeadingFontSizeMobile,
			sectionHeadingLineHeight,
			sectionHeadingLineHeightTablet,
			sectionHeadingLineHeightMobile,
			sectionContentColor,
			sectionContentLoadGoogleFonts,
			sectionContentFontFamily,
			sectionContentFontWeight,
			sectionContentFontSubset,
			sectionContentFontSizeType,
			sectionContentLineHeightType,
			sectionContentFontSize,
			sectionContentFontSizeTablet,
			sectionContentFontSizeMobile,
			sectionContentLineHeight,
			sectionContentLineHeightTablet,
			sectionContentLineHeightMobile,

			backgroundType,
			backgroundImage,
			backgroundColor,
			backgroundPosition,
			backgroundAttachment,
			backgroundRepeat,
			backgroundSize,
			backgroundOpacity,
			gradientValue,
			// Order Overview
			orderOverviewTextColor,
			orderOverviewLoadGoogleFonts,
			orderOverviewFontFamily,
			orderOverviewFontWeight,
			orderOverviewFontSubset,
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
			downloadHeadingLoadGoogleFonts,
			downloadHeadingFontFamily,
			downloadHeadingFontWeight,
			downloadHeadingFontSubset,
			downloadHeadingFontSizeType,
			downloadHeadingLineHeightType,
			downloadHeadingFontSize,
			downloadHeadingFontSizeTablet,
			downloadHeadingFontSizeMobile,
			downloadHeadingLineHeight,
			downloadHeadingLineHeightTablet,
			downloadHeadingLineHeightMobile,
			downloadContentColor,
			downloadContentLoadGoogleFonts,
			downloadContentFontFamily,
			downloadContentFontWeight,
			downloadContentFontSubset,
			downloadContentFontSizeType,
			downloadContentLineHeightType,
			downloadContentFontSize,
			downloadContentFontSizeTablet,
			downloadContentFontSizeMobile,
			downloadContentLineHeight,
			downloadContentLineHeightTablet,
			downloadContentLineHeightMobile,
			// Order Details
			orderDetailHeadingColor,
			orderDetailHeadingLoadGoogleFonts,
			orderDetailHeadingFontFamily,
			orderDetailHeadingFontWeight,
			orderDetailHeadingFontSubset,
			orderDetailHeadingFontSizeType,
			orderDetailHeadingLineHeightType,
			orderDetailHeadingFontSize,
			orderDetailHeadingFontSizeTablet,
			orderDetailHeadingFontSizeMobile,
			orderDetailHeadingLineHeight,
			orderDetailHeadingLineHeightTablet,
			orderDetailHeadingLineHeightMobile,
			orderDetailContentColor,
			orderDetailContentLoadGoogleFonts,
			orderDetailContentFontFamily,
			orderDetailContentFontWeight,
			orderDetailContentFontSubset,
			orderDetailContentFontSizeType,
			orderDetailContentLineHeightType,
			orderDetailContentFontSize,
			orderDetailContentFontSizeTablet,
			orderDetailContentFontSizeMobile,
			orderDetailContentLineHeight,
			orderDetailContentLineHeightTablet,
			orderDetailContentLineHeightMobile,
			// Customer Details
			customerDetailHeadingColor,
			customerDetailHeadingLoadGoogleFonts,
			customerDetailHeadingFontFamily,
			customerDetailHeadingFontWeight,
			customerDetailHeadingFontSubset,
			customerDetailHeadingFontSizeType,
			customerDetailHeadingLineHeightType,
			customerDetailHeadingFontSize,
			customerDetailHeadingFontSizeTablet,
			customerDetailHeadingFontSizeMobile,
			customerDetailHeadingLineHeight,
			customerDetailHeadingLineHeightTablet,
			customerDetailHeadingLineHeightMobile,
			customerDetailContentColor,
			customerDetailContentLoadGoogleFonts,
			customerDetailContentFontFamily,
			customerDetailContentFontWeight,
			customerDetailContentFontSubset,
			customerDetailContentFontSizeType,
			customerDetailContentLineHeightType,
			customerDetailContentFontSize,
			customerDetailContentFontSizeTablet,
			customerDetailContentFontSizeMobile,
			customerDetailContentLineHeight,
			customerDetailContentLineHeightTablet,
			customerDetailContentLineHeightMobile,

			odbackgroundType,
			odbackgroundImage,
			odbackgroundColor,
			odbackgroundPosition,
			odbackgroundAttachment,
			odbackgroundRepeat,
			odbackgroundSize,
			odbackgroundOpacity,

			dbackgroundType,
			dbackgroundImage,
			dbackgroundColor,
			dbackgroundPosition,
			dbackgroundAttachment,
			dbackgroundRepeat,
			dbackgroundSize,
			dbackgroundOpacity,

			odetailbackgroundType,
			odetailbackgroundImage,
			odetailbackgroundColor,
			odetailbackgroundPosition,
			odetailbackgroundAttachment,
			odetailbackgroundRepeat,
			odetailbackgroundSize,
			odetailbackgroundOpacity,

			cdetailbackgroundType,
			cdetailbackgroundImage,
			cdetailbackgroundColor,
			cdetailbackgroundPosition,
			cdetailbackgroundAttachment,
			cdetailbackgroundRepeat,
			cdetailbackgroundSize,
			cdetailbackgroundOpacity,

			//New
			odgradientValue,
			dgradientValue,
			cdetailgradientValue,
			odetailgradientValue,

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
		},
	} = props;

	let loadHeadingGoogleFonts;
	let loadSectionHeadingGoogleFonts;
	let loadSectionContentGoogleFonts;
	let loadOrderOverviewGoogleFonts;
	let loadDownloadHeadingGoogleFonts;
	let loadDownloadContentGoogleFonts;
	let loadOrderDetailHeadingGoogleFonts;
	let loadOrderDetailContentGoogleFonts;
	let loadCustomerDetailHeadingGoogleFonts;
	let loadCustomerDetailContentGoogleFonts;

	if ( true === headingLoadGoogleFonts ) {
		const hconfig = {
			google: {
				families: [
					headingFontFamily +
						( headingFontWeight ? ':' + headingFontWeight : '' ),
				],
			},
		};
		loadHeadingGoogleFonts = (
			<WebfontLoader config={ hconfig }></WebfontLoader>
		);
	}

	if ( true === sectionHeadingLoadGoogleFonts ) {
		const shconfig = {
			google: {
				families: [
					sectionHeadingFontFamily +
						( sectionHeadingFontWeight
							? ':' + sectionHeadingFontWeight
							: '' ),
				],
			},
		};
		loadSectionHeadingGoogleFonts = (
			<WebfontLoader config={ shconfig }></WebfontLoader>
		);
	}
	if ( true === sectionContentLoadGoogleFonts ) {
		const scconfig = {
			google: {
				families: [
					sectionContentFontFamily +
						( sectionContentFontWeight
							? ':' + sectionContentFontWeight
							: '' ),
				],
			},
		};
		loadSectionContentGoogleFonts = (
			<WebfontLoader config={ scconfig }></WebfontLoader>
		);
	}

	if ( true === orderOverviewLoadGoogleFonts ) {
		const ooconfig = {
			google: {
				families: [
					orderOverviewFontFamily +
						( orderOverviewFontWeight
							? ':' + orderOverviewFontWeight
							: '' ),
				],
			},
		};
		loadOrderOverviewGoogleFonts = (
			<WebfontLoader config={ ooconfig }></WebfontLoader>
		);
	}

	if ( true === downloadHeadingLoadGoogleFonts ) {
		const dhconfig = {
			google: {
				families: [
					downloadHeadingFontFamily +
						( downloadHeadingFontWeight
							? ':' + downloadHeadingFontWeight
							: '' ),
				],
			},
		};
		loadDownloadHeadingGoogleFonts = (
			<WebfontLoader config={ dhconfig }></WebfontLoader>
		);
	}
	if ( true === downloadContentLoadGoogleFonts ) {
		const dcconfig = {
			google: {
				families: [
					downloadContentFontFamily +
						( downloadContentFontWeight
							? ':' + downloadContentFontWeight
							: '' ),
				],
			},
		};
		loadDownloadContentGoogleFonts = (
			<WebfontLoader config={ dcconfig }></WebfontLoader>
		);
	}

	if ( true === orderDetailHeadingLoadGoogleFonts ) {
		const odhconfig = {
			google: {
				families: [
					orderDetailHeadingFontFamily +
						( orderDetailHeadingFontWeight
							? ':' + orderDetailHeadingFontWeight
							: '' ),
				],
			},
		};
		loadOrderDetailHeadingGoogleFonts = (
			<WebfontLoader config={ odhconfig }></WebfontLoader>
		);
	}
	if ( true === orderDetailContentLoadGoogleFonts ) {
		const odcconfig = {
			google: {
				families: [
					orderDetailContentFontFamily +
						( orderDetailContentFontWeight
							? ':' + orderDetailContentFontWeight
							: '' ),
				],
			},
		};
		loadOrderDetailContentGoogleFonts = (
			<WebfontLoader config={ odcconfig }></WebfontLoader>
		);
	}

	if ( true === customerDetailHeadingLoadGoogleFonts ) {
		const cdhconfig = {
			google: {
				families: [
					customerDetailHeadingFontFamily +
						( customerDetailHeadingFontWeight
							? ':' + customerDetailHeadingFontWeight
							: '' ),
				],
			},
		};
		loadCustomerDetailHeadingGoogleFonts = (
			<WebfontLoader config={ cdhconfig }></WebfontLoader>
		);
	}
	if ( true === customerDetailContentLoadGoogleFonts ) {
		const cddcconfig = {
			google: {
				families: [
					customerDetailContentFontFamily +
						( customerDetailContentFontWeight
							? ':' + customerDetailContentFontWeight
							: '' ),
				],
			},
		};
		loadCustomerDetailContentGoogleFonts = (
			<WebfontLoader config={ cddcconfig }></WebfontLoader>
		);
	}

	const OrderReviewStyles = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Order Review', 'cartflows' ) }
				initialOpen={ false }
			>
				<div className="wcf-block-settings-subheading">
					{ __( 'Background', 'cartflows' ) }
				</div>
				<Background
					setAttributes={ setAttributes }
					backgroundSize={ {
						value: odbackgroundSize,
						label: 'odbackgroundSize',
					} }
					gradientOverlay={ {
						value: true,
					} }
					backgroundRepeat={ {
						value: odbackgroundRepeat,
						label: 'odbackgroundRepeat',
					} }
					backgroundAttachment={ {
						value: odbackgroundAttachment,
						label: 'odbackgroundAttachment',
					} }
					backgroundPosition={ {
						value: odbackgroundPosition,
						label: 'odbackgroundPosition',
					} }
					backgroundImage={ {
						value: odbackgroundImage,
						label: 'odbackgroundImage',
					} }
					backgroundGradient={ {
						value: odgradientValue,
						label: 'odgradientValue',
					} }
					backgroundColor={ {
						value: odbackgroundColor,
						label: 'odbackgroundColor',
					} }
					backgroundType={ {
						value: odbackgroundType,
						label: 'odbackgroundType',
					} }
					backgroundVideoType={ {
						value: false,
					} }
					{ ...props }
				/>
				<Range
					label={ __( 'Opacity', 'cartflows' ) }
					value={ odbackgroundOpacity }
					onChange={ ( value ) =>
						setAttributes( { odbackgroundOpacity: value } )
					}
					data={ {
						value: odbackgroundOpacity,
						label: 'odbackgroundOpacity',
					} }
					min={ 0 }
					max={ 100 }
					allowReset
					initialPosition={ 0 }
					displayUnit={ false }
					setAttributes={ setAttributes }
				/>
				<div className="wcf-block-settings-subheading">
					{ __( 'Content', 'cartflows' ) }
				</div>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'cartflows' ) }
					colorValue={
						orderOverviewTextColor ? orderOverviewTextColor : ''
					}
					data={ {
						value: orderOverviewTextColor,
						label: 'orderOverviewTextColor',
					} }
					setAttributes={ setAttributes }
				/>
				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: orderOverviewLoadGoogleFonts,
						label: 'orderOverviewLoadGoogleFonts',
					} }
					fontFamily={ {
						value: orderOverviewFontFamily,
						label: 'orderOverviewFontFamily',
					} }
					fontWeight={ {
						value: orderOverviewFontWeight,
						label: 'orderOverviewFontWeight',
					} }
					fontStyle={ {
						value: orderOverviewFontStyle,
						label: 'orderOverviewFontStyle',
					} }
					fontSubset={ {
						value: orderOverviewFontSubset,
						label: 'orderOverviewFontSubset',
					} }
					fontSizeType={ {
						value: orderOverviewFontSizeType,
						label: 'orderOverviewFontSizeType',
					} }
					fontSize={ {
						value: orderOverviewFontSize,
						label: 'orderOverviewFontSize',
					} }
					fontSizeMobile={ {
						value: orderOverviewFontSizeMobile,
						label: 'orderOverviewFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: orderOverviewFontSizeTablet,
						label: 'orderOverviewFontSizeTablet',
					} }
					lineHeightType={ {
						value: orderOverviewLineHeightType,
						label: 'orderOverviewLineHeightType',
					} }
					lineHeight={ {
						value: orderOverviewLineHeight,
						label: 'orderOverviewLineHeight',
					} }
					lineHeightMobile={ {
						value: orderOverviewLineHeightMobile,
						label: 'orderOverviewLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: orderOverviewLineHeightTablet,
						label: 'orderOverviewLineHeightTablet',
					} }
					letterSpacing={ false }
					disableTransform={ true }
					disableDecoration={ true }
				/>
			</UAGAdvancedPanelBody>
		);
	};
	const OrderDetailStyle = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Order Details', 'cartflows' ) }
				initialOpen={ false }
			>
				<div className="wcf-block-settings-subheading">
					{ __( 'Heading', 'cartflows' ) }
				</div>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'cartflows' ) }
					colorValue={
						orderDetailHeadingColor ? orderDetailHeadingColor : ''
					}
					data={ {
						value: orderDetailHeadingColor,
						label: 'orderDetailHeadingColor',
					} }
					setAttributes={ setAttributes }
				/>
				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: orderDetailHeadingLoadGoogleFonts,
						label: 'orderDetailHeadingLoadGoogleFonts',
					} }
					fontFamily={ {
						value: orderDetailHeadingFontFamily,
						label: 'orderDetailHeadingFontFamily',
					} }
					fontWeight={ {
						value: orderDetailHeadingFontWeight,
						label: 'orderDetailHeadingFontWeight',
					} }
					fontStyle={ {
						value: orderDetailHeadingFontStyle,
						label: 'orderDetailHeadingFontStyle',
					} }
					fontSubset={ {
						value: orderDetailHeadingFontSubset,
						label: 'orderDetailHeadingFontSubset',
					} }
					fontSizeType={ {
						value: orderDetailHeadingFontSizeType,
						label: 'orderDetailHeadingFontSizeType',
					} }
					fontSize={ {
						value: orderDetailHeadingFontSize,
						label: 'orderDetailHeadingFontSize',
					} }
					fontSizeMobile={ {
						value: orderDetailHeadingFontSizeMobile,
						label: 'orderDetailHeadingFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: orderDetailHeadingFontSizeTablet,
						label: 'orderDetailHeadingFontSizeTablet',
					} }
					lineHeightType={ {
						value: orderDetailHeadingLineHeightType,
						label: 'orderDetailHeadingLineHeightType',
					} }
					lineHeight={ {
						value: orderDetailHeadingLineHeight,
						label: 'orderDetailHeadingLineHeight',
					} }
					lineHeightMobile={ {
						value: orderDetailHeadingLineHeightMobile,
						label: 'orderDetailHeadingLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: orderDetailHeadingLineHeightTablet,
						label: 'orderDetailHeadingLineHeightTablet',
					} }
					letterSpacing={ false }
					disableTransform={ true }
					disableDecoration={ true }
				/>
				<div className="wcf-block-settings-subheading">
					{ __( 'Content', 'cartflows' ) }
				</div>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'cartflows' ) }
					colorValue={
						orderDetailContentColor ? orderDetailContentColor : ''
					}
					data={ {
						value: orderDetailContentColor,
						label: 'orderDetailContentColor',
					} }
					setAttributes={ setAttributes }
				/>
				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: orderDetailContentLoadGoogleFonts,
						label: 'orderDetailContentLoadGoogleFonts',
					} }
					fontFamily={ {
						value: orderDetailContentFontFamily,
						label: 'orderDetailContentFontFamily',
					} }
					fontWeight={ {
						value: orderDetailContentFontWeight,
						label: 'orderDetailContentFontWeight',
					} }
					fontStyle={ {
						value: orderDetailContentFontStyle,
						label: 'orderDetailContentFontStyle',
					} }
					fontSubset={ {
						value: orderDetailContentFontSubset,
						label: 'orderDetailContentFontSubset',
					} }
					fontSizeType={ {
						value: orderDetailContentFontSizeType,
						label: 'orderDetailContentFontSizeType',
					} }
					fontSize={ {
						value: orderDetailContentFontSize,
						label: 'orderDetailContentFontSize',
					} }
					fontSizeMobile={ {
						value: orderDetailContentFontSizeMobile,
						label: 'orderDetailContentFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: orderDetailContentFontSizeTablet,
						label: 'orderDetailContentFontSizeTablet',
					} }
					lineHeightType={ {
						value: orderDetailContentLineHeightType,
						label: 'orderDetailContentLineHeightType',
					} }
					lineHeight={ {
						value: orderDetailContentLineHeight,
						label: 'orderDetailContentLineHeight',
					} }
					lineHeightMobile={ {
						value: orderDetailContentLineHeightMobile,
						label: 'orderDetailContentLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: orderDetailContentLineHeightTablet,
						label: 'orderDetailContentLineHeightTablet',
					} }
					letterSpacing={ false }
					disableTransform={ true }
					disableDecoration={ true }
				/>
				<div className="wcf-block-settings-subheading">
					{ __( 'Background', 'cartflows' ) }
				</div>
				<Background
					setAttributes={ setAttributes }
					backgroundSize={ {
						value: odetailbackgroundSize,
						label: 'odetailbackgroundSize',
					} }
					gradientOverlay={ {
						value: true,
					} }
					backgroundRepeat={ {
						value: odetailbackgroundRepeat,
						label: 'odetailbackgroundRepeat',
					} }
					backgroundAttachment={ {
						value: odetailbackgroundAttachment,
						label: 'odetailbackgroundAttachment',
					} }
					backgroundPosition={ {
						value: odetailbackgroundPosition,
						label: 'odetailbackgroundPosition',
					} }
					backgroundImage={ {
						value: odetailbackgroundImage,
						label: 'odetailbackgroundImage',
					} }
					backgroundGradient={ {
						value: odetailgradientValue,
						label: 'odetailgradientValue',
					} }
					backgroundColor={ {
						value: odetailbackgroundColor,
						label: 'odetailbackgroundColor',
					} }
					backgroundType={ {
						value: odetailbackgroundType,
						label: 'odetailbackgroundType',
					} }
					backgroundVideoType={ {
						value: false,
					} }
					{ ...props }
				/>
				<Range
					label={ __( 'Opacity', 'cartflows' ) }
					value={ odetailbackgroundOpacity }
					onChange={ ( value ) =>
						setAttributes( { odetailbackgroundOpacity: value } )
					}
					data={ {
						value: odetailbackgroundOpacity,
						label: 'odetailbackgroundOpacity',
					} }
					min={ 0 }
					max={ 100 }
					allowReset
					initialPosition={ 0 }
					displayUnit={ false }
					setAttributes={ setAttributes }
				/>
			</UAGAdvancedPanelBody>
		);
	};
	const DownloadStyles = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Download Details', 'cartflows' ) }
				initialOpen={ false }
			>
				<div className="wcf-block-settings-subheading">
					{ __( 'Heading', 'cartflows' ) }
				</div>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'cartflows' ) }
					colorValue={
						downloadHeadingColor ? downloadHeadingColor : ''
					}
					data={ {
						value: downloadHeadingColor,
						label: 'downloadHeadingColor',
					} }
					setAttributes={ setAttributes }
				/>
				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: downloadHeadingLoadGoogleFonts,
						label: 'downloadHeadingLoadGoogleFonts',
					} }
					fontFamily={ {
						value: downloadHeadingFontFamily,
						label: 'downloadHeadingFontFamily',
					} }
					fontWeight={ {
						value: downloadHeadingFontWeight,
						label: 'downloadHeadingFontWeight',
					} }
					fontStyle={ {
						value: downloadHeadingFontStyle,
						label: 'downloadHeadingFontStyle',
					} }
					fontSubset={ {
						value: downloadHeadingFontSubset,
						label: 'downloadHeadingFontSubset',
					} }
					fontSizeType={ {
						value: downloadHeadingFontSizeType,
						label: 'downloadHeadingFontSizeType',
					} }
					fontSize={ {
						value: downloadHeadingFontSize,
						label: 'downloadHeadingFontSize',
					} }
					fontSizeMobile={ {
						value: downloadHeadingFontSizeMobile,
						label: 'downloadHeadingFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: downloadHeadingFontSizeTablet,
						label: 'downloadHeadingFontSizeTablet',
					} }
					lineHeightType={ {
						value: downloadHeadingLineHeightType,
						label: 'downloadHeadingLineHeightType',
					} }
					lineHeight={ {
						value: downloadHeadingLineHeight,
						label: 'downloadHeadingLineHeight',
					} }
					lineHeightMobile={ {
						value: downloadHeadingLineHeightMobile,
						label: 'downloadHeadingLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: downloadHeadingLineHeightTablet,
						label: 'downloadHeadingLineHeightTablet',
					} }
					letterSpacing={ false }
					disableTransform={ true }
					disableDecoration={ true }
				/>
				<div className="wcf-block-settings-subheading">
					{ __( 'Content', 'cartflows' ) }
				</div>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'cartflows' ) }
					colorValue={
						downloadContentColor ? downloadContentColor : ''
					}
					data={ {
						value: downloadContentColor,
						label: 'downloadContentColor',
					} }
					setAttributes={ setAttributes }
				/>
				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: downloadContentLoadGoogleFonts,
						label: 'downloadContentLoadGoogleFonts',
					} }
					fontFamily={ {
						value: downloadContentFontFamily,
						label: 'downloadContentFontFamily',
					} }
					fontWeight={ {
						value: downloadContentFontWeight,
						label: 'downloadContentFontWeight',
					} }
					fontStyle={ {
						value: downloadContentFontStyle,
						label: 'downloadContentFontStyle',
					} }
					fontSubset={ {
						value: downloadContentFontSubset,
						label: 'downloadContentFontSubset',
					} }
					fontSizeType={ {
						value: downloadContentFontSizeType,
						label: 'downloadContentFontSizeType',
					} }
					fontSize={ {
						value: downloadContentFontSize,
						label: 'downloadContentFontSize',
					} }
					fontSizeMobile={ {
						value: downloadContentFontSizeMobile,
						label: 'downloadContentFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: downloadContentFontSizeTablet,
						label: 'downloadContentFontSizeTablet',
					} }
					lineHeightType={ {
						value: downloadContentLineHeightType,
						label: 'downloadContentLineHeightType',
					} }
					lineHeight={ {
						value: downloadContentLineHeight,
						label: 'downloadContentLineHeight',
					} }
					lineHeightMobile={ {
						value: downloadContentLineHeightMobile,
						label: 'downloadContentLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: downloadContentLineHeightTablet,
						label: 'downloadContentLineHeightTablet',
					} }
					letterSpacing={ false }
					disableTransform={ true }
					disableDecoration={ true }
				/>
				<div className="wcf-block-settings-subheading">
					{ __( 'Background', 'cartflows' ) }
				</div>
				<Background
					setAttributes={ setAttributes }
					backgroundSize={ {
						value: dbackgroundSize,
						label: 'dbackgroundSize',
					} }
					gradientOverlay={ {
						value: true,
					} }
					backgroundRepeat={ {
						value: dbackgroundRepeat,
						label: 'dbackgroundRepeat',
					} }
					backgroundAttachment={ {
						value: dbackgroundAttachment,
						label: 'dbackgroundAttachment',
					} }
					backgroundPosition={ {
						value: dbackgroundPosition,
						label: 'dbackgroundPosition',
					} }
					backgroundImage={ {
						value: dbackgroundImage,
						label: 'dbackgroundImage',
					} }
					backgroundGradient={ {
						value: dgradientValue,
						label: 'dgradientValue',
					} }
					backgroundColor={ {
						value: dbackgroundColor,
						label: 'dbackgroundColor',
					} }
					backgroundType={ {
						value: dbackgroundType,
						label: 'dbackgroundType',
					} }
					backgroundVideoType={ {
						value: false,
					} }
					{ ...props }
				/>
				<Range
					label={ __( 'Opacity', 'cartflows' ) }
					value={ dbackgroundOpacity }
					onChange={ ( value ) =>
						setAttributes( {
							dbackgroundOpacity: value,
						} )
					}
					data={ {
						value: dbackgroundOpacity,
						label: 'dbackgroundOpacity',
					} }
					min={ 0 }
					max={ 100 }
					allowReset
					initialPosition={ 0 }
					displayUnit={ false }
					setAttributes={ setAttributes }
				/>
			</UAGAdvancedPanelBody>
		);
	};
	const CustomerDetailStyle = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Customer Details', 'cartflows' ) }
				initialOpen={ false }
			>
				<div className="wcf-block-settings-subheading">
					{ __( 'Heading', 'cartflows' ) }
				</div>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'cartflows' ) }
					colorValue={
						customerDetailHeadingColor
							? customerDetailHeadingColor
							: ''
					}
					data={ {
						value: customerDetailHeadingColor,
						label: 'customerDetailHeadingColor',
					} }
					setAttributes={ setAttributes }
				/>
				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: customerDetailHeadingLoadGoogleFonts,
						label: 'customerDetailHeadingLoadGoogleFonts',
					} }
					fontFamily={ {
						value: customerDetailHeadingFontFamily,
						label: 'customerDetailHeadingFontFamily',
					} }
					fontWeight={ {
						value: customerDetailHeadingFontWeight,
						label: 'customerDetailHeadingFontWeight',
					} }
					fontStyle={ {
						value: customerDetailHeadingFontStyle,
						label: 'customerDetailHeadingFontStyle',
					} }
					fontSubset={ {
						value: customerDetailHeadingFontSubset,
						label: 'customerDetailHeadingFontSubset',
					} }
					fontSizeType={ {
						value: customerDetailHeadingFontSizeType,
						label: 'customerDetailHeadingFontSizeType',
					} }
					fontSize={ {
						value: customerDetailHeadingFontSize,
						label: 'customerDetailHeadingFontSize',
					} }
					fontSizeMobile={ {
						value: customerDetailHeadingFontSizeMobile,
						label: 'customerDetailHeadingFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: customerDetailHeadingFontSizeTablet,
						label: 'customerDetailHeadingFontSizeTablet',
					} }
					lineHeightType={ {
						value: customerDetailHeadingLineHeightType,
						label: 'customerDetailHeadingLineHeightType',
					} }
					lineHeight={ {
						value: customerDetailHeadingLineHeight,
						label: 'customerDetailHeadingLineHeight',
					} }
					lineHeightMobile={ {
						value: customerDetailHeadingLineHeightMobile,
						label: 'customerDetailHeadingLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: customerDetailHeadingLineHeightTablet,
						label: 'customerDetailHeadingLineHeightTablet',
					} }
					letterSpacing={ false }
					disableTransform={ true }
					disableDecoration={ true }
				/>
				<div className="wcf-block-settings-subheading">
					{ __( 'Content', 'cartflows' ) }
				</div>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'cartflows' ) }
					colorValue={
						customerDetailContentColor
							? customerDetailContentColor
							: ''
					}
					data={ {
						value: customerDetailContentColor,
						label: 'customerDetailContentColor',
					} }
					setAttributes={ setAttributes }
				/>
				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: customerDetailContentLoadGoogleFonts,
						label: 'customerDetailContentLoadGoogleFonts',
					} }
					fontFamily={ {
						value: customerDetailContentFontFamily,
						label: 'customerDetailContentFontFamily',
					} }
					fontStyle={ {
						value: customerDetailContentFontStyle,
						label: 'customerDetailContentFontStyle',
					} }
					fontWeight={ {
						value: customerDetailContentFontWeight,
						label: 'customerDetailContentFontWeight',
					} }
					fontSubset={ {
						value: customerDetailContentFontSubset,
						label: 'customerDetailContentFontSubset',
					} }
					fontSizeType={ {
						value: customerDetailContentFontSizeType,
						label: 'customerDetailContentFontSizeType',
					} }
					fontSize={ {
						value: customerDetailContentFontSize,
						label: 'customerDetailContentFontSize',
					} }
					fontSizeMobile={ {
						value: customerDetailContentFontSizeMobile,
						label: 'customerDetailContentFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: customerDetailContentFontSizeTablet,
						label: 'customerDetailContentFontSizeTablet',
					} }
					lineHeightType={ {
						value: customerDetailContentLineHeightType,
						label: 'customerDetailContentLineHeightType',
					} }
					lineHeight={ {
						value: customerDetailContentLineHeight,
						label: 'customerDetailContentLineHeight',
					} }
					lineHeightMobile={ {
						value: customerDetailContentLineHeightMobile,
						label: 'customerDetailContentLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: customerDetailContentLineHeightTablet,
						label: 'customerDetailContentLineHeightTablet',
					} }
					letterSpacing={ false }
					disableTransform={ true }
					disableDecoration={ true }
				/>
				<div className="wcf-block-settings-subheading">
					{ __( 'Background', 'cartflows' ) }
				</div>
				<Background
					setAttributes={ setAttributes }
					backgroundSize={ {
						value: cdetailbackgroundSize,
						label: 'cdetailbackgroundSize',
					} }
					gradientOverlay={ {
						value: true,
					} }
					backgroundRepeat={ {
						value: cdetailbackgroundRepeat,
						label: 'cdetailbackgroundRepeat',
					} }
					backgroundAttachment={ {
						value: cdetailbackgroundAttachment,
						label: 'cdetailbackgroundAttachment',
					} }
					backgroundPosition={ {
						value: cdetailbackgroundPosition,
						label: 'cdetailbackgroundPosition',
					} }
					backgroundImage={ {
						value: cdetailbackgroundImage,
						label: 'cdetailbackgroundImage',
					} }
					backgroundGradient={ {
						value: cdetailgradientValue,
						label: 'cdetailgradientValue',
					} }
					backgroundColor={ {
						value: cdetailbackgroundColor,
						label: 'cdetailbackgroundColor',
					} }
					backgroundType={ {
						value: cdetailbackgroundType,
						label: 'cdetailbackgroundType',
					} }
					backgroundVideoType={ {
						value: false,
					} }
					{ ...props }
				/>
				<Range
					label={ __( 'Opacity', 'cartflows' ) }
					value={ cdetailbackgroundOpacity }
					onChange={ ( value ) =>
						setAttributes( {
							cdetailbackgroundOpacity: value,
						} )
					}
					data={ {
						value: cdetailbackgroundOpacity,
						label: 'cdetailbackgroundOpacity',
					} }
					min={ 0 }
					max={ 100 }
					allowReset
					initialPosition={ 0 }
					displayUnit={ false }
					setAttributes={ setAttributes }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const SectionGlobalStyles = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Sections', 'cartflows' ) }
				initialOpen={ false }
				className="uagb__url-panel-body"
			>
				<div className="wcf-block-settings-subheading">
					{ __( 'Heading', 'cartflows' ) }
				</div>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'cartflows' ) }
					colorValue={ sectionHeadingColor }
					data={ {
						value: sectionHeadingColor,
						label: 'sectionHeadingColor',
					} }
					setAttributes={ setAttributes }
				/>
				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: sectionHeadingLoadGoogleFonts,
						label: 'sectionHeadingLoadGoogleFonts',
					} }
					fontFamily={ {
						value: sectionHeadingFontFamily,
						label: 'sectionHeadingFontFamily',
					} }
					fontWeight={ {
						value: sectionHeadingFontWeight,
						label: 'sectionHeadingFontWeight',
					} }
					fontStyle={ {
						value: sectionHeadingFontStyle,
						label: 'sectionHeadingFontStyle',
					} }
					fontSubset={ {
						value: sectionHeadingFontSubset,
						label: 'sectionHeadingFontSubset',
					} }
					fontSizeType={ {
						value: sectionHeadingFontSizeType,
						label: 'sectionHeadingFontSizeType',
					} }
					fontSize={ {
						value: sectionHeadingFontSize,
						label: 'sectionHeadingFontSize',
					} }
					fontSizeMobile={ {
						value: sectionHeadingFontSizeMobile,
						label: 'sectionHeadingFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: sectionHeadingFontSizeTablet,
						label: 'sectionHeadingFontSizeTablet',
					} }
					lineHeightType={ {
						value: sectionHeadingLineHeightType,
						label: 'sectionHeadingLineHeightType',
					} }
					lineHeight={ {
						value: sectionHeadingLineHeight,
						label: 'sectionHeadingLineHeight',
					} }
					lineHeightMobile={ {
						value: sectionHeadingLineHeightMobile,
						label: 'sectionHeadingLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: sectionHeadingLineHeightTablet,
						label: 'sectionHeadingLineHeightTablet',
					} }
					letterSpacing={ false }
					disableTransform={ true }
					disableDecoration={ true }
				/>
				<div className="wcf-block-settings-subheading">
					{ __( 'Content', 'cartflows' ) }
				</div>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'cartflows' ) }
					colorValue={ sectionContentColor }
					data={ {
						value: sectionContentColor,
						label: 'sectionContentColor',
					} }
					setAttributes={ setAttributes }
				/>
				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: sectionContentLoadGoogleFonts,
						label: 'sectionContentLoadGoogleFonts',
					} }
					fontFamily={ {
						value: sectionContentFontFamily,
						label: 'sectionContentFontFamily',
					} }
					fontWeight={ {
						value: sectionContentFontWeight,
						label: 'sectionContentFontWeight',
					} }
					fontStyle={ {
						value: sectionContentFontStyle,
						label: 'sectionContentFontStyle',
					} }
					fontSubset={ {
						value: sectionContentFontSubset,
						label: 'sectionContentFontSubset',
					} }
					fontSizeType={ {
						value: sectionContentFontSizeType,
						label: 'sectionContentFontSizeType',
					} }
					fontSize={ {
						value: sectionContentFontSize,
						label: 'sectionContentFontSize',
					} }
					fontSizeMobile={ {
						value: sectionContentFontSizeMobile,
						label: 'sectionContentFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: sectionContentFontSizeTablet,
						label: 'sectionContentFontSizeTablet',
					} }
					lineHeightType={ {
						value: sectionContentLineHeightType,
						label: 'sectionContentLineHeightType',
					} }
					lineHeight={ {
						value: sectionContentLineHeight,
						label: 'sectionContentLineHeight',
					} }
					lineHeightMobile={ {
						value: sectionContentLineHeightMobile,
						label: 'sectionContentLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: sectionContentLineHeightTablet,
						label: 'sectionContentLineHeightTablet',
					} }
					letterSpacing={ false }
					disableTransform={ true }
					disableDecoration={ true }
				/>
				<div className="wcf-block-settings-subheading">
					{ __( 'Background', 'cartflows' ) }
				</div>
				<Background
					setAttributes={ setAttributes }
					backgroundSize={ {
						value: backgroundSize,
						label: 'backgroundSize',
					} }
					gradientOverlay={ {
						value: true,
					} }
					backgroundRepeat={ {
						value: backgroundRepeat,
						label: 'backgroundRepeat',
					} }
					backgroundAttachment={ {
						value: backgroundAttachment,
						label: 'backgroundAttachment',
					} }
					backgroundPosition={ {
						value: backgroundPosition,
						label: 'backgroundPosition',
					} }
					backgroundImage={ {
						value: backgroundImage,
						label: 'backgroundImage',
					} }
					backgroundGradient={ {
						value: gradientValue,
						label: 'gradientValue',
					} }
					backgroundColor={ {
						value: backgroundColor,
						label: 'backgroundColor',
					} }
					backgroundType={ {
						value: backgroundType,
						label: 'backgroundType',
					} }
					backgroundVideoType={ {
						value: false,
					} }
					{ ...props }
				/>
				<Range
					label={ __( 'Opacity', 'cartflows' ) }
					value={ backgroundOpacity }
					onChange={ ( value ) =>
						setAttributes( { backgroundOpacity: value } )
					}
					data={ {
						value: backgroundOpacity,
						label: 'backgroundOpacity',
					} }
					min={ 0 }
					max={ 100 }
					allowReset
					initialPosition={ 0 }
					setAttributes={ setAttributes }
				/>
				<Range
					label={ __( 'Section Spacing', 'cartflows' ) }
					value={ sectionSpacing }
					onChange={ ( value ) =>
						setAttributes( { sectionSpacing: value } )
					}
					data={ {
						value: sectionSpacing,
						label: 'sectionSpacing',
					} }
					min={ 0 }
					max={ 100 }
					allowReset
					initialPosition={ 0 }
					displayUnit={ false }
					setAttributes={ setAttributes }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const ThankyouText = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Heading', 'cartflows' ) }
				initialOpen={ false }
				className="uagb__url-panel-body"
			>
				<p>{ __( 'Thank You Text', 'cartflows' ) }</p>
				<TextControl
					value={ thanyouText }
					onChange={ ( value ) =>
						setAttributes( { thanyouText: value } )
					}
					placeholder={ __(
						'Thank you. Your order has been received.',
						'cartflows'
					) }
				/>
			</UAGAdvancedPanelBody>
		);
	};
	const Sections = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Sections', 'cartflows' ) }
				initialOpen={ false }
				className="uagb__url-panel-body"
			>
				<ToggleControl
					label={ __( 'Order Overview', 'cartflows' ) }
					checked={ orderOverview }
					help={ __( 'Hide/Show Order Overview.', 'cartflows' ) }
					onChange={ () =>
						setAttributes( { orderOverview: ! orderOverview } )
					}
				/>
				<ToggleControl
					label={ __( 'Order Detail', 'cartflows' ) }
					checked={ orderDetails }
					help={ __( 'Hide/Show Order Detail.', 'cartflows' ) }
					onChange={ () =>
						setAttributes( { orderDetails: ! orderDetails } )
					}
				/>
				<ToggleControl
					label={ __( 'Billing Address', 'cartflows' ) }
					checked={ billingAddress }
					help={ __( 'Hide/Show Billing Address.', 'cartflows' ) }
					onChange={ () =>
						setAttributes( {
							billingAddress: ! billingAddress,
						} )
					}
				/>
				<ToggleControl
					label={ __( 'Shipping Address', 'cartflows' ) }
					checked={ shippingAddress }
					help={ __( 'Hide/Show Shipping Address.', 'cartflows' ) }
					onChange={ () =>
						setAttributes( {
							shippingAddress: ! shippingAddress,
						} )
					}
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const ThankyouTextStyles = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Heading Text', 'cartflows' ) }
				initialOpen={ false }
			>
				<AdvancedPopColorControl
					label={ __( 'Heading Text Color', 'cartflows' ) }
					colorValue={ headingColor ? headingColor : '' }
					data={ {
						value: headingColor,
						label: 'headingColor',
					} }
					setAttributes={ setAttributes }
				/>
				<MultiButtonsControl
					setAttributes={ setAttributes }
					label={ __( 'Text Alignment', 'cartflows' ) }
					data={ {
						value: headingAlignment,
						label: 'headingAlignment',
					} }
					className="uagb-multi-button-alignment-control"
					options={ [
						{
							value: 'left',
							icon: (
								<Icon
									icon={ renderSVG( 'fa fa-align-left' ) }
								/>
							),
							tooltip: __( 'Left', 'cartflows' ),
						},
						{
							value: 'center',
							icon: (
								<Icon
									icon={ renderSVG( 'fa fa-align-center' ) }
								/>
							),
							tooltip: __( 'Center', 'cartflows' ),
						},
						{
							value: 'right',
							icon: (
								<Icon
									icon={ renderSVG( 'fa fa-align-right' ) }
								/>
							),
							tooltip: __( 'Right', 'cartflows' ),
						},
					] }
					showIcons={ true }
				/>
				<Range
					label={ __( 'Heading Bottom Spacing(px)', 'cartflows' ) }
					value={ headingBottomSpacing }
					setAttributes={ setAttributes }
					onChange={ ( value ) =>
						setAttributes( { headingBottomSpacing: value } )
					}
					data={ {
						value: headingBottomSpacing,
						label: 'headingBottomSpacing',
					} }
					min={ 0 }
					max={ 100 }
					allowReset
					initialPosition={ 0 }
					displayUnit={ false }
				/>

				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: headingLoadGoogleFonts,
						label: 'headingLoadGoogleFonts',
					} }
					fontFamily={ {
						value: headingFontFamily,
						label: 'headingFontFamily',
					} }
					fontWeight={ {
						value: headingFontWeight,
						label: 'headingFontWeight',
					} }
					fontStyle={ {
						value: headingFontStyle,
						label: 'headingFontStyle',
					} }
					fontSubset={ {
						value: headingFontSubset,
						label: 'headingFontSubset',
					} }
					fontSizeType={ {
						value: headingFontSizeType,
						label: 'headingFontSizeType',
					} }
					fontSize={ {
						value: headingFontSize,
						label: 'headingFontSize',
					} }
					fontSizeMobile={ {
						value: headingFontSizeMobile,
						label: 'headingFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: headingFontSizeTablet,
						label: 'headingFontSizeTablet',
					} }
					lineHeightType={ {
						value: headingLineHeightType,
						label: 'headingLineHeightType',
					} }
					lineHeight={ {
						value: headingLineHeight,
						label: 'headingLineHeight',
					} }
					lineHeightMobile={ {
						value: headingLineHeightMobile,
						label: 'headingLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: headingLineHeightTablet,
						label: 'headingLineHeightTablet',
					} }
					// letterSpacing={ {
					// 	value: generalLetterSpacing,
					// 	label: 'generalLetterSpacing',
					// } }
					// letterSpacingTablet={ {
					// 	value: generalLetterSpacingTablet,
					// 	label: 'generalLetterSpacingTablet',
					// } }
					// letterSpacingMobile={ {
					// 	value: generalLetterSpacingMobile,
					// 	label: 'generalLetterSpacingMobile',
					// } }
					// letterSpacingType={ {
					// 	value: generalLetterSpacingType,
					// 	label: 'generalLetterSpacingType',
					// } }
					letterSpacing={ false }
					disableTransform={ true }
					disableDecoration={ true }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	return (
		<>
			<InspectorControls>
				<InspectorTabs>
					<InspectorTab { ...UAGTabs.general }>
						{ ThankyouText() }
						{ Sections() }
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }>
						{ ThankyouTextStyles() }
						{ SectionGlobalStyles() }
						{ OrderReviewStyles() }
						{ DownloadStyles() }
						{ OrderDetailStyle() }
						{ CustomerDetailStyle() }
					</InspectorTab>
					<InspectorTab
						{ ...UAGTabs.advance }
						parentProps={ props }
					></InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			{ loadHeadingGoogleFonts }
			{ loadSectionHeadingGoogleFonts }
			{ loadSectionContentGoogleFonts }
			{ loadOrderOverviewGoogleFonts }
			{ loadDownloadHeadingGoogleFonts }
			{ loadDownloadContentGoogleFonts }
			{ loadOrderDetailHeadingGoogleFonts }
			{ loadOrderDetailContentGoogleFonts }
			{ loadCustomerDetailHeadingGoogleFonts }
			{ loadCustomerDetailContentGoogleFonts }
		</>
	);
};

export default React.memo( Settings );
