import { store as coreStore } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';

export const useGetSureFormsKeys = ( formId ) => {
	const postType = useSelect( ( select ) =>
		select( 'core/editor' ).getCurrentPostType()
	);

	return useSelect( ( select ) => {
		if ( 'sureforms_form' === postType ) {
			return select( 'core/editor' ).getEditedPostAttribute( 'meta' );
		}
		const form = select( coreStore ).getEntityRecord(
			'postType',
			'sureforms_form',
			formId
		);
		const postMeta = form?.meta;
		return postMeta;
	} );
};
