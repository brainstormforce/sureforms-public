import { applyFilters } from '@wordpress/hooks';

export const getInstantFormAdditionalSettings = ( settings, args ) => {
	return applyFilters(
		'srfm.instant_form_settings.additional.settings',
		settings,
		args
	);
};

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
 * Filters the submit button with the provided filter.
 *
 * @param {Object} submitButton - The submit button to filter.
 * @param {Object} args         - Additional arguments for the filter.
 * @return {Object} - The filtered submit button.
 */
export const filterSubmitButton = ( submitButton, args ) => {
	return applyFilters( 'srfm.submit.button', submitButton, args );
};

/**
 * Filters the style panels by allowing additional panels or options to be added.
 *
 * @param {Array}  panels - The existing style panel configuration.
 * @param {Object} args   - Additional arguments for the filter.
 * @return {Array} - The filtered style panels.
 */
export const getStylePanels = ( panels, args ) => {
	return applyFilters( 'srfm.style.panels', panels, args );
};
