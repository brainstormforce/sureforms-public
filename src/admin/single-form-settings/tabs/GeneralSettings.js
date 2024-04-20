import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import { ToggleControl, SelectControl } from '@wordpress/components';
import { useDeviceType } from '@Controls/getPreviewType';
import PostURLPanel from '../components/form-permalink/Panel';
import SRFMMediaPicker from '@Components/image';
import MultiButtonsControl from '@Components/multi-buttons-control';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import Range from '@Components/range/Range.js';

function GeneralSettings( props ) {
	const { editPost } = useDispatch( editorStore );
	const { defaultKeys, isPageBreak } = props;
	const root = document.documentElement.querySelector( 'body' );

	let sureformsKeys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);
	const deviceType = useDeviceType();
	const [ rootContainer, setRootContainer ] = useState(
		document.getElementById( 'srfm-form-container' )
	);

	// if device type is desktop then
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

					setRootContainer(
						iframeBody.querySelector( '.is-root-container' )
					);
				}
			} else {
				setRootContainer(
					document.getElementById( 'srfm-form-container' )
				);
			}
		}, 100 );
	}, [ deviceType, rootContainer ] );

	if ( sureformsKeys && '_srfm_show_labels' in sureformsKeys ) {
		if ( rootContainer ) {
			if ( ! sureformsKeys._srfm_show_labels ) {
				rootContainer.classList.add( 'srfm-hide-labels' );
			} else {
				rootContainer.classList.remove( 'srfm-hide-labels' );
			}
			if ( ! sureformsKeys._srfm_show_asterisk ) {
				rootContainer.classList.add( 'srfm-hide-asterisk' );
			} else {
				rootContainer.classList.remove( 'srfm-hide-asterisk' );
			}
		}
		// Button text
		root.style.setProperty(
			'--srfm-submit-button-text',
			sureformsKeys._srfm_submit_button_text
				? '"' + sureformsKeys._srfm_submit_button_text + '"'
				: '"' + __( 'SUBMIT', 'sureforms' ) + '"'
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
	} else {
		sureformsKeys = defaultKeys;
		editPost( {
			meta: sureformsKeys,
		} );
	}

	/*
	 * function to update post metas.
	 */
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

		if ( option === '_srfm_show_labels' ) {
			if ( ! value ) {
				rootContainer.classList.add( 'srfm-hide-labels' );
				updateMeta( '_srfm_show_asterisk', false );
			} else {
				rootContainer.classList.remove( 'srfm-hide-labels' );
				updateMeta( '_srfm_show_asterisk', true );
			}
		}

		if ( option === '_srfm_show_asterisk' ) {
			if ( ! value ) {
				rootContainer.classList.add( 'srfm-hide-asterisk' );
			} else {
				rootContainer.classList.remove( 'srfm-hide-asterisk' );
			}
		}

		// Button
		if ( option === '_srfm_submit_button_text' ) {
			root.style.setProperty(
				'--srfm-submit-button-text',
				value
					? '"' + value + '"'
					: '"' + __( 'SUBMIT', 'sureforms' ) + '"'
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
			<SRFMAdvancedPanelBody
				title={ __( 'General', 'sureforms' ) }
				initialOpen={ true }
			>
				<ToggleControl
					label={ __( 'Show Labels', 'sureforms' ) }
					checked={ sureformsKeys._srfm_show_labels }
					onChange={ ( value ) => {
						updateMeta( '_srfm_show_labels', value );
					} }
				/>
				{ sureformsKeys._srfm_show_labels && (
					<ToggleControl
						label={ __( 'Show Asterisk', 'sureforms' ) }
						checked={ sureformsKeys._srfm_show_asterisk }
						onChange={ ( value ) => {
							updateMeta( '_srfm_show_asterisk', value );
						} }
					/>
				) }
				<p className="components-base-control__help" />
				<ToggleControl
					label={ __(
						'Show Form Title on the Page/Post',
						'sureforms'
					) }
					checked={ sureformsKeys._srfm_page_form_title }
					onChange={ ( value ) => {
						updateMeta( '_srfm_page_form_title', value );
					} }
				/>
			</SRFMAdvancedPanelBody>
			<SRFMAdvancedPanelBody
				title={ __( 'Submit Button', 'sureforms' ) }
				initialOpen={ false }
			>
				<SRFMTextControl
					data={ {
						value: sureformsKeys._srfm_submit_button_text,
						label: '_srfm_submit_button_text',
					} }
					label={ __( 'Submit Button Text', 'sureforms' ) }
					placeholder={ __( 'SUBMIT', 'sureforms' ) }
					value={ sureformsKeys._srfm_submit_button_text }
					onChange={ ( value ) => {
						updateMeta( '_srfm_submit_button_text', value );
					} }
					isFormSpecific={ true }
				/>
			</SRFMAdvancedPanelBody>
			{ isPageBreak && (
				<SRFMAdvancedPanelBody
					title={ __( 'Page Break', 'sureforms' ) }
					initialOpen={ false }
				>
					<SRFMTextControl
						label={ __( 'First Page Label', 'sureforms' ) }
						value={ sureformsKeys._srfm_first_page_label }
						data={ {
							value: sureformsKeys._srfm_first_page_label,
							label: '_srfm_first_page_label',
						} }
						onChange={ ( value ) =>
							updateMeta( '_srfm_first_page_label', value )
						}
					/>
					<SelectControl
						label={ __( 'Progress Indicator', 'sureforms' ) }
						value={
							sureformsKeys._srfm_page_break_progress_indicator
						}
						className="srfm-progress-control"
						options={ [
							{ label: 'None', value: 'none' },
							{
								label: 'Progress Bar',
								value: 'progress-bar',
							},
							{
								label: 'Connector',
								value: 'connector',
							},
							{
								label: 'Steps',
								value: 'steps',
							},
						] }
						onChange={ ( value ) =>
							updateMeta(
								'_srfm_page_break_progress_indicator',
								value
							)
						}
						__nextHasNoMarginBottom
					/>

					<ToggleControl
						label={ __( 'Show Labels', 'sureforms' ) }
						checked={ sureformsKeys._srfm_page_break_toggle_label }
						onChange={ ( value ) => {
							updateMeta(
								'_srfm_page_break_toggle_label',
								value
							);
						} }
					/>

					<SRFMTextControl
						data={ {
							value: sureformsKeys._srfm_previous_button_text,
							label: '_srfm_previous_button_text',
						} }
						label={ __( 'Previous Button Text', 'sureforms' ) }
						value={ sureformsKeys._srfm_previous_button_text }
						onChange={ ( value ) => {
							updateMeta( '_srfm_previous_button_text', value );
						} }
						isFormSpecific={ true }
					/>
					<SRFMTextControl
						data={ {
							value: sureformsKeys._srfm_previous_button_text,
							label: '_srfm_next_button_text',
						} }
						label={ __( 'Next Button Text', 'sureforms' ) }
						value={ sureformsKeys._srfm_next_button_text }
						onChange={ ( value ) => {
							updateMeta( '_srfm_next_button_text', value );
						} }
						isFormSpecific={ true }
					/>
				</SRFMAdvancedPanelBody>
			) }
			<SRFMAdvancedPanelBody
				title={ __( 'Instant Form', 'sureforms' ) }
				initialOpen={ false }
			>
				<ToggleControl
					label={ __( 'Enable Instant Form', 'sureforms' ) }
					checked={ sureformsKeys._srfm_instant_form }
					onChange={ ( value ) => {
						updateMeta( '_srfm_instant_form', value );
					} }
				/>
				{ sureformsKeys._srfm_instant_form && (
					<>
						<ToggleControl
							label={ __(
								'Show Title on Instant Forms',
								'sureforms'
							) }
							checked={
								sureformsKeys._srfm_single_page_form_title
							}
							onChange={ ( value ) => {
								updateMeta(
									'_srfm_single_page_form_title',
									value
								);
							} }
						/>
						<PostURLPanel />
					</>
				) }
			</SRFMAdvancedPanelBody>
			{ sureformsKeys._srfm_instant_form && (
				<SRFMAdvancedPanelBody
					title={ __( 'Instant Form Styling', 'sureforms' ) }
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
		</>
	);
}

export default GeneralSettings;
