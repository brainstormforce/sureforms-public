import { __ } from '@wordpress/i18n';

export const STATUS_OPTIONS = [
	{ value: 'all', label: __( 'All Status', 'sureforms' ) },
	{ value: 'read', label: __( 'Read', 'sureforms' ) },
	{ value: 'unread', label: __( 'Unread', 'sureforms' ) },
	{ value: 'trash', label: __( 'Trash', 'sureforms' ) },
];

export const FORM_OPTIONS = [
	{ value: 'all', label: __( 'All Forms', 'sureforms' ) },
	{ value: 'contact-us', label: __( 'Contact Us', 'sureforms' ) },
	{ value: 'newsletter', label: __( 'Newsletter', 'sureforms' ) },
	{
		value: 'job-application',
		label: __( 'Job Application Form', 'sureforms' ),
	},
	{
		value: 'support-request',
		label: __( 'Support Request', 'sureforms' ),
	},
];

export const ENTRIES_PER_PAGE_OPTIONS = [
	{ value: 10, label: '10' },
	{ value: 25, label: '25' },
	{ value: 50, label: '50' },
	{ value: 100, label: '100' },
];

export const TABLE_HEADERS = [
	{
		label: __( 'Form ID', 'sureforms' ),
		key: 'formId',
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
