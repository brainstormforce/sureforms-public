import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import SRFMMediaPicker from '@Components/image';
import MultiButtonsControl from '@Components/multi-buttons-control';
import Range from '@Components/range/Range.js';
import svgIcons from '@Svg/svgs.json';
import { ExternalLink, FormToggle, Popover } from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { select, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useId, useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { cleanForSlug } from '@wordpress/url';
import parse from 'html-react-parser';
import { createRoot } from 'react-dom/client';
import ConversationalFormSettings from './components/ConversationalFormSettings';
import { applyFilters } from '@wordpress/hooks';

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
	const { _srfm_submit_button_text, _srfm_instant_form_settings } = select( editorStore ).getEditedPostAttribute( 'meta' );

	const {
		// Form background color / image.
		bg_type,
		bg_color,
		bg_image,

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

	const { editPost } = useDispatch( editorStore );

	const [ isLiveMode, setIsLiveMode ] = useState( false );

	const [ popoverAnchor, setPopoverAnchor ] = useState();
	const [ openPopover, setOpenPopover ] = useState( false ); // Load / unload popover component from DOM.
	const [ hidePopover, setHidePopover ] = useState( false ); // Just hide the popover using CSS instead of unloading it from DOM.
	const [ isLinkCopied, setIsLinkCopied ] = useState( false );
	const [ editPostSlug, setEditPostSlug ] = useState( {
		edit: false,
		forceEmptyField: false,
	} );

	const { prefix, postName } = select( editorStore ).getPermalinkParts();

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
			toggleElementsStyle.innerText = toggleElements.join( ', ' ) + '{ display:none !important; }';

			// Adding visibility:hidden separately so that it don't create visual disturbance.
			toggleElementsStyle.innerText += '.editor-header__settings > :not(.srfm-instant-form-root) { visibility:hidden !important; }';

			document.head.append( toggleElementsStyle );
		}
	};

	// Returns array of instant styles that don't needs iframe refresh.
	const getInstantStyles = () => {
		const instantStyles = [];

		// Instantly display changes inside iframe without refreshing the iframe.
		switch ( findDifferentKeyValue( live_mode_prev_srfm_instant_form_settings, _srfm_instant_form_settings ) ) {
			case 'cover_color':
				instantStyles.push( `
					${ ! use_banner_as_page_background ? '.single-sureforms_form .srfm-single-page-container .srfm-page-banner' : 'html body.single-sureforms_form' } {
						background-color: ${ cover_color };
					}
				` );
				break;

			case 'bg_color':
				instantStyles.push( `
					#srfm-single-page-container {
						--srfm-bg-color: ${ bg_color };
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

		url.search = params.toString();

		return url.toString();
	};

	/**
	 * Manage live preview mode.
	 */
	useEffect( () => {
		const contentArea = document.querySelector( '#editor .interface-interface-skeleton__editor' );

		if ( ! contentArea ) {
			live_mode_prev_srfm_instant_form_settings = {};
			return;
		}

		let iframe = contentArea.querySelector( '.srfm-instant-form-live-mode-iframe' );

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
			iframe.setAttribute( 'style', 'height:100vh;background-color:#ffffff;' );
			contentArea.append( iframe );
		}

		/** @type {document} */
		const iframeContentDocument = iframe.contentDocument;
		const instantStyles = getInstantStyles();

		if ( instantStyles.length > 0 ) {
			/** @type {HTMLStyleElement} */
			let styleTag = iframeContentDocument.head.querySelector( '#srfm-instant-form-live-mode-styles' );

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
	}, [ isLiveMode, _srfm_instant_form_settings ] );

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
	const { showInstantFormSiteLogoGroup, showInstantFormStylingGroup } = applyFilters( 'srfm.instantFormComponent', {
		showInstantFormSiteLogoGroup: true,
		showInstantFormStylingGroup: true,
	} );

	return (
		<>
			<button ref={ setPopoverAnchor } onClick={ () => setOpenPopover( ! openPopover ) } className="srfm-instant-form-button">
				<div className="srfm-instant-form-status" style={ !! enable_instant_form ? { backgroundColor: '#22C55E' } : {} } />
				<span>{ __( 'Instant Form', 'sureforms' ) }</span>
			</button>

			{ openPopover && (
				<Popover
					resize
					hidden={ hidePopover }
					placement="bottom-end"
					anchor={ popoverAnchor }
					onFocusOutside={ ( event ) => {
						if ( event.relatedTarget?.className === popoverAnchor.className ) {
							// Bail if clicked on the Instant Form toggle button, and remove Live Preview.
							return;
						}

						if ( event.relatedTarget?.className?.includes( 'media-modal' ) ) {
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
								label={ __( 'Enable Instant Form', 'sureforms' ) }
								checked={ true === enable_instant_form }
								onChange={ () => onHandleChange( 'enable_instant_form', ! enable_instant_form ) }
							/>

							<InstantFormToggle
								label={ __( 'Enable Preview', 'sureforms' ) }
								checked={ true === isLiveMode }
								onChange={ () => setIsLiveMode( ! isLiveMode ) }
							/>

							<InstantFormToggle
								label={ __( 'Show Title', 'sureforms' ) }
								checked={ true === single_page_form_title }
								onChange={ () => onHandleChange( 'single_page_form_title', ! single_page_form_title ) }
							/>

							<ConversationalFormSettings setHidePopover={ setHidePopover } />
						</div>

						<div className="srfm-instant-form-settings-separator" />

						{
						 showInstantFormSiteLogoGroup &&
							<>
								<div className="srfm-instant-form-settings-group">
									<div className="srfm-instant-form-settings">
										<label>{ __( 'Site Logo', 'sureforms' ) }</label>
										<SRFMMediaPicker
											label={ '' }
											onModalClose={ () => setHidePopover( false ) }
											onSelectImage={ ( media ) => onImageSelect( 'site_logo', media ) }
											backgroundImage={ site_logo }
											onRemoveImage={ () => onHandleChange( 'site_logo', '' ) }
											isFormSpecific={ true }
										/>
									</div>

									<div className="srfm-instant-form-settings">
										<label>{ __( 'Banner Background', 'sureforms' ) }</label>
										<MultiButtonsControl
											data={ {
												value: cover_type,
												label: 'cover_type',
											} }
											options={ [
												{
													value: 'color',
													label: __( 'Color', 'sureforms' ),
												},
												{
													value: 'image',
													label: __( 'Image', 'sureforms' ),
												},
											] }
											showIcons={ false }
											onChange={ ( value ) => onHandleChange( 'cover_type', value ) }
										/>
									</div>

									{
										'image' === cover_type ? (
											<div className="srfm-instant-form-settings">
												<label>{ __( 'Upload Image', 'sureforms' ) }</label>
												<SRFMMediaPicker
													label={ '' }
													onModalClose={ () => setHidePopover( false ) }
													onSelectImage={ ( media ) => onImageSelect( 'cover_image', media ) }
													backgroundImage={ cover_image }
													onRemoveImage={ () => onHandleChange( 'cover_image', '' ) }
													isFormSpecific={ true }
												/>
											</div>
										) : (
											<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
												<label>{ __( 'Background Color', 'sureforms' ) }</label>
												<AdvancedPopColorControl
													colorValue={ cover_color }
													data={ {
														value: cover_color,
														label: 'cover_color',
													} }
													onColorChange={ ( colorValue ) => onHandleChange( 'cover_color', colorValue ) }
													value={ cover_color }
													isFormSpecific={ true }
												/>
											</div>
										)
									}
								</div>
								<div className="srfm-instant-form-settings-separator" />
							</>
						}

						{
						 showInstantFormStylingGroup &&
							<>
								<div className="srfm-instant-form-settings-group">
									<InstantFormToggle
										label={ __( 'Use banner as page background', 'sureforms' ) }
										checked={ true === use_banner_as_page_background }
										onChange={ () => onHandleChange( 'use_banner_as_page_background', ! use_banner_as_page_background ) }
									/>

									<div className="srfm-instant-form-settings">
										<label>{ __( 'Form Background', 'sureforms' ) }</label>
										<MultiButtonsControl
											data={ {
												value: bg_type,
												label: 'bg_type',
											} }
											options={ [
												{
													value: 'color',
													label: __( 'Color', 'sureforms' ),
												},
												{
													value: 'image',
													label: __( 'Image', 'sureforms' ),
												},
											] }
											showIcons={ false }
											onChange={ ( value ) => onHandleChange( 'bg_type', value ) }
										/>
									</div>

									{
										'image' === bg_type ? (
											<div className="srfm-instant-form-settings">
												<label>{ __( 'Upload Image', 'sureforms' ) }</label>
												<SRFMMediaPicker
													label={ '' }
													onModalClose={ () => setHidePopover( false ) }
													onSelectImage={ ( imageURL ) => onImageSelect( 'bg_image', imageURL ) }
													backgroundImage={ bg_image }
													onRemoveImage={ () => onHandleChange( 'bg_image', '' ) }
													isFormSpecific={ true }
												/>
											</div>
										) : (
											<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
												<label>{ __( 'Background Color', 'sureforms' ) }</label>
												<AdvancedPopColorControl
													colorValue={ bg_color }
													data={ {
														value: bg_color,
														label: 'bg_color',
													} }
													onColorChange={ ( colorValue ) => onHandleChange( 'bg_color', colorValue ) }
													value={ bg_color }
													isFormSpecific={ true }
												/>
											</div>
										)
									}

									<div className="srfm-instant-form-settings">
										<Range
											label={ __( 'Form Width', 'sureforms' ) }
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
											onChange={ ( value ) => onHandleChange( 'form_container_width', value ) }
										/>
									</div>
								</div>
								<div className="srfm-instant-form-settings-separator" />
							</>
						}

						<div className="srfm-instant-form-settings-group">
							<div className="srfm-instant-form-settings">
								<label>{ __( 'URL', 'sureforms' ) }</label>
								<div className="srfm-instant-form-url">
									<input
										readOnly
										disabled={ editPostSlug.edit }
										type="url"
										value={ link }
										onClick={ () => setEditPostSlug( { ...editPostSlug, ...{ edit: ! editPostSlug.edit } } ) }
									/>
									<button
										type="button"
										ref={ clipboardRef }
									>
										{ isLinkCopied ? parse( svgIcons[ 'square-checked' ] ) : parse( svgIcons.copy ) }
									</button>
								</div>

								{
									!! editPostSlug.edit && (
										<Popover
											variant="toolbar"
											noArrow={ false }
											focusOnMount={ true }
											onFocusOutside={ () => setEditPostSlug( { ...editPostSlug, ...{ edit: false } } ) }
										>
											<div className="srfm-instant-form-settings-container">
												<div className="srfm-instant-form-settings-group">
													<div className="srfm-instant-form-settings">
														<label>{ __( 'URL Slug', 'sureforms' ) }</label>

														<input
															type="text"
															value={ ! editPostSlug.forceEmptyField ? postName : '' }
															spellCheck={ false }
															autoComplete={ 'off' }
															onChange={ ( e ) => {
																editPost( {
																	slug: e.target.value,
																} );

																if ( e.target.value ) {
																	return setEditPostSlug( { ...editPostSlug, ...{ forceEmptyField: false } } );
																}

																// Force empty field of this input field.
																setEditPostSlug( { ...editPostSlug, ...{ forceEmptyField: true } } );
															} }
															onBlur={ ( e ) => {
																editPost( {
																	slug: cleanForSlug( e.target.value.replace( prefix, '' ) ),
																} );
																setEditPostSlug( { ...editPostSlug, ...{ forceEmptyField: false } } );
															} }
														/>
														<small>
															{ __( 'The last part of the URL.', 'sureforms' ) }{ ' ' }
															<ExternalLink href={ 'https://wordpress.org/documentation/article/page-post-settings-sidebar/#permalink' }>
																{ __( 'Learn more.', 'sureforms' ) }
															</ExternalLink>
														</small>
													</div>
												</div>
											</div>
										</Popover>
									)
								}
							</div>
						</div>
					</div>
				</Popover>
			) }
		</>
	);
};

export const InstantFormToggle = ( props ) => {
	const toggleID = useId();

	return (
		<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
			<label htmlFor={ toggleID } style={ { cursor: 'pointer' } }>{ props.label }</label>
			<FormToggle
				{ ...props }
				label=""
				id={ toggleID }
			/>
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
			const toolbarElement = editorEl.querySelector( '.edit-post-header__settings' ) || editorEl.querySelector( '.editor-header__settings' );
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
