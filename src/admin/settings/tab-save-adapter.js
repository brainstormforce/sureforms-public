import { useEffect } from '@wordpress/element';
import { dispatch } from '@wordpress/data';
// Side-effect import: ensures the `sureforms` store is registered in the
// shared `wp.data` registry before this hook runs, even when imported
// from a separate webpack entry (e.g. pro bundles).
import '../../store/store';
import { STORE_NAME as SRFM_STORE_NAME } from '../../store/constants';

// Cross-bundle handler registry. Pro plugins ship in a separate webpack
// bundle; both bundles reach the same `wp.data` store, but module-scope
// JS state is per-bundle. Hoisting the map onto `window` lets free's
// `Component.js` invoke a save handler registered from pro's bundle.
// Snake_case namespace matches the repo's existing `window.__srfm_*`
// globals (e.g. `__srfm_google_maps_filter_registered`).
const handlers =
	( typeof window !== 'undefined' && window.__srfm_tab_save_handlers ) ||
	new Map();
if ( typeof window !== 'undefined' && ! window.__srfm_tab_save_handlers ) {
	window.__srfm_tab_save_handlers = handlers;
}

/**
 * Hook for tab components that own their own save logic (e.g. pro
 * Google Maps, pro User Registration). Registers an entry in the
 * sureforms store's `tabAdapters` slice (for free's header Save button
 * to read `isDirty` / `isSaving` from) and registers `onSave` in the
 * cross-bundle handler map (so the header Save click reaches the tab).
 *
 * @param {string}   slug             Tab slug (matches the path used by Component.js).
 * @param {Object}   options          Adapter state + save handler.
 * @param {boolean}  options.isDirty  Whether this tab has unsaved changes.
 * @param {boolean}  options.isSaving Whether this tab is currently saving.
 * @param {Function} options.onSave   Click handler for the header Save button.
 */
export const useGlobalSettingsTabSaveAdapter = (
	slug,
	{ isDirty, isSaving, onSave }
) => {
	// Register save handler in the cross-bundle map. We dispatch
	// imperatively (not via `useDispatch`) because `useDispatch` can
	// resolve to `null` if pro mounts before free finishes registering
	// the store. The side-effect import above ensures the store IS
	// registered by the time this code runs.
	useEffect( () => {
		handlers.set( slug, onSave );
		return () => {
			handlers.delete( slug );
		};
	}, [ slug, onSave ] );

	useEffect( () => {
		const srfmDispatch = dispatch( SRFM_STORE_NAME );
		srfmDispatch?.setGlobalSettingsTabAdapter?.( slug, {
			isDirty,
			isSaving,
		} );
	}, [ slug, isDirty, isSaving ] );

	useEffect( () => {
		return () => {
			const srfmDispatch = dispatch( SRFM_STORE_NAME );
			srfmDispatch?.removeGlobalSettingsTabAdapter?.( slug );
		};
	}, [ slug ] );
};

/**
 * Look up the registered save handler for a tab slug. Returns null when
 * no adapter is registered.
 *
 * @param {string} slug Tab slug.
 * @return {Function|null} Registered save handler or null.
 */
export const getTabSaveHandler = ( slug ) => handlers.get( slug ) || null;
