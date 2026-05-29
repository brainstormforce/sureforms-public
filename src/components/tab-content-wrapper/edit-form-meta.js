import { dispatch, select } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as coreStore } from '@wordpress/core-data';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

import { STORE_NAME as SRFM_STORE_NAME } from '../../store/constants';
import { notify } from '@Utils/notify';
// Side-effect import: ensures the `sureforms` store is registered in the
// shared `wp.data` registry by the time any consumer of this helper runs,
// even when imported from a separate webpack entry (e.g. pro bundles).
import '../../store/store';

/**
 * Single write surface for form-settings meta. Pushes the value into our
 * Redux `values` slice (the source of truth for the dirty signal) and
 * mirrors it onto WP's `core/editor` so block sidebar panels still see
 * the change.
 *
 * @param {string} metaKey Post meta key, e.g. `_srfm_email_notification`.
 * @param {*}      value   New value (string, array, or object — caller decides shape).
 */
export const srfmEditFormMeta = ( metaKey, value ) => {
	dispatch( SRFM_STORE_NAME )?.setFormSettingValue?.( metaKey, value );
	dispatch( editorStore )?.editPost?.( {
		meta: { [ metaKey ]: value },
	} );
};

/**
 * Immediate-commit variant of `srfmEditFormMeta`. Used for list-mutation
 * actions (status toggle, delete) that should not be seen as "dirty" — the
 * caller doesn't want the user to have to click a Save button afterwards.
 *
 * Flow: optimistic local commit (both baseline + values) → POST to
 * `/sureforms/v1/form-settings` → sync `core/editor` and Redux baseline
 * with the server's sanitized response so the dialog's
 * `selectSingleFormSettingUnsave` guard stays false through the round-trip.
 * On failure, the local update is reverted and an error toast is shown.
 *
 * @param {string} metaKey Post meta key, e.g. `_srfm_email_notification`.
 * @param {*}      value   New value to persist.
 * @return {Promise<void>}
 */
export const srfmSaveFormMeta = async ( metaKey, value ) => {
	const srfmStore = select( SRFM_STORE_NAME );
	// Snapshot for rollback on POST failure. We need *both* the pre-save
	// baseline and the pre-save value because they may differ — the user
	// could have pending edits when toggle/delete is clicked.
	const previousValue = srfmStore?.selectFormSettingValue?.( metaKey );
	const previousBaseline =
		srfmStore?.selectFormSettingBaselineValue?.( metaKey );

	// Optimistic: commit baseline AND values atomically so the staged
	// state matches the about-to-be-saved payload immediately. Keeps the
	// dirty signal from flipping mid-flight.
	dispatch( SRFM_STORE_NAME )?.commitSavedMeta?.( { [ metaKey ]: value } );
	dispatch( editorStore )?.editPost?.( {
		meta: { [ metaKey ]: value },
	} );

	const editorSelect = select( editorStore );
	const postId = editorSelect.getCurrentPostId();
	if ( ! postId ) {
		// No persistent post yet — baseline already synced above.
		return;
	}

	const postType = editorSelect.getCurrentPostType();
	const savedMeta = editorSelect.getCurrentPostAttribute( 'meta' ) || {};

	try {
		const response = await apiFetch( {
			path: '/sureforms/v1/form-settings',
			method: 'POST',
			data: { post_id: postId, meta_data: { [ metaKey ]: value } },
		} );

		const mergedMeta = { ...savedMeta, ...( response?.meta || {} ) };
		dispatch( coreStore ).receiveEntityRecords(
			'postType',
			postType,
			[ { id: postId, meta: mergedMeta } ],
			undefined,
			false
		);

		if ( response?.meta && Object.keys( response.meta ).length > 0 ) {
			// Sync edited buffer with the sanitized response so
			// Gutenberg's `isEditedPostDirty` reads clean (the buffer
			// still holds the user's pre-sanitize value pushed during
			// typing via `srfmEditFormMeta`). Idempotent against the
			// entity record we just synced.
			dispatch( editorStore ).editPost( { meta: response.meta } );
			dispatch( SRFM_STORE_NAME ).commitSavedMeta( response.meta );
		}
	} catch ( error ) {
		// Atomic revert in one dispatch — writes baseline AND value for
		// this key in a single new state. Subscribers comparing the two
		// can't observe an intermediate equal state between hops.
		dispatch( SRFM_STORE_NAME ).revertOnSaveFailure( {
			[ metaKey ]: {
				baseline: previousBaseline,
				value: previousValue,
			},
		} );
		// Only surface `error.message` when it came from the REST handler
		// (which sets `error.code`); raw network failures throw a
		// `TypeError` with a translator-unfriendly message.
		const friendly = error?.code
			? error.message
			: __( 'Failed to save. Please try again.', 'sureforms' );
		notify.error( friendly );
	}
};
