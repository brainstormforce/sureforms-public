import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import UAGMediaPicker from '@Components/image';
import Range from '@Components/range/Range.js';
import { ToggleControl, SelectControl } from '@wordpress/components';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import MultiButtonsControl from '@Components/multi-buttons-control';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
	faAlignLeft,
	faAlignRight,
	faAlignCenter,
	faAlignJustify,
} from '@fortawesome/free-solid-svg-icons';

function AppearanceSettings( props ) {
	const { editPost } = useDispatch( editorStore );
	const { default_keys } = props;

	let sureforms_keys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	const root = document.documentElement;

	if ( sureforms_keys && '_sureforms_color1' in sureforms_keys ) {
		root.style.setProperty(
			'--sureforms_bg',
			sureforms_keys._sureforms_bg
				? 'url(' + sureforms_keys._sureforms_bg + ')'
				: 'none'
		);
		root.style.setProperty(
			'--sureforms_col1',
			sureforms_keys._sureforms_color1
				? sureforms_keys._sureforms_color1
				: 'none'
		);
		root.style.setProperty(
			'--sureforms_textcol1',
			sureforms_keys._sureforms_textcolor1
				? sureforms_keys._sureforms_textcolor1
				: 'none'
		);
		root.style.setProperty(
			'--sureforms_col2',
			sureforms_keys._sureforms_color2
				? sureforms_keys._sureforms_color2
				: 'none'
		);
		root.style.setProperty(
			'--sureforms_fontsize',
			sureforms_keys._sureforms_fontsize
				? sureforms_keys._sureforms_fontsize + 'px'
				: 'none'
		);
		root.style.setProperty(
			'--sureforms_submit_alignment',
			sureforms_keys._sureforms_submit_alignment
				? sureforms_keys._sureforms_submit_alignment
				: 'none'
		);
		root.style.setProperty(
			'--sureforms_submit_width',
			sureforms_keys._sureforms_submit_width
				? sureforms_keys._sureforms_submit_width
				: ''
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

		if ( option === '_sureforms_bg' ) {
			if ( value ) {
				value_id = value.id;
				value = value.sizes.full.url;
			}
			key_id = option + '_id';
			root.style.setProperty(
				'--sureforms_bg',
				value ? 'url(' + value + ')' : 'none'
			);
		}

		if ( option === '_sureforms_color1' ) {
			root.style.setProperty( '--sureforms_col1', value ? value : '' );
		}

		if ( option === '_sureforms_textcolor1' ) {
			root.style.setProperty(
				'--sureforms_textcol1',
				value ? value : ''
			);
		}

		if ( option === '_sureforms_color2' ) {
			root.style.setProperty( '--sureforms_col2', value ? value : '' );
		}

		if ( option === '_sureforms_fontsize' ) {
			root.style.setProperty(
				'--sureforms_fontsize',
				value ? value + 'px' : 'none'
			);
		}

		if ( option === '_sureforms_submit_alignment' ) {
			root.style.setProperty(
				'--sureforms_submit_alignment',
				value ? value : 'left'
			);
			if ( value === 'justify' ) {
				root.style.setProperty( '--sureforms_submit_width', '100%' );
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
		updateMeta( '_sureforms_bg', imageUrl );
	};

	/*
	 * Event to set Image as null while removing it.
	 */
	const onRemoveRestImage = () => {
		updateMeta( '_sureforms_bg', '' );
	};

	return (
		<>
			<UAGAdvancedPanelBody
				title={ __( 'General', 'sureforms' ) }
				initialOpen={ true }
			>
				<SelectControl
					label={ __( 'Form Styling', 'sureforms' ) }
					value={ sureforms_keys._sureforms_form_styling }
					options={ [
						{ label: 'Theme Inherited', value: 'inherit' },
						{
							label: 'Classic Styling',
							value: 'classic',
						},
					] }
					onChange={ ( value ) => {
						updateMeta( '_sureforms_form_styling', value );
					} }
					__nextHasNoMarginBottom
				/>
				<p className="components-base-control__help">
					{ __( 'Select the styling for SureForm.', 'sureforms' ) }
				</p>
				<AdvancedPopColorControl
					label={ __( 'Primary color', 'sureforms' ) }
					help={ __( 'Labels, Borders, Button, etc.', 'sureforms' ) }
					colorValue={ sureforms_keys._sureforms_color1 }
					data={ {
						value: sureforms_keys._sureforms_color1,
						label: '_sureforms_color1',
					} }
					onColorChange={ ( colorValue ) =>
						updateMeta( '_sureforms_color1', colorValue )
					}
					value={ sureforms_keys._sureforms_color1 }
					isFormSpecific={ true }
				/>
				<AdvancedPopColorControl
					label={ __( 'Text color on primary', 'sureforms' ) }
					help={ __(
						'Color when text background is primary',
						'sureforms'
					) }
					colorValue={ sureforms_keys._sureforms_textcolor1 }
					data={ {
						value: sureforms_keys._sureforms_textcolor1,
						label: '_sureforms_textcolor1',
					} }
					onColorChange={ ( colorValue ) =>
						updateMeta( '_sureforms_textcolor1', colorValue )
					}
					value={ sureforms_keys._sureforms_textcolor1 }
					isFormSpecific={ true }
				/>
				<AdvancedPopColorControl
					label={ __( 'Secondary color', 'sureforms' ) }
					help={ __( 'Help, Placeholders, etc.', 'sureforms' ) }
					colorValue={ sureforms_keys._sureforms_color2 }
					data={ {
						value: sureforms_keys._sureforms_color2,
						label: '_sureforms_color2',
					} }
					onColorChange={ ( colorValue ) =>
						updateMeta( '_sureforms_color2', colorValue )
					}
					value={ sureforms_keys._sureforms_color2 }
					isFormSpecific={ true }
				/>
				<UAGMediaPicker
					label={ __( 'Background Image', 'sureforms' ) }
					onSelectImage={ onSelectRestImage }
					backgroundImage={ sureforms_keys._sureforms_bg }
					onRemoveImage={ onRemoveRestImage }
					isFormSpecific={ true }
				/>
				<Range
					label={ __( 'Font size', 'sureforms' ) }
					help={ __( 'Customize the form font size.', 'sureforms' ) }
					value={ sureforms_keys._sureforms_fontsize }
					min={ 16 }
					max={ 24 }
					displayUnit={ false }
					data={ {
						value: sureforms_keys._sureforms_fontsize,
						label: '_sureforms_fontsize',
					} }
					onChange={ ( value ) =>
						updateMeta( '_sureforms_fontsize', value )
					}
					isFormSpecific={ true }
				/>
			</UAGAdvancedPanelBody>
			<UAGAdvancedPanelBody
				title={ __( 'Submit Button', 'sureforms' ) }
				initialOpen={ false }
			>
				<ToggleControl
					label={ __(
						'Inherit button styling from the Theme',
						'sureforms'
					) }
					checked={
						sureforms_keys._sureforms_submit_styling_inherit_from_theme
					}
					onChange={ ( value ) => {
						updateMeta(
							'_sureforms_submit_styling_inherit_from_theme',
							value ? true : false
						);
					} }
				/>
				<p className="components-base-control__help">
					{ __(
						'Turn toggle on to inherit the theme styling for button',
						'sureforms'
					) }
				</p>
				<MultiButtonsControl
					label={ __( 'Button Alignment', 'sureforms' ) }
					data={ {
						value: sureforms_keys._sureforms_submit_alignment,
						label: '_sureforms_submit_alignment',
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
						if (
							sureforms_keys._sureforms_submit_alignment === value
						) {
							updateMeta( '_sureforms_submit_alignment', 'left' );
							updateMeta( '_sureforms_submit_width', '' );
						} else if ( 'justify' === value ) {
							updateMeta( '_sureforms_submit_alignment', value );
							updateMeta( '_sureforms_submit_width', '100%' );
						} else {
							updateMeta( '_sureforms_submit_alignment', value );
							updateMeta( '_sureforms_submit_width', '' );
						}
					} }
				/>
			</UAGAdvancedPanelBody>
		</>
	);
}

export default AppearanceSettings;
