import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import SRFMMediaPicker from '@Components/image';
import MultiButtonsControl from '@Components/multi-buttons-control';
import Range from '@Components/range/Range.js';
import SRFMTextControl from '@Components/text-control';
import { useDeviceType } from '@Controls/getPreviewType';
import svgIcons from '@Image/single-form-logo.json';
import {
	ExternalLink,
	Modal,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import parse from 'html-react-parser';
import SingleFormSettingsPopup from '../components/SingleFormSettingPopup';
import PostURLPanel from '../components/form-permalink/Panel';

let prevMetaHash = '';

function GeneralSettings( props ) {
	const { createNotice } = useDispatch( 'core/notices' );

	const { status } = useSelect( ( select ) =>
		select( editorStore ).getCurrentPost()
	);

	const { editPost } = useDispatch( editorStore );
	const { defaultKeys, isPageBreak } = props;
	let sureformsKeys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);
	const deviceType = useDeviceType();
	const [ rootContainer, setRootContainer ] = useState(
		document.getElementById( 'srfm-form-container' )
	);
	const root = document.documentElement.querySelector( 'body' );
	const [ isOpen, setOpen ] = useState( false );
	const [ popupTab, setPopupTab ] = useState( false );
	const [ hasValidationErrors, setHasValidationErrors ] = useState(false);

	const openModal = ( e ) => {
		const popupTabTarget = e.currentTarget.getAttribute( 'data-popup' );
		setPopupTab( popupTabTarget );
		setOpen( true );
		prevMetaHash = btoa( JSON.stringify( sureformsKeys ) );
	};
	const closeModal = () => {

		if (hasValidationErrors && !confirm(__('Your settings are not saved as you have some validation errors. Are you sure you want to close?'))) {
			return;
		}

		setOpen( false );

		if ( btoa( JSON.stringify( sureformsKeys ) ) !== prevMetaHash ) {
			createNotice(
				'warning',
				sprintf(
					__(
						'There are few unsaved changes. Please %s this form to save them.',
						'sureforms'
					),
					'publish' === status
						? __( 'update', 'sureforms' )
						: __( 'publish', 'sureforms' )
				),
				{
					id: 'srfm-unsaved-changes-warning',
					isDismissible: true,
				}
			);
		}
	};

	const modalIcon = parse( svgIcons.modalLogo );

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
		}

		// Header Background Image
		if ( option === '_srfm_cover_image' ) {
			if ( value ) {
				value_id = value.id;
				value = value.sizes.full.url;
			}
			key_id = option + '_id';
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

		const option_array = {};

		if ( key_id ) {
			option_array[ key_id ] = value_id;
		}
		option_array[ option ] = value;
		editPost( {
			meta: option_array,
		} );
	}

	const onSelectRestImage = ( meta, media ) => {
		let imageUrl = media;
		if (
			! media ||
			! media.url ||
			! media.type ||
			'image' !== media.type
		) {
			imageUrl = null;
		}

		updateMeta( meta, imageUrl );
	};

	/*
	 * Event to set Image as null while removing it.
	 */
	const onRemoveRestImage = ( meta ) => {
		updateMeta( meta, '' );
	};

	const instantFormUrl = useSelect( ( select ) =>
		select( editorStore ).getPermalink()
	);

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
			</SRFMAdvancedPanelBody>
			{ isPageBreak && (
				<SRFMAdvancedPanelBody
					title={ __( 'Page Break', 'sureforms' ) }
					initialOpen={ false }
				>
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
								'Show Title on Instant Form',
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
						{ sureformsKeys._srfm_instant_form && (
							<>
								<div>
									<div className="srfm-instant-form-settings-separator"></div>
								</div>
								<p className="srfm-panel__body-sub-heading">
									{ __(
										'Instant Form Styling Settings',
										'sureforms'
									) }
								</p>
								<p className="components-base-control__help">
									{ __(
										'Please preview the styling for the instant form ',
										'sureforms'
									) }
									<ExternalLink href={ instantFormUrl }>
										{ __( 'here', 'sureforms' ) }
									</ExternalLink>
								</p>
								<SRFMMediaPicker
									label={ __(
										'Header Background Image',
										'sureforms'
									) }
									onSelectImage={ ( media ) =>
										onSelectRestImage(
											'_srfm_cover_image',
											media
										)
									}
									backgroundImage={
										sureformsKeys._srfm_cover_image
									}
									onRemoveImage={ () =>
										onRemoveRestImage( '_srfm_cover_image' )
									}
									isFormSpecific={ true }
								/>
								<Range
									label={ __(
										'Form Container Width',
										'sureforms'
									) }
									value={
										sureformsKeys._srfm_form_container_width
									}
									min={ 650 }
									max={ 1000 }
									displayUnit={ false }
									data={ {
										value: sureformsKeys._srfm_form_container_width,
										label: '_srfm_form_container_width',
									} }
									onChange={ ( value ) =>
										updateMeta(
											'_srfm_form_container_width',
											value
										)
									}
									isFormSpecific={ true }
								/>
								<p className="components-base-control__help" />
								<MultiButtonsControl
									label={ __(
										'Background Type',
										'sureforms'
									) }
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
										label={ __(
											'Background Image',
											'sureforms'
										) }
										onSelectImage={ ( media ) =>
											onSelectRestImage(
												'_srfm_bg_image',
												media
											)
										}
										backgroundImage={
											sureformsKeys._srfm_bg_image
										}
										onRemoveImage={ () =>
											onRemoveRestImage(
												'_srfm_bg_image'
											)
										}
										isFormSpecific={ true }
									/>
								) : (
									<AdvancedPopColorControl
										label={ __(
											'Background Color',
											'sureforms'
										) }
										colorValue={
											sureformsKeys._srfm_bg_color
										}
										data={ {
											value: sureformsKeys._srfm_bg_color,
											label: '_srfm_bg_color',
										} }
										onColorChange={ ( colorValue ) => {
											if (
												colorValue !==
												sureformsKeys._srfm_bg_color
											) {
												updateMeta(
													'_srfm_bg_color',
													colorValue
												);
											}
										} }
										value={ sureformsKeys._srfm_bg_color }
										isFormSpecific={ true }
									/>
								) }
							</>
						) }
					</>
				) }
			</SRFMAdvancedPanelBody>
			<FormBehaviorButton
				settingName={ __( 'Form Behavior', 'sureforms' ) }
				popupId="email_notification"
				openModal={ openModal }
			/>
			{ isOpen && (
				<Modal
					onRequestClose={ closeModal }
					title={ __( 'Form Behavior', 'sureforms' ) }
					className="srfm-settings-modal"
					icon={ modalIcon }
					isFullScreen={ true }
				>
					<SingleFormSettingsPopup
						sureformsKeys={ sureformsKeys }
						targetTab={ popupTab }
						setHasValidationErrors={setHasValidationErrors}
					/>
				</Modal>
			) }
		</>
	);
}

