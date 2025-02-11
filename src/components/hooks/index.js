import { applyFilters } from '@wordpress/hooks';

export const getInstantFormAdditionalSettings = ( settings, args ) => {
	return applyFilters(
		'srfm.instant_form_settings.additional.settings',
		settings,
		args
	);
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
