/**
 * Reducer for managing global state in SureForms.
 */

const defaultState = {
	srfmGlobalData: {},
	// Single-form-settings dialog state.
	formSettings: {
		// Per-meta-key server snapshot. `TabContentWrapper.handleSave`
		// snapshots this pre-save and reverts to it on POST failure.
		baseline: {},
		// Per-meta-key in-flight staged edits. `srfmEditFormMeta` writes
		// here; the save payload is built by reading from this slice.
		values: {},
		// Active sub-form dirty flag — pushed by the tab via
		// `setSingleFormSettingUnsave`. The dialog's tab-switch / close
		// guard reads this.
		singleFormSettingUnsave: false,
		// Active sub-form in-flight Save flag — pushed by
		// `TabContentWrapper.handleSave` around its await. The dialog's
		// guards read this and block tab-switch / close while a POST is
		// pending so a mid-await "Discard" can't revert local state for
		// a save the server has already accepted.
		singleFormSettingSaving: false,
		// Incremented by `discardChanges`. Tabs subscribe and reset their
		// local React state when it bumps.
		discardCounter: 0,
		// Copy for the dialog-level back-arrow discard modal. Set by the
		// active tab via `requestBackDiscard`, cleared on confirm/cancel.
		// Shape: `{ title, description, confirmText, cancelText }`.
		pendingBackDiscard: null,
		// Incremented by `confirmBackDiscard`. Tabs subscribe to fire
		// their own back-navigation callback when the user confirms.
		backDiscardConfirmCounter: 0,
		// Latest toast payload — Dialog.js subscribes to the counter
		// below and fires @bsf/force-ui's `toast.success/.error` for
		// each new value. Copy-only; rendering happens at the dialog.
		pendingToast: null,
		// Incremented on every `requestToast`. Counter pattern so the
		// listener fires once per dispatch even if the payload is
		// reference-equal.
		toastCounter: 0,
		// Per-slot dirty contributions from sibling panels that can't
		// reach the tab owner's local React state (e.g. Compliance
		// Settings is a Dialog.js-level sibling of Form Restriction
		// but still drives the same Save button). The tab owner reads
		// `selectTabDirtyContributionsAggregate` and ORs it into its
		// own `isDirty`. Each consumer cleans up its slot on unmount
		// via `setTabDirtyContribution(slot, false)`.
		tabDirtyContributions: {},
	},
	globalSettings: {
		// Map of tab slug → { isDirty, isSaving } registered by tab
		// components that own their own save logic (e.g. pro tabs). The
		// header Save button reads from here when an entry exists for the
		// active tab; otherwise it falls back to free's per-tab dirty
		// tracking inside Component.js.
		tabAdapters: {},
		// Aggregate free-tab dirty signal pushed from Component.js, so the
		// tab-switch guard can decide whether to intercept without holding
		// a reference to Component's local state.
		pageDirty: false,
		// `{ to: '?page=...&tab=...' }` while a tab-switch is blocked and
		// the "Unsaved changes" modal is asking the user to confirm.
		pendingNav: null,
		// Per-slot dirty contributions from extension panels rendered into
		// global-settings pages via filters (e.g. pro IP/Country/Keyword
		// Defaults under Global Defaults → Form Restrictions). Host
		// `Component.js` ORs the aggregate into the header Save button's
		// `isDirty` so the button reflects extension edits too.
		extensionDirty: {},
		// Per-slot `isSaving` contributions, parallel to `extensionDirty`.
		// Host ORs into the header Save button's `isSaving` so the
		// spinner shows even when only an extension panel is mid-POST.
		extensionSaving: {},
		// Incremented when the host's Save button is clicked. Extension
		// panels subscribe to the counter and fire their own POSTs on
		// each bump — parallel to the host's `saveAllDirtyTabs`.
		extensionSaveCounter: 0,
		// Tab slug attached to the most-recent
		// `requestGlobalSettingsExtensionSave` bump. Extensions filter
		// on this so a save on a different tab doesn't trigger their
		// POST. `null` means broadcast (back-compat path).
		extensionSaveTab: null,
	},
};

