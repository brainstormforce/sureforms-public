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
	const deviceType = useDeviceType();
	const [ submitBtn, setSubmitBtn ] = useState(
		document.querySelector( '.srfm-submit-richtext' )
	);
	const [ submitBtnCtn, setSubmitBtnCtn ] = useState(
		document.querySelector( '.srfm-submit-btn-container' )
	);
	const [ fieldSpacing, setFieldSpacing ] = useState( formStyling?.field_spacing || 'medium' );

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

	const getMetaValue = useSelect( ( hookSelect ) => {
		const getStore = hookSelect( editorStore );
		const metaValue = getStore.getEditedPostAttribute( 'meta' );
		const getPermalinkParts = getStore.getPermalinkParts();

		return {
			_srfm_submit_button_text: metaValue?._srfm_submit_button_text,
			_srfm_instant_form_settings: metaValue?._srfm_instant_form_settings,
			getPermalinkParts,
		};
	}, [] );
	const _srfm_instant_form_settings = getMetaValue._srfm_instant_form_settings || {};
	const onHandleChange = ( updatedSettings ) => {
		const [ key, value ] = Object.entries( updatedSettings )[0];
		if ( _srfm_instant_form_settings?.[ key ] === value ) {
			// Do not re-render if the value is same. This is necessary for color picker type controls which re-render on selection.
			return;
		}

		const instantFormSettings = {
			..._srfm_instant_form_settings,
			...{
				[ key ]: value,
			},
		};

		editPost( {
			meta: {
				_srfm_instant_form_settings: instantFormSettings,
			},
		} );
	};

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

		const updatedSettings = {
			..._srfm_instant_form_settings,
			...{
				[ key ]: imageURL,
				[ key_id ]: imageID,
			},
		};

		editPost( {
			meta: {
				_srfm_instant_form_settings: updatedSettings,
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
		if ( sureformsKeys ) {
			const defaultTextColor = '#1E1E1E';

			// Form Container
			const cssProperties = {
				'--srfm-color-scheme-primary': formStyling?.primary_color || '#0C78FB',
				'--srfm-btn-color-hover': `hsl( from ${ formStyling?.primary_color || '#0C78FB' } h s l / 0.9)`,
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
				'--srfm-btn-border-color': formStyling?.primary_color || '#0C78FB',

				// Button alignment
				'--srfm-submit-alignment': formStyling?.submit_button_alignment || 'left',
				'--srfm-submit-width': sureformsKeys?._srfm_submit_width || '',
				'--srfm-submit-alignment-backend': sureformsKeys._srfm_submit_alignment_backend || '',
				'--srfm-submit-width-backend': sureformsKeys._srfm_submit_width_backend || '',
			};

			addStyleInRoot( root, cssProperties );
		} else {
			sureformsKeys = defaultKeys;
			editPost( {
				meta: sureformsKeys,
			} );
		}
	}, [ sureformsKeys ] );

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
		const cssProperties = {};

		switch ( option ) {
			case 'primary_color':
				cssProperties[ '--srfm-color-scheme-primary' ] = value || '#0C78FB';
				cssProperties[ '--srfm-btn-color-hover' ] = `hsl( from ${ value || '#0C78FB' } h s l / 0.9)`;
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
		}

		addStyleInRoot( root, cssProperties );

		editPost( {
			meta: {
				_srfm_forms_styling: { ...formStyling, [ option ]: value },
			},
		} );
	}

	const form = [
		{
			id: 'background',
			component: (
				<>
					<Background
						gradientOverlay={ {value: true} }
						backgroundVideoType={ {value: false} }
						backgroundType={ {
							value: _srfm_instant_form_settings?.bg_type,
							label: 'bg_type',
						} }
						backgroundColor={ {
							value: _srfm_instant_form_settings?.bg_color,
							label: 'bg_color',
						} }
						backgroundImage={ {
							value: _srfm_instant_form_settings?.bg_image,
							label: 'bg_image',
						} }
						backgroundPosition={ {
							value: _srfm_instant_form_settings?.bg_image_position,
							label: 'bg_image_position',
						} }
						backgroundAttachment={ {
							value: _srfm_instant_form_settings?.bg_image_attachment,
							label: 'bg_image_attachment',
						} }
						backgroundRepeat={ {
							value: _srfm_instant_form_settings?.bg_image_repeat,
							label: 'bg_image_repeat',
						} }
						backgroundSize={ {
							value: _srfm_instant_form_settings?.bg_image_size,
							label: 'bg_image_size',
						} }
						backgroundCustomSize={ {
							desktop: {
								value: _srfm_instant_form_settings?.bg_image_size_custom,
								label: 'bg_image_size_custom',
							},
							tablet: {
								value: _srfm_instant_form_settings?.bg_image_size_custom,
								label: 'bg_image_size_custom',
							},
							mobile: {
								value: _srfm_instant_form_settings?.bg_image_size_custom,
								label: 'bg_image_size_custom',
							}
						} }
						backgroundCustomSizeType={ {
							value: _srfm_instant_form_settings?.bg_image_size_custom_type || 'px',
							label: 'bg_image_size_custom_type',
						} }
						customPosition={ {
							value: _srfm_instant_form_settings?.bg_image_custom_position,
							label: 'bg_image_custom_position',
						} }
						// Gradient Properties
						gradientType={ {
							value: _srfm_instant_form_settings?.gradient_type,
							label: 'gradient_type',
						} }
						backgroundGradientColor1={ {
							value: _srfm_instant_form_settings?.bg_gradient_color_1,
							label: 'bg_gradient_color_1',
						} }
						backgroundGradientColor2={ {
							value: _srfm_instant_form_settings?.bg_gradient_color_2,
							label: 'bg_gradient_color_2',
						} }
						backgroundGradientLocation1={ {
							value: _srfm_instant_form_settings?.bg_gradient_location_1,
							label: 'bg_gradient_location_1',
						} }
						backgroundGradientLocation2={ {
							value: _srfm_instant_form_settings?.bg_gradient_location_2,
							label: 'bg_gradient_location_2',
						} }
						backgroundGradientAngle={ {
							value: _srfm_instant_form_settings?.bg_gradient_angle,
							label: 'bg_gradient_angle',
						} }
						backgroundGradientType={ {
							value: _srfm_instant_form_settings?.bg_gradient_type,
							label: 'bg_gradient_type',
						} }
						backgroundGradient={ {
							value: _srfm_instant_form_settings?.bg_gradient,
							label: 'bg_gradient',
						} }

						imageResponsive={ false }
						label={ __( 'Background', 'sureforms' ) }
						setAttributes={ onHandleChange }
						onHandleChange={ onHandleChange }
						meta={ _srfm_instant_form_settings }
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
						{ __( 'Presets', 'sureforms' ) }
					</h2>
					<PremiumBadge
						tooltipHeading={ __(
							'Unlock Form Presets',
							'sureforms'
						) }
						tooltipContent={ __(
							'Upgrade to the SureForms Starter Plan to access a range of form presets that can be applied to your form with a single click, saving you time and effort.',
							'sureforms'
						) }
						tooltipPosition={ 'bottom' }
						utmMedium={ 'editor_form_presets' }
					/>
					{ chevronDown }
				</div>
			</div>
		</>
	);

	const isPresetPanelPresent = enhancedStylePanels.find( ( panel ) => panel.panelId === 'presets' );
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
