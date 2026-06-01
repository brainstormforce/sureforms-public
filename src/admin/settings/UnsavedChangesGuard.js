import { __ } from '@wordpress/i18n';
import { useSelect, useDispatch } from '@wordpress/data';
import { useNavigate } from 'react-router-dom';
import ConfirmationDialog from '@Admin/components/ConfirmationDialog';
import { STORE_NAME as SRFM_STORE_NAME } from '../../store/constants';

/**
 * Renders the "Unsaved changes" confirmation modal for the global
 * settings page. Listens for `globalSettings.pendingNav` in the
 * sureforms store — `Navigation.js` sets it when the user clicks a
 * different tab while any free or pro tab is dirty.
 *
 * Confirm: invokes `window.__srfm_global_settings_discard` (registered
 * by `Component.js`) to revert free-tab state to its saved baselines,
 * clears `pendingNav`, and navigates via react-router.
 *
 * Cancel: clears `pendingNav` and leaves local state intact. The cancel
 * button is labelled "Keep editing" so the copy matches what actually
 * happens — Save is per-tab and must still be clicked manually.
 */
const UnsavedChangesGuard = () => {
	const pendingNav = useSelect(
		( select ) =>
			select( SRFM_STORE_NAME )?.selectGlobalSettingsPendingNav?.() ||
			null,
		[]
	);
	const { clearGlobalSettingsPendingNav } = useDispatch( SRFM_STORE_NAME );
	const navigate = useNavigate();

	const onConfirm = () => {
		if ( ! pendingNav ) {
			return;
		}
		const target = pendingNav.to;
		clearGlobalSettingsPendingNav();
		if ( typeof window.__srfm_global_settings_discard === 'function' ) {
			window.__srfm_global_settings_discard();
		}
		navigate( target );
	};

	const onCancel = () => clearGlobalSettingsPendingNav();

	return (
		<ConfirmationDialog
			isOpen={ pendingNav !== null }
			title={ __( 'Unsaved changes', 'sureforms' ) }
			description={ __(
				'Some fields have unsaved changes. Discard them to continue, or stay to save your edits.',
				'sureforms'
			) }
			confirmButtonText={ __( 'Discard & switch', 'sureforms' ) }
			cancelButtonText={ __( 'Keep editing', 'sureforms' ) }
			onConfirm={ onConfirm }
			onCancel={ onCancel }
			destructiveConfirmButton
		/>
	);
};

export default UnsavedChangesGuard;