const FormBehaviorButton = ( { settingName, popupId, openModal } ) => {
	return (
		<div className="srfm-custom-layout-panel components-panel__body">
			<h2 className="components-panel__body-title">
				<button
					className="components-button components-panel__body-toggle"
					onClick={ openModal }
					data-popup={ popupId }
				>
					<span className="srfm-title">
						<div>{ settingName }</div>
					</span>
					<svg
						width="24"
						height="24"
						viewBox="0 0 24 24"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
					>
						<g id="heroicons-mini/ellipsis-horizontal">
							<g id="Union">
								<path
									d="M3.60156 12.0031C3.60156 11.009 4.40745 10.2031 5.40156 10.2031C6.39567 10.2031 7.20156 11.009 7.20156 12.0031C7.20156 12.9972 6.39567 13.8031 5.40156 13.8031C4.40745 13.8031 3.60156 12.9972 3.60156 12.0031Z"
									fill="#555D66"
								/>
								<path
									d="M10.2016 12.0031C10.2016 11.009 11.0074 10.2031 12.0016 10.2031C12.9957 10.2031 13.8016 11.009 13.8016 12.0031C13.8016 12.9972 12.9957 13.8031 12.0016 13.8031C11.0074 13.8031 10.2016 12.9972 10.2016 12.0031Z"
									fill="#555D66"
								/>
								<path
									d="M18.6016 10.2031C17.6074 10.2031 16.8016 11.009 16.8016 12.0031C16.8016 12.9972 17.6074 13.8031 18.6016 13.8031C19.5957 13.8031 20.4016 12.9972 20.4016 12.0031C20.4016 11.009 19.5957 10.2031 18.6016 10.2031Z"
									fill="#555D66"
								/>
							</g>
						</g>
					</svg>
				</button>
			</h2>
		</div>
	);
};

export default GeneralSettings;
