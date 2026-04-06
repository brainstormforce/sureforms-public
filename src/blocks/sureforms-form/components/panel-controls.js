/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
	faAlignLeft,
	faAlignRight,
	faAlignCenter,
	faAlignJustify,
} from '@fortawesome/free-solid-svg-icons';

/**
 * Internal dependencies
 */
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import Spacing from '@Components/spacing';
import Background from '@Components/enhanced-background';
import MultiButtonsControl from '@Components/multi-buttons-control';

/**
 * Get color panel controls
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to set attributes.
 * @param {Function} props.onSelectImage Function to handle image selection.
 * @return {Array} Array of control objects with id and component.
 */
export const getColorControls = ( {
	attributes,
	setAttributes,
	onSelectImage,
} ) => {
	const {
		primaryColor,
		textColor,
		textOnPrimaryColor,
		bgType,
		bgColor,
		bgGradient,
		bgImage,
		bgImagePosition,
		bgImageSize,
		bgImageRepeat,
		bgImageAttachment,
	} = attributes;

	return applyFilters(
		'srfm.embed.colorControls',
		[
			{
				id: 'primaryColor',
				component: (
					<AdvancedPopColorControl
						label={ __( 'Primary Color', 'sureforms' ) }
						colorValue={ primaryColor }
						data={ {
							value: primaryColor,
							label: 'primaryColor',
						} }
						setAttributes={ setAttributes }
						value={ primaryColor }
					/>
				),
			},
			{
				id: 'textColor',
				component: (
					<AdvancedPopColorControl
						label={ __( 'Text Color', 'sureforms' ) }
						colorValue={ textColor }
						data={ {
							value: textColor,
							label: 'textColor',
						} }
						setAttributes={ setAttributes }
						value={ textColor }
					/>
				),
			},
			{
				id: 'textOnPrimaryColor',
				component: (
					<AdvancedPopColorControl
						label={ __( 'Text on Primary', 'sureforms' ) }
						colorValue={ textOnPrimaryColor }
						data={ {
							value: textOnPrimaryColor,
							label: 'textOnPrimaryColor',
						} }
						setAttributes={ setAttributes }
						value={ textOnPrimaryColor }
					/>
				),
			},
			{
				id: 'background',
				component: (
					<Background
						setAttributes={ setAttributes }
						label={ __( 'Background', 'sureforms' ) }
						backgroundType={ {
							value: bgType,
							label: 'bgType',
						} }
						backgroundColor={ {
							value: bgColor,
							label: 'bgColor',
						} }
						backgroundImage={ {
							value: bgImage,
							label: 'bgImage',
						} }
						backgroundPosition={ {
							value: bgImagePosition,
							label: 'bgImagePosition',
						} }
						backgroundSize={ {
							value: bgImageSize,
							label: 'bgImageSize',
						} }
						backgroundRepeat={ {
							value: bgImageRepeat,
							label: 'bgImageRepeat',
						} }
						backgroundAttachment={ {
							value: bgImageAttachment,
							label: 'bgImageAttachment',
						} }
						backgroundGradient={ {
							value: bgGradient,
							label: 'bgGradient',
						} }
						onSelectImage={ onSelectImage }
						disableOverlay={ true }
					/>
				),
			},
		],
		{ attributes, setAttributes }
	);
};

/**
 * Get layout panel controls
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to set attributes.
 * @return {Array} Array of control objects with id and component.
 */
