import React from 'react';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import Border from '@Components/responsive-border';
import UAGSelectControl from '@Components/select-control';
import TypographyControl from '@Components/typography';
import WebfontLoader from '@Components/typography/fontloader';

import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';

import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import BoxShadowControl from '@Components/box-shadow';

const Settings = ( props ) => {
	props = props.parentProps;

	const {
		attributes,
		setAttributes,
		attributes: {
			input_skins,
			block_id,
			// General
			generalPrimaryColor,
			generalLoadGoogleFonts,
			generalFontFamily,
			generalFontWeight,
			generalFontSubset,
			generalFontSizeType,
			generalLineHeightType,
			generalFontSize,
			generalFontSizeTablet,
			generalFontSizeMobile,
			generalLineHeight,
			generalLineHeightTablet,
			generalLineHeightMobile,
			generalTransform,
			generalLetterSpacing,
			generalLetterSpacingTablet,
			generalLetterSpacingMobile,
			generalLetterSpacingType,
			// Input Fields
			inputFieldLoadGoogleFonts,
			inputFieldFontFamily,
			inputFieldFontWeight,
			inputFieldFontSubset,
			inputFieldFontSizeType,
			inputFieldLineHeightType,
			inputFieldFontSize,
			inputFieldFontSizeTablet,
			inputFieldFontSizeMobile,
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
			submitButtonLoadGoogleFonts,
			submitButtonFontFamily,
			submitButtonFontWeight,
			submitButtonFontSubset,
			submitButtonFontSize,
			submitButtonFontSizeType,
			submitButtonFontSizeTablet,
			submitButtonFontSizeMobile,
			submitButtonLineHeightType,
			submitButtonLineHeight,
			submitButtonLineHeightTablet,
			submitButtonLineHeightMobile,
			submitButtonTextColor,
			submitButtonTransform,
			submitButtonLetterSpacing,
			submitButtonLetterSpacingTablet,
			submitButtonLetterSpacingMobile,
			submitButtonLetterSpacingType,

			submitButtonBackgroundColor,
			submitButtonTextHoverColor,
			submitButtonBackgroundHoverColor,

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
			generalFontStyle,
			submitButtonFontStyle,
			inputFieldFontStyle,
		},
	} = props;

	let loadGeneralGoogleFonts;
	let loadInputFieldGoogleFonts;
	let loadSubmitButtonGoogleFonts;

	if ( true === generalLoadGoogleFonts ) {
		const gconfig = {
			google: {
				families: [
					generalFontFamily +
						( generalFontWeight ? ':' + generalFontWeight : '' ),
				],
			},
		};
		loadGeneralGoogleFonts = (
			<WebfontLoader config={ gconfig }></WebfontLoader>
		);
	}

	if ( true === inputFieldLoadGoogleFonts ) {
		const ifconfig = {
			google: {
				families: [
					inputFieldFontFamily +
						( inputFieldFontWeight
							? ':' + inputFieldFontWeight
							: '' ),
				],
			},
		};
		loadInputFieldGoogleFonts = (
			<WebfontLoader config={ ifconfig }></WebfontLoader>
		);
	}

	if ( true === submitButtonLoadGoogleFonts ) {
		const sbfconfig = {
			google: {
				families: [
					submitButtonFontFamily +
						( submitButtonFontWeight
							? ':' + submitButtonFontWeight
							: '' ),
				],
			},
		};
		loadSubmitButtonGoogleFonts = (
			<WebfontLoader config={ sbfconfig }></WebfontLoader>
		);
	}

	const skinOption = [
		{ value: 'deafult', label: __( 'Default', 'cartflows' ) },
		{
			value: 'floating-labels',
			label: __( 'Floating Label', 'cartflows' ),
		},
	];

	const ButtonSettings = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Button', 'cartflows' ) }
				initialOpen={ false }
			>
				<AdvancedPopColorControl
					label={ __( 'Background Color', 'cartflows' ) }
					colorValue={
						submitButtonBackgroundColor
							? submitButtonBackgroundColor
							: ''
					}
					data={ {
						value: submitButtonBackgroundColor,
						label: 'submitButtonBackgroundColor',
					} }
					setAttributes={ setAttributes }
				/>

				<AdvancedPopColorControl
					label={ __( 'Background Hover Color', 'cartflows' ) }
					colorValue={
						submitButtonBackgroundHoverColor
							? submitButtonBackgroundHoverColor
							: ''
					}
					data={ {
						value: submitButtonBackgroundHoverColor,
						label: 'submitButtonBackgroundHoverColor',
					} }
					setAttributes={ setAttributes }
				/>

				<Border
					setAttributes={ setAttributes }
					deviceType={ props.deviceType }
					prefix={ 'submitButton' }
					attributes={ attributes }
				/>
				<BoxShadowControl
					blockId={ block_id }
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
			</UAGAdvancedPanelBody>
		);
	};

	const ButtonTextSettings = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Button Text', 'cartflows' ) }
				initialOpen={ false }
			>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'cartflows' ) }
					colorValue={
						submitButtonTextColor ? submitButtonTextColor : ''
					}
					data={ {
						value: submitButtonTextColor,
						label: 'submitButtonTextColor',
					} }
					setAttributes={ setAttributes }
				/>
				<AdvancedPopColorControl
					label={ __( 'Text Hover Color', 'cartflows' ) }
					colorValue={
						submitButtonTextHoverColor
							? submitButtonTextHoverColor
							: ''
					}
					data={ {
						value: submitButtonTextHoverColor,
						label: 'submitButtonTextHoverColor',
					} }
					setAttributes={ setAttributes }
				/>

				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: submitButtonLoadGoogleFonts,
						label: 'submitButtonLoadGoogleFonts',
					} }
					fontFamily={ {
						value: submitButtonFontFamily,
						label: 'submitButtonFontFamily',
					} }
					fontWeight={ {
						value: submitButtonFontWeight,
						label: 'submitButtonFontWeight',
					} }
					fontStyle={ {
						value: submitButtonFontStyle,
						label: 'submitButtonFontStyle',
					} }
					fontSubset={ {
						value: submitButtonFontSubset,
						label: 'submitButtonFontSubset',
					} }
					fontSizeType={ {
						value: submitButtonFontSizeType,
						label: 'submitButtonFontSizeType',
					} }
					fontSize={ {
						value: submitButtonFontSize,
						label: 'submitButtonFontSize',
					} }
					fontSizeMobile={ {
						value: submitButtonFontSizeMobile,
						label: 'submitButtonFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: submitButtonFontSizeTablet,
						label: 'submitButtonFontSizeTablet',
					} }
					lineHeightType={ {
						value: submitButtonLineHeightType,
						label: 'submitButtonLineHeightType',
					} }
					lineHeight={ {
						value: submitButtonLineHeight,
						label: 'submitButtonLineHeight',
					} }
					lineHeightMobile={ {
						value: submitButtonLineHeightMobile,
						label: 'submitButtonLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: submitButtonLineHeightTablet,
						label: 'submitButtonLineHeightTablet',
					} }
					transform={ {
						value: submitButtonTransform,
						label: 'submitButtonTransform',
					} }
					letterSpacing={ {
						value: submitButtonLetterSpacing,
						label: 'submitButtonLetterSpacing',
					} }
					letterSpacingTablet={ {
						value: submitButtonLetterSpacingTablet,
						label: 'submitButtonLetterSpacingTablet',
					} }
					letterSpacingMobile={ {
						value: submitButtonLetterSpacingMobile,
						label: 'submitButtonLetterSpacingMobile',
					} }
					letterSpacingType={ {
						value: submitButtonLetterSpacingType,
						label: 'submitButtonLetterSpacingType',
					} }
					disableDecoration={ true }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const InputFieldSettings = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Input Field', 'cartflows' ) }
				initialOpen={ false }
			>
				<AdvancedPopColorControl
					label={ __( 'Label Color', 'cartflows' ) }
					colorValue={
						inputFieldLabelColor ? inputFieldLabelColor : ''
					}
					data={ {
						value: inputFieldLabelColor,
						label: 'inputFieldLabelColor',
					} }
					setAttributes={ setAttributes }
				/>
				<AdvancedPopColorControl
					label={ __( 'Field Background Color', 'cartflows' ) }
					colorValue={
						inputFieldBackgroundColor
							? inputFieldBackgroundColor
							: ''
					}
					data={ {
						value: inputFieldBackgroundColor,
						label: 'inputFieldBackgroundColor',
					} }
					setAttributes={ setAttributes }
				/>
				<AdvancedPopColorControl
					label={ __( 'Field Text Color', 'cartflows' ) }
					colorValue={
						inputFieldTextPlaceholderColor
							? inputFieldTextPlaceholderColor
							: ''
					}
					data={ {
						value: inputFieldTextPlaceholderColor,
						label: 'inputFieldTextPlaceholderColor',
					} }
					setAttributes={ setAttributes }
				/>
				<Border
					setAttributes={ setAttributes }
					deviceType={ props.deviceType }
					prefix={ 'inputField' }
					attributes={ attributes }
				/>

				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: inputFieldLoadGoogleFonts,
						label: 'inputFieldLoadGoogleFonts',
					} }
					fontFamily={ {
						value: inputFieldFontFamily,
						label: 'inputFieldFontFamily',
					} }
					fontWeight={ {
						value: inputFieldFontWeight,
						label: 'inputFieldFontWeight',
					} }
					fontStyle={ {
						value: inputFieldFontStyle,
						label: 'inputFieldFontStyle',
					} }
					fontSubset={ {
						value: inputFieldFontSubset,
						label: 'inputFieldFontSubset',
					} }
					fontSizeType={ {
						value: inputFieldFontSizeType,
						label: 'inputFieldFontSizeType',
					} }
					fontSize={ {
						value: inputFieldFontSize,
						label: 'inputFieldFontSize',
					} }
					fontSizeMobile={ {
						value: inputFieldFontSizeMobile,
						label: 'inputFieldFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: inputFieldFontSizeTablet,
						label: 'inputFieldFontSizeTablet',
					} }
					lineHeightType={ {
						value: inputFieldLineHeightType,
						label: 'inputFieldLineHeightType',
					} }
					lineHeight={ {
						value: inputFieldLineHeight,
						label: 'inputFieldLineHeight',
					} }
					lineHeightMobile={ {
						value: inputFieldLineHeightMobile,
						label: 'inputFieldLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: inputFieldLineHeightTablet,
						label: 'inputFieldLineHeightTablet',
					} }
					transform={ {
						value: inputFieldTransform,
						label: 'inputFieldTransform',
					} }
					letterSpacing={ {
						value: inputFieldLetterSpacing,
						label: 'inputFieldLetterSpacing',
					} }
					letterSpacingTablet={ {
						value: inputFieldLetterSpacingTablet,
						label: 'inputFieldLetterSpacingTablet',
					} }
					letterSpacingMobile={ {
						value: inputFieldLetterSpacingMobile,
						label: 'inputFieldLetterSpacingMobile',
					} }
					letterSpacingType={ {
						value: inputFieldLetterSpacingType,
						label: 'inputFieldLetterSpacingType',
					} }
					disableDecoration={ true }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const InputSkin = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Input Skin', 'cartflows' ) }
				initialOpen={ false }
				className="uagb__url-panel-body"
			>
				<UAGSelectControl
					label={ __( 'Skin', 'cartflows' ) }
					value={ input_skins }
					onChange={ ( value ) =>
						setAttributes( { input_skins: value } )
					}
					options={ skinOption }
					data={ {
						value: input_skins,
						label: 'input_skins',
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
					colorValue={ generalPrimaryColor }
					data={ {
						value: generalPrimaryColor,
						label: 'generalPrimaryColor',
					} }
					setAttributes={ setAttributes }
				/>
				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: generalLoadGoogleFonts,
						label: 'generalLoadGoogleFonts',
					} }
					fontFamily={ {
						value: generalFontFamily,
						label: 'generalFontFamily',
					} }
					fontWeight={ {
						value: generalFontWeight,
						label: 'generalFontWeight',
					} }
					fontStyle={ {
						value: generalFontStyle,
						label: 'generalFontStyle',
					} }
					fontSubset={ {
						value: generalFontSubset,
						label: 'generalFontSubset',
					} }
					fontSizeType={ {
						value: generalFontSizeType,
						label: 'generalFontSizeType',
					} }
					fontSize={ {
						value: generalFontSize,
						label: 'generalFontSize',
					} }
					fontSizeMobile={ {
						value: generalFontSizeMobile,
						label: 'generalFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: generalFontSizeTablet,
						label: 'generalFontSizeTablet',
					} }
					lineHeightType={ {
						value: generalLineHeightType,
						label: 'generalLineHeightType',
					} }
					lineHeight={ {
						value: generalLineHeight,
						label: 'generalLineHeight',
					} }
					lineHeightMobile={ {
						value: generalLineHeightMobile,
						label: 'generalLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: generalLineHeightTablet,
						label: 'generalLineHeightTablet',
					} }
					transform={ {
						value: generalTransform,
						label: 'generalTransform',
					} }
					letterSpacing={ {
						value: generalLetterSpacing,
						label: 'generalLetterSpacing',
					} }
					letterSpacingTablet={ {
						value: generalLetterSpacingTablet,
						label: 'generalLetterSpacingTablet',
					} }
					letterSpacingMobile={ {
						value: generalLetterSpacingMobile,
						label: 'generalLetterSpacingMobile',
					} }
					letterSpacingType={ {
						value: generalLetterSpacingType,
						label: 'generalLetterSpacingType',
					} }
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
						{ InputSkin() }
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }>
						{ GlobalStyles() }
						{ InputFieldSettings() }
						{ ButtonSettings() }
						{ ButtonTextSettings() }
					</InspectorTab>
					<InspectorTab
						{ ...UAGTabs.advance }
						parentProps={ props }
					></InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			{ loadGeneralGoogleFonts }
			{ loadInputFieldGoogleFonts }
			{ loadSubmitButtonGoogleFonts }
		</>
	);
};

export default React.memo( Settings );
