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
import { chevronDown } from '@wordpress/icons';
import PremiumBadge from '@Admin/components/PremiumBadge';
import { formPresetAccordion } from '@Components/hooks';

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

	if ( sureformsKeys ) {
		// Form Container
		root.style.setProperty(
			'--srfm-color-scheme-primary',
			formStyling?.primary_color || '#0C78FB'
		);
		root.style.setProperty(
			'--srfm-btn-color-hover',
			formStyling?.primary_color
				? `hsl( from ${ formStyling.primary_color } h s l / 0.9)`
				: `hsl( from #0C78FB h s l / 0.9)`
		);
		root.style.setProperty(
			'--srfm-color-scheme-text-on-primary',
			formStyling?.text_color_on_primary || '#FFFFFF'
		);

		const defaultTextColor = '#1E1E1E';

		root.style.setProperty(
			'--srfm-color-scheme-text',
			formStyling?.text_color || defaultTextColor
		);
		root.style.setProperty(
			'--srfm-color-input-label',
			formStyling?.text_color || defaultTextColor
		);
		root.style.setProperty(
			'--srfm-color-input-placeholder',
			formStyling?.text_color
				? `hsl( from ${ formStyling.text_color } h s l / 0.5)`
				: `hsl( from ${ defaultTextColor } h s l / 0.5)`
		);
		root.style.setProperty(
			'--srfm-color-input-text',
			formStyling?.text_color || defaultTextColor
		);
		root.style.setProperty(
			'--srfm-color-input-description',
			formStyling?.text_color
				? `hsl( from ${ formStyling.text_color } h s l / 0.65)`
				: `hsl( from ${ defaultTextColor } h s l / 0.65)`
		);
		root.style.setProperty(
			'--srfm-color-input-prefix',
			formStyling?.text_color
				? `hsl( from ${ formStyling.text_color } h s l / 0.65)`
				: `hsl( from ${ defaultTextColor } h s l / 0.65)`
		);
		root.style.setProperty(
			'--srfm-color-input-background',
			formStyling?.text_color
				? `hsl( from ${ formStyling.text_color } h s l / 0.02)`
				: `hsl( from ${ defaultTextColor } h s l / 0.02)`
		);
		root.style.setProperty(
			'--srfm-color-input-background-disabled',
			formStyling?.text_color
				? `hsl( from ${ formStyling.text_color } h s l / 0.05)`
				: `hsl( from ${ defaultTextColor } h s l / 0.05)`
		);
		root.style.setProperty(
			'--srfm-color-input-border',
			formStyling?.text_color
				? `hsl( from ${ formStyling.text_color } h s l / 0.25)`
				: `hsl( from ${ defaultTextColor } h s l / 0.25)`
		);
		root.style.setProperty(
			'--srfm-color-input-border-disabled',
			formStyling?.text_color
				? `hsl( from ${ formStyling.text_color } h s l / 0.15)`
				: `hsl( from ${ defaultTextColor } h s l / 0.15)`
		);
		root.style.setProperty(
			'--srfm-color-input-border-focus-glow',
			formStyling.primary_color
				? `hsl( from ${ formStyling.primary_color } h s l / 0.15 )`
				: '#FAE4DC'
		);

		// checkbox and gdpr - for small, medium and large checkbox sizes
		root.style.setProperty(
			'--srfm-checkbox-input-border-radius',
			'4px'
		);

		root.style.setProperty(
			'--srfm-color-multi-choice-svg',
			formStyling?.text_color
				? `hsl( from ${ formStyling.text_color } h s l / 0.7)`
				: `hsl( from ${ defaultTextColor } h s l / 0.7)`
		);

		// Button
		// Button text color
		root.style.setProperty(
			'--srfm-btn-text-color',
			formStyling?.text_color_on_primary || '#FFFFFF'
		);
		// btn border color
		root.style.setProperty(
			'--srfm-btn-border-color',
			formStyling?.primary_color || '#0C78FB'
		);

		// Button alignment
		root.style.setProperty(
			'--srfm-submit-alignment',
			formStyling?.submit_button_alignment || 'left'
		);
		root.style.setProperty(
			'--srfm-submit-width',
			sureformsKeys._srfm_submit_width
				? sureformsKeys._srfm_submit_width
				: ''
		);
		root.style.setProperty(
			'--srfm-submit-alignment-backend',
			sureformsKeys._srfm_submit_alignment_backend
				? sureformsKeys._srfm_submit_alignment_backend
				: ''
		);
		root.style.setProperty(
			'--srfm-submit-width-backend',
			sureformsKeys._srfm_submit_width_backend
				? sureformsKeys._srfm_submit_width_backend
				: ''
		);
	} else {
		sureformsKeys = defaultKeys;
		editPost( {
			meta: sureformsKeys,
		} );
	}

	function updateMeta( option, value ) {
		const value_id = 0;
		const key_id = '';

		// Button
		if ( option === '_srfm_button_border_width' ) {
			root.style.setProperty(
				'--srfm-btn-border-width',
				value ? value + 'px' : '0px'
			);
		}
		if ( option === '_srfm_button_border_color' ) {
			root.style.setProperty(
				'--srfm-btn-border-color',
				value ? value : '#000000'
			);
		}

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

		for ( const [ key, value ] of Object.entries( finalSize ) ) {
			root.style.setProperty( key, value );
		}
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
		if ( option === 'primary_color' ) {
			root.style.setProperty(
				'--srfm-color-scheme-primary',
				value || '#0C78FB'
			);

			root.style.setProperty(
				'--srfm-btn-color-hover',
				value
					? `hsl( from ${ value } h s l / 0.9)`
					: `hsl( from #0C78FB h s l / 0.9)`
			);
		}

		if ( option === 'text_color' ) {
			const defaultTextColor = '#1E1E1E';

			root.style.setProperty(
				'--srfm-color-scheme-text',
				value ? value : defaultTextColor
			);
			root.style.setProperty(
				'--srfm-color-input-label',
				value ? value : defaultTextColor
			);
			root.style.setProperty(
				'--srfm-color-input-placeholder',
				value ? value : defaultTextColor
			);
			root.style.setProperty(
				'--srfm-color-input-text',
				value ? value : defaultTextColor
			);
			root.style.setProperty(
				'--srfm-color-input-description',
				value
					? `hsl( from ${ value } h s l / 0.65)`
					: `hsl( from ${ defaultTextColor } h s l / 0.65)`
			);
			root.style.setProperty(
				'--srfm-color-input-prefix',
				value
					? `hsl( from ${ value } h s l / 0.65)`
					: `hsl( from ${ defaultTextColor } h s l / 0.65)`
			);
			root.style.setProperty(
				'--srfm-color-input-background',
				value
					? `hsl( from ${ value } h s l / 0.02)`
					: `hsl( from ${ defaultTextColor } h s l / 0.02)`
			);
			root.style.setProperty(
				'--srfm-color-input-background-disabled',
				value
					? `hsl( from ${ value } h s l / 0.05)`
					: `hsl( from ${ defaultTextColor } h s l / 0.05)`
			);
			root.style.setProperty(
				'--srfm-color-input-border',
				value
					? `hsl( from ${ value } h s l / 0.25)`
					: `hsl( from ${ defaultTextColor } h s l / 0.25)`
			);
			root.style.setProperty(
				'--srfm-color-input-border-disabled',
				value
					? `hsl( from ${ value } h s l / 0.15)`
					: `hsl( from ${ defaultTextColor } h s l / 0.15)`
			);
		}

		if ( option === 'text_color_on_primary' ) {
			root.style.setProperty(
				'--srfm-color-scheme-text-on-primary',
				value || '#FFFFFF'
			);
		}

		if ( option === 'field_spacing' ) {
			root.style.setProperty( '--srfm-field-spacing', value || 'medium' );
		}

		if ( option === 'submit_button_alignment' ) {
			root.style.setProperty(
				'--srfm-submit-alignment',
				value || 'left'
			);
			root.style.setProperty(
				'--srfm-submit-width-backend',
				'max-content'
			);
			updateMeta( '_srfm_submit_width_backend', 'max-content' );

			if ( value === 'left' ) {
				root.style.setProperty(
					'--srfm-submit-alignment-backend',
					'100%'
				);
				updateMeta( '_srfm_submit_alignment_backend', '100%' );
			}
			if ( value === 'right' ) {
				root.style.setProperty(
					'--srfm-submit-alignment-backend',
					'0%'
				);
				updateMeta( '_srfm_submit_alignment_backend', '0%' );
			}
			if ( value === 'center' ) {
				root.style.setProperty(
					'--srfm-submit-alignment-backend',
					'50%'
				);
				updateMeta( '_srfm_submit_alignment_backend', '50%' );
			}
			if ( value === 'justify' ) {
				root.style.setProperty(
					'--srfm-submit-alignment-backend',
					'50%'
				);
				root.style.setProperty( '--srfm-submit-width-backend', 'auto' );
				updateMeta( '_srfm_submit_alignment_backend', '50%' );
			}
		}

		editPost( {
			meta: {
				_srfm_forms_styling: { ...formStyling, [ option ]: value },
			},
		} );
	}

	const formPresetAccordionMarkup = formPresetAccordion(
		<div className="srfm-panel-preview">
			<div className="components-panel__body" style={ { 'border-bottom': 'unset' } }>
				<h2 className="components-panel__body-title">
					{ ' ' }
					{ __( 'Presets', 'sureforms' ) }{ ' ' }
				</h2>
				<PremiumBadge
					badgeName={ 'Premium' }
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
		</div>,
		{ formStyling, updateFormStyling }
	);

	return (
		<>
			{ formPresetAccordionMarkup }
			<SRFMAdvancedPanelBody
				title={ __( 'Form', 'sureforms' ) }
				initialOpen={ false }
			>
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
				{/* <AdvancedPopColorControl
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
				<p className="components-base-control__help" /> */}
			</SRFMAdvancedPanelBody>
			<SRFMAdvancedPanelBody
				title={ __( 'Fields', 'sureforms' ) }
				initialOpen={ false }
			>
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
			</SRFMAdvancedPanelBody>
			{ ! isInlineButtonBlockPresent && (
				<SRFMAdvancedPanelBody
					title={ __( 'Button', 'sureforms' ) }
					initialOpen={ false }
				>
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
				</SRFMAdvancedPanelBody>
			) }
		</>
	);
}

export default StyleSettings;
