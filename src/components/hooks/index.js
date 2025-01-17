import { applyFilters } from '@wordpress/hooks';

export const attributeOptionsWithFilter = ( options, props ) => {
	return applyFilters( 'srfm.block.attributes.panel.body', options, props );
};

export const enhanceDropdownOptions = ( option, args ) => {
	return applyFilters( 'srfm.blocks.dropdown.options.enhance', option, args );
};

export const enhanceMultiChoiceOptions = ( option, args ) => {
	return applyFilters(
		'srfm.blocks.multichoice.options.enhance',
		option,
		args
	);
};

export const submitButtonContainerOptions = ( options, args ) => {
	return applyFilters( 'srfm.submit.button.container.options', options, args );
};
