import { __, sprintf } from '@wordpress/i18n';
import {
	useCallback,
	useEffect,
	useMemo,
	useState,
} from '@wordpress/element';
import { Button, ButtonGroup, Checkbox, Text, Tooltip } from '@bsf/force-ui';
import {
	ArrowLeft,
	ArrowRight,
	Check,
	ChevronLeft,
	ChevronRight,
	ExternalLink,
	Eye,
	FileText,
	ImageOff,
	Info,
	LoaderCircle,
	Lock,
	Mail,
	Monitor,
	Palette,
	Smartphone,
	Wrench,
	X,
} from 'lucide-react';
import { cn } from '@Utils/Helpers';
import { trackTemplateAction } from '@Utils/form-templates';
import TemplateStylePanel from './TemplateStylePanel.js';
import {
	PremiumBadge,
	getLockLabels,
	getMissingSetup,
	showsPreviewImage,
} from './template-features.js';
import useFocusTrap from '../use-focus-trap.js';
import { resolveLook } from '../template-colors.js';
import usePreviewRestyle from '../use-preview-restyle.js';
import {
	EmailsPanel,
	ThankYouPanel,
	buildMessageChanges,
	getMessageDefaults,
	hasMessages,
} from './TemplateMessages.js';

// Below this width the Customize panel opens as a bottom sheet.
const NARROW_QUERY = '(max-width: 1023px)';

// Brand focus ring, shown only for keyboard focus.
const FOCUS_RING =
	'focus:ring-0 focus:ring-offset-0 focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-toggle-on';

// Fields and widgets that use the arrow keys themselves.
const ARROW_KEY_TARGETS =
	'input, textarea, select, [contenteditable="true"], [role="tab"], [role="slider"], [role="radio"], [role="listbox"], [role="option"], [role="menu"]';

