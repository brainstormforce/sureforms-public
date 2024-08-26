import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect, useRef } from '@wordpress/element';

const SRFMEditorHeader = () => {
	const postId = useSelect( ( select ) => {
		return select( 'core/editor' ).getCurrentPostId();
	}, [] );

	const [ title, setTitle ] = useEntityProp(
		'postType',
		'sureforms_form',
		'title',
		postId
	);

	const formTitleInputRef = useRef( null );
	useEffect( () => {
		if ( formTitleInputRef.current ) {
			formTitleInputRef.current.focus();
		}
	}, [] );

	return (
		<TextControl
			ref={ formTitleInputRef }
			className="srfm-header-title-input"
			placeholder={ __( 'Form Title', 'sureforms' ) }
			value={ title }
			onChange={ ( value ) => {
				setTitle( value );
			} }
			autoComplete="off"
		/>
	);
};

export default SRFMEditorHeader;
