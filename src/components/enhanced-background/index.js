import { __ } from '@wordpress/i18n';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import { SelectControl, FocalPointPicker } from '@wordpress/components';
import styles from './editor.lazy.scss';
import GradientSettings from '@Components/gradient-settings';
import { useRef, useLayoutEffect, useEffect } from '@wordpress/element';
import SRFMMediaPicker from '@Components/image';
import MultiButtonsControl from '@Components/multi-buttons-control';
import SRFM_Block_Icons from '@Controls/block-icons';
import SRFMHelpText from '@Components/help-text';
import Range from '@Components/range/Range';
import Separator from '@Components/separator';

const Background = ( props ) => {
	const panelRef = useRef( null );

	// Add and remove the CSS on the drop and remove of the component.
	useLayoutEffect( () => {
		styles?.use();
		return () => {
			styles?.unuse();
		};
	}, [] );

	/**
	 * Adding a containerRef and observer to trigger the resize event on the container dimension change.
	 * This is to handle the case where focal point picker uses default bounds for the image due delayed image load.
	 * Triggering the resize event will force the focal point picker to recalculate the bounds and this works for the image overlay focal point picker as well.
	 */
	const containerRef = useRef( null );
	useEffect( () => {
		if ( ! containerRef.current ) {
			return;
		}

		const observer = new ResizeObserver( () => {
			window.dispatchEvent( new Event( 'resize' ) );
		} );

		observer.observe( containerRef.current );
		return () => observer.disconnect();
	}, [] );

	const {
		setAttributes,
		onSelectImage,
		backgroundImageColor,
		overlayType,
		overlayOpacity,
		backgroundSize,
		backgroundRepeat,
		backgroundAttachment,
		backgroundPosition,
		backgroundImage,
		backgroundColor,
		backgroundType,
		backgroundCustomSize,
		backgroundCustomSizeType,
		gradientOverlay,
		help = false,
		backgroundOverlaySize,
		backgroundOverlayRepeat,
		backgroundOverlayAttachment,
		backgroundOverlayPosition,
		backgroundOverlayImage,
		backgroundOverlayCustomSize,
		backgroundOverlayCustomSizeType,
		overlayBlendMode,
		disableOverlay = false,
		bgTextOptions,
		label = __( 'Type', 'sureforms' ),
	} = props;

	const overlayOptions = [];

	switch ( backgroundType.value ) {
		case 'color':
		case 'gradient':
			overlayOptions.push( {
				value: 'image',
				icon: SRFM_Block_Icons.bg_image,
				tooltip: __( 'Image', 'sureforms' ),
			} );
			break;
		case 'image':
			overlayOptions.push(
				{
					value: 'color',
					icon: SRFM_Block_Icons.bg_color,
					tooltip: __( 'Color', 'sureforms' ),
				},
				{
					value: 'gradient',
					icon: SRFM_Block_Icons.bg_gradient,
					tooltip: __( 'Gradient', 'sureforms' ),
				},
				{
					value: 'image',
					icon: SRFM_Block_Icons.bg_image,
					tooltip: __( 'Image', 'sureforms' ),
				}
			);
			break;
		default:
			overlayOptions.push(
				{
					value: 'none',
					label: __( 'None', 'sureforms' ),
				},
				{
					value: 'color',
					label: __( 'Classic', 'sureforms' ),
				}
			);
			break;
	}

	const bgIconOptions = [
		{
			value: 'color',
			icon: SRFM_Block_Icons.bg_color,
			tooltip: __( 'Color', 'sureforms' ),
		},
		{
			value: 'gradient',
			icon: SRFM_Block_Icons.bg_gradient,
			tooltip: __( 'Gradient', 'sureforms' ),
		},
		{
			value: 'image',
			icon: SRFM_Block_Icons.bg_image,
			tooltip: __( 'Image', 'sureforms' ),
		},
	];

	let bgSizeOptions = [
		{
			value: 'auto',
			label: __( 'Auto', 'sureforms' ),
		},
		{
			value: 'cover',
			label: __( 'Cover', 'sureforms' ),
		},
		{
			value: 'contain',
			label: __( 'Contain', 'sureforms' ),
		},
		{
			value: 'custom',
			label: __( 'Custom', 'sureforms' ),
		},
	];

	if ( ! backgroundCustomSize ) {
		bgSizeOptions = [
			{
				value: 'auto',
				label: __( 'Auto', 'sureforms' ),
			},
			{
				value: 'cover',
				label: __( 'Cover', 'sureforms' ),
			},
			{
				value: 'contain',
				label: __( 'Contain', 'sureforms' ),
			},
		];
	}

	// Render Common Overlay Controls.
	const renderOverlayControls = () => {
		return (
			<div className="srfm-background-image-overlay-opacity">
				<Range
					label={ __( 'Overlay Opacity', 'sureforms' ) }
					setAttributes={ setAttributes }
					value={ overlayOpacity.value }
					data={ {
						value: overlayOpacity.value,
						label: overlayOpacity.label,
					} }
					min={ 0 }
					max={ 1 }
					step={ 0.05 }
					displayUnit={ false }
					isFormSpecific={ true }
				/>
			</div>
		);
	};

	//Render Common Overlay Controls.
	const renderOverlayImageControls = () => {
		return (
			<>
				<div className="srfm-background-image">
					<SRFMMediaPicker
						backgroundOverlayImage={ backgroundOverlayImage.value }
						disableLabel={ true }
						backgroundImage={ backgroundOverlayImage.value }
						onSelectImage={ ( media ) =>
							onSelectImage( backgroundOverlayImage.label, media )
						}
						onRemoveImage={ () =>
							setAttributes( {
								[ backgroundOverlayImage.label ]: '',
							} )
						}
						isFormSpecific={ true }
					/>
					{ backgroundOverlayImage.value && (
						<>
							<div className="srfm-background-image-position">
								<FocalPointPicker
									label={ __(
										'Image Position',
										'sureforms'
									) }
									url={ backgroundOverlayImage?.value }
									value={ backgroundOverlayPosition?.value }
									onChange={ ( focalPoint ) => {
										setAttributes( {
											[ backgroundOverlayPosition?.label ]:
												focalPoint,
										} );
									} }
								/>
							</div>
							<div className="srfm-background-image-attachment">
								<SelectControl
									label={ __( 'Attachment', 'sureforms' ) }
									value={ backgroundOverlayAttachment.value }
									onChange={ ( value ) =>
										setAttributes( {
											[ backgroundOverlayAttachment.label ]:
												value,
										} )
									}
									options={ [
										{
											value: 'fixed',
											label: __( 'Fixed', 'sureforms' ),
										},
										{
											value: 'scroll',
											label: __( 'Scroll', 'sureforms' ),
										},
									] }
								/>
							</div>
							<div className="srfm-background-blend-mode">
								<SelectControl
									label={ __( 'Blend Mode', 'sureforms' ) }
									value={ overlayBlendMode.value }
									onChange={ ( value ) =>
										setAttributes( {
											[ overlayBlendMode.label ]: value,
										} )
									}
									options={ [
										{
											value: 'normal',
											label: __( 'Normal', 'sureforms' ),
										},
										{
											value: 'multiply',
											label: __(
												'Multiply',
												'sureforms'
											),
										},
										{
											value: 'screen',
											label: __( 'Screen', 'sureforms' ),
										},
										{
											value: 'overlay',
											label: __( 'Overlay', 'sureforms' ),
										},
										{
											value: 'darken',
											label: __( 'Darken', 'sureforms' ),
										},
										{
											value: 'lighten',
											label: __( 'Lighten', 'sureforms' ),
										},
										{
											value: 'color-dodge',
											label: __(
												'Color Dodge',
												'sureforms'
											),
										},
										{
											value: 'saturation',
											label: __(
												'Saturation',
												'sureforms'
											),
										},
										{
											value: 'color',
											label: __( 'Color', 'sureforms' ),
										},
									] }
								/>
							</div>
							<div className="srfm-background-image-repeat">
								<SelectControl
									label={ __( 'Repeat', 'sureforms' ) }
									value={ backgroundOverlayRepeat.value }
									onChange={ ( value ) =>
										setAttributes( {
											[ backgroundOverlayRepeat.label ]:
												value,
										} )
									}
									options={ [
										{
											value: 'no-repeat',
											label: __(
												'No Repeat',
												'sureforms'
											),
										},
										{
											value: 'repeat',
											label: __( 'Repeat', 'sureforms' ),
										},
										{
											value: 'repeat-x',
											label: __(
												'Repeat-x',
												'sureforms'
											),
										},
										{
											value: 'repeat-y',
											label: __(
												'Repeat-y',
												'sureforms'
											),
										},
									] }
								/>
							</div>
							<div className="srfm-background-image-size">
								<SelectControl
									label={ __( 'Size', 'sureforms' ) }
									value={ backgroundOverlaySize.value }
									onChange={ ( value ) =>
										setAttributes( {
											[ backgroundOverlaySize.label ]:
												value,
										} )
									}
									options={ bgSizeOptions }
								/>
								{ 'custom' === backgroundOverlaySize.value &&
									backgroundOverlayCustomSize && (
									<Range
										label={ __( 'Width', 'sureforms' ) }
										value={
											backgroundOverlayCustomSize?.value
										}
										data={ {
											value: backgroundOverlayCustomSize?.value,
											label: backgroundOverlayCustomSize?.label,
										} }
										min={ 0 }
										limitMax={ {
											px: 1600,
											'%': 100,
											em: 574,
										} }
										unit={ {
											value: backgroundOverlayCustomSizeType.value,
											label: backgroundOverlayCustomSizeType.label,
										} }
										units={ [
											{
												name: __(
													'PX',
													'sureforms'
												),
												unitValue: 'px',
											},
											{
												name: __(
													'%',
													'sureforms'
												),
												unitValue: '%',
											},
											{
												name: __(
													'EM',
													'sureforms'
												),
												unitValue: 'em',
											},
										] }
										setAttributes={ setAttributes }
										responsive={ false }
										isFormSpecific={ true }
										displayUnit={ true }
									/>
								) }
							</div>
						</>
					) }
					{ renderOverlayControls() }
				</div>
			</>
		);
	};

	const buttonControl = (
		<>
			<Separator />
			<div className="srfm-background-image-overlay-type">
				<MultiButtonsControl
					setAttributes={ setAttributes }
					label={ __( 'Overlay Type', 'sureforms' ) }
					data={ {
						value: overlayType?.value,
						label: overlayType?.label,
					} }
					options={ overlayOptions }
					showIcons={ true }
					colorVariant="secondary"
					layoutVariant="inline"
				/>
			</div>
		</>
	);

	const overlayControls = (
		<>
			{ backgroundType.value === 'image' && backgroundImage?.value && (
				<>
					{ buttonControl }
					{ 'color' === overlayType?.value && (
						<>
							<div className="srfm-background-image-overlay-color">
								<AdvancedPopColorControl
									label={ __(
										'Image Overlay Color',
										'sureforms'
									) }
									colorValue={ backgroundImageColor.value }
									data={ {
										value: backgroundImageColor.value,
										label: backgroundImageColor.label,
									} }
									onColorChange={ ( colorValue ) =>
										setAttributes( {
											[ backgroundImageColor.label ]:
												colorValue,
										} )
									}
									value={ backgroundImageColor.value }
									isFormSpecific={ true }
								/>
							</div>
							{ renderOverlayControls() }
						</>
					) }
					{ 'gradient' === overlayType?.value && (
						<>
							<div className="srfm-background-image-overlay-gradient">
								<GradientSettings
									backgroundGradient={
										props.overlayBackgroundGradient
									}
									setAttributes={ setAttributes }
									gradientType={ props.overlayGradientType }
									backgroundGradientColor2={
										props.overlayBackgroundGradientColor2
									}
									backgroundGradientColor1={
										props.overlayBackgroundGradientColor1
									}
									backgroundGradientType={
										props.overlayBackgroundGradientType
									}
									backgroundGradientLocation1={
										props.overlayBackgroundGradientLocation1
									}
									backgroundGradientLocation2={
										props.overlayBackgroundGradientLocation2
									}
									backgroundGradientAngle={
										props.overlayBackgroundGradientAngle
									}
								/>
							</div>
							{ renderOverlayControls() }
						</>
					) }
					{ 'image' === overlayType?.value &&
						renderOverlayImageControls() }
				</>
			) }
			<Separator />
		</>
	);

	const advancedControls = (
		<>
			<div className="srfm-bg-type-container">
				<MultiButtonsControl
					label={ label }
					data={ {
						value: backgroundType.value,
						label: backgroundType.label,
					} }
					options={ bgTextOptions ? bgTextOptions : bgIconOptions }
					showIcons={ bgTextOptions ? false : true }
					colorVariant="secondary"
					layoutVariant="inline"
					setAttributes={ setAttributes }
				/>
			</div>
			{ 'color' === backgroundType.value && (
				<div className="srfm-background-color">
					<AdvancedPopColorControl
						label={ __( 'Background Color', 'sureforms' ) }
						colorValue={ backgroundColor.value }
						data={ {
							value: backgroundColor.value,
							label: backgroundColor.label,
						} }
						onColorChange={ ( colorValue ) =>
							setAttributes( {
								[ backgroundColor.label ]: colorValue,
							} )
						}
						value={ backgroundColor.value }
						isFormSpecific={ true }
					/>
				</div>
			) }
			{ 'image' === backgroundType.value && (
				<div className="srfm-background-image">
					<SRFMMediaPicker
						onSelectImage={ ( media ) =>
							onSelectImage( backgroundImage.label, media )
						}
						backgroundImage={ backgroundImage.value }
						onRemoveImage={ () =>
							setAttributes( { [ backgroundImage.label ]: '' } )
						}
						isFormSpecific={ true }
						disableLabel={ true }
					/>
					{ backgroundImage.value && (
						<>
							<div
								className="srfm-background-image-position"
								ref={ containerRef }
							>
								<FocalPointPicker
									label={ __(
										'Image Position',
										'sureforms'
									) }
									url={ backgroundImage?.value }
									value={ backgroundPosition?.value }
									onChange={ ( focalPoint ) => {
										setAttributes( {
											[ backgroundPosition?.label ]:
												focalPoint,
										} );
									} }
								/>
							</div>
							<div className="srfm-background-image-attachment">
								<SelectControl
									label={ __( 'Attachment', 'sureforms' ) }
									value={ backgroundAttachment.value }
									onChange={ ( value ) =>
										setAttributes( {
											[ backgroundAttachment.label ]:
												value,
										} )
									}
									options={ [
										{
											value: 'fixed',
											label: __( 'Fixed', 'sureforms' ),
										},
										{
											value: 'scroll',
											label: __( 'Scroll', 'sureforms' ),
										},
									] }
								/>
							</div>
							<div className="srfm-background-image-repeat">
								<SelectControl
									label={ __( 'Repeat', 'sureforms' ) }
									value={ backgroundRepeat.value }
									onChange={ ( value ) =>
										setAttributes( {
											[ backgroundRepeat.label ]: value,
										} )
									}
									options={ [
										{
											value: 'no-repeat',
											label: __(
												'No Repeat',
												'sureforms'
											),
										},
										{
											value: 'repeat',
											label: __( 'Repeat', 'sureforms' ),
										},
										{
											value: 'repeat-x',
											label: __(
												'Repeat-x',
												'sureforms'
											),
										},
										{
											value: 'repeat-y',
											label: __(
												'Repeat-y',
												'sureforms'
											),
										},
									] }
								/>
							</div>
							<div className="srfm-background-image-size">
								<SelectControl
									label={ __( 'Size', 'sureforms' ) }
									value={ backgroundSize.value }
									onChange={ ( value ) =>
										setAttributes( {
											[ backgroundSize.label ]: value,
										} )
									}
									options={ bgSizeOptions }
								/>
								{ 'custom' === backgroundSize.value &&
									backgroundCustomSize && (
									<Range
										label={ __( 'Width', 'sureforms' ) }
										value={
											backgroundCustomSize?.value
										}
										data={ {
											value: backgroundCustomSize?.value,
											label: backgroundCustomSize?.label,
										} }
										min={ 0 }
										limitMax={ {
											px: 1600,
											'%': 100,
											em: 574,
										} }
										unit={ {
											value: backgroundCustomSizeType?.value,
											label: backgroundCustomSizeType?.label,
										} }
										units={ [
											{
												name: __(
													'PX',
													'sureforms'
												),
												unitValue: 'px',
											},
											{
												name: __(
													'%',
													'sureforms'
												),
												unitValue: '%',
											},
											{
												name: __(
													'EM',
													'sureforms'
												),
												unitValue: 'em',
											},
										] }
										displayUnit={ true }
										setAttributes={ setAttributes }
										responsive={ false }
										isFormSpecific={ true }
									/>
								) }
							</div>
						</>
					) }
				</div>
			) }
			{ 'gradient' === backgroundType.value && (
				<div className="srfm-background-gradient">
					<GradientSettings
						backgroundGradient={ props.backgroundGradient }
						gradientType={ props.gradientType }
						setAttributes={ props.setAttributes }
						backgroundGradientColor2={
							props.backgroundGradientColor2
						}
						backgroundGradientColor1={
							props.backgroundGradientColor1
						}
						backgroundGradientType={ props.backgroundGradientType }
						backgroundGradientLocation1={
							props.backgroundGradientLocation1
						}
						backgroundGradientLocation2={
							props.backgroundGradientLocation2
						}
						backgroundGradientAngle={
							props.backgroundGradientAngle
						}
					/>
				</div>
			) }
			{ ! disableOverlay && overlayControls }
		</>
	);
	return (
		<div ref={ panelRef } className="components-base-control">
			<div className="srfm-bg-select-control">
				{ advancedControls }
				<SRFMHelpText text={ help } />
			</div>
		</div>
	);
};

export default Background;
