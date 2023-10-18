import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

export const useGetCurrentFormId = ( clientId ) => {
	return useSelect( ( select ) => {
		// parent block id attribute.
		const parents = select( blockEditorStore ).getBlockParents( clientId );
		const parentBlock = select( blockEditorStore ).getBlocksByClientId(
			parents?.[ 0 ]
		);
		// current post id.
		const post_id = select( 'core/editor' ).getCurrentPostId();
		return parentBlock?.[ 0 ]?.attributes?.id || post_id;
	} );
};
