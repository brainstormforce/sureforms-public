import { __ } from '@wordpress/i18n';

const gutterOptions = [
	{
		value: '0',
		label: __( 'None', 'sureforms' ),
		shortName: __( 'None', 'sureforms' ),
	},
	{
		value: '5',
		/* translators: abbreviation for small size */
		label: __( 'S', 'sureforms' ),
		tooltip: __( 'Small', 'sureforms' ),
	},
	{
		value: '10',
		/* translators: abbreviation for medium size */
		label: __( 'M', 'sureforms' ),
		tooltip: __( 'Medium', 'sureforms' ),
	},
	{
		value: '15',
		/* translators: abbreviation for large size */
		label: __( 'L', 'sureforms' ),
		tooltip: __( 'Large', 'sureforms' ),
	},
	{
		value: '20',
		/* translators: abbreviation for largest size */
		label: __( 'XL', 'sureforms' ),
		tooltip: __( 'Huge', 'sureforms' ),
	},
];

export default gutterOptions;
