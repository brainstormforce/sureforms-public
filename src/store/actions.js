/**
 * Actions for the SureForms store.
 */

/**
 * Update the global state with new data.
 *
 * @param {Object} payload - The new global data to be set.
 * @return {Object} Action object to update the global state.
 */
export const updateSrfmGlobalData = ( payload ) => {
	if ( ! payload || typeof payload !== 'object' ) {
		throw new Error( 'Invalid payload: must be an object.' );
	}

	return {
		type: 'UPDATE_SRFM_GLOBAL_DATA',
		payload,
	};
};

/**
 * Stage a single form-settings meta key/value into `formSettings.values`.
 * Source of truth for the dialog's per-tab Save payload — `srfmEditFormMeta`
 * funnels every staged write here so the Save button reads the same bytes
 * the user changed (not whatever `core/editor` happens to hold).
 *
 * @param {string} metaKey Post meta key, e.g. `_srfm_email_notification`.
 * @param {*}      value   New value; caller decides shape.
 * @return {Object} Action object.
 */
export const setFormSettingValue = ( metaKey, value ) => ( {
	type: 'SRFM_FORM_SETTINGS_SET_VALUE',
	metaKey,
	value,
} );

/**
 * Commit a server-sanitized response into `formSettings`. Writes both
 * `baseline` and `values` so dirty diffs immediately fall to false on a
 * successful save round-trip.
 *
 * @param {Object} values `{ [metaKey]: value }` from the REST response.
 * @return {Object} Action object.
 */
export const commitSavedMeta = ( values ) => ( {
	type: 'SRFM_FORM_SETTINGS_COMMIT_SAVED_META',
	values: values || {},
} );

/**
 * Atomic POST-failure revert. Replaces the 2-step
 * `commitSavedMeta(prevBaselines) → srfmEditFormMeta(prevValue)` dance
 * with a single dispatch so subscribers comparing `value` vs `baseline`
 * never see the brief window where the two were equal (which would
 * flip a Redux-baseline-derived `isDirty` selector through false →
 * true and trigger any subscribed effects).
 *
 * Each entry in `entries` carries the per-key pre-save baseline and
 * value to restore — both written in one reducer hop.
 *
 * @param {Object} entries `{ [metaKey]: { baseline, value } }`.
 * @return {Object} Action object.
 */
export const revertOnSaveFailure = ( entries ) => ( {
	type: 'SRFM_FORM_SETTINGS_REVERT_ON_FAILURE',
	entries: entries || {},
} );

/**
 * Reset `formSettings.values` back to `formSettings.baseline`. Dispatched
 * by the dialog's "Discard & continue" branch to wipe in-flight edits
 * before navigating to a different tab or closing the dialog.
 *
 * @return {Object} Action object.
 */
export const discardFormSettingValues = () => ( {
	type: 'SRFM_FORM_SETTINGS_DISCARD_VALUES',
} );

/**
 * Tear the entire `formSettings` slice down. Dispatched on dialog close
 * so the next mount starts from a clean slate.
 *
 * @return {Object} Action object.
 */
export const discardFormSettingsState = () => ( {
	type: 'SRFM_FORM_SETTINGS_DISCARD',
} );

/**
 * Push the active sub-form's local dirty signal into Redux so the
 * dialog's tab-switch / close guard can read it without holding a
 * reference to the tab component.
 *
 * @param {boolean} unsaved Whether the active sub-form has unsaved edits.
 * @return {Object} Action object.
 */
export const setSingleFormSettingUnsave = ( unsaved ) => ( {
	type: 'SRFM_SINGLE_FORM_SETTING_SET_UNSAVE',
	unsaved,
} );

/**
 * Bump the discard counter. Tabs subscribe to
 * `selectSingleFormSettingDiscardCounter` and reset their local React
 * state when it changes.
 *
 * @return {Object} Action object.
 */
export const discardChanges = () => ( {
	type: 'SRFM_SINGLE_FORM_SETTING_DISCARD',
} );

/**
 * Push the active sub-form's in-flight Save signal into Redux. The
 * dialog's tab-switch / close / Esc / backdrop guards read this and
 * block the interception while a POST is mid-flight — without the
 * lock, a "Discard & continue" click during the await would revert
 * local React state even though the server has already accepted the
 * save.
 *
 * `TabContentWrapper.handleSave` toggles this around its await; tabs
 * with their own custom save flow (none today) would need to do the
 * same if they want the guard to respect their in-flight window.
 *
 * @param {boolean} saving Whether a save POST is currently in flight.
 * @return {Object} Action object.
 */
