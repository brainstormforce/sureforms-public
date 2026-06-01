/*
 * Selectors for the SureForms store.
 */

export const selectSrfmGlobalData = ( state ) => state.srfmGlobalData;

export const selectFormSettingValue = ( state, metaKey ) =>
	state.formSettings?.values?.[ metaKey ];

export const selectFormSettingBaselineValue = ( state, metaKey ) =>
	state.formSettings?.baseline?.[ metaKey ];

export const selectSingleFormSettingUnsave = ( state ) =>
	!! state.formSettings?.singleFormSettingUnsave;

export const selectSingleFormSettingSaving = ( state ) =>
	!! state.formSettings?.singleFormSettingSaving;

export const selectSingleFormSettingDiscardCounter = ( state ) =>
	state.formSettings?.discardCounter || 0;

export const selectPendingBackDiscard = ( state ) =>
	state.formSettings?.pendingBackDiscard || null;

export const selectBackDiscardConfirmCounter = ( state ) =>
	state.formSettings?.backDiscardConfirmCounter || 0;

export const selectPendingToast = ( state ) =>
	state.formSettings?.pendingToast || null;

export const selectToastCounter = ( state ) =>
	state.formSettings?.toastCounter || 0;

/**
 * Aggregate per-slot dirty contributions into a single boolean — true
 * if any sibling panel that registered a slot has reported dirty.
 *
 * Used by the tab owner (e.g. `FormRestriction`) to OR-fold sibling
 * panels' dirty signals into the Save button's `isDirty` prop without
 * those siblings needing to live inside the owner's render tree.
 *
 * @param {Object} state Store state.
 * @return {boolean} True if any slot is dirty.
 */
export const selectTabDirtyContributionsAggregate = ( state ) => {
	const map = state.formSettings?.tabDirtyContributions || {};
	return Object.values( map ).some( Boolean );
};

export const selectGlobalSettingsTabAdapter = ( state, slug ) =>
	state.globalSettings?.tabAdapters?.[ slug ] || null;

export const selectAnyGlobalSettingsTabAdapterDirty = ( state ) => {
	const map = state.globalSettings?.tabAdapters || {};
	return Object.values( map ).some( ( a ) => !! a?.isDirty );
};

export const selectGlobalSettingsPageDirty = ( state ) =>
	!! state.globalSettings?.pageDirty;

export const selectGlobalSettingsPendingNav = ( state ) =>
	state.globalSettings?.pendingNav || null;

/**
 * Aggregate per-slot dirty contributions from global-settings extension
 * panels (filter-driven sub-sections) into a single boolean. Used by
 * the host page's header Save button to OR-fold extension dirty into
 * its own dirty signal.
 *
 * @param {Object} state Store state.
 * @return {boolean} True if any slot is dirty.
 */
export const selectGlobalSettingsExtensionsDirtyAggregate = ( state ) => {
	const map = state.globalSettings?.extensionDirty || {};
	return Object.values( map ).some( Boolean );
};

/**
 * Read the extension-save request counter. Extension panels subscribe
 * to this and fire their own POSTs on each bump (the host dispatches
 * `requestGlobalSettingsExtensionSave` from its Save click handler).
 *
 * @param {Object} state Store state.
 * @return {number} Monotonic counter, starts at 0.
 */
export const selectGlobalSettingsExtensionSaveCounter = ( state ) =>
	state.globalSettings?.extensionSaveCounter || 0;

/**
 * Read the tab slug attached to the latest extension-save bump.
 * Extensions read this alongside the counter and skip their POST when
 * the slug doesn't match their host tab. `null` means the host did not
 * scope the request (broadcast).
 *
 * @param {Object} state Store state.
 * @return {string|null} Tab slug or null.
 */
export const selectGlobalSettingsExtensionSaveTab = ( state ) =>
	state.globalSettings?.extensionSaveTab || null;

/**
 * Aggregate per-slot `isSaving` contributions from global-settings
 * extension panels. Mirrors
 * `selectGlobalSettingsExtensionsDirtyAggregate`. Host ORs into the
 * header Save button's `isSaving` so the spinner / "Saving…" label
 * surfaces even when only an extension panel is mid-POST.
 *
 * @param {Object} state Store state.
 * @return {boolean} True if any slot is currently saving.
 */
export const selectGlobalSettingsExtensionsSavingAggregate = ( state ) => {
	const map = state.globalSettings?.extensionSaving || {};
	return Object.values( map ).some( Boolean );
};
