import { useEffect, useState, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { BaseControl } from '@wordpress/components';
import { MediaUpload } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { getIdFromString, getPanelIdFromRef } from '@Utils/Helpers';
import SRFM_Block_Icons from '@Controls/block-icons';
import apiFetch from '@wordpress/api-fetch';
import getSRFMEditorStateLocalStorage from '@Controls/getSRFMEditorStateLocalStorage';
import SRFMConfirmPopup from '../popup-confirm';
import SRFMHelpText from '@Components/help-text';
import { applyFilters } from '@wordpress/hooks';

const SRFMMediaPicker = ( props ) => {
	const [ panelNameForHook, setPanelNameForHook ] = useState( null );
	const panelRef = useRef( null );

	const selectedBlock = useSelect( ( select ) => {
		return select( 'core/block-editor' ).getSelectedBlock();
	}, [] );

	const srfmLocalStorage = getSRFMEditorStateLocalStorage();
	const blockNameForHook = selectedBlock?.name.split( '/' ).pop(); // eslint-disable-line @wordpress/no-unused-vars-before-return

	useEffect( () => {
		setPanelNameForHook( getPanelIdFromRef( panelRef ) );
	}, [ blockNameForHook ] );

	const [ isOpen, setOpen ] = useState( false );

	const {
		onModalClose,
		onSelectImage,
		backgroundImage,
		onRemoveImage,
		slug = 'image',
		label = __( 'Image', 'sureforms' ),
		disableLabel = false,
		disableRemove = false,
		allow = [ 'image' ],
		disableDynamicContent = false,
		help = false,
		addIcon = null,
	} = props;

	// This is used to render an icon in place of the background image when needed.
	let placeholderIcon;

	// These are the localized texts that will show on the Select / Change Button and Popup.
	let selectMediaLabel, replaceMediaLabel;

	switch ( slug ) {
		case 'video':
			selectMediaLabel = __( 'Select Video', 'sureforms' );
			replaceMediaLabel = __( 'Change Video', 'sureforms' );
			placeholderIcon = SRFM_Block_Icons.video_placeholder;
			break;
		case 'lottie':
			selectMediaLabel = __( 'Select Lottie Animation', 'sureforms' );
			replaceMediaLabel = __( 'Change Lottie Animation', 'sureforms' );
			placeholderIcon = SRFM_Block_Icons.lottie;
			break;
		case 'svg':
			selectMediaLabel = __( 'Upload SVG', 'sureforms' );
			replaceMediaLabel = __( 'Change SVG', 'sureforms' );
			break;
		default:
			selectMediaLabel = __( 'Select Image', 'sureforms' );
			replaceMediaLabel = __( 'Change Image', 'sureforms' );
	}

	const registerImageExtender = disableDynamicContent
		? null
		: applyFilters(
			'srfm.registerImageExtender',
			'',
			selectedBlock?.name,
			onSelectImage
		  );
	const registerImageLinkExtender = disableDynamicContent
		? null
		: applyFilters(
			'srfm.registerImageLinkExtender',
			'',
			selectedBlock?.name,
			'bgImageLink',
			'url'
		  );

	const isShowImageUploader = () => {
		if ( disableDynamicContent ) {
			return true;
		}
		const dynamicContent = selectedBlock?.attributes?.dynamicContent;
		if ( dynamicContent && dynamicContent?.bgImage?.enable === true ) {
			return false;
		}
		return true;
	};

	const onConfirm = ( open ) => {
		const formData = new window.FormData();
		formData.append( 'action', 'srfm_svg_confirmation' );
		formData.append(
			'svg_nonce',
			srfm_blocks_info.srfm_svg_confirmation_nonce
		);
		formData.append( 'confirmation', 'yes' );

		apiFetch( {
			url: srfm_blocks_info.ajax_url,
			method: 'POST',
			body: formData,
		} ).then( ( response ) => {
			if ( response.success ) {
				srfmLocalStorage.setItem(
					'srfmSvgConfirmation',
					JSON.stringify( 'yes' )
				);
				open();
			}
		} );
	};

	const OpenMediaUploader = ( open ) => {
		const svgConfirmation = getSRFMEditorStateLocalStorage(
			'srfmSvgConfirmation'
		);
		if ( slug !== 'svg' || svgConfirmation === 'yes' ) {
			open();
			return;
		}

		setOpen( true );
	};

	const renderMediaUploader = ( open ) => {
		const uploadType = backgroundImage ? 'replace' : 'add';
		return (
			<>
				{ 'add' === uploadType && (
					<button
						className={ `srfm-media-control__clickable srfm-media-control__clickable--${ uploadType }` }
						onClick={ () => OpenMediaUploader( open ) }
					>
						{ renderButton( uploadType ) }
					</button>
				) }
				<div className="srfm-media-control__footer">
					<button
						className="srfm-control-label"
						onClick={ () => OpenMediaUploader( open ) }
					>
						{ replaceMediaLabel }
					</button>
					{ registerImageExtender }
				</div>
				{ slug === 'svg' && (
					<SRFMConfirmPopup
						isOpen={ isOpen }
						setOpen={ setOpen }
						onConfirm={ onConfirm }
						title={ __( 'Upload SVG?', 'sureforms' ) }
						description={ __(
							'Upload SVG can be potentially risky. Are you sure?',
							'sureforms'
						) }
						confirmLabel={ __( 'Upload Anyway', 'sureforms' ) }
						cancelLabel={ __( 'Cancel', 'sureforms' ) }
						executable={ open }
					/>
				) }
			</>
		);
	};

	const renderButton = ( buttonType ) => {
		let renderIcon = SRFM_Block_Icons[ buttonType ];

		if ( addIcon && 'add' === buttonType ) {
			renderIcon = addIcon;
		}

		return (
			<div
				className={ `srfm-media-control__button srfm-media-control__button--${ buttonType }` }
			>
				{ renderIcon }
			</div>
		);
	};

	// This Can Be Deprecated.
	const generateBackground = ( media ) => {
		const regex = /(?:\.([^.]+))?$/;
		let mediaURL = media;
		switch ( regex.exec( String( mediaURL ) )[ 1 ] ) {
			// For Lottie JSON Files.
			case 'json':
				mediaURL = '';
				break;
			// For Videos.
			case 'avi':
			case 'mpg':
			case 'mp4':
			case 'm4v':
			case 'mov':
			case 'ogv':
			case 'vtt':
			case 'wmv':
			case '3gp':
			case '3g2':
				mediaURL = '';
				break;
		}
		return mediaURL;
	};

	const controlName = getIdFromString( props?.label );
	const controlBeforeDomElement = applyFilters(
		`srfm.${ blockNameForHook }.${ panelNameForHook }.${ controlName }.before`,
		'',
		blockNameForHook
	);
	const controlAfterDomElement = applyFilters(
		`srfm.${ blockNameForHook }.${ panelNameForHook }.${ controlName }`,
		'',
		blockNameForHook
	);

	return (
		<div ref={ panelRef } className="components-base-control">
			{ controlBeforeDomElement }
			<BaseControl
				className="srfm-media-control"
				id={ `srfm-option-selector-${ slug }` }
				label={ label }
				hideLabelFromVision={ disableLabel }
			>
				{ isShowImageUploader() ? (
					<>
						<div
							className="srfm-media-control__wrapper"
							style={ {
								backgroundImage:
									! placeholderIcon &&
									backgroundImage &&
									! backgroundImage?.svg &&
									`url("${ generateBackground(
										backgroundImage
									) }")`,
							} }
						>
							{ placeholderIcon && backgroundImage && (
								<div className="srfm-media-control__icon srfm-media-control__icon--stroke">
									{ placeholderIcon }
								</div>
							) }
							{ backgroundImage?.svg && (
								<div
									className="srfm-media-control__icon srfm-media-control__icon--stroke"
									// eslint-disable-next-line react/no-danger
									dangerouslySetInnerHTML={ {
										__html: backgroundImage.svg,
									} }
								></div>
							) }
							<MediaUpload
								title={ selectMediaLabel }
								onSelect={ onSelectImage }
								allowedTypes={ allow }
								value={ backgroundImage }
								render={ ( { open } ) =>
									renderMediaUploader( open )
								}
								onClose={ onModalClose }
							/>
							{ ! disableRemove && backgroundImage && (
								<button
									className="srfm-media-control__clickable srfm-media-control__clickable--close"
									onClick={ onRemoveImage }
								>
									{ renderButton( 'close' ) }
								</button>
							) }
						</div>
						<SRFMHelpText text={ help } />
					</>
				) : (
					registerImageExtender
				) }
			</BaseControl>
			{ registerImageLinkExtender }
			{ controlAfterDomElement }
		</div>
	);
};

export default SRFMMediaPicker;
