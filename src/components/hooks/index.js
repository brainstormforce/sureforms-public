import { applyFilters } from '@wordpress/hooks';

/**
 * Filters the attribute options with the provided filter.
 *
 * @param {Array}  options - The attribute options to filter.
 * @param {Object} props   - Additional properties for the filter.
 * @return {Array} - The filtered attribute options.
 */
export const attributeOptionsWithFilter = ( options, props ) => {
	return applyFilters( 'srfm.block.attributes.panel.body', options, props );
};

/**
 * Enhances the dropdown options with the provided filter.
 *
 * @param {Object} option - The dropdown option to enhance.
 * @param {Object} args   - Additional arguments for the filter.
 * @return {Object} - The enhanced dropdown option.
 */
export const enhanceDropdownOptions = ( option, args ) => {
	return applyFilters( 'srfm.blocks.dropdown.options.enhance', option, args );
};

/**
 * Enhances the multi-choice options with the provided filter.
 *
 * @param {Object} option - The multi-choice option to enhance.
 * @param {Object} args   - Additional arguments for the filter.
 * @return {Object} - The enhanced multi-choice option.
 */
export const enhanceMultiChoiceOptions = ( option, args ) => {
	return applyFilters(
		'srfm.blocks.multichoice.options.enhance',
		option,
		args
	);
};

/**
 * Filters the submit button container options with the provided filter.
 *
 * @param {Array}  options - The submit button container options to filter.
 * @param {Object} args    - Additional arguments for the filter.
 * @return {Array} - The filtered submit button container options.
 */
export const submitButtonContainerOptions = ( options, args ) => {
	return applyFilters(
		'srfm.submit.button.container.options',
		options,
		args
	);
};

/**
 * Filters the submit button with the provided filter.
 *
 * @param {Object} submitButton - The submit button to filter.
 * @param {Object} args         - Additional arguments for the filter.
 * @return {Object} - The filtered submit button.
 */
export const filterSubmitButton = ( submitButton, args ) => {
	return applyFilters( 'srfm.submit.button', submitButton, args );
};
