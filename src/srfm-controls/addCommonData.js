import { applyFilters } from '@wordpress/hooks';
function addCommonData( configData = {} ) {
	let data = {
		example: {
			attributes: {
				isPreview: true,
			},
		},
		usesContext: [ 'postId', 'postType' ],
	};

	if ( 'site-editor' === srfm_blocks_info.is_site_editor ) {
		data = {};
	}
	return applyFilters( 'addCommonData', {
		...configData,
		...data,
	} );
}
export default addCommonData;
