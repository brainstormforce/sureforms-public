import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import SRFMMediaPicker from '@Components/image';
import MultiButtonsControl from '@Components/multi-buttons-control';
import Range from '@Components/range/Range.js';
import svgIcons from '@Svg/svgs.json';
import { Popover, ToggleControl } from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { select, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { render, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import parse from 'html-react-parser';

const InstantFormComponent = () => {
	const {
		// Form background color / image.
		_srfm_bg_type,
		_srfm_bg_color,
		_srfm_bg_image,

		// Form banner color / image.
		_srfm_cover_type,
		_srfm_cover_color,
		_srfm_cover_image,

		_srfm_site_logo,
		_srfm_instant_form,
		_srfm_form_container_width,
		_srfm_single_page_form_title,
		_srfm_use_banner_as_page_background,
	} = select( editorStore ).getEditedPostAttribute( 'meta' );

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
		editPost( {
			meta: {
				[ key ]: value,
			},
		} );
	};

	/**
	 * Handles the selection of an image and updates the post metadata with the selected image's URL and ID.
	 *
	 * This function performs the following steps:
	 * 1. Sets the visibility of an element to be visible by calling `setIsHidden(false)`.
	 * 2. Checks if the provided `media` object is valid and of type 'image'.
	 * 3. If valid, it extracts the image's ID and URL, and then updates the post metadata with this information.
	 * 4. If the `media` object is not valid or is not an image, it sets the image URL to `null`.
	 *
	 * @param {string} key   - The key used to identify the metadata field for the image URL in the post metadata.
	 * @param {Object} media - The media object representing the selected image.
	 */
	const onImageSelect = ( key, media ) => {
		setHidePopover( false );

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

		editPost( {
			meta: {
				[ key ]: imageURL,
				[ key_id ]: imageID,
			},
		} );
	};

	return (
		<>
			<button ref={ setPopoverAnchor } onClick={ () => setOpenPopover( ! openPopover ) } className="srfm-instant-form-button">
				<div className="srfm-instant-form-status" style={ !! _srfm_instant_form ? { backgroundColor: '#22C55E' } : {} } />
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
									checked={ _srfm_instant_form }
									onChange={ ( value ) => onHandleChange( '_srfm_instant_form', value ) }
								/>
							</div>

							<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
								<label>{ __( 'Show Title', 'sureforms' ) }</label>
								<ToggleControl
									checked={ _srfm_single_page_form_title }
									onChange={ ( value ) => onHandleChange( '_srfm_single_page_form_title', value ) }
								/>
							</div>
						</div>

						<div className="srfm-instant-form-settings-separator" />

						<div className="srfm-instant-form-settings-group">
							<div className="srfm-instant-form-settings">
								<label>{ __( 'Site Logo', 'sureforms' ) }</label>
								<SRFMMediaPicker
									label={ '' }
									onSelectImage={ ( media ) => onImageSelect( '_srfm_site_logo', media ) }
									backgroundImage={ _srfm_site_logo }
									onRemoveImage={ () => onHandleChange( '_srfm_site_logo', '' ) }
									isFormSpecific={ true }
								/>
							</div>

							<div className="srfm-instant-form-settings">
								<label>{ __( 'Banner Background', 'sureforms' ) }</label>
								<MultiButtonsControl
									data={ {
										value: _srfm_cover_type,
										label: '_srfm_cover_type',
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
									onChange={ ( value ) => onHandleChange( '_srfm_cover_type', value ) }
								/>
							</div>

							{
								'image' === _srfm_cover_type ? (
									<div className="srfm-instant-form-settings">
										<label>{ __( 'Upload Image', 'sureforms' ) }</label>
										<SRFMMediaPicker
											label={ '' }
											onSelectImage={ ( media ) => onImageSelect( '_srfm_cover_image', media ) }
											backgroundImage={ _srfm_cover_image }
											onRemoveImage={ () => onHandleChange( '_srfm_cover_image', '' ) }
											isFormSpecific={ true }
										/>
									</div>
								) : (
									<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
										<label>{ __( 'Background Color', 'sureforms' ) }</label>
										<AdvancedPopColorControl
											colorValue={ _srfm_cover_color }
											data={ {
												value: _srfm_cover_color,
												label: '_srfm_cover_color',
											} }
											onColorChange={ ( colorValue ) => onHandleChange( '_srfm_cover_color', colorValue ) }
											value={ _srfm_cover_color }
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
									checked={ _srfm_use_banner_as_page_background }
									onChange={ ( value ) => onHandleChange( '_srfm_use_banner_as_page_background', value ) }
								/>
							</div>

							<div className="srfm-instant-form-settings">
								<label>{ __( 'Form Background', 'sureforms' ) }</label>
								<MultiButtonsControl
									data={ {
										value: _srfm_bg_type,
										label: '_srfm_bg_type',
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
									onChange={ ( value ) => onHandleChange( '_srfm_bg_type', value ) }
								/>
							</div>

							{
								'image' === _srfm_bg_type ? (
									<div className="srfm-instant-form-settings">
										<label>{ __( 'Upload Image', 'sureforms' ) }</label>
										<SRFMMediaPicker
											label={ '' }
											onSelectImage={ ( imageURL ) => onImageSelect( '_srfm_bg_image', imageURL ) }
											backgroundImage={ _srfm_bg_image }
											onRemoveImage={ () => onHandleChange( '_srfm_bg_image', '' ) }
											isFormSpecific={ true }
										/>
									</div>
								) : (
									<div className="srfm-instant-form-settings srfm-instant-form-settings-inline">
										<label>{ __( 'Background Color', 'sureforms' ) }</label>
										<AdvancedPopColorControl
											colorValue={ _srfm_bg_color }
											data={ {
												value: _srfm_bg_color,
												label: '_srfm_bg_color',
											} }
											onColorChange={ ( colorValue ) => onHandleChange( '_srfm_bg_color', colorValue ) }
											value={ _srfm_bg_color }
											isFormSpecific={ true }
										/>
									</div>
								)
							}

							<div className="srfm-instant-form-settings">
								<Range
									label={ __( 'Form Width', 'sureforms' ) }
									data={ {
										value: _srfm_form_container_width,
										label: '_srfm_form_container_width',
									} }
									value={ _srfm_form_container_width }
									min={ 650 }
									max={ 1000 }
									displayUnit={ false }
									responsive={ false }
									isFormSpecific={ true }
									onChange={ ( value ) => onHandleChange( '_srfm_form_container_width', value ) }
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
	const rootDiv = document.createElement( 'div' );
	rootDiv.classList.add( 'srfm-instant-form-root' );

	// check if gutenberg's editor root element is present.
	const editorEl = document.getElementById( 'editor' );
	if ( ! editorEl ) {
		// do nothing if there's no gutenberg root element on page.
		return;
	}

	const unsubscribe = wp.data.subscribe( function () {
		setTimeout( function () {
			render( <InstantFormComponent />, rootDiv );
			if ( ! document.querySelector( '.srfm-instant-form-root' ) ) {
				const toolbarElement = editorEl.querySelector( '.edit-post-header__settings' ) || editorEl.querySelector( '.editor-header__settings' );
				if ( !! toolbarElement ) {
					toolbarElement.prepend( rootDiv );
				}
			}
		}, 1 );
	} );
	// unsubscribe
	if ( document.querySelector( '.srfm-instant-form-root' ) ) {
		unsubscribe();
	}
};
