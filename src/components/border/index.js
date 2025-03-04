/**
 * Border component.
 *
 */
import { __ } from '@wordpress/i18n';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import SRFMSelectControl from '@Components/select-control';
import SRFMTabsControl from '@Components/tabs';
import SRFMHelpText from '@Components/help-text';
import Separator from '@Components/separator';
import Spacing from '@Components/spacing';

const defaultProps = {
	borderStyleLabel: __( 'Style', 'sureforms' ),
	borderWidthLabel: __( 'Width', 'sureforms' ),
	borderRadiusLabel: __( 'Radius', 'sureforms' ),
	borderColorLabel: __( 'Color', 'sureforms' ),
	borderHoverColorLabel: __( 'Hover Color', 'sureforms' ),
};

const Border = ( props ) => {
	const {
		setAttributes,
		disabledBorderTitle,
		disableBottomSeparator,
		showWidth,
		help = false,
		// Default Attributes.
		borderStyleLabel,
		borderWidthLabel,
		borderRadiusLabel,
		borderColorLabel,
		borderHoverColorLabel,
		label = __( 'Border', 'sureforms' ),
		normalTabLabel = __( 'Normal', 'sureforms' ),
		hoverTabLabel = __( 'Hover', 'sureforms' ),
		// Custom Attributes - Start.
		borderStyle,
		// Border Width.
		borderWidthTop,
		borderWidthRight,
		borderWidthBottom,
		borderWidthLeft,
		borderWidthLink,
		// Border Radius.
		borderRadiusTop,
		borderRadiusRight,
		borderRadiusBottom,
		borderRadiusLeft,
		borderRadiusUnit,
		borderRadiusLink,
		// Color.
		borderColor,
		borderHoverColor,
		// Custom Attributes - End.
	} = props;

	const tabsToUse = [
		{
			name: 'normal',
			title: normalTabLabel,
		},
		{
			name: 'hover',
			title: hoverTabLabel,
		},
	];

	const tabOutputNormal = (
		<AdvancedPopColorControl
			label={ borderColorLabel }
			colorValue={ borderColor?.value }
			data={ {
				value: borderColor?.value,
				label: borderColor?.label,
			} }
			setAttributes={ setAttributes }
		/>
	);
	const tabOutputHover = (
		<AdvancedPopColorControl
			label={ borderHoverColorLabel }
			colorValue={ borderHoverColor?.value }
			data={ {
				value: borderHoverColor?.value,
				label: borderHoverColor?.label,
			} }
			setAttributes={ setAttributes }
		/>
	);

	const advancedControls = (
		<>
			{ ! disabledBorderTitle && (
				<p className="srfm-panel__body-sub-heading">{ label }</p>
			) }
			<SRFMSelectControl
				label={ borderStyleLabel }
				data={ {
					value: borderStyle?.value,
					label: borderStyle?.label,
				} }
				setAttributes={ setAttributes }
				options={ [
					{
						value: 'default',
						label: __( 'Default', 'sureforms' ),
					},
					{
						value: 'none',
						label: __( 'None', 'sureforms' ),
					},
					{
						value: 'solid',
						label: __( 'Solid', 'sureforms' ),
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
						value: 'groove',
						label: __( 'Groove', 'sureforms' ),
					},
					{
						value: 'inset',
						label: __( 'Inset', 'sureforms' ),
					},
					{
						value: 'outset',
						label: __( 'Outset', 'sureforms' ),
					},
					{
						value: 'ridge',
						label: __( 'Ridge', 'sureforms' ),
					},
				] }
			/>
			{ 'default' !== borderStyle?.value &&
				'none' !== borderStyle?.value &&
				showWidth !== false && (
				<Spacing
					label={ borderWidthLabel }
					valueTop={ {
						value: borderWidthTop?.value,
						label: borderWidthTop?.label,
					} }
					valueRight={ {
						value: borderWidthRight?.value,
						label: borderWidthRight?.label,
					} }
					valueBottom={ {
						value: borderWidthBottom?.value,
						label: borderWidthBottom?.label,
					} }
					valueLeft={ {
						value: borderWidthLeft?.value,
						label: borderWidthLeft?.label,
					} }
					disableUnits={ true }
					setAttributes={ setAttributes }
					link={ {
						value: borderWidthLink?.value,
						label: borderWidthLink?.label,
					} }
					min={ 0 }
				/>
			) }
			{ 'default' !== borderStyle?.value && (
				<Spacing
					label={ borderRadiusLabel }
					valueTop={ {
						value: borderRadiusTop?.value,
						label: borderRadiusTop?.label,
					} }
					valueRight={ {
						value: borderRadiusRight?.value,
						label: borderRadiusRight?.label,
					} }
					valueBottom={ {
						value: borderRadiusBottom?.value,
						label: borderRadiusBottom?.label,
					} }
					valueLeft={ {
						value: borderRadiusLeft?.value,
						label: borderRadiusLeft?.label,
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
					unit={ {
						value: borderRadiusUnit?.value,
						label: borderRadiusUnit?.label,
					} }
					setAttributes={ setAttributes }
					link={ {
						value: borderRadiusLink?.value,
						label: borderRadiusLink?.label,
					} }
					min={ 0 }
				/>
			) }
			{ ! [ 'none', 'default' ].includes( borderStyle.value ) && (
				<SRFMTabsControl
					tabs={ tabsToUse }
					normal={ tabOutputNormal }
					hover={ tabOutputHover }
					active={ '' }
					disableBottomSeparator={ true }
				/>
			) }
			<SRFMHelpText text={ help } />
		</>
	);

	return (
		<div>
			{ advancedControls }
		</div>
	);
};

export default Border;

Border.defaultProps = defaultProps;
