import { dispatch } from '@wordpress/data';
import { STORE_NAME as SRFM_STORE_NAME } from '@Store/constants';
// Side-effect import: ensures the `sureforms` store is registered by the
// time any consumer calls `notify.success/.error`, even when this module
// loads from a separate webpack entry (e.g. pro bundles).
import '@Store/store';

const DEFAULT_DURATION = 5000;

/**
 * Internal: dispatch a `requestToast` action with normalized shape.
 *
 * @param {'success'|'error'} type               Toast variant.
 * @param {string}            message            Toast body copy.
 * @param {Object}            [options]
 * @param {number}            [options.duration] Auto-dismiss delay in ms.
 */
const enqueue = ( type, message, options = {} ) => {
	dispatch( SRFM_STORE_NAME )?.requestToast?.( {
		type,
		message,
		duration: options.duration ?? DEFAULT_DURATION,
	} );
};

/**
 * Centralized toast surface for the single-form-settings dialog. Tabs and
 * helpers call `notify.success(...)` / `notify.error(...)` instead of
 * importing `@bsf/force-ui`'s `toast` directly. `Dialog.js` owns the
 * `<Toaster>` mount and subscribes to the store; here we just queue the
 * payload.
 *
 * Why dispatch (vs. calling the toast lib directly): the helper works
 * from non-React call sites (e.g. async helpers like `srfmSaveFormMeta`)
 * and from any tab regardless of which Toaster is mounted, because the
 * dispatch flows through the shared store.
 */
export const notify = {
	success: ( message, options ) => enqueue( 'success', message, options ),
	error: ( message, options ) => enqueue( 'error', message, options ),
};
