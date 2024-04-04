import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import SRFMMediaPicker from '@Components/image';
import Range from '@Components/range/Range.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
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
	const { defaultKeys } = props;

	let sureformsKeys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);
	const root = document.documentElement.querySelector( 'body' );
	const deviceType = useDeviceType();
	const [ submitBtn, setSubmitBtn ] = useState(
		document.querySelector( '.srfm-submit-button' )
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
						iframeBody.querySelector( '.srfm-submit-button' )
					);
					submitButtonInherit();
				}
			} else {
				setSubmitBtn( document.querySelector( '.srfm-submit-button' ) );
				submitButtonInherit();
			}
		}, 1000 );
	}, [ deviceType, submitBtn, sureformsKeys._srfm_inherit_theme_button ] );

	function submitButtonInherit() {
		const inheritClass = 'wp-block-button__link';
		const customClass = 'srfm-btn-bg-color';
		const btnClass =
			sureformsKeys?._srfm_inherit_theme_button &&
			sureformsKeys._srfm_inherit_theme_button
				? inheritClass
				: customClass;

		if ( submitBtn ) {
			if ( submitBtn.classList.contains( inheritClass ) ) {
				submitBtn.classList.remove( inheritClass );
			}
			if ( submitBtn.classList.contains( customClass ) ) {
				submitBtn.classList.remove( customClass );
			}
			submitBtn.classList.add( btnClass );
		}
	}

	if ( sureformsKeys ) {
		// Form Container
		// Primary color
		root.style.setProperty(
			'--srfm-primary-color',
			sureformsKeys._srfm_color1 ? sureformsKeys._srfm_color1 : '#0284C7'
		);
		// Label color
		root.style.setProperty(
			'--srfm-label-text-color',
			sureformsKeys._srfm_label_color
				? sureformsKeys._srfm_label_color
				: 'none'
		);
		// Help text color
		root.style.setProperty(
			'--srfm-help-color',
			sureformsKeys._srfm_help_color
				? sureformsKeys._srfm_help_color
				: '#6b7280'
		);
		// Background image
		root.style.setProperty(
			'--srfm-bg-image',
			sureformsKeys._srfm_bg_image
				? 'url(' + sureformsKeys._srfm_bg_image + ')'
				: 'none'
		);
		// Background color
		root.style.setProperty(
			'--srfm-bg-color',
			sureformsKeys._srfm_bg_color
				? sureformsKeys._srfm_bg_color
				: '#ffffff'
		);
		// Font Size
		root.style.setProperty(
			'--srfm-font-size',
			sureformsKeys._srfm_fontsize
				? sureformsKeys._srfm_fontsize + 'px'
				: 'none'
		);

		// Input
		// Input text color
		root.style.setProperty(
			'--srfm-body-input-color',
			sureformsKeys._srfm_input_text_color
				? sureformsKeys._srfm_input_text_color
				: '#4B5563'
		);
		// Input placeholder color
		root.style.setProperty(
			'--srfm-placeholder-color',
			sureformsKeys._srfm_input_placeholder_color
				? sureformsKeys._srfm_input_placeholder_color
				: '#9ca3af'
		);
		// Input background color
		root.style.setProperty(
			'--srfm-base-background-color',
			sureformsKeys._srfm_input_bg_color
				? sureformsKeys._srfm_input_bg_color
				: '#ffffff'
		);
		// Input border color
		root.style.setProperty(
			'--srfm-border-color',
			sureformsKeys._srfm_input_border_color
				? sureformsKeys._srfm_input_border_color
				: '#D0D5DD'
		);
		// Input border width
		root.style.setProperty(
			'--srfm-border',
			sureformsKeys._srfm_input_border_width
				? sureformsKeys._srfm_input_border_width + 'px'
				: '1px'
		);
		// Input border radius
		root.style.setProperty(
			'--srfm-border-radius',
			sureformsKeys._srfm_input_border_radius
				? sureformsKeys._srfm_input_border_radius + 'px'
				: '4px'
		);

		// Error
		// Error color & asterisk color
		root.style.setProperty(
			'--srfm-error-text-color',
			sureformsKeys._srfm_field_error_color
				? sureformsKeys._srfm_field_error_color
				: '#dc2626'
		);

		// Button
		// Button text color
		root.style.setProperty(
			'--srfm-btn-text-color',
			sureformsKeys._srfm_button_text_color
				? sureformsKeys._srfm_button_text_color
				: '#000000'
		);
		// Button bg Color
		root.style.setProperty(
			'--srfm-btn-bg-color',
			sureformsKeys._srfm_button_bg_color
				? sureformsKeys._srfm_button_bg_color
				: ''
		);
		// btn border color
		root.style.setProperty(
			'--srfm-btn-border-color',
			sureformsKeys._srfm_button_border_color
				? sureformsKeys._srfm_button_border_color
				: '#000000'
		);
		// btn border width
		root.style.setProperty(
			'--srfm-btn-border-width',
			sureformsKeys._srfm_button_border_width
				? sureformsKeys._srfm_button_border_width + 'px'
				: '0px'
		);
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
			sureformsKeys._srfm_submit_alignment
				? sureformsKeys._srfm_submit_alignment
				: 'none'
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
		let value_id = 0;
		let key_id = '';

		// Form Container
		if ( option === '_srfm_bg_image' ) {
			if ( value ) {
				value_id = value.id;
				value = value.sizes.full.url;
			}
			key_id = option + '_id';
			root.style.setProperty(
				'--srfm-bg-image',
				value ? 'url(' + value + ')' : 'none'
			);
		}

		if ( option === '_srfm_color1' ) {
			root.style.setProperty(
				'--srfm-primary-color',
				value ? value : '#0284C7'
			);
		}
		if ( option === '_srfm_fontsize' ) {
			root.style.setProperty(
				'--srfm_fontsize',
				value ? value + 'px' : 'none'
			);
		}
		if ( option === '_srfm_label_color' ) {
			root.style.setProperty(
				'--srfm-label-text-color',
				value ? value : '#0284C7'
			);
		}
		if ( option === '_srfm_help_color' ) {
			root.style.setProperty(
				'--srfm-help-color',
				value ? value : '#6b7280'
			);
		}

		// Input
		if ( option === '_srfm_input_text_color' ) {
			root.style.setProperty(
				'--srfm-body-input-color',
				value ? value : '#4b5563'
			);
		}
		if ( option === '_srfm_input_placeholder_color' ) {
			root.style.setProperty(
				'--srfm-placeholder-color',
				value ? value : '#9ca3af'
			);
		}
		if ( option === '_srfm_input_bg_color' ) {
			root.style.setProperty(
				'--srfm-base-background-color',
				value ? value : '#ffffff'
			);
		}
		if ( option === '_srfm_input_border_color' ) {
			root.style.setProperty(
				'--srfm-border-color',
				value ? value : '#D0D5DD'
			);
		}
		if ( option === '_srfm_input_border_width' ) {
			root.style.setProperty(
				'--srfm-border',
				value ? value + 'px' : '1px'
			);
		}
		if ( option === '_srfm_input_border_radius' ) {
			root.style.setProperty(
				'--srfm-border-radius',
				value ? value + 'px' : '4px'
			);
		}

		// Error color & asterisk color
		if ( option === '_srfm_field_error_color' ) {
			root.style.setProperty(
				'--srfm-error-text-color',
				value ? value : '#dc2626'
			);
		}

		if ( option === '_srfm_submit_alignment' ) {
			root.style.setProperty(
				'--srfm-submit-alignment',
				value ? value : 'left'
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

	const onSelectRestImage = ( media ) => {
		let imageUrl = null;
		if ( ! media || ! media.url ) {
			imageUrl = null;
		} else {
			imageUrl = media;
		}

		if ( ! media.type || 'image' !== media.type ) {
			imageUrl = null;
		}
		updateMeta( '_srfm_bg_image', imageUrl );
	};

	/*
	 * Event to set Image as null while removing it.
	 */
	const onRemoveRestImage = () => {
		updateMeta( '_srfm_bg_image', '' );
	};

	return (
		<>
			{ sureformsKeys._srfm_instant_form && (
				<SRFMAdvancedPanelBody
					title={ __( 'Intstant Form', 'sureforms' ) }
					initialOpen={ false }
				>
					<Range
						label={ __( 'Form Container Width', 'sureforms' ) }
						value={ sureformsKeys._srfm_form_container_width }
						min={ 650 }
						max={ 1000 }
						displayUnit={ false }
						data={ {
							value: sureformsKeys._srfm_form_container_width,
							label: '_srfm_form_container_width',
						} }
						onChange={ ( value ) =>
							updateMeta( '_srfm_form_container_width', value )
						}
						isFormSpecific={ true }
					/>
					<p className="components-base-control__help" />
					<MultiButtonsControl
						label={ __( 'Background Type', 'sureforms' ) }
						data={ {
							value: sureformsKeys._srfm_bg_type,
							label: '_srfm_bg_type',
						} }
						options={ [
							{
								value: 'image',
								label: __( 'Image', 'sureforms' ),
							},
							{
								value: 'color',
								label: __( 'Color', 'sureforms' ),
							},
						] }
						showIcons={ false }
						onChange={ ( value ) => {
							updateMeta( '_srfm_bg_type', value );
							if ( value === 'color' ) {
								updateMeta( '_srfm_bg_image', '' );
								updateMeta(
									'_srfm_bg_color',
									sureformsKeys._srfm_bg_color
										? sureformsKeys._srfm_bg_color
										: '#ffffff'
								);
							} else {
								updateMeta( '_srfm_bg_color', '' );
								updateMeta(
									'_srfm_bg_image',
									sureformsKeys._srfm_bg_image
										? sureformsKeys._srfm_bg_image
										: ''
								);
							}
						} }
					/>
					<p className="components-base-control__help" />
					{ sureformsKeys._srfm_bg_type === 'image' ? (
						<SRFMMediaPicker
							label={ __( 'Background Image', 'sureforms' ) }
							onSelectImage={ onSelectRestImage }
							backgroundImage={ sureformsKeys._srfm_bg_image }
							onRemoveImage={ onRemoveRestImage }
							isFormSpecific={ true }
						/>
					) : (
						<AdvancedPopColorControl
							label={ __( 'Background Color', 'sureforms' ) }
							colorValue={ sureformsKeys._srfm_bg_color }
							data={ {
								value: sureformsKeys._srfm_bg_color,
								label: '_srfm_bg_color',
							} }
							onColorChange={ ( colorValue ) => {
								if (
									colorValue !== sureformsKeys._srfm_bg_color
								) {
									updateMeta( '_srfm_bg_color', colorValue );
								}
							} }
							value={ sureformsKeys._srfm_bg_color }
							isFormSpecific={ true }
						/>
					) }
				</SRFMAdvancedPanelBody>
			) }
			<SRFMAdvancedPanelBody
				title={ __( 'Form Container', 'sureforms' ) }
				initialOpen={ false }
			>
				<AdvancedPopColorControl
					label={ __( 'Primary Color', 'sureforms' ) }
					colorValue={ sureformsKeys._srfm_color1 }
					data={ {
						value: sureformsKeys._srfm_color1,
						label: '_srfm_color1',
					} }
					onColorChange={ ( colorValue ) => {
						if ( colorValue !== sureformsKeys._srfm_color1 ) {
							updateMeta( '_srfm_color1', colorValue );
						}
					} }
					value={ sureformsKeys._srfm_color1 }
					isFormSpecific={ true }
				/>

				<p className="components-base-control__help" />
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
				/>
				{ sureformsKeys._srfm_show_labels && (
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
				/>
			</SRFMAdvancedPanelBody>
			<SRFMAdvancedPanelBody
				title={ __( 'Input', 'sureforms' ) }
				initialOpen={ false }
			>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'sureforms' ) }
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
					label={ __( 'Background Color', 'sureforms' ) }
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
			</SRFMAdvancedPanelBody>
			<SRFMAdvancedPanelBody
				title={ __( 'Error', 'sureforms' ) }
				initialOpen={ false }
			>
				<AdvancedPopColorControl
					label={ __( 'Color', 'sureforms' ) }
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
			</SRFMAdvancedPanelBody>
			<SRFMAdvancedPanelBody
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
							icon: <FontAwesomeIcon icon={ faAlignLeft } />,
							tooltip: __( 'Left', 'sureforms' ),
						},
						{
							value: 'center',
							icon: <FontAwesomeIcon icon={ faAlignCenter } />,
							tooltip: __( 'Center', 'sureforms' ),
						},
						{
							value: 'right',
							icon: <FontAwesomeIcon icon={ faAlignRight } />,
							tooltip: __( 'Right', 'sureforms' ),
						},
						{
							value: 'justify',
							icon: <FontAwesomeIcon icon={ faAlignJustify } />,
							tooltip: __( 'Full Width', 'sureforms' ),
						},
					] }
					showIcons={ true }
					onChange={ ( value ) => {
						if ( sureformsKeys._srfm_submit_alignment === value ) {
							updateMeta( '_srfm_submit_alignment', 'left' );
							updateMeta( '_srfm_submit_width', '' );
						} else if ( 'justify' === value ) {
							updateMeta( '_srfm_submit_alignment', value );
							updateMeta( '_srfm_submit_width', '100%' );
							updateMeta( '_srfm_submit_width_backend', 'auto' );
						} else {
							updateMeta( '_srfm_submit_alignment', value );
							updateMeta( '_srfm_submit_width', '' );
						}
					} }
				/>
			</SRFMAdvancedPanelBody>
			<SRFMAdvancedPanelBody
				title={ __( 'Advanced', 'sureforms' ) }
				initialOpen={ false }
			>
				<SRFMTextControl
					data={ {
						value: sureformsKeys._srfm_additional_classes,
						label: '_srfm_additional_classes',
					} }
					label={ __( 'Additional CSS Class(es)', 'sureforms' ) }
					value={ sureformsKeys._srfm_additional_classes }
					onChange={ ( value ) => {
						updateMeta( '_srfm_additional_classes', value );
					} }
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help">
					{ __(
						' Separate multiple classes with spaces. ',
						'sureforms'
					) }
				</p>
			</SRFMAdvancedPanelBody>
		</>
	);
}

export default StyleSettings;
