import { __ } from '@wordpress/i18n';

/**
 * Hook to get smart tags based on context.
 *
 * Form context includes form-specific tags (from form fields).
 * Global context only includes generic tags.
 *
 * @param {string} context - 'form' | 'global'
 * @return {Object} Smart tags organized by category
 */
export const useEmailSmartTags = ( context ) => {
	// srfm_block_data is available in the block editor (form settings).
	// srfm_admin is available on the global settings page.
	// Fall back to srfm_admin so generic tags work in both contexts.
	const blockData = window.srfm_block_data || {};
	const adminData = window.srfm_admin || {};

	// Generic smart tags available in both contexts.
	const rawSmartTags =
		blockData.smart_tags_array || adminData.smart_tags_array;
	const genericSmartTags = rawSmartTags ? Object.entries( rawSmartTags ) : [];

	const rawEmailSmartTags =
		blockData.smart_tags_array_email || adminData.smart_tags_array_email;
	const genericEmailSmartTags = rawEmailSmartTags
		? Object.entries( rawEmailSmartTags )
		: [];

	// Global context: only generic tags.
	if ( context === 'global' ) {
		return {
			smartTags: [
				{
					tags: genericSmartTags,
					label: __( 'Generic tags', 'sureforms' ),
				},
			],
			emailSmartTags: [
				{
					tags: genericEmailSmartTags,
					label: __( 'Generic tags', 'sureforms' ),
				},
			],
			formSmartTags: [],
			formEmailSmartTags: [],
		};
	}

	// Form context: includes form-specific tags.
	const formSmartTags = window.sureforms?.formSpecificSmartTags ?? [];
	const formEmailSmartTags =
		window.sureforms?.formSpecificEmailSmartTags ?? [];

	return {
		smartTags: [
			{
				tags: formSmartTags,
				label: __( 'Form input tags', 'sureforms' ),
			},
			{
				tags: genericSmartTags,
				label: __( 'Generic tags', 'sureforms' ),
			},
		],
		emailSmartTags: [
			{
				tags: formEmailSmartTags,
				label: __( 'Form input tags', 'sureforms' ),
			},
			{
				tags: genericEmailSmartTags,
				label: __( 'Generic tags', 'sureforms' ),
			},
		],
		formSmartTags,
		formEmailSmartTags,
	};
};
