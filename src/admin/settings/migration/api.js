/**
 * REST client for the form-migration tab.
 *
 * Thin wrapper around `@wordpress/api-fetch` for the three SureForms migrator
 * endpoints registered in `inc/migrator/bootstrap.php`. The REST nonce is
 * attached automatically by WordPress's apiFetch middleware.
 *
 * @since x.x.x
 */

import apiFetch from '@wordpress/api-fetch';

const BASE = '/sureforms/v1/migrator';

/**
 * Fetch the list of importable source plugins.
 *
 * @return {Promise<{sources: Array<{key: string, title: string, installed: boolean, form_count: number}>}>} Resolves with the registered source list.
 */
export const listSources = () =>
	apiFetch( { path: `${ BASE }/sources` } );

/**
 * Fetch the list of forms in one source plugin.
 *
 * @param {string} key Source key (e.g. 'cf7').
 * @return {Promise<{forms: Array<{id: number|string, name: string, imported_srfm_id: number}>}>} Resolves with the forms inside the source.
 */
export const listForms = ( key ) =>
	apiFetch( { path: `${ BASE }/sources/${ encodeURIComponent( key ) }/forms` } );

/**
 * Import (or dry-run) selected forms from one source.
 *
 * @param {string}               key     Source key.
 * @param {Array<number|string>} formIds Source form ids to import. Empty = all.
 * @param {boolean}              dryRun  If true, no posts are inserted; preview is returned.
 * @return {Promise<{imported: Array<{srfm_id: number, source_id: number|string, name: string, edit_url: string}>, failed: Array<string>, unsupported_fields: Array<string>, preview?: Record<string, string>}>} Resolves with the import outcome.
 */
export const importForms = ( key, formIds, dryRun = false ) =>
	apiFetch( {
		path: `${ BASE }/sources/${ encodeURIComponent( key ) }/import`,
		method: 'POST',
		data: {
			form_ids: formIds,
			dry_run: dryRun,
		},
	} );
