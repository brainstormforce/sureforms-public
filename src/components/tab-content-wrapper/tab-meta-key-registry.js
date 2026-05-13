import { applyFilters } from '@wordpress/hooks';

/**
 * Free-side map of dialog tab id → the post meta keys that tab owns.
 * Pro tabs append entries via the `srfm.formSettings.tabMetaKeys` filter.
 *
 * Used by `TabContentWrapper.handleSave` to build the REST POST payload
 * scoped to a single tab without dirtying meta the user didn't touch.
 *
 * @type {Object<string, string[]>}
 */
export const TAB_META_KEYS = {
	email_notification: [ '_srfm_email_notification' ],
	form_confirmation: [ '_srfm_form_confirmation' ],
	spam_protection: [
		'_srfm_captcha_security_type',
		'_srfm_form_recaptcha',
	],
	form_custom_css: [ '_srfm_form_custom_css' ],
	'advanced-settings': [ '_srfm_form_restriction', '_srfm_compliance' ],
};

/**
 * Resolve a tab's owned meta keys, applying the pro extension filter.
 *
 * @param {string} tabId Tab identifier (matches the `tabId` prop on TabContentWrapper).
 * @return {string[]} Meta keys for this tab; empty array if unknown.
 */
export const getTabMetaKeys = ( tabId ) => {
	const map = applyFilters( 'srfm.formSettings.tabMetaKeys', TAB_META_KEYS );
	return Array.isArray( map?.[ tabId ] ) ? map[ tabId ] : [];
};
