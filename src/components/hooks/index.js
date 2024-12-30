import { applyFilters } from '@wordpress/hooks';

export const attributeOptionsWithFilter = ( options, props ) => {
	return applyFilters( 'srfm.block.attributes.panel.body', options, props );
};
