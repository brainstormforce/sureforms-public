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
 * `form_count` is the source plugin's total form count; `imported_count` is
 * how many of those forms are already mapped to a SureForms post via the
 * idempotency option; `pending_count = form_count - imported_count` is the
 * actionable number the onboarding picker uses to decide whether a card is
 * enabled. The Forms-listing banner and the picker hide themselves entirely
 * when every source reports `pending_count: 0`.
 *
 * @return {Promise<{sources: Array<{key: string, title: string, installed: boolean, form_count: number, imported_count: number, pending_count: number}>}>} Resolves with the registered source list.
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
 * @param {string}                        key          Source key.
 * @param {Array<number|string>}          formIds      Source form ids to import. Empty = all.
 * @param {boolean}                       dryRun       If true, no posts are inserted; preview is returned.
 * @param {Object<string|number, string>} behavior     Optional per-source re-import behavior - keyed by source form id, value is one of `update`, `skip`, `create`. Defaults to `update` when omitted.
 * @param {string}                        postStatus   Status for newly created forms; `draft` (default) or `publish`. Imported forms land as drafts so the user can review the migrated markup before publishing.
 * @param {boolean}                       skipExisting When true, any source form already mapped to a SureForms post is skipped instead of overwritten. Per-id `behavior` entries still win. The onboarding step passes `true` so its "Import all" never silently overwrites previously-imported forms.
 * @return {Promise<{imported: Array<{srfm_id: number, source_id: number|string, name: string, edit_url: string}>, failed: Array<string>, skipped: Array<{srfm_id: number, source_id: number|string, name: string, edit_url: string}>, unsupported_fields: Array<string>, preview?: Record<string, string>}>} Resolves with the import outcome.
 */
export const importForms = (
	key,
	formIds,
	dryRun = false,
	behavior = {},
	postStatus = 'draft',
	skipExisting = false
) =>
	apiFetch( {
		path: `${ BASE }/sources/${ encodeURIComponent( key ) }/import`,
		method: 'POST',
		data: {
			form_ids: formIds,
			dry_run: dryRun,
			behavior,
			post_status: postStatus,
			skip_existing: skipExisting,
		},
	} );

/**
 * Persist the current user's dismissal of the Forms-listing migration banner.
 *
 * @return {Promise<{dismissed: boolean}>} Resolves once the preference is stored.
 */
export const dismissMigrationBanner = () =>
	apiFetch( {
		path: '/sureforms/v1/user-meta/dismiss-migration-banner',
		method: 'POST',
	} );
