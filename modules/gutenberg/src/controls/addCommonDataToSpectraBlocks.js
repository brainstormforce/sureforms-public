import { applyFilters } from '@wordpress/hooks';
function addCommonDataToSpectraBlocks( configData = {} ) {
	let data = {
		example: {
			attributes: {
				isPreview: true,
			},
		},
		usesContext: [ 'postId', 'postType' ],
	};

	if ( 'site-editor' === srfm_spec_blocks_info.is_site_editor ) {
		data = {};
	}
	return applyFilters( 'addCommonDataToSpectraBlocks', {
		...configData,
		...data,
	} );
}
export default addCommonDataToSpectraBlocks;
