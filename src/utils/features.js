/**
 * Whether a site-wide feature is on (Settings → Features).
 *
 * Reads `window.srfmIsFeatureEnabled`, printed by Admin::localize_features()
 * so SureForms Pro bundles share the same check. A key that is not listed reads as on.
 *
 * @param {string} key Feature key, e.g. 'payments'.
 * @return {boolean} Whether the feature is on.
 */
export const isFeatureEnabled = ( key ) =>
	typeof window.srfmIsFeatureEnabled === 'function'
		? window.srfmIsFeatureEnabled( key )
		: true;

/**
 * Drop the items whose `feature` is switched off. Items without a `feature`
 * key are kept, so this only hides what a feature owns, its upsells included.
 * `feature` may list several keys, and the item is kept only while all are on:
 * a Pro upsell names its own key, which is not registered without Pro, plus the
 * free feature it needs (e.g. `[ 'pdf', 'entries' ]`).
 *
 * @param {Array<Object>} items Tabs, nav items or options.
 * @return {Array<Object>} The items that belong to features that are on.
 */
export const withEnabledFeatures = ( items ) =>
	items.filter(
		( item ) =>
			! item.feature ||
			[].concat( item.feature ).every( ( key ) => isFeatureEnabled( key ) )
	);
