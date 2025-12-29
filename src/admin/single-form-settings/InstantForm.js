import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import SRFMMediaPicker from '@Components/image';
import MultiButtonsControl from '@Components/multi-buttons-control';
import Range from '@Components/range/Range.js';
import svgIcons from '@Svg/svgs.json';
import { ExternalLink, FormToggle, Popover } from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { select, useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useId, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { cleanForSlug } from '@wordpress/url';
import parse from 'html-react-parser';
import { createRoot } from 'react-dom/client';
import ConversationalFormSettingsPreview from './components/ConversationalFormSettingsPreview';
import { applyFilters } from '@wordpress/hooks';
import { getInstantFormAdditionalSettings } from '@Components/hooks';
import Spacing from '@Components/spacing';
import { instantFormAttributes } from '@Attributes/getBlocksDefaultAttributes';
import { setDefaultFormAttributes } from '@Utils/Helpers';
import FormSettingsPopup from './tabs/formSettingsPopup';

let live_mode_prev_srfm_instant_form_settings = {};

// Extract the key from obj1 if the obj1 key value is different from obj2 key value.
function findDifferentKeyValue( obj1, obj2 ) {
	let differentKey = '';

	// Iterate through the keys of obj2 (or obj1, they should be the same)
	for ( const key in obj2 ) {
		if ( obj2.hasOwnProperty( key ) ) {
			// Check if the key exists in obj1 and if the values are different
			if ( obj1.hasOwnProperty( key ) && obj1[ key ] !== obj2[ key ] ) {
				differentKey = key;
				break;
			}
		}
	}
	return differentKey;
}

const InstantFormComponent = () => {
	const getMetaValue = useSelect( ( hookSelect ) => {
		const getStore = hookSelect( editorStore );
		const metaValue = getStore.getEditedPostAttribute( 'meta' );
		const getPermalinkParts = getStore.getPermalinkParts();

		return {
			_srfm_submit_button_text: metaValue?._srfm_submit_button_text,
			_srfm_instant_form_settings: metaValue?._srfm_instant_form_settings,
			_srfm_forms_styling: metaValue?._srfm_forms_styling,
			getPermalinkParts,
		};
	}, [] );

	const _srfm_submit_button_text =
		getMetaValue._srfm_submit_button_text || '';
	const _srfm_instant_form_settings =
		getMetaValue._srfm_instant_form_settings || {};
	const _srfm_forms_styling = getMetaValue._srfm_forms_styling || {};
	const prefix = getMetaValue?.getPermalinkParts?.prefix;
	const postName = getMetaValue?.getPermalinkParts?.postName;

	const {
		// Form banner color / image.
		cover_type,
		cover_color,
		cover_image,

		site_logo,
		enable_instant_form,
		form_container_width,
		single_page_form_title,
		use_banner_as_page_background,
	} = _srfm_instant_form_settings;

	// Set the default keys in the meta object if they are not present.
	setDefaultFormAttributes( instantFormAttributes, _srfm_forms_styling );

	const {
		// Form Properties.
		// Padding.
		instant_form_padding_top,
		instant_form_padding_right,
		instant_form_padding_bottom,
		instant_form_padding_left,
		instant_form_padding_unit,
		instant_form_padding_link,
		// Border Radius.
		instant_form_border_radius_top,
		instant_form_border_radius_right,
		instant_form_border_radius_bottom,
		instant_form_border_radius_left,
		instant_form_border_radius_unit,
		instant_form_border_radius_link,
	} = _srfm_forms_styling;

	const { editPost } = useDispatch( editorStore );

	const [ isLiveMode, setIsLiveMode ] = useState( false );

	const [ popoverAnchor, setPopoverAnchor ] = useState();
	const [ openPopover, setOpenPopover ] = useState( false ); // Load / unload popover component from DOM.
	const [ hidePopover, setHidePopover ] = useState( false ); // Just hide the popover using CSS instead of unloading it from DOM.

	// Form Settings Popover state
	const [ formSettingsPopoverAnchor, setFormSettingsPopoverAnchor ] = useState();
	const [ openFormSettingsPopover, setOpenFormSettingsPopover ] = useState( false );
	const [ hideFormSettingsPopover, setHideFormSettingsPopover ] = useState( false );

	const [ isLinkCopied, setIsLinkCopied ] = useState( false );
	const [ editPostSlug, setEditPostSlug ] = useState( {
		edit: false,
		forceEmptyField: false,
	} );

	const link = prefix + postName;

	const clipboardRef = useCopyToClipboard( link, () => {
		setIsLinkCopied( true );
		setTimeout( () => setIsLinkCopied( false ), 2000 );
	} );

	// Hide or show the elements in live mode.
	const toggleElementsInLiveMode = ( remove = false ) => {
		const styleTagID = 'srfm-instant-form-toggle-elements-style';
		const tag = document.getElementById( styleTagID );

		if ( remove ) {
			return tag?.remove();
		}

		if ( ! tag ) {
			// Array of elements selectors to hide/show when live preview is enabled/disabled.
			const toggleElements = [
				'.interface-interface-skeleton__body',
				'.editor-header .editor-header__toolbar',
				'.interface-navigable-region.interface-interface-skeleton__footer',
			];

			const toggleElementsStyle = document.createElement( 'style' );
			toggleElementsStyle.id = styleTagID;
			toggleElementsStyle.innerText =
				toggleElements.join( ', ' ) + '{ display:none !important; }';

			// Adding visibility:hidden separately so that it don't create visual disturbance.
			toggleElementsStyle.innerText +=
				'.editor-header__settings > :not(.srfm-instant-form-root) { visibility:hidden !important; }';

			document.head.append( toggleElementsStyle );
		}
	};

	// Returns array of instant styles that don't needs iframe refresh.
	const getInstantStyles = () => {
		const instantStyles = [];

		// Instantly display changes inside iframe without refreshing the iframe.
		switch (
			findDifferentKeyValue(
				live_mode_prev_srfm_instant_form_settings,
				_srfm_instant_form_settings
			)
		) {
			case 'cover_color':
				instantStyles.push( `
					${
	! use_banner_as_page_background
		? '.single-sureforms_form .srfm-single-page-container .srfm-page-banner'
		: 'html body.single-sureforms_form'
} {
						background-color: ${ cover_color };
					}
				` );
				break;

			case 'form_container_width':
				instantStyles.push( `
					#srfm-single-page-container {
						--srfm-form-container-width: ${ form_container_width }px;
					}
				` );
				break;

			default:
				break;
		}

		return instantStyles;
	};

	// Filter to add additional metas for live preview.
	const additionalMetasForLivePreview = applyFilters(
		'srfm.instantFormLivePreviewAdditionalMetas',
		{
			isLiveMode,
			_srfm_submit_button_text,
			_srfm_instant_form_settings,
		}
	);

	const liveModeDependencies = Object.values( additionalMetasForLivePreview );

	// Returns URL for the Instant Form Live Preview.
	const getIframePreviewURL = ( postLink ) => {
		const url = new URL( postLink ); // Use the default ( not edited ) post link for live mode as edited version is not saved yet.
		const params = new URLSearchParams( url.search );

		params.set( 'preview', true );
		params.set( 'live_mode', true );
		params.set( '_srfm_submit_button_text', _srfm_submit_button_text ); // This will help display submit button if live preview is for unpublished form (draft).

		Object.keys( _srfm_instant_form_settings ).forEach( ( key ) => {
			params.set( key, _srfm_instant_form_settings[ key ] );
		} );

		// use liveModeDependencies
		liveModeDependencies.forEach( ( value ) => {
			Object.keys( value ).forEach( ( key ) => {
				params.set( key, value[ key ] );
			} );
		} );

		url.search = params.toString();

		return url.toString();
	};

	/**
	 * Manage live preview mode.
	 */
	useEffect( () => {
		const contentArea = document.querySelector(
			'#editor .interface-interface-skeleton__editor'
		);

		if ( ! contentArea ) {
			live_mode_prev_srfm_instant_form_settings = {};
			return;
		}

		let iframe = contentArea.querySelector(
			'.srfm-instant-form-live-mode-iframe'
		);

		if ( ! isLiveMode ) {
			// Unload live mode iframe is live mode is disabled.
			iframe?.remove();
			toggleElementsInLiveMode( true );

			live_mode_prev_srfm_instant_form_settings = {};
			return;
		}

		toggleElementsInLiveMode();

		const currentPost = select( editorStore ).getCurrentPost();

		if ( ! iframe ) {
			iframe = document.createElement( 'iframe' );
			iframe.className = 'srfm-instant-form-live-mode-iframe';
			iframe.setAttribute(
				'style',
				'height:100vh;background-color:#ffffff;'
			);
			contentArea.append( iframe );
		}

		/** @type {document} */
		const iframeContentDocument = iframe.contentDocument;
		const instantStyles = getInstantStyles();

		if ( instantStyles.length > 0 ) {
			/** @type {HTMLStyleElement} */
			let styleTag = iframeContentDocument.head.querySelector(
				'#srfm-instant-form-live-mode-styles'
			);

			if ( ! styleTag ) {
				styleTag = document.createElement( 'style' );
				styleTag.id = 'srfm-instant-form-live-mode-styles';
			}

			styleTag.innerHTML += instantStyles.join( ' ' );
			iframeContentDocument.head.append( styleTag );
		} else {
			// Pre-content load.
			const iframeHTMLTag = iframeContentDocument.querySelector( 'html' );
			iframeHTMLTag.style.opacity = 0;
			iframeHTMLTag.style.transition = 'all 0.5s ease-in-out';

			// Refresh the iframe for other keys.
			iframe.src = getIframePreviewURL( currentPost.link );
		}

		live_mode_prev_srfm_instant_form_settings = _srfm_instant_form_settings;
	}, [ ...liveModeDependencies ] );

	const onHandleChange = ( key, value ) => {
		if ( _srfm_instant_form_settings?.[ key ] === value ) {
			// Do not re-render if the value is same. This is necessary for color picker type controls which re-render on selection.
			return;
		}

		const instantFormSettings = {
			..._srfm_instant_form_settings,
			...{
				[ key ]: value,
			},
		};

		editPost( {
			meta: {
				_srfm_instant_form_settings: instantFormSettings,
			},
		} );
	};

	// Custom setAttributes function to update the form styling related settings.
	const customSetAttributes = ( updatedSettings ) => {
		editPost( {
			meta: {
				_srfm_forms_styling: {
					..._srfm_forms_styling,
					...updatedSettings,
				},
			},
		} );
	};

	/**
	 * Handles the selection of an image and updates the post metadata with the selected image's URL and ID.
	 *
	 * This function performs the following steps:
	 * 1. Checks if the provided `media` object is valid and of type 'image'.
	 * 2. If valid, it extracts the image's ID and URL, and then updates the post metadata with this information.
	 * 3. If the `media` object is not valid or is not an image, it sets the image URL to `null`.
	 *
	 * @param {string} key   - The key used to identify the metadata field for the image URL in the post metadata.
	 * @param {Object} media - The media object representing the selected image.
	 */
	const onImageSelect = ( key, media ) => {
		let key_id = '';
		let imageID = 0;
		let imageURL = media;

		if (
			! media ||
			! media.url ||
			! media.type ||
			'image' !== media.type
		) {
			imageURL = null;
		}

		if ( imageURL ) {
			imageID = imageURL.id;
			imageURL = imageURL.sizes.full.url;
		}
		key_id = key + '_id';

		const instantFormSettings = {
			..._srfm_instant_form_settings,
			...{
				[ key ]: imageURL,
				[ key_id ]: imageID,
			},
		};

		editPost( {
			meta: {
				_srfm_instant_form_settings: instantFormSettings,
			},
		} );
	};

	// apply filters to show/hide instant form components.
	const {
		showInstantFormSiteLogoGroup,
		showInstantFormStylingGroup,
		showInstantFormTitleSetting,
	} = applyFilters( 'srfm.instantFormComponent', {
		showInstantFormSiteLogoGroup: true,
		showInstantFormStylingGroup: true,
		showInstantFormTitleSetting: true,
	} );

	// Filter to add additional settings to instant form popup.
	const additionalSettings = getInstantFormAdditionalSettings( [], {
		setHidePopover,
	} );

	return (
		<>
			<div className="srfm-instant-form-button-wrapper" style={
				{
					display: 'flex',
					gap: '10px'
				}
			}>
				<button
					ref={ setFormSettingsPopoverAnchor }
					className="srfm-form-settings-button"
					onClick={ () => setOpenFormSettingsPopover( ! openFormSettingsPopover ) }
					style={{
						cursor: 'pointer',
						color: '#475467',
						background: 'rgba(0, 0, 0, 0)',
						border: '1px solid #d1d5db',
						borderRadius: '4px',
						padding: '10px 16px 10px 16px',
						display: 'flex',
						alignItems: 'center',
						gap: '8px',
						width: 'max-content',
						justifyContent: 'center',
					}}
				>
					{ __( 'Form Settings', 'sureforms' ) }
				</button>
				<button
					ref={ setPopoverAnchor }
					onClick={ () => setOpenPopover( ! openPopover ) }
					className="srfm-instant-form-button"
				>
					<div
						className="srfm-instant-form-status"
						style={
							!! enable_instant_form
								? { backgroundColor: '#22C55E' }
								: {}
						}
					/>
					<span>{ __( 'Instant Form', 'sureforms' ) }</span>
				</button>
			</div>

			{ openPopover && (
				<Popover
					resize
					hidden={ hidePopover }
					placement="bottom-end"
					anchor={ popoverAnchor }
					onFocusOutside={ ( event ) => {
						if (
							event.relatedTarget?.className ===
							popoverAnchor.className
						) {
							// Bail if clicked on the Instant Form toggle button, and remove Live Preview.
							return;
						}

						if (
							event.relatedTarget?.className?.includes(
								'media-modal'
							)
						) {
							// Unloading the Popover triggers error when media uploader modal is opened.
							// Don't close the Popover when media uploader is opened, instead just hide the popover.
							setHidePopover( true );
							return;
						}

						setOpenPopover( false );
					} }
					className="srfm-instant-form-popover"
				>
					<div className="srfm-instant-form-settings-container">
						<div className="srfm-instant-form-settings-group">
							<InstantFormToggle
								label={ __(
									'Enable Instant Form',
									'sureforms'
								) }
								checked={ true === enable_instant_form }
								onChange={ () =>
									onHandleChange(
										'enable_instant_form',
										! enable_instant_form
									)
								}
							/>

							<InstantFormToggle
								label={ __( 'Enable Preview', 'sureforms' ) }
								checked={ true === isLiveMode }
								onChange={ () => setIsLiveMode( ! isLiveMode ) }
							/>

							<ConversationalFormSettingsPreview
								setHidePopover={ setHidePopover }
							/>

							{ showInstantFormTitleSetting && (
								<InstantFormToggle
									label={ __( 'Show Title', 'sureforms' ) }
									checked={ true === single_page_form_title }
									onChange={ () =>
										onHandleChange(
											'single_page_form_title',
											! single_page_form_title
										)
									}
								/>
							) }
						</div>

						<div className="srfm-instant-form-settings-separator" />

						{ additionalSettings.length > 0 &&
							additionalSettings.map( ( setting, index ) => (
								<div key={ index }>{ setting }</div>
							) ) }

						{ showInstantFormSiteLogoGroup && (
							<>
								<div className="srfm-instant-form-settings-group">
									<div className="srfm-instant-form-settings">
										<label>
											{ __( 'Site Logo', 'sureforms' ) }
										</label>
										<SRFMMediaPicker
											label={ '' }
											onModalClose={ () =>
												setHidePopover( false )
											}
											onSelectImage={ ( media ) =>
												onImageSelect(
													'site_logo',
													media
												)
											}
											backgroundImage={ site_logo }
											onRemoveImage={ () =>
												onHandleChange(
													'site_logo',
													''
												)
											}
											isFormSpecific={ true }
										/>
									</div>

									<div className="srfm-instant-form-settings">
										<label>
											{ __(
												'Banner Background',
												'sureforms'
											) }
										</label>
										<MultiButtonsControl
											data={ {
												value: cover_type,
												label: 'cover_type',
											} }
											options={ [
												{
													value: 'color',
													label: __(
														'Color',
														'sureforms'
													),
												},
												{
													value: 'image',
													label: __(
														'Image',
														'sureforms'
													),
												},
											] }
											showIcons={ false }
											onChange={ ( value ) =>
												onHandleChange(
													'cover_type',
													value
												)
											}
										/>
									</div>

									{ 'image' === cover_type ? (
										<div className="srfm-instant-form-settings">
											<label>
												{ __(
													'Upload Image',
													'sureforms'
												) }
											</label>
											<SRFMMediaPicker
												label={ '' }
												onModalClose={ () =>
													setHidePopover( false )
												}
												onSelectImage={ ( media ) =>
													onImageSelect(
														'cover_image',
														media
													)
												}
												backgroundImage={ cover_image }
												onRemoveImage={ () =>
													onHandleChange(
														'cover_image',
														''
													)
												}
												isFormSpecific={ true }
											/>
										</div>
									) : (
										<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
											<label>
												{ __(
													'Background Color',
													'sureforms'
												) }
											</label>
											<AdvancedPopColorControl
												colorValue={ cover_color }
												data={ {
													value: cover_color,
													label: 'cover_color',
												} }
												onColorChange={ (
													colorValue
												) =>
													onHandleChange(
														'cover_color',
														colorValue
													)
												}
												value={ cover_color }
												isFormSpecific={ true }
											/>
										</div>
									) }
								</div>
								<div className="srfm-instant-form-settings-separator" />
							</>
						) }

						{ showInstantFormStylingGroup && (
							<>
								<div className="srfm-instant-form-settings-group">
									<InstantFormToggle
										label={ __(
											'Use banner as page background',
											'sureforms'
										) }
										checked={
											true ===
											use_banner_as_page_background
										}
										onChange={ () =>
											onHandleChange(
												'use_banner_as_page_background',
												! use_banner_as_page_background
											)
										}
									/>

									<div className="srfm-instant-form-settings">
										<Range
											label={ __(
												'Form Width',
												'sureforms'
											) }
											data={ {
												value: form_container_width,
												label: 'form_container_width',
											} }
											value={ form_container_width }
											min={ 560 }
											max={ 1000 }
											displayUnit={ false }
											responsive={ false }
											isFormSpecific={ true }
											onChange={ ( value ) =>
												onHandleChange(
													'form_container_width',
													value
												)
											}
										/>
									</div>
									<div className="srfm-instant-form-settings">
										<Spacing
											label={ __(
												'Instant Form Padding',
												'sureforms'
											) }
											valueTop={ {
												value: instant_form_padding_top,
												label: 'instant_form_padding_top',
											} }
											valueRight={ {
												value: instant_form_padding_right,
												label: 'instant_form_padding_right',
											} }
											valueBottom={ {
												value: instant_form_padding_bottom,
												label: 'instant_form_padding_bottom',
											} }
											valueLeft={ {
												value: instant_form_padding_left,
												label: 'instant_form_padding_left',
											} }
											unit={ {
												value: instant_form_padding_unit,
												label: 'instant_form_padding_unit',
											} }
											link={ {
												value: instant_form_padding_link,
												label: 'instant_form_padding_link',
											} }
											setAttributes={
												customSetAttributes
											}
										/>
									</div>
									<div className="srfm-instant-form-settings">
										<Spacing
											label={ __(
												'Instant Form Border Radius',
												'sureforms'
											) }
											valueTop={ {
												value: instant_form_border_radius_top,
												label: 'instant_form_border_radius_top',
											} }
											valueRight={ {
												value: instant_form_border_radius_right,
												label: 'instant_form_border_radius_right',
											} }
											valueBottom={ {
												value: instant_form_border_radius_bottom,
												label: 'instant_form_border_radius_bottom',
											} }
											valueLeft={ {
												value: instant_form_border_radius_left,
												label: 'instant_form_border_radius_left',
											} }
											unit={ {
												value: instant_form_border_radius_unit,
												label: 'instant_form_border_radius_unit',
											} }
											link={ {
												value: instant_form_border_radius_link,
												label: 'instant_form_border_radius_link',
											} }
											setAttributes={
												customSetAttributes
											}
										/>
									</div>
								</div>
								<div className="srfm-instant-form-settings-separator" />
							</>
						) }

						<div className="srfm-instant-form-settings-group">
							<div className="srfm-instant-form-settings">
								<label>{ __( 'URL', 'sureforms' ) }</label>
								<div className="srfm-instant-form-url">
									<input
										readOnly
										disabled={ editPostSlug.edit }
										type="url"
										value={ link }
										onClick={ () =>
											setEditPostSlug( {
												...editPostSlug,
												...{
													edit: ! editPostSlug.edit,
												},
											} )
										}
									/>
									<button type="button" ref={ clipboardRef }>
										{ isLinkCopied
											? parse(
												svgIcons[ 'square-checked' ]
											  )
											: parse( svgIcons.copy ) }
									</button>
								</div>

								{ !! editPostSlug.edit && (
									<Popover
										variant="toolbar"
										noArrow={ false }
										focusOnMount={ true }
										onFocusOutside={ () =>
											setEditPostSlug( {
												...editPostSlug,
												...{ edit: false },
											} )
										}
									>
										<div className="srfm-instant-form-settings-container">
											<div className="srfm-instant-form-settings-group">
												<div className="srfm-instant-form-settings">
													<label>
														{ __(
															'URL Slug',
															'sureforms'
														) }
													</label>

													<input
														type="text"
														value={
															! editPostSlug.forceEmptyField
																? postName
																: ''
														}
														spellCheck={ false }
														autoComplete={ 'off' }
														onChange={ ( e ) => {
															editPost( {
																slug: e.target
																	.value,
															} );

															if (
																e.target.value
															) {
																return setEditPostSlug(
																	{
																		...editPostSlug,
																		...{
																			forceEmptyField: false,
																		},
																	}
																);
															}

															// Force empty field of this input field.
															setEditPostSlug( {
																...editPostSlug,
																...{
																	forceEmptyField: true,
																},
															} );
														} }
														onBlur={ ( e ) => {
															editPost( {
																slug: cleanForSlug(
																	e.target.value.replace(
																		prefix,
																		''
																	)
																),
															} );
															setEditPostSlug( {
																...editPostSlug,
																...{
																	forceEmptyField: false,
																},
															} );
														} }
													/>
													<small>
														{ __(
															'The last part of the URL.',
															'sureforms'
														) }{ ' ' }
														<ExternalLink
															href={
																'https://wordpress.org/documentation/article/page-post-settings-sidebar/#permalink'
															}
														>
															{ __(
																'Learn more.',
																'sureforms'
															) }
														</ExternalLink>
													</small>
												</div>
											</div>
										</div>
									</Popover>
								) }
							</div>
						</div>
					</div>
				</Popover>
			) }

			{ openFormSettingsPopover && (
				<FormSettingsPopup
					popoverAnchor={ formSettingsPopoverAnchor }
					setOpenPopover={ setOpenFormSettingsPopover }
					hidePopover={ hideFormSettingsPopover }
					setHidePopover={ setHideFormSettingsPopover }
				/>
			) }
		</>
	);
};

export const InstantFormToggle = ( props ) => {
	const toggleID = useId();

	return (
		<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
			<label htmlFor={ toggleID } style={ { cursor: 'pointer' } }>
				{ props.label }
			</label>
			<FormToggle { ...props } label="" id={ toggleID } />
		</div>
	);
};

export default () => {
	// check if gutenberg's editor root element is present.
	const editorEl = document.getElementById( 'editor' );
	if ( ! editorEl ) {
		// do nothing if there's no gutenberg root element on page.
		return;
	}

	const rootDiv = document.createElement( 'div' );
	rootDiv.classList.add( 'srfm-instant-form-root' );
	const root = createRoot( rootDiv );

	const unsubscribe = wp.data.subscribe( function () {
		setTimeout( function () {
			root.render( <InstantFormComponent /> );
			if ( document.querySelector( '.srfm-instant-form-root' ) ) {
				return;
			}
			const toolbarElement =
				editorEl.querySelector( '.edit-post-header__settings' ) ||
				editorEl.querySelector( '.editor-header__settings' );
			if ( !! toolbarElement ) {
				toolbarElement.prepend( rootDiv );
			}
		}, 1 );
	} );
	// unsubscribe
	if ( document.querySelector( '.srfm-instant-form-root' ) ) {
		unsubscribe();
	}
};
