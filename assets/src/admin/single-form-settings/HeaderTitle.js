import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';

const HeaderTitle = () => {
	const postId = wp.data.select( 'core/editor' ).getCurrentPostId();
	const postStatus = useSelect( ( select ) => {
		return select( 'core/editor' ).getEditedPostAttribute( 'status' );
	}, [] );

	const [ title, setTitle ] = useEntityProp(
		'postType',
		'sureforms_form',
		'title',
		postId
	);

	return (
		<TextControl
			style={ {
				width: '500px',
				padding: '11px 16px',
				marginTop: '12px',
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
			{ ...( postStatus === 'draft' && { autoFocus: true } ) }
		/>
	);
};

export default HeaderTitle;