export const setSingleFormSettingSaving = ( saving ) => ( {
	type: 'SRFM_SINGLE_FORM_SETTING_SET_SAVING',
	saving: !! saving,
} );

/**
 * Record a single panel's dirty contribution under its own slot in
 * `formSettings.tabDirtyContributions`. Used by sibling panels that
 * can't reach the tab owner's local React state directly (e.g.
 * Compliance Settings is a `Dialog.js`-level sibling of the Form
 * Restriction panel but should still drive the same Save button).
 *
 * The aggregate `selectTabDirtyContributionsAggregate` returns true
 * if any slot is dirty — the tab owner ORs it into its own
 * `isDirty` prop on TabContentWrapper.
 *
 * Slots auto-clean on consumer unmount via a `setTabDirtyContribution(
 * slot, false )` in the effect cleanup, so a tab switch / discard
 * doesn't leave a stale `true` in the map.
 *
 * @param {string}  slot  Stable slot id (e.g. `'compliance'`).
 * @param {boolean} dirty Latest dirty boolean for this slot.
 * @return {Object} Action object.
 */
export const setTabDirtyContribution = ( slot, dirty ) => ( {
	type: 'SRFM_FORM_SETTINGS_SET_TAB_DIRTY_CONTRIBUTION',
	slot,
	dirty: !! dirty,
} );

/**
 * No-op marker for the popup's "Stay" branch. Kept for symmetry with
 * `discardChanges` so the dialog has a single hook to extend later.
 *
 * @return {Object} Action object.
 */
export const keepChanges = () => ( {
	type: 'SRFM_SINGLE_FORM_SETTING_KEEP',
} );

/**
 * Open the dialog-level back-arrow discard modal with caller-supplied
 * copy. The modal is rendered by `Dialog.js`; the active tab subscribes
 * to `selectBackDiscardConfirmCounter` to fire its own
 * `handleBackNotification` / equivalent when the user confirms.
 *
 * @param {Object} payload             Copy for the confirmation modal.
 * @param {string} payload.title       Modal title.
 * @param {string} payload.description Modal body copy.
 * @param {string} payload.confirmText Confirm button label (destructive).
 * @param {string} payload.cancelText  Cancel button label.
 * @return {Object} Action object.
 */
export const requestBackDiscard = ( payload ) => ( {
	type: 'SRFM_FORM_SETTINGS_REQUEST_BACK_DISCARD',
	payload: payload || {},
} );

/**
 * Confirm branch of the back-arrow discard modal. Clears the pending
 * payload AND bumps `backDiscardConfirmCounter` so the tab that opened
 * the modal can react.
 *
 * @return {Object} Action object.
 */
export const confirmBackDiscard = () => ( {
	type: 'SRFM_FORM_SETTINGS_CONFIRM_BACK_DISCARD',
} );

/**
 * Cancel branch of the back-arrow discard modal. Clears the pending
 * payload without bumping the counter.
 *
 * @return {Object} Action object.
 */
export const cancelBackDiscard = () => ( {
	type: 'SRFM_FORM_SETTINGS_CANCEL_BACK_DISCARD',
} );

/**
 * Queue a toast for the dialog-level Toaster to surface. Consumers call
 * `notify.success/.error(...)` from `src/utils/notify.js` rather than
 * dispatching this directly — the helper wraps this action so non-React
 * call sites (e.g. `srfmSaveFormMeta`) can still emit toasts cleanly.
 *
 * Writes `pendingToast` AND bumps `toastCounter`. Dialog.js subscribes
 * to the counter and dismisses + fires the underlying @bsf/force-ui
 * `toast.success/.error` on every bump.
 *
 * @param {Object} payload            Toast payload.
 * @param {string} payload.type       `'success' | 'error'`.
 * @param {string} payload.message    Toast body copy.
 * @param {number} [payload.duration] Auto-dismiss delay in ms.
 * @return {Object} Action object.
 */
export const requestToast = ( payload ) => ( {
	type: 'SRFM_FORM_SETTINGS_REQUEST_TOAST',
	payload: payload || {},
} );

/**
 * Register / update a tab save adapter. Used by tab components that own
 * their own save logic (e.g. pro tabs) so the global-settings header
 * Save button can route to them.
 *
 * @param {string}  slug             Tab slug.
 * @param {Object}  options          Adapter state.
 * @param {boolean} options.isDirty  Whether this tab has unsaved changes.
 * @param {boolean} options.isSaving Whether this tab is currently saving.
 * @return {Object} Action object.
 */
