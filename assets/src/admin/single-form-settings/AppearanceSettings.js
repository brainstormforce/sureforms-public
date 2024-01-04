import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import SRFMMediaPicker from '@Components/image';
import Range from '@Components/range/Range.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import MultiButtonsControl from '@Components/multi-buttons-control';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
	faAlignLeft,
	faAlignRight,
	faAlignCenter,
	faAlignJustify,
} from '@fortawesome/free-solid-svg-icons';
import SRFMTextControl from '@Components/text-control';
import { ToggleControl } from '@wordpress/components';

function AppearanceSettings( props ) {
	const { editPost } = useDispatch( editorStore );
	const { default_keys } = props;

	let sureforms_keys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	const root = document.documentElement;

	if ( sureforms_keys && '_srfm_color1' in sureforms_keys ) {
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
			root.style.setProperty( '--srfm_submit_width_backend', '100px' );
			updateMeta( '_srfm_submit_width_backend', '100px' );

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
				title={ __( 'General', 'sureforms' ) }
				initialOpen={ true }
			>
				{ /* <SelectControl
					label={ __( 'Form Styling', 'sureforms' ) }
					value={ sureforms_keys._srfm_form_styling }
					options={ [
						{ label: 'Theme Inherited', value: 'inherit' },
						{
							label: 'Classic Styling',
							value: 'classic',
						},
					] }
					onChange={ ( value ) => {
						updateMeta( '_srfm_form_styling', value );
					} }
					__nextHasNoMarginBottom
				/> */ }
				{ /* <p className="components-base-control__help">
					{ __(
						'Update settings to view changes on page',
						'sureforms'
					) }
				</p> */ }
				<ToggleControl
					label={ __(
						'Hide form title on the Page/Post',
						'sureforms'
					) }
					checked={ sureforms_keys._srfm_page_form_title }
					onChange={ ( value ) => {
						updateMeta( '_srfm_page_form_title', value );
					} }
				/>
				<ToggleControl
					label={ __(
						'Hide form title on the Single Form page',
						'sureforms'
					) }
					checked={ sureforms_keys._srfm_single_page_form_title }
					onChange={ ( value ) => {
						updateMeta( '_srfm_single_page_form_title', value );
					} }
				/>
				<AdvancedPopColorControl
					label={ __( 'Primary color', 'sureforms' ) }
					help={ __( 'Labels, Borders, Button, etc.', 'sureforms' ) }
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
				<AdvancedPopColorControl
					label={ __( 'Text color on primary', 'sureforms' ) }
					help={ __(
						'Color when text background is primary',
						'sureforms'
					) }
					colorValue={ sureforms_keys._srfm_textcolor1 }
					data={ {
						value: sureforms_keys._srfm_textcolor1,
						label: '_srfm_textcolor1',
					} }
					onColorChange={ ( colorValue ) => {
						if ( colorValue !== sureforms_keys._srfm_textcolor1 ) {
							updateMeta( '_srfm_textcolor1', colorValue );
						}
					} }
					value={ sureforms_keys._srfm_textcolor1 }
					isFormSpecific={ true }
				/>
				{ 'inherit' === sureforms_keys._srfm_form_styling && (
					<AdvancedPopColorControl
						label={ __( 'Secondary color', 'sureforms' ) }
						help={ __( 'Help, Placeholders, etc.', 'sureforms' ) }
						colorValue={ sureforms_keys._srfm_color2 }
						data={ {
							value: sureforms_keys._srfm_color2,
							label: '_srfm_color2',
						} }
						onColorChange={ ( colorValue ) => {
							if ( colorValue !== sureforms_keys._srfm_color2 ) {
								updateMeta( '_srfm_color2', colorValue );
							}
						} }
						value={ sureforms_keys._srfm_color2 }
						isFormSpecific={ true }
					/>
				) }
				<SRFMMediaPicker
					label={ __( 'Background Image', 'sureforms' ) }
					onSelectImage={ onSelectRestImage }
					backgroundImage={ sureforms_keys._srfm_bg }
					onRemoveImage={ onRemoveRestImage }
					isFormSpecific={ true }
				/>
				<Range
					label={ __( 'Font size', 'sureforms' ) }
					help={ __(
						'Customize the form font size.',
						'sureforms'
					) }
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
				<Range
					label={ __( 'Form Container Width(px)', 'sureforms' ) }
					help={ __(
						'Customize the form Form Container width.',
						'sureforms'
					) }
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
			</SRFMAdvancedPanelBody>
			<SRFMAdvancedPanelBody
				title={ __( 'Submit Button', 'sureforms' ) }
				initialOpen={ false }
			>
				{ /* Might be used later */ }
				{ /* <ToggleControl
					label={ __(
						'Inherit button styling from the Theme',
						'sureforms'
					) }
					checked={
						sureforms_keys._srfm_submit_styling_inherit_from_theme
					}
					onChange={ ( value ) => {
						updateMeta(
							'_srfm_submit_styling_inherit_from_theme',
							value ? true : false
						);
					} }
				/>
				<p className="components-base-control__help">
					{ __(
						'Turn toggle on to inherit the theme styling for button',
						'sureforms'
					) }
				</p> */ }
				<SRFMTextControl
					data={ {
						value: sureforms_keys._srfm_submit_button_text,
						label: '_srfm_submit_button_text',
					} }
					label={ __( 'Submit Button Text', 'sureforms' ) }
					placeholder={ __( 'SUBMIT', 'sureforms' ) }
					value={ sureforms_keys._srfm_submit_button_text }
					onChange={ ( value ) => {
						const btnText = value.toUpperCase();
						updateMeta( '_srfm_submit_button_text', btnText );
					} }
					isFormSpecific={ true }
				/>
				<p className="components-base-control__help" />
				<MultiButtonsControl
					label={ __( 'Button Alignment', 'sureforms' ) }
					data={ {
						value: sureforms_keys._srfm_submit_alignment,
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
						if ( sureforms_keys._srfm_submit_alignment === value ) {
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
		</>
	);
}

export default AppearanceSettings;
