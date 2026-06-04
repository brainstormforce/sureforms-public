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
 * @param {string}                        key      Source key.
 * @param {Array<number|string>}          formIds  Source form ids to import. Empty = all.
 * @param {boolean}                       dryRun   If true, no posts are inserted; preview is returned.
 * @param {Object<string|number, string>} behavior Optional per-source re-import behavior — keyed by source form id, value is one of `update`, `skip`, `create`. Defaults to `update` when omitted.
 * @param {string}                        postStatus Status for newly created forms; `draft` (default) or `publish`. Imported forms land as drafts so the user can review the migrated markup before publishing.
 * @return {Promise<{imported: Array<{srfm_id: number, source_id: number|string, name: string, edit_url: string}>, failed: Array<string>, skipped: Array<{srfm_id: number, source_id: number|string, name: string, edit_url: string}>, unsupported_fields: Array<string>, preview?: Record<string, string>}>} Resolves with the import outcome.
 */
export const importForms = (
	key,
	formIds,
	dryRun = false,
	behavior = {},
	postStatus = 'draft'
) =>
	apiFetch( {
		path: `${ BASE }/sources/${ encodeURIComponent( key ) }/import`,
		method: 'POST',
		data: {
			form_ids: formIds,
			dry_run: dryRun,
			behavior,
			post_status: postStatus,
		},
	} );
