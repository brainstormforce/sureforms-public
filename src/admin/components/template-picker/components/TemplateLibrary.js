import { __, sprintf, _n } from '@wordpress/i18n';
import {
	useCallback,
	useEffect,
	useMemo,
	useState,
} from '@wordpress/element';
import { Button, Checkbox, Toaster, toast } from '@bsf/force-ui';
import {
	ArrowLeft,
	ArrowRight,
	Check,
	Crown,
	ExternalLink,
	Eye,
	FileText,
	ImageOff,
	Info,
	Lock,
	Mail,
	MessageSquarePlus,
	Monitor,
	Palette,
	PartyPopper,
	Search,
	Smartphone,
	Wrench,
	X,
} from 'lucide-react';
import { cn } from '@Utils/Helpers';
import {
	createFormFromTemplate,
	recordEmptySearch,
	requestTemplate,
	trackTemplateAction,
} from '@Utils/form-templates';
import Header from './Header.js';
import TemplateStylePanel from './TemplateStylePanel.js';
import {
	FeatureChip,
	IncludedFeatures,
	PremiumBadge,
	getCardImage,
	getFeatures,
	getLockLabels,
	getMissingSetup,
	isPremiumTemplate,
	openTemplateUnlock,
	showsPreviewImage,
} from './template-features.js';
import useFocusTrap from '../use-focus-trap.js';
import { resolveLook } from '../template-colors.js';
import {
	EmailsPanel,
	ThankYouPanel,
	buildMessageChanges,
	getMessageDefaults,
	hasMessages,
} from './TemplateMessages.js';

// Wait this long after the last change before rendering the preview again.
const RESTYLE_DELAY = 250;

// A preview that hasn't loaded after this long is treated as blocked.
const PREVIEW_TIMEOUT = 10000;

// Below this width the Customize panel opens as a bottom sheet.
const NARROW_QUERY = '(max-width: 1023px)';

/**
 * Preview URL for a template with a chosen style.
 *
 * @param {Object} template Template.
 * @param {Object} style    Chosen style.
 * @return {string} URL.
 */
const styledPreviewUrl = ( template, style ) =>
	Object.keys( style ).length
		? `${ template.preview_url }&srfm_style=${ encodeURIComponent(
			JSON.stringify( style )
		  ) }`
		: template.preview_url;

/**
 * Whether a loaded preview frame shows something other than the preview page,
 * e.g. a PHP error, a login screen, or the browser's "refused to connect"
 * page. A frame from another origin can't be read, so it is trusted.
 *
 * @param {HTMLIFrameElement} iframe Frame that fired `load`.
 * @return {boolean} The preview is broken.
 */
const isPreviewBroken = ( iframe ) => {
	let sameOrigin = false;
	try {
		sameOrigin =
			new URL( iframe.src, window.location.href ).origin ===
			window.location.origin;
	} catch ( error ) {
		return false;
	}
	let doc = null;
	try {
		doc = iframe.contentDocument;
	} catch ( error ) {
		doc = null;
	}
	if ( ! doc ) {
		// Same-origin pages are readable, so no document means the browser
		// replaced it with its own error page. Not in a cross-origin isolated
		// page though (the block editor sends Document-Isolation-Policy):
		// there even a working same-origin frame can't be read.
		return sameOrigin && ! window.crossOriginIsolated;
	}
	if ( 'about:blank' === doc.location?.href ) {
		return false;
	}
	return ! doc.querySelector( '.srfm-template-preview' );
};

/**
 * Whether a media query matches, kept up to date.
 *
 * @param {string} query Media query.
 * @return {boolean} Matches.
 */
const useMediaQuery = ( query ) => {
	const getMatch = () =>
		typeof window !== 'undefined' && window.matchMedia
			? window.matchMedia( query ).matches
			: false;
	const [ matches, setMatches ] = useState( getMatch );

	useEffect( () => {
		if ( ! window.matchMedia ) {
			return;
		}
		const list = window.matchMedia( query );
		const onChange = () => setMatches( list.matches );
		onChange();
		list.addEventListener?.( 'change', onChange );
		return () => list.removeEventListener?.( 'change', onChange );
	}, [ query ] );

	return matches;
};

/**
 * False on the first paint, true right after, to run an enter transition.
 *
 * @return {boolean} Entered.
 */
const useEntered = () => {
	const [ entered, setEntered ] = useState( false );
	useEffect( () => {
		const frame = window.requestAnimationFrame( () => setEntered( true ) );
		return () => window.cancelAnimationFrame( frame );
	}, [] );
	return entered;
};

/**
 * Placeholder shaped like a form, shown while a preview loads.
 *
 * @param {Object} props           Props.
 * @param {string} props.className Extra classes.
 */
const FormSkeleton = ( { className } ) => (
	<span
		className={ cn(
			'absolute inset-0 flex flex-col gap-4 p-8 bg-background-primary animate-pulse motion-reduce:animate-none',
			className
		) }
		aria-hidden="true"
	>
		<span className="block h-5 w-2/5 rounded bg-background-tertiary" />
		{ [ 0, 1, 2 ].map( ( row ) => (
			<span key={ row } className="flex flex-col gap-2">
				<span className="block h-3 w-1/4 rounded bg-background-tertiary" />
				<span className="block h-9 w-full rounded-md bg-background-secondary border border-solid border-border-subtle" />
			</span>
		) ) }
		<span className="block h-9 w-1/3 mt-2 rounded-md bg-background-tertiary" />
	</span>
);

const THUMB_HEIGHT = 288;

