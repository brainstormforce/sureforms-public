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

const InstantFormToggle = ( props ) => {
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

const InstantFormComponent = () => {
	const { _srfm_instant_form_settings } = select( editorStore ).getEditedPostAttribute( 'meta' );

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

	/**
	 * Manage live preview mode.
	 */
	useEffect( () => {
		const contentArea = document.querySelector( '#editor .interface-interface-skeleton__body' );

		if ( ! contentArea ) {
			return;
		}

		let iframe = contentArea.querySelector( '.srfm-instant-form-live-mode-iframe' );

		if ( ! isLiveMode ) {
			// Unload live mode iframe is live mode is disabled.
			contentArea.classList.remove( 'srfm-instant-form-live-mode' );
			iframe?.remove();
			return;
		}

		contentArea.classList.add( 'srfm-instant-form-live-mode' );

		const currentPost = select( editorStore ).getCurrentPost();

		const url = new URL( currentPost.link ); // Use the default ( not edited ) post link for live mode as edited version is not saved yet.
		const params = new URLSearchParams( _srfm_instant_form_settings );

		params.set( 'live_mode', true );

		url.search = params.toString();

		if ( ! iframe ) {
			iframe = document.createElement( 'iframe' );
			iframe.className = 'srfm-instant-form-live-mode-iframe';
			contentArea.append( iframe );
		}

		// Pre-content load.
		const iframeHTMLTag = iframe.contentDocument.querySelector( 'html' );
		iframeHTMLTag.style.opacity = 0;
		iframeHTMLTag.style.transition = 'all 0.5s ease-in-out';

		iframe.src = url.toString();
	}, [ isLiveMode, _srfm_instant_form_settings ] );

	const onHandleChange = ( key, value ) => {
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
							// Bail if clicked on the Instant Form toggle button.
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
								label={ __( 'Enable Live Mode', 'sureforms' ) }
								checked={ true === isLiveMode }
								onChange={ () => setIsLiveMode( ! isLiveMode ) }
							/>

							<InstantFormToggle
								label={ __( 'Show Title', 'sureforms' ) }
								checked={ true === single_page_form_title }
								onChange={ () => onHandleChange( 'single_page_form_title', ! single_page_form_title ) }
							/>
						</div>

						<div className="srfm-instant-form-settings-separator" />

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

														<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
															<label>{ __( 'URL Slug', 'sureforms' ) }</label>
															<button
																className="button button-link"
																onClick={ () => setEditPostSlug( { ...editPostSlug, ...{ edit: false } } ) }
															>
																{ __( 'Save', 'sureforms' ) }
															</button>
														</div>
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
