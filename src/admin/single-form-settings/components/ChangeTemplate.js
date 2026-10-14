import { __, sprintf } from '@wordpress/i18n';
import {
	createPortal,
	useCallback,
	useEffect,
	useLayoutEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { select as selectStore, useDispatch, useSelect } from '@wordpress/data';
import { cloneBlock, parse } from '@wordpress/blocks';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import { store as noticesStore } from '@wordpress/notices';
import { Button, Modal, Notice, Spinner } from '@wordpress/components';
import { LayoutTemplate, X } from 'lucide-react';
import {
	TemplateGrid,
	TemplatePreview,
} from '@Admin/components/template-picker/components/TemplateLibrary';
import {
	createFormFromTemplate,
	fetchTemplateContent,
	fetchTemplates,
	recordTemplateSwap,
} from '@Utils/form-templates';

/**
 * The editor's Tailwind styles apply inside #srfm-dialog-root, so the
 * template screens render there.
 *
 * @return {HTMLElement|null} Portal root.
 */
const usePortalRoot = () => {
	const [ root, setRoot ] = useState( null );
	useLayoutEffect( () => {
		let element = document.getElementById( 'srfm-dialog-root' );
		if ( ! element ) {
			element = document.createElement( 'div' );
			element.id = 'srfm-dialog-root';
			document.body.appendChild( element );
		}
		setRoot( element );
	}, [] );
	return root;
};

/**
 * "Change template" in the form editor header. Opens the same template
 * library as the Add New Form screen: live previews, and a full preview with
 * the Customize panel. Using a template replaces the open form's fields and
 * design with exactly what was previewed.
 *
 * Existing entries and integrations are keyed to the form's fields, so a form
 * that already has entries is offered a new form instead. Otherwise the user
 * confirms before their form is replaced, and the change can be undone.
 */
const ChangeTemplate = () => {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ library, setLibrary ] = useState( null );
	const [ loadError, setLoadError ] = useState( '' );
	const [ previewId, setPreviewId ] = useState( '' );
	const [ busy, setBusy ] = useState( false );
	// The template waiting on the user:
	// { template, style, content, styling, instantForm, hasEntries }.
	const [ pending, setPending ] = useState( null );
	const portalRoot = usePortalRoot();
	// The admin bar stays visible over the editor, so the screens start below it.
	const [ adminBarHeight, setAdminBarHeight ] = useState( 0 );

	const { postId, title, rootClientIds, meta, isSaving, isAutosaving } =
		useSelect( ( select ) => {
			const editor = select( editorStore );
			return {
				postId: editor.getCurrentPostId(),
				title: editor.getEditedPostAttribute( 'title' ),
				meta: editor.getEditedPostAttribute( 'meta' ) || {},
				rootClientIds: select( blockEditorStore ).getBlockOrder(),
				isSaving: editor.isSavingPost(),
				isAutosaving: editor.isAutosavingPost(),
			};
		}, [] );

	// A swap counts once the form is saved with it, not when it is applied:
	// an undone or abandoned swap is not recorded.
	const unsavedSwap = useRef( '' );
	const wasSaving = useRef( false );
	useEffect( () => {
		const justSaved = wasSaving.current && ! isSaving;
		wasSaving.current = isSaving && ! isAutosaving;
		if (
			! justSaved ||
			! unsavedSwap.current ||
			! selectStore( editorStore ).didPostSaveRequestSucceed()
		) {
			return;
		}
		recordTemplateSwap( postId, unsavedSwap.current ).catch( () => {} );
		unsavedSwap.current = '';
	}, [ isSaving, isAutosaving, postId ] );

	const { replaceBlocks, insertBlocks } = useDispatch( blockEditorStore );
	const { editPost } = useDispatch( editorStore );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	const templates = library?.templates || [];
	const preview = templates.find( ( t ) => t.id === previewId );

	const open = async () => {
		setAdminBarHeight(
			document.getElementById( 'wpadminbar' )?.offsetHeight || 0
		);
		setIsOpen( true );
		if ( library ) {
			return;
		}
		try {
			setLibrary( await fetchTemplates() );
		} catch ( error ) {
			setLoadError(
				error?.message ||
					__( 'Templates could not be loaded.', 'sureforms' )
			);
		}
	};

	const close = useCallback( () => {
		setIsOpen( false );
		setPreviewId( '' );
		setPending( null );
		setBusy( false );
	}, [] );

	const closePreview = useCallback( () => setPreviewId( '' ), [] );

	// Escape closes the library when no preview or confirmation is on top.
	useEffect( () => {
		if ( ! isOpen || previewId || pending ) {
			return;
		}
		const onKeyDown = ( event ) => {
			if ( 'Escape' === event.key ) {
				close();
			}
		};
		document.addEventListener( 'keydown', onKeyDown );
		return () => document.removeEventListener( 'keydown', onKeyDown );
	}, [ isOpen, previewId, pending, close ] );

	const applyTemplate = ( {
		template,
		content,
		styling,
		instantForm,
		settings,
	} ) => {
		const blocks = parse( content );

		// Keep copies to restore. The editor's own undo does not reliably
		// revert a whole-form block replacement here, so Undo puts these back.
		const previousBlocks = selectStore( blockEditorStore )
			.getBlocks()
			.map( ( block ) => cloneBlock( block ) );
		const previousTitle = title;
		const previousMeta = {
			_srfm_forms_styling: meta._srfm_forms_styling,
			_srfm_instant_form_settings: meta._srfm_instant_form_settings,
			_srfm_single_page_form_title: meta._srfm_single_page_form_title,
		};
		// The template's button text, emails, thank-you message and steps.
		// Only keys the editor knows about are applied.
		const templateSettings = Object.fromEntries(
			Object.entries( settings || {} ).filter( ( [ key ] ) =>
				Object.prototype.hasOwnProperty.call( meta, key )
			)
		);
		Object.keys( templateSettings ).forEach( ( key ) => {
			previousMeta[ key ] = meta[ key ];
		} );

		// Keep a title the user typed; replace one that came from a template.
		const changeTitle =
			title !== template.title &&
			( ! title || templates.some( ( t ) => t.title === title ) );
		if ( changeTitle ) {
			editPost( { title: template.title } );
		}

		if ( rootClientIds.length ) {
			replaceBlocks( rootClientIds, blocks );
		} else {
			insertBlocks( blocks );
		}

		// The design the user previewed: the styling the template sets, over
		// the form's own, and the banner on the form's own page. Other page
		// settings stay as they are.
		editPost( {
			meta: {
				...templateSettings,
				_srfm_forms_styling: {
					...( meta._srfm_forms_styling || {} ),
					...styling,
				},
				_srfm_instant_form_settings: {
					...( meta._srfm_instant_form_settings || {} ),
					...instantForm,
				},
				_srfm_single_page_form_title: 0,
			},
		} );

		unsavedSwap.current = template.id;

		createSuccessNotice(
			sprintf(
				/* translators: %s: template name. */
				__( 'Form replaced with the %s template.', 'sureforms' ),
				template.title
			),
			{
				type: 'snackbar',
				actions: [
					{
						label: __( 'Undo', 'sureforms' ),
						onClick: () => {
							replaceBlocks(
								selectStore( blockEditorStore ).getBlockOrder(),
								previousBlocks
							);
							editPost( { meta: previousMeta } );
							unsavedSwap.current = '';
							if ( changeTitle ) {
								editPost( { title: previousTitle } );
							}
						},
					},
				],
			}
		);
		close();
	};

	const chooseTemplate = async ( template, style = {} ) => {
		if ( busy ) {
			return;
		}

		if ( template.is_locked ) {
			window.open(
				srfm_block_data?.upgrade_url ||
					'https://sureforms.com/pricing/',
				'_blank',
				'noopener,noreferrer'
			);
			return;
		}

		setBusy( true );
		try {
			const response = await fetchTemplateContent(
				template.id,
				postId,
				style
			);
			setBusy( false );

			const next = {
				template,
				style,
				content: response.content,
				styling: response.styling,
				instantForm: response.instant_form || {},
				settings: response.settings || {},
				hasEntries: Boolean( response?.has_entries ),
			};

			if ( next.hasEntries || rootClientIds.length ) {
				setPending( next );
				return;
			}

			applyTemplate( next );
		} catch ( error ) {
			setBusy( false );
			createErrorNotice(
				error?.message ||
					__( 'The template could not be loaded.', 'sureforms' ),
				{ type: 'snackbar' }
			);
		}
	};

	const createNewForm = async ( { template, style } ) => {
		setBusy( true );
		try {
			const response = await createFormFromTemplate( template.id, style );
			if ( response?.edit_url ) {
				window.location.href = response.edit_url;
				return;
			}
			throw new Error();
		} catch ( error ) {
			setBusy( false );
			createErrorNotice(
				error?.message ||
					__(
						'The form could not be created. Please try again.',
						'sureforms'
					),
				{ type: 'snackbar' }
			);
		}
	};

	const renderConfirm = () => {
		const { template, hasEntries } = pending;

		return (
			<Modal
				title={ template.title }
				onRequestClose={ () => setPending( null ) }
				className="srfm-change-template-modal"
				size="medium"
			>
				<div className="srfm-change-template__confirm">
					{ hasEntries ? (
						<>
							<Notice status="warning" isDismissible={ false }>
								{ __(
									'This form already has entries. Replacing its fields would separate those entries and any connected integrations from the new fields.',
									'sureforms'
								) }
							</Notice>
							<p>
								{ sprintf(
									/* translators: %s: template name. */
									__(
										'Create a new form from the %s template, with the design you chose, instead. This form stays as it is.',
										'sureforms'
									),
									template.title
								) }
							</p>
						</>
					) : (
						<p>
							{ sprintf(
								/* translators: %s: template name. */
								__(
									'Replace this form\'s fields and design with the %s template as you previewed it? You can undo this straight after.',
									'sureforms'
								),
								template.title
							) }
						</p>
					) }
					<div className="srfm-change-template__actions">
						<Button
							variant="tertiary"
							onClick={ () => setPending( null ) }
						>
							{ __( 'Back', 'sureforms' ) }
						</Button>
						{ hasEntries ? (
							<Button
								variant="primary"
								isBusy={ busy }
								disabled={ busy }
								onClick={ () => createNewForm( pending ) }
							>
								{ __( 'Create new form', 'sureforms' ) }
							</Button>
						) : (
							<Button
								variant="primary"
								onClick={ () => applyTemplate( pending ) }
							>
								{ __( 'Replace form', 'sureforms' ) }
							</Button>
						) }
					</div>
				</div>
			</Modal>
		);
	};

	const renderLibrary = () => {
		if ( loadError ) {
			return (
				<Notice status="error" isDismissible={ false }>
					{ loadError }
				</Notice>
			);
		}

		if ( ! library ) {
			return (
				<div className="flex justify-center py-16">
					<Spinner />
				</div>
			);
		}

		return (
			<TemplateGrid
				templates={ templates }
				categories={ library.categories || [] }
				onSelect={ setPreviewId }
				requests={ library.requests }
			>
				<p className="m-0 text-sm text-text-secondary">
					{ __(
						'Preview a template and customize its design. Using it replaces this form\'s fields and design.',
						'sureforms'
					) }
				</p>
			</TemplateGrid>
		);
	};

	return (
		<>
			<Button
				className="srfm-change-template-button"
				variant="tertiary"
				icon={ <LayoutTemplate size={ 18 } aria-hidden="true" /> }
				label={ __( 'Change template', 'sureforms' ) }
				showTooltip
				onClick={ open }
			/>
			{ isOpen &&
				portalRoot &&
				createPortal(
					<div
						className="fixed inset-0 z-[99998] flex flex-col bg-background-secondary"
						style={ { top: adminBarHeight } }
						role="dialog"
						aria-modal="true"
						aria-labelledby="srfm-change-template-title"
					>
						<div className="flex items-center justify-between gap-4 px-6 py-3 bg-background-primary border-0 border-b border-solid border-border-subtle">
							<h2
								id="srfm-change-template-title"
								className="m-0 text-base font-semibold text-text-primary"
							>
								{ __( 'Change template', 'sureforms' ) }
							</h2>
							<Button
								variant="tertiary"
								icon={ <X size={ 18 } aria-hidden="true" /> }
								label={ __( 'Close', 'sureforms' ) }
								onClick={ close }
							/>
						</div>
						<div className="flex-1 overflow-y-auto px-8 py-8">
							<div className="max-w-[1200px] mx-auto">
								{ renderLibrary() }
							</div>
						</div>
						{ preview && (
							<TemplatePreview
								key={ preview.id }
								template={ preview }
								isCreating={ busy }
								presets={ library?.themes }
								top={ adminBarHeight }
								onUse={ chooseTemplate }
								onClose={ closePreview }
							/>
						) }
					</div>,
					portalRoot
				) }
			{ pending && renderConfirm() }
		</>
	);
};

export default ChangeTemplate;
