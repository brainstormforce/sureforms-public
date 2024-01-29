import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import SRFMMediaPicker from '@Components/image';
import Range from '@Components/range/Range.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import MultiButtonsControl from '@Components/multi-buttons-control';
import SRFMTabsControl from '@Components/tabs';

function StyleSettings( props ) {
	const { editPost } = useDispatch( editorStore );
	const { default_keys } = props;

	let sureforms_keys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);
	const root = document.documentElement;

	if ( sureforms_keys && '_srfm_color1' in sureforms_keys ) {
		root.style.setProperty(
			'--srfm-label-text-color',
			sureforms_keys._srfm_label_color
				? sureforms_keys._srfm_label_color
				: 'none'
		);
		root.style.setProperty(
			'-srfm-body-input-color',
			sureforms_keys._srfm_input_text_color
				? sureforms_keys._srfm_input_text_color
				: '#4B5563'
		);
		// placeholder color
		root.style.setProperty(
			'--srfm-placeholder-color',
			sureforms_keys._srfm_input_placeholder_color
				? sureforms_keys._srfm_input_placeholder_color
				: '#9ca3af'
		);
		// input background color
		root.style.setProperty(
			'--srfm-base-background-color',
			sureforms_keys._srfm_input_bg_color
				? sureforms_keys._srfm_input_bg_color
				: '#ffffff'
		);
		// input border color
		root.style.setProperty(
			'--srfm-border-color',
			sureforms_keys._srfm_input_border_color
				? sureforms_keys._srfm_input_border_color
				: '#d0d5dd'
		);
		// input border width
		root.style.setProperty(
			'--srfm-border',
			sureforms_keys._srfm_input_border_width
				? sureforms_keys._srfm_input_border_width + 'px'
				: '1px'
		);
		// border radius
		root.style.setProperty(
			'--srfm-border-radius',
			sureforms_keys._srfm_input_border_radius
				? sureforms_keys._srfm_input_border_radius + 'px'
				: '4px'
		);
		root.style.setProperty(
			'--srfm_bg',
			sureforms_keys._srfm_bg
				? 'url(' + sureforms_keys._srfm_bg + ')'
				: 'none'
		);
		root.style.setProperty(
			'--srfm-primary-color',
			sureforms_keys._srfm_color1
				? sureforms_keys._srfm_color1
				: '#0284C7'
		);
		root.style.setProperty(
			'--srfm-primary-text-color',
			sureforms_keys._srfm_textcolor1
				? sureforms_keys._srfm_textcolor1
				: '#fff'
		);
		root.style.setProperty(
			'--srfm-secondary-color',
			sureforms_keys._srfm_color2 ? sureforms_keys._srfm_color2 : 'none'
		);
		root.style.setProperty(
			'--srfm-font-size',
			sureforms_keys._srfm_fontsize
				? sureforms_keys._srfm_fontsize + 'px'
				: 'none'
		);
		root.style.setProperty(
			'--srfm_submit_alignment',
			sureforms_keys._srfm_submit_alignment
				? sureforms_keys._srfm_submit_alignment
				: 'none'
		);
		root.style.setProperty(
			'--srfm_submit_width',
			sureforms_keys._srfm_submit_width
				? sureforms_keys._srfm_submit_width
				: ''
		);
		root.style.setProperty(
			'--srfm_submit_alignment_backend',
			sureforms_keys._srfm_submit_alignment_backend
				? sureforms_keys._srfm_submit_alignment_backend
				: ''
		);
		root.style.setProperty(
			'--srfm_submit_width_backend',
			sureforms_keys._srfm_submit_width_backend
				? sureforms_keys._srfm_submit_width_backend
				: ''
		);
		root.style.setProperty(
			'--srfm_submit_button_text',
			sureforms_keys._srfm_submit_button_text
				? '"' + sureforms_keys._srfm_submit_button_text + '"'
				: '"' + __( 'SUBMIT', 'sureforms' ) + '"'
		);
	} else {
		sureforms_keys = default_keys;
		editPost( {
			meta: sureforms_keys,
		} );
	}

	function updateMeta( option, value ) {
		let value_id = 0;
		let key_id = '';

		if ( option === '_srfm_bg' ) {
			if ( value ) {
				value_id = value.id;
				value = value.sizes.full.url;
			}
			key_id = option + '_id';
			root.style.setProperty(
				'--srfm_bg',
				value ? 'url(' + value + ')' : 'none'
			);
		}

		if ( option === '_srfm_color1' ) {
			root.style.setProperty(
				'--srfm-primary-color',
				value ? value : '#0284C7'
			);
		}

		if ( option === '_srfm_label_color' ) {
			root.style.setProperty(
				'--srfm-label-text-color',
				value ? value : '#0284C7'
			);
		}

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
				value ? value : '#d0d5dd'
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

		if ( option === '_srfm_textcolor1' ) {
			root.style.setProperty(
				'--srfm-primary-text-color',
				value ? value : '#fff'
			);
		}

		if ( option === '_srfm_color2' ) {
			root.style.setProperty(
				'--srfm-secondary-color',
				value ? value : ''
			);
		}

		if ( option === '_srfm_fontsize' ) {
			root.style.setProperty(
				'--srfm_fontsize',
				value ? value + 'px' : 'none'
			);
		}

		if ( option === '_srfm_submit_button_text' ) {
			root.style.setProperty(
				'--srfm_submit_button_text',
				value
					? '"' + value + '"'
					: '"' + __( 'SUBMIT', 'sureforms' ) + '"'
			);
		}

		if ( option === '_srfm_submit_alignment' ) {
			root.style.setProperty(
				'--srfm_submit_alignment',
				value ? value : 'left'
			);
			root.style.setProperty(
				'--srfm_submit_width_backend',
				'max-content'
			);
			updateMeta( '_srfm_submit_width_backend', 'max-content' );

			if ( value === 'left' ) {
				root.style.setProperty(
					'--srfm_submit_alignment_backend',
					'100%'
				);
				updateMeta( '_srfm_submit_alignment_backend', '100%' );
			}
			if ( value === 'right' ) {
				root.style.setProperty(
					'--srfm_submit_alignment_backend',
					'0%'
				);
				updateMeta( '_srfm_submit_alignment_backend', '0%' );
			}
			if ( value === 'center' ) {
				root.style.setProperty(
					'--srfm_submit_alignment_backend',
					'50%'
				);
				updateMeta( '_srfm_submit_alignment_backend', '50%' );
			}
			if ( value === 'justify' ) {
				root.style.setProperty(
					'--srfm_submit_alignment_backend',
					'50%'
				);
				root.style.setProperty( '--srfm_submit_width_backend', 'auto' );
				updateMeta( '_srfm_submit_alignment_backend', '50%' );
			}
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
		updateMeta( '_srfm_bg', imageUrl );
	};

	/*
	 * Event to set Image as null while removing it.
	 */
	const onRemoveRestImage = () => {
		updateMeta( '_srfm_bg', '' );
	};

	return (
		<>
			<SRFMAdvancedPanelBody
				title={ __( 'Form Container', 'sureforms' ) }
				initialOpen={ true }
			>
				<Range
					label={ __( 'Form Container Width(px)', 'sureforms' ) }
					value={ sureforms_keys._srfm_form_container_width }
					min={ 650 }
					max={ 1000 }
					displayUnit={ false }
					data={ {
						value: sureforms_keys._srfm_form_container_width,
						label: '_srfm_form_container_width',
					} }
					onChange={ ( value ) =>
						updateMeta( '_srfm_form_container_width', value )
					}
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<AdvancedPopColorControl
					label={ __( 'Primary color', 'sureforms' ) }
					colorValue={ sureforms_keys._srfm_color1 }
					data={ {
						value: sureforms_keys._srfm_color1,
						label: '_srfm_color1',
					} }
					onColorChange={ ( colorValue ) => {
						if ( colorValue !== sureforms_keys._srfm_color1 ) {
							updateMeta( '_srfm_color1', colorValue );
						}
					} }
					value={ sureforms_keys._srfm_color1 }
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<MultiButtonsControl
					label={ __( 'Background Type', 'sureforms' ) }
					data={ {
						value: sureforms_keys._srfm_bg_type,
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
							updateMeta( '_srfm_bg', '' );
							updateMeta(
								'_srfm_bg_color',
								sureforms_keys._srfm_bg_color
									? sureforms_keys._srfm_bg_color
									: '#ffffff'
							);
						} else {
							updateMeta( '_srfm_bg_color', '' );
							updateMeta(
								'_srfm_bg',
								sureforms_keys._srfm_bg
									? sureforms_keys._srfm_bg
									: ''
							);
						}
					} }
				/>
				<p className="components-base-control__help" />
				{ sureforms_keys._srfm_bg_type === 'image' ? (
					<SRFMMediaPicker
						label={ __( 'Background Image', 'sureforms' ) }
						onSelectImage={ onSelectRestImage }
						backgroundImage={ sureforms_keys._srfm_bg }
						onRemoveImage={ onRemoveRestImage }
						isFormSpecific={ true }
					/>
				) : (
					<AdvancedPopColorControl
						label={ __( 'Background Color', 'sureforms' ) }
						colorValue={ sureforms_keys._srfm_bg_color }
						data={ {
							value: sureforms_keys._srfm_bg_color,
							label: '_srfm_bg_color',
						} }
						onColorChange={ ( colorValue ) => {
							if (
								colorValue !== sureforms_keys._srfm_bg_color
							) {
								updateMeta( '_srfm_bg_color', colorValue );
							}
						} }
						value={ sureforms_keys._srfm_bg_color }
						isFormSpecific={ true }
					/>
				) }
				<p className="components-base-control__help" />
				<Range
					label={ __( 'Font size', 'sureforms' ) }
					value={ sureforms_keys._srfm_fontsize }
					min={ 16 }
					max={ 24 }
					displayUnit={ false }
					data={ {
						value: sureforms_keys._srfm_fontsize,
						label: '_srfm_fontsize',
					} }
					onChange={ ( value ) =>
						updateMeta( '_srfm_fontsize', value )
					}
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<AdvancedPopColorControl
					label={ __( 'Label color', 'sureforms' ) }
					colorValue={ sureforms_keys._srfm_label_color }
					data={ {
						value: sureforms_keys._srfm_label_color,
						label: '_srfm_label_color',
					} }
					onColorChange={ ( colorValue ) => {
						if ( colorValue !== sureforms_keys._srfm_label_color ) {
							updateMeta( '_srfm_label_color', colorValue );
						}
					} }
					value={ sureforms_keys._srfm_label_color }
					isFormSpecific={ true }
				/>
			</SRFMAdvancedPanelBody>
			<SRFMAdvancedPanelBody
				title={ __( 'Input', 'sureforms' ) }
				initialOpen={ false }
			>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'sureforms' ) }
					colorValue={ sureforms_keys._srfm_input_text_color }
					data={ {
						value: sureforms_keys._srfm_input_text_color,
						label: '_srfm_input_text_color',
					} }
					onColorChange={ ( colorValue ) => {
						if (
							colorValue !== sureforms_keys._srfm_input_text_color
						) {
							updateMeta( '_srfm_input_text_color', colorValue );
						}
					} }
					value={ sureforms_keys._srfm_input_text_color }
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<AdvancedPopColorControl
					label={ __( 'Placeholder Color', 'sureforms' ) }
					colorValue={ sureforms_keys._srfm_input_placeholder_color }
					data={ {
						value: sureforms_keys._srfm_input_placeholder_color,
						label: '_srfm_input_placeholder_color',
					} }
					onColorChange={ ( colorValue ) => {
						if (
							colorValue !==
							sureforms_keys._srfm_input_placeholder_color
						) {
							updateMeta(
								'_srfm_input_placeholder_color',
								colorValue
							);
						}
					} }
					value={ sureforms_keys._srfm_input_placeholder_color }
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<AdvancedPopColorControl
					label={ __( 'Background Color', 'sureforms' ) }
					colorValue={ sureforms_keys._srfm_input_bg_color }
					data={ {
						value: sureforms_keys._srfm_input_bg_color,
						label: '_srfm_input_bg_color',
					} }
					onColorChange={ ( colorValue ) => {
						if (
							colorValue !== sureforms_keys._srfm_input_bg_color
						) {
							updateMeta( '_srfm_input_bg_color', colorValue );
						}
					} }
					value={ sureforms_keys._srfm_input_bg_color }
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<AdvancedPopColorControl
					label={ __( 'Border Color', 'sureforms' ) }
					colorValue={ sureforms_keys._srfm_input_border_color }
					data={ {
						value: sureforms_keys._srfm_input_border_color,
						label: '_srfm_input_border_color',
					} }
					onColorChange={ ( colorValue ) => {
						if (
							colorValue !==
							sureforms_keys._srfm_input_border_color
						) {
							updateMeta(
								'_srfm_input_border_color',
								colorValue
							);
						}
					} }
					value={ sureforms_keys._srfm_input_border_color }
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<Range
					label={ __( 'Border width', 'sureforms' ) }
					value={ sureforms_keys._srfm_input_border_width }
					min={ 1 }
					max={ 10 }
					displayUnit={ false }
					data={ {
						value: sureforms_keys._srfm_input_border_width,
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
					value={ sureforms_keys._srfm_input_border_radius }
					min={ 0 }
					max={ 100 }
					displayUnit={ false }
					data={ {
						value: sureforms_keys._srfm_input_border_radius,
						label: '_srfm_input_border_radius',
					} }
					onChange={ ( value ) =>
						updateMeta( '_srfm_input_border_radius', value )
					}
					isFormSpecific={ true }
				/>
			</SRFMAdvancedPanelBody>
			<SRFMAdvancedPanelBody
				title={ __( 'Message', 'sureforms' ) }
				initialOpen={ false }
			>
				<SRFMTabsControl
					tabs={ [
						{
							name: 'normal',
							title: __( 'Success', 'sureforms' ),
						},
						{
							name: 'active',
							title: __( 'Error', 'sureforms' ),
						},
					] }
					normal={
						<>
							<AdvancedPopColorControl
								label={ __( 'Text Color', 'sureforms' ) }
								colorValue={
									sureforms_keys._srfm_message_text_color
								}
								data={ {
									value: sureforms_keys._srfm_message_text_color,
									label: '_srfm_message_text_color',
								} }
								onColorChange={ ( colorValue ) => {
									if (
										colorValue !==
										sureforms_keys._srfm_message_text_color
									) {
										updateMeta(
											'_srfm_message_text_color',
											colorValue
										);
									}
								} }
								value={
									sureforms_keys._srfm_message_text_color
								}
								isFormSpecific={ true }
							/>
							<AdvancedPopColorControl
								label={ __( 'Background Color', 'sureforms' ) }
								colorValue={
									sureforms_keys._srfm_message_bg_color
								}
								data={ {
									value: sureforms_keys._srfm_message_bg_color,
									label: '_srfm_message_bg_color',
								} }
								onColorChange={ ( colorValue ) => {
									if (
										colorValue !==
										sureforms_keys._srfm_message_bg_color
									) {
										updateMeta(
											'_srfm_message_bg_color',
											colorValue
										);
									}
								} }
								value={ sureforms_keys._srfm_message_bg_color }
								isFormSpecific={ true }
							/>
						</>
					}
					active={
						<>
							<AdvancedPopColorControl
								label={ __( 'Text Color', 'sureforms' ) }
								colorValue={
									sureforms_keys._srfm_field_error_color
								}
								data={ {
									value: sureforms_keys._srfm_field_error_color,
									label: '_srfm_field_error_color',
								} }
								onColorChange={ ( colorValue ) => {
									if (
										colorValue !==
										sureforms_keys._srfm_field_error_color
									) {
										updateMeta(
											'_srfm_field_error_color',
											colorValue
										);
									}
								} }
								value={ sureforms_keys._srfm_field_error_color }
								isFormSpecific={ true }
							/>
							<AdvancedPopColorControl
								label={ __( 'Surface Color', 'sureforms' ) }
								colorValue={
									sureforms_keys._srfm_field_error_surface_color
								}
								data={ {
									value: sureforms_keys._srfm_field_error_surface_color,
									label: '_srfm_field_error_surface_color',
								} }
								onColorChange={ ( colorValue ) => {
									if (
										colorValue !==
										sureforms_keys._srfm_field_error_surface_color
									) {
										updateMeta(
											'_srfm_field_error_surface_color',
											colorValue
										);
									}
								} }
								value={
									sureforms_keys._srfm_field_error_surface_color
								}
								isFormSpecific={ true }
							/>
							<AdvancedPopColorControl
								label={ __( 'Background Color', 'sureforms' ) }
								colorValue={
									sureforms_keys._srfm_field_error_bg_color
								}
								data={ {
									value: sureforms_keys._srfm_field_error_bg_color,
									label: '_srfm_field_error_bg_color',
								} }
								onColorChange={ ( colorValue ) => {
									if (
										colorValue !==
										sureforms_keys._srfm_field_error_bg_color
									) {
										updateMeta(
											'_srfm_field_error_bg_color',
											colorValue
										);
									}
								} }
								value={
									sureforms_keys._srfm_field_error_bg_color
								}
								isFormSpecific={ true }
								disableBottomSeparator={ true }
							/>
						</>
					}
					disableBottomSeparator={ true }
				/>
			</SRFMAdvancedPanelBody>
			<SRFMAdvancedPanelBody
				title={ __( 'Submit Button', 'sureforms' ) }
				initialOpen={ false }
			>
				<AdvancedPopColorControl
					label={ __( 'Text Color', 'sureforms' ) }
					colorValue={ sureforms_keys._srfm_button_text_color }
					data={ {
						value: sureforms_keys._srfm_button_text_color,
						label: '_srfm_button_text_color',
					} }
					onColorChange={ ( colorValue ) => {
						if (
							colorValue !==
							sureforms_keys._srfm_button_text_color
						) {
							updateMeta( '_srfm_button_text_color', colorValue );
						}
					} }
					value={ sureforms_keys._srfm_button_text_color }
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<MultiButtonsControl
					label={ __( 'Button Style', 'sureforms' ) }
					data={ {
						value: sureforms_keys._srfm_submit_style,
						label: '_srfm_submit_style',
					} }
					options={ [
						{
							value: 'filled',
							label: __( 'Filled', 'sureforms' ),
						},
						{
							value: 'transparent',
							label: __( 'Transparent', 'sureforms' ),
						},
					] }
					showIcons={ false }
					onChange={ ( value ) => {
						updateMeta( '_srfm_submit_style', value );
					} }
				/>
				{ sureforms_keys._srfm_submit_style === 'filled' && (
					<>
						<p className="components-base-control__help" />
						<AdvancedPopColorControl
							label={ __( 'Background Color', 'sureforms' ) }
							colorValue={ sureforms_keys._srfm_button_bg_color }
							data={ {
								value: sureforms_keys._srfm_button_bg_color,
								label: '_srfm_button_bg_color',
							} }
							onColorChange={ ( colorValue ) => {
								if (
									colorValue !==
									sureforms_keys._srfm_button_bg_color
								) {
									updateMeta(
										'_srfm_button_bg_color',
										colorValue
									);
								}
							} }
							value={ sureforms_keys._srfm_button_bg_color }
							isFormSpecific={ true }
						/>
					</>
				) }
				<p className="components-base-control__help" />
				<AdvancedPopColorControl
					label={ __( 'Border Color', 'sureforms' ) }
					colorValue={ sureforms_keys._srfm_button_border_color }
					data={ {
						value: sureforms_keys._srfm_button_border_color,
						label: '_srfm_button_border_color',
					} }
					onColorChange={ ( colorValue ) => {
						if (
							colorValue !==
							sureforms_keys._srfm_button_border_color
						) {
							updateMeta(
								'_srfm_button_border_color',
								colorValue
							);
						}
					} }
					value={ sureforms_keys._srfm_button_border_color }
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<Range
					label={ __( 'Border width', 'sureforms' ) }
					value={ sureforms_keys._srfm_button_border_width }
					min={ 650 }
					max={ 1000 }
					displayUnit={ false }
					data={ {
						value: sureforms_keys._srfm_button_border_width,
						label: '_srfm_button_border_width',
					} }
					onChange={ ( value ) =>
						updateMeta( '_srfm_button_border_width', value )
					}
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<Range
					label={ __( 'Border Radius', 'sureforms' ) }
					value={ sureforms_keys._srfm_button_border_radius }
					min={ 650 }
					max={ 1000 }
					displayUnit={ false }
					data={ {
						value: sureforms_keys._srfm_button_border_radius,
						label: '_srfm_button_border_radius',
					} }
					onChange={ ( value ) =>
						updateMeta( '_srfm_button_border_radius', value )
					}
					isFormSpecific={ true }
				/>
			</SRFMAdvancedPanelBody>
			<SRFMAdvancedPanelBody
				title={ __( 'Advanced', 'sureforms' ) }
				initialOpen={ false }
			>
				<SRFMTextControl
					data={ {
						value: sureforms_keys._srfm_additional_classes,
						label: '_srfm_additional_classes',
					} }
					label={ __( 'Additional CSS Class(es)', 'sureforms' ) }
					value={ sureforms_keys._srfm_additional_classes }
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
