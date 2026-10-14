import apiFetch from '@wordpress/api-fetch';
/**
 * Create a form from a template.
 *
 * @param {string}  templateId                   Template id.
 * @param {Object}  style                        Chosen style, {} for the template's own.
 * @param {Object}  options                      Options.
 * @param {boolean} options.enableSpamProtection Also turn on the site-wide honeypot.
 * @param {Object}  options.messages             Thank-you message and email text changed
 *                                               before creating; only changed values.
 * @return {Promise<Object>} Response with `edit_url`.
 */
export const createFormFromTemplate = (
	templateId,
	style = {},
	{ enableSpamProtection = false, messages } = {}
) => {
	const data = { template_id: templateId };
	if ( Object.keys( style ).length ) {
		data.style = style;
	}
	if ( enableSpamProtection ) {
		data.enable_spam_protection = true;
	}
	if ( messages && Object.keys( messages ).length ) {
		data.messages = messages;
	}
	return apiFetch( {
		path: 'sureforms/v1/form-templates/create',
		method: 'POST',
		data,
	} );
};

export const fetchTemplates = () =>
	apiFetch( { path: 'sureforms/v1/form-templates' } );

export const fetchTemplateContent = ( templateId, formId, style = {} ) =>
	apiFetch( {
		path: `sureforms/v1/form-templates/${ encodeURIComponent(
			templateId
		) }?form_id=${ parseInt( formId, 10 ) || 0 }${
			Object.keys( style ).length
				? `&style=${ encodeURIComponent( JSON.stringify( style ) ) }`
				: ''
		}`,
	} );

export const recordTemplateSwap = ( formId, templateId ) =>
	apiFetch( {
		path: 'sureforms/v1/form-templates/swap',
		method: 'POST',
		data: { form_id: formId, template_id: templateId },
	} );

export const requestTemplate = ( query, type = 'request' ) =>
	apiFetch( {
		path: 'sureforms/v1/form-templates/request',
		method: 'POST',
		data: { query, type },
	} );
