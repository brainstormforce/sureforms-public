import { __ } from '@wordpress/i18n';

/**
 * Confirmation type options for the radio group.
 * Shared between GlobalDefaults and FormConfirmation pages.
 */
export const CONFIRMATION_TYPE_OPTIONS = [
	{
		label: __( 'Success Message', 'sureforms' ),
		value: 'same page',
	},
	{
		label: __( 'Redirect', 'sureforms' ),
		value: 'different page',
	},
];

/**
 * Redirect sub-options when "Redirect" is selected.
 * Shared between GlobalDefaults and FormConfirmation pages.
 */
export const REDIRECT_SUB_OPTIONS = [
	{
		label: __( 'Page', 'sureforms' ),
		value: 'different page',
	},
	{
		label: __( 'Custom URL', 'sureforms' ),
		value: 'custom url',
	},
];
