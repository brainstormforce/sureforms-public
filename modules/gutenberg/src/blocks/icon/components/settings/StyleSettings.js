/**
 * Block Icon : Style Settings.
 */
import { __ } from '@wordpress/i18n';
import { memo } from '@wordpress/element';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import UAGTabsControl from '@Components/tabs';
import ColorSwitchControl from '@Components/color-switch-control';
import ResponsiveBorder from '@Components/responsive-border';
import TextShadowControl from '@Components/text-shadow';
import BoxShadowControl from '@Components/box-shadow';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import SpacingControl from '@Components/spacing-control';
import {
	dropShadowPresets,
	boxShadowPresets,
	boxShadowHoverPresets,
} from '../../presets';
import UAGPresets from '@Components/presets';
import { ToggleControl } from '@wordpress/components';

const StyleSettings = ( props ) => {
	const { attributes, setAttributes, deviceType } = props;

	const {
		block_id,
		// Color
		iconColor,
		iconBackgroundColorType,
		iconBackgroundColor,
		iconBackgroundGradientColor,
		iconHoverColor,
		iconHoverBackgroundColorType,
		iconHoverBackgroundColor,
		iconHoverBackgroundGradientColor,
		// Padding
		iconTopPadding,
		iconRightPadding,
		iconBottomPadding,
		iconLeftPadding,
		iconTopTabletPadding,
		iconRightTabletPadding,
		iconBottomTabletPadding,
		iconLeftTabletPadding,
		iconTopMobilePadding,
		iconRightMobilePadding,
		iconBottomMobilePadding,
		iconLeftMobilePadding,
		iconPaddingUnit,
		iconMobilePaddingUnit,
		iconTabletPaddingUnit,
		iconPaddingLink,
		// Margin
		iconTopMargin,
		iconRightMargin,
		iconBottomMargin,
		iconLeftMargin,
		iconTopTabletMargin,
		iconRightTabletMargin,
		iconBottomTabletMargin,
		iconLeftTabletMargin,
		iconTopMobileMargin,
		iconRightMobileMargin,
		iconBottomMobileMargin,
		iconLeftMobileMargin,
		iconMarginUnit,
		iconMobileMarginUnit,
		iconTabletMarginUnit,
		iconMarginLink,
		// Shadow
		iconShadowColor,
		iconShadowHOffset,
		iconShadowVOffset,
		iconShadowBlur,
		// Box Shadow
		useSeparateBoxShadows,
		iconBoxShadowColor,
		iconBoxShadowHOffset,
		iconBoxShadowVOffset,
		iconBoxShadowBlur,
		iconBoxShadowSpread,
		iconBoxShadowPosition,
		iconBoxShadowColorHover,
		iconBoxShadowHOffsetHover,
		iconBoxShadowVOffsetHover,
		iconBoxShadowBlurHover,
		iconBoxShadowSpreadHover,
		iconBoxShadowPositionHover,
	} = attributes;

	return (
		<>
			<UAGAdvancedPanelBody
				title={ __( 'Icon', 'sureforms' ) }
				initialOpen={ true }
			>
				<UAGTabsControl
					tabs={ [
						{
							name: 'normal',
							title: __( 'Normal', 'sureforms' ),
						},
						{
							name: 'hover',
							title: __( 'Hover', 'sureforms' ),
						},
					] }
					normal={
						<>
							<AdvancedPopColorControl
								label={ __( 'Icon Color', 'sureforms' ) }
								colorValue={ iconColor ? iconColor : '' }
								data={ {
									value: iconColor,
									label: 'iconColor',
								} }
								setAttributes={ setAttributes }
							/>
							<ColorSwitchControl
								label={ __( 'Background Type', 'sureforms' ) }
								type={ {
									value: iconBackgroundColorType,
									label: 'iconBackgroundColorType',
								} }
								classic={ {
									value: iconBackgroundColor,
									label: 'iconBackgroundColor',
								} }
								gradient={ {
									value: iconBackgroundGradientColor,
									label: 'iconBackgroundGradientColor',
								} }
								setAttributes={ setAttributes }
							/>
						</>
					}
					hover={
						<>
							<AdvancedPopColorControl
								label={ __( 'Icon Color', 'sureforms' ) }
								colorValue={
									iconHoverColor ? iconHoverColor : ''
								}
								data={ {
									value: iconHoverColor,
									label: 'iconHoverColor',
								} }
								setAttributes={ setAttributes }
							/>
							<ColorSwitchControl
								label={ __( 'Background Type', 'sureforms' ) }
								type={ {
									value: iconHoverBackgroundColorType,
									label: 'iconHoverBackgroundColorType',
								} }
								classic={ {
									value: iconHoverBackgroundColor,
									label: 'iconHoverBackgroundColor',
								} }
								gradient={ {
									value: iconHoverBackgroundGradientColor,
									label: 'iconHoverBackgroundGradientColor',
								} }
								setAttributes={ setAttributes }
							/>
						</>
					}
					disableBottomSeparator={ false }
				/>
				<ResponsiveBorder
					setAttributes={ setAttributes }
					prefix={ 'icon' }
					attributes={ attributes }
					deviceType={ deviceType }
					disableBottomSeparator={ true }
				/>
			</UAGAdvancedPanelBody>
			<UAGAdvancedPanelBody
				title={ __( 'Drop Shadow', 'sureforms' ) }
				initialOpen={ false }
			>
				<UAGPresets
					setAttributes={ setAttributes }
					presets={ dropShadowPresets }
					presetInputType="radioImage"
				/>
				<TextShadowControl
					blockId={ block_id }
					setAttributes={ setAttributes }
					textShadowColor={ {
						value: iconShadowColor,
						label: 'iconShadowColor',
						title: __( 'Color', 'sureforms' ),
					} }
					textShadowHOffset={ {
						value: iconShadowHOffset,
						label: 'iconShadowHOffset',
						title: __( 'Horizontal', 'sureforms' ),
					} }
					textShadowVOffset={ {
						value: iconShadowVOffset,
						label: 'iconShadowVOffset',
						title: __( 'Vertical', 'sureforms' ),
					} }
					textShadowBlur={ {
						value: iconShadowBlur,
						label: 'iconShadowBlur',
						title: __( 'Blur', 'sureforms' ),
					} }
					popup={ false }
				/>
			</UAGAdvancedPanelBody>
			<UAGAdvancedPanelBody
				title={ __( 'Box Shadow', 'sureforms' ) }
				initialOpen={ false }
			>
				<ToggleControl
					label={ __( 'Separate Hover Shadow', 'sureforms' ) }
					checked={ useSeparateBoxShadows }
					onChange={ () =>
						setAttributes( {
							useSeparateBoxShadows: ! useSeparateBoxShadows,
						} )
					}
				/>
				{ useSeparateBoxShadows ? (
					<UAGTabsControl
						tabs={ [
							{
								name: 'normal',
								title: __( 'Normal', 'sureforms' ),
							},
							{
								name: 'hover',
								title: __( 'Hover', 'sureforms' ),
							},
						] }
						normal={
							<>
								<UAGPresets
									setAttributes={ setAttributes }
									presets={ boxShadowPresets }
									presetInputType="radioImage"
								/>
								<BoxShadowControl
									blockId={ block_id }
									setAttributes={ setAttributes }
									boxShadowColor={ {
										value: iconBoxShadowColor,
										label: 'iconBoxShadowColor',
										title: __( 'Color', 'sureforms' ),
									} }
									boxShadowHOffset={ {
										value: iconBoxShadowHOffset,
										label: 'iconBoxShadowHOffset',
										title: __( 'Horizontal', 'sureforms' ),
									} }
									boxShadowVOffset={ {
										value: iconBoxShadowVOffset,
										label: 'iconBoxShadowVOffset',
										title: __( 'Vertical', 'sureforms' ),
									} }
									boxShadowBlur={ {
										value: iconBoxShadowBlur,
										label: 'iconBoxShadowBlur',
										title: __( 'Blur', 'sureforms' ),
									} }
									boxShadowSpread={ {
										value: iconBoxShadowSpread,
										label: 'iconBoxShadowSpread',
										title: __( 'Spread', 'sureforms' ),
									} }
									boxShadowPosition={ {
										value: iconBoxShadowPosition,
										label: 'iconBoxShadowPosition',
										title: __( 'Position', 'sureforms' ),
									} }
								/>
							</>
						}
						hover={
							<>
								<UAGPresets
									setAttributes={ setAttributes }
									presets={ boxShadowHoverPresets }
									presetInputType="radioImage"
								/>
								<BoxShadowControl
									blockId={ block_id }
									setAttributes={ setAttributes }
									boxShadowColor={ {
										value: iconBoxShadowColorHover,
										label: 'iconBoxShadowColorHover',
										title: __( 'Color', 'sureforms' ),
									} }
									boxShadowHOffset={ {
										value: iconBoxShadowHOffsetHover,
										label: 'iconBoxShadowHOffsetHover',
										title: __( 'Horizontal', 'sureforms' ),
									} }
									boxShadowVOffset={ {
										value: iconBoxShadowVOffsetHover,
										label: 'iconBoxShadowVOffsetHover',
										title: __( 'Vertical', 'sureforms' ),
									} }
									boxShadowBlur={ {
										value: iconBoxShadowBlurHover,
										label: 'iconBoxShadowBlurHover',
										title: __( 'Blur', 'sureforms' ),
									} }
									boxShadowSpread={ {
										value: iconBoxShadowSpreadHover,
										label: 'iconBoxShadowSpreadHover',
										title: __( 'Spread', 'sureforms' ),
									} }
									boxShadowPosition={ {
										value: iconBoxShadowPositionHover,
										label: 'iconBoxShadowPositionHover',
										title: __( 'Position', 'sureforms' ),
									} }
								/>
							</>
						}
						disableBottomSeparator={ true }
					/>
				) : (
					<>
						<UAGPresets
							setAttributes={ setAttributes }
							presets={ boxShadowPresets }
							presetInputType="radioImage"
						/>
						<BoxShadowControl
							blockId={ block_id }
							setAttributes={ setAttributes }
							boxShadowColor={ {
								value: iconBoxShadowColor,
								label: 'iconBoxShadowColor',
								title: __( 'Color', 'sureforms' ),
							} }
							boxShadowHOffset={ {
								value: iconBoxShadowHOffset,
								label: 'iconBoxShadowHOffset',
								title: __( 'Horizontal', 'sureforms' ),
							} }
							boxShadowVOffset={ {
								value: iconBoxShadowVOffset,
								label: 'iconBoxShadowVOffset',
								title: __( 'Vertical', 'sureforms' ),
							} }
							boxShadowBlur={ {
								value: iconBoxShadowBlur,
								label: 'iconBoxShadowBlur',
								title: __( 'Blur', 'sureforms' ),
							} }
							boxShadowSpread={ {
								value: iconBoxShadowSpread,
								label: 'iconBoxShadowSpread',
								title: __( 'Spread', 'sureforms' ),
							} }
							boxShadowPosition={ {
								value: iconBoxShadowPosition,
								label: 'iconBoxShadowPosition',
								title: __( 'Position', 'sureforms' ),
							} }
						/>
					</>
				) }
			</UAGAdvancedPanelBody>
			<UAGAdvancedPanelBody
				title={ __( 'Spacing', 'sureforms' ) }
				initialOpen={ false }
			>
				<SpacingControl
					{ ...props }
					label={ __( 'Padding', 'sureforms' ) }
					valueTop={ {
						value: iconTopPadding,
						label: 'iconTopPadding',
					} }
					valueRight={ {
						value: iconRightPadding,
						label: 'iconRightPadding',
					} }
					valueBottom={ {
						value: iconBottomPadding,
						label: 'iconBottomPadding',
					} }
					valueLeft={ {
						value: iconLeftPadding,
						label: 'iconLeftPadding',
					} }
					valueTopTablet={ {
						value: iconTopTabletPadding,
						label: 'iconTopTabletPadding',
					} }
					valueRightTablet={ {
						value: iconRightTabletPadding,
						label: 'iconRightTabletPadding',
					} }
					valueBottomTablet={ {
						value: iconBottomTabletPadding,
						label: 'iconBottomTabletPadding',
					} }
					valueLeftTablet={ {
						value: iconLeftTabletPadding,
						label: 'iconLeftTabletPadding',
					} }
					valueTopMobile={ {
						value: iconTopMobilePadding,
						label: 'iconTopMobilePadding',
					} }
					valueRightMobile={ {
						value: iconRightMobilePadding,
						label: 'iconRightMobilePadding',
					} }
					valueBottomMobile={ {
						value: iconBottomMobilePadding,
						label: 'iconBottomMobilePadding',
					} }
					valueLeftMobile={ {
						value: iconLeftMobilePadding,
						label: 'iconLeftMobilePadding',
					} }
					unit={ {
						value: iconPaddingUnit,
						label: 'iconPaddingUnit',
					} }
					mUnit={ {
						value: iconMobilePaddingUnit,
						label: 'iconMobilePaddingUnit',
					} }
					tUnit={ {
						value: iconTabletPaddingUnit,
						label: 'iconTabletPaddingUnit',
					} }
					attributes={ attributes }
					setAttributes={ setAttributes }
					link={ {
						value: iconPaddingLink,
						label: 'iconPaddingLink',
					} }
				/>
				<SpacingControl
					{ ...props }
					label={ __( 'Margin', 'sureforms' ) }
					valueTop={ {
						value: iconTopMargin,
						label: 'iconTopMargin',
					} }
					valueRight={ {
						value: iconRightMargin,
						label: 'iconRightMargin',
					} }
					valueBottom={ {
						value: iconBottomMargin,
						label: 'iconBottomMargin',
					} }
					valueLeft={ {
						value: iconLeftMargin,
						label: 'iconLeftMargin',
					} }
					valueTopTablet={ {
						value: iconTopTabletMargin,
						label: 'iconTopTabletMargin',
					} }
					valueRightTablet={ {
						value: iconRightTabletMargin,
						label: 'iconRightTabletMargin',
					} }
					valueBottomTablet={ {
						value: iconBottomTabletMargin,
						label: 'iconBottomTabletMargin',
					} }
					valueLeftTablet={ {
						value: iconLeftTabletMargin,
						label: 'iconLeftTabletMargin',
					} }
					valueTopMobile={ {
						value: iconTopMobileMargin,
						label: 'iconTopMobileMargin',
					} }
					valueRightMobile={ {
						value: iconRightMobileMargin,
						label: 'iconRightMobileMargin',
					} }
					valueBottomMobile={ {
						value: iconBottomMobileMargin,
						label: 'iconBottomMobileMargin',
					} }
					valueLeftMobile={ {
						value: iconLeftMobileMargin,
						label: 'iconLeftMobileMargin',
					} }
					unit={ {
						value: iconMarginUnit,
						label: 'iconMarginUnit',
					} }
					mUnit={ {
						value: iconMobileMarginUnit,
						label: 'iconMobileMarginUnit',
					} }
					tUnit={ {
						value: iconTabletMarginUnit,
						label: 'iconTabletMarginUnit',
					} }
					attributes={ attributes }
					setAttributes={ setAttributes }
					link={ {
						value: iconMarginLink,
						label: 'iconMarginLink',
					} }
				/>
			</UAGAdvancedPanelBody>
		</>
	);
};

export default memo( StyleSettings );
