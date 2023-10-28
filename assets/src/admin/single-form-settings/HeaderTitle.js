import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { TextControl } from '@wordpress/components';

const HeaderTitle = () => {
	const postId = wp.data.select( 'core/editor' ).getCurrentPostId();

	const [ title, setTitle ] = useEntityProp(
		'postType',
		'sureforms_form',
		'title',
		postId
	);

	return (
		<TextControl
			style={ {
				width: '311px',
				padding: '11px 16px',
				marginTop: '12px',
				borderRadius: '4px',
				border: '1px solid #949494',
				background: '#FFF',
				boxShadow: 'none',
			} }
			placeholder={ __( 'Form Title', 'sureforms' ) }
			value={ title }
			onChange={ ( value ) => {
				setTitle( value );
			} }
			autoComplete="off"
			autoFocus={ true }
		/>
	);
};

export default HeaderTitle;
