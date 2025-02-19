import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import MultiButtonsControl from '@Components/multi-buttons-control';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
	faAlignLeft,
	faAlignRight,
	faAlignCenter,
	faAlignJustify,
} from '@fortawesome/free-solid-svg-icons';
import { useDeviceType } from '@Controls/getPreviewType';
import { getStylePanels } from '@Components/hooks';
import { addStyleInRoot } from '@Utils/Helpers';
import { chevronDown } from '@wordpress/icons';
import PremiumBadge from '@Admin/components/PremiumBadge';
import Background from '@Components/enhanced-background';

function StyleSettings( props ) {
	const { editPost } = useDispatch( editorStore );
	const { defaultKeys, isInlineButtonBlockPresent } = props;

	let sureformsKeys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);
	const formStyling = sureformsKeys?._srfm_forms_styling || {};
	const root = document.documentElement.querySelector( 'body' );
	const editor = root.querySelector( '.editor-styles-wrapper' );
	const deviceType = useDeviceType();
	const [ submitBtn, setSubmitBtn ] = useState(
		document.querySelector( '.srfm-submit-richtext' )
	);
	const [ submitBtnCtn, setSubmitBtnCtn ] = useState(
		document.querySelector( '.srfm-submit-btn-container' )
	);
	const [ fieldSpacing, setFieldSpacing ] = useState( formStyling?.field_spacing || 'medium' );

	const {
		// Background Properties
		bg_type,
		bg_color,
		bg_image,
		bg_image_position,
		bg_image_attachment,
		bg_image_repeat,
		bg_image_size,
		bg_image_size_custom,
		bg_image_size_custom_type,
		// Gradient Properties
		gradient_type,
		bg_gradient_color_1,
		bg_gradient_color_2,
		bg_gradient_location_1,
		bg_gradient_location_2,
		bg_gradient_angle,
		bg_gradient_type,
		bg_gradient,
		// Overlay Properties
		bg_gradient_overlay_type,
		bg_overlay_opacity,
		bg_image_overlay_color,
		bg_overlay_image,
		bg_overlay_position,
		bg_overlay_attachment,
		overlay_blend_mode,
		bg_overlay_repeat,
		bg_overlay_size,
		bg_overlay_custom_size,
		bg_overlay_custom_size_type,
	} = formStyling;

	// Apply the sizings when field spacing changes.
	useEffect( () => {
		applyFieldSpacing( fieldSpacing );
	}, [ fieldSpacing ] );

	// if device type is desktop then change the submit button
	useEffect( () => {
		setTimeout( () => {
			setSubmitBtnCtn(
				document.querySelector( '.srfm-submit-btn-container' )
			);
			setSubmitBtn( document.querySelector( '.srfm-submit-richtext' ) );
			submitButtonInherit();
		}, 1000 );
	}, [ deviceType, submitBtn, sureformsKeys._srfm_inherit_theme_button ] );

	const onHandleChange = ( updatedSettings ) => {
		const [ key, value ] = Object.entries( updatedSettings )[ 0 ];
		if ( formStyling?.[ key ] === value ) {
			// Do not re-render if the value is same. This is necessary for color picker type controls which re-render on selection.
			return;
		}

		addStyleInRoot( root, getCSSProperties( key, value ) );
		const formStylingSettings = {
			...formStyling,
			[ key ]: value,
		};

		editPost( {
			meta: {
				_srfm_forms_styling: formStylingSettings,
			},
		} );
	};

	const [ gradientOptions, setGradientOptions ] = useState( {
		type: bg_gradient_type || 'linear',
		color_1: bg_gradient_color_1 || '#FFC9B2',
		color_2: bg_gradient_color_2 || '#C7CBFF',
		location_1: bg_gradient_location_1 || 0,
		location_2: bg_gradient_location_2 || 100,
		angle: bg_gradient_angle || 90,
	} );

	/**
	 * Handles the selection of an image and updates the post metadata with the selected image's URL and ID.
	 *
	 * This function performs the following steps:
	 * 1. Checks if the provided `media` object is valid and of type 'image'.
	 * 2. If valid, it extracts the image's ID and URL, and then updates the post metadata with this information.
	 * 3. If the `media` object is not valid or is not an image, it sets the image URL to `null`.
	 *
	 * @param {string} key   - The key used to identify the metadata field for the image URL in the post metadata.
	 * @param {Object} media - The media object representing the selected image.
	 */
	const onImageSelect = ( key, media ) => {
		let key_id = '';
		let imageID = 0;
		let imageURL = media;

		if (
			! media ||
			! media.url ||
			! media.type ||
			'image' !== media.type
		) {
			imageURL = null;
		}

		if ( imageURL ) {
			imageID = imageURL.id;
			imageURL = imageURL.sizes.full.url;
		}
		key_id = key + '_id';

		addStyleInRoot( root, getCSSProperties( key, imageURL ) );

		editPost( {
			meta: {
				_srfm_forms_styling: {
					...formStyling,
					[ key ]: imageURL,
					[ key_id ]: imageID,
				},
			},
		} );
	};

	function submitButtonInherit() {
		const inheritClass = [ 'srfm-btn-alignment', 'wp-block-button__link' ];
		const customClass = [
			'srfm-button',
			'srfm-submit-button',
			'srfm-btn-alignment',
			'srfm-btn-bg-color',
		];
		const btnClass =
			sureformsKeys?._srfm_inherit_theme_button &&
			sureformsKeys._srfm_inherit_theme_button
				? inheritClass
				: customClass;
		if ( submitBtn ) {
			if (
				sureformsKeys?._srfm_inherit_theme_button &&
				sureformsKeys._srfm_inherit_theme_button
			) {
				submitBtn.classList.remove( ...customClass );
				submitBtnCtn.classList.add( 'wp-block-button' );
				submitBtn.classList.add( ...btnClass );
			} else {
				submitBtn.classList.remove( inheritClass );
				submitBtnCtn.classList.remove( 'wp-block-button' );
				submitBtn.classList.add( ...btnClass );
			}
		}
	}


	useEffect( () => {
		// Add the background type class to the editor for styling purpose.
		addBackgroundTypeClass( bg_type );
		addOverlayTypeClass( bg_gradient_overlay_type, bg_type );
	}, [ bg_type, bg_gradient_overlay_type ] );

	useEffect( () => {
		if ( sureformsKeys ) {
			const defaultTextColor = '#1E1E1E';

			// Form Container
			const cssProperties = {
				'--srfm-color-scheme-primary': formStyling?.primary_color || '#111C44',
				'--srfm-btn-color-hover': `hsl( from ${ formStyling?.primary_color || '#111C44' } h s l / 0.9)`,
				'--srfm-color-scheme-text-on-primary': formStyling?.text_color_on_primary || '#FFFFFF',
				'--srfm-color-scheme-text': formStyling?.text_color || defaultTextColor,
				'--srfm-color-input-label': formStyling?.text_color || defaultTextColor,
				'--srfm-color-input-placeholder': `hsl( from ${ formStyling?.text_color || defaultTextColor } h s l / 0.5)`,
				'--srfm-color-input-text': formStyling?.text_color || defaultTextColor,
				'--srfm-color-input-description': `hsl( from ${ formStyling?.text_color || defaultTextColor } h s l / 0.65)`,
				'--srfm-color-input-prefix': `hsl( from ${ formStyling?.text_color || defaultTextColor } h s l / 0.65)`,
				'--srfm-color-input-background': `hsl( from ${ formStyling?.text_color || defaultTextColor } h s l / 0.02)`,
				'--srfm-color-input-background-disabled': `hsl( from ${ formStyling?.text_color || defaultTextColor } h s l / 0.05)`,
				'--srfm-color-input-border': `hsl( from ${ formStyling?.text_color || defaultTextColor } h s l / 0.25)`,
				'--srfm-color-input-border-disabled': `hsl( from ${ formStyling?.text_color || defaultTextColor } h s l / 0.15)`,
				'--srfm-color-input-border-focus-glow': `hsl( from ${ formStyling?.primary_color || '#FAE4DC' } h s l / 0.15 )`,
				// checkbox and gdpr - for small, medium and large checkbox sizes
				'--srfm-checkbox-input-border-radius': '4px',
				'--srfm-color-multi-choice-svg': `hsl( from ${ formStyling?.text_color || defaultTextColor } h s l / 0.7)`,
				// Button
				// Button text color
				'--srfm-btn-text-color': formStyling?.text_color_on_primary || '#FFFFFF',
				// btn border color
				'--srfm-btn-border-color': formStyling?.primary_color || '#111C44',

				// Button alignment
				'--srfm-submit-alignment': formStyling?.submit_button_alignment || 'left',
				'--srfm-submit-width': sureformsKeys?._srfm_submit_width || '',
				'--srfm-submit-alignment-backend': sureformsKeys._srfm_submit_alignment_backend || '',
				'--srfm-submit-width-backend': sureformsKeys._srfm_submit_width_backend || '',
				// Background Control Settings.
				'--srfm-bg-color': formStyling?.bg_color || '#FFFFFF',
				'--srfm-bg-image': formStyling?.bg_image ? `url(${ formStyling?.bg_image })` : 'none',
				'--srfm-bg-position': formStyling?.bg_image_position.replace( '-', ' ' ) || 'left top',
				'--srfm-bg-attachment': formStyling?.bg_image_attachment || 'scroll',
				'--srfm-bg-repeat': formStyling?.bg_image_repeat || 'no-repeat',
				'--srfm-bg-size': formStyling?.bg_image_size || 'auto',
				'--srfm-bg-size-custom': formStyling?.bg_image_size_custom || 100,
				'--srfm-bg-size-custom-type': formStyling?.bg_image_size_custom_type || '%',
				// Gradient Variables.
				'--srfm-bg-gradient': formStyling?.gradient_type === 'basic' ? formStyling?.bg_gradient || 'linear-gradient(90deg, #FFC9B2 0%, #C7CBFF 100%)' : getGradientCSS( gradientOptions.type, gradientOptions.color_1, gradientOptions.color_2, gradientOptions.location_1, gradientOptions.location_2, gradientOptions.angle ),
				// Overlay Variables - Image.
				'--srfm-bg-overlay-image': formStyling?.bg_overlay_image ? `url(${ formStyling?.bg_overlay_image })` : 'none',
				'--srfm-bg-overlay-position': formStyling?.bg_overlay_position.replace( '-', ' ' ) || 'left top',
				'--srfm-bg-overlay-attachment': formStyling?.bg_overlay_attachment || 'scroll',
				'--srfm-bg-overlay-repeat': formStyling?.bg_overlay_repeat || 'no-repeat',
				'--srfm-bg-overlay-blend-mode': formStyling?.overlay_blend_mode || 'normal',
				'--srfm-bg-overlay-size': formStyling?.bg_overlay_size || 'auto',
				'--srfm-bg-overlay-custom-size': formStyling?.bg_overlay_custom_size || 100,
				'--srfm-bg-overlay-custom-size-type': formStyling?.bg_overlay_custom_size_type || '%',
				'--srfm-bg-overlay-opacity': formStyling?.bg_overlay_opacity ?? 1,
				// Overlay Variables - Color.
				'--srfm-bg-overlay-color': formStyling?.bg_image_overlay_color || '#FFFFFF75',
			};

			addStyleInRoot( root, cssProperties );
		} else {
			sureformsKeys = defaultKeys;
			editPost( {
				meta: sureformsKeys,
			} );
		}
	}, [ sureformsKeys, gradientOptions, bg_gradient ] );

	function updateMeta( option, value ) {
		const value_id = 0;
		const key_id = '';

		const cssProperties = {};
		// Button
		switch ( option ) {
			case '_srfm_button_border_width':
				cssProperties[ '--srfm-btn-border-width' ] = value ? value + 'px' : '0px';
				break;
			case '_srfm_button_border_color':
				cssProperties[ '--srfm-btn-border-color' ] = value ? value : '#000000';
				break;
		}

		addStyleInRoot( root, cssProperties );

		const option_array = {};

		if ( key_id ) {
			option_array[ key_id ] = value_id;
		}
		option_array[ option ] = value;
		editPost( {
			meta: option_array,
		} );
	}

	/**
	 * Applies the specified field spacing to the form by setting the corresponding CSS variables.
	 *
	 * This function merges the base sizes defined for 'small' spacing with the
	 * override sizes for the specified spacing (if any), and applies the resulting
	 * CSS variables to the root element.
	 *
	 * @param {string} sizingValue - The selected field spacing size ('small', 'medium', 'large').
	 * @return {void}
	 * @since 0.0.7
	 */
	function applyFieldSpacing( sizingValue ) {
		const baseSize = srfm_admin?.field_spacing_vars?.small;
		const overrideSize = srfm_admin?.field_spacing_vars[ sizingValue ] || {};
		const finalSize = { ...baseSize, ...overrideSize };

		addStyleInRoot( root, finalSize );
	}

	/**
	 * Update the form styling options on user input
	 * and update the meta values in the database.
	 *
	 * @param {string} option
	 * @param {string} value
	 * @return {void}
	 * @since 0.0.7
	 */
	function updateFormStyling( option, value ) {
		addStyleInRoot( root, getCSSProperties( option, value ) );

		editPost( {
			meta: {
				_srfm_forms_styling: { ...formStyling, [ option ]: value },
			},
		} );
	}

	function getCSSProperties( option, value ) {
		console.log( 'option', option );
		console.log( 'value', value );
		const cssProperties = {};
		switch ( option ) {
			case 'primary_color':
				cssProperties[ '--srfm-color-scheme-primary' ] = value || '#111C44';
				cssProperties[ '--srfm-btn-color-hover' ] = `hsl( from ${ value || '#111C44' } h s l / 0.9)`;
				break;
			case 'text_color':
				const defaultTextColor = '#1E1E1E';
				cssProperties[ '--srfm-color-scheme-text' ] = value || defaultTextColor;
				cssProperties[ '--srfm-color-input-label' ] = value || defaultTextColor;
				cssProperties[ '--srfm-color-input-placeholder' ] = value || defaultTextColor;
				cssProperties[ '--srfm-color-input-text' ] = value || defaultTextColor;
				cssProperties[ '--srfm-color-input-description' ] = `hsl( from ${ value || defaultTextColor } h s l / 0.65)`;
				cssProperties[ '--srfm-color-input-prefix' ] = `hsl( from ${ value || defaultTextColor } h s l / 0.65)`;
				cssProperties[ '--srfm-color-input-background' ] = `hsl( from ${ value || defaultTextColor } h s l / 0.02)`;
				cssProperties[ '--srfm-color-input-background-disabled' ] = `hsl( from ${ value || defaultTextColor } h s l / 0.05)`;
				cssProperties[ '--srfm-color-input-border' ] = `hsl( from ${ value || defaultTextColor } h s l / 0.25)`;
				cssProperties[ '--srfm-color-input-border-disabled' ] = `hsl( from ${ value || defaultTextColor } h s l / 0.15)`;
				break;
			case 'text_color_on_primary':
				cssProperties[ '--srfm-color-scheme-text-on-primary' ] = value || '#FFFFFF';
				break;
			case 'field_spacing':
				cssProperties[ '--srfm-field-spacing' ] = value || 'medium';
				break;
			case 'submit_button_alignment':
				cssProperties[ '--srfm-submit-alignment' ] = value || 'left';
				cssProperties[ '--srfm-submit-width-backend' ] = 'max-content';
				updateMeta( '_srfm_submit_width_backend', 'max-content' );

				if ( value === 'left' ) {
					cssProperties[ '--srfm-submit-alignment-backend' ] = '100%';
					updateMeta( '_srfm_submit_alignment_backend', '100%' );
				} else if ( value === 'right' ) {
					cssProperties[ '--srfm-submit-alignment-backend' ] = '0%';
					updateMeta( '_srfm_submit_alignment_backend', '0%' );
				} else if ( value === 'center' ) {
					cssProperties[ '--srfm-submit-alignment-backend' ] = '50%';
					updateMeta( '_srfm_submit_alignment_backend', '50%' );
				} else if ( value === 'justify' ) {
					cssProperties[ '--srfm-submit-alignment-backend' ] = '50%';
					cssProperties[ '--srfm-submit-width-backend' ] = 'auto';
					updateMeta( '_srfm_submit_alignment_backend', '50%' );
				}
				break;
			case 'bg_color':
				cssProperties[ '--srfm-bg-color' ] = value || '#FFFFFF';
				break;
			// Image Variables.
			case 'bg_image':
				cssProperties[ '--srfm-bg-image' ] = value ? `url(${ value })` : 'none';
				break;
			case 'bg_image_position':
				cssProperties[ '--srfm-bg-position' ] = value.replace( '-', ' ' ) || 'left top';
				break;
			case 'bg_image_attachment':
				cssProperties[ '--srfm-bg-attachment' ] = value || 'scroll';
				break;
			case 'bg_image_repeat':
				cssProperties[ '--srfm-bg-repeat' ] = value || 'no-repeat';
				break;
			case 'bg_image_size':
				cssProperties[ '--srfm-bg-size' ] = value || 'auto';
				break;
			case 'bg_image_size_custom':
				cssProperties[ '--srfm-bg-size-custom' ] = value || 100;
				break;
			case 'bg_image_size_custom_type':
				cssProperties[ '--srfm-bg-size-custom-type' ] = value || '%';
				break;
			// Gradient Variables.
			case 'gradient_type':
			case 'bg_gradient':
			case 'bg_gradient_type':
			case 'bg_gradient_color_1':
			case 'bg_gradient_color_2':
			case 'bg_gradient_location_1':
			case 'bg_gradient_location_2':
			case 'bg_gradient_angle':
				if ( ( option === 'gradient_type' && value === 'basic' ) || option === 'bg_gradient' ) {
					cssProperties[ '--srfm-bg-gradient' ] = bg_gradient;
					break;
				}
				const updatedGradientOptions = {
					...gradientOptions,
					[ option.replace( 'bg_gradient_', '' ) ]: value,
				};
				setGradientOptions( updatedGradientOptions );
				break;
			// Overlay Variables - Image.
			case 'bg_gradient_overlay_type': // might be used in future.
				cssProperties[ '--srfm-bg-gradient-overlay-type' ] = value || 'none';
				break;
			case 'bg_overlay_image':
				cssProperties[ '--srfm-bg-overlay-image' ] = value ? `url(${ value })` : 'none';
				break;
			case 'bg_overlay_position':
				cssProperties[ '--srfm-bg-overlay-position' ] = value.replace( '-', ' ' ) || 'left top';
				break;
			case 'bg_overlay_attachment':
				cssProperties[ '--srfm-bg-overlay-attachment' ] = value || 'scroll';
				break;
			case 'bg_overlay_repeat':
				cssProperties[ '--srfm-bg-overlay-repeat' ] = value || 'no-repeat';
				break;
			case 'overlay_blend_mode':
				cssProperties[ '--srfm-bg-overlay-blend-mode' ] = value || 'normal';
				break;
			case 'bg_overlay_size':
				cssProperties[ '--srfm-bg-overlay-size' ] = value || 'auto';
				break;
			case 'bg_overlay_custom_size':
				cssProperties[ '--srfm-bg-overlay-custom-size' ] = value || 100;
				break;
			case 'bg_overlay_custom_size_type':
				cssProperties[ '--srfm-bg-overlay-custom-size-type' ] = value || '%';
				break;
			case 'bg_overlay_opacity':
				cssProperties[ '--srfm-bg-overlay-opacity' ] = value ?? 1;
				break;
			// Overlay Variables - Color.
			case 'bg_image_overlay_color':
				cssProperties[ '--srfm-bg-overlay-color' ] = value || '#FFFFFF75';
				break;
		}

		return cssProperties;
	}

	function getGradientCSS( type = 'linear', color1 = '#FFC9B2', color2 = '#C7CBFF', loc1 = 0, loc2 = 100, angle = 90 ) {
		if ( type === 'radial' ) {
			return `radial-gradient(circle at ${ loc1 }%, ${ color1 }, ${ color2 } ${ loc2 }% )`;
		} else {
			return `linear-gradient( ${ angle }deg, ${ color1 } ${ loc1 }%, ${ color2 } ${ loc2 }%)`;
		}
	}

	const addBackgroundTypeClass = ( type ) => {
		switch ( type ) {
			case 'image':
				editor.classList.add( 'srfm-bg-type-image' );
				editor.classList.remove( 'srfm-bg-type-color' );
				editor.classList.remove( 'srfm-bg-type-gradient' );
				break;
			case 'gradient':
				editor.classList.add( 'srfm-bg-type-gradient' );
				editor.classList.remove( 'srfm-bg-type-color' );
				editor.classList.remove( 'srfm-bg-type-image' );
				break;
			// For background type color or deselect state, keep the color as default class.
			default:
				editor.classList.add( 'srfm-bg-type-color' );
				editor.classList.remove( 'srfm-bg-type-image' );
				editor.classList.remove( 'srfm-bg-type-gradient' );
		}
	};

	const addOverlayTypeClass = ( overlayType, bgType ) => {
		if ( bgType === 'image' ) {
			switch ( overlayType ) {
				case 'color':
					editor.classList.add( 'srfm-overlay-type-color' );
					editor.classList.remove( 'srfm-overlay-type-image' );
					editor.classList.remove( 'srfm-overlay-type-gradient' );
					break;
				case 'image':
					editor.classList.add( 'srfm-overlay-type-image' );
					editor.classList.remove( 'srfm-overlay-type-color' );
					editor.classList.remove( 'srfm-overlay-type-gradient' );
					break;
				case 'gradient':
					editor.classList.add( 'srfm-overlay-type-gradient' );
					editor.classList.remove( 'srfm-overlay-type-color' );
					editor.classList.remove( 'srfm-overlay-type-image' );
			}
		} else {
			if ( overlayType === 'image' ) {
				editor.classList.add( 'srfm-overlay-type-image' );
				editor.classList.remove( 'srfm-overlay-type-color' );
				editor.classList.remove( 'srfm-overlay-type-gradient' );
			}
		}
	};

	const form = [
		{
			id: 'background',
			component: (
				<>
					<Background
						// Background Properties
						backgroundType={ {
							value: bg_type,
							label: 'bg_type',
						} }
						backgroundColor={ {
							value: bg_color,
							label: 'bg_color',
						} }
						backgroundImage={ {
							value: bg_image,
							label: 'bg_image',
						} }
						backgroundPosition={ {
							value: bg_image_position,
							label: 'bg_image_position',
						} }
						backgroundAttachment={ {
							value: bg_image_attachment,
							label: 'bg_image_attachment',
						} }
						backgroundRepeat={ {
							value: bg_image_repeat,
							label: 'bg_image_repeat',
						} }
						backgroundSize={ {
							value: bg_image_size,
							label: 'bg_image_size',
						} }
						backgroundCustomSize={ {
							desktop: {
								value: bg_image_size_custom,
								label: 'bg_image_size_custom',
							},
							tablet: {
								value: bg_image_size_custom,
								label: 'bg_image_size_custom',
							},
							mobile: {
								value: bg_image_size_custom,
								label: 'bg_image_size_custom',
							},
						} }
						backgroundCustomSizeType={ {
							value: bg_image_size_custom_type || '%',
							label: 'bg_image_size_custom_type',
						} }
						// Gradient Properties
						gradientOverlay={ { value: true } }
						gradientType={ {
							value: gradient_type,
							label: 'gradient_type',
						} }
						backgroundGradientColor1={ {
							value: bg_gradient_color_1,
							label: 'bg_gradient_color_1',
						} }
						backgroundGradientColor2={ {
							value: bg_gradient_color_2,
							label: 'bg_gradient_color_2',
						} }
						backgroundGradientLocation1={ {
							value: bg_gradient_location_1,
							label: 'bg_gradient_location_1',
						} }
						backgroundGradientLocation2={ {
							value: bg_gradient_location_2,
							label: 'bg_gradient_location_2',
						} }
						backgroundGradientAngle={ {
							value: bg_gradient_angle,
							label: 'bg_gradient_angle',
						} }
						backgroundGradientType={ {
							value: bg_gradient_type,
							label: 'bg_gradient_type',
						} }
						backgroundGradient={ {
							value: bg_gradient || 'linear-gradient(90deg, #FFC9B2 0%, #C7CBFF 100%)',
							label: 'bg_gradient',
						} }
						// Overlay Properties
						overlayType={ {
							value: bg_gradient_overlay_type,
							label: 'bg_gradient_overlay_type',
						} }
						overlayOpacity={ {
							value: bg_overlay_opacity,
							label: 'bg_overlay_opacity',
						} }
						backgroundOverlayImage={ {
							value: bg_overlay_image,
							label: 'bg_overlay_image',
						} }
						backgroundImageColor={ {
							value: bg_image_overlay_color,
							label: 'bg_image_overlay_color',
						} }
						backgroundOverlayPosition={ {
							value: bg_overlay_position,
							label: 'bg_overlay_position',
						} }
						backgroundOverlayAttachment={ {
							value: bg_overlay_attachment,
							label: 'bg_overlay_attachment',
						} }
						overlayBlendMode={ {
							value: overlay_blend_mode,
							label: 'overlay_blend_mode',
						} }
						backgroundOverlayRepeat={ {
							value: bg_overlay_repeat,
							label: 'bg_overlay_repeat',
						} }
						backgroundOverlaySize={ {
							value: bg_overlay_size,
							label: 'bg_overlay_size',
						} }
						backgroundOverlayCustomSize={ {
							desktop: {
								value: bg_overlay_custom_size,
								label: 'bg_overlay_custom_size',
							},
							tablet: {
								value: bg_overlay_custom_size,
								label: 'bg_overlay_custom_size',
							},
							mobile: {
								value: bg_overlay_custom_size,
								label: 'bg_overlay_custom_size',
							},
						} }
						backgroundOverlayCustomSizeType={ {
							value: bg_overlay_custom_size_type,
							label: 'bg_overlay_custom_size_type',
						} }
						label={ __( 'Background', 'sureforms' ) }
						setAttributes={ onHandleChange }
						onSelectImage={ onImageSelect }
					/>
					<p className="components-base-control__help" />
				</>
			),
		},
		{
			id: 'primary_color',
			component: (
				<>
					<AdvancedPopColorControl
						label={ __( 'Primary Color', 'sureforms' ) }
						colorValue={ formStyling?.primary_color }
						data={ {
							value: formStyling?.primary_color,
							label: 'primary_color',
						} }
						onColorChange={ ( colorValue ) => {
							if ( colorValue !== formStyling?.primary_color ) {
								updateFormStyling( 'primary_color', colorValue );
							}
						} }
						value={ formStyling?.primary_color }
						isFormSpecific={ true }
					/>
					<p className="components-base-control__help" />
				</>
			),
		},
		{
			id: 'text_color',
			component: (
				<>
					<AdvancedPopColorControl
						label={ __( 'Text Color', 'sureforms' ) }
						colorValue={ formStyling?.text_color }
						data={ {
							value: formStyling?.text_color,
							label: 'text_color',
						} }
						onColorChange={ ( colorValue ) => {
							if ( colorValue !== formStyling?.text_color ) {
								updateFormStyling( 'text_color', colorValue );
							}
						} }
						value={ formStyling?.text_color }
						isFormSpecific={ true }
					/>
					<p className="components-base-control__help" />
				</> ),
		},
		{
			id: 'text_color_on_primary',
			component: (
				<>
					<AdvancedPopColorControl
						label={ __( 'Text Color on Primary', 'sureforms' ) }
						colorValue={ formStyling?.text_color_on_primary }
						data={ {
							value: formStyling?.text_color_on_primary,
							label: 'text_color_on_primary',
						} }
						onColorChange={ ( colorValue ) => {
							if (
								colorValue !== formStyling?.text_color_on_primary
							) {
								updateFormStyling(
									'text_color_on_primary',
									colorValue
								);
							}
						} }
						value={ formStyling?.text_color_on_primary }
						isFormSpecific={ true }
					/>
					<p className="components-base-control__help" />
				</>
			),
		},
	];

	const fields = [
		{
			id: 'field_spacing',
			component: (
				<MultiButtonsControl
					label={ __( 'Field Spacing', 'sureforms' ) }
					data={ {
						value: formStyling?.field_spacing || 'medium',
						label: 'field_spacing',
					} }
					options={ [
						{
							value: 'small',
							label: __( 'Small', 'sureforms' ),
						},
						{
							value: 'medium',
							label: __( 'Medium', 'sureforms' ),
						},
						{
							value: 'large',
							label: __( 'Large', 'sureforms' ),
						},
					] }
					showIcons={ false }
					onChange={ ( value ) => {
						updateFormStyling( 'field_spacing', value );
						setFieldSpacing( value );
					} }
				/>
			),
		},
	];

	const button = [
		{
			id: 'submit_button_alignment',
			component: (
				! isInlineButtonBlockPresent && (
					<>
						<p className="components-base-control__help" />
						<MultiButtonsControl
							label={ __(
								'Submit Button Alignment',
								'sureforms'
							) }
							data={ {
								value: formStyling?.submit_button_alignment,
								label: 'submit_button_alignment',
							} }
							options={ [
								{
									value: 'left',
									icon: (
										<FontAwesomeIcon icon={ faAlignLeft } />
									),
									tooltip: __( 'Left', 'sureforms' ),
								},
								{
									value: 'center',
									icon: (
										<FontAwesomeIcon
											icon={ faAlignCenter }
										/>
									),
									tooltip: __( 'Center', 'sureforms' ),
								},
								{
									value: 'right',
									icon: (
										<FontAwesomeIcon
											icon={ faAlignRight }
										/>
									),
									tooltip: __( 'Right', 'sureforms' ),
								},
								{
									value: 'justify',
									icon: (
										<FontAwesomeIcon
											icon={ faAlignJustify }
										/>
									),
									tooltip: __( 'Full Width', 'sureforms' ),
								},
							] }
							showIcons={ true }
							onChange={ ( value ) => {
								updateFormStyling(
									'submit_button_alignment',
									value || 'left'
								);
								if ( 'justify' === value ) {
									updateMeta( '_srfm_submit_width', '100%' );
									updateMeta(
										'_srfm_submit_width_backend',
										'auto'
									);
								} else {
									updateMeta( '_srfm_submit_width', '' );
								}
							} }
						/>
					</>
				)
			),
		},
	];

	const baseStylePanels = [
		{
			panelId: 'form',
			title: __( 'Form', 'sureforms' ),
			content: form,
			initialOpen: true,
		},
		{
			panelId: 'fields',
			title: __( 'Fields', 'sureforms' ),
			content: fields,
			initialOpen: false,
		},
		{
			panelId: 'button',
			title: __( 'Button', 'sureforms' ),
			content: button,
			initialOpen: false,
		},
	];

	const enhancedStylePanels = getStylePanels( baseStylePanels, { props, sureformsKeys, editPost, formStyling, updateFormStyling } );

	const presetPreview = (
		<>
			<div className="srfm-panel-preview">
				<div className="components-panel__body" style={ { 'border-bottom': 'unset' } }>
					<h2 className="components-panel__body-title">
						{ __( 'Form Theme', 'sureforms' ) }
					</h2>
					<PremiumBadge
						tooltipHeading={ __(
							'Unlock Form Theme',
							'sureforms'
						) }
						tooltipContent={ __(
							'With the SureForms Starter Plan, access essential form styling options to personalize the look and feel of your forms, ensuring a seamless and engaging user experience.',
							'sureforms'
						) }
						tooltipPosition={ 'bottom' }
						utmMedium={ 'editor_form_themes' }
					/>
					{ chevronDown }
				</div>
			</div>
		</>
	);

	const isPresetPanelPresent = enhancedStylePanels.find( ( panel ) => panel.panelId === 'themes' );
	return (
		<>
			{ ! isPresetPanelPresent && presetPreview }
			{ enhancedStylePanels.map( ( panel ) => {
				const { panelId, title, content, initialOpen } = panel;
				const panelOptions = content.map( ( item ) => item.component );
				return (
					<SRFMAdvancedPanelBody
						key={ panelId }
						title={ title }
						initialOpen={ initialOpen }
					>
						{ panelOptions }
					</SRFMAdvancedPanelBody>
				);
			} ) }
		</>
	);
}

export default StyleSettings;
