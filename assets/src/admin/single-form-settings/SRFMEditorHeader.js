import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

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

	const status = useEntityProp(
		'postType',
		'sureforms_form',
		'status',
		postId
	);

	return (
		<TextControl
			className="srfm-header-title-input"
			placeholder={ __( 'Form Title', 'sureforms' ) }
			value={ title }
			onChange={ ( value ) => {
				setTitle( value );
			} }
			// eslint-disable-next-line jsx-a11y/no-autofocus
			autoFocus={ 'auto-draft' === status || 'draft' === status ? true : false }
			autoComplete="off"
		/>
	);
};

export default SRFMEditorHeader;
