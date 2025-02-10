import { applyFilters } from '@wordpress/hooks';

export const getInstantFormAdditionalSettings = ( settings, args ) => {
	return applyFilters(
		'srfm.instant_form_settings.additional.settings',
		settings,
		args
	);
};

export const formPresetAccordion = ( panel, args ) => {
	return applyFilters( 'srfm.form_styling.preset.accordion', panel, args );
};

/**
 * Filters the submit button container options with the provided filter.
 *
 * @param {Array}  options - The submit button container options to filter.
 * @param {Object} args    - Additional arguments for the filter.
 * @return {Array} - The filtered submit button container options.
 */
export const submitButtonContainerStyles = ( options, args ) => {
	return applyFilters(
		'srfm.submit.button.container.styles',
		options,
		args
	);
};