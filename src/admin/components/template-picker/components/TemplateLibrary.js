import { __, sprintf, _n } from '@wordpress/i18n';
import {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from '@wordpress/element';
import { useNavigate } from 'react-router-dom';
import { Text, Title, Toaster, toast } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';
import {
	createFormFromTemplate,
	recordEmptySearch,
	trackTemplateAction,
} from '@Utils/form-templates';
import {
	clearStartChoice,
	getStartChoice,
	trackStartStep,
} from '@Utils/form-start';
import Header from './Header.js';
import TemplateCard from './TemplateCard.js';
import TemplatePreview from './TemplatePreview.js';
import { openTemplateUnlock } from './template-features.js';
import {
	DEFAULT_FILTERS,
	countOptions,
	getSearchText,
	isNarrowed,
	matchesSearch,
	passesFilters,
	sortTemplates,
} from './template-filters.js';
import {
	CreateButtons,
	FilterSelect,
	NoTemplates,
	NotQuiteCard,
	PlanSwitch,
	ResultCount,
	TemplateSearch,
	canGenerateWithAi,
	useInView,
} from './LibraryControls.js';
import useFocusTrap from '../use-focus-trap.js';

export { TemplatePreview };

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
		const labels = Object.fromEntries(
			categories.map( ( category ) => [ category.id, category.label ] )
		);
		return new Set(
			templates
				.filter( ( template ) =>
					matchesSearch( getSearchText( template, labels ), search )
				)
				.map( ( template ) => template.id )
		);
	}, [ templates, categories, search ] );

// Wait this long after typing stops before counting a search with no results.
const NO_RESULTS_DELAY = 1500;

/**
 * Counts a search that found no template, once per search and without its
 * text. Like template requests, only while the site shares usage data.
 *
 * @param {string}  search   Search text.
 * @param {boolean} noResult No template matches the search.
 * @param {boolean} enabled  Usage sharing is on.
 */
const useEmptySearchSignal = ( search, noResult, enabled ) => {
	useEffect( () => {
		if ( ! enabled || ! search || ! noResult ) {
			return;
		}
		const timer = setTimeout( () => {
			recordEmptySearch().catch( () => {} );
		}, NO_RESULTS_DELAY );
		return () => clearTimeout( timer );
	}, [ search, noResult, enabled ] );
};

/**
 * Search, filters and the grid of template cards. Shared by the template
 * library screen and the editor's "Change template".
 *
 * @param {Object}   props                    Props.
 * @param {Array}    props.templates          Templates.
 * @param {Array}    props.categories         Categories.
 * @param {Function} props.onSelect           Called with a template id to preview it, and
 *                                            the templates shown, in order.
 * @param {Array}    props.presets            Style presets, for the cards' colors.
 * @param {Object}   props.children           Intro shown above the search, when there is no heading.
 * @param {string}   props.heading            Page title, shown centered above the search.
 * @param {Object}   props.requests           Request settings: { enabled, settings_url }.
 * @param {string}   props.query              Search text, when the parent keeps it.
 * @param {Function} props.onQueryChange      Called with new search text, with `query`.
 * @param {Function} props.onSearchVisibility Called with whether the search box is in view.
 * @param {boolean}  props.canCreate          Offer "Start from scratch" when nothing fits.
 *                                            Only on the Add New Form screen.
 * @param {Function} props.onBuildWithAi      Open the AI builder with a prompt. Only on the
 *                                            Add New Form screen, and while AI can run.
 */