/**
 * Card thumbnail: a picture of the template. Cards never load the live
 * preview page, which is a full front-end request; only the opened preview
 * does. A template without a picture, or whose picture doesn't load, shows a
 * plain form outline.
 *
 * @param {Object} props          Props.
 * @param {Object} props.template Template.
 */
export const TemplateThumbnail = ( { template } ) => {
	const [ imageFailed, setImageFailed ] = useState( false );
	const image = getCardImage( template );
	return (
		<span
			className="relative block w-full overflow-hidden bg-background-primary"
			style={ { height: THUMB_HEIGHT } }
			aria-hidden="true"
		>
			{ image && ! imageFailed ? (
				<img
					src={ image }
					alt=""
					loading="lazy"
					decoding="async"
					onError={ () => setImageFailed( true ) }
					className="block w-full h-full object-cover object-top"
				/>
			) : (
				<FormSkeleton />
			) }
		</span>
	);
};

/**
 * Bottom sheet holding the Customize panel on narrow screens.
 *
 * @param {Object}   props          Props.
 * @param {Function} props.onClose  Close the sheet.
 * @param {*}        props.children The panel.
 */
const CustomizeSheet = ( { onClose, children } ) => {
	const entered = useEntered();
	return (
		<>
			<div
				className={ cn(
					'absolute inset-0 z-30 bg-black/30 transition-opacity duration-200 motion-reduce:transition-none',
					entered ? 'opacity-100' : 'opacity-0'
				) }
				onClick={ onClose }
				aria-hidden="true"
			/>
			<div
				className={ cn(
					'absolute inset-x-0 bottom-0 z-40 flex max-h-[75%] transition-transform duration-200 ease-out motion-reduce:transition-none',
					entered ? 'translate-y-0' : 'translate-y-full'
				) }
			>
				{ children }
			</div>
		</>
	);
};

/**
 * "Form | Thank-you page | Emails" switch above the preview. A tablist with
 * arrow-key navigation; only the selected tab is in the tab order.
 *
 * @param {Object}   props          Props.
 * @param {Array}    props.tabs     Tabs: { id, label, Icon }.
 * @param {string}   props.selected Selected tab id.
 * @param {Function} props.onSelect Called with a tab id.
 */
const PreviewTabs = ( { tabs, selected, onSelect } ) => {
	const onKeyDown = ( event ) => {
		const index = tabs.findIndex( ( tab ) => tab.id === selected );
		const next = {
			ArrowRight: index + 1,
			ArrowLeft: index - 1,
			Home: 0,
			End: tabs.length - 1,
		}[ event.key ];
		if ( undefined === next ) {
			return;
		}
		event.preventDefault();
		const tab = tabs[ ( next + tabs.length ) % tabs.length ];
		onSelect( tab.id );
		event.currentTarget.ownerDocument
			.getElementById( `srfm-template-tab-${ tab.id }` )
			?.focus();
	};

	return (
		<div
			role="tablist"
			aria-label={ __( 'What to preview', 'sureforms' ) }
			className="flex max-w-full gap-1 p-1 rounded-lg bg-background-tertiary overflow-x-auto"
		>
			{ tabs.map( ( { id, label, Icon } ) => (
				<button
					key={ id }
					id={ `srfm-template-tab-${ id }` }
					type="button"
					role="tab"
					aria-selected={ selected === id }
					aria-controls={ `srfm-template-tabpanel-${ id }` }
					tabIndex={ selected === id ? 0 : -1 }
					onClick={ () => onSelect( id ) }
					onKeyDown={ onKeyDown }
					className={ cn(
						'flex items-center gap-1.5 px-3 py-1.5 text-xs sm:text-sm font-medium whitespace-nowrap rounded-md border-0 cursor-pointer transition-colors motion-reduce:transition-none',
						'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-border-interactive',
						selected === id
							? 'bg-background-primary text-text-primary shadow-sm'
							: 'bg-transparent text-text-secondary hover:text-text-primary'
					) }
				>
					<Icon className="size-4" aria-hidden="true" />
					{ label }
				</button>
			) ) }
		</div>
	);
};

/**
 * Full-screen preview of one template, rendered exactly as it will look on
 * the site, with the action to create a form from it.
 *
 * @param {Object}   props                     Props.
 * @param {Object}   props.template            Template.
 * @param {boolean}  props.isCreating          Whether the form is being created.
 * @param {Function} props.onUse               Use the template: ( template, style, { enableSpamProtection, messages } ).
 * @param {Function} props.onClose             Close the preview.
 * @param {Array}    props.presets             Style presets for the Customize panel.
 * @param {number}   props.top                 Space to leave above, e.g. for the admin bar.
 * @param {boolean}  props.offerSpamProtection Offer to turn on the site-wide honeypot
 *                                             while creating. Off by default.
 * @param {boolean}  props.spamProtectionOff   The site-wide honeypot is off, so the
 *                                             visitor email is held back unless it
 *                                             is turned on while creating.
 * @param {boolean}  props.editMessages        Let the thank-you message and emails be
 *                                             edited before creating. Otherwise they
 *                                             are only shown.
 */