export const setGlobalSettingsTabAdapter = (
	slug,
	{ isDirty = false, isSaving = false } = {}
) => ( {
	type: 'SRFM_GLOBAL_SETTINGS_SET_TAB_ADAPTER',
	slug,
	isDirty,
	isSaving,
} );

/**
 * Remove a previously-registered tab save adapter (unmount cleanup).
 *
 * @param {string} slug Tab slug.
 * @return {Object} Action object.
 */
export const removeGlobalSettingsTabAdapter = ( slug ) => ( {
	type: 'SRFM_GLOBAL_SETTINGS_REMOVE_TAB_ADAPTER',
	slug,
} );

/**
 * Push the aggregate free-tab dirty signal into Redux. `Component.js`
 * dispatches this on every render so the tab-switch guard / pro tabs
 * can read it without holding a reference to `Component`.
 *
 * @param {boolean} isDirty Aggregate dirty signal.
 * @return {Object} Action object.
 */
export const setGlobalSettingsPageDirty = ( isDirty ) => ( {
	type: 'SRFM_GLOBAL_SETTINGS_SET_PAGE_DIRTY',
	isDirty,
} );

/**
 * Request navigation to a different tab. The guard reads `pendingNav`
 * and surfaces the "Unsaved changes" modal; on confirm it clears
 * `pendingNav` and navigates.
 *
 * @param {string} to Target search string (e.g. `?page=…&tab=…`).
 * @return {Object} Action object.
 */
export const requestGlobalSettingsNavigation = ( to ) => ( {
	type: 'SRFM_GLOBAL_SETTINGS_REQUEST_NAV',
	to,
} );

/**
 * Clear the pending navigation (popup confirmed or cancelled).
 *
 * @return {Object} Action object.
 */
export const clearGlobalSettingsPendingNav = () => ( {
	type: 'SRFM_GLOBAL_SETTINGS_CLEAR_PENDING_NAV',
} );

/**
 * Record a global-settings extension panel's dirty contribution under
 * its own slot in `globalSettings.extensionDirty`. Used by pro sub-
 * sections that render via filter (e.g. the IP / Country / Keyword
 * Defaults under Global Defaults → Form Restrictions) to push their
 * locally-computed `isDirty` boolean up to the host page so the
 * header Save button reflects their edits too.
 *
 * Slots auto-clean on consumer unmount via a
 * `setGlobalSettingsExtensionDirty( slot, false )` in the effect
 * cleanup.
 *
 * @param {string}  slot  Stable slot id (e.g. `'advanced-restrictions'`).
 * @param {boolean} dirty Latest dirty boolean for this slot.
 * @return {Object} Action object.
 */
export const setGlobalSettingsExtensionDirty = ( slot, dirty ) => ( {
	type: 'SRFM_GLOBAL_SETTINGS_SET_EXTENSION_DIRTY',
	slot,
	dirty: !! dirty,
} );

/**
 * Bump the extension-save request counter, scoped to the tab whose
 * header Save button was clicked. Registered extension panels subscribe
 * via `selectGlobalSettingsExtensionSaveCounter` and check
 * `selectGlobalSettingsExtensionSaveTab` so a save on Tab A doesn't
 * trigger an extension registered for Tab B (and so a third-party
 * extension written without an internal `if (isDirty)` guard can't
 * POST empty payloads on every header click).
 *
 * @param {string} [tab] Tab slug whose Save button fired. Optional for
 *                       backward-compat: `null` means "broadcast to all".
 * @return {Object} Action object.
 */
export const requestGlobalSettingsExtensionSave = ( tab = null ) => ( {
	type: 'SRFM_GLOBAL_SETTINGS_REQUEST_EXTENSION_SAVE',
	tab,
} );

/**
 * Record a global-settings extension panel's in-flight `isSaving`
 * boolean under its own slot. Mirrors `setGlobalSettingsExtensionDirty`
 * — the host ORs the aggregate into the header Save button's
 * `isSaving` so the spinner shows even when only an extension panel
 * is mid-POST.
 *
 * @param {string}  slot   Stable slot id matching the dirty contribution.
 * @param {boolean} saving Whether this slot is currently POSTing.
 * @return {Object} Action object.
 */
export const setGlobalSettingsExtensionSaving = ( slot, saving ) => ( {
	type: 'SRFM_GLOBAL_SETTINGS_SET_EXTENSION_SAVING',
	slot,
	saving: !! saving,
} );
