import { __ } from '@wordpress/i18n';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import { SelectControl } from '@wordpress/components';
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
		xPositionDesktop,
		xPositionType,
		yPositionDesktop,
		yPositionType,
		help = false,
		label = __( 'Type', 'sureforms' ),
	} = props;

	const blockNameForHook = getSelectedBlock()?.name.split( '/' ).pop(); // eslint-disable-line @wordpress/no-unused-vars-before-return
	useEffect( () => {
		setPanelNameForHook( getPanelIdFromRef( panelRef ) );
	}, [ blockNameForHook ] );

	let overlayOptions = [];

	overlayOptions = [
		{
			value: 'none',
			label: __( 'None', 'sureforms' ),
		},
		{
			value: 'color',
			label: __( 'Classic', 'sureforms' ),
		},
	];
	if ( gradientOverlay.value ) {
		overlayOptions.push( {
			value: 'gradient',
			label: __( 'Gradient', 'sureforms' ),
		} );
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

	const setImage =
		imageResponsive &&
		( backgroundImage.desktop?.value ||
			backgroundImage.tablet?.value ||
			backgroundImage.mobile?.value )
			? true
			: false;

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
                            onSelectImage={ ( media ) => onSelectImage( backgroundImage.value, media ) }
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
									label={ __(
										'Image Position',
										'sureforms'
									) }
									value={ backgroundPosition.value }
									onChange={ ( value ) =>
										onHandleChange( {[backgroundPosition.label]: value} ) }
									options={ [
										{
											value: 'left top',
											label: __(
												'Top Left',
												'sureforms'
											),
										},
										{
											value: 'center top',
											label: __(
												'Top Center',
												'sureforms'
											),
										},
										{
											value: 'right top',
											label: __(
												'Top Right',
												'sureforms'
											),
										},
										{
											value: 'center top',
											label: __(
												'Center Top',
												'sureforms'
											),
										},
										{
											value: 'center center',
											label: __(
												'Center Center',
												'sureforms'
											),
										},
										{
											value: 'center bottom',
											label: __(
												'Center Bottom',
												'sureforms'
											),
										},
										{
											value: 'left bottom',
											label: __(
												'Bottom Left',
												'sureforms'
											),
										},
										{
											value: 'center bottom',
											label: __(
												'Bottom Center',
												'sureforms'
											),
										},
										{
											value: 'right bottom',
											label: __(
												'Bottom Right',
												'sureforms'
											),
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
									onChange={ ( value ) => onHandleChange( {[backgroundSize.label]: value} ) }
									options={ bgSizeOptions }
								/>
								{ 'custom' === backgroundSize.value &&
									backgroundCustomSize && (
									<ResponsiveSlider
										label={ __( 'Width', 'sureforms' ) }
										data={ {
											desktop: {
												value: backgroundCustomSize
													.desktop.value,
												label: backgroundCustomSize
													.desktop.label,
											},
											tablet: {
												value: backgroundCustomSize
													.tablet.value,
												label: backgroundCustomSize
													.tablet.label,
											},
											mobile: {
												value: backgroundCustomSize
													.mobile.value,
												label: backgroundCustomSize
													.mobile.label,
											},
										} }
										min={ 0 }
										limitMax={ {
											px: 1600,
											'%': 100,
											em: 574,
										} }
										unit={ {
											value: backgroundCustomSizeType.value,
											label: backgroundCustomSizeType.label,
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
										setAttributes={ setAttributes } // Modified the onHandleChange function.
									/>
								) }
							</div>
						</>
					) }
					{ imageResponsive && backgroundImage && (
						<ResponsiveSRFMImage
							backgroundImage={ backgroundImage }
							setAttributes={ setAttributes }
						/>
					) }
					{ imageResponsive && backgroundImage && setImage && (
						<>
							<div className="srfm-background-image-position">
								<MultiButtonsControl
									onChange={ ( value ) => onHandleChange( {[customPosition.label]: value} ) }
									label={ __(
										'Image Position',
										'sureforms'
									) }
									data={ {
										value: customPosition.value,
										label: customPosition.label,
									} }
									options={ [
										{
											value: 'default',
											label: __( 'Default', 'sureforms' ),
										},
										{
											value: 'custom',
											label: __( 'Custom', 'sureforms' ),
										},
									] }
								/>
							</div>
							{ 'custom' !== customPosition.value && (
								<div className="srfm-background-image-position">
									<ResponsiveSRFMFocalPointPicker
										backgroundPosition={
											backgroundPosition
										}
										setAttributes={ setAttributes }
										backgroundImage={ backgroundImage }
									/>
								</div>
							) }
							{ 'custom' === customPosition.value && (
								<>
									<div className="srfm-background-image-position">
										<ResponsiveSlider
											label={ __(
												'X Position',
												'sureforms'
											) }
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
											limitMin={ {
												px: -800,
												'%': -100,
												em: -100,
												vw: -100,
											} }
											limitMax={ {
												px: 800,
												'%': 100,
												em: 100,
												vw: 100,
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
												{
													name: __(
														'VW',
														'sureforms'
													),
													unitValue: 'vw',
												},
											] }
											setAttributes={ setAttributes }
										/>
									</div>
									<div className="srfm-background-image-position">
										<ResponsiveSlider
											label={ __(
												'Y Position',
												'sureforms'
											) }
											data={ {
												desktop: {
													value: yPositionDesktop.value,
													label: 'yPositionDesktop',
													unit: {
														value: yPositionType.value,
														label: 'yPositionType',
													},
												},
											} }
											limitMin={ {
												px: -800,
												'%': -100,
												em: -100,
												vh: -100,
											} }
											limitMax={ {
												px: 800,
												'%': 100,
												em: 100,
												vh: 100,
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
												{
													name: __(
														'VH',
														'sureforms'
													),
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
												label: __(
													'Fixed',
													'sureforms'
												),
											},
											{
												value: 'scroll',
												label: __(
													'Scroll',
													'sureforms'
												),
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
												label: __(
													'No Repeat',
													'sureforms'
												),
											},
											{
												value: 'repeat',
												label: __(
													'Repeat',
													'sureforms'
												),
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
												label: __(
													'Auto',
													'sureforms'
												),
											},
											{
												value: 'cover',
												label: __(
													'Cover',
													'sureforms'
												),
											},
											{
												value: 'contain',
												label: __(
													'Contain',
													'sureforms'
												),
											},
											{
												value: 'custom',
												label: __(
													'Custom',
													'sureforms'
												),
											},
										],
									} }
									onChange={ ( value ) => onHandleChange( {[backgroundSize.label]: value} ) }
								/>
								{ 'custom' ===
									backgroundSize[ deviceType ].value &&
									backgroundCustomSize && (
									<ResponsiveSlider
										label={ __( 'Width', 'sureforms' ) }
										data={ backgroundCustomSize }
										min={ 0 }
										limitMax={ {
											px: 1600,
											'%': 100,
											em: 574,
										} }
										unit={ {
											value: backgroundCustomSizeType.value,
											label: backgroundCustomSizeType.label,
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
                                        onChange={ ( value ) => onHandleChange( {[backgroundCustomSizeType.label]: value} ) }
									/>
								) }
							</div>
						</>
					) }
					{ overlayType &&
						backgroundImage &&
						( ( imageResponsive && setImage ) ||
							( ! imageResponsive &&
								backgroundImage?.value ) ) && (
						<>
							<div className="srfm-background-image-overlay-type">
								<MultiButtonsControl
									setAttributes={ setAttributes }
									label={ __(
										'Overlay Type',
										'sureforms'
									) }
									data={ {
										value: overlayType.value,
										label: overlayType.label,
									} }
									className="srfm-multi-button-alignment-control"
									options={ overlayOptions }
									showIcons={ false }
								/>
							</div>
							{ 'color' === overlayType.value && (
								<div className="srfm-background-image-overlay-color">
									<AdvancedPopColorControl
										label={ __(
											'Image Overlay Color',
											'sureforms'
										) }
										colorValue={
											backgroundImageColor.value
										}
										data={ {
											value: backgroundImageColor.value,
											label: backgroundImageColor.label,
										} }
										setAttributes={ setAttributes }
									/>
								</div>
							) }
							{ 'gradient' === overlayType.value && (
								<div className="srfm-background-image-overlay-gradient">
									<GradientSettings
										backgroundGradient={
											props.backgroundGradient
										}
										setAttributes={ props.setAttributes }
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
							) }
						</>
					) }
				</div>
			) }
			{ gradientOverlay.value && 'gradient' === backgroundType.value && (
				<div className="srfm-background-gradient">
					<GradientSettings
						backgroundGradient={ props.backgroundGradient }
						gradientType={ props.gradientType }
                        setAttributes={ props.setAttributes } // Modified the onHandleChange function.
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