export const TemplatePreview = ( {
	template,
	isCreating,
	onUse,
	onClose,
	presets,
	top = 0,
	offerSpamProtection = false,
	spamProtectionOff = offerSpamProtection,
	editMessages = false,
} ) => {
	const isNarrow = useMediaQuery( NARROW_QUERY );
	const entered = useEntered();
	const [ device, setDevice ] = useState( 'desktop' );
	const [ style, setStyle ] = useState( {} );
	const [ showPanel, setShowPanel ] = useState( () => ! isNarrow );
	const [ protectFromSpam, setProtectFromSpam ] = useState( true );
	// 'form', 'thank-you' or 'emails'.
	const [ tab, setTab ] = useState( 'form' );
	// Message text changed from the template's, by key. Kept across tabs.
	const [ messageEdits, setMessageEdits ] = useState( {} );
	// Two frames: the new render loads behind the shown one and swaps in when
	// ready, so restyling never flashes an empty page.
	const [ frames, setFrames ] = useState( [ template.preview_url, '' ] );
	const [ shown, setShown ] = useState( 0 );
	// 'loading', 'ready' or 'failed'.
	const [ status, setStatus ] = useState( 'loading' );
	// A locked template's picture that doesn't load falls back to the live preview.
	const [ imageFailed, setImageFailed ] = useState( false );
	const showImage = showsPreviewImage( template ) && ! imageFailed;
	const [ dialogNode, setDialogNode ] = useState( null );

	useEffect( () => {
		trackTemplateAction( 'preview', template.id );
	}, [ template.id ] );
	// The card that opened the preview gets focus back on close. Read before
	// the dialog exists, so there is no node to take ownerDocument from yet.
	const [ opener ] = useState( () =>
		// eslint-disable-next-line @wordpress/no-global-active-element
		typeof document !== 'undefined' ? document.activeElement : null
	);

	useFocusTrap( dialogNode );

	useEffect( () => {
		if ( isNarrow ) {
			setShowPanel( false );
		}
	}, [ isNarrow ] );

	useEffect( () => {
		const url = styledPreviewUrl( template, style );
		const timer = setTimeout( () => {
			setFrames( ( current ) => {
				if ( current[ shown ] === url ) {
					return current;
				}
				const next = [ ...current ];
				next[ 1 - shown ] = url;
				return next;
			} );
		}, RESTYLE_DELAY );
		return () => clearTimeout( timer );
	}, [ style, template, shown ] );

	useEffect( () => {
		if ( showImage ) {
			return;
		}
		const timer = setTimeout(
			() =>
				setStatus( ( current ) =>
					'loading' === current ? 'failed' : current
				),
			PREVIEW_TIMEOUT
		);
		return () => clearTimeout( timer );
	}, [ showImage ] );

	useEffect( () => {
		dialogNode?.focus();
	}, [ dialogNode ] );

	useEffect(
		() => () => {
			if ( opener?.isConnected && 'function' === typeof opener.focus ) {
				opener.focus();
			}
		},
		[ opener ]
	);

	useEffect( () => {
		const onKeyDown = ( event ) => {
			if ( 'Escape' !== event.key ) {
				return;
			}
			// In a text field, Escape leaves the field first, so typing a
			// message is not lost to a stray key.
			const field = event.target;
			if (
				field?.matches?.( 'input[type="text"], textarea' ) &&
				dialogNode?.contains( field )
			) {
				dialogNode.focus();
				return;
			}
			onClose();
		};
		document.addEventListener( 'keydown', onKeyDown );
		return () => document.removeEventListener( 'keydown', onKeyDown );
	}, [ onClose, dialogNode ] );

	const devices = [
		{
			id: 'desktop',
			label: __( 'Desktop', 'sureforms' ),
			Icon: Monitor,
		},
		{
			id: 'mobile',
			label: __( 'Mobile', 'sureforms' ),
			Icon: Smartphone,
		},
	];

	const canCustomize = ! template.is_locked;
	const lockLabels = getLockLabels( template );
	const missingSetup = getMissingSetup( template );
	// The live preview of a locked template leaves out the fields this site
	// can't show; say so, unless its picture is shown instead.
	const showMissingNotice =
		template.is_locked && ! showImage && template.preview_missing > 0;
	const showSpamOption = offerSpamProtection && ! template.is_locked;
	// The server only sets up the visitor email while spam protection is on.
	const holdsVisitorEmail =
		spamProtectionOff && ! ( showSpamOption && protectFromSpam );
	const panelId = 'srfm-template-customize-panel';

	const showMessages = hasMessages( template );
	const canEditMessages = editMessages && showMessages && canCustomize;
	const messageValues = useMemo(
		() => ( {
			...getMessageDefaults( template.messages ),
			...messageEdits,
		} ),
		[ template.messages, messageEdits ]
	);
	const setMessage = useCallback(
		( key, value ) =>
			setMessageEdits( ( current ) => ( {
				...current,
				[ key ]: value,
			} ) ),
		[]
	);
	const tabs = [
		{ id: 'form', label: __( 'Form', 'sureforms' ), Icon: FileText },
		{
			id: 'thank-you',
			label: __( 'Thank-you page', 'sureforms' ),
			Icon: PartyPopper,
		},
		{ id: 'emails', label: __( 'Emails', 'sureforms' ), Icon: Mail },
	];
	const activeTab = showMessages ? tab : 'form';
	const tabPanelProps = ( id ) =>
		showMessages
			? {
				id: `srfm-template-tabpanel-${ id }`,
				role: 'tabpanel',
				'aria-labelledby': `srfm-template-tab-${ id }`,
				hidden: activeTab !== id,
			  }
			: {};

	const use = () =>
		onUse( template, style, {
			enableSpamProtection: showSpamOption && protectFromSpam,
			messages: canEditMessages
				? buildMessageChanges( template.messages, messageValues )
				: undefined,
		} );

	// Colors don't change emails, so the Customize panel steps aside there.
	const showCustomize = canCustomize && 'emails' !== activeTab;
	const panel = showCustomize && showPanel && (
		<TemplateStylePanel
			id={ panelId }
			style={ style }
			theme={ template.theme }
			presets={ presets }
			onChange={ setStyle }
			className={
				isNarrow
					? 'w-full max-w-none rounded-t-xl border-l-0 border-t shadow-2xl'
					: undefined
			}
			onClose={ isNarrow ? () => setShowPanel( false ) : undefined }
		/>
	);

	return (
		<div
			ref={ setDialogNode }
			tabIndex="-1"
			className={ cn(
				'fixed inset-0 z-[99999] flex flex-col bg-background-secondary focus:outline-none',
				'transition-[opacity,transform] duration-200 ease-out motion-reduce:transition-none motion-reduce:transform-none',
				entered
					? 'opacity-100 translate-y-0'
					: 'opacity-0 translate-y-2'
			) }
			style={ top ? { top } : undefined }
			role="dialog"
			aria-modal="true"
			aria-labelledby="srfm-template-preview-title"
			aria-describedby="srfm-template-preview-description"
		>
			<div className="flex items-center gap-2 sm:gap-4 px-3 sm:px-5 py-3 bg-background-primary border-0 border-b border-solid border-border-subtle">
				<Button
					variant="ghost"
					size="sm"
					icon={ <ArrowLeft className="size-4" /> }
					aria-label={ __( 'All templates', 'sureforms' ) }
					onClick={ onClose }
				>
					<span className="hidden sm:inline">
						{ __( 'All templates', 'sureforms' ) }
					</span>
				</Button>
				<div className="flex flex-col min-w-0 flex-1">
					<div className="flex items-center gap-2 min-w-0">
						<h2
							id="srfm-template-preview-title"
							className="m-0 text-base font-semibold text-text-primary truncate"
						>
							{ template.title }
						</h2>
						{ template.is_locked && (
							<PremiumBadge
								template={ template }
								className="shrink-0"
							/>
						) }
					</div>
					<span
						id="srfm-template-preview-description"
						className="hidden sm:block text-xs text-text-secondary truncate"
					>
						{ template.description }
					</span>
				</div>
				<div
					className={ cn(
						'hidden gap-1 p-1 rounded-md bg-background-secondary',
						'emails' !== activeTab && 'md:flex'
					) }
					role="group"
					aria-label={ __( 'Preview size', 'sureforms' ) }
				>
					{ devices.map( ( { id, label, Icon } ) => (
						<button
							key={ id }
							type="button"
							aria-pressed={ device === id }
							aria-label={ label }
							title={ label }
							onClick={ () => setDevice( id ) }
							className={ cn(
								'flex items-center justify-center p-1.5 rounded border-0 cursor-pointer transition-colors',
								'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-border-interactive',
								device === id
									? 'bg-background-primary text-icon-primary shadow-sm'
									: 'bg-transparent text-icon-secondary hover:text-icon-primary'
							) }
						>
							<Icon className="size-4" aria-hidden="true" />
						</button>
					) ) }
				</div>
				{ showCustomize && (
					<Button
						variant={ showPanel ? 'secondary' : 'outline' }
						size="sm"
						aria-expanded={ showPanel }
						aria-controls={ showPanel ? panelId : undefined }
						icon={ <Palette className="size-4" /> }
						onClick={ () => setShowPanel( ( open ) => ! open ) }
					>
						{ __( 'Customize', 'sureforms' ) }
					</Button>
				) }
				<Button
					variant="ghost"
					size="sm"
					aria-label={ __( 'Close preview', 'sureforms' ) }
					icon={ <X className="size-4" /> }
					onClick={ onClose }
				/>
			</div>
			<div className="relative flex-1 min-h-0 flex">
				<div className="flex-1 min-w-0 flex flex-col">
					{ showMessages && (
						<div className="flex justify-center px-3 sm:px-6 pt-3 sm:pt-4">
							<PreviewTabs
								tabs={ tabs }
								selected={ activeTab }
								onSelect={ setTab }
							/>
						</div>
					) }
					{ showMissingNotice && 'form' === activeTab && (
						<div className="flex justify-center px-3 sm:px-6 pt-3 sm:pt-4">
							<p
								className="m-0 flex items-start gap-1.5 px-3 py-1.5 text-xs text-text-secondary rounded-md bg-background-primary border border-solid border-border-subtle"
								role="note"
							>
								<Info
									className="size-3.5 shrink-0 mt-px text-icon-secondary"
									aria-hidden="true"
								/>
								{ __(
									'Some fields in this template need SureForms Premium, so this preview leaves them out.',
									'sureforms'
								) }
							</p>
						</div>
					) }
					<div
						{ ...tabPanelProps( 'form' ) }
						className={ cn(
							'flex-1 min-h-0 flex justify-center p-3 sm:p-6 overflow-hidden',
							'form' !== activeTab && 'hidden'
						) }
					>
						<div
							className={ cn(
								'relative h-full transition-[width,max-width] duration-300 motion-reduce:transition-none',
								'mobile' === device
									? 'w-full max-w-[390px]'
									: 'w-full max-w-[1100px]'
							) }
						>
							{ showImage && (
								<>
									<div
										// Scrolls with the keyboard too, like the live preview.
										// eslint-disable-next-line jsx-a11y/no-noninteractive-tabindex
										tabIndex={ 0 }
										className="absolute inset-0 overflow-y-auto rounded-lg border border-solid border-border-subtle bg-background-primary shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-border-interactive"
									>
										<img
											src={ template.preview_image }
											alt={ sprintf(
												/* translators: %s: template name. */
												__(
													'Preview of the %s form',
													'sureforms'
												),
												template.title
											) }
											decoding="async"
											onError={ () => setImageFailed( true ) }
											// At the form's own width (pictures are taken at 522px), centered like the live preview, never stretched.
											className="block w-full max-w-[570px] h-auto mx-auto px-6 py-10"
										/>
									</div>
									<span className="absolute top-3 left-1/2 -translate-x-1/2 z-10 inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium whitespace-nowrap rounded-full bg-background-inverse/90 text-text-on-color shadow-md">
										<Crown
											className="size-3.5"
											aria-hidden="true"
										/>
										{ __(
											'Preview of the Premium version',
											'sureforms'
										) }
									</span>
								</>
							) }
							{ ! showImage &&
								frames.map(
									( src, index ) =>
										!! src && (
											<iframe
											// One element per slot, so React keeps the shown
											// frame while the other one reloads.
												key={ index }
												src={ src }
												title={ sprintf(
												/* translators: %s: template name. */
													__(
														'Preview of the %s form',
														'sureforms'
													),
													template.title
												) }
												aria-hidden={ index !== shown }
												tabIndex={
													index === shown ? 0 : -1
												}
												onLoad={ ( event ) => {
													if (
														isPreviewBroken(
															event.currentTarget
														)
													) {
														setStatus( 'failed' );
														return;
													}
													if ( index !== shown ) {
														setShown( index );
													}
													setStatus( 'ready' );
												} }
												className={ cn(
													'absolute inset-0 size-full border border-solid border-border-subtle rounded-lg bg-background-primary shadow-sm transition-opacity duration-200 motion-reduce:transition-none',
													index === shown &&
													'ready' === status
														? 'opacity-100 z-10'
														: 'opacity-0 z-0 pointer-events-none'
												) }
											/>
										)
								) }
							{ ! showImage && 'loading' === status && (
								<FormSkeleton className="z-20 rounded-lg border border-solid border-border-subtle shadow-sm" />
							) }
							{ ! showImage && 'failed' === status && (
								<div
									className="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 p-6 text-center rounded-lg border border-dashed border-border-subtle bg-background-primary"
									role="status"
								>
									<ImageOff
										className="size-8 text-icon-secondary"
										aria-hidden="true"
									/>
									<p className="m-0 max-w-sm text-sm text-text-secondary">
										{ __(
											"Preview couldn't load on this site. You can still use the template.",
											'sureforms'
										) }
									</p>
								</div>
							) }
						</div>
					</div>
					{ showMessages && 'thank-you' === activeTab && (
						<div
							{ ...tabPanelProps( 'thank-you' ) }
							className="flex-1 min-h-0 overflow-y-auto px-3 sm:px-6 pt-3 pb-6"
						>
							<ThankYouPanel
								template={ template }
								values={ messageValues }
								setValue={ setMessage }
								look={ resolveLook(
									style,
									template.theme,
									presets || srfm_admin?.form_template_themes
								) }
								editable={ canEditMessages }
								device={ device }
							/>
						</div>
					) }
					{ showMessages && 'emails' === activeTab && (
						<div
							{ ...tabPanelProps( 'emails' ) }
							className="flex-1 min-h-0 overflow-y-auto px-3 sm:px-6 pt-3 pb-6"
						>
							<EmailsPanel
								template={ template }
								values={ messageValues }
								setValue={ setMessage }
								editable={ canEditMessages }
								held={ holdsVisitorEmail }
							/>
						</div>
					) }
				</div>
				{ panel && ! isNarrow && panel }
				{ panel && isNarrow && (
					<CustomizeSheet onClose={ () => setShowPanel( false ) }>
						{ panel }
					</CustomizeSheet>
				) }
			</div>
			<div className="flex flex-col lg:flex-row lg:items-center gap-3 lg:gap-6 px-3 sm:px-5 py-3 bg-background-primary border-0 border-t border-solid border-border-subtle">
				<div className="flex-1 min-w-0">
					<IncludedFeatures template={ template } />
				</div>
				<div className="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-5 shrink-0">
					{ lockLabels.notice && (
						<p className="m-0 text-xs text-text-secondary">
							{ lockLabels.notice }
						</p>
					) }
					{ missingSetup.length > 0 && (
						<p className="m-0 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-text-secondary">
							<Wrench
								className="size-3.5 shrink-0 text-icon-secondary"
								aria-hidden="true"
							/>
							<span className="font-medium text-text-primary">
								{ __( 'Needs setup:', 'sureforms' ) }
							</span>
							{ missingSetup.map( ( step, index ) => (
								<span key={ step.id } className="inline-flex">
									{ step.url ? (
										<a
											href={ step.url }
											target="_blank"
											rel="noopener noreferrer"
											className="inline-flex items-center gap-0.5 font-medium text-link-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-border-interactive rounded-sm"
										>
											{ step.label }
											<ExternalLink
												className="size-3"
												aria-hidden="true"
											/>
											<span className="sr-only">
												{ __(
													'(opens in a new tab)',
													'sureforms'
												) }
											</span>
										</a>
									) : (
										step.label
									) }
									{ index < missingSetup.length - 1 && ',' }
								</span>
							) ) }
						</p>
					) }
					{ showSpamOption && (
						<Checkbox
							size="sm"
							checked={ protectFromSpam }
							onChange={ ( checked ) =>
								setProtectFromSpam( Boolean( checked ) )
							}
							label={ {
								heading: __(
									'Protect this form from spam',
									'sureforms'
								),
								description: __(
									"Turns on SureForms' invisible honeypot for all forms",
									'sureforms'
								),
							} }
						/>
					) }
					<Button
						variant="primary"
						size="md"
						className="w-full sm:w-auto whitespace-nowrap"
						loading={ isCreating }
						disabled={ isCreating }
						icon={
							template.is_locked ? (
								<Lock className="size-4" />
							) : (
								<ArrowRight className="size-4" />
							)
						}
						iconPosition={ template.is_locked ? 'left' : 'right' }
						onClick={ use }
					>
						{ template.is_locked
							? lockLabels.button
							: __( 'Use this template', 'sureforms' ) }
					</Button>
				</div>
			</div>
		</div>
	);
};

