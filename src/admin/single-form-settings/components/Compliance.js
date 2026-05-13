import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { Title } from '@bsf/force-ui';
import TabContentWrapper from '@Components/tab-content-wrapper';
import { ComplianceFields } from '@Admin/shared-components/compliance';
import { srfmEditFormMeta } from '@Components/tab-content-wrapper/edit-form-meta';
import { STORE_NAME as SRFM_STORE_NAME } from '@Store/constants';

/**
 * Compliance Settings panel — rendered as a sibling of Form Restriction
 * inside the Advanced Settings tab (`Dialog.js` mounts both side-by-side
 * inside the tab's `component` Fragment).
 *
 * Wires into the tab's Save button via the per-slot dirty map in Redux
 * (`formSettings.tabDirtyContributions`). `FormRestriction` ORs the
 * aggregate into its own `isDirty`, so the same Save button responds
 * to Compliance edits without Compliance having to live inside
 * FormRestriction's render tree.
 *
 * Writes route through `srfmEditFormMeta` so the value lands in the
 * shared `formSettings.values` slice that `TabContentWrapper.handleSave`
 * reads when building the POST payload (and the existing core/editor
 * consumers — the post sidebar etc. — still see the change via the
 * mirror inside `srfmEditFormMeta`).
 *
 * Dirty derivation compares the edited buffer vs the saved snapshot
 * via `core/editor` selectors — the panel has no local React state
 * buffer, so saved-vs-edited on the editor store is the authoritative
 * comparison.
 *
 * @param {Object} props                Component props.
 * @param {Array}  props.complianceData Current `_srfm_compliance` array
 *                                      (single item today).
 */
const Compliance = ( { complianceData } ) => {
	const { setTabDirtyContribution } = useDispatch( SRFM_STORE_NAME );

	/**
	 * Handle field change and stage to Redux + core/editor.
	 *
	 * @param {string} key   - Field key to update.
	 * @param {*}      value - New value.
	 */
	const handleChange = ( key, value ) => {
		const updatedData = complianceData.map( ( item ) => ( {
			...item,
			[ key ]: value,
		} ) );
		srfmEditFormMeta( '_srfm_compliance', updatedData );
	};

	// Dirty derivation: edited buffer vs saved snapshot. No local
	// React state in this component — writes go straight through
	// `srfmEditFormMeta`, so saved-vs-edited on the editor store is
	// the source of truth.
	const isDirty = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		const edited = editor.getEditedPostAttribute( 'meta' )
			?._srfm_compliance;
		const saved = editor.getCurrentPostAttribute( 'meta' )
			?._srfm_compliance;
		return JSON.stringify( edited ) !== JSON.stringify( saved );
	}, [] );

	// Push our slot's contribution into Redux. The host
	// (`FormRestriction`) reads the aggregate via
	// `selectTabDirtyContributionsAggregate` and ORs it into the Save
	// button's `isDirty`. Cleanup on unmount clears the slot so a tab
	// switch / discard doesn't leave a stale `true`.
	useEffect( () => {
		setTabDirtyContribution( 'compliance', isDirty );
		return () => setTabDirtyContribution( 'compliance', false );
	}, [ isDirty, setTabDirtyContribution ] );

	return (
		<TabContentWrapper className="!mt-0">
			<>
				<Title
					size="xs"
					className="mb-4"
					title={ __( 'Compliance Settings', 'sureforms' ) }
				/>
				<ComplianceFields
					context="form"
					values={ complianceData[ 0 ] }
					onChange={ handleChange }
				/>
			</>
		</TabContentWrapper>
	);
};

export default Compliance;
