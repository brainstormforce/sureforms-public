import { applyFilters } from '@wordpress/hooks';

export const AfterAttributesPanelBody = ( options, props ) => {
	return applyFilters( 'srfm.block.attributes.panel.body', options, props );
};
