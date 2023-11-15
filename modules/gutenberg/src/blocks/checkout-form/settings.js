import React from 'react';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import Background from '@Components/background';
import Border from '@Components/responsive-border';
import UAGSelectControl from '@Components/select-control';
import TypographyControl from '@Components/typography';
import WebfontLoader from '@Components/typography/fontloader';

import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import Range from '@Components/range/Range.js';
import SpacingControl from '@Components/spacing-control';

import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import BoxShadowControl from '@Components/box-shadow';

const Settings = ( props ) => {
	props = props.parentProps;

	const {
		attributes: {
			inputSkins,
			layout,
			inputFontSize,
			inputFontSizeType,
			inputFontSizeTablet,
			inputFontSizeMobile,
			inputFontFamily,
			inputFontWeight,
			inputFontSubset,
			inputLineHeightType,
			inputLineHeight,
			inputLineHeightTablet,
			inputLineHeightMobile,
			inputLoadGoogleFonts,
			inputTransform,
			inputLetterSpacing,
			inputLetterSpacingTablet,
			inputLetterSpacingMobile,
			inputLetterSpacingType,
			fieldBgColor,
			fieldLabelColor,
			fieldInputColor,
			// fieldBorderStyle,
			// fieldBorderWidth,
			// fieldBorderRadius,
			// fieldBorderColor,
			// fieldBorderHoverColor,
			errorMsgColor,
			errorMsgBgColor,
			errorMsgBorderColor,
			paymentdescriptionColor,
			paymenttitleColor,
			sectionbgColor,
			informationbgColor,
			sectionBorderRadius,
			buttonFontSize,
			buttonFontSizeType,
			buttonFontSizeTablet,
			buttonFontSizeMobile,
			buttonFontFamily,
			buttonFontWeight,
			buttonFontSubset,
			buttonLineHeightType,
			buttonLineHeight,
			buttonLineHeightTablet,
			buttonLineHeightMobile,
			buttonLoadGoogleFonts,
			buttonTextColor,
			buttonTextHoverColor,
			buttonTransform,
			buttonLetterSpacing,
			buttonLetterSpacingTablet,
			buttonLetterSpacingMobile,
			buttonLetterSpacingType,
			// buttonBorderColor,
			// buttonBorderHoverColor,
			// buttonBorderStyle,
			// buttonBorderWidth,
			// buttonBorderRadius,
			headBgColor,
			headFontSize,
			headFontSizeType,
			headFontSizeTablet,
			headFontSizeMobile,
			headFontFamily,
			headFontWeight,
			headFontSubset,
			headLineHeightType,
			headLineHeight,
			headLineHeightTablet,
			headLineHeightMobile,
			headLoadGoogleFonts,
			headTransform,
			headLetterSpacing,
			headLetterSpacingTablet,
			headLetterSpacingMobile,
			headLetterSpacingType,
			globaltextColor,
			globalbgColor,
			globalFontSize,
			globalFontSizeType,
			globalFontSizeTablet,
			globalFontSizeMobile,
			globalFontFamily,
			globalFontWeight,
			globalFontSubset,
			globalLineHeightType,
			globalLineHeight,
			globalLineHeightTablet,
			globalLineHeightMobile,
			globalLoadGoogleFonts,
			globalTransform,
			globalLetterSpacing,
			globalLetterSpacingTablet,
			globalLetterSpacingMobile,
			globalLetterSpacingType,
			backgroundType,
			backgroundImage,
			backgroundColor,
			backgroundHoverColor,
			backgroundPosition,
			backgroundAttachment,
			backgroundRepeat,
			backgroundSize,
			backgroundOpacity,
			boxShadowColor,
			boxShadowHOffset,
			boxShadowVOffset,
			boxShadowBlur,
			boxShadowSpread,
			boxShadowPosition,
			errorLabelColor,
			errorFieldBorderColor,
			orderReviewColumnColor,
			orderReviewColumnTextColor,
			gradientValue,

			//New attrs.
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
			spacingLink,

			buttonFontStyle,
			inputFontStyle,
			globalFontStyle,
			headFontStyle,
		},
		setAttributes,
		attributes,
	} = props;

	let loadButtonGoogleFonts;
	let loadheadGoogleFonts;
	let loadglobalGoogleFonts;
	let loadinputLoadGoogleFonts;

	if ( inputLoadGoogleFonts === true ) {
		const inputconfig = {
			google: {
				families: [
					globalFontFamily +
						( globalFontWeight ? ':' + globalFontWeight : '' ),
				],
			},
		};

		loadinputLoadGoogleFonts = (
			<WebfontLoader config={ inputconfig }></WebfontLoader>
		);
	}

	if ( globalLoadGoogleFonts === true ) {
		const globalconfig = {
			google: {
				families: [
					globalFontFamily +
						( globalFontWeight ? ':' + globalFontWeight : '' ),
				],
			},
		};

		loadglobalGoogleFonts = (
			<WebfontLoader config={ globalconfig }></WebfontLoader>
		);
	}

	if ( buttonLoadGoogleFonts === true ) {
		const buttonconfig = {
			google: {
				families: [
					buttonFontFamily +
						( buttonFontWeight ? ':' + buttonFontWeight : '' ),
				],
			},
		};

		loadButtonGoogleFonts = (
			<WebfontLoader config={ buttonconfig }></WebfontLoader>
		);
	}

	if ( headLoadGoogleFonts === true ) {
		const headconfig = {
			google: {
				families: [
					headFontFamily +
						( headFontWeight ? ':' + headFontWeight : '' ),
				],
			},
		};

		loadheadGoogleFonts = (
			<WebfontLoader config={ headconfig }></WebfontLoader>
		);
	}

	let Options = [];

	if ( cf_blocks_info.is_cartflows_pro_install === '' ) {
		Options = [
			{
				value: 'modern-checkout',
				label: __( 'Modern Checkout', 'cartflows' ),
			},
			{
				value: 'modern-one-column',
				label: __( 'Modern One Column', 'cartflows' ),
			},
			{ value: 'two-column', label: __( 'Two Column', 'cartflows' ) },
			{
				value: 'one-column',
				label: __( 'One Column', 'cartflows' ),
			},
			{
				value: 'two-step',
				label: __( 'Two Step ( Pro )', 'cartflows' ),
			},
			{
				value: 'multistep-checkout',
				label: __( 'Multistep Checkout ( Pro )', 'cartflows' ),
			},
		];
	} else {
		Options = [
			{
				value: 'modern-checkout',
				label: __( 'Modern Checkout', 'cartflows' ),
			},
			{
				value: 'modern-one-column',
				label: __( 'Modern One Column', 'cartflows' ),
			},
			{ value: 'two-column', label: __( 'Two Column', 'cartflows' ) },
			{ value: 'one-column', label: __( 'One Column', 'cartflows' ) },
			{ value: 'two-step', label: __( 'Two Step', 'cartflows' ) },
			{
				value: 'multistep-checkout',
				label: __( 'Multistep Checkout', 'cartflows' ),
			},
		];
	}

	const skinOption = [
		{ value: 'deafult', label: __( 'Default', 'cartflows' ) },
		{ value: 'modern-label', label: __( 'Modern Labels', 'cartflows' ) },
	];

	const ButtonStyles = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Buttons', 'cartflows' ) }
				initialOpen={ false }
			>
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

				{ backgroundType === 'color' && (
					<AdvancedPopColorControl
						label={ __( 'Background Hover Color', 'cartflows' ) }
						colorValue={
							backgroundHoverColor ? backgroundHoverColor : ''
						}
						data={ {
							value: backgroundHoverColor,
							label: 'backgroundHoverColor',
						} }
						setAttributes={ setAttributes }
					/>
				) }

				<Border
					setAttributes={ setAttributes }
					deviceType={ props.deviceType }
					prefix={ 'button' }
					attributes={ attributes }
				/>
				<BoxShadowControl
					setAttributes={ setAttributes }
					label={ __( 'Box Shadow', 'cartflows' ) }
					boxShadowColor={ {
						value: boxShadowColor,
						label: 'boxShadowColor',
						title: __( 'Color', 'cartflows' ),
					} }
					boxShadowHOffset={ {
						value: boxShadowHOffset,
						label: 'boxShadowHOffset',
						title: __( 'Horizontal', 'cartflows' ),
					} }
					boxShadowVOffset={ {
						value: boxShadowVOffset,
						label: 'boxShadowVOffset',
						title: __( 'Vertical', 'cartflows' ),
					} }
					boxShadowBlur={ {
						value: boxShadowBlur,
						label: 'boxShadowBlur',
						title: __( 'Blur', 'cartflows' ),
					} }
					boxShadowSpread={ {
						value: boxShadowSpread,
						label: 'boxShadowSpread',
						title: __( 'Spread', 'cartflows' ),
					} }
					boxShadowPosition={ {
						value: boxShadowPosition,
						label: 'boxShadowPosition',
						title: __( 'Position', 'cartflows' ),
					} }
					popup={ true }
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
			</UAGAdvancedPanelBody>
		);
	};

	const ButtonTextSettings = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Buttons Text', 'cartflows' ) }
				initialOpen={ false }
			>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'cartflows' ) }
					colorValue={ buttonTextColor ? buttonTextColor : '' }
					data={ {
						value: buttonTextColor,
						label: 'buttonTextColor',
					} }
					setAttributes={ setAttributes }
				/>
				<AdvancedPopColorControl
					label={ __( 'Text Hover Color', 'cartflows' ) }
					colorValue={
						buttonTextHoverColor ? buttonTextHoverColor : ''
					}
					data={ {
						value: buttonTextHoverColor,
						label: 'buttonTextHoverColor',
					} }
					setAttributes={ setAttributes }
				/>

				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: buttonLoadGoogleFonts,
						label: 'buttonLoadGoogleFonts',
					} }
					fontFamily={ {
						value: buttonFontFamily,
						label: 'buttonFontFamily',
					} }
					fontWeight={ {
						value: buttonFontWeight,
						label: 'buttonFontWeight',
					} }
					fontStyle={ {
						value: buttonFontStyle,
						label: 'buttonFontStyle',
					} }
					fontSubset={ {
						value: buttonFontSubset,
						label: 'buttonFontSubset',
					} }
					fontSizeType={ {
						value: buttonFontSizeType,
						label: 'buttonFontSizeType',
					} }
					fontSize={ {
						value: buttonFontSize,
						label: 'buttonFontSize',
					} }
					fontSizeMobile={ {
						value: buttonFontSizeMobile,
						label: 'buttonFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: buttonFontSizeTablet,
						label: 'buttonFontSizeTablet',
					} }
					lineHeightType={ {
						value: buttonLineHeightType,
						label: 'buttonLineHeightType',
					} }
					lineHeight={ {
						value: buttonLineHeight,
						label: 'buttonLineHeight',
					} }
					lineHeightMobile={ {
						value: buttonLineHeightMobile,
						label: 'buttonLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: buttonLineHeightTablet,
						label: 'buttonLineHeightTablet',
					} }
					transform={ {
						value: buttonTransform,
						label: 'buttonTransform',
					} }
					letterSpacing={ {
						value: buttonLetterSpacing,
						label: 'buttonLetterSpacing',
					} }
					letterSpacingTablet={ {
						value: buttonLetterSpacingTablet,
						label: 'buttonLetterSpacingTablet',
					} }
					letterSpacingMobile={ {
						value: buttonLetterSpacingMobile,
						label: 'buttonLetterSpacingMobile',
					} }
					letterSpacingType={ {
						value: buttonLetterSpacingType,
						label: 'buttonLetterSpacingType',
					} }
					disableDecoration={ true }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const InputFieldStyles = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Input Field', 'cartflows' ) }
				initialOpen={ false }
			>
				<AdvancedPopColorControl
					label={ __( 'Label Color', 'cartflows' ) }
					colorValue={ fieldLabelColor ? fieldLabelColor : '' }
					data={ {
						value: fieldLabelColor,
						label: 'fieldLabelColor',
					} }
					setAttributes={ setAttributes }
				/>
				<AdvancedPopColorControl
					label={ __( 'Field Background Color', 'cartflows' ) }
					colorValue={ fieldBgColor ? fieldBgColor : '' }
					data={ {
						value: fieldBgColor,
						label: 'fieldBgColor',
					} }
					setAttributes={ setAttributes }
				/>
				<AdvancedPopColorControl
					label={ __( 'Field Text Color', 'cartflows' ) }
					colorValue={ fieldInputColor ? fieldInputColor : '' }
					data={ {
						value: fieldInputColor,
						label: 'fieldInputColor',
					} }
					setAttributes={ setAttributes }
				/>
				<Border
					setAttributes={ setAttributes }
					deviceType={ props.deviceType }
					prefix={ 'field' }
					attributes={ attributes }
				/>

				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: inputLoadGoogleFonts,
						label: 'inputLoadGoogleFonts',
					} }
					fontFamily={ {
						value: inputFontFamily,
						label: 'inputFontFamily',
					} }
					fontWeight={ {
						value: inputFontWeight,
						label: 'inputFontWeight',
					} }
					fontStyle={ {
						value: inputFontStyle,
						label: 'inputFontStyle',
					} }
					fontSubset={ {
						value: inputFontSubset,
						label: 'inputFontSubset',
					} }
					fontSizeType={ {
						value: inputFontSizeType,
						label: 'inputFontSizeType',
					} }
					fontSize={ {
						value: inputFontSize,
						label: 'inputFontSize',
					} }
					fontSizeMobile={ {
						value: inputFontSizeMobile,
						label: 'inputFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: inputFontSizeTablet,
						label: 'inputFontSizeTablet',
					} }
					lineHeightType={ {
						value: inputLineHeightType,
						label: 'inputLineHeightType',
					} }
					lineHeight={ {
						value: inputLineHeight,
						label: 'inputLineHeight',
					} }
					lineHeightMobile={ {
						value: inputLineHeightMobile,
						label: 'inputLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: inputLineHeightTablet,
						label: 'inputLineHeightTablet',
					} }
					transform={ {
						value: inputTransform,
						label: 'inputTransform',
					} }
					letterSpacing={ {
						value: inputLetterSpacing,
						label: 'inputLetterSpacing',
					} }
					letterSpacingTablet={ {
						value: inputLetterSpacingTablet,
						label: 'inputLetterSpacingTablet',
					} }
					letterSpacingMobile={ {
						value: inputLetterSpacingMobile,
						label: 'inputLetterSpacingMobile',
					} }
					letterSpacingType={ {
						value: inputLetterSpacingType,
						label: 'inputLetterSpacingType',
					} }
					disableDecoration={ true }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const layoutSetting = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Layout', 'cartflows' ) }
				initialOpen={ false }
				className="uagb__url-panel-body"
			>
				<UAGSelectControl
					label={ __( 'Select Layout', 'cartflows' ) }
					value={ layout }
					onChange={ ( value ) => setAttributes( { layout: value } ) }
					options={ Options }
					data={ {
						value: layout,
						label: 'layout',
					} }
				/>
				{ ( 'two-step' === layout ||
					'multistep-checkout' === layout ) &&
					cf_blocks_info.is_cartflows_pro_install === '' && (
						<p className="components-base-control__help">
							{ __(
								'Note: This feature is available in the CartFlows higher plan. Upgrade Now!.',
								'cartflows'
							) }
						</p>
					) }
			</UAGAdvancedPanelBody>
		);
	};
	const InputSkin = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Input Field Skin', 'cartflows' ) }
				initialOpen={ false }
				className="uagb__url-panel-body"
			>
				<UAGSelectControl
					setAttributes={ setAttributes }
					label={ __( 'Style', 'cartflows' ) }
					value={ inputSkins }
					onChange={ ( value ) =>
						setAttributes( { inputSkins: value } )
					}
					options={ skinOption }
					data={ {
						value: inputSkins,
						label: 'inputSkins',
					} }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const GlobalStyles = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Global', 'cartflows' ) }
				initialOpen={ false }
				className="uagb__url-panel-body"
			>
				<AdvancedPopColorControl
					label={ __( 'Primary Color', 'cartflows' ) }
					colorValue={ globalbgColor }
					data={ {
						value: globalbgColor,
						label: 'globalbgColor',
					} }
					setAttributes={ setAttributes }
				/>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'cartflows' ) }
					colorValue={ globaltextColor }
					data={ {
						value: globaltextColor,
						label: 'globaltextColor',
					} }
					setAttributes={ setAttributes }
				/>
				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: globalLoadGoogleFonts,
						label: 'globalLoadGoogleFonts',
					} }
					fontFamily={ {
						value: globalFontFamily,
						label: 'globalFontFamily',
					} }
					fontWeight={ {
						value: globalFontWeight,
						label: 'globalFontWeight',
					} }
					fontStyle={ {
						value: globalFontStyle,
						label: 'globalFontStyle',
					} }
					fontSubset={ {
						value: globalFontSubset,
						label: 'globalFontSubset',
					} }
					fontSizeType={ {
						value: globalFontSizeType,
						label: 'globalFontSizeType',
					} }
					fontSize={ {
						value: globalFontSize,
						label: 'globalFontSize',
					} }
					fontSizeMobile={ {
						value: globalFontSizeMobile,
						label: 'globalFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: globalFontSizeTablet,
						label: 'globalFontSizeTablet',
					} }
					lineHeightType={ {
						value: globalLineHeightType,
						label: 'globalLineHeightType',
					} }
					lineHeight={ {
						value: globalLineHeight,
						label: 'globalLineHeight',
					} }
					lineHeightMobile={ {
						value: globalLineHeightMobile,
						label: 'globalLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: globalLineHeightTablet,
						label: 'globalLineHeightTablet',
					} }
					transform={ {
						value: globalTransform,
						label: 'globalTransform',
					} }
					letterSpacing={ {
						value: globalLetterSpacing,
						label: 'globalLetterSpacing',
					} }
					letterSpacingTablet={ {
						value: globalLetterSpacingTablet,
						label: 'globalLetterSpacingTablet',
					} }
					letterSpacingMobile={ {
						value: globalLetterSpacingMobile,
						label: 'globalLetterSpacingMobile',
					} }
					letterSpacingType={ {
						value: globalLetterSpacingType,
						label: 'globalLetterSpacingType',
					} }
					disableDecoration={ true }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const HeadingStyles = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Heading', 'cartflows' ) }
				initialOpen={ false }
				className="uagb__url-panel-body"
			>
				<AdvancedPopColorControl
					label={ __( 'Heading Color', 'cartflows' ) }
					colorValue={ headBgColor }
					data={ {
						value: headBgColor,
						label: 'headBgColor',
					} }
					setAttributes={ setAttributes }
				/>

				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: headLoadGoogleFonts,
						label: 'headLoadGoogleFonts',
					} }
					fontFamily={ {
						value: headFontFamily,
						label: 'headFontFamily',
					} }
					fontWeight={ {
						value: headFontWeight,
						label: 'headFontWeight',
					} }
					fontStyle={ {
						value: headFontStyle,
						label: 'headFontStyle',
					} }
					fontSubset={ {
						value: headFontSubset,
						label: 'headFontSubset',
					} }
					fontSizeType={ {
						value: headFontSizeType,
						label: 'headFontSizeType',
					} }
					fontSize={ {
						value: headFontSize,
						label: 'headFontSize',
					} }
					fontSizeMobile={ {
						value: headFontSizeMobile,
						label: 'headFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: headFontSizeTablet,
						label: 'headFontSizeTablet',
					} }
					lineHeightType={ {
						value: headLineHeightType,
						label: 'headLineHeightType',
					} }
					lineHeight={ {
						value: headLineHeight,
						label: 'headLineHeight',
					} }
					lineHeightMobile={ {
						value: headLineHeightMobile,
						label: 'headLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: headLineHeightTablet,
						label: 'headLineHeightTablet',
					} }
					transform={ {
						value: headTransform,
						label: 'headTransform',
					} }
					letterSpacing={ {
						value: headLetterSpacing,
						label: 'headLetterSpacing',
					} }
					letterSpacingTablet={ {
						value: headLetterSpacingTablet,
						label: 'headLetterSpacingTablet',
					} }
					letterSpacingMobile={ {
						value: headLetterSpacingMobile,
						label: 'headLetterSpacingMobile',
					} }
					letterSpacingType={ {
						value: headLetterSpacingType,
						label: 'headLetterSpacingType',
					} }
					disableDecoration={ true }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const PaymentSection = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Payment Section', 'cartflows' ) }
				initialOpen={ false }
				className="uagb__url-panel-body"
			>
				<AdvancedPopColorControl
					label={ __( 'Title Color', 'cartflows' ) }
					colorValue={ paymenttitleColor }
					data={ {
						value: paymenttitleColor,
						label: 'paymenttitleColor',
					} }
					setAttributes={ setAttributes }
				/>
				<AdvancedPopColorControl
					label={ __( 'Title Background Color', 'cartflows' ) }
					colorValue={ sectionbgColor }
					data={ {
						value: sectionbgColor,
						label: 'sectionbgColor',
					} }
					setAttributes={ setAttributes }
				/>
				<AdvancedPopColorControl
					label={ __( 'Description Color', 'cartflows' ) }
					colorValue={ paymentdescriptionColor }
					data={ {
						value: paymentdescriptionColor,
						label: 'paymentdescriptionColor',
					} }
					setAttributes={ setAttributes }
				/>
				<AdvancedPopColorControl
					label={ __( 'Desc Background Color', 'cartflows' ) }
					colorValue={ informationbgColor }
					data={ {
						value: informationbgColor,
						label: 'informationbgColor',
					} }
					setAttributes={ setAttributes }
				/>

				<Range
					label={ __( 'Border Radius', 'cartflows' ) }
					value={ sectionBorderRadius }
					onChange={ ( value ) =>
						setAttributes( { sectionBorderRadius: value } )
					}
					data={ {
						value: sectionBorderRadius,
						label: 'sectionBorderRadius',
					} }
					min={ 0 }
					max={ 100 }
					allowReset
					initialPosition={ 0 }
					setAttributes={ setAttributes }
				/>
				<SpacingControl
					{ ...props }
					label={ __( 'Padding', 'cartflows' ) }
					valueTop={ {
						value: paymentSectionpaddingTop,
						label: 'paymentSectionpaddingTop',
					} }
					valueRight={ {
						value: paymentSectionpaddingRight,
						label: 'paymentSectionpaddingRight',
					} }
					valueBottom={ {
						value: paymentSectionpaddingBottom,
						label: 'paymentSectionpaddingBottom',
					} }
					valueLeft={ {
						value: paymentSectionpaddingLeft,
						label: 'paymentSectionpaddingLeft',
					} }
					valueTopTablet={ {
						value: paymentSectionpaddingTopTablet,
						label: 'paymentSectionpaddingTopTablet',
					} }
					valueRightTablet={ {
						value: paymentSectionpaddingRightTablet,
						label: 'paymentSectionpaddingRightTablet',
					} }
					valueBottomTablet={ {
						value: paymentSectionpaddingBottomTablet,
						label: 'paymentSectionpaddingBottomTablet',
					} }
					valueLeftTablet={ {
						value: paymentSectionpaddingLeftTablet,
						label: 'paymentSectionpaddingLeftTablet',
					} }
					valueTopMobile={ {
						value: paymentSectionpaddingTopMobile,
						label: 'paymentSectionpaddingTopMobile',
					} }
					valueRightMobile={ {
						value: paymentSectionpaddingRightMobile,
						label: 'paymentSectionpaddingRightMobile',
					} }
					valueBottomMobile={ {
						value: paymentSectionpaddingBottomMobile,
						label: 'paymentSectionpaddingBottomMobile',
					} }
					valueLeftMobile={ {
						value: paymentSectionpaddingLeftMobile,
						label: 'paymentSectionpaddingLeftMobile',
					} }
					unit={ {
						value: paymentSectionpaddingTypeDesktop,
						label: 'paymentSectionpaddingTypeDesktop',
					} }
					mUnit={ {
						value: paymentSectionpaddingTypeTablet,
						label: 'paymentSectionpaddingTypeTablet',
					} }
					tUnit={ {
						value: paymentSectionpaddingTypeMobile,
						label: 'paymentSectionpaddingTypeMobile',
					} }
					link={ {
						value: spacingLink,
						label: 'spacingLink',
					} }
					attributes={ props.attributes }
					setAttributes={ setAttributes }
					deviceType={ props.deviceType }
				/>
				<SpacingControl
					{ ...props }
					label={ __( 'Margin', 'cartflows' ) }
					valueTop={ {
						value: paymentSectionMarginTop,
						label: 'paymentSectionMarginTop',
					} }
					valueRight={ {
						value: paymentSectionMarginRight,
						label: 'paymentSectionMarginRight',
					} }
					valueBottom={ {
						value: paymentSectionMarginBottom,
						label: 'paymentSectionMarginBottom',
					} }
					valueLeft={ {
						value: paymentSectionMarginLeft,
						label: 'paymentSectionMarginLeft',
					} }
					valueTopTablet={ {
						value: paymentSectionMarginTopTablet,
						label: 'paymentSectionMarginTopTablet',
					} }
					valueRightTablet={ {
						value: paymentSectionMarginRightTablet,
						label: 'paymentSectionMarginRightTablet',
					} }
					valueBottomTablet={ {
						value: paymentSectionMarginBottomTablet,
						label: 'paymentSectionMarginBottomTablet',
					} }
					valueLeftTablet={ {
						value: paymentSectionMarginLeftTablet,
						label: 'paymentSectionMarginLeftTablet',
					} }
					valueTopMobile={ {
						value: paymentSectionMarginTopMobile,
						label: 'paymentSectionMarginTopMobile',
					} }
					valueRightMobile={ {
						value: paymentSectionMarginRightMobile,
						label: 'paymentSectionMarginRightMobile',
					} }
					valueBottomMobile={ {
						value: paymentSectionMarginBottomMobile,
						label: 'paymentSectionMarginBottomMobile',
					} }
					valueLeftMobile={ {
						value: paymentSectionMarginLeftMobile,
						label: 'paymentSectionMarginLeftMobile',
					} }
					unit={ {
						value: paymentSectionMarginTypeDesktop,
						label: 'paymentSectionMarginTypeDesktop',
					} }
					mUnit={ {
						value: paymentSectionMarginTypeTablet,
						label: 'paymentSectionMarginTypeTablet',
					} }
					tUnit={ {
						value: paymentSectionMarginTypeMobile,
						label: 'paymentSectionMarginTypeMobile',
					} }
					link={ {
						value: spacingLink,
						label: 'spacingLink',
					} }
					attributes={ props.attributes }
					setAttributes={ setAttributes }
					deviceType={ props.deviceType }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const ErrorMessages = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Success/Error Message', 'cartflows' ) }
				initialOpen={ false }
				className="uagb__url-panel-body"
			>
				<p className="components-base-control__help">
					{ __(
						'Note: This styling can be only seen at frontend',
						'cartflows'
					) }
				</p>
				<AdvancedPopColorControl
					label={ __( 'Message Color', 'cartflows' ) }
					colorValue={ errorMsgColor }
					data={ {
						value: errorMsgColor,
						label: 'errorMsgColor',
					} }
					setAttributes={ setAttributes }
				/>
				<AdvancedPopColorControl
					label={ __( 'Background Color', 'cartflows' ) }
					colorValue={ errorMsgBgColor }
					data={ {
						value: errorMsgBgColor,
						label: 'errorMsgBgColor',
					} }
					setAttributes={ setAttributes }
				/>
				<AdvancedPopColorControl
					label={ __( 'Border Color', 'cartflows' ) }
					colorValue={ errorMsgBorderColor }
					data={ {
						value: errorMsgBorderColor,
						label: 'errorMsgBorderColor',
					} }
					setAttributes={ setAttributes }
				/>
			</UAGAdvancedPanelBody>
		);
	};
	const InputFieldValidation = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Input Field Validation', 'cartflows' ) }
				initialOpen={ false }
				className="uagb__url-panel-body"
			>
				<p className="components-base-control__help">
					{ __(
						'Note: This styling can be only seen at frontend',
						'cartflows'
					) }
				</p>
				<AdvancedPopColorControl
					label={ __( 'Label Color', 'cartflows' ) }
					colorValue={ errorLabelColor }
					data={ {
						value: errorLabelColor,
						label: 'errorLabelColor',
					} }
					setAttributes={ setAttributes }
				/>
				<AdvancedPopColorControl
					label={ __( 'Border Color', 'cartflows' ) }
					colorValue={ errorFieldBorderColor }
					data={ {
						value: errorFieldBorderColor,
						label: 'errorFieldBorderColor',
					} }
					setAttributes={ setAttributes }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const OrderReview = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Order Review', 'cartflows' ) }
				initialOpen={ false }
				className="uagb__url-panel-body"
			>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'cartflows' ) }
					colorValue={ orderReviewColumnTextColor }
					data={ {
						value: orderReviewColumnTextColor,
						label: 'orderReviewColumnTextColor',
					} }
					setAttributes={ setAttributes }
				/>
				<AdvancedPopColorControl
					label={ __( 'Background Color', 'cartflows' ) }
					colorValue={ orderReviewColumnColor }
					data={ {
						value: orderReviewColumnColor,
						label: 'orderReviewColumnColor',
					} }
					setAttributes={ setAttributes }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const proSettings = () => {
		return wp.hooks.applyFilters(
			'wcf-checkout-pro-general-settings',
			props
		);
	};

	const proStyles = () => {
		return wp.hooks.applyFilters(
			'wcf-checkout-pro-style-settings',
			props
		);
	};

	return (
		<>
			<InspectorControls>
				<InspectorTabs>
					<InspectorTab { ...UAGTabs.general }>
						{ layoutSetting() }
						{ InputSkin() }
						{ cf_blocks_info.is_cartflows_pro_install &&
							proSettings() }
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }>
						{ GlobalStyles() }
						{ HeadingStyles() }
						{ InputFieldStyles() }
						{ InputFieldValidation() }
						{ ButtonStyles() }
						{ ButtonTextSettings() }
						{ 'modern-checkout' === layout && OrderReview() }
						{ PaymentSection() }
						{ ErrorMessages() }
						{ cf_blocks_info.is_cartflows_pro_install &&
							proStyles() }
					</InspectorTab>
					<InspectorTab
						{ ...UAGTabs.advance }
						parentProps={ props }
					></InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			{ loadglobalGoogleFonts }
			{ loadButtonGoogleFonts }
			{ loadheadGoogleFonts }
			{ loadinputLoadGoogleFonts }
		</>
	);
};

export default React.memo( Settings );
