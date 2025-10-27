import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

/**
 * Export forms as JSON file
 *
 * @param {number|Array<number>} formIds  - Form ID(s) to export
 * @param {string}               filename - Optional custom filename
 * @return {Promise<void>}
 */
export const exportForms = async ( formIds, filename = null ) => {
	try {
		// Ensure formIds is an array
		const ids = Array.isArray( formIds ) ? formIds : [ formIds ];

		const response = await apiFetch( {
			path: '/sureforms/v1/forms/export',
			method: 'POST',
			data: {
				post_ids: ids,
			},
		} );

		// Create and trigger download
		if ( response.success && response.data ) {
			const blob = new Blob(
				[ JSON.stringify( response.data, null, 2 ) ],
				{
					type: 'application/json',
				}
			);
			const url = window.URL.createObjectURL( blob );
			const link = document.createElement( 'a' );
			link.href = url;

			// Generate filename if not provided
			if ( ! filename ) {
				filename = 'sureforms-export-form.json';
			}

			link.download = filename;
			document.body.appendChild( link );
			link.click();
			document.body.removeChild( link );
			window.URL.revokeObjectURL( url );
		} else {
			throw new Error(
				__( 'Export failed - No data received', 'sureforms' )
			);
		}
	} catch ( error ) {
		console.error( 'Export error:', error );
		throw error;
	}
};
