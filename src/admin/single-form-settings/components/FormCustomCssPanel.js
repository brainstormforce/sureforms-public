import { __ } from '@wordpress/i18n';
import { useDispatch, useSelect, select as wpSelect } from '@wordpress/data';
import {
	useRef,
	useEffect,
	useMemo,
	useState,
	useLayoutEffect,
} from '@wordpress/element';
import { store as editorStore } from '@wordpress/editor';
import editorStyles from './editor.lazy.scss';
import { TextArea, Label, Container } from '@bsf/force-ui';
import TabContentWrapper from '@Components/tab-content-wrapper';
import { srfmEditFormMeta } from '@Components/tab-content-wrapper/edit-form-meta';
import { STORE_NAME as SRFM_STORE_NAME } from '@Store/constants';

const FormCustomCssPanel = ( { formCustomCssData } ) => {
	const tabRef = useRef( null );

	// `data` is the live editing state; `prevData` is the last-saved
	// baseline. Both start from the post-meta value on mount so isDirty
	// reads false until the user edits.
	const [ data, setData ] = useState( formCustomCssData || '' );
	const [ prevData, setPrevData ] = useState( formCustomCssData || '' );
	const [ isSaving, setIsSaving ] = useState( false );

	const isDirty = useMemo( () => data !== prevData, [ data, prevData ] );

	// Push the local dirty signal into the store so the dialog's
	// unsaved-changes guard can read it.
	const { setSingleFormSettingUnsave } = useDispatch( SRFM_STORE_NAME );
	useEffect( () => {
		setSingleFormSettingUnsave( isDirty );
	}, [ isDirty, setSingleFormSettingUnsave ] );

	// Unmount cleanup — covers every exit path so the central flag
	// doesn't leak past a discard / tab switch.
	useEffect( () => {
		return () => {
			setSingleFormSettingUnsave( false );
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Listen for the discard signal dispatched by the dialog's "Discard
	// & continue" branch. Each bump is one discard event; the ref skips
	// the initial render so the tab doesn't reset on mount.
	const discardCounter = useSelect(
		( s ) =>
			s( SRFM_STORE_NAME )?.selectSingleFormSettingDiscardCounter?.() ||
			0,
		[]
	);
	const lastDiscardCounter = useRef( discardCounter );
	useEffect( () => {
		if ( discardCounter === lastDiscardCounter.current ) {
			return;
		}
		lastDiscardCounter.current = discardCounter;
		setData( prevData );
	}, [ discardCounter ] );

	// Use the editor styles. This is important to make sure the editor styles are applied to the custom CSS editor.
	useLayoutEffect( () => {
		editorStyles.use();
		return () => {
			editorStyles.unuse();
		};
	}, [] );

	// Initialize the editor when the component is mounted and cleanup the editor when the component is unmounted.
	useEffect( () => {
		const cleanupEditors = () => {
			const srfmCustomCSSPanel = document.querySelector(
				'.srfm-custom-css-panel'
			);
			const editors =
				srfmCustomCSSPanel?.querySelectorAll( '.CodeMirror-wrap' );
			editors?.forEach( ( editor ) => {
				editor?.remove();
			} );
		};

		cleanupEditors(); // Remove existing editors when the component is mounted

		const initializeEditor = () => {
			const editor = wp?.codeEditor?.initialize( tabRef?.current, {
				...wp.codeEditor.defaultSettings.codemirror,
				scrollbarStyle: null,
			} );

			const codeMirrorEditor = document.querySelector(
				'.srfm-css-editor .CodeMirror-code'
			);

			if ( codeMirrorEditor ) {
				codeMirrorEditor?.addEventListener( 'keyup', function () {
					editor?.codemirror?.save();
					const value = editor?.codemirror?.getValue();
					setData( value );
				} );
			}
		};

		initializeEditor(); // Initialize the editor when the component is mounted

		return cleanupEditors; // Remove existing editors when the component is unmounted
	}, [ tabRef ] );

	// Returns null on valid; an error string otherwise. Stages the
	// in-flight value into Redux `values` so `handleSave` reads the
	// right payload. Custom CSS has no validation today; the function
	// shape is kept for parity with the other migrated tabs.
	const validateBeforeSave = () => {
		srfmEditFormMeta( '_srfm_form_custom_css', data );
		return null;
	};

	const onSaveSuccess = () => {
		// `wp_kses_post` runs server-side on `_srfm_form_custom_css`. If
		// the user wrote CSS containing `<`/`>` (e.g. attribute
		// selectors) the persisted bytes differ from local `data`.
		// Re-baseline from `getCurrentPostAttribute` — the entity record
		// that `TabContentWrapper.handleSave` just synced via
		// `receiveEntityRecords` with the sanitized response. We read
		// `current` rather than `edited` because `srfmEditFormMeta`
		// during typing pushes the pre-sanitize value into the edited
		// buffer; the current attribute is the post-save canonical bytes.
		const savedMeta =
			wpSelect( editorStore ).getCurrentPostAttribute( 'meta' ) || {};
		const saved =
			savedMeta._srfm_form_custom_css !== undefined
				? savedMeta._srfm_form_custom_css
				: data;
		setData( saved );
		setPrevData( saved );
	};

	return (
		<TabContentWrapper
			title={ __( 'Custom CSS', 'sureforms' ) }
			showTitleHelpText={ true }
			titleHelpText={ __(
				'Add custom CSS rules to style this specific form independently of global styles.',
				'sureforms'
			) }
			tabId="form_custom_css"
			showSaveButton={ true }
			validate={ validateBeforeSave }
			isDirty={ isDirty }
			isSaving={ isSaving }
			onSavingChange={ setIsSaving }
			onSaveSuccess={ onSaveSuccess }
		>
			<Container direction="column" className="gap-2">
				<Label
					variant="neutral"
					size="md"
					className="font-normal text-text-secondary"
				>
					{ __(
						'The following CSS styles added below will only apply to this form container.',
						'sureforms'
					) }
				</Label>
				<div className="srfm-custom-css-panel srfm-css-editor">
					<TextArea
						aria-label={ __( 'Custom CSS Panel', 'sureforms' ) }
						id="srfm-css-editor"
						size="sm"
						value={ data }
						ref={ tabRef }
					/>
				</div>
			</Container>
		</TabContentWrapper>
	);
};

export default FormCustomCssPanel;