// Tooltips render outside the screen's root, above this dialog.
const tooltipProps = {
	placement: 'bottom',
	arrow: true,
	tooltipPortalId: 'srfm-dashboard-container-tooltip',
	className: 'z-[999999]',
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
 * Placeholder shaped like the form card, shown while a preview loads.
 */
const FormSkeleton = () => (
	<span
		className="absolute inset-0 z-20 flex justify-center px-6 pt-10 overflow-hidden"
		aria-hidden="true"
	>
		<span className="flex flex-col gap-4 w-full max-w-[650px] h-fit p-7 rounded-[10px] bg-background-primary animate-pulse motion-reduce:animate-none">
			<span className="block h-5 w-2/5 rounded bg-background-tertiary" />
			{ [ 0, 1, 2 ].map( ( row ) => (
				<span key={ row } className="flex flex-col gap-2">
					<span className="block h-3 w-1/4 rounded bg-background-tertiary" />
					<span className="block h-9 w-full rounded-md bg-background-secondary border border-solid border-border-subtle" />
				</span>
			) ) }
			<span className="block h-9 w-1/3 mt-2 rounded-md bg-background-tertiary" />
		</span>
	</span>
);

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
			className="flex max-w-full gap-1 p-1 rounded-[9px] bg-background-tertiary overflow-x-auto"
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
						'flex items-center gap-[7px] h-[34px] px-3 sm:px-3.5 text-[13px] whitespace-nowrap rounded-md border-0 cursor-pointer transition-colors motion-reduce:transition-none',
						'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-toggle-on',
						selected === id
							? 'bg-background-primary font-semibold text-text-primary shadow-[0_1px_1px_rgba(0,0,0,.05)]'
							: 'bg-transparent font-medium text-text-tertiary hover:text-text-primary'
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
 * Desktop / mobile switch for the preview.
 *
 * @param {Object}   props           Props.
 * @param {string}   props.device    'desktop' or 'mobile'.
 * @param {Function} props.onChange  Called with a device id.
 * @param {boolean}  props.inverse   Dark selected state, for the full-screen preview.
 * @param {string}   props.className Extra classes.
 */
const DeviceSwitch = ( { device, onChange, inverse = false, className } ) => {
	const devices = [
		{
			id: 'desktop',
			label: __( 'Desktop preview', 'sureforms' ),
			Icon: Monitor,
		},
		{
			id: 'mobile',
			label: __( 'Mobile preview', 'sureforms' ),
			Icon: Smartphone,
		},
	];
	return (
		<div
			role="group"
			aria-label={ __( 'Preview size', 'sureforms' ) }
			className={ className }
		>
			<ButtonGroup.Group
				activeItem={ device }
				onChange={ ( { value } ) => onChange( value.slug ) }
				size="sm"
				className="gap-0.5 p-[3px] border-0 rounded-[9px] bg-background-tertiary"
			>
				{ devices.map( ( { id, label, Icon } ) => (
					<ButtonGroup.Button
						key={ id }
						slug={ id }
						text={ <Icon className="size-[17px]" aria-hidden="true" /> }
						aria-label={ label }
						aria-pressed={ device === id }
						title={ label }
						className={ cn(
							'w-9 h-8 p-0 border-0 rounded-[7px] transition-colors',
							FOCUS_RING,
							device === id &&
								( inverse
									? 'bg-background-inverse hover:bg-background-inverse text-text-on-color'
									: 'bg-background-primary hover:bg-background-primary text-text-primary shadow-[0_1px_1px_rgba(0,0,0,.06)]' ),
							device !== id &&
								'bg-transparent hover:bg-transparent text-text-tertiary hover:text-text-primary'
						) }
					/>
				) ) }
			</ButtonGroup.Group>
		</div>
	);
};

/**
 * Square icon button for the top bar, named by its tooltip.
 *
 * @param {Object} props           Props.
 * @param {string} props.label     Accessible name and tooltip.
 * @param {*}      props.icon      Icon.
 * @param {string} props.className Extra classes.
 */
const IconButton = ( { label, icon, className, ...props } ) => (
	<Tooltip content={ label } { ...tooltipProps } triggers={ [ 'hover', 'focus' ] }>
		<Button
			variant="outline"
			size="sm"
			icon={ icon }
			aria-label={ label }
			className={ cn(
				'size-[38px] p-0 shrink-0 justify-center rounded-lg bg-background-primary text-icon-secondary outline-[#D1D5DB] hover:bg-background-secondary',
				FOCUS_RING,
				className
			) }
			{ ...props }
		/>
	</Tooltip>
);

/**
 * The live form inside the preview stage: one or two frames rendering the
 * template with the real form renderer, restyled as colors are picked.
 * Keyed by template, so stepping to another template starts fresh.
 *
 * @param {Object}  props              Props.
 * @param {Object}  props.template     Template.
 * @param {Object}  props.style        Chosen style.
 * @param {Object}  props.look         Look of the chosen style.
 * @param {Object}  props.renderedLook Look of the first render.
 * @param {Array}   props.presets      Presets.
 * @param {boolean} props.canRestyle   Colors can change.
 */
const LiveForm = ( {
	template,
	style,
	look,
	renderedLook,
	presets,
	canRestyle,
} ) => {
	// Once colors can change, the preview stays ready to restyle.
	const [ restyleReady, setRestyleReady ] = useState( canRestyle );
	useEffect( () => {
		if ( canRestyle ) {
			setRestyleReady( true );
		}
	}, [ canRestyle ] );
	const { frames, shown, status, isUpdating, setFrameNode, onFrameLoad } =
		usePreviewRestyle( {
			template,
			style,
			look,
			renderedLook,
			presets,
			active: true,
			canRestyle: restyleReady,
		} );

	return (
		<>
			{ frames.map(
				( src, index ) =>
					!! src && (
						<iframe
							// One element per slot, so the shown frame stays
							// while the other one renders a change.
							key={ index }
							ref={ setFrameNode( index ) }
							src={ src }
							title={ sprintf(
								/* translators: %s: template name. */
								__( 'Preview of the %s form', 'sureforms' ),
								template.title
							) }
							aria-hidden={ index !== shown }
							tabIndex={ index === shown ? 0 : -1 }
							// Keeps Tab out of the fields of the frame behind.
							inert={ index !== shown ? '' : undefined }
							onLoad={ ( event ) => onFrameLoad( index, event ) }
							// See-through: the form card sits on the stage's page color.
							className={ cn(
								'absolute inset-0 size-full border-0 bg-transparent [color-scheme:light] transition-opacity duration-200 motion-reduce:transition-none',
								index === shown && 'ready' === status
									? 'opacity-100 z-10'
									: 'opacity-0 z-0 pointer-events-none'
							) }
						/>
					)
			) }
			{ 'ready' === status && (
				<span
					role="status"
					className={ cn(
						'absolute top-3 left-1/2 -translate-x-1/2 z-20 inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium whitespace-nowrap rounded-full bg-background-inverse/90 text-text-on-color shadow-md',
						'transition-opacity duration-200 motion-reduce:transition-none',
						isUpdating ? 'opacity-100' : 'opacity-0'
					) }
				>
					{ isUpdating && (
						<>
							<LoaderCircle
								className="size-3.5 animate-spin motion-reduce:animate-none"
								aria-hidden="true"
							/>
							{ __( 'Updating preview…', 'sureforms' ) }
						</>
					) }
				</span>
			) }
			{ 'loading' === status && <FormSkeleton /> }
			{ 'failed' === status && (
				<div
					className="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 p-6 text-center"
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
		</>
	);
};

/**
 * Picture of a locked template's Premium version, in place of the live form.
 *
 * @param {Object}   props          Props.
 * @param {Object}   props.template Template.
 * @param {Function} props.onError  Called when the picture doesn't load.
 */
const PremiumPicture = ( { template, onError } ) => (
	<>
		<div
			// Scrolls with the keyboard too, like the live preview.
			// eslint-disable-next-line jsx-a11y/no-noninteractive-tabindex
			tabIndex={ 0 }
			className="absolute inset-0 overflow-y-auto focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-toggle-on"
		>
			<img
				src={ template.preview_image }
				alt={ sprintf(
					/* translators: %s: template name. */
					__( 'Preview of the %s form', 'sureforms' ),
					template.title
				) }
				decoding="async"
				onError={ onError }
				// At the form's own width (pictures are taken at 522px), centered like the live preview, never stretched.
				className="block w-full max-w-[570px] h-auto mx-auto px-6 py-10"
			/>
		</div>
		<span className="absolute top-3 left-1/2 -translate-x-1/2 z-10 inline-flex items-center gap-1.5 px-3 py-1 text-xs font-medium whitespace-nowrap rounded-full bg-background-inverse/90 text-text-on-color shadow-md">
			<Lock className="size-3.5" aria-hidden="true" />
			{ __( 'Preview of the Premium version', 'sureforms' ) }
		</span>
	</>
);

/**
 * The template preview: the real form on its page color, the thank-you page
 * and the emails, with Customize, and the action to create a form from it.
 * Its arrows step through the templates this site can use, in the order the
 * library shows them.
 *
 * @param {Object}   props                     Props.
 * @param {Object}   props.template            Template.
 * @param {Array}    props.browseList          Templates the arrows step through, in order,
 *                                             e.g. the library's filtered cards. Locked
 *                                             ones are skipped.
 * @param {Function} props.onNavigate          Called with a template id to show it instead.
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
const TemplatePreview = ( {
	template,
	browseList = [],
	onNavigate,
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
	// What the visitor chose to look at stays as they step through templates.
	const [ device, setDevice ] = useState( 'desktop' );
	const [ showPanel, setShowPanel ] = useState( () => ! isNarrow );
	const [ fullScreen, setFullScreen ] = useState( false );
	const [ protectFromSpam, setProtectFromSpam ] = useState( true );
	// 'form', 'thank-you' or 'emails'.
	const [ tab, setTab ] = useState( 'form' );
	// Each template starts with its own style and messages.
	const [ shownId, setShownId ] = useState( template.id );
	const [ style, setStyle ] = useState( {} );
	// Message text changed from the template's, by key.
	const [ messageEdits, setMessageEdits ] = useState( {} );
	// A locked template's picture that doesn't load falls back to the live preview.
	const [ imageFailed, setImageFailed ] = useState( false );
	if ( shownId !== template.id ) {
		setShownId( template.id );
		setStyle( {} );
		setMessageEdits( {} );
		setImageFailed( false );
	}
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

	const themePresets = presets || window.srfm_admin?.form_template_themes;
	const look = useMemo(
		() => resolveLook( style, template.theme, themePresets ),
		[ style, template.theme, themePresets ]
	);
	// The page is first rendered without a style, in the template's own look.
	const renderedLook = useMemo(
		() => resolveLook( {}, template.theme, themePresets ),
		[ template.theme, themePresets ]
	);
	// Colors only change from the Customize panel, so the preview gets ready
	// to restyle once it has been open.
	const canRestyle = showPanel && ! template.is_locked;

	// The templates the arrows step through: the ones this site can use.
	const browsable = useMemo(
		() => browseList.filter( ( item ) => ! item.is_locked ),
		[ browseList ]
	);
	const position = browsable.findIndex( ( item ) => item.id === template.id );
	const canBrowse = Boolean( onNavigate ) && position > -1 && browsable.length > 1;
	const step = useCallback(
		( by ) => {
			if ( ! canBrowse ) {
				return;
			}
			const next =
				browsable[
					( position + by + browsable.length ) % browsable.length
				];
			onNavigate( next.id );
		},
		[ canBrowse, browsable, position, onNavigate ]
	);

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
			const target = event.target;
			if ( 'Escape' === event.key ) {
				// In a text field, Escape leaves the field first, so typing a
				// message is not lost to a stray key.
				if (
					target?.matches?.( 'input[type="text"], textarea' ) &&
					dialogNode?.contains( target )
				) {
					dialogNode.focus();
					return;
				}
				if ( fullScreen ) {
					setFullScreen( false );
					return;
				}
				onClose();
				return;
			}
			// ← / → step through templates, unless focus is somewhere that
			// uses the arrow keys, or in a dialog on top (a confirmation).
			const by = { ArrowLeft: -1, ArrowRight: 1 }[ event.key ];
			if (
				! by ||
				event.defaultPrevented ||
				event.metaKey ||
				event.ctrlKey ||
				event.altKey ||
				event.shiftKey ||
				! dialogNode?.contains( target ) ||
				target?.isContentEditable ||
				target?.closest?.( ARROW_KEY_TARGETS )
			) {
				return;
			}
			event.preventDefault();
			// RTL reads the arrows the other way round.
			step( 'rtl' === dialogNode.ownerDocument.documentElement.dir ? -by : by );
		};
		document.addEventListener( 'keydown', onKeyDown );
		return () => document.removeEventListener( 'keydown', onKeyDown );
	}, [ onClose, dialogNode, fullScreen, step ] );

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
			Icon: Check,
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

	const openFullScreen = () => {
		setTab( 'form' );
		setFullScreen( true );
	};

	// Colors don't change emails, so the Customize panel steps aside there.
	const showCustomize = canCustomize && 'emails' !== activeTab;
	const panel = showCustomize && showPanel && ! fullScreen && (
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

	// The page color behind the form: the stage the form, thank-you page and
	// emails sit on, like the template's card in the library.
	const stageClass =
		'relative flex-1 min-w-0 rounded-[14px] border border-solid border-[rgba(17,24,39,.07)] transition-colors duration-200 motion-reduce:transition-none';

	// Unfinished setup and notices that apply: the bar is only there for them.
	const showFooter =
		missingSetup.length > 0 || showSpamOption || Boolean( lockLabels.notice );

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
			<div
				className={ cn(
					'shrink-0 flex flex-wrap lg:flex-nowrap items-center gap-x-3.5 gap-y-2 min-h-16 ps-3 pe-3 lg:pe-5 py-2.5 lg:py-0 bg-background-primary border-0 border-b border-solid border-border-subtle',
					// Covered by the full-screen preview, and out of the tab order.
					fullScreen && 'hidden'
				) }
			>
				<Button
					variant="ghost"
					size="sm"
					icon={ <ArrowLeft className="size-4" /> }
					aria-label={ __( 'All templates', 'sureforms' ) }
					onClick={ onClose }
					className={ cn(
						'shrink-0 h-9 ps-1.5 pe-2.5 gap-[7px] rounded-lg text-sm font-semibold text-text-primary hover:bg-background-tertiary',
						FOCUS_RING
					) }
				>
					<span className="hidden sm:inline">
						{ __( 'All templates', 'sureforms' ) }
					</span>
				</Button>
				<span
					className="shrink-0 w-px h-7 bg-border-subtle"
					aria-hidden="true"
				/>
				<div className="flex flex-col min-w-0 flex-1">
					<div className="flex items-center gap-2 min-w-0">
						<h2
							id="srfm-template-preview-title"
							className="m-0 text-base leading-snug font-semibold tracking-[-0.01em] text-text-primary truncate"
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
						className="hidden min-[1000px]:block mt-px text-[13px] text-text-tertiary truncate"
					>
						{ template.description }
					</span>
				</div>
				<div className="order-last lg:order-none basis-full lg:basis-auto flex items-center justify-end gap-2.5 min-w-0">
					{ canBrowse && (
						<div
							role="group"
							aria-label={ __( 'Browse templates', 'sureforms' ) }
							className="flex items-center gap-0.5 me-auto lg:me-0"
						>
							<Button
								variant="ghost"
								size="sm"
								icon={ <ChevronLeft className="size-4 rtl:rotate-180" /> }
								aria-label={ __( 'Previous template', 'sureforms' ) }
								onClick={ () => step( -1 ) }
								className={ cn(
									'size-8 p-0 justify-center rounded-lg text-icon-secondary hover:bg-background-tertiary hover:text-text-primary',
									FOCUS_RING
								) }
							/>
							<Text
								as="span"
								aria-live="polite"
								className="min-w-12 text-center text-[12.5px] font-medium text-text-tertiary tabular-nums max-[999px]:sr-only"
							>
								<span aria-hidden="true">
									{ sprintf(
										/* translators: 1: position of the template, 2: number of templates. */
										__( '%1$d / %2$d', 'sureforms' ),
										position + 1,
										browsable.length
									) }
								</span>
								<span className="sr-only">
									{ sprintf(
										/* translators: 1: position of the template, 2: number of templates. */
										__( 'Template %1$d of %2$d', 'sureforms' ),
										position + 1,
										browsable.length
									) }
								</span>
							</Text>
							<Button
								variant="ghost"
								size="sm"
								icon={ <ChevronRight className="size-4 rtl:rotate-180" /> }
								aria-label={ __( 'Next template', 'sureforms' ) }
								onClick={ () => step( 1 ) }
								className={ cn(
									'size-8 p-0 justify-center rounded-lg text-icon-secondary hover:bg-background-tertiary hover:text-text-primary',
									FOCUS_RING
								) }
							/>
						</div>
					) }
					{ 'emails' !== activeTab && (
						<DeviceSwitch
							device={ device }
							onChange={ setDevice }
							className="hidden md:block"
						/>
					) }
					<IconButton
						label={ __( 'Preview full screen', 'sureforms' ) }
						icon={ <Eye className="size-4" /> }
						onClick={ openFullScreen }
					/>
					{ showCustomize && (
						<IconButton
							label={ __( 'Customize', 'sureforms' ) }
							icon={ <Palette className="size-4" /> }
							aria-pressed={ showPanel }
							aria-controls={ showPanel ? panelId : undefined }
							onClick={ () => setShowPanel( ( open ) => ! open ) }
							className={
								showPanel
									? 'bg-[#FFF3EE] hover:bg-[#FFF3EE] outline-[rgba(213,68,7,.35)] text-[#8A3A12]'
									: undefined
							}
						/>
					) }
					<Button
						variant="primary"
						size="sm"
						className={ cn(
							'shrink-0 h-[38px] px-4 gap-2 rounded-lg text-sm font-semibold whitespace-nowrap',
							FOCUS_RING
						) }
						loading={ isCreating }
						disabled={ isCreating }
						icon={
							template.is_locked ? (
								<Lock className="size-4" />
							) : (
								<ArrowRight className="size-4 rtl:rotate-180" />
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
				<Button
					variant="ghost"
					size="sm"
					aria-label={ __( 'Close preview', 'sureforms' ) }
					icon={ <X className="size-5" /> }
					onClick={ onClose }
					className={ cn(
						'shrink-0 size-10 p-0 justify-center rounded-lg text-icon-secondary hover:bg-background-tertiary hover:text-text-primary',
						FOCUS_RING
					) }
				/>
			</div>
			<div className="relative flex-1 min-h-0 flex">
				<div className="flex-1 min-w-0 flex flex-col">
					<div
						className={ cn(
							'shrink-0 flex flex-col items-center gap-3 px-3 sm:px-6 pt-3 sm:pt-4 pb-3 sm:pb-3.5',
							// Covered by the full-screen preview, and out of the tab order.
							fullScreen && 'hidden'
						) }
					>
						{ showMessages && (
							<PreviewTabs
								tabs={ tabs }
								selected={ activeTab }
								onSelect={ setTab }
							/>
						) }
						{ showMissingNotice && 'form' === activeTab && (
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
						) }
					</div>
					<div
						{ ...tabPanelProps( 'form' ) }
						className={ cn(
							'flex-1 min-h-0 flex px-3 sm:px-6 pb-3 sm:pb-6',
							'form' !== activeTab && 'hidden'
						) }
					>
						<div
							className={ cn(
								stageClass,
								'flex justify-center overflow-hidden',
								fullScreen &&
									'fixed inset-0 z-50 pt-16 rounded-none border-0'
							) }
							style={ { background: look.page } }
							role={ fullScreen ? 'region' : undefined }
							aria-label={
								fullScreen
									? __( 'Full screen preview', 'sureforms' )
									: undefined
							}
						>
							{ fullScreen && (
								<>
									<Button
										variant="outline"
										size="sm"
										icon={ <ArrowLeft className="size-4 rtl:rotate-180" /> }
										onClick={ () => setFullScreen( false ) }
										className={ cn(
											'absolute top-4 start-4 z-30 h-10 ps-3 pe-4 gap-2 rounded-full text-sm font-semibold text-text-primary bg-background-primary/90 hover:bg-background-primary outline-[rgba(17,24,39,.12)] shadow-sm backdrop-blur',
											FOCUS_RING
										) }
									>
										{ __( 'Back', 'sureforms' ) }
									</Button>
									<DeviceSwitch
										device={ device }
										onChange={ setDevice }
										inverse
										className="absolute top-4 end-4 z-30 rounded-[10px] bg-background-primary/90 shadow-sm backdrop-blur [&>div]:bg-transparent"
									/>
								</>
							) }
							<div
								className={ cn(
									'relative h-full w-full transition-[max-width] duration-300 motion-reduce:transition-none',
									'mobile' === device && 'max-w-[390px]'
								) }
							>
								{ showImage ? (
									<PremiumPicture
										template={ template }
										onError={ () => setImageFailed( true ) }
									/>
								) : (
									<LiveForm
										key={ template.id }
										template={ template }
										style={ style }
										look={ look }
										renderedLook={ renderedLook }
										presets={ themePresets }
										canRestyle={ canRestyle }
									/>
								) }
							</div>
						</div>
					</div>
					{ showMessages && 'thank-you' === activeTab && (
						<div
							{ ...tabPanelProps( 'thank-you' ) }
							className="flex-1 min-h-0 flex px-3 sm:px-6 pb-3 sm:pb-6"
						>
							<div
								className={ cn( stageClass, 'flex overflow-y-auto' ) }
								style={ { background: look.page } }
							>
								<div className="m-auto w-full px-3 sm:px-6 py-8 sm:py-10">
									<ThankYouPanel
										template={ template }
										values={ messageValues }
										setValue={ setMessage }
										look={ look }
										editable={ canEditMessages }
										device={ device }
									/>
								</div>
							</div>
						</div>
					) }
					{ showMessages && 'emails' === activeTab && (
						<div
							{ ...tabPanelProps( 'emails' ) }
							className="flex-1 min-h-0 flex px-3 sm:px-6 pb-3 sm:pb-6"
						>
							<div
								className={ cn( stageClass, 'flex overflow-y-auto' ) }
								style={ { background: look.page } }
							>
								<div className="m-auto w-full px-3 sm:px-6 py-8 sm:py-10">
									<EmailsPanel
										template={ template }
										values={ messageValues }
										setValue={ setMessage }
										look={ look }
										editable={ canEditMessages }
										held={ holdsVisitorEmail }
									/>
								</div>
							</div>
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
			{ showFooter && (
				<div
					className={ cn(
						'shrink-0 flex flex-wrap items-center gap-x-6 gap-y-3 px-3 sm:px-6 py-3 bg-background-primary border-0 border-t border-solid border-border-subtle',
						// Covered by the full-screen preview, and out of the tab order.
						fullScreen && 'hidden'
					) }
				>
					{ missingSetup.length > 0 && (
						<p className="m-0 flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs text-text-secondary">
							<Wrench
								className="size-3.5 shrink-0 text-icon-secondary"
								aria-hidden="true"
							/>
							<span className="font-medium text-text-primary">
								{ __( 'Needs setup:', 'sureforms' ) }
							</span>
							{ missingSetup.map( ( setupStep, index ) => (
								<span key={ setupStep.id } className="inline-flex">
									{ setupStep.url ? (
										<a
											href={ setupStep.url }
											target="_blank"
											rel="noopener noreferrer"
											className="inline-flex items-center gap-0.5 font-medium text-link-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-border-interactive rounded-sm"
										>
											{ setupStep.label }
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
										setupStep.label
									) }
									{ index < missingSetup.length - 1 && ',' }
								</span>
							) ) }
						</p>
					) }
					{ lockLabels.notice && (
						<p className="m-0 text-xs text-text-secondary">
							{ lockLabels.notice }
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
				</div>
			) }
		</div>
	);
};

export default TemplatePreview;
