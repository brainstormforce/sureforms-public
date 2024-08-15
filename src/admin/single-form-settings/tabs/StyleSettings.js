import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import Range from '@Components/range/Range.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import MultiButtonsControl from '@Components/multi-buttons-control';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { ToggleControl } from '@wordpress/components';
import {
	faAlignLeft,
	faAlignRight,
	faAlignCenter,
	faAlignJustify,
} from '@fortawesome/free-solid-svg-icons';
import { useDeviceType } from '@Controls/getPreviewType';

function StyleSettings( props ) {
	const { editPost } = useDispatch( editorStore );
	const { defaultKeys, isInlineButtonBlockPresent, isPageBreak } = props;

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
	const [ fieldSpacing, setFieldSpacing ] = useState( formStyling?.field_spacing || 'small' );

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

	const setCssValueInRoot = ( varName, value = null, defaultValue = null ) => {
		const variableName = `--${ varName }`;
		const variableValue = !! value ? value : defaultValue;
		root.style.setProperty( variableName, variableValue );
	};

	if ( sureformsKeys ) {
		const defaultTextColor = '#1E1E1E';
		// Set the CSS variables for the form styling.
		[
			// Form Container
			[ 'srfm-color-scheme-primary', formStyling?.primary_color || '#0C78FB' ],
			[ 'srfm-btn-color-hover', `rgba( from ${ formStyling?.primary_color || '#0C78FB' } r g b / 0.9)` ],
			[ 'srfm-color-scheme-text-on-primary', formStyling?.text_color_on_primary || '#FFFFFF' ],
			[ 'srfm-color-scheme-text', formStyling?.text_color || defaultTextColor ],
			[ 'srfm-color-input-label', formStyling?.text_color || defaultTextColor ],
			[ 'srfm-color-input-placeholder', `rgba( from ${ formStyling?.text_color || defaultTextColor } r g b / 0.5)` ],
			[ 'srfm-color-input-text', formStyling?.text_color || defaultTextColor ],
			[ 'srfm-color-input-description', `rgba( from ${ formStyling?.text_color || defaultTextColor } r g b / 0.65)` ],
			[ 'srfm-color-input-prefix', `rgba( from ${ formStyling?.text_color || defaultTextColor } r g b / 0.65)` ],
			[ 'srfm-color-input-background', `rgba( from ${ formStyling?.text_color || defaultTextColor } r g b / 0.02)` ],
			[ 'srfm-color-input-background-disabled', `rgba( from ${ formStyling?.text_color || defaultTextColor } r g b / 0.05)` ],
			[ 'srfm-color-input-border', `rgba( from ${ formStyling?.text_color || defaultTextColor } r g b / 0.25)` ],
			[ 'srfm-color-input-border-disabled', `rgba( from ${ formStyling?.text_color || defaultTextColor } r g b / 0.15)` ],
			[ 'srfm-color-input-border-focus-glow', formStyling?.primary_color ? `rgba( from ${ formStyling?.primary_color } r g b / 0.15)` : '#FAE4DC' ],
			// checkbox and gdpr - for small, medium and large checkbox sizes
			[ 'srfm-checkbox-description-margin-left', '24px' ],
			[ 'srfm-checkbox-input-border-radius', '4px' ],
			[ 'srfm-color-multi-choice-svg', `rgba( from ${ formStyling?.text_color || defaultTextColor } r g b / 0.7)` ],
			// Button text color
			[ 'srfm-btn-text-color', formStyling?.text_color_on_primary || '#FFFFFF' ],
			// btn border color
			[ 'srfm-btn-border-color', formStyling?.primary_color || '#0C78FB' ],
			// Button alignment
			[ 'srfm-submit-alignment', formStyling?.submit_button_alignment || 'left' ],
			[ 'srfm-submit-width', sureformsKeys?._srfm_submit_width || '' ],
			[ 'srfm-submit-alignment-backend', sureformsKeys?._srfm_submit_alignment_backend || '' ],
			[ 'srfm-submit-width-backend', sureformsKeys?._srfm_submit_width_backend || '' ],
		].forEach( ( [ varName, value ] ) => setCssValueInRoot( varName, value ) );
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
			setCssValueInRoot( 'srfm-btn-border-width', value ? value + 'px' : '0px' );
		} else if ( option === '_srfm_button_border_color' ) {
			setCssValueInRoot( 'srfm-btn-border-color', value || '#000000' );
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
	 * @since x.x.x
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
	 * @since x.x.x
	 */
	function updateFormStyling( option, value ) {
		// List of CSS variables to update.
		const cssVarList = [];

		if ( option === 'primary_color' ) {
			cssVarList.push( [ 'srfm-color-scheme-primary', value || '#0C78FB' ] );
			cssVarList.push( [ 'srfm-btn-color-hover', `rgba( from ${ value || '#0C78FB' } r g b / 0.9)` ] );
		} else if ( option === 'text_color' ) {
			const defaultTextColor = '#1E1E1E';

			// Push the CSS variables to update.
			cssVarList.push( [ 'srfm-color-scheme-text', value || defaultTextColor ] );
			cssVarList.push( [ 'srfm-color-input-label', value || defaultTextColor ] );
			cssVarList.push( [ 'srfm-color-input-placeholder', value || defaultTextColor ] );
			cssVarList.push( [ 'srfm-color-input-text', value || defaultTextColor ] );
			cssVarList.push( [ 'srfm-color-input-description', `rgba( from ${ value || defaultTextColor } r g b / 0.65)` ] );
			cssVarList.push( [ 'srfm-color-input-prefix', `rgba( from ${ value || defaultTextColor } r g b / 0.65)` ] );
			cssVarList.push( [ 'srfm-color-input-background', `rgba( from ${ value || defaultTextColor } r g b / 0.02)` ] );
			cssVarList.push( [ 'srfm-color-input-background-disabled', `rgba( from ${ value || defaultTextColor } r g b / 0.05)` ] );
			cssVarList.push( [ 'srfm-color-input-border', `rgba( from ${ value || defaultTextColor } r g b / 0.25)` ] );
			cssVarList.push( [ 'srfm-color-input-border-disabled', `rgba( from ${ value || defaultTextColor } r g b / 0.15)` ] );
		} else if ( option === 'text_color_on_primary' ) {
			cssVarList.push( [ 'srfm-color-scheme-text-on-primary', value || '#FFFFFF' ] );
		} else if ( option === 'field_spacing' ) {
			cssVarList.push( [ 'srfm-field-spacing', value || 'small' ] );
		} else if ( option === 'submit_button_alignment' ) {
			cssVarList.push( [ 'srfm-submit-alignment', value || 'left' ] );
			cssVarList.push( [ 'srfm-submit-width-backend', 'max-content' ] );

			updateMeta( '_srfm_submit_width_backend', 'max-content' );

			if ( value === 'left' ) {
				cssVarList.push( [ 'srfm-submit-alignment-backend', '100%' ] );
				updateMeta( '_srfm_submit_alignment_backend', '100%' );
			} else if ( value === 'right' ) {
				cssVarList.push( [ 'srfm-submit-alignment-backend', '0%' ] );
				updateMeta( '_srfm_submit_alignment_backend', '0%' );
			} else if ( value === 'center' ) {
				cssVarList.push( [ 'srfm-submit-alignment-backend', '50%' ] );
				updateMeta( '_srfm_submit_alignment_backend', '50%' );
			} else if ( value === 'justify' ) {
				cssVarList.push( [ 'srfm-submit-alignment-backend', '50%' ] );
				cssVarList.push( [ 'srfm-submit-width-backend', 'auto' ] );

				updateMeta( '_srfm_submit_alignment_backend', '50%' );
			}
		}

		// Update the CSS variables.
		cssVarList?.forEach( ( [ varName, varValue ] ) => setCssValueInRoot( varName, varValue ) );

		editPost( {
			meta: {
				_srfm_forms_styling: { ...formStyling, [ option ]: value },
			},
		} );
	}

	return (
		<>
			<SRFMAdvancedPanelBody
				title={ __( 'Form Container', 'sureforms' ) }
				initialOpen={ true }
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
				<MultiButtonsControl
					label={ __( 'Field Spacing', 'sureforms' ) }
					data={ {
						value: formStyling?.field_spacing || 'small',
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
				{ ! isInlineButtonBlockPresent && (
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
				) }
			</SRFMAdvancedPanelBody>
			{ isPageBreak && (
				<SRFMAdvancedPanelBody
					title={ __( 'Page Break Buttons', 'sureforms' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Inherit From Theme', 'sureforms' ) }
						checked={
							sureformsKeys._srfm_page_break_inherit_theme_button
						}
						onChange={ ( value ) => {
							updateMeta(
								'_srfm_page_break_inherit_theme_button',
								value
							);
						} }
					/>
					{ ! sureformsKeys._srfm_page_break_inherit_theme_button && (
						<>
							<AdvancedPopColorControl
								label={ __( 'Text Color', 'sureforms' ) }
								colorValue={
									sureformsKeys._srfm_page_break_button_text_color
								}
								data={ {
									value: sureformsKeys._srfm_page_break_button_text_color,
									label: '_srfm_page_break_button_text_color',
								} }
								onColorChange={ ( colorValue ) => {
									if (
										colorValue !==
										sureformsKeys._srfm_page_break_button_text_color
									) {
										updateMeta(
											'_srfm_page_break_button_text_color',
											colorValue
										);
									}
								} }
								value={
									sureformsKeys._srfm_page_break_button_text_color
								}
								isFormSpecific={ true }
							/>
							{ sureformsKeys._srfm_page_break_button_bg_type ===
								'filled' && (
								<>
									<p className="components-base-control__help" />
									<AdvancedPopColorControl
										label={ __(
											'Background Color',
											'sureforms'
										) }
										colorValue={
											sureformsKeys._srfm_page_break_button_bg_color
										}
										data={ {
											value: sureformsKeys._srfm_page_break_button_bg_color,
											label: '_srfm_page_break_button_bg_color',
										} }
										onColorChange={ ( colorValue ) => {
											if (
												colorValue !==
												sureformsKeys._srfm_page_break_button_bg_color
											) {
												updateMeta(
													'_srfm_page_break_button_bg_color',
													colorValue
												);
											}
										} }
										value={
											sureformsKeys._srfm_page_break_button_bg_color
										}
										isFormSpecific={ true }
									/>
								</>
							) }
							{ sureformsKeys._srfm_page_break_button_bg_type ===
								'filled' && (
								<>
									<p className="components-base-control__help" />
									<AdvancedPopColorControl
										label={ __(
											'Border Color',
											'sureforms'
										) }
										colorValue={
											sureformsKeys._srfm_page_break_button_border_color
										}
										data={ {
											value: sureformsKeys._srfm_page_break_button_border_color,
											label: '_srfm_page_break_button_border_color',
										} }
										onColorChange={ ( colorValue ) => {
											if (
												colorValue !==
												sureformsKeys._srfm_page_break_button_border_color
											) {
												updateMeta(
													'_srfm_page_break_button_border_color',
													colorValue
												);
											}
										} }
										value={
											sureformsKeys._srfm_page_break_button_border_color
										}
										isFormSpecific={ true }
									/>
									<p className="components-base-control__help" />
									<Range
										label={ __(
											'Border Width',
											'sureforms'
										) }
										value={
											sureformsKeys._srfm_page_break_button_border_width
										}
										min={ 0 }
										max={ 10 }
										displayUnit={ false }
										data={ {
											value: sureformsKeys._srfm_page_break_button_border_width,
											label: '_srfm_page_break_button_border_width',
										} }
										onChange={ ( value ) =>
											updateMeta(
												'_srfm_page_break_button_border_width',
												value
											)
										}
										isFormSpecific={ true }
									/>
									<p className="components-base-control__help" />
									<Range
										label={ __(
											'Border Radius',
											'sureforms'
										) }
										value={
											sureformsKeys._srfm_page_break_button_border_radius
										}
										min={ 1 }
										max={ 100 }
										displayUnit={ false }
										data={ {
											value: sureformsKeys._srfm_page_break_button_border_radius,
											label: '_srfm_page_break_button_border_radius',
										} }
										onChange={ ( value ) =>
											updateMeta(
												'_srfm_page_break_button_border_radius',
												value
											)
										}
										isFormSpecific={ true }
									/>
								</>
							) }
						</>
					) }
				</SRFMAdvancedPanelBody>
			) }
		</>
	);
}

export default StyleSettings;
