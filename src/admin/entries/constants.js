import { __ } from '@wordpress/i18n';

export const STATUS_OPTIONS = [
	{ value: '', label: __( 'All Status', 'sureforms' ) },
	{ value: 'read', label: __( 'Read', 'sureforms' ) },
	{ value: 'unread', label: __( 'Unread', 'sureforms' ) },
	{ value: 'trash', label: __( 'Trash', 'sureforms' ) },
];

/**
 * Get form options from forms map
 *
 * @param {Object} formsMap - Map of form IDs to form titles
 * @return {Array} Form options array
 */
export const getFormOptions = ( formsMap = {} ) => {
	const options = [ { value: '', label: __( 'All Forms', 'sureforms' ) } ];

	Object.entries( formsMap ).forEach( ( [ id, title ] ) => {
		options.push( {
			value: id,
			label: title,
		} );
	} );

	return options;
};

export const ENTRIES_PER_PAGE_OPTIONS = [ 10, 25, 50, 100 ];

export const TABLE_HEADERS = [
	{
		label: __( 'Entry ID', 'sureforms' ),
		key: 'id',
		sortable: true,
	},
	{
		label: __( 'Form Name', 'sureforms' ),
		key: 'formName',
	},
	{
		label: __( 'Status', 'sureforms' ),
		key: 'status',
		sortable: true,
	},
	{
		label: __( 'First Field', 'sureforms' ),
		key: 'firstField',
	},
	{
		label: __( 'Date & Time', 'sureforms' ),
		key: 'dateTime',
		sortable: true,
	},
	{
		label: __( 'Actions', 'sureforms' ),
		key: 'actions',
		align: 'right',
	},
];