/**
 * Reducer function to handle actions and update the state.
 *
 * @param {Object} state  - The current state of the store.
 * @param {Object} action - The action to be processed.
 * @return {Object} The new state after applying the action.
 */
function reducer( state = defaultState, action ) {
	switch ( action.type ) {
		case 'UPDATE_SRFM_GLOBAL_DATA':
			return {
				...state,
				srfmGlobalData: {
					...state.srfmGlobalData,
					...action.payload,
				},
			};

		case 'SRFM_FORM_SETTINGS_SET_VALUE':
			return {
				...state,
				formSettings: {
					...state.formSettings,
					values: {
						...state.formSettings.values,
						[ action.metaKey ]: action.value,
					},
				},
			};
		case 'SRFM_FORM_SETTINGS_COMMIT_SAVED_META': {
			const next = { ...action.values };
			return {
				...state,
				formSettings: {
					...state.formSettings,
					baseline: { ...state.formSettings.baseline, ...next },
					values: { ...state.formSettings.values, ...next },
				},
			};
		}
		case 'SRFM_FORM_SETTINGS_REVERT_ON_FAILURE': {
			// Single-hop revert: write baseline AND value for each
			// affected key in one new state object so any subscriber
			// comparing the two through useSelect never observes the
			// intermediate "equal" state that the previous 2-step
			// revert produced.
			const entries = action.entries || {};
			const nextBaseline = { ...state.formSettings.baseline };
			const nextValues = { ...state.formSettings.values };
			Object.keys( entries ).forEach( ( key ) => {
				const entry = entries[ key ] || {};
				nextBaseline[ key ] = entry.baseline;
				nextValues[ key ] = entry.value;
			} );
			return {
				...state,
				formSettings: {
					...state.formSettings,
					baseline: nextBaseline,
					values: nextValues,
				},
			};
		}
		case 'SRFM_FORM_SETTINGS_DISCARD_VALUES':
			return {
				...state,
				formSettings: {
					...state.formSettings,
					values: { ...state.formSettings.baseline },
				},
			};
		case 'SRFM_FORM_SETTINGS_DISCARD':
			// Lifecycle reset on dialog close. Wipe `globalSettings` too —
			// the dialog and the global-settings page are mutually
			// exclusive views, so any registered adapters / pending nav /
			// extension slots from a prior visit are stale state once the
			// dialog closes.
			return {
				...state,
				formSettings: {
					baseline: {},
					values: {},
					singleFormSettingUnsave: false,
					singleFormSettingSaving: false,
					discardCounter: 0,
					pendingBackDiscard: null,
					backDiscardConfirmCounter: 0,
					pendingToast: null,
					toastCounter: 0,
					tabDirtyContributions: {},
				},
				globalSettings: {
					tabAdapters: {},
					pageDirty: false,
					pendingNav: null,
					extensionDirty: {},
					extensionSaving: {},
					extensionSaveCounter: 0,
					extensionSaveTab: null,
				},
			};
		case 'SRFM_SINGLE_FORM_SETTING_SET_UNSAVE':
			return {
				...state,
				formSettings: {
					...state.formSettings,
					singleFormSettingUnsave: !! action.unsaved,
				},
			};
		case 'SRFM_SINGLE_FORM_SETTING_SET_SAVING':
			return {
				...state,
				formSettings: {
					...state.formSettings,
					singleFormSettingSaving: !! action.saving,
				},
			};
		case 'SRFM_SINGLE_FORM_SETTING_DISCARD':
			return {
				...state,
				formSettings: {
					...state.formSettings,
					singleFormSettingUnsave: false,
					discardCounter:
						( state.formSettings.discardCounter || 0 ) + 1,
				},
			};
		case 'SRFM_SINGLE_FORM_SETTING_KEEP':
			// No state change today. Dispatched from the popup's "Stay"
			// branch so behaviour is symmetric and we have a single hook
			// to extend later.
			return state;

		case 'SRFM_FORM_SETTINGS_REQUEST_BACK_DISCARD':
			return {
				...state,
				formSettings: {
					...state.formSettings,
					pendingBackDiscard: action.payload || null,
				},
			};
		case 'SRFM_FORM_SETTINGS_CONFIRM_BACK_DISCARD':
			return {
				...state,
				formSettings: {
					...state.formSettings,
					pendingBackDiscard: null,
					backDiscardConfirmCounter:
						( state.formSettings.backDiscardConfirmCounter || 0 ) +
						1,
				},
			};
		case 'SRFM_FORM_SETTINGS_CANCEL_BACK_DISCARD':
			return {
				...state,
				formSettings: {
					...state.formSettings,
					pendingBackDiscard: null,
				},
			};
		case 'SRFM_FORM_SETTINGS_REQUEST_TOAST':
			return {
				...state,
				formSettings: {
					...state.formSettings,
					pendingToast: action.payload || null,
					toastCounter:
						( state.formSettings.toastCounter || 0 ) + 1,
				},
			};
		case 'SRFM_FORM_SETTINGS_SET_TAB_DIRTY_CONTRIBUTION': {
			if ( ! action.slot ) {
				return state;
			}
			const current =
				state.formSettings.tabDirtyContributions || {};
			// Skip if value is already what we'd set — avoids unnecessary
			// re-renders for consumers subscribed via useSelect.
			if ( current[ action.slot ] === action.dirty ) {
				return state;
			}
			return {
				...state,
				formSettings: {
					...state.formSettings,
					tabDirtyContributions: {
						...current,
						[ action.slot ]: action.dirty,
					},
				},
			};
		}

		case 'SRFM_GLOBAL_SETTINGS_SET_TAB_ADAPTER':
			return {
				...state,
				globalSettings: {
					...state.globalSettings,
					tabAdapters: {
						...state.globalSettings.tabAdapters,
						[ action.slug ]: {
							isDirty: !! action.isDirty,
							isSaving: !! action.isSaving,
						},
					},
				},
			};
		case 'SRFM_GLOBAL_SETTINGS_REMOVE_TAB_ADAPTER': {
			const { [ action.slug ]: _, ...rest } =
				state.globalSettings.tabAdapters;
			return {
				...state,
				globalSettings: {
					...state.globalSettings,
					tabAdapters: rest,
				},
			};
		}
		case 'SRFM_GLOBAL_SETTINGS_SET_PAGE_DIRTY':
			return {
				...state,
				globalSettings: {
					...state.globalSettings,
					pageDirty: !! action.isDirty,
				},
			};
		case 'SRFM_GLOBAL_SETTINGS_REQUEST_NAV':
			return {
				...state,
				globalSettings: {
					...state.globalSettings,
					pendingNav: { to: action.to },
				},
			};
		case 'SRFM_GLOBAL_SETTINGS_CLEAR_PENDING_NAV':
			return {
				...state,
				globalSettings: {
					...state.globalSettings,
					pendingNav: null,
				},
			};
		case 'SRFM_GLOBAL_SETTINGS_SET_EXTENSION_DIRTY': {
			if ( ! action.slot ) {
				return state;
			}
			const current = state.globalSettings.extensionDirty || {};
			if ( current[ action.slot ] === action.dirty ) {
				return state;
			}
			return {
				...state,
				globalSettings: {
					...state.globalSettings,
					extensionDirty: {
						...current,
						[ action.slot ]: action.dirty,
					},
				},
			};
		}
		case 'SRFM_GLOBAL_SETTINGS_REQUEST_EXTENSION_SAVE':
			return {
				...state,
				globalSettings: {
					...state.globalSettings,
					extensionSaveCounter:
						( state.globalSettings.extensionSaveCounter || 0 ) +
						1,
					extensionSaveTab: action.tab || null,
				},
			};
		case 'SRFM_GLOBAL_SETTINGS_SET_EXTENSION_SAVING': {
			if ( ! action.slot ) {
				return state;
			}
			const current = state.globalSettings.extensionSaving || {};
			if ( current[ action.slot ] === action.saving ) {
				return state;
			}
			return {
				...state,
				globalSettings: {
					...state.globalSettings,
					extensionSaving: {
						...current,
						[ action.slot ]: action.saving,
					},
				},
			};
		}

		default:
			return state;
	}
}

export default reducer;