export const getLayoutControls = ( { attributes, setAttributes } ) => {
	const {
		formPaddingTop,
		formPaddingRight,
		formPaddingBottom,
		formPaddingLeft,
		formPaddingUnit,
		formPaddingLink,
		formBorderRadiusTop,
		formBorderRadiusRight,
		formBorderRadiusBottom,
		formBorderRadiusLeft,
		formBorderRadiusUnit,
		formBorderRadiusLink,
	} = attributes;

	return applyFilters(
		'srfm.embed.layoutControls',
		[
			{
				id: 'padding',
				component: (
					<Spacing
						label={ __( 'Padding', 'sureforms' ) }
						valueTop={ {
							value: formPaddingTop,
							label: 'formPaddingTop',
						} }
						valueRight={ {
							value: formPaddingRight,
							label: 'formPaddingRight',
						} }
						valueBottom={ {
							value: formPaddingBottom,
							label: 'formPaddingBottom',
						} }
						valueLeft={ {
							value: formPaddingLeft,
							label: 'formPaddingLeft',
						} }
						unit={ {
							value: formPaddingUnit,
							label: 'formPaddingUnit',
						} }
						link={ {
							value: formPaddingLink,
							label: 'formPaddingLink',
						} }
						setAttributes={ setAttributes }
						isFormSpecific={ false }
					/>
				),
			},
			{
				id: 'borderRadius',
				component: (
					<Spacing
						label={ __( 'Border Radius', 'sureforms' ) }
						valueTop={ {
							value: formBorderRadiusTop,
							label: 'formBorderRadiusTop',
						} }
						valueRight={ {
							value: formBorderRadiusRight,
							label: 'formBorderRadiusRight',
						} }
						valueBottom={ {
							value: formBorderRadiusBottom,
							label: 'formBorderRadiusBottom',
						} }
						valueLeft={ {
							value: formBorderRadiusLeft,
							label: 'formBorderRadiusLeft',
						} }
						unit={ {
							value: formBorderRadiusUnit,
							label: 'formBorderRadiusUnit',
						} }
						link={ {
							value: formBorderRadiusLink,
							label: 'formBorderRadiusLink',
						} }
						setAttributes={ setAttributes }
						isFormSpecific={ false }
					/>
				),
			},
		],
		{ attributes, setAttributes }
	);
};

/**
 * Get fields panel controls
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to set attributes.
 * @return {Array} Array of control objects with id and component.
 */
export const getFieldControls = ( { attributes, setAttributes } ) => {
	const { fieldSpacing } = attributes;

	return applyFilters(
		'srfm.embed.fieldControls',
		[
			{
				id: 'fieldSpacing',
				component: (
					<MultiButtonsControl
						label={ __( 'Field Spacing', 'sureforms' ) }
						data={ {
							value: fieldSpacing,
							label: 'fieldSpacing',
						} }
						options={ [
							{
								label: __( 'Small', 'sureforms' ),
								value: 'small',
								tooltip: __( 'Small', 'sureforms' ),
							},
							{
								label: __( 'Medium', 'sureforms' ),
								value: 'medium',
								tooltip: __( 'Medium', 'sureforms' ),
							},
							{
								label: __( 'Large', 'sureforms' ),
								value: 'large',
								tooltip: __( 'Large', 'sureforms' ),
							},
						] }
						setAttributes={ setAttributes }
					/>
				),
			},
		],
		{ attributes, setAttributes }
	);
};

/**
 * Get button panel controls
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to set attributes.
 * @return {Array} Array of control objects with id and component.
 */
export const getButtonControls = ( { attributes, setAttributes } ) => {
	const { buttonAlignment } = attributes;

	return applyFilters(
		'srfm.embed.buttonControls',
		[
			{
				id: 'buttonAlignment',
				component: (
					<MultiButtonsControl
						label={ __( 'Button Alignment', 'sureforms' ) }
						data={ {
							value: buttonAlignment,
							label: 'buttonAlignment',
						} }
						options={ [
							{
								value: 'left',
								icon: <FontAwesomeIcon icon={ faAlignLeft } />,
								tooltip: __( 'Left', 'sureforms' ),
							},
							{
								value: 'center',
								icon: (
									<FontAwesomeIcon icon={ faAlignCenter } />
								),
								tooltip: __( 'Center', 'sureforms' ),
							},
							{
								value: 'right',
								icon: <FontAwesomeIcon icon={ faAlignRight } />,
								tooltip: __( 'Right', 'sureforms' ),
							},
							{
								value: 'justify',
								icon: (
									<FontAwesomeIcon icon={ faAlignJustify } />
								),
								tooltip: __( 'Full Width', 'sureforms' ),
							},
						] }
						showIcons={ true }
						setAttributes={ setAttributes }
					/>
				),
			},
		],
		{ attributes, setAttributes }
	);
};
