import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import SRFMMediaPicker from '@Components/image';
import MultiButtonsControl from '@Components/multi-buttons-control';
import Range from '@Components/range/Range.js';
import svgIcons from '@Svg/svgs.json';
import { Popover, ToggleControl } from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { select, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import parse from 'html-react-parser';
import { createRoot } from 'react-dom/client';

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

	const [ popoverAnchor, setPopoverAnchor ] = useState();
	const [ openPopover, setOpenPopover ] = useState( false ); // Load / unload popover component from DOM.
	const [ hidePopover, setHidePopover ] = useState( false ); // Just hide the popover using CSS instead of unloading it from DOM.
	const [ isLinkCopied, setIsLinkCopied ] = useState( false );

	const { link } = select( editorStore ).getCurrentPost();

	const clipboardRef = useCopyToClipboard( link, () => {
		setIsLinkCopied( true );
		setTimeout( () => setIsLinkCopied( false ), 2000 );
	} );

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
	console.log( 'Rendered' );

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
						if ( event.relatedTarget.className === popoverAnchor.className ) {
							// Bail if clicked on the Instant Form toggle button.
							return;
						}

						if ( event.relatedTarget.className.includes( 'media-modal' ) ) {
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
							<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
								<label>{ __( 'Enable Instant Form', 'sureforms' ) }</label>
								<ToggleControl
									checked={ true === enable_instant_form }
									onChange={ ( value ) => onHandleChange( 'enable_instant_form', value ) }
								/>
							</div>

							<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
								<label>{ __( 'Show Title', 'sureforms' ) }</label>
								<ToggleControl
									checked={ single_page_form_title }
									onChange={ ( value ) => onHandleChange( 'single_page_form_title', value ) }
								/>
							</div>
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
							<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
								<label>{ __( 'Use banner as page background', 'sureforms' ) }</label>
								<ToggleControl
									checked={ use_banner_as_page_background }
									onChange={ ( value ) => onHandleChange( 'use_banner_as_page_background', value ) }
								/>
							</div>

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
									min={ 650 }
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
									<input type="url" readOnly defaultValue={ link } />
									<button
										type="button"
										ref={ clipboardRef }
									>
										{ isLinkCopied ? parse( svgIcons[ 'square-checked' ] ) : parse( svgIcons.copy ) }
									</button>
								</div>
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
