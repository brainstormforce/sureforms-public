import apiFetch from '@wordpress/api-fetch';

/**
 * The ways to start a new form on the Add New Form start screen.
 *
 * @since 2.13.0
 */
export const START_CHOICES = [ 'ai', 'template', 'scratch' ];

// The choice lives for the rest of the tab session, so a form created after
// the AI sign-in redirect still counts for the path picked on the start screen.
const STORAGE_KEY = 'srfm_start_choice';
const MAX_AGE = 2 * 60 * 60 * 1000;
// Set just before Add New Form reloads itself, so that one load counts as
// the same visit.
const CARRY_KEY = 'srfm_start_carry';

/**
 * Whether the start screen is shown, or skipped straight to the AI screen.
 *
 * @since 2.13.0
 * @return {boolean} Shown.
 */
export const isStartScreenEnabled = () =>
	false !== srfm_admin?.form_start?.enabled;

// Links that already ask for an AI form (the AI sign-in coming back, the
// dashboard AI prompt, a preset form type) skip the start screen.
const AI_PARAMS = [ 'access_key', 'srfm_ai_dashboard_prompt', 'form_type' ];

/**
 * Which Add New Form screen a query string opens.
 *
 * @since 2.13.0
 * @param {URLSearchParams} params Query args.
 * @return {string} `templates`, `ai` or `start`.
 */
export const getAddNewFormScreen = ( params ) => {
	const view = params.get( 'view' );
	if ( 'templates' === view ) {
		return 'templates';
	}
	if (
		'ai' === view ||
		! isStartScreenEnabled() ||
		AI_PARAMS.some( ( param ) => params.has( param ) )
	) {
		return 'ai';
	}
	return 'start';
};

/**
 * The start screen choice still in play in this tab, if any.
 *
 * @since 2.13.0
 * @return {string} `ai`, `template`, `scratch`, or '' when none.
 */
export const getStartChoice = () => {
	try {
		const stored = JSON.parse(
			window.sessionStorage.getItem( STORAGE_KEY ) || 'null'
		);
		if (
			stored &&
			START_CHOICES.includes( stored.choice ) &&
			Date.now() - stored.at < MAX_AGE
		) {
			return stored.choice;
		}
	} catch ( error ) {
		// Storage blocked or bad JSON: no choice.
	}
	return '';
};

/**
 * Keep the stored choice only for the visit that made it. A fresh page load
 * that opens another screen (a payment or quiz link, the dashboard prompt)
 * never saw the start screen, so a choice left from earlier would credit
 * that form to a path, and a switch, that never happened. Kept when the AI
 * sign-in comes back (`access_key`), when Add New Form reloads itself
 * (reloadAddNewForm()), and on a reload or Back / Forward, which return to
 * the same visit.
 *
 * @since 2.13.0
 * @param {URLSearchParams} params     Query args the page opened with.
 * @param {string}          navigation How the page was loaded: `navigate`, `reload` or `back_forward`.
 */
export const scopeStartChoiceToVisit = (
	params,
	navigation = window.performance?.getEntriesByType?.( 'navigation' )?.[ 0 ]
		?.type || 'navigate'
) => {
	let carried = false;
	try {
		carried = '1' === window.sessionStorage.getItem( CARRY_KEY );
		window.sessionStorage.removeItem( CARRY_KEY );
	} catch ( error ) {
		// Storage blocked: there's no stored choice to keep either.
	}
	if (
		! carried &&
		'navigate' === navigation &&
		'start' !== getAddNewFormScreen( params ) &&
		! params.has( 'access_key' )
	) {
		clearStartChoice();
	}
};

/**
 * Load an Add New Form screen again as part of the same visit, e.g. the AI
 * screen after the AI sign-in or a "Try Again". A plain page load would look
 * like a new visit and drop the start screen choice.
 *
 * @since 2.13.0
 * @param {string} url Add New Form URL.
 */
export const reloadAddNewForm = ( url ) => {
	try {
		window.sessionStorage.setItem( CARRY_KEY, '1' );
	} catch ( error ) {
		// Storage blocked: there's no stored choice to keep either.
	}
	window.location.href = url;
};

/**
 * Remember the choice made on the start screen.
 *
 * @since 2.13.0
 * @param {string} choice `ai`, `template` or `scratch`.
 */
export const setStartChoice = ( choice ) => {
	try {
		window.sessionStorage.setItem(
			STORAGE_KEY,
			JSON.stringify( { choice, at: Date.now() } )
		);
	} catch ( error ) {
		// Storage blocked: the form just won't carry the choice.
	}
};

/**
 * Forget the choice, e.g. when the start screen is shown again.
 *
 * @since 2.13.0
 */
export const clearStartChoice = () => {
	try {
		window.sessionStorage.removeItem( STORAGE_KEY );
	} catch ( error ) {
		// Nothing stored.
	}
};

let queue = Promise.resolve();

/**
 * Count a step of the form start funnel. Fire and forget: tracking never
 * blocks or breaks creating a form. Path steps are only counted while a
 * start screen choice is in play.
 *
 * @since 2.13.0
 * @param {string} action `view`, `choose` or a path step (see PHP Form_Start::STEPS).
 * @param {Object} data   Extra data: `choice` and `position` for `choose`.
 * @return {Promise} Settles once the count is sent (or skipped); never rejects.
 */
export const trackStartStep = ( action, data = {} ) => {
	// Only the Add New Form screens are part of the funnel; the editor reuses
	// some of their components (e.g. the template preview).
	const onAddNewForm =
		'add-new-form' ===
		new URLSearchParams( window.location.search ).get( 'page' );
	if ( ! srfm_admin?.form_start?.tracking || ! onAddNewForm ) {
		return Promise.resolve();
	}

	const choice = data.choice || getStartChoice();
	if ( ! [ 'view', 'choose' ].includes( action ) && ! choice ) {
		return Promise.resolve();
	}

	const send = () =>
		apiFetch( {
			path: 'sureforms/v1/form-start/track',
			method: 'POST',
			data: { ...data, action, choice },
			// A choice is often the last thing before leaving the page.
			keepalive: true,
		} ).catch( () => {} );

	// One at a time: each count rewrites the stored totals, so overlapping
	// requests would drop each other's counts.
	queue = queue.then( send );
	return queue;
};

/**
 * URL of a new blank form, tagged with the start screen choice in play so the
 * form is counted for that path (a user who picked AI and then built from
 * scratch counts as AI → scratch).
 *
 * @since 2.13.0
 * @param {string} choice Choice to tag, defaults to the one in play.
 * @return {string} URL.
 */
export const getScratchUrl = ( choice = getStartChoice() ) => {
	const url = `${ srfm_admin.site_url }/wp-admin/post-new.php?post_type=sureforms_form`;
	return choice ? `${ url }&srfm_start=${ choice }` : url;
};
