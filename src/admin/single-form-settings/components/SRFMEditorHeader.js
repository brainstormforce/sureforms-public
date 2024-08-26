import { __ } from '@wordpress/i18n';
import { useEntityProp } from '@wordpress/core-data';
import { TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { createRoot } from 'react-dom/client';

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

	return (
		<TextControl
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

export const addHeaderCenterContainer = () => {
	let intervalToClear = setInterval( () => {
		const headerCenterContainer =
			document.querySelector( '.edit-post-header__center' ) ||
			// added support for WP 6.6.
			document.querySelector( '.editor-header__center' );
		if ( headerCenterContainer ) {
			// Clear the interval.
			clearInterval( intervalToClear );

			// remove the command bar and add our custom header title editor
			const header = document.querySelector( '.editor-post-title__block' );
			if ( header ) {
				header.remove();
			}
			const root = createRoot( headerCenterContainer );
			root.render( <SRFMEditorHeader /> );
		}
	}, 50 );
};