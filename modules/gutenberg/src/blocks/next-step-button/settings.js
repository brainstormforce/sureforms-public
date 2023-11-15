import React from 'react';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import Background from '@Components/background';
import Border from '@Components/responsive-border';
import SpacingControl from '@Components/spacing-control';
import { Icon } from '@wordpress/components';
import UAGSelectControl from '@Components/select-control';
import TypographyControl from '@Components/typography';
import WebfontLoader from '@Components/typography/fontloader';
import MultiButtonsControl from '@Components/multi-buttons-control';
import renderSVG from '@Controls/renderIcon';
import Range from '@Components/range/Range.js';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';

import UAGTabsControl from '@Components/tabs';

import UAGIconPicker from '@Components/icon-picker';

import UAGAdvancedPanelBody from '@Components/advanced-panel-body';

const Settings = ( props ) => {
	props = props.parentProps;

	const {
		attributes,
		setAttributes,
		attributes: {
			// Icon
			icon,
			iconColor,
			iconHoverColor,
			iconSize,
			iconPosition,
			iconSpacing,
			align,
			malign,
			talign,
			titletextTransform,
			subtitletextTransform,
			titleletterSpacing,
			titleLetterSpacingTablet,
			titleLetterSpacingMobile,
			titleLetterSpacingType,
			subtitleletterSpacing,
			subtitleLetterSpacingTablet,
			subtitleLetterSpacingMobile,
			subtitleLetterSpacingType,
			// Alignment
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
			// Text Color
			textColor,
			textHoverColor,
			buttonHoverColor,
			// Title
			titleFontFamily,
			titleFontWeight,
			titleFontSubset,
			titleFontSize,
			titleFontSizeType,
			titleFontSizeMobile,
			titleFontSizeTablet,
			titleLineHeight,
			titleLineHeightType,
			titleLineHeightMobile,
			titleLineHeightTablet,
			titleLoadGoogleFonts,
			// Sub Title
			subTitleFontFamily,
			subTitleFontWeight,
			subTitleFontSubset,
			subTitleFontSize,
			subTitleFontSizeType,
			subTitleFontSizeMobile,
			subTitleFontSizeTablet,
			subTitleLineHeight,
			subTitleLineHeightType,
			subTitleLineHeightMobile,
			subTitleLineHeightTablet,
			subTitleLoadGoogleFonts,
			// Title Bottom Margin
			titleBottomSpacing,
			backgroundType,
			backgroundImage,
			backgroundColor,
			backgroundPosition,
			backgroundAttachment,
			backgroundRepeat,
			backgroundSize,
			backgroundOpacity,
			gradientValue,
			//New attrs.
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
			spacingLink,

			titleFontStyle,
			subTitleFontStyle,
		},
	} = props;

	let loadTitleGoogleFonts;
	let loadSubTitleGoogleFonts;

	if ( titleLoadGoogleFonts === true ) {
		const titleconfig = {
			google: {
				families: [
					titleFontFamily +
						( titleFontWeight ? ':' + titleFontWeight : '' ),
				],
			},
		};

		loadTitleGoogleFonts = (
			<WebfontLoader config={ titleconfig }></WebfontLoader>
		);
	}

	if ( subTitleLoadGoogleFonts === true ) {
		const subtitleconfig = {
			google: {
				families: [
					subTitleFontFamily +
						( subTitleFontWeight ? ':' + subTitleFontWeight : '' ),
				],
			},
		};

		loadSubTitleGoogleFonts = (
			<WebfontLoader config={ subtitleconfig }></WebfontLoader>
		);
	}

	const backgroundSettings = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Background', 'cartflows' ) }
				initialOpen={ false }
			>
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
						colorValue={ buttonHoverColor ? buttonHoverColor : '' }
						data={ {
							value: buttonHoverColor,
							label: 'buttonHoverColor',
						} }
						setAttributes={ setAttributes }
					/>
				) }
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
					setAttributes={ setAttributes }
					min={ 0 }
					max={ 100 }
					allowReset
					initialPosition={ 0 }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const borderSettings = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Border', 'cartflows' ) }
				initialOpen={ false }
			>
				<Border
					setAttributes={ setAttributes }
					deviceType={ props.deviceType }
					prefix={ 'btn' }
					attributes={ attributes }
					disableBottomSeparator={ true }
					disabledBorderTitle={ true }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const spacingSettings = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Spacing', 'cartflows' ) }
				initialOpen={ false }
			>
				<SpacingControl
					{ ...props }
					label={ __( 'Padding', 'cartflows' ) }
					valueTop={ {
						value: paddingBtnTop,
						label: 'paddingBtnTop',
					} }
					valueRight={ {
						value: paddingBtnRight,
						label: 'paddingBtnRight',
					} }
					valueBottom={ {
						value: paddingBtnBottom,
						label: 'paddingBtnBottom',
					} }
					valueLeft={ {
						value: paddingBtnLeft,
						label: 'paddingBtnLeft',
					} }
					valueTopTablet={ {
						value: paddingBtnTopTablet,
						label: 'paddingBtnTopTablet',
					} }
					valueRightTablet={ {
						value: paddingBtnRightTablet,
						label: 'paddingBtnRightTablet',
					} }
					valueBottomTablet={ {
						value: paddingBtnBottomTablet,
						label: 'paddingBtnBottomTablet',
					} }
					valueLeftTablet={ {
						value: paddingBtnLeftTablet,
						label: 'paddingBtnLeftTablet',
					} }
					valueTopMobile={ {
						value: paddingBtnTopMobile,
						label: 'paddingBtnTopMobile',
					} }
					valueRightMobile={ {
						value: paddingBtnRightMobile,
						label: 'paddingBtnRightMobile',
					} }
					valueBottomMobile={ {
						value: paddingBtnBottomMobile,
						label: 'paddingBtnBottomMobile',
					} }
					valueLeftMobile={ {
						value: paddingBtnLeftMobile,
						label: 'paddingBtnLeftMobile',
					} }
					unit={ {
						value: paddingTypeDesktop,
						label: 'desktopPaddingType',
					} }
					mUnit={ {
						value: paddingTypeTablet,
						label: 'mobilePaddingType',
					} }
					tUnit={ {
						value: paddingTypeMobile,
						label: 'tabletPaddingType',
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

	const titleSettings = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Title', 'cartflows' ) }
				initialOpen={ false }
			>
				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: titleLoadGoogleFonts,
						label: 'titleLoadGoogleFonts',
					} }
					fontFamily={ {
						value: titleFontFamily,
						label: 'titleFontFamily',
					} }
					fontWeight={ {
						value: titleFontWeight,
						label: 'titleFontWeight',
					} }
					fontStyle={ {
						value: titleFontStyle,
						label: 'titleFontStyle',
					} }
					fontSubset={ {
						value: titleFontSubset,
						label: 'titleFontSubset',
					} }
					fontSizeType={ {
						value: titleFontSizeType,
						label: 'titleFontSizeType',
					} }
					fontSize={ {
						value: titleFontSize,
						label: 'titleFontSize',
					} }
					fontSizeMobile={ {
						value: titleFontSizeMobile,
						label: 'titleFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: titleFontSizeTablet,
						label: 'titleFontSizeTablet',
					} }
					lineHeightType={ {
						value: titleLineHeightType,
						label: 'titleLineHeightType',
					} }
					lineHeight={ {
						value: titleLineHeight,
						label: 'titleLineHeight',
					} }
					lineHeightMobile={ {
						value: titleLineHeightMobile,
						label: 'titleLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: titleLineHeightTablet,
						label: 'titleLineHeightTablet',
					} }
					transform={ {
						value: titletextTransform,
						label: 'titletextTransform',
					} }
					letterSpacing={ {
						value: titleletterSpacing,
						label: 'titleletterSpacing',
					} }
					letterSpacingTablet={ {
						value: titleLetterSpacingTablet,
						label: 'titleLetterSpacingTablet',
					} }
					letterSpacingMobile={ {
						value: titleLetterSpacingMobile,
						label: 'titleLetterSpacingMobile',
					} }
					letterSpacingType={ {
						value: titleLetterSpacingType,
						label: 'titleLetterSpacingType',
					} }
					disableDecoration={ true }
				/>
				<Range
					label={ __( 'Title Bottom Spacing', 'cartflows' ) }
					value={ titleBottomSpacing }
					onChange={ ( value ) =>
						setAttributes( { titleBottomSpacing: value } )
					}
					setAttributes={ setAttributes }
					data={ {
						value: titleBottomSpacing,
						label: 'titleBottomSpacing',
					} }
					min={ 0 }
					max={ 500 }
					beforeIcon=""
					allowReset
					initialPosition={ 0 }
					displayUnit={ false }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const subtitleSettings = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'SubTitle', 'cartflows' ) }
				initialOpen={ false }
			>
				<TypographyControl
					label={ __( 'Typography', 'cartflows' ) }
					attributes={ attributes }
					setAttributes={ setAttributes }
					loadGoogleFonts={ {
						value: subTitleLoadGoogleFonts,
						label: 'subTitleLoadGoogleFonts',
					} }
					fontFamily={ {
						value: subTitleFontFamily,
						label: 'subTitleFontFamily',
					} }
					fontWeight={ {
						value: subTitleFontWeight,
						label: 'subTitleFontWeight',
					} }
					fontStyle={ {
						value: subTitleFontStyle,
						label: 'subTitleFontStyle',
					} }
					fontSubset={ {
						value: subTitleFontSubset,
						label: 'subTitleFontSubset',
					} }
					fontSizeType={ {
						value: subTitleFontSizeType,
						label: 'subTitleFontSizeType',
					} }
					fontSize={ {
						value: subTitleFontSize,
						label: 'subTitleFontSize',
					} }
					fontSizeMobile={ {
						value: subTitleFontSizeMobile,
						label: 'subTitleFontSizeMobile',
					} }
					fontSizeTablet={ {
						value: subTitleFontSizeTablet,
						label: 'subTitleFontSizeTablet',
					} }
					lineHeightType={ {
						value: subTitleLineHeightType,
						label: 'subTitleLineHeightType',
					} }
					lineHeight={ {
						value: subTitleLineHeight,
						label: 'subTitleLineHeight',
					} }
					lineHeightMobile={ {
						value: subTitleLineHeightMobile,
						label: 'subTitleLineHeightMobile',
					} }
					lineHeightTablet={ {
						value: subTitleLineHeightTablet,
						label: 'subTitleLineHeightTablet',
					} }
					transform={ {
						value: subtitletextTransform,
						label: 'subtitletextTransform',
					} }
					letterSpacing={ {
						value: subtitleletterSpacing,
						label: 'subtitleletterSpacing',
					} }
					letterSpacingTablet={ {
						value: subtitleLetterSpacingTablet,
						label: 'subtitleLetterSpacingTablet',
					} }
					letterSpacingMobile={ {
						value: subtitleLetterSpacingMobile,
						label: 'subtitleLetterSpacingMobile',
					} }
					letterSpacingType={ {
						value: subtitleLetterSpacingType,
						label: 'subtitleLetterSpacingType',
					} }
					disableDecoration={ true }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const contentSettings = () => {
		const tabOutputNormal = (
			<>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'cartflows' ) }
					colorValue={ textColor ? textColor : '' }
					data={ {
						value: textColor,
						label: 'textColor',
					} }
					setAttributes={ setAttributes }
				/>
			</>
		);
		const tabOutputHover = (
			<>
				<AdvancedPopColorControl
					label={ __( 'Text Hover Color', 'cartflows' ) }
					colorValue={ textHoverColor ? textHoverColor : '' }
					data={ {
						value: textHoverColor,
						label: 'textHoverColor',
					} }
					setAttributes={ setAttributes }
				/>
			</>
		);
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Text', 'cartflows' ) }
				initialOpen={ false }
			>
				<MultiButtonsControl
					setAttributes={ setAttributes }
					label={ __( 'Text Alignment', 'cartflows' ) }
					data={ {
						value: textAlignment,
						label: 'textAlignment',
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
				<UAGTabsControl
					tabs={ [
						{
							name: 'normal',
							title: __( 'Normal', 'cartflows' ),
						},
						{
							name: 'hover',
							title: __( 'Hover', 'cartflows' ),
						},
					] }
					normal={ tabOutputNormal }
					hover={ tabOutputHover }
					disableBottomSeparator={ true }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	const IconSettings = () => {
		const iconNormal = (
			<>
				<AdvancedPopColorControl
					label={ __( 'Icon Color', 'cartflows' ) }
					colorValue={ iconColor ? iconColor : '' }
					data={ {
						value: iconColor,
						label: 'iconColor',
					} }
					setAttributes={ setAttributes }
				/>
			</>
		);
		const iconHover = (
			<>
				<AdvancedPopColorControl
					label={ __( 'Icon Hover Color', 'cartflows' ) }
					colorValue={ iconHoverColor ? iconHoverColor : '' }
					data={ {
						value: iconHoverColor,
						label: 'iconHoverColor',
					} }
					setAttributes={ setAttributes }
				/>
			</>
		);
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Icon', 'cartflows' ) }
				initialOpen={ false }
				className="uagb__url-panel-body"
			>
				<UAGIconPicker
					label={ __( 'Icon', 'cartflows' ) }
					value={ icon }
					onChange={ ( value ) => setAttributes( { icon: value } ) }
				/>
				{ '' !== icon && (
					<>
						<UAGSelectControl
							setAttributes={ setAttributes }
							label={ __( 'Position', 'cartflows' ) }
							onChange={ ( value ) =>
								setAttributes( { iconPosition: value } )
							}
							data={ {
								value: iconPosition,
								label: 'iconPosition',
							} }
							value={ iconPosition }
							options={ [
								{
									value: 'before_title',
									label: __( 'Before Title', 'cartflows' ),
								},
								{
									value: 'after_title',
									label: __( 'After Title', 'cartflows' ),
								},
								{
									value: 'before_title_sub_title',
									label: __(
										'Before Title & Sub Title',
										'cartflows'
									),
								},
								{
									value: 'after_title_sub_title',
									label: __(
										'After Title & Sub Title',
										'cartflows'
									),
								},
							] }
						/>
						<Range
							label={ __(
								'Gap Between Icon And Text',
								'cartflows'
							) }
							setAttributes={ setAttributes }
							value={ iconSpacing }
							data={ {
								value: iconSpacing,
								label: 'iconSpacing',
							} }
							onChange={ ( value ) =>
								setAttributes( {
									iconSpacing: value,
								} )
							}
							min={ 0 }
							max={ 50 }
							displayUnit={ false }
						/>
						<Range
							label={ __( 'Icon Size', 'cartflows' ) }
							setAttributes={ setAttributes }
							value={ iconSize }
							data={ {
								value: iconSize,
								label: 'iconSize',
							} }
							onChange={ ( value ) =>
								setAttributes( {
									iconSize: value,
								} )
							}
							min={ 0 }
							max={ 50 }
							displayUnit={ false }
						/>

						<UAGTabsControl
							tabs={ [
								{
									name: 'normal',
									title: __( 'Normal', 'cartflows' ),
								},
								{
									name: 'hover',
									title: __( 'Hover', 'cartflows' ),
								},
							] }
							normal={ iconNormal }
							hover={ iconHover }
							disableBottomSeparator={ true }
						/>
					</>
				) }
			</UAGAdvancedPanelBody>
		);
	};

	const buttonAlignment = () => {
		return (
			<UAGAdvancedPanelBody
				title={ __( 'Alignment', 'cartflows' ) }
				initialOpen={ false }
			>
				<MultiButtonsControl
					setAttributes={ setAttributes }
					label={ __( 'Alignment', 'cartflows' ) }
					data={ {
						desktop: {
							value: align,
							label: 'align',
						},
						tablet: {
							value: talign,
							label: 'talign',
						},
						mobile: {
							value: malign,
							label: 'malign',
						},
					} }
					responsive={ true }
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
						{
							value: 'full',
							icon: (
								<Icon
									icon={ renderSVG( 'fa fa-align-justify' ) }
								/>
							),
							tooltip: __( 'Full Width', 'cartflows' ),
						},
					] }
					showIcons={ true }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	return (
		<>
			<InspectorControls>
				<InspectorTabs>
					<InspectorTab { ...UAGTabs.general }>
						{ buttonAlignment() }
						{ IconSettings() }
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }>
						{ backgroundSettings() }
						{ borderSettings() }
						{ spacingSettings() }
						{ contentSettings() }
						{ titleSettings() }
						{ subtitleSettings() }
					</InspectorTab>
					<InspectorTab
						{ ...UAGTabs.advance }
						parentProps={ props }
					></InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			{ loadTitleGoogleFonts }
			{ loadSubTitleGoogleFonts }
		</>
	);
};

export default React.memo( Settings );
