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
