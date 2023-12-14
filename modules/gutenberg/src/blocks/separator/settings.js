import { memo } from '@wordpress/element';
import TypographyControl from '@Components/typography';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import MultiButtonsControl from '@Components/multi-buttons-control';
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import renderSVG from '@Controls/renderIcon';
import { Icon } from '@wordpress/components';
import UAGTextControl from '@Components/text-control';
import ResponsiveSlider from '@Components/responsive-slider';
import UAGSelectControl from '@Components/select-control';
import UAGIconPicker from '@Components/icon-picker';
// Extend component
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';

const Settings = ( props ) => {
	const { attributes, setAttributes } = props;
	const {
		separatorStyle,
		separatorAlign,
		separatorAlignTablet,
		separatorAlignMobile,
		separatorWidth,
		separatorWidthTablet,
		separatorWidthMobile,
		separatorWidthType,
		separatorColor,
		separatorBorderHeight,
		separatorBorderHeightMobile,
		separatorBorderHeightTablet,
		separatorBorderHeightUnit,
		separatorSize,
		separatorSizeMobile,
		separatorSizeTablet,
		separatorSizeType,
		separatorHeight,
		separatorHeightMobile,
		separatorHeightTablet,
		separatorHeightType,
		elementType,
		separatorText,
		separatorTextTag,
		separatorIcon,
		elementPosition,
		elementSpacing,
		elementSpacingTablet,
		elementSpacingMobile,
		elementSpacingUnit,
		elementTextLoadGoogleFonts,
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
	} = attributes;

	// Separator settings.
	const separatorGeneralSettings = () => {
		return (
			<>
				<UAGAdvancedPanelBody
					title={ __( 'Separator', 'sureforms' ) }
					initialOpen={ true }
				>
					<UAGSelectControl
						label={ __( 'Style', 'sureforms' ) }
						data={ {
							value: separatorStyle,
							label: 'separatorStyle',
						} }
						help={
							separatorStyle !== 'none' &&
							separatorStyle !== 'dotted' &&
							separatorStyle !== 'dashed' &&
							separatorStyle !== 'double' &&
							separatorStyle !== 'solid'
								? __(
									'Note: Please set Separator Height for proper thickness.',
									'sureforms'
								  )
								: false
						}
						setAttributes={ setAttributes }
						options={ [
							{
								value: 'none',
								label: __( 'None', 'sureforms' ),
							},
							{
								value: 'dotted',
								label: __( 'Dotted', 'sureforms' ),
							},
							{
								value: 'dashed',
								label: __( 'Dashed', 'sureforms' ),
							},
							{
								value: 'double',
								label: __( 'Double', 'sureforms' ),
							},
							{
								value: 'solid',
								label: __( 'Solid', 'sureforms' ),
							},
							{
								value: 'rectangles',
								label: __( 'Rectangles', 'sureforms' ),
							},
							{
								value: 'parallelogram',
								label: __( 'Parallelogram', 'sureforms' ),
							},
							{
								value: 'slash',
								label: __( 'Slash', 'sureforms' ),
							},
							{
								value: 'leaves',
								label: __( 'Leaves', 'sureforms' ),
							},
						] }
					/>
					<MultiButtonsControl
						setAttributes={ setAttributes }
						label={ __( 'Add Element', 'sureforms' ) }
						data={ {
							value: elementType,
							label: 'elementType',
						} }
						options={ [
							{
								value: 'none',
								label: __( 'None', 'sureforms' ),
							},
							{
								value: 'text',
								label: __( 'Text', 'sureforms' ),
							},
							{
								value: 'icon',
								label: __( 'Icon', 'sureforms' ),
							},
						] }
						showIcons={ false }
						responsive={ false }
					/>
					{ elementType === 'text' && (
						<>
							<UAGTextControl
								label={ __( 'Text', 'sureforms' ) }
								data={ {
									value: separatorText,
									label: 'separatorText',
								} }
								setAttributes={ setAttributes }
								value={ separatorText }
							/>
							<MultiButtonsControl
								setAttributes={ setAttributes }
								label={ __( 'Heading Tag', 'sureforms' ) }
								data={ {
									value: separatorTextTag,
									label: 'separatorTextTag',
								} }
								options={ [
									{
										value: 'h1',
										label: __( 'H1', 'sureforms' ),
									},
									{
										value: 'h2',
										label: __( 'H2', 'sureforms' ),
									},
									{
										value: 'h3',
										label: __( 'H3', 'sureforms' ),
									},
									{
										value: 'h4',
										label: __( 'H4', 'sureforms' ),
									},
									{
										value: 'h5',
										label: __( 'H5', 'sureforms' ),
									},
									{
										value: 'h6',
										label: __( 'H6', 'sureforms' ),
									},
									{
										value: 'span',
										label: __( 'Span', 'sureforms' ),
									},
									{
										value: 'p',
										label: __( 'P', 'sureforms' ),
									},
								] }
							/>
						</>
					) }
					{ elementType === 'icon' && (
						<>
							<UAGIconPicker
								label={ __( 'Icon', 'sureforms' ) }
								value={ separatorIcon }
								onChange={ ( value ) =>
									setAttributes( { separatorIcon: value } )
								}
							/>
						</>
					) }
				</UAGAdvancedPanelBody>
			</>
		);
	};

	const separatorStyleSettings = () => {
		return (
			<UAGAdvancedPanelBody title="Separator" initialOpen={ true }>
				<MultiButtonsControl
					setAttributes={ setAttributes }
					label={ __( 'Alignment', 'sureforms' ) }
					data={ {
						desktop: {
							value: separatorAlign,
							label: 'separatorAlign',
						},
						tablet: {
							value: separatorAlignTablet,
							label: 'separatorAlignTablet',
						},
						mobile: {
							value: separatorAlignMobile,
							label: 'separatorAlignMobile',
						},
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
							tooltip: __( 'Left', 'sureforms' ),
						},
						{
							value: 'center',
							icon: (
								<Icon
									icon={ renderSVG( 'fa fa-align-center' ) }
								/>
							),
							tooltip: __( 'Center', 'sureforms' ),
						},
						{
							value: 'right',
							icon: (
								<Icon
									icon={ renderSVG( 'fa fa-align-right' ) }
								/>
							),
							tooltip: __( 'Right', 'sureforms' ),
						},
					] }
					showIcons={ true }
					responsive={ true }
				/>
				<ResponsiveSlider
					label={ __( 'Width', 'sureforms' ) }
					data={ {
						desktop: {
							value: separatorWidth,
							label: 'separatorWidth',
						},
						tablet: {
							value: separatorWidthTablet,
							label: 'separatorWidthTablet',
						},
						mobile: {
							value: separatorWidthMobile,
							label: 'separatorWidthMobile',
						},
					} }
					min={ 0 }
					max={ '%' === separatorWidthType ? 100 : 500 }
					unit={ {
						value: separatorWidthType,
						label: 'separatorWidthType',
					} }
					units={ [
						{
							name: __( 'Pixel', 'sureforms' ),
							unitValue: 'px',
						},
						{
							name: __( '%', 'sureforms' ),
							unitValue: '%',
						},
					] }
					setAttributes={ setAttributes }
				/>
				<ResponsiveSlider
					label={ __( 'Height', 'sureforms' ) }
					data={ {
						desktop: {
							value: separatorHeight,
							label: 'separatorHeight',
						},
						tablet: {
							value: separatorHeightTablet,
							label: 'separatorHeightTablet',
						},
						mobile: {
							value: separatorHeightMobile,
							label: 'separatorHeightMobile',
						},
					} }
					min={ 0 }
					max={ '%' === separatorHeightType ? 100 : 500 }
					unit={ {
						value: separatorHeightType,
						label: 'separatorHeightType',
					} }
					units={ [
						{
							name: __( 'Pixel', 'sureforms' ),
							unitValue: 'px',
						},
						{
							name: __( '%', 'sureforms' ),
							unitValue: '%',
						},
					] }
					setAttributes={ setAttributes }
				/>
				{ separatorStyle !== 'solid' &&
					separatorStyle !== 'double' &&
					separatorStyle !== 'dotted' &&
					separatorStyle !== 'dashed' &&
					separatorStyle !== 'none' && (
					<ResponsiveSlider
						label={ __( 'Size', 'sureforms' ) }
						data={ {
							desktop: {
								value: separatorSize,
								label: 'separatorSize',
							},
							tablet: {
								value: separatorSizeTablet,
								label: 'separatorSizeTablet',
							},
							mobile: {
								value: separatorSizeMobile,
								label: 'separatorSizeMobile',
							},
						} }
						min={ 0 }
						max={ '%' === separatorSizeType ? 100 : 500 }
						unit={ {
							value: separatorSizeType,
							label: 'separatorSizeType',
						} }
						units={ [
							{
								name: __( 'Pixel', 'sureforms' ),
								unitValue: 'px',
							},
							{
								name: __( '%', 'sureforms' ),
								unitValue: '%',
							},
						] }
						setAttributes={ setAttributes }
					/>
				) }
				{ separatorStyle !== 'none' && (
					<ResponsiveSlider
						label={ __( 'Separator Height', 'sureforms' ) }
						data={ {
							desktop: {
								value: separatorBorderHeight,
								label: 'separatorBorderHeight',
							},
							tablet: {
								value: separatorBorderHeightTablet,
								label: 'separatorBorderHeightTablet',
							},
							mobile: {
								value: separatorBorderHeightMobile,
								label: 'separatorBorderHeightMobile',
							},
						} }
						min={ 0 }
						max={ '%' === separatorSizeType ? 100 : 500 }
						unit={ {
							value: separatorBorderHeightUnit,
							label: 'separatorBorderHeightUnit',
						} }
						units={ [
							{
								name: __( 'Pixel', 'sureforms' ),
								unitValue: 'px',
							},
							{
								name: __( '%', 'sureforms' ),
								unitValue: '%',
							},
						] }
						setAttributes={ setAttributes }
					/>
				) }
				{ separatorStyle !== 'none' && (
					<AdvancedPopColorControl
						label={ __( 'Color', 'sureforms' ) }
						colorValue={ separatorColor ? separatorColor : '' }
						data={ {
							value: separatorColor,
							label: 'separatorColor',
						} }
						setAttributes={ setAttributes }
					/>
				) }
			</UAGAdvancedPanelBody>
		);
	};

	const iconAndTextStyleSettings = () => {
		return (
			<UAGAdvancedPanelBody
				title={
					elementType === 'text'
						? __( 'Text', 'sureforms' )
						: __( 'Icon', 'sureforms' )
				}
				initialOpen={ false }
			>
				<MultiButtonsControl
					setAttributes={ setAttributes }
					label={ __( 'Alignment', 'sureforms' ) }
					responsive={ true }
					data={ {
						desktop: {
							value: elementPosition,
							label: 'elementPosition',
						},
						tablet: {
							value: elementPosition,
							label: 'elementPosition',
						},
						mobile: {
							value: elementPosition,
							label: 'elementPosition',
						},
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
							tooltip: __( 'Left', 'sureforms' ),
						},
						{
							value: 'center',
							icon: (
								<Icon
									icon={ renderSVG( 'fa fa-align-center' ) }
								/>
							),
							tooltip: __( 'Center', 'sureforms' ),
						},
						{
							value: 'right',
							icon: (
								<Icon
									icon={ renderSVG( 'fa fa-align-right' ) }
								/>
							),
							tooltip: __( 'Right', 'sureforms' ),
						},
					] }
					showIcons={ true }
				/>
				<AdvancedPopColorControl
					label={ __( 'Color', 'sureforms' ) }
					colorValue={ elementColor ? elementColor : '' }
					data={ {
						value: elementColor,
						label: 'elementColor',
					} }
					setAttributes={ setAttributes }
				/>
				{ elementType === 'text' && (
					<TypographyControl
						label={ __( 'Typography', 'sureforms' ) }
						attributes={ attributes }
						setAttributes={ setAttributes }
						loadGoogleFonts={ {
							value: elementTextLoadGoogleFonts,
							label: 'elementTextLoadGoogleFonts',
						} }
						fontFamily={ {
							value: elementTextFontFamily,
							label: 'elementTextFontFamily',
						} }
						fontWeight={ {
							value: elementTextFontWeight,
							label: 'elementTextFontWeight',
						} }
						fontStyle={ {
							value: elementTextFontStyle,
							label: 'elementTextFontStyle',
						} }
						fontSizeType={ {
							value: elementTextFontSizeType,
							label: 'elementTextFontSizeType',
						} }
						fontSize={ {
							value: elementTextFontSize,
							label: 'elementTextFontSize',
						} }
						fontSizeMobile={ {
							value: elementTextFontSizeMobile,
							label: 'elementTextFontSizeMobile',
						} }
						fontSizeTablet={ {
							value: elementTextFontSizeTablet,
							label: 'elementTextFontSizeTablet',
						} }
						lineHeightType={ {
							value: elementTextLineHeightType,
							label: 'elementTextLineHeightType',
						} }
						lineHeight={ {
							value: elementTextLineHeight,
							label: 'elementTextLineHeight',
						} }
						lineHeightMobile={ {
							value: elementTextLineHeightMobile,
							label: 'elementTextLineHeightMobile',
						} }
						lineHeightTablet={ {
							value: elementTextLineHeightTablet,
							label: 'elementTextLineHeightTablet',
						} }
						letterSpacing={ {
							value: elementTextLetterSpacing,
							label: 'elementTextLetterSpacing',
						} }
						letterSpacingTablet={ {
							value: elementTextLetterSpacingTablet,
							label: 'elementTextLetterSpacingTablet',
						} }
						letterSpacingMobile={ {
							value: elementTextLetterSpacingMobile,
							label: 'elementTextLetterSpacingMobile',
						} }
						letterSpacingType={ {
							value: elementTextLetterSpacingType,
							label: 'elementTextLetterSpacingType',
						} }
						transform={ {
							value: elementTextTransform,
							label: 'elementTextTransform',
						} }
						decoration={ {
							value: elementTextDecoration,
							label: 'elementTextDecoration',
						} }
					/>
				) }

				{ elementType === 'icon' && (
					<ResponsiveSlider
						label={ __( 'Icon Size', 'sureforms' ) }
						data={ {
							desktop: {
								value: elementIconWidth,
								label: 'elementIconWidth',
							},
							tablet: {
								value: elementIconWidthTablet,
								label: 'elementIconWidthTablet',
							},
							mobile: {
								value: elementIconWidthMobile,
								label: 'elementIconWidthMobile',
							},
						} }
						min={ 0 }
						max={ 100 }
						unit={ {
							value: elementIconWidthType,
							label: 'elementIconWidthType',
						} }
						units={ [
							{
								name: __( 'Pixel', 'sureforms' ),
								unitValue: 'px',
							},
							{
								name: __( 'EM', 'sureforms' ),
								unitValue: 'em',
							},
						] }
						setAttributes={ setAttributes }
					/>
				) }
				<ResponsiveSlider
					label={ __( 'Spacing', 'sureforms' ) }
					data={ {
						desktop: {
							value: elementSpacing,
							label: 'elementSpacing',
						},
						tablet: {
							value: elementSpacingTablet,
							label: 'elementSpacingTablet',
						},
						mobile: {
							value: elementSpacingMobile,
							label: 'elementSpacingMobile',
						},
					} }
					min={ 0 }
					max={ 500 }
					unit={ {
						value: elementSpacingUnit,
						label: 'elementSpacingUnit',
					} }
					units={ [
						{
							name: __( 'Pixel', 'sureforms' ),
							unitValue: 'px',
						},
					] }
					setAttributes={ setAttributes }
				/>
			</UAGAdvancedPanelBody>
		);
	};

	return (
		<div>
			<InspectorControls>
				<InspectorTabs>
					<InspectorTab { ...UAGTabs.general }>
						{ separatorGeneralSettings() }
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }>
						{ separatorStyleSettings() }
						{ elementType !== 'none' && iconAndTextStyleSettings() }
					</InspectorTab>
				</InspectorTabs>
			</InspectorControls>
		</div>
	);
};
export default memo( Settings );
