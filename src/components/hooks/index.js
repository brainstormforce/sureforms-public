import { applyFilters } from '@wordpress/hooks';

export const getInstantFormAdditionalSettings = ( settings, args ) => {
	return applyFilters(
		'srfm.instant_form_settings.additional.settings',
		settings,
		args
	);
};

export const getFormStylingOptions = ( options ) => {
	return applyFilters( 'srfm.form_styling.options', options );
};
