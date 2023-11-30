import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { TextControl, Button, Modal } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';
import Setup from '../components/template-picker/Setup';
import apiFetch from '@wordpress/api-fetch';
import { createBlocksFromInnerBlocksTemplate, parse } from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';

const SRFMEditorHeader = ( { clientId } ) => {
	const postId = useSelect( ( select ) => {
		return select( 'core/editor' ).getCurrentPostId();
	}, [] );
	const postStatus = useSelect( ( select ) => {
		return select( 'core/editor' ).getEditedPostAttribute( 'status' );
	}, [] );
	const [ title, setTitle ] = useEntityProp(
		'postType',
		'sureforms_form',
		'title',
		postId
	);
	const [ patterns, setPatterns ] = useState( [] );
	const [ template, setTemplate ] = useState( '' );
	const { replaceInnerBlocks, resetBlocks } = useDispatch( blockEditorStore );
	const [ templatePickerVisible, setTemplatePickerVisible ] = useState();
	// const blockCount = useSelect( ( select ) =>
	// 	select( 'core/editor' ).getBlockCount( clientId )
	// );
	// let sureforms_keys = useSelect( ( select ) =>
	// 	select( editorStore ).getEditedPostAttribute( 'meta' )
	// );
	const { editPost } = useDispatch( editorStore );

	useEffect( () => {
		getPatterns();
	}, [] );

	// useEffect( () => {
	// if ( ! templatePickerVisible ) {
	// if ( sureforms_keys._srfm_form_template === 'blank-form' ) {
	// 	setTemplatePickerVisible( false );
	// } else if (
	// 	sureforms_keys._srfm_form_template === '' &&
	// 	! templatePickerVisible
	// ) {
	// 	setTemplatePickerVisible( true );
	// } else if ( blockCount === 0 ) {
	// 	setTemplatePickerVisible( false );
	// }
	// }, [
	// sureforms_keys._srfm_form_template,
	//  blockCount
	// ] );

	useEffect( () => {
		if ( template && postStatus !== 'publish' ) {
			onCreate( template );
		} else if ( template ) {
			onCreate( template );
		}
	}, [ template, onCreate ] );

	const getPatterns = async () => {
		const newPatterns = await apiFetch( {
			path: '/sureforms/v1/form-patterns',
		} );
		setPatterns( newPatterns );
	};

	/**
	 * Maybe create the template for the form.
	 *
	 */
	const maybeCreateTemplate = async () => {
		const newPattern = patterns.find(
			( singlePattern ) =>
				singlePattern.name === `sureforms/${ template }`
		);

		if ( ! newPattern ) {
			alert( 'Something went wrong' );
			return;
		}
		// parse blocks.
		const parsed = parse( newPattern.content );

		return parsed;
	};

	const onCreate = async () => {
		const option_array = {};
		option_array[ '_srfm_form_template' ] = template;
		editPost( {
			meta: option_array,
		} );
		const result = await maybeCreateTemplate( {
			template,
		} );
		resetBlocks( [] );
		replaceInnerBlocks(
			clientId,
			createBlocksFromInnerBlocksTemplate( result ),
			false
		);
	};

	const handleTemplatePicker = ( choice ) => {
		setTemplatePickerVisible( ! templatePickerVisible );
		if ( typeof choice !== 'object' ) {
			setTemplate( choice );
		}
	};

	return (
		<>
			{ /* Template picker (can be made as a separate component) */ }
			<Button
				style={ {
					marginTop: '16px',
					marginRight: '12px',
					borderRadius: '4px',
					border: '1px solid #94A3B8',
					background: 'transparent',
					color: 'black',
					fontFamily: 'Inter',
					fontSize: '12px',
					fontStyle: 'normal',
					fontWeight: '400',
					display: 'flex',
					justifyContent: 'center',
					gap: '4px',
				} }
				variant="primary"
				onClick={ handleTemplatePicker }
			>
				<svg
					width="16"
					height="16"
					viewBox="0 0 36 36"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
				>
					<path
						fillRule="evenodd"
						clipRule="evenodd"
						d="M17.519 35.5C27.1944 35.5 35.0379 27.665 35.0379 18C35.0379 8.33502 27.1944 0.5 17.519 0.5C7.84351 0.5 0 8.33502 0 18C0 27.665 7.84351 35.5 17.519 35.5ZM17.5944 9.25C16.1877 9.25 14.241 10.0536 13.2463 11.0449L10.5448 13.7372H24.0104L28.5129 9.25H17.5944ZM21.7689 24.9551C20.7742 25.9464 18.8275 26.75 17.4208 26.75H6.50228L11.0048 22.2628H24.4704L21.7689 24.9551ZM26.1453 15.9808H8.29837L7.45535 16.8221C5.4592 18.617 6.05123 20.0192 8.84675 20.0192H26.742L27.5853 19.1779C29.562 17.3936 28.9408 15.9808 26.1453 15.9808Z"
						fill="currentColor"
					/>
				</svg>
				{ __( 'Template Picker', 'sureforms' ) }
			</Button>
			{ templatePickerVisible && (
				<Modal
					focusOnMount //focus on the first element in the modal
					shouldCloseOnEsc
					shouldCloseOnClickOutside
					overlayClassName="my-extra-modal-overlay-class"
					title={ __( 'Choose A Starting Template', 'sureforms' ) }
					onRequestClose={ handleTemplatePicker }
				>
					<Setup
						templates={ patterns }
						onCreate={ onCreate }
						clientId={ clientId }
						handleTemplatePicker={ handleTemplatePicker }
					/>
				</Modal>
			) }
			<TextControl
				style={ {
					width: '500px',
					padding: '11px 16px',
					marginTop: '12px',
					marginRight: '50px',
					borderRadius: '4px',
					border: '1px solid #94A3B8',
					background: ' #F9FAFB',
					boxShadow: 'none',
					fontFamily: 'Inter',
					fontSize: '16px',
					fontStyle: 'normal',
					fontWeight: '400',
				} }
				className="srfm-header-title-input"
				placeholder={ __( 'Form Title', 'sureforms' ) }
				value={ title }
				onChange={ ( value ) => {
					setTitle( value );
				} }
				autoComplete="off"
			/>
		</>
	);
};

export default SRFMEditorHeader;
