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

export const requestTemplate = ( query ) =>
	apiFetch( {
		path: 'sureforms/v1/form-templates/request',
		method: 'POST',
		data: { query, type: 'request' },
	} );

/**
 * Count a search that found no template. Only the count is shared, never
 * the search text.
 *
 * @since x.x.x
 * @return {Promise<Object>} Response.
 */
export const recordEmptySearch = () =>
	apiFetch( {
		path: 'sureforms/v1/form-templates/request',
		method: 'POST',
		data: { type: 'search' },
	} );

/**
 * The template settings to apply to an open form.
 *
 * Only settings the editor knows about are applied. The form's messages
 * (`messageKeys`: emails, thank-you message, button text, step labels) are
 * left alone unless the user chose to replace them too.
 *
 * @since x.x.x
 * @param {Object}   settings        `settings` from the template content.
 * @param {Object}   meta            The open form's meta.
 * @param {string[]} messageKeys     `message_meta_keys` from the template list.
 * @param {boolean}  replaceMessages Also replace the form's messages.
 * @return {Object} Settings to apply, by meta key.
 */
export const pickTemplateSettings = (
	settings,
	meta,
	messageKeys,
	replaceMessages
) =>
	Object.fromEntries(
		Object.entries( settings || {} ).filter(
			( [ key ] ) =>
				Object.prototype.hasOwnProperty.call( meta || {}, key ) &&
				( replaceMessages || ! ( messageKeys || [] ).includes( key ) )
		)
	);
