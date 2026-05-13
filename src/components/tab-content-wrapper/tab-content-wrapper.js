import { Button, Container, Title, Label } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';
import { ArrowLeftIcon, Save, LoaderCircle } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { useDispatch, select, dispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as coreStore } from '@wordpress/core-data';
import apiFetch from '@wordpress/api-fetch';

import { STORE_NAME as SRFM_STORE_NAME } from '../../store/constants';
import { getTabMetaKeys } from './tab-meta-key-registry';
import { notify } from '@Utils/notify';

const TabContentWrapper = ( {
	children,
	title,
	className,
	onClickBack,
	onClickAction,
	actionBtnText,
	actionBtnIcon,
	actionBtnVariant = 'primary',
	actionLeftContent,
	actionBtnDisabled = false,
	hideTitle = false,
	shouldShowAutoSaveText = false,
	showTitleHelpText = false,
	titleHelpText = '',
	autoSaveHelpText = __(
		'All changes will be saved automatically when you press back.',
		'sureforms'
	),
	shouldAddHelpTextPadding = true,
	// Header Save button — opt-in. Tabs that own the explicit Save flow
	// pass `showSaveButton={true}` alongside `tabId` / `validate` /
	// `isDirty` / `isSaving` / `onSavingChange` / `onSaveSuccess`. Default
	// `false` keeps un-migrated tabs from rendering a perma-disabled
	// button while we roll out the new flow tab by tab.
	showSaveButton = false,
	// Validate before POST. Return `null` to proceed, or an error string
	// to abort + surface as a toast.
	validate,
	// Tab-owned dirty flag. Drives the Save button's `disabled` state.
	isDirty: customIsDirty,
	// Tab-owned in-flight save flag. The wrapper flips this via
	// `onSavingChange(true|false)` around the POST so the Save button
	// locks during the network call (prevents double-fires).
	isSaving = false,
	onSavingChange,
	// Fires after a successful POST so the tab can re-baseline its local
	// state (e.g. `setPrevData(formData)`).
	onSaveSuccess,
	// Resolves the meta-keys owned by this tab via the registry.
	tabId,
} ) => {
	const metaKeys = tabId ? getTabMetaKeys( tabId ) : [];

	const {
		commitSavedMeta,
		setSingleFormSettingSaving,
		revertOnSaveFailure,
	} = useDispatch( SRFM_STORE_NAME );

	const handleSave = async () => {
		// Contract for tabs / extensions plugging into this Save:
		//
		// 1. Every staged change MUST go through `srfmEditFormMeta`. We
		//    build the POST payload below from Redux `values`; meta
		//    written via `editPost({meta:...})` alone is invisible here
		//    and would be silently dropped from the save.
		// 2. `validate()` may dispatch additional `srfmEditFormMeta`
		//    calls right before returning `null` (the "commit on
		//    validate" pattern used by FormCustomCss + SpamProtection).
		//    That's safe today because `setFormSettingValue` is a plain
		//    action — the next-line `selectFormSettingValue` read sees
		//    the update synchronously. Don't switch the action to a
		//    `controls`/generator yield without revisiting this read.
		if ( typeof validate === 'function' ) {
			const validationError = validate();
			if ( validationError ) {
				notify.error( validationError );
				return;
			}
		}

		const editorSelect = select( editorStore );
		const postId = editorSelect.getCurrentPostId();
		const savedMeta = editorSelect.getCurrentPostAttribute( 'meta' ) || {};
		const srfmStore = select( SRFM_STORE_NAME );

		// Read the save payload from our Redux `values` slice — never from
		// `core/editor` — so the bytes we POST match exactly what the user
		// changed, with no autosave or block-sidebar drift mixed in.
		const metaData = metaKeys.reduce( ( acc, key ) => {
			const value = srfmStore.selectFormSettingValue( key );
			if ( value !== undefined ) {
				acc[ key ] = value;
			}
			return acc;
		}, {} );

		if ( ! postId || Object.keys( metaData ).length === 0 ) {
			if ( typeof onSaveSuccess === 'function' ) {
				onSaveSuccess();
			}
			notify.success( __( 'Form settings saved.', 'sureforms' ) );
			return;
		}

		const postType = editorSelect.getCurrentPostType();

		// Snapshot per-key { baseline, value } so we can revert on POST
		// failure. baseline + value may differ (the user could have
		// pending edits when Save is clicked).
		const previousState = metaKeys.reduce( ( acc, key ) => {
			acc[ key ] = {
				baseline: srfmStore.selectFormSettingBaselineValue( key ),
				value: srfmStore.selectFormSettingValue( key ),
			};
			return acc;
		}, {} );

		// Optimistic baseline commit: align baseline with the about-to-be-
		// saved payload BEFORE the await yields. Keeps the dialog's
		// `singleFormSettingUnsave` signal from briefly flipping true
		// while the POST is in flight.
		commitSavedMeta( metaData );

		if ( typeof onSavingChange === 'function' ) {
			onSavingChange( true );
		}
		// Mirror in-flight save to Redux so the dialog's tab-switch /
		// close guards can block during the await. Without this, a
		// "Discard & continue" click mid-POST would revert local React
		// state for a save the server has already accepted.
		setSingleFormSettingSaving?.( true );
		try {
			const response = await apiFetch( {
				path: '/sureforms/v1/form-settings',
				method: 'POST',
				data: { post_id: postId, meta_data: metaData },
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
				// Push the sanitized response into the editor's edited
				// buffer so it matches the entity record. Without this,
				// the buffer would still hold the user's pre-sanitize
				// typed value (pushed by `srfmEditFormMeta` during
				// typing) and Gutenberg's `isEditedPostDirty` would
				// stay true — leaving the core "Update" button active
				// even though our own save round-trip is complete.
				// Idempotent: response.meta matches the value we just
				// receiveEntityRecords-synced, so Gutenberg's dirty
				// detection now reads clean.
				dispatch( editorStore ).editPost( { meta: response.meta } );
				commitSavedMeta( response.meta );
			}

			if ( typeof onSaveSuccess === 'function' ) {
				onSaveSuccess();
			}

			notify.success(
				response?.message ||
					__( 'Form settings saved.', 'sureforms' )
			);
		} catch ( error ) {
			// Atomic revert: one dispatch writes baseline AND value for
			// every affected key in a single new state, so subscribers
			// comparing `value` vs `baseline` can't observe an
			// intermediate equal state between hops.
			revertOnSaveFailure?.( previousState );
			// Only surface `error.message` when it came from the REST
			// handler (which sets `error.code`) — a raw network failure
			// throws a `TypeError` with a translator-unfriendly message
			// like "Failed to fetch" that's worse than the static copy.
			const friendly = error?.code
				? error.message
				: __( 'Failed to save form settings.', 'sureforms' );
			notify.error( friendly );
		} finally {
			if ( typeof onSavingChange === 'function' ) {
				onSavingChange( false );
			}
			setSingleFormSettingSaving?.( false );
		}
	};

	const handleBack = () => {
		if ( typeof onClickBack !== 'function' ) {
			return;
		}

		onClickBack();
	};

	const handleClickAction = ( data ) => {
		if ( typeof onClickAction !== 'function' ) {
			return;
		}

		onClickAction( data );
	};

	return (
		<div className={ cn( 'pb-8', ! hideTitle && 'space-y-7' ) }>
			<Container align="center" justify="between">
				<Container className="gap-0" direction="column">
					<Container.Item className="flex items-center gap-2">
						{ onClickBack && (
							<Button
								className="p-0"
								size="md"
								variant="ghost"
								onClick={ handleBack }
								icon={ <ArrowLeftIcon /> }
							/>
						) }
						{ ! hideTitle && (
							<Title tag="h4" title={ title } size="md" />
						) }
					</Container.Item>
					{ showTitleHelpText && (
						<Container.Item className="">
							<Label
								size="sm"
								variant="help"
								className="text-text-on-button-disabled font-normal"
							>
								{ titleHelpText }
							</Label>
						</Container.Item>
					) }

					{ shouldShowAutoSaveText && (
						<Container.Item
							className={ cn(
								shouldAddHelpTextPadding ? 'pl-7' : ''
							) }
						>
							<Label
								size="sm"
								variant="help"
								className="text-text-on-button-disabled font-normal"
							>
								{ autoSaveHelpText }
							</Label>
						</Container.Item>
					) }
				</Container>
				<Container align="center" className="gap-3">
					{ actionLeftContent }
					{ onClickAction && (
						<Button
							size="md"
							onClick={ handleClickAction }
							icon={ actionBtnIcon }
							variant={ actionBtnVariant }
							disabled={ actionBtnDisabled }
						>
							{ actionBtnText }
						</Button>
					) }
					{ showSaveButton && (
						<Button
							size="md"
							variant="primary"
							onClick={ handleSave }
							disabled={ ! customIsDirty || isSaving }
							iconPosition="left"
							icon={
								isSaving ? (
									<LoaderCircle className="animate-spin size-4" />
								) : (
									<Save />
								)
							}
						>
							{ isSaving
								? __( 'Saving…', 'sureforms' )
								: __( 'Save', 'sureforms' ) }
						</Button>
					) }
				</Container>
			</Container>
			<div
				className={ cn(
					'bg-background-primary rounded-xl p-4 shadow-sm',
					className
				) }
			>
				{ children }
			</div>
		</div>
	);
};

export default TabContentWrapper;
