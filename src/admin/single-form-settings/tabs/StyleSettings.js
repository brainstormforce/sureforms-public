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

	// if device type is desktop then change the submit button
	useEffect( () => {
		setTimeout( () => {
			const tabletPreview =
				document.getElementsByClassName( 'is-tablet-preview' );
			const mobilePreview =
				document.getElementsByClassName( 'is-mobile-preview' );
			if ( tabletPreview.length !== 0 || mobilePreview.length !== 0 ) {
				const preview = tabletPreview[ 0 ] || mobilePreview[ 0 ];
				if ( preview ) {
					const iframe = preview.querySelector( 'iframe' );
					const iframeDocument =
						iframe?.contentWindow.document ||
						iframe?.contentDocument;
					const iframeBody = iframeDocument
						?.querySelector( 'html' )
						?.querySelector( 'body' );

					setSubmitBtn(
						iframeBody.querySelector( '.srfm-submit-richtext' )
					);
					setSubmitBtnCtn(
						iframeBody.querySelector( '.srfm-submit-btn-container' )
					);
					submitButtonInherit();
				}
			} else {
				setSubmitBtnCtn(
					document.querySelector( '.srfm-submit-btn-container' )
				);
				setSubmitBtn(
					document.querySelector( '.srfm-submit-richtext' )
				);
				submitButtonInherit();
			}
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
			formStyling?.text_color || defaultTextColor
		);
		root.style.setProperty(
			'--srfm-color-input-text',
			formStyling?.text_color || defaultTextColor
		);
		root.style.setProperty(
			'--srfm-color-input-description',
			formStyling?.text_color ? `rgba( from ${ formStyling.text_color } r g b / 0.65)` : `rgba( from ${ defaultTextColor } r g b / 0.65)`
		);
		root.style.setProperty(
			'--srfm-color-input-prefix',
			formStyling?.text_color ? `rgba( from ${ formStyling.text_color } r g b / 0.65)` : `rgba( from ${ defaultTextColor } r g b / 0.65)`
		);
		root.style.setProperty(
			'--srfm-color-input-background',
			formStyling?.text_color ? `rgba( from ${ formStyling.text_color } r g b / 0.02)` : `rgba( from ${ defaultTextColor } r g b / 0.02)`
		);
		root.style.setProperty(
			'--srfm-color-input-background-disabled',
			formStyling?.text_color ? `rgba( from ${ formStyling.text_color } r g b / 0.05)` : `rgba( from ${ defaultTextColor } r g b / 0.05)`
		);
		root.style.setProperty(
			'--srfm-color-input-border',
			formStyling?.text_color ? `rgba( from ${ formStyling.text_color } r g b / 0.25)` : `rgba( from ${ defaultTextColor } r g b / 0.25)`
		);
		root.style.setProperty(
			'--srfm-color-input-border-disabled',
			formStyling?.text_color ? `rgba( from ${ formStyling.text_color } r g b / 0.15)` : `rgba( from ${ defaultTextColor } r g b / 0.15)`
		);

		// Button
		// Button text color
		root.style.setProperty(
			'--srfm-btn-text-color',
			formStyling?.text_color_on_primary || '#FFFFFF'
		);
		// Button bg Color
		root.style.setProperty(
			'--srfm-btn-bg-color',
			formStyling?.primary_color || '#0C78FB'
		);
		// btn border color
		root.style.setProperty(
			'--srfm-btn-border-color',
			formStyling?.primary_color || '#0C78FB'
		);
		// btn border width
		// root.style.setProperty(
		// 	'--srfm-btn-border-width',
		// 	sureformsKeys._srfm_button_border_width
		// 		? sureformsKeys._srfm_button_border_width + 'px'
		// 		: '0px'
		// );
		// btn border radius
		root.style.setProperty(
			'--srfm-btn-border-radius',
			sureformsKeys._srfm_button_border_radius
				? sureformsKeys._srfm_button_border_radius + 'px'
				: '4px'
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

		// if ( option === '_srfm_color1' ) {
		// 	root.style.setProperty(
		// 		'--srfm-color-scheme-primary',
		// 		value ? value : '#D54407'
		// 	);
		// }
		// if ( option === '_srfm_label_color' ) {
		// 	root.style.setProperty(
		// 		'--srfm-color-scheme-text-on-primary',
		// 		value ? value : '#111827'
		// 	);
		// }
		// if ( option === '_srfm_help_color' ) {
		// 	const defaultHelpColor = '#4B5563';

		// 	root.style.setProperty(
		// 		'--srfm-color-scheme-text',
		// 		value ? value : defaultHelpColor
		// 	);
		// 	root.style.setProperty(
		// 		'--srfm-color-input-label',
		// 		value ? value : defaultHelpColor
		// 	);
		// 	root.style.setProperty(
		// 		'--srfm-color-input-placeholder',
		// 		value ? value : defaultHelpColor
		// 	);
		// 	root.style.setProperty(
		// 		'--srfm-color-input-text',
		// 		value ? value : defaultHelpColor
		// 	);
		// 	root.style.setProperty(
		// 		'--srfm-color-input-description',
		// 		value ? `rgba( from ${value} r g b / 0.65)` : `rgba( from ${defaultHelpColor} r g b / 0.65)`
		// 	);
		// 	root.style.setProperty(
		// 		'--srfm-color-input-prefix',
		// 		value ? `rgba( from ${value} r g b / 0.65)` : `rgba( from ${defaultHelpColor} r g b / 0.65)`
		// 	);
		// 	root.style.setProperty(
		// 		'--srfm-color-input-background',
		// 		value ? `rgba( from ${value} r g b / 0.02)` : `rgba( from ${defaultHelpColor} r g b / 0.02)`
		// 	);
		// 	root.style.setProperty(
		// 		'--srfm-color-input-background-disabled',
		// 		value ? `rgba( from ${value} r g b / 0.05)` : `rgba( from ${defaultHelpColor} r g b / 0.05)`
		// 	);
		// 	root.style.setProperty(
		// 		'--srfm-color-input-border',
		// 		value ? `rgba( from ${value} r g b / 0.25)` : `rgba( from ${defaultHelpColor} r g b / 0.25)`
		// 	);
		// 	root.style.setProperty(
		// 		'--srfm-color-input-border-disabled',
		// 		value ? `rgba( from ${value} r g b / 0.15)` : `rgba( from ${defaultHelpColor} r g b / 0.15)`
		// 	);
		// }

		// if ( option === '_srfm_fontsize' ) {
		// 	root.style.setProperty(
		// 		'--srfm_fontsize',
		// 		value ? value + 'px' : 'none'
		// 	);
		// }

		// if ( option === '_srfm_submit_alignment' ) {
		// 	root.style.setProperty(
		// 		'--srfm-submit-alignment',
		// 		value ? value : 'left'
		// 	);
		// 	root.style.setProperty(
		// 		'--srfm-submit-width-backend',
		// 		'max-content'
		// 	);
		// 	updateMeta( '_srfm_submit_width_backend', 'max-content' );

		// 	if ( value === 'left' ) {
		// 		root.style.setProperty(
		// 			'--srfm-submit-alignment-backend',
		// 			'100%'
		// 		);
		// 		updateMeta( '_srfm_submit_alignment_backend', '100%' );
		// 	}
		// 	if ( value === 'right' ) {
		// 		root.style.setProperty(
		// 			'--srfm-submit-alignment-backend',
		// 			'0%'
		// 		);
		// 		updateMeta( '_srfm_submit_alignment_backend', '0%' );
		// 	}
		// 	if ( value === 'center' ) {
		// 		root.style.setProperty(
		// 			'--srfm-submit-alignment-backend',
		// 			'50%'
		// 		);
		// 		updateMeta( '_srfm_submit_alignment_backend', '50%' );
		// 	}
		// 	if ( value === 'justify' ) {
		// 		root.style.setProperty(
		// 			'--srfm-submit-alignment-backend',
		// 			'50%'
		// 		);
		// 		root.style.setProperty( '--srfm-submit-width-backend', 'auto' );
		// 		updateMeta( '_srfm_submit_alignment_backend', '50%' );
		// 	}
		// }

		// Button
		if ( option === '_srfm_button_text_color' ) {
			root.style.setProperty(
				'--srfm-btn-text-color',
				value ? value : '#000000'
			);
		}
		// Will be used later
		// if ( option === '_srfm_btn_bg_type' ) {
		// 	if ( value === 'transparent' ) {
		// 		submitBtn.classList.add( 'srfm-btn-bg-transparent' );
		// 	} else {
		// 		submitBtn.classList.remove( 'srfm-btn-bg-transparent' );
		// 		submitBtn.classList.add( 'srfm-btn-bg-color' );
		// 	}
		// }
		if ( option === '_srfm_button_bg_color' ) {
			submitBtn.classList.add( 'srfm-btn-bg-color' );
			submitBtn.classList.remove( 'srfm-btn-bg-transparent' );
			root.style.setProperty( '--srfm-btn-bg-color', value ? value : '' );
		}
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

	function updateFormStyling( option, value ) {
		if ( option === 'primary_color' ) {
			root.style.setProperty(
				'--srfm-color-scheme-primary',
				value || '#0C78FB'
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
				value ? `rgba( from ${ value } r g b / 0.65)` : `rgba( from ${ defaultTextColor } r g b / 0.65)`
			);
			root.style.setProperty(
				'--srfm-color-input-prefix',
				value ? `rgba( from ${ value } r g b / 0.65)` : `rgba( from ${ defaultTextColor } r g b / 0.65)`
			);
			root.style.setProperty(
				'--srfm-color-input-background',
				value ? `rgba( from ${ value } r g b / 0.02)` : `rgba( from ${ defaultTextColor } r g b / 0.02)`
			);
			root.style.setProperty(
				'--srfm-color-input-background-disabled',
				value ? `rgba( from ${ value } r g b / 0.05)` : `rgba( from ${ defaultTextColor } r g b / 0.05)`
			);
			root.style.setProperty(
				'--srfm-color-input-border',
				value ? `rgba( from ${ value } r g b / 0.25)` : `rgba( from ${ defaultTextColor } r g b / 0.25)`
			);
			root.style.setProperty(
				'--srfm-color-input-border-disabled',
				value ? `rgba( from ${ value } r g b / 0.15)` : `rgba( from ${ defaultTextColor } r g b / 0.15)`
			);
		}

		if ( option === 'text_color_on_primary' ) {
			root.style.setProperty(
				'--srfm-color-scheme-text-on-primary',
				value || '#FFFFFF'
			);

			root.style.setProperty(
				'--srfm-btn-text-color',
				formStyling?.text_color_on_primary || '#FFFFFF'
			);
		}

		if ( option === 'field_spacing' ) {
			root.style.setProperty(
				'--srfm-field-spacing',
				value || 'small'
			);
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
			meta: { _srfm_forms_styling: { ...formStyling, [ option ]: value } },
		} );
	}

	return (
		<>
			<SRFMAdvancedPanelBody
				title={ __( 'Form Container', 'sureforms' ) }
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
						if ( colorValue !== formStyling?.text_color_on_primary ) {
							updateMeta( 'text_color_on_primary', colorValue );
						}
					} }
					value={ formStyling?.text_color_on_primary }
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<AdvancedPopColorControl
					label={ __( 'Background Color', 'sureforms' ) }
					colorValue={ formStyling?.background_color }
					data={ {
						value: formStyling?.background_color,
						label: 'background_color',
					} }
					onColorChange={ ( colorValue ) => {
						if ( colorValue !== formStyling?.background_color ) {
							updateMeta( 'background_color', colorValue );
						}
					} }
					value={ formStyling?.background_color }
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
					} }
				/>
				{ ! isInlineButtonBlockPresent && (
					<>
						<p className="components-base-control__help" />
						<MultiButtonsControl
							label={ __( 'Submit Button Alignment', 'sureforms' ) }
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
								updateFormStyling( 'submit_button_alignment', value || 'left' );
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

				{ /* Will be impleted properly later */ }
				{ /* <p className="components-base-control__help" />
				<Range
					label={ __( 'Font Size', 'sureforms' ) }
					value={ sureformsKeys._srfm_fontsize }
					min={ 16 }
					max={ 24 }
					displayUnit={ false }
					data={ {
						value: sureformsKeys._srfm_fontsize,
						label: '_srfm_fontsize',
					} }
					onChange={ ( value ) =>
						updateMeta( '_srfm_fontsize', value )
					}
					isFormSpecific={ true }
				/> */ }
				{ /* { sureformsKeys._srfm_show_labels && (
					<>
						<p className="components-base-control__help" />
						<AdvancedPopColorControl
							label={ __( 'Label Color', 'sureforms' ) }
							colorValue={ sureformsKeys._srfm_label_color }
							data={ {
								value: sureformsKeys._srfm_label_color,
								label: '_srfm_label_color',
							} }
							onColorChange={ ( colorValue ) => {
								if (
									colorValue !==
									sureformsKeys._srfm_label_color
								) {
									updateMeta(
										'_srfm_label_color',
										colorValue
									);
								}
							} }
							value={ sureformsKeys._srfm_label_color }
							isFormSpecific={ true }
						/>{ ' ' }
					</>
				) }
				<p className="components-base-control__help" />
				<AdvancedPopColorControl
					label={ __( 'Help Text Color', 'sureforms' ) }
					colorValue={ sureformsKeys._srfm_help_color }
					data={ {
						value: sureformsKeys._srfm_help_color,
						label: '_srfm_help_color',
					} }
					onColorChange={ ( colorValue ) => {
						if ( colorValue !== sureformsKeys._srfm_help_color ) {
							updateMeta( '_srfm_help_color', colorValue );
						}
					} }
					value={ sureformsKeys._srfm_help_color }
					isFormSpecific={ true }
				/> */ }
			</SRFMAdvancedPanelBody>
			{ /* <SRFMAdvancedPanelBody
				title={ __( 'Input', 'sureforms' ) }
				initialOpen={ false }
			>
				<AdvancedPopColorControl
					label={ __( 'Input Text Color', 'sureforms' ) }
					colorValue={ sureformsKeys._srfm_input_text_color }
					data={ {
						value: sureformsKeys._srfm_input_text_color,
						label: '_srfm_input_text_color',
					} }
					onColorChange={ ( colorValue ) => {
						if (
							colorValue !== sureformsKeys._srfm_input_text_color
						) {
							updateMeta( '_srfm_input_text_color', colorValue );
						}
					} }
					value={ sureformsKeys._srfm_input_text_color }
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<AdvancedPopColorControl
					label={ __( 'Placeholder Color', 'sureforms' ) }
					colorValue={ sureformsKeys._srfm_input_placeholder_color }
					data={ {
						value: sureformsKeys._srfm_input_placeholder_color,
						label: '_srfm_input_placeholder_color',
					} }
					onColorChange={ ( colorValue ) => {
						if (
							colorValue !==
							sureformsKeys._srfm_input_placeholder_color
						) {
							updateMeta(
								'_srfm_input_placeholder_color',
								colorValue
							);
						}
					} }
					value={ sureformsKeys._srfm_input_placeholder_color }
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<AdvancedPopColorControl
					label={ __( 'Field Background Color', 'sureforms' ) }
					colorValue={ sureformsKeys._srfm_input_bg_color }
					data={ {
						value: sureformsKeys._srfm_input_bg_color,
						label: '_srfm_input_bg_color',
					} }
					onColorChange={ ( colorValue ) => {
						if (
							colorValue !== sureformsKeys._srfm_input_bg_color
						) {
							updateMeta( '_srfm_input_bg_color', colorValue );
						}
					} }
					value={ sureformsKeys._srfm_input_bg_color }
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<AdvancedPopColorControl
					label={ __( 'Border Color', 'sureforms' ) }
					colorValue={ sureformsKeys._srfm_input_border_color }
					data={ {
						value: sureformsKeys._srfm_input_border_color,
						label: '_srfm_input_border_color',
					} }
					onColorChange={ ( colorValue ) => {
						if (
							colorValue !==
							sureformsKeys._srfm_input_border_color
						) {
							updateMeta(
								'_srfm_input_border_color',
								colorValue
							);
						}
					} }
					value={ sureformsKeys._srfm_input_border_color }
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<Range
					label={ __( 'Border Width', 'sureforms' ) }
					value={ sureformsKeys._srfm_input_border_width }
					min={ 1 }
					max={ 10 }
					displayUnit={ false }
					data={ {
						value: sureformsKeys._srfm_input_border_width,
						label: '_srfm_input_border_width',
					} }
					onChange={ ( value ) =>
						updateMeta( '_srfm_input_border_width', value )
					}
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<Range
					label={ __( 'Border Radius', 'sureforms' ) }
					value={ sureformsKeys._srfm_input_border_radius }
					min={ 0 }
					max={ 100 }
					displayUnit={ false }
					data={ {
						value: sureformsKeys._srfm_input_border_radius,
						label: '_srfm_input_border_radius',
					} }
					onChange={ ( value ) =>
						updateMeta( '_srfm_input_border_radius', value )
					}
					isFormSpecific={ true }
				/>
			</SRFMAdvancedPanelBody> */ }
			{ /* <SRFMAdvancedPanelBody
				title={ __( 'Error Message', 'sureforms' ) }
				initialOpen={ false }
			>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'sureforms' ) }
					colorValue={ sureformsKeys._srfm_field_error_color }
					data={ {
						value: sureformsKeys._srfm_field_error_color,
						label: '_srfm_field_error_color',
					} }
					onColorChange={ ( colorValue ) => {
						if (
							colorValue !== sureformsKeys._srfm_field_error_color
						) {
							updateMeta( '_srfm_field_error_color', colorValue );
						}
					} }
					value={ sureformsKeys._srfm_field_error_color }
					isFormSpecific={ true }
				/>
				<AdvancedPopColorControl
					label={ __( 'Surface Color', 'sureforms' ) }
					colorValue={ sureformsKeys._srfm_field_error_surface_color }
					data={ {
						value: sureformsKeys._srfm_field_error_surface_color,
						label: '_srfm_field_error_surface_color',
					} }
					onColorChange={ ( colorValue ) => {
						if (
							colorValue !==
							sureformsKeys._srfm_field_error_surface_color
						) {
							updateMeta(
								'_srfm_field_error_surface_color',
								colorValue
							);
						}
					} }
					value={ sureformsKeys._srfm_field_error_surface_color }
					isFormSpecific={ true }
				/>
				<AdvancedPopColorControl
					label={ __( 'Background Color', 'sureforms' ) }
					colorValue={ sureformsKeys._srfm_field_error_bg_color }
					data={ {
						value: sureformsKeys._srfm_field_error_bg_color,
						label: '_srfm_field_error_bg_color',
					} }
					onColorChange={ ( colorValue ) => {
						if (
							colorValue !==
							sureformsKeys._srfm_field_error_bg_color
						) {
							updateMeta(
								'_srfm_field_error_bg_color',
								colorValue
							);
						}
					} }
					value={ sureformsKeys._srfm_field_error_bg_color }
					isFormSpecific={ true }
					disableBottomSeparator={ true }
				/>
			</SRFMAdvancedPanelBody> */ }
			{ /* <SRFMAdvancedPanelBody
				title={ __( 'Submit Button', 'sureforms' ) }
				initialOpen={ false }
			>
				<ToggleControl
					label={ __( 'Inherit From Theme', 'sureforms' ) }
					checked={ sureformsKeys._srfm_inherit_theme_button }
					onChange={ ( value ) => {
						updateMeta( '_srfm_inherit_theme_button', value );
					} }
				/>
				{ ! sureformsKeys._srfm_inherit_theme_button && (
					<>
						<AdvancedPopColorControl
							label={ __( 'Text Color', 'sureforms' ) }
							colorValue={ sureformsKeys._srfm_button_text_color }
							data={ {
								value: sureformsKeys._srfm_button_text_color,
								label: '_srfm_button_text_color',
							} }
							onColorChange={ ( colorValue ) => {
								if (
									colorValue !==
									sureformsKeys._srfm_button_text_color
								) {
									updateMeta(
										'_srfm_button_text_color',
										colorValue
									);
								}
							} }
							value={ sureformsKeys._srfm_button_text_color }
							isFormSpecific={ true }
						/>
						{ sureformsKeys._srfm_btn_bg_type === 'filled' && (
							<>
								<p className="components-base-control__help" />
								<AdvancedPopColorControl
									label={ __(
										'Background Color',
										'sureforms'
									) }
									colorValue={
										sureformsKeys._srfm_button_bg_color
									}
									data={ {
										value: sureformsKeys._srfm_button_bg_color,
										label: '_srfm_button_bg_color',
									} }
									onColorChange={ ( colorValue ) => {
										if (
											colorValue !==
											sureformsKeys._srfm_button_bg_color
										) {
											updateMeta(
												'_srfm_button_bg_color',
												colorValue
											);
										}
									} }
									value={
										sureformsKeys._srfm_button_bg_color
									}
									isFormSpecific={ true }
								/>
							</>
						) }
						{ sureformsKeys._srfm_btn_bg_type === 'filled' && (
							<>
								<p className="components-base-control__help" />
								<AdvancedPopColorControl
									label={ __( 'Border Color', 'sureforms' ) }
									colorValue={
										sureformsKeys._srfm_button_border_color
									}
									data={ {
										value: sureformsKeys._srfm_button_border_color,
										label: '_srfm_button_border_color',
									} }
									onColorChange={ ( colorValue ) => {
										if (
											colorValue !==
											sureformsKeys._srfm_button_border_color
										) {
											updateMeta(
												'_srfm_button_border_color',
												colorValue
											);
										}
									} }
									value={
										sureformsKeys._srfm_button_border_color
									}
									isFormSpecific={ true }
								/>
								<p className="components-base-control__help" />
								<Range
									label={ __( 'Border Width', 'sureforms' ) }
									value={
										sureformsKeys._srfm_button_border_width
									}
									min={ 0 }
									max={ 10 }
									displayUnit={ false }
									data={ {
										value: sureformsKeys._srfm_button_border_width,
										label: '_srfm_button_border_width',
									} }
									onChange={ ( value ) =>
										updateMeta(
											'_srfm_button_border_width',
											value
										)
									}
									isFormSpecific={ true }
								/>
								<p className="components-base-control__help" />
								<Range
									label={ __( 'Border Radius', 'sureforms' ) }
									value={
										sureformsKeys._srfm_button_border_radius
									}
									min={ 1 }
									max={ 100 }
									displayUnit={ false }
									data={ {
										value: sureformsKeys._srfm_button_border_radius,
										label: '_srfm_button_border_radius',
									} }
									onChange={ ( value ) =>
										updateMeta(
											'_srfm_button_border_radius',
											value
										)
									}
									isFormSpecific={ true }
								/>
							</>
						) }{ ' ' }
					</>
				) }
				{ ! isInlineButtonBlockPresent && (
					<>
						<p className="components-base-control__help" />
						<MultiButtonsControl
							label={ __( 'Button Alignment', 'sureforms' ) }
							data={ {
								value: sureformsKeys._srfm_submit_alignment,
								label: '_srfm_submit_alignment',
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
								if (
									sureformsKeys._srfm_submit_alignment ===
									value
								) {
									updateMeta(
										'_srfm_submit_alignment',
										'left'
									);
									updateMeta( '_srfm_submit_width', '' );
								} else if ( 'justify' === value ) {
									updateMeta(
										'_srfm_submit_alignment',
										value
									);
									updateMeta( '_srfm_submit_width', '100%' );
									updateMeta(
										'_srfm_submit_width_backend',
										'auto'
									);
								} else {
									updateMeta(
										'_srfm_submit_alignment',
										value
									);
									updateMeta( '_srfm_submit_width', '' );
								}
							} }
						/>
					</>
				) }
			</SRFMAdvancedPanelBody> */ }
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