export const TemplateGrid = ( {
	templates,
	categories,
	onSelect,
	presets,
	children,
	heading,
	requests,
	query: controlledQuery,
	onQueryChange,
	onSearchVisibility,
	canCreate = false,
	onBuildWithAi,
} ) => {
	const [ filters, setFilters ] = useState( DEFAULT_FILTERS );
	const [ ownQuery, setOwnQuery ] = useState( '' );
	const isControlled = undefined !== controlledQuery;
	const query = isControlled ? controlledQuery : ownQuery;
	const setQuery = isControlled ? onQueryChange : setOwnQuery;
	const [ gridNode, setGridNode ] = useState( null );
	const searchRef = useRef( null );
	const searchBoxRef = useRef( null );

	// Shown inside a dialog (the editor's "Change template"), keep focus in it.
	const dialog =
		gridNode?.closest( '[role="dialog"][aria-modal="true"]' ) || null;
	useFocusTrap( dialog, Boolean( dialog ) );
	useInView( searchBoxRef, onSearchVisibility );

	// "/" jumps to the search, unless something else has focus to type in.
	useEffect( () => {
		const onKeyDown = ( event ) => {
			if (
				'/' !== event.key ||
				event.metaKey ||
				event.ctrlKey ||
				event.altKey ||
				event.defaultPrevented
			) {
				return;
			}
			const target = event.target;
			if (
				target?.isContentEditable ||
				target?.closest?.( 'input, textarea, select, [role="listbox"]' )
			) {
				return;
			}
			// Not while a preview is open over the grid.
			const openDialog = target?.closest?.( '[role="dialog"]' );
			if ( openDialog && ! openDialog.contains( gridNode ) ) {
				return;
			}
			event.preventDefault();
			searchRef.current?.focus();
		};
		document.addEventListener( 'keydown', onKeyDown );
		return () => document.removeEventListener( 'keydown', onKeyDown );
	}, [ gridNode ] );

	const search = query.trim();
	const matches = useMatchingTemplates( templates, categories, search );
	const isMatched = useCallback(
		( template ) => matches.has( template.id ),
		[ matches ]
	);
	const isShown = ( template ) =>
		passesFilters( template, filters, isMatched( template ) );
	const shownCount = templates.filter( isShown ).length;
	const narrowed = isNarrowed( filters, search );
	const ordered = useMemo(
		() => sortTemplates( templates, narrowed ),
		[ templates, narrowed ]
	);
	const requestsOn = Boolean( requests?.enabled );
	// Only a search the whole library has nothing for, whatever the filters,
	// says what is missing from it.
	const searchFoundNothing = Boolean( search ) && 0 === matches.size;
	useEmptySearchSignal( search, searchFoundNothing, requestsOn );

	// The preview steps through the cards as they are shown now.
	const selectTemplate = ( id ) =>
		onSelect( id, ordered.filter( isShown ) );

	const setFilter = ( key ) => ( value ) =>
		setFilters( ( current ) => ( { ...current, [ key ]: value } ) );

	const categoryCounts = countOptions(
		templates,
		filters,
		isMatched,
		'category'
	);
	const typeCounts = countOptions( templates, filters, isMatched, 'formType' );
	const categoryOptions = [
		{
			id: 'all',
			label: __( 'All categories', 'sureforms' ),
			short: __( 'All', 'sureforms' ),
			count: categoryCounts.all,
		},
		...categories
			.filter( ( category ) =>
				templates.some( ( t ) => t.category === category.id )
			)
			.map( ( category ) => ( {
				...category,
				count: categoryCounts[ category.id ] || 0,
			} ) ),
	];
	const typeOptions = [
		{
			id: 'all',
			label: __( 'All form types', 'sureforms' ),
			short: __( 'All', 'sureforms' ),
		},
		{ id: 'single', label: __( 'Single page', 'sureforms' ) },
		{ id: 'multistep', label: __( 'Multi-step', 'sureforms' ) },
		{ id: 'conversational', label: __( 'Conversational', 'sureforms' ) },
	]
		.filter(
			( option ) =>
				'all' === option.id ||
				templates.some(
					( t ) => ( t.form_type || 'single' ) === option.id
				)
		)
		.map( ( option ) => ( {
			...option,
			count: typeCounts[ option.id ] || 0,
		} ) );

	// Opens the AI builder with the search; the search text isn't sent anywhere.
	const buildWithAi = onBuildWithAi
		? () => onBuildWithAi( search )
		: undefined;
	// A short list for a search or filter: offer to make the form instead.
	const isThin = canCreate && narrowed && shownCount > 0 && shownCount <= 3;

	const searchBox = (
		<div ref={ searchBoxRef } className="w-full max-w-[640px]">
			<TemplateSearch
				ref={ searchRef }
				value={ query }
				onChange={ setQuery }
				placeholder={ sprintf(
					/* translators: %d: number of templates. */
					_n(
						'Search %d template, try quote or booking',
						'Search %d templates, try quote or booking',
						templates.length,
						'sureforms'
					),
					templates.length
				) }
			/>
		</div>
	);

	return (
		<div ref={ setGridNode } className="flex flex-col">
			{ heading ? (
				<div className="flex flex-col items-center gap-5 pt-10 pb-12 text-center">
					<Title
						tag="h1"
						title={ heading }
						className="[&_h1]:text-[28px] [&_h1]:leading-[1.45] [&_h1]:font-bold [&_h1]:tracking-[-0.022em] [&_h1]:text-text-primary"
					/>
					{ searchBox }
				</div>
			) : (
				<div className="flex flex-col gap-4 pb-6">
					{ children }
					{ searchBox }
				</div>
			) }

			<div className="flex flex-wrap items-center justify-between gap-3 pb-5">
				<div className="order-1 flex flex-wrap items-center gap-3.5 max-[760px]:gap-2.5 max-[560px]:w-full">
					<FilterSelect
						name={ __( 'Category', 'sureforms' ) }
						label={ __( 'Category:', 'sureforms' ) }
						value={ filters.category }
						options={ categoryOptions }
						onChange={ setFilter( 'category' ) }
					/>
					{ typeOptions.length > 2 && (
						<FilterSelect
							name={ __( 'Form type', 'sureforms' ) }
							label={ __( 'Form type:', 'sureforms' ) }
							value={ filters.formType }
							options={ typeOptions }
							onChange={ setFilter( 'formType' ) }
						/>
					) }
					<ResultCount count={ shownCount } />
				</div>
				<PlanSwitch
					value={ filters.plan }
					onChange={ setFilter( 'plan' ) }
				/>
			</div>

			{ 0 === shownCount && (
				<NoTemplates canCreate={ canCreate } onAi={ buildWithAi } />
			) }

			<div
				className={ cn(
					'grid grid-cols-1 min-[601px]:grid-cols-2 min-[901px]:grid-cols-3 gap-7 pb-7',
					0 === shownCount && 'hidden'
				) }
			>
				{ /* Cards are hidden rather than removed, so their live previews
				load once instead of on every filter or keystroke. */ }
				{ ordered.map( ( template ) => (
					<TemplateCard
						key={ template.id }
						template={ template }
						presets={ presets }
						hidden={ ! isShown( template ) }
						onSelect={ selectTemplate }
					/>
				) ) }
			</div>

			{ isThin && <NotQuiteCard onAi={ buildWithAi } /> }
			{ shownCount > 0 && ! isThin && (
				<Text className="pt-2.5 pb-[72px] text-[13px] text-center text-text-tertiary">
					{ __( 'That is every template', 'sureforms' ) }
				</Text>
			) }
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
	// The cards shown when the preview opened, for its arrows.
	const [ previewList, setPreviewList ] = useState( [] );
	const [ creatingId, setCreatingId ] = useState( '' );
	const [ query, setQuery ] = useState( '' );
	// The header gets its own search once the big one scrolls out of view.
	const [ searchInView, setSearchInView ] = useState( true );
	const navigate = useNavigate();
	const aiReady = canGenerateWithAi();
	// Opens the AI screen in place with the prompt typed in, without
	// generating: that spends a credit, so it waits for the visitor.
	const openAiBuilder = useCallback(
		( prompt = '' ) =>
			navigate( {
				search: `?page=add-new-form&view=ai${
					prompt ? `&srfm_ai_prompt=${ encodeURIComponent( prompt ) }` : ''
				}`,
			} ),
		[ navigate ]
	);

	const closePreview = useCallback( () => setPreviewId( '' ), [] );
	const openPreview = useCallback( ( id, shown = [] ) => {
		setPreviewList( shown );
		setPreviewId( id );
	}, [] );

	useEffect( () => {
		trackTemplateAction( 'library_open' );
		trackStartStep( 'template_open' );
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
				{ ...options, startChoice: getStartChoice() }
			);
			if ( response?.edit_url ) {
				clearStartChoice();
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
			<Header
				hideLogoOnPhone={ ! searchInView }
				actions={
					<div className="flex items-center justify-end gap-2 min-w-0 flex-1 max-[620px]:ps-3">
						<TemplateSearch
							variant="compact"
							value={ query }
							onChange={ setQuery }
							placeholder={ __( 'Search templates', 'sureforms' ) }
							aria-hidden={ searchInView ? 'true' : undefined }
							tabIndex={ searchInView ? -1 : undefined }
							className={ cn(
								'max-w-[300px] min-w-[130px] flex-[0_1_300px] max-[620px]:min-w-0 max-[620px]:flex-[1_1_80px] transition-[opacity,transform,visibility] duration-150 motion-reduce:transition-none',
								searchInView
									? 'invisible opacity-0 -translate-y-1 max-[620px]:hidden'
									: 'visible opacity-100 translate-y-0'
							) }
						/>
						<CreateButtons
							size="header"
							onAi={ aiReady ? () => openAiBuilder() : undefined }
							className="flex-nowrap"
						/>
					</div>
				}
			/>
			<div className="mt-14 px-5 min-[901px]:px-10 pb-4">
				<div className="max-w-[1200px] mx-auto">
					<TemplateGrid
						templates={ templates }
						categories={ categories }
						onSelect={ openPreview }
						requests={ srfm_admin?.form_template_requests }
						heading={ __( 'Choose a Template', 'sureforms' ) }
						query={ query }
						onQueryChange={ setQuery }
						onSearchVisibility={ setSearchInView }
						canCreate
						onBuildWithAi={ aiReady ? openAiBuilder : undefined }
					/>
				</div>
			</div>

			{ preview && (
				<TemplatePreview
					template={ preview }
					browseList={ previewList }
					onNavigate={ setPreviewId }
					isCreating={ Boolean( creatingId ) }
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
