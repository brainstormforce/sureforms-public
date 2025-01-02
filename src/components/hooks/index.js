import { applyFilters } from '@wordpress/hooks';

export const attributeOptionsWithFilter = ( options, props ) => {
	return applyFilters( 'srfm.block.attributes.panel.body', options, props );
};

export const shouldShowDropdownValues = ( value ) => {
	return applyFilters( 'srfm.block.dropdown.values', false, value );
}

export const shouldShowMultiChoiceValues = ( value ) => {
	return applyFilters( 'srfm.block.multi-choice.values', false, value );
}