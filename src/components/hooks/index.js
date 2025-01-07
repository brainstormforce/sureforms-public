import { applyFilters } from '@wordpress/hooks';

export const attributeOptionsWithFilter = ( options, props ) => {
	return applyFilters( 'srfm.block.attributes.panel.body', options, props );
};

export const enhanceDropdownOptions = ( option, args ) => {
	return applyFilters( 'srfm.blocks.dropdown.options.enhance', option, args );
};

export const enhanceMultiChoiceOptions = ( option, args ) => {
	return applyFilters(
		'srfm.blocks.multi.choice.options.enhance',
		option,
		args
	);
};
