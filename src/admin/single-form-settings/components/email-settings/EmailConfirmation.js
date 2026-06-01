import { __ } from '@wordpress/i18n';
import {
	useCallback,
	useState,
	useEffect,
	useMemo,
	useRef,
} from '@wordpress/element';
import { useDispatch, useSelect } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';
import { Container, Switch } from '@bsf/force-ui';
import ModalWarning from '@Components/force-ui-components/ModalWarning';
import TabContentWrapper from '@Components/tab-content-wrapper';
import { EmailNotificationFields } from '@Admin/shared-components/email-notification';
import { STORE_NAME as SRFM_STORE_NAME } from '@Store/constants';

const EmailConfirmation = ( props ) => {
	const {
		data,
		handleConfirmEmail,
		handleBackNotification,
		setHasValidationErrors,
	} = props;

	// Populated initial state. For Add New this carries the suggested
	// defaults (template name, {site_title} / {admin_email} placeholders);
	// for Edit it's seeded from `data` so the form mounts with the entry's
	// saved values.
	const initFormData = () => ( {
		id: data.id || false,
		// Preserve `false` (disabled) — `data.status || true` was forcing a
		// disabled notification back to enabled on mount.
		status: data.status ?? true,
		is_raw_format: data.is_raw_format || false,
		name: data.name || 'New Notification',
		email_to: data.email_to || '',
		subject: data.subject || '',
		email_reply_to: data.email_reply_to || '',
		email_bcc: data.email_bcc || '',
		email_cc: data.email_cc || '',
		email_body: data.email_body || '',
		from_name: data.from_name || '{site_title}',
		from_email: data.from_email || '{admin_email}',
	} );

	// Empty baseline used as `prevData` for the Add New flow only.
	//
	// Intentionally dirty-on-mount: when the user clicks "Add New", they
	// have explicitly asked to create a notification. The populated
	// template defaults (template name, {site_title}, {admin_email}) ARE
	// the proposed payload — saving them as-is is a valid outcome. With
	// `formData` carrying those defaults and `prevData` blank, the JSON
	// diff reads as "unsaved" on mount so Save activates immediately and
	// the dialog's unsaved-changes guard catches accidental dismissal.
	//
	// Trade-off: closing the browser tab right after opening Add New
	// without typing fires the native `beforeunload` prompt. Acceptable
	// because the alternative (deferring dirty until first keystroke)
	// would let users walk away from an in-flight "Add New" with no
	// indication they had pending work.
	const initBlankData = () => ( {
		id: false,
		status: true,
		is_raw_format: false,
		name: '',
		email_to: '',
		subject: '',
		email_reply_to: '',
		email_bcc: '',
		email_cc: '',
		email_body: '',
		from_name: '',
		from_email: '',
	} );

	const [ formData, setFormData ] = useState( initFormData );
	// Edit: snapshot of the entry on mount (formData == prevData → clean).
	// Add New: blank baseline so the diff against the populated formData
	// reads dirty on mount. `onSaveSuccess` re-snaps prevData = formData
	// after a successful save in either flow.
	const [ prevData, setPrevData ] = useState( () =>
		data?.id ? initFormData() : initBlankData()
	);
	// Tab-owned in-flight save flag. TabContentWrapper toggles this via
	// `onSavingChange` around the POST so the Save button locks during the
	// network call (prevents double-fires).
	const [ isSaving, setIsSaving ] = useState( false );
	const [ dynamicSubject, setDynamicSubject ] = useState(
		data?.subject || ''
	);
	// Ref holds the callback to fire when the user confirms the
	// dialog-level back-arrow discard modal. We can't ship the function
	// itself through Redux, so we stash it locally and react to the
	// confirm-counter bump below.
	const onBackConfirmRef = useRef( null );

	const localIsDirty = useMemo(
		() => JSON.stringify( formData ) !== JSON.stringify( prevData ),
		[ formData, prevData ]
	);

	// Per-slot dirty contributions from filter-driven extension panels
	// (e.g. pro "Attach Uploaded Files" rendered via
	// `srfm.singleFormEmail.settings`). Same shape as FormRestriction's
	// `extensionDirtyMap`: the setter is `useCallback([])`-stable and
	// rides through the filter ctx as a plain function prop, so pro
	// can call it without needing a Redux dispatch or cross-bundle
	// React Context.
	const [ extensionDirtyMap, setExtensionDirtyMap ] = useState( {} );
	const registerExtensionDirty = useCallback( ( slot, value ) => {
		if ( ! slot ) {
			return;
		}
		setExtensionDirtyMap( ( prev ) => {
			if ( prev[ slot ] === value ) {
				return prev;
			}
			return { ...prev, [ slot ]: value };
		} );
	}, [] );
	const extensionsIsDirty = useMemo(
		() => Object.values( extensionDirtyMap ).some( Boolean ),
		[ extensionDirtyMap ]
	);

	const isDirty = localIsDirty || extensionsIsDirty;

	// Push the local dirty signal into the store so the dialog's
	// unsaved-changes guard (tab switch / X / Esc / backdrop / beforeunload)
	// can read it without holding a reference to this component.
	const { setSingleFormSettingUnsave, requestBackDiscard } = useDispatch(
		SRFM_STORE_NAME
	);
	useEffect( () => {
		setSingleFormSettingUnsave( isDirty );
	}, [ isDirty, setSingleFormSettingUnsave ] );

	// Unmount cleanup — covers every exit path (back-arrow discard, dialog
	// close, tab switch via Discard & continue). Without this, the central
	// flag would stay `true` after a local back-arrow discard and mis-fire
	// the dialog popup on the next tab click.
	useEffect( () => {
		return () => {
			setSingleFormSettingUnsave( false );
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Listen for the discard signal dispatched by the dialog's "Discard &
	// continue" branch. Each bump of `discardCounter` is one discard event;
	// the ref skips the initial render so the tab doesn't reset itself on
	// mount.
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
		setFormData( prevData );
		setDynamicSubject( prevData?.subject || '' );
	}, [ discardCounter ] );

	// Listen for the dialog-level back-arrow discard confirm. Each bump of
	// `backDiscardConfirmCounter` means the user clicked "Discard & go
	// back" in the centralized modal — fire the callback we stashed in
	// `onBackConfirmRef` (set by `onClickBack` when the modal was opened).
	const backDiscardConfirmCounter = useSelect(
		( s ) =>
			s( SRFM_STORE_NAME )?.selectBackDiscardConfirmCounter?.() || 0,
		[]
	);
	const lastBackDiscardCounter = useRef( backDiscardConfirmCounter );
	useEffect( () => {
		if ( backDiscardConfirmCounter === lastBackDiscardCounter.current ) {
			return;
		}
		lastBackDiscardCounter.current = backDiscardConfirmCounter;
		const cb = onBackConfirmRef.current;
		onBackConfirmRef.current = null;
		cb?.();
	}, [ backDiscardConfirmCounter ] );

	// Clear any stale form-level validation error flag when the user edits.
	// Validation runs at Save click (`validateBeforeSave`), not on every
	// keystroke — keeps toasts from spamming during typing.
	useEffect( () => {
		setHasValidationErrors( false );
	}, [ formData ] );

	/**
	 * Field change handler shared with EmailNotificationFields. Routes
	 * subject through `dynamicSubject` so the smart-tag append flow keeps
	 * working, and merges everything else into local `formData`.
	 *
	 * @param {string} key   Field key (matches the FormData property).
	 * @param {*}      value New value.
	 */
	const handleChange = ( key, value ) => {
		if ( key === 'subject' ) {
			setDynamicSubject( value );
			return; // The subject useEffect below mirrors into formData.
		}
		setFormData( ( prev ) => ( { ...prev, [ key ]: value } ) );
	};

	// Mirror dynamicSubject into formData so isDirty / Save payload see it.
	useEffect( () => {
		setFormData( ( prev ) => ( { ...prev, subject: dynamicSubject } ) );
	}, [ dynamicSubject ] );

	const onClickBack = () => {
		const isValid = handleConfirmEmail( formData, { silent: true } );
		if ( isValid && ! isDirty ) {
			handleBackNotification();
			return;
		}
		const reason = ! isValid ? 'invalid' : 'dirty';
		// Stash the navigation callback locally — Redux can't carry
		// function references. The confirm-counter listener above will
		// fire it when the user clicks "Discard & go back".
		onBackConfirmRef.current = handleBackNotification;
		requestBackDiscard( {
			title:
				reason === 'invalid'
					? __( 'Some fields need attention', 'sureforms' )
					: __( 'Unsaved changes', 'sureforms' ),
			description:
				reason === 'invalid'
					? __(
						'A recipient email address and subject line are required before this notification can be saved. Fix the highlighted fields, or discard your changes to go back.',
						'sureforms'
					  )
					: __(
						'You have unsaved changes for this notification. Discard them to go back, or stay to save them.',
						'sureforms'
					  ),
			confirmText: __( 'Discard & go back', 'sureforms' ),
			cancelText:
				reason === 'invalid'
					? __( 'Stay & fix', 'sureforms' )
					: __( 'Keep editing', 'sureforms' ),
		} );
	};

	const validateBeforeSave = () => {
		// `handleConfirmEmail` runs the side effects (red border on the
		// invalid input + `setHasValidationErrors(true)`); we still call
		// it with `silent: true` so it doesn't emit its own toast. The
		// returned boolean tells us validity; we compute the *specific*
		// toast copy locally so the user knows which field to fix.
		if ( handleConfirmEmail( formData, { silent: true } ) ) {
			return null;
		}
		const missingEmail = ! ( formData.email_to || '' ).trim();
		const missingSubject = ! ( formData.subject || '' ).trim();
		if ( missingEmail && missingSubject ) {
			return __(
				'Please provide a recipient email address and subject line.',
				'sureforms'
			);
		}
		if ( missingEmail ) {
			return __(
				'Please provide a recipient email address.',
				'sureforms'
			);
		}
		return __( 'Please provide a subject line.', 'sureforms' );
	};

	const onSaveSuccess = () => setPrevData( formData );

	// Resolve filter-driven extension panels for the Email Notification
	// editor. Seed the array with our own `is-raw-format` entry so pro
	// callbacks that anchor relative to it (e.g. AttachUploads splices
	// after `is-raw-format`) keep their existing behaviour. Then split
	// the result by id into two render zones:
	//
	//   - `inlineExtensions` — rendered between Email Body and From
	//     Name (matches the position those entries had in master's
	//     single-form options array). Includes `is-raw-format` itself
	//     plus anything anchored to it.
	//   - `trailingExtensions` — rendered after Reply To. Catches
	//     panels that were pushed to the end of the array in master
	//     (e.g. `email-conditional-logic` = "Add Trigger Conditions").
	//
	// The id allowlist below is the explicit set known to anchor near
	// `is-raw-format`. Anything else defaults to the trailing zone —
	// that matches master's "pushed to end" default for plain
	// `.push()` filter callbacks.
	const INLINE_EXTENSION_IDS = [ 'is-raw-format', 'attach-uploads' ];
	const filteredEmailSettings = applyFilters(
		'srfm.singleFormEmail.settings',
		[
			{
				id: 'is-raw-format',
				component: (
					<div key="is-raw-format" className="space-y-3">
						<Switch
							size="sm"
							label={ {
								heading: __(
									'Send as Raw HTML',
									'sureforms'
								),
								description: __(
									'When enabled, the email body HTML will be preserved exactly as written and wrapped in a professional email template.',
									'sureforms'
								),
							} }
							checked={ formData.is_raw_format }
							onChange={ ( checked ) =>
								setFormData( ( prev ) => ( {
									...prev,
									is_raw_format: checked,
								} ) )
							}
						/>
						{ formData.is_raw_format && (
							<ModalWarning
								message={ __(
									'Smart tags that reference user-submitted fields will not be escaped in raw HTML mode. Avoid inserting untrusted field values directly into the email body.',
									'sureforms'
								) }
							/>
						) }
					</div>
				),
			},
		],
		{
			...props,
			formData,
			setFormData,
			// Filter-driven extension panels (e.g. pro AttachUploads,
			// EmailConditionalLogic) push their own dirty boolean via
			// this callback so the host Save button activates on
			// extension edits. Stable reference so the consumer
			// effect deps don't churn.
			registerExtensionDirty,
		}
	);
	const inlineExtensions = filteredEmailSettings.filter( ( option ) =>
		INLINE_EXTENSION_IDS.includes( option.id )
	);
	const trailingExtensions = filteredEmailSettings.filter(
		( option ) => ! INLINE_EXTENSION_IDS.includes( option.id )
	);

	return (
		<TabContentWrapper
			title={ __( 'Email Notifications', 'sureforms' ) }
			onClickBack={ onClickBack }
			shouldShowAutoSaveText={ true }
			tabId="email_notification"
			showSaveButton={ true }
			validate={ validateBeforeSave }
			isDirty={ isDirty }
			isSaving={ isSaving }
			onSavingChange={ setIsSaving }
			onSaveSuccess={ onSaveSuccess }
		>
			<Container direction="column" className="gap-4 px-2">
				<EmailNotificationFields
					context="form"
					values={ formData }
					onChange={ handleChange }
					showNameField={ true }
					showAllDataTag={ true }
					dynamicSubject={ dynamicSubject }
					setDynamicSubject={ setDynamicSubject }
					onValidationError={ setHasValidationErrors }
					// `is-raw-format` + anchored extensions (e.g.
					// pro AttachUploads which splices after it)
					// render between Email Body and From Name —
					// matches master's single-form options
					// ordering.
					renderAfterEmailBody={ inlineExtensions.map(
						( option ) => option.component
					) }
					// Trailing extension panels — pushed to the
					// end of the filter array (e.g. pro
					// EmailConditionalLogic = "Add Trigger
					// Conditions") render after Reply To.
					renderAfterFields={ trailingExtensions.map(
						( option ) => option.component
					) }
				/>
			</Container>
		</TabContentWrapper>
	);
};

export default EmailConfirmation;
