import { applyFilters } from '@wordpress/hooks';

export const AfterAttributesPanelBody = ( props ) => {
    return applyFilters( 'srfm.after.attributes.panel.body',[], props );
}