/**
 * Ids of the templates that match a search. Every word must appear in the
 * template's name, description, category, field labels or features.
 *
 * @param {Array}  templates  Templates.
 * @param {Array}  categories Categories.
 * @param {string} search     Search text.
 * @return {Set<string>} Matching template ids.
 */
const useMatchingTemplates = ( templates, categories, search ) =>
	useMemo( () => {
		const words = search.toLowerCase().split( /\s+/ ).filter( Boolean );
		const labels = Object.fromEntries(
			categories.map( ( category ) => [ category.id, category.label ] )
		);
		return new Set(
			templates
				.filter( ( template ) => {
					const text = [
						template.title,
						template.description,
						labels[ template.category ],
						...( template.fields || [] ),
						...getFeatures( template.features ).map(
							( feature ) => feature.label
						),
					]
						.join( ' ' )
						.toLowerCase();
					return words.every( ( word ) => text.includes( word ) );
				} )
				.map( ( template ) => template.id )
		);
	}, [ templates, categories, search ] );

// Wait this long after typing stops before counting a search with no results.
const NO_RESULTS_DELAY = 1500;

/**
 * Shown when a search finds no template: offers to request one.
 *
 * Requests reach the SureForms team through usage sharing, so they are only
 * sent when the site has it on. Searches with no results are counted the
 * same way, once per search, without their text.
 *
 * @param {Object} props          Props.
 * @param {string} props.query    Search text.
 * @param {Object} props.requests { enabled, settings_url }.
 */
const TemplateRequestCard = ( { query, requests } ) => {
	const [ status, setStatus ] = useState( 'idle' );
	const enabled = Boolean( requests?.enabled );

	useEffect( () => {
		setStatus( 'idle' );
		if ( ! enabled ) {
			return;
		}
		const timer = setTimeout( () => {
			recordEmptySearch().catch( () => {} );
		}, NO_RESULTS_DELAY );
		return () => clearTimeout( timer );
	}, [ query, enabled ] );

	const send = async () => {
		setStatus( 'sending' );
		try {
			await requestTemplate( query );
			setStatus( 'sent' );
		} catch ( error ) {
			setStatus( 'error' );
		}
	};

	return (
		<div className="flex flex-col items-center gap-3 px-6 py-10 text-center bg-background-primary border border-dashed border-border-subtle rounded-lg">
			<MessageSquarePlus
				className="size-6 text-icon-secondary"
				aria-hidden="true"
			/>
			<div className="flex flex-col gap-1">
				<p className="m-0 text-base font-semibold text-text-primary">
					{ sprintf(
						/* translators: %s: search text. */
						__( 'No template for “%s” yet', 'sureforms' ),
						query
					) }
				</p>
				<p className="m-0 text-sm text-text-secondary">
					{ enabled
						? __(
							'Request it and we will consider adding it. You can also start from a template that is close, and change it.',
							'sureforms'
						  )
						: __(
							'Template requests are shared with the SureForms team through usage sharing, which is off on this site.',
							'sureforms'
						  ) }
				</p>
			</div>
			{ enabled && 'sent' !== status && (
				<Button
					variant="primary"
					size="sm"
					loading={ 'sending' === status }
					disabled={ 'sending' === status }
					onClick={ send }
				>
					{ __( 'Request this template', 'sureforms' ) }
				</Button>
			) }
			{ 'sent' === status && (
				<p
					className="m-0 flex items-center gap-1.5 text-sm font-medium text-text-primary"
					role="status"
				>
					<Check className="size-4" aria-hidden="true" />
					{ __(
						'Thanks, your request has been noted.',
						'sureforms'
					) }
				</p>
			) }
			{ 'error' === status && (
				<p className="m-0 text-sm text-text-error" role="alert">
					{ __(
						'The request could not be sent. Please try again.',
						'sureforms'
					) }
				</p>
			) }
			{ ! enabled && requests?.settings_url && (
				<a
					href={ requests.settings_url }
					className="text-sm font-medium text-link-primary"
				>
					{ __( 'Turn on usage sharing', 'sureforms' ) }
				</a>
			) }
		</div>
	);
};

/**
 * Category filter and the grid of template cards. Shared by the template
 * library screen and the editor's "Change template".
 *
 * @param {Object}   props            Props.
 * @param {Array}    props.templates  Templates.
 * @param {Array}    props.categories Categories.
 * @param {Function} props.onSelect   Called with a template id to preview it.
 * @param {Object}   props.children   Heading shown beside the filter.
 * @param {Object}   props.requests   Request settings: { enabled, settings_url }.
 */
export const TemplateGrid = ( {
	templates,
	categories,
	onSelect,
	children,
	requests,
} ) => {
	const [ activeCategory, setActiveCategory ] = useState( 'all' );
	// Only templates that need a paid plan. Combines with a category or search.
	const [ premiumOnly, setPremiumOnly ] = useState( false );
	const [ query, setQuery ] = useState( '' );
	const [ gridNode, setGridNode ] = useState( null );

	// Shown inside a dialog (the editor's "Change template"), keep focus in it.
	const dialog =
		gridNode?.closest( '[role="dialog"][aria-modal="true"]' ) || null;
	useFocusTrap( dialog, Boolean( dialog ) );
	const search = query.trim();
	const matches = useMatchingTemplates( templates, categories, search );

	// A search looks through every category.
	const isShown = ( template ) =>
		( ! premiumOnly || isPremiumTemplate( template ) ) &&
		( search
			? matches.has( template.id )
			: 'all' === activeCategory || template.category === activeCategory );
	const shownCount = templates.filter( isShown ).length;
	const hasPremium = templates.some( isPremiumTemplate );

	const chips = [
		{ id: 'all', label: __( 'All', 'sureforms' ) },
		...categories.filter( ( category ) =>
			templates.some( ( t ) => t.category === category.id )
		),
	];

	return (
		<div ref={ setGridNode } className="flex flex-col gap-6">
			<div className="flex flex-wrap items-end justify-between gap-4">
				{ children }
				<div className="flex flex-wrap items-center gap-3 w-full sm:w-auto">
					<label className="relative flex items-center w-full sm:w-auto">
						<span className="sr-only">
							{ __( 'Search templates', 'sureforms' ) }
						</span>
						<Search
							className="absolute left-2.5 size-4 text-icon-secondary pointer-events-none"
							aria-hidden="true"
						/>
						<input
							type="search"
							value={ query }
							onChange={ ( event ) =>
								setQuery( event.target.value )
							}
							placeholder={ __(
								'Search templates',
								'sureforms'
							) }
							className="w-full sm:w-56 h-8 pl-8 pr-2 m-0 text-sm rounded-md border border-solid border-border-subtle bg-background-primary text-text-primary focus:outline-none focus:ring-2 focus:ring-border-interactive"
						/>
					</label>
					<div
						className="flex flex-wrap gap-2"
						role="group"
						aria-label={ __( 'Filter templates', 'sureforms' ) }
					>
						{ chips.map( ( chip ) => (
							<button
								key={ chip.id }
								type="button"
								aria-pressed={
									! search && activeCategory === chip.id
								}
								onClick={ () => {
									setQuery( '' );
									setActiveCategory( chip.id );
								} }
								className={ cn(
									'px-3 py-1 text-xs font-medium rounded-full border border-solid cursor-pointer transition-colors',
									'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-border-interactive',
									! search && activeCategory === chip.id
										? 'bg-button-primary text-text-on-color border-transparent'
										: 'bg-background-primary text-text-secondary border-border-subtle hover:text-text-primary',
									search && 'opacity-50'
								) }
							>
								{ chip.label }
							</button>
						) ) }
						{ hasPremium && (
							<button
								type="button"
								aria-pressed={ premiumOnly }
								aria-label={ __(
									'Show only Premium templates',
									'sureforms'
								) }
								title={ __(
									'Show only Premium templates',
									'sureforms'
								) }
								onClick={ () =>
									setPremiumOnly( ( current ) => ! current )
								}
								className={ cn(
									'inline-flex items-center gap-1 px-3 py-1 text-xs font-medium rounded-full border border-solid cursor-pointer transition-colors',
									'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-border-interactive',
									premiumOnly
										? 'bg-brand-background-50 text-brand-800 border-brand-border-300'
										: 'bg-background-primary text-text-secondary border-border-subtle hover:text-text-primary',
								) }
							>
								<Crown className="size-3" aria-hidden="true" />
								{ __( 'Premium', 'sureforms' ) }
							</button>
						) }
					</div>
				</div>
			</div>

			{ search && shownCount > 0 && (
				<p
					className="m-0 -mt-2 text-xs text-text-tertiary"
					role="status"
				>
					{ sprintf(
						/* translators: 1: number of templates, 2: search text. */
						_n(
							'%1$d template for “%2$s”',
							'%1$d templates for “%2$s”',
							shownCount,
							'sureforms'
						),
						shownCount,
						search
					) }
				</p>
			) }

			{ ! search && premiumOnly && 0 === shownCount && (
				<p className="m-0 text-sm text-text-secondary" role="status">
					{ __( 'No Premium templates in this category yet.', 'sureforms' ) }
				</p>
			) }

			{ search && 0 === shownCount && (
				<TemplateRequestCard query={ search } requests={ requests } />
			) }

			<div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
				{ /* Cards are hidden rather than removed, so their live previews
				load once instead of on every filter or keystroke. */ }
				{ templates.map( ( template ) => {
					const features = getFeatures( template.features ).slice(
						0,
						3
					);
					return (
						<button
							key={ template.id }
							type="button"
							hidden={ ! isShown( template ) }
							onClick={ () => onSelect( template.id ) }
							aria-describedby={
								features.length
									? `srfm-template-features-${ template.id }`
									: undefined
							}
							aria-label={
								template.is_locked
									? sprintf(
										/* translators: %s: template name. */
										__(
											'Preview the %s template (Premium)',
											'sureforms'
										),
										template.title
									  )
									: sprintf(
										/* translators: %s: template name. */
										__( 'Preview the %s template', 'sureforms' ),
										template.title
									  )
							}
							className={ cn(
								'group flex flex-col p-0 text-left bg-background-primary border border-solid border-border-subtle rounded-lg overflow-hidden shadow-sm cursor-pointer',
								'transition-[transform,box-shadow,border-color] duration-200 ease-out hover:-translate-y-0.5 hover:shadow-lg hover:border-border-interactive',
								'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-border-interactive',
								'motion-reduce:transition-none motion-reduce:hover:translate-y-0',
								! isShown( template ) && 'hidden'
							) }
						>
							<span className="relative block w-full border-0 border-b border-solid border-border-subtle">
								<TemplateThumbnail template={ template } />
								<span className="absolute inset-0 flex items-center justify-center bg-background-primary/0 group-hover:bg-background-primary/40 group-focus-visible:bg-background-primary/40 transition-colors motion-reduce:transition-none">
									<span className="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-md bg-button-primary text-text-on-color shadow-md opacity-0 translate-y-1 group-hover:opacity-100 group-hover:translate-y-0 group-focus-visible:opacity-100 group-focus-visible:translate-y-0 transition-[opacity,transform] duration-200 motion-reduce:transition-none">
										<Eye
											className="size-3.5"
											aria-hidden="true"
										/>
										{ __( 'Preview', 'sureforms' ) }
									</span>
								</span>
							</span>
							<span className="flex flex-col gap-2 px-4 py-3">
								<span className="flex items-center justify-between gap-2">
									<span className="flex flex-col min-w-0">
										<span className="text-sm font-semibold text-text-primary truncate">
											{ template.title }
										</span>
										<span className="text-xs text-text-tertiary">
											{ sprintf(
												/* translators: %d: number of fields. */
												_n(
													'%d field',
													'%d fields',
													template.fields.length,
													'sureforms'
												),
												template.fields.length
											) }
										</span>
									</span>
									{ template.is_locked && (
										<PremiumBadge
											template={ template }
											className="shrink-0"
										/>
									) }
								</span>
								{ features.length > 0 && (
									<span
										id={ `srfm-template-features-${ template.id }` }
										className="flex flex-nowrap gap-1 overflow-hidden"
									>
										{ features.map( ( feature ) => (
											<FeatureChip
												key={ feature.id }
												label={ feature.short }
												title={ feature.label }
												Icon={ feature.Icon }
											/>
										) ) }
									</span>
								) }
							</span>
						</button>
					);
				} ) }
			</div>
		</div>
	);
};

