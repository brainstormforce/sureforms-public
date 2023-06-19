import {
	Button,
	PanelBody,
	BaseControl,
	Dropdown,
	PanelRow,
	ColorPicker,
	RangeControl,
} from '@wordpress/components';
import { MediaUpload } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

const ALLOWED_MEDIA_TYPES = [ 'image' ];

function AppearanceSettings( props ) {
	const { editPost } = useDispatch( editorStore );
	const { default_keys } = props;

	let sureforms_keys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);
	const root = document.documentElement;

	if ( ! sureforms_keys ) {
		sureforms_keys = default_keys;
		editPost( {
			meta: sureforms_keys,
		} );
	} else if ( '_sureforms_color1' in sureforms_keys ) {
		if ( ! sureforms_keys._sureforms_color1 ) {
			sureforms_keys = default_keys;
			editPost( {
				meta: sureforms_keys,
			} );
		}
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
			root.style.setProperty(
				'--sureforms_col1',
				value ? value : 'none'
			);
		}

		if ( option === '_sureforms_color2' ) {
			root.style.setProperty(
				'--sureforms_col2',
				value ? value : 'none'
			);
		}

		if ( option === '_sureforms_fontsize' ) {
			root.style.setProperty(
				'--sureforms_fontsize',
				value ? value + 'px' : 'none'
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

	return (
		<PanelBody>
			<PanelRow className="sureforms_colorpicker">
				<BaseControl
					id="sureforms-primary-color"
					label={ __( 'Primary color', 'sureforms' ) }
					help={ __( 'Labels, Borders, Button, etc.', 'sureforms' ) }
				>
					<Dropdown
						className="components-color-palette__item-wrapper components-color-palette__custom-color components-circular-option-picker__option-wrapper"
						contentClassName="components-color-palette__picker"
						renderToggle={ ( { isOpen, onToggle } ) => (
							<button
								style={ {
									background:
										sureforms_keys._sureforms_color1,
								} }
								type="button"
								aria-expanded={ isOpen }
								className="components-color-palette__item components-circular-option-picker__option"
								onClick={ onToggle }
							></button>
						) }
						renderContent={ () => (
							<ColorPicker
								color={ sureforms_keys._sureforms_color1 }
								onChangeComplete={ ( value ) =>
									updateMeta( '_sureforms_color1', value.hex )
								}
								disableAlpha
							/>
						) }
					/>
				</BaseControl>
				<BaseControl
					id="sureforms-secondary-color"
					label={ __( 'Secondary color', 'sureforms' ) }
					help={ __( 'Help, Placeholders, etc.', 'sureforms' ) }
				>
					<Dropdown
						className="components-color-palette__item-wrapper components-color-palette__custom-color components-circular-option-picker__option-wrapper"
						contentClassName="components-color-palette__picker"
						renderToggle={ ( { isOpen, onToggle } ) => (
							<button
								style={ {
									background:
										sureforms_keys._sureforms_color2,
								} }
								type="button"
								aria-expanded={ isOpen }
								className="components-color-palette__item components-circular-option-picker__option"
								onClick={ onToggle }
							></button>
						) }
						renderContent={ () => (
							<ColorPicker
								color={ sureforms_keys._sureforms_color2 }
								onChangeComplete={ ( value ) =>
									updateMeta( '_sureforms_color2', value.hex )
								}
								disableAlpha
							/>
						) }
					/>
				</BaseControl>
			</PanelRow>
			<PanelRow>
				<BaseControl
					id="sureforms-background-image"
					label={ __( 'Background image', 'sureforms' ) }
				>
					<MediaUpload
						onSelect={ ( file ) =>
							updateMeta( '_sureforms_bg', file )
						}
						value={ sureforms_keys._sureforms_bg }
						allowedTypes={ ALLOWED_MEDIA_TYPES }
						render={ ( { open } ) => (
							<div
								style={ {
									display: 'flex',
									flexDirection: 'column',
									justifyContent: 'center',
									gap: '30px',
								} }
							>
								<Button
									className="sureforms-background-image__toggle"
									onClick={ open }
								>
									{ sureforms_keys._sureforms_bg ? (
										<img
											src={ sureforms_keys._sureforms_bg }
											alt=""
										/>
									) : (
										''
									) }
									{ ! sureforms_keys._sureforms_bg
										? __(
											'Set a background image',
											'sureforms'
										  )
										: '' }
								</Button>
								{ sureforms_keys._sureforms_bg ? (
									<Button
										onClick={ () =>
											updateMeta( '_sureforms_bg', '' )
										}
										isDestructive
									>
										{ __(
											'Remove background image',
											'sureforms'
										) }
									</Button>
								) : (
									''
								) }
							</div>
						) }
					/>
				</BaseControl>
			</PanelRow>
			<PanelRow>
				<BaseControl
					id="sureforms-font-size"
					label={ __( 'Font size', 'sureforms' ) }
					help={ __( 'Customize the form font size.', 'sureforms' ) }
				>
					<RangeControl
						value={ sureforms_keys._sureforms_fontsize }
						onChange={ ( value ) =>
							updateMeta( '_sureforms_fontsize', value )
						}
						min={ 16 }
						max={ 24 }
						beforeIcon="editor-textcolor"
						afterIcon="editor-textcolor"
					/>
				</BaseControl>
			</PanelRow>
		</PanelBody>
	);
}

export default AppearanceSettings;
