import { __ } from '@wordpress/i18n';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import { SelectControl, ToggleControl } from '@wordpress/components';
import styles from './editor.lazy.scss';
import GradientSettings from '@Components/gradient-settings';
import {
	useEffect,
	useState,
	useRef,
	useLayoutEffect,
} from '@wordpress/element';
import SRFMMediaPicker from '@Components/image';
import ResponsiveSlider from '@Components/responsive-slider';
import ResponsiveSelectControl from '@Components/responsive-select';
import { useDeviceType } from '@Controls/getPreviewType';
import ResponsiveSRFMImage from '@Components/responsive-image';
import ResponsiveSRFMFocalPointPicker from '@Components/responsive-focal-point-picker';
import MultiButtonsControl from '@Components/multi-buttons-control';
import SRFM_Block_Icons from '@Controls/block-icons';
import { getPanelIdFromRef } from '@Utils/Helpers';
import { select } from '@wordpress/data';
import SRFMHelpText from '@Components/help-text';
import { applyFilters } from '@wordpress/hooks';
import Range from '@Components/range/Range';
import Separator from '@Components/separator';

const Background = ( props ) => {
	const { getSelectedBlock } = select( 'core/block-editor' );
	const [ panelNameForHook, setPanelNameForHook ] = useState( null );
	const panelRef = useRef( null );

	// Add and remove the CSS on the drop and remove of the component.
	useLayoutEffect( () => {
		styles?.use();
		return () => {
			styles?.unuse();
		};
	}, [] );

	const deviceType = useDeviceType().toLowerCase();

	const {
		setAttributes,
        onHandleChange,
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
		imageResponsive,
		gradientOverlay,
		customPosition,
		centralizedPosition,
		xPositionDesktop,
		xPositionType,
		yPositionDesktop,
		yPositionType,
		help = false,
		backgroundOverlaySize,
		backgroundOverlayRepeat,
		backgroundOverlayAttachment,
		backgroundOverlayPosition,
		backgroundOverlayImage,
		backgroundOverlayCustomSize,
		backgroundOverlayCustomSizeType,
		customOverlayPosition,
		xPositionOverlayDesktop,
		xPositionOverlayType,
		yPositionOverlayDesktop,
		yPositionOverlayTablet,
		yPositionOverlayMobile,
		yPositionOverlayType,
		yPositionOverlayTypeTablet,
		yPositionOverlayTypeMobile,
		overlayBlendMode,
		imageOverlayResponsive,
		label = __( 'Type', 'sureforms' ),
	} = props;

	const blockNameForHook = getSelectedBlock()?.name.split( '/' ).pop(); // eslint-disable-line @wordpress/no-unused-vars-before-return
	useEffect( () => {
		setPanelNameForHook( getPanelIdFromRef( panelRef ) );
	}, [ blockNameForHook ] );

	let overlayOptions = [];

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
                },
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

    // Using backgroundImage.value instead of backgroundImage.desktop.value
	const setImage =
		imageResponsive && backgroundImage?.value
			? true
			: false;

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
					/>
				</div>
		);
	};

	//Render Common Overlay Controls.
	const renderOverlayImageControls = () => {
		const onRemoveOverlayImage = () => {
			setAttributes( { [ backgroundOverlayImage.label ]: null } );
		};

		const onSelectOverlayImage = ( media ) => {
			if ( ! media || ! media.url ) {
				setAttributes( { [ backgroundOverlayImage.label ]: null } );
				return;
			}

			if ( ! media.type || 'image' !== media.type ) {
				return;
			}

			setAttributes( { [ backgroundOverlayImage.label ]: media } );
		};

		const setOverlayImage =
			imageOverlayResponsive &&
			( backgroundOverlayImage.desktop?.value ||
				backgroundOverlayImage.tablet?.value ||
				backgroundOverlayImage.mobile?.value )
				? true
				: false;

		return (
			<>
				<div className="srfm-background-image">
					{ ! imageOverlayResponsive && (
						<SRFMMediaPicker
							onSelectImage={ onSelectOverlayImage }
							backgroundOverlayImage={ backgroundOverlayImage.value }
							onRemoveImage={ onRemoveOverlayImage }
							disableLabel={ true }
						/>
					) }
					{ ! imageOverlayResponsive && backgroundOverlayImage.value && (
						<>
							<div className="srfm-background-image-position">
								<SelectControl
									label={ __( 'Image Position', 'sureforms' ) }
									value={ backgroundOverlayPosition.value }
									onChange={ ( value ) =>
										setAttributes( {
											[ backgroundOverlayPosition.label ]: value,
										} )
									}
									options={ [
										{
											value: 'left top',
											label: __( 'Top Left', 'sureforms' ),
										},
										{
											value: 'center top',
											label: __( 'Top Center', 'sureforms' ),
										},
										{
											value: 'right top',
											label: __( 'Top Right', 'sureforms' ),
										},
										{
											value: 'center top',
											label: __( 'Center Top', 'sureforms' ),
										},
										{
											value: 'center center',
											label: __( 'Center Center', 'sureforms' ),
										},
										{
											value: 'center bottom',
											label: __( 'Center Bottom', 'sureforms' ),
										},
										{
											value: 'left bottom',
											label: __( 'Bottom Left', 'sureforms' ),
										},
										{
											value: 'center bottom',
											label: __( 'Bottom Center', 'sureforms' ),
										},
										{
											value: 'right bottom',
											label: __( 'Bottom Right', 'sureforms' ),
										},
									] }
								/>
							</div>
							<div className="srfm-background-image-attachment">
								<SelectControl
									label={ __( 'Attachment', 'sureforms' ) }
									value={ backgroundOverlayAttachment.value }
									onChange={ ( value ) =>
										setAttributes( {
											[ backgroundOverlayAttachment.label ]: value,
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
											label: __( 'Multiply', 'sureforms' ),
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
											label: __( 'Color Dodge', 'sureforms' ),
										},
										{
											value: 'saturation',
											label: __( 'Saturation', 'sureforms' ),
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
											[ backgroundOverlayRepeat.label ]: value,
										} )
									}
									options={ [
										{
											value: 'no-repeat',
											label: __( 'No Repeat', 'sureforms' ),
										},
										{
											value: 'repeat',
											label: __( 'Repeat', 'sureforms' ),
										},
										{
											value: 'repeat-x',
											label: __( 'Repeat-x', 'sureforms' ),
										},
										{
											value: 'repeat-y',
											label: __( 'Repeat-y', 'sureforms' ),
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
											[ backgroundOverlaySize.label ]: value,
										} )
									}
									options={ bgSizeOptions }
								/>
								{ 'custom' === backgroundOverlaySize.value && backgroundOverlayCustomSize && (
									<ResponsiveSlider
										label={ __( 'Width', 'sureforms' ) }
										data={ {
											desktop: {
												value: backgroundOverlayCustomSize.desktop.value,
												label: backgroundOverlayCustomSize.desktop.label,
											},
											tablet: {
												value: backgroundOverlayCustomSize.tablet.value,
												label: backgroundOverlayCustomSize.tablet.label,
											},
											mobile: {
												value: backgroundOverlayCustomSize.mobile.value,
												label: backgroundOverlayCustomSize.mobile.label,
											},
										} }
										min={ 0 }
										limitMax={ { 'px': 1600, '%': 100, 'em': 574 } }
										unit={ {
											value: backgroundOverlayCustomSizeType.value,
											label: backgroundOverlayCustomSizeType.label,
										} }
										units={ [
											{
												name: __( 'PX', 'sureforms' ),
												unitValue: 'px',
											},
											{
												name: __( '%', 'sureforms' ),
												unitValue: '%',
											},
											{
												name: __( 'EM', 'sureforms' ),
												unitValue: 'em',
											},
										] }
										setAttributes={ setAttributes }
									/>
								) }
							</div>
						</>
					) }
					{ imageOverlayResponsive && backgroundOverlayImage && (
						<ResponsiveSRFMImage
							backgroundImage={ backgroundOverlayImage }
							setAttributes={ setAttributes }
						/>
					) }
					{ imageOverlayResponsive && backgroundOverlayImage && setOverlayImage && (
						<>
							<div className="srfm-background-image-position">
								<MultiButtonsControl
									setAttributes={ setAttributes }
									label={ __( 'Image Position', 'sureforms' ) }
									data={ {
										value: customOverlayPosition.value,
										label: customOverlayPosition.label,
									} }
									options={ [
										{ value: 'default', label: __( 'Default', 'sureforms' ) },
										{ value: 'custom', label: __( 'Custom', 'sureforms' ) },
									] }
								/>
							</div>
							{ 'custom' !== customOverlayPosition.value && (
								<div className="srfm-background-image-position">
									<ResponsiveSRFMFocalPointPicker
										backgroundPosition={ backgroundOverlayPosition }
										setAttributes={ setAttributes }
										backgroundImage={ backgroundOverlayImage }
									/>
								</div>
							) }
							{ 'custom' === customOverlayPosition.value && (
								<>
									<div className="srfm-background-image-position">
										<ResponsiveSlider
											label={ __( 'X Position', 'sureforms' ) }
											data={ {
												desktop: {
													value: xPositionOverlayDesktop.value,
													label: 'xPositionOverlayDesktop',
													unit: {
														value: xPositionOverlayType.value,
														label: 'xPositionOverlayType',
													},
												},
											} }
											limitMin={ { 'px': -800, '%': -100, 'em': -100, 'vw': -100 } }
											limitMax={ { 'px': 800, '%': 100, 'em': 100, 'vw': 100 } }
											units={ [
												{
													name: __( 'PX', 'sureforms' ),
													unitValue: 'px',
												},
												{
													name: __( '%', 'sureforms' ),
													unitValue: '%',
												},
												{
													name: __( 'EM', 'sureforms' ),
													unitValue: 'em',
												},
												{
													name: __( 'VW', 'sureforms' ),
													unitValue: 'vw',
												},
											] }
											setAttributes={ setAttributes }
										/>
									</div>
									<div className="srfm-background-image-position">
										<ResponsiveSlider
											label={ __( 'Y Position', 'sureforms' ) }
											data={ {
												desktop: {
													value: yPositionOverlayDesktop.value,
													label: 'yPositionOverlayDesktop',
													unit: {
														value: yPositionOverlayType.value,
														label: 'yPositionOverlayType',
													},
												},
												tablet: {
													value: yPositionOverlayTablet.value,
													label: 'yPositionOverlayTablet',
													unit: {
														value: yPositionOverlayTypeTablet.value,
														label: 'yPositionOverlayTypeTablet',
													},
												},
												mobile: {
													value: yPositionOverlayMobile.value,
													label: 'yPositionOverlayMobile',
													unit: {
														value: yPositionOverlayTypeMobile.value,
														label: 'yPositionOverlayTypeMobile',
													},
												},
											} }
											limitMin={ { 'px': -800, '%': -100, 'em': -100, 'vh': -100 } }
											limitMax={ { 'px': 800, '%': 100, 'em': 100, 'vh': 100 } }
											units={ [
												{
													name: __( 'PX', 'sureforms' ),
													unitValue: 'px',
												},
												{
													name: __( '%', 'sureforms' ),
													unitValue: '%',
												},
												{
													name: __( 'EM', 'sureforms' ),
													unitValue: 'em',
												},
												{
													name: __( 'VH', 'sureforms' ),
													unitValue: 'vh',
												},
											] }
											setAttributes={ setAttributes }
										/>
									</div>
								</>
							) }
							<div className="srfm-background-image-attachment">
								<ResponsiveSelectControl
									label={ __( 'Attachment', 'sureforms' ) }
									data={ backgroundOverlayAttachment }
									options={ {
										desktop: [
											{
												value: 'fixed',
												label: __( 'Fixed', 'sureforms' ),
											},
											{
												value: 'scroll',
												label: __( 'Scroll', 'sureforms' ),
											},
										],
									} }
									setAttributes={ setAttributes }
								/>
							</div>
							<div className="srfm-background-blend-mode">
								<ResponsiveSelectControl
									label={ __( 'Blend Mode', 'sureforms' ) }
									data={ overlayBlendMode }
									options={ {
										desktop: [
											{
												value: 'normal',
												label: __( 'Normal', 'sureforms' ),
											},
											{
												value: 'multiply',
												label: __( 'Multiply', 'sureforms' ),
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
												label: __( 'Color Dodge', 'sureforms' ),
											},
											{
												value: 'saturation',
												label: __( 'Saturation', 'sureforms' ),
											},
											{
												value: 'color',
												label: __( 'Color', 'sureforms' ),
											},
										],
									} }
									setAttributes={ setAttributes }
								/>
							</div>
							<div className="srfm-background-image-repeat">
								<ResponsiveSelectControl
									label={ __( 'Repeat', 'sureforms' ) }
									data={ backgroundOverlayRepeat }
									options={ {
										desktop: [
											{
												value: 'no-repeat',
												label: __( 'No Repeat', 'sureforms' ),
											},
											{
												value: 'repeat',
												label: __( 'Repeat', 'sureforms' ),
											},
											{
												value: 'repeat-x',
												label: __( 'Repeat-x', 'sureforms' ),
											},
											{
												value: 'repeat-y',
												label: __( 'Repeat-y', 'sureforms' ),
											},
										],
									} }
									setAttributes={ setAttributes }
								/>
							</div>
							<div className="srfm-background-image-size">
								<ResponsiveSelectControl
									label={ __( 'Size', 'sureforms' ) }
									data={ backgroundOverlaySize }
									options={ {
										desktop: [
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
										],
									} }
									setAttributes={ setAttributes }
								/>
								{ 'custom' === backgroundOverlaySize[ deviceType ].value &&
									backgroundOverlayCustomSize && (
										<ResponsiveSlider
											label={ __( 'Width', 'sureforms' ) }
											data={ backgroundOverlayCustomSize }
											min={ 0 }
											limitMax={ { 'px': 1600, '%': 100, 'em': 572 } }
											unit={ {
												value: backgroundOverlayCustomSizeType.value,
												label: backgroundOverlayCustomSizeType.label,
											} }
											units={ [
												{
													name: __( 'PX', 'sureforms' ),
													unitValue: 'px',
												},
												{
													name: __( '%', 'sureforms' ),
													unitValue: '%',
												},
												{
													name: __( 'EM', 'sureforms' ),
													unitValue: 'em',
												},
											] }
											setAttributes={ setAttributes }
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
						value: overlayType.value,
						label: overlayType.label,
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
			{ ( ( backgroundType.value === 'color' && backgroundColor.value ) ||
				( backgroundType.value === 'gradient' && gradientOverlay.value ) ) && (
				<>
					{ buttonControl }
					{ 'image' === overlayType.value && renderOverlayImageControls() }
				</>
			) }
			{ backgroundType.value === 'image' &&
				( ( imageResponsive && setImage ) || ( ! imageResponsive && backgroundImage?.value ) ) && (
					<>
						{ buttonControl }
						{ 'color' === overlayType.value && (
							<>
								<div className="srfm-background-image-overlay-color">
									<AdvancedPopColorControl
										label={ __( 'Image Overlay Color', 'sureforms' ) }
										colorValue={ backgroundImageColor.value }
										data={ {
											value: backgroundImageColor.value,
											label: backgroundImageColor.label,
										} }
										setAttributes={ setAttributes }
									/>
								</div>
								{ renderOverlayControls() }
							</>
						) }
						{ 'gradient' === overlayType.value && (
							<>
								<div className="srfm-background-image-overlay-gradient">
                                <GradientSettings
										backgroundGradient={
											props.backgroundOverlayGradient
										}
										setAttributes={ setAttributes }
										gradientType={ props.gradientType }
										backgroundGradientColor2={
											props.backgroundGradientColor2
										}
										backgroundGradientColor1={
											props.backgroundGradientColor1
										}
										backgroundGradientType={
											props.backgroundGradientType
										}
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
								{ renderOverlayControls() }
							</>
						) }
						{ 'image' === overlayType.value && renderOverlayImageControls() }
					</>
				) }
                <Separator />
		</>
	);

	const advancedControls = (
		<>
			<MultiButtonsControl
				label={ label }
				data={ {
					value: backgroundType.value,
					label: backgroundType.label,
				} }
				options={ bgIconOptions }
				showIcons={ true }
				colorVariant="secondary"
				layoutVariant="inline"
                onChange={ ( value ) => onHandleChange( { [ backgroundType.label ]: value } ) }
			/>
			{ 'color' === backgroundType.value && (
				<div className="srfm-background-color">
                    <AdvancedPopColorControl
                        label={ __( 'Background Color', 'sureforms' ) }
                        colorValue={ backgroundColor.value }
                        data={ {
                            value: backgroundColor.value,
                            label: backgroundColor.label,
                        } }
                        onColorChange={ ( colorValue ) => onHandleChange( { [backgroundColor.label]: colorValue } ) }
                        value={ backgroundColor.value }
                        isFormSpecific={ true }
                    />
				</div>
			) }
			{ 'image' === backgroundType.value && (
				<div className="srfm-background-image">
					{ ! imageResponsive && (
                        <SRFMMediaPicker
                            onSelectImage={ ( media ) => onSelectImage( backgroundImage.label, media ) }
                            backgroundImage={ backgroundImage.value }
                            onRemoveImage={ () => onHandleChange( {[backgroundImage.label]: ''} ) }
                            isFormSpecific={ true }
                            disableLabel={ true }
                        />
					) }
					{ ! imageResponsive && backgroundImage.value && (
						<>
							<div className="srfm-background-image-position">
								<SelectControl
									label={ __( 'Image Position', 'sureforms' ) }
									value={ backgroundPosition.value }
									onChange={ ( value ) =>
										onHandleChange( {[backgroundPosition.label]: value} ) }
									options={ [
										{
											value: 'left top',
											label: __( 'Top Left', 'sureforms' ),
										},
										{
											value: 'center top',
											label: __( 'Top Center', 'sureforms' ),
										},
										{
											value: 'right top',
											label: __( 'Top Right', 'sureforms' ),
										},
										{
											value: 'center top',
											label: __( 'Center Top', 'sureforms' ),
										},
										{
											value: 'center center',
											label: __( 'Center Center', 'sureforms' ),
										},
										{
											value: 'center bottom',
											label: __( 'Center Bottom', 'sureforms' ),
										},
										{
											value: 'left bottom',
											label: __( 'Bottom Left', 'sureforms' ),
										},
										{
											value: 'center bottom',
											label: __( 'Bottom Center', 'sureforms' ),
										},
										{
											value: 'right bottom',
											label: __( 'Bottom Right', 'sureforms' ),
										},
									] }
								/>
							</div>
							<div className="srfm-background-image-attachment">
								<SelectControl
									label={ __( 'Attachment', 'sureforms' ) }
									value={ backgroundAttachment.value }
									onChange={ ( value ) => onHandleChange( {[backgroundAttachment.label]: value} ) }
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
									onChange={ ( value ) => onHandleChange( {[backgroundRepeat.label]: value} ) }
									options={ [
										{
											value: 'no-repeat',
											label: __( 'No Repeat', 'sureforms' ),
										},
										{
											value: 'repeat',
											label: __( 'Repeat', 'sureforms' ),
										},
										{
											value: 'repeat-x',
											label: __( 'Repeat-x', 'sureforms' ),
										},
										{
											value: 'repeat-y',
											label: __( 'Repeat-y', 'sureforms' ),
										},
									] }
								/>
							</div>
							<div className="srfm-background-image-size">
								<SelectControl
									label={ __( 'Size', 'sureforms' ) }
									value={ backgroundSize.value }
									onChange={ ( value ) => onHandleChange( {[backgroundSize.label]: value} ) }
									options={ bgSizeOptions }
								/>
								{ 'custom' === backgroundSize.value && backgroundCustomSize && (
									<ResponsiveSlider
										label={ __( 'Width', 'sureforms' ) }
										data={ {
											desktop: {
												value: backgroundCustomSize.desktop.value,
												label: backgroundCustomSize.desktop.label,
											},
											tablet: {
												value: backgroundCustomSize.tablet.value,
												label: backgroundCustomSize.tablet.label,
											},
											mobile: {
												value: backgroundCustomSize.mobile.value,
												label: backgroundCustomSize.mobile.label,
											},
										} }
										min={ 0 }
										limitMax={ { 'px': 1600, '%': 100, 'em': 571 } }
										unit={ {
											value: backgroundCustomSizeType.value,
											label: backgroundCustomSizeType.label,
										} }
										units={ [
											{
												name: __( 'PX', 'sureforms' ),
												unitValue: 'px',
											},
											{
												name: __( '%', 'sureforms' ),
												unitValue: '%',
											},
											{
												name: __( 'EM', 'sureforms' ),
												unitValue: 'em',
											},
										] }
										setAttributes={ setAttributes } // Modified the onHandleChange function.
									/>
								) }
							</div>
						</>
					) }
					{ imageResponsive && backgroundImage && (
						<ResponsiveSRFMImage backgroundImage={ backgroundImage } setAttributes={ setAttributes } />
					) }
					{ imageResponsive && backgroundImage && setImage && (
						<>
							<div className="srfm-background-image-position">
								<MultiButtonsControl
									onChange={ ( value ) => onHandleChange( {[customPosition.label]: value} ) }
									label={ __( 'Image Position', 'sureforms' ) }
									data={ {
										value: customPosition.value,
										label: customPosition.label,
									} }
									options={ [
										{ value: 'default', label: __( 'Default', 'sureforms' ) },
										{ value: 'custom', label: __( 'Custom', 'sureforms' ) },
									] }
								/>
							</div>
							{ 'custom' !== customPosition.value && (
								<div className="srfm-background-image-position">
									<ResponsiveSRFMFocalPointPicker
										backgroundPosition={ backgroundPosition }
										setAttributes={ setAttributes }
										backgroundImage={ backgroundImage }
									/>
								</div>
							) }
							{ 'custom' === customPosition.value && (
								<>
									<div className="srfm-background-image-position">
										{ (
											<div className="srfm-background-image-axis-position">
												<ToggleControl
													label={ __(
														'Centralized Position',
														'sureforms'
													) }
													checked={ centralizedPosition.value }
													onChange={ () =>
														setAttributes( {
															[ centralizedPosition.label ]: ! centralizedPosition.value,
														} )
													}
												/>
											</div>
										) }

										<ResponsiveSlider
											label={ __( 'X Position', 'sureforms' ) }
											data={ {
												desktop: {
													value: xPositionDesktop.value,
													label: 'xPositionDesktop',
													unit: {
														value: xPositionType.value,
														label: 'xPositionType',
													},
												},
											} }
											limitMin={ { 'px': -1000, '%': -100, 'em': -100, 'vw': -100 } }
											limitMax={ { 'px': 1000, '%': 100, 'em': 100, 'vw': 100 } }
											units={ [
												{
													name: __( 'PX', 'sureforms' ),
													unitValue: 'px',
												},
												{
													name: __( '%', 'sureforms' ),
													unitValue: '%',
												},
												{
													name: __( 'EM', 'sureforms' ),
													unitValue: 'em',
												},
												{
													name: __( 'VW', 'sureforms' ),
													unitValue: 'vw',
												},
											] }
											setAttributes={ setAttributes }
										/>
									</div>
									<div className="srfm-background-image-position">
										<ResponsiveSlider
											label={ __( 'Y Position', 'sureforms' ) }
											data={ {
												desktop: {
													value: yPositionDesktop.value,
													label: yPositionDesktop.label,
													unit: {
														value: yPositionType.value,
														label: yPositionType.label,
													},
												},
											} }
											limitMin={ { 'px': -800, '%': -100, 'em': -100, 'vh': -100 } }
											limitMax={ { 'px': 800, '%': 100, 'em': 100, 'vh': 100 } }
											units={ [
												{
													name: __( 'PX', 'sureforms' ),
													unitValue: 'px',
												},
												{
													name: __( '%', 'sureforms' ),
													unitValue: '%',
												},
												{
													name: __( 'EM', 'sureforms' ),
													unitValue: 'em',
												},
												{
													name: __( 'VH', 'sureforms' ),
													unitValue: 'vh',
												},
											] }
											setAttributes={ setAttributes }
										/>
									</div>
								</>
							) }
							<div className="srfm-background-image-attachment">
								<ResponsiveSelectControl
									label={ __( 'Attachment', 'sureforms' ) }
									data={ backgroundAttachment }
									options={ {
										desktop: [
											{
												value: 'fixed',
												label: __( 'Fixed', 'sureforms' ),
											},
											{
												value: 'scroll',
												label: __( 'Scroll', 'sureforms' ),
											},
										],
									} }
									setAttributes={ setAttributes }
								/>
							</div>
							<div className="srfm-background-image-repeat">
								<ResponsiveSelectControl
									label={ __( 'Repeat', 'sureforms' ) }
									data={ backgroundRepeat }
									options={ {
										desktop: [
											{
												value: 'no-repeat',
												label: __( 'No Repeat', 'sureforms' ),
											},
											{
												value: 'repeat',
												label: __( 'Repeat', 'sureforms' ),
											},
											{
												value: 'repeat-x',
												label: __( 'Repeat-x', 'sureforms' ),
											},
											{
												value: 'repeat-y',
												label: __( 'Repeat-y', 'sureforms' ),
											},
										],
									} }
									setAttributes={ setAttributes }
								/>
							</div>
							<div className="srfm-background-image-size">
								<ResponsiveSelectControl
									label={ __( 'Size', 'sureforms' ) }
									data={ backgroundSize }
									options={ {
										desktop: [
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
										],
									} }
									onChange={ ( value ) => onHandleChange( {[backgroundSize.label]: value} ) }
								/>
                                {/* Changed backgroundSize[deviceType].value since responsive support is not provided. */}
								{ 'custom' === backgroundSize.value && backgroundCustomSize && (
									<ResponsiveSlider
										label={ __( 'Width', 'sureforms' ) }
										data={ backgroundCustomSize }
										min={ 0 }
										limitMax={ { 'px': 1600, '%': 100, 'em': 574 } }
										unit={ {
											value: backgroundCustomSizeType.value,
											label: backgroundCustomSizeType.label,
										} }
										units={ [
											{
												name: __( 'PX', 'sureforms' ),
												unitValue: 'px',
											},
											{
												name: __( '%', 'sureforms' ),
												unitValue: '%',
											},
											{
												name: __( 'EM', 'sureforms' ),
												unitValue: 'em',
											},
										] }
                                        onChange={ ( value ) => onHandleChange( {[backgroundCustomSizeType.label]: value} ) }
									/>
								) }
							</div>
						</>
					) }
				</div>
			) }
			{ gradientOverlay.value && 'gradient' === backgroundType.value && (
				<div className="srfm-background-gradient">
					<GradientSettings
						backgroundGradient={ props.backgroundGradient }
						gradientType={ props.gradientType }
						setAttributes={ props.setAttributes }
						backgroundGradientColor2={ props.backgroundGradientColor2 }
						backgroundGradientColor1={ props.backgroundGradientColor1 }
						backgroundGradientType={ props.backgroundGradientType }
						backgroundGradientLocation1={ props.backgroundGradientLocation1 }
						backgroundGradientLocation2={ props.backgroundGradientLocation2 }
						backgroundGradientAngle={ props.backgroundGradientAngle }
					/>
				</div>
			) }
            { overlayControls }
		</>
	);
	const controlName = 'background'; // there is no label props that's why keep hard coded label
	const controlBeforeDomElement = applyFilters(
		`srfm.${ blockNameForHook }.${ panelNameForHook }.${ controlName }.before`,
		'',
		blockNameForHook
	);
	const controlAfterDomElement = applyFilters(
		`srfm.${ blockNameForHook }.${ panelNameForHook }.${ controlName }`,
		'',
		blockNameForHook
	);

	return (
		<div ref={ panelRef } className="components-base-control">
			{ controlBeforeDomElement }
			<div className="srfm-bg-select-control">
				{ advancedControls }
				<SRFMHelpText text={ help } />
			</div>
			{ controlAfterDomElement }
		</div>
	);
};

export default Background;
