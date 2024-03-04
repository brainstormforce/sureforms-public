import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import { ToggleControl, SelectControl } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useDeviceType } from '@Controls/getPreviewType';
import PostURLPanel from '../components/form-permalink/Panel';

function GeneralSettings( props ) {
	const { editPost } = useDispatch( editorStore );
	const { default_keys, setEnableQuickActionSidebar, isPageBreak } = props;
	const root = document.documentElement.querySelector( 'body' );

	let sureforms_keys = useSelect( ( select ) =>
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

	if ( sureforms_keys && '_srfm_show_labels' in sureforms_keys ) {
		if ( rootContainer ) {
			if ( ! sureforms_keys._srfm_show_labels ) {
				rootContainer.classList.add( 'srfm-hide-labels' );
			} else {
				rootContainer.classList.remove( 'srfm-hide-labels' );
			}
			if ( ! sureforms_keys._srfm_show_asterisk ) {
				rootContainer.classList.add( 'srfm-hide-asterisk' );
			} else {
				rootContainer.classList.remove( 'srfm-hide-asterisk' );
			}
		}
		// Button text
		root.style.setProperty(
			'--srfm-submit-button-text',
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

	/*
	 * function to update post metas.
	 */
	function updateMeta( option, value ) {
		const value_id = 0;
		const key_id = '';

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

	return (
		<>
			<SRFMAdvancedPanelBody
				title={ __( 'General', 'sureforms' ) }
				initialOpen={ true }
			>
				<ToggleControl
					label={ __( 'Show Labels', 'sureforms' ) }
					checked={ sureforms_keys._srfm_show_labels }
					onChange={ ( value ) => {
						updateMeta( '_srfm_show_labels', value );
					} }
				/>
				{ sureforms_keys._srfm_show_labels && (
					<ToggleControl
						label={ __( 'Show Asterisk', 'sureforms' ) }
						checked={ sureforms_keys._srfm_show_asterisk }
						onChange={ ( value ) => {
							updateMeta( '_srfm_show_asterisk', value );
						} }
					/>
				) }
				<p className="components-base-control__help" />
				<ToggleControl
					label={ __(
						'Hide Form Title on the Page/Post',
						'sureforms'
					) }
					checked={ sureforms_keys._srfm_page_form_title }
					onChange={ ( value ) => {
						updateMeta( '_srfm_page_form_title', value );
					} }
				/>
				<ToggleControl
					label={ __(
						'Hide Form Title on the Single Form Page',
						'sureforms'
					) }
					checked={ sureforms_keys._srfm_single_page_form_title }
					onChange={ ( value ) => {
						updateMeta( '_srfm_single_page_form_title', value );
					} }
				/>
			</SRFMAdvancedPanelBody>
			<SRFMAdvancedPanelBody
				title={ __( 'Submit Button', 'sureforms' ) }
				initialOpen={ false }
			>
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
			</SRFMAdvancedPanelBody>
			{ isPageBreak && (
				<SRFMAdvancedPanelBody
					title={ __( 'Page Break', 'sureforms' ) }
					initialOpen={ false }
				>
					<SRFMTextControl
						label={ __( 'First Page Label', 'sureforms' ) }
						value={ sureforms_keys._srfm_first_page_label }
						data={ {
							value: sureforms_keys._srfm_first_page_label,
							label: '_srfm_first_page_label',
						} }
						onChange={ ( value ) =>
							updateMeta( '_srfm_first_page_label', value )
						}
					/>
					<SelectControl
						label={ __( 'Progress Indicator', 'sureforms' ) }
						value={
							sureforms_keys._srfm_page_break_progress_indicator
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
						checked={ sureforms_keys._srfm_page_break_toggle_label }
						onChange={ ( value ) => {
							updateMeta(
								'_srfm_page_break_toggle_label',
								value
							);
						} }
					/>
					{ sureforms_keys._srfm_page_break_progress_indicator !==
						'progress-bar' && (
						<ToggleControl
							label={ __( 'Show Labels', 'sureforms' ) }
							checked={
								sureforms_keys._srfm_page_break_toggle_label
							}
							onChange={ ( value ) => {
								updateMeta(
									'_srfm_page_break_toggle_label',
									value
								);
							} }
						/>
					) }
					<SRFMTextControl
						data={ {
							value: sureforms_keys._srfm_previous_button_text,
							label: '_srfm_previous_button_text',
						} }
						label={ __( 'Previous Button Text', 'sureforms' ) }
						value={ sureforms_keys._srfm_previous_button_text }
						onChange={ ( value ) => {
							updateMeta( '_srfm_previous_button_text', value );
						} }
						isFormSpecific={ true }
					/>
					<SRFMTextControl
						data={ {
							value: sureforms_keys._srfm_previous_button_text,
							label: '_srfm_next_button_text',
						} }
						label={ __( 'Next Button Text', 'sureforms' ) }
						value={ sureforms_keys._srfm_next_button_text }
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
					checked={ sureforms_keys._srfm_instant_form }
					onChange={ ( value ) => {
						updateMeta( '_srfm_instant_form', value );
					} }
				/>
				{ sureforms_keys._srfm_instant_form && <PostURLPanel /> }
			</SRFMAdvancedPanelBody>
		</>
	);
}

export default GeneralSettings;