/**
 * Whether the site-wide honeypot is known to be off. `wp_localize_script()`
 * turns `false` into '', and a missing value means the site didn't say, so
 * nothing is offered then.
 *
 * @param {*} value `form_template_spam_protection` as localized.
 * @return {boolean} Known to be off.
 */
const isSpamProtectionOff = ( value ) =>
	false === value || '' === value || '0' === value || 0 === value;

/**
 * Template library: its own screen, opened from "Choose from a template" on
 * the Add New Form screen. Each card shows the form as it will look on the
 * site; opening one shows a full preview with "Use this template".
 */
const TemplateLibrary = () => {
	const templates = srfm_admin?.form_templates || [];
	const categories = srfm_admin?.form_template_categories || [];
	const [ previewId, setPreviewId ] = useState( '' );
	const [ creatingId, setCreatingId ] = useState( '' );

	const closePreview = useCallback( () => setPreviewId( '' ), [] );

	useEffect( () => {
		trackTemplateAction( 'library_open' );
	}, [] );

	const isRTL = srfm_admin?.is_rtl;
	const preview = templates.find( ( t ) => t.id === previewId );

	const createFromTemplate = async ( template, style = {}, options = {} ) => {
		if ( creatingId ) {
			return;
		}

		if ( template.is_locked ) {
			openTemplateUnlock( template );
			return;
		}

		setCreatingId( template.id );
		try {
			const response = await createFormFromTemplate(
				template.id,
				style,
				options
			);
			if ( response?.edit_url ) {
				window.location.href = response.edit_url;
				return;
			}
			throw new Error();
		} catch ( error ) {
			setCreatingId( '' );
			toast.error(
				error?.message ||
					__(
						'The form could not be created. Please try again.',
						'sureforms'
					)
			);
		}
	};

	return (
		<div className="h-screen overflow-y-auto bg-background-secondary">
			<Toaster
				className="z-[999999]"
				position={ isRTL ? 'bottom-left' : 'bottom-right' }
				design="stack"
				theme="light"
				dismissAfter={ 5000 }
			/>
			<Header />
			<div className="mt-14 px-4 sm:px-8 pt-8 sm:pt-10 pb-16">
				<div className="max-w-[1200px] mx-auto">
					<TemplateGrid
						templates={ templates }
						categories={ categories }
						onSelect={ setPreviewId }
						requests={ srfm_admin?.form_template_requests }
					>
						<div className="flex flex-col gap-1">
							<h1 className="m-0 text-2xl font-semibold text-text-primary">
								{ __( 'Choose a template', 'sureforms' ) }
							</h1>
							<p className="m-0 text-sm text-text-secondary">
								{ __(
									'Preview any template as it will look on your site, then create a form from it. You can change everything afterwards.',
									'sureforms'
								) }
							</p>
						</div>
					</TemplateGrid>
				</div>
			</div>

			{ preview && (
				<TemplatePreview
					// A new template starts with its own messages and style.
					key={ preview.id }
					template={ preview }
					isCreating={ creatingId === preview.id }
					onUse={ createFromTemplate }
					onClose={ closePreview }
					offerSpamProtection={ isSpamProtectionOff(
						srfm_admin?.form_template_spam_protection
					) }
					editMessages
				/>
			) }
		</div>
	);
};

export default TemplateLibrary;
