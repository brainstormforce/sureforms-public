import apiFetch from '@wordpress/api-fetch';

/**
 * The ways to start a new form on the Add New Form start screen.
 *
 * @since x.x.x
 */
export const START_CHOICES = [ 'ai', 'template', 'scratch' ];

// The choice lives for the rest of the tab session, so a form created after
// the AI sign-in redirect still counts for the path picked on the start screen.
const STORAGE_KEY = 'srfm_start_choice';
const MAX_AGE = 2 * 60 * 60 * 1000;

/**
 * Whether the start screen is shown, or skipped straight to the AI screen.
 *
 * @since x.x.x
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
 * @since x.x.x
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
 * @since x.x.x
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
 * Remember the choice made on the start screen.
 *
 * @since x.x.x
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
 * @since x.x.x
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
 * @since x.x.x
 * @param {string} action `view`, `choose` or a path step (see PHP Form_Start::STEPS).
 * @param {Object} data   Extra data: `choice` and `position` for `choose`.
 */
export const trackStartStep = ( action, data = {} ) => {
	// Only the Add New Form screens are part of the funnel; the editor reuses
	// some of their components (e.g. the template preview).
	const onAddNewForm =
		'add-new-form' ===
		new URLSearchParams( window.location.search ).get( 'page' );
	if ( ! srfm_admin?.form_start?.tracking || ! onAddNewForm ) {
		return;
	}

	const choice = data.choice || getStartChoice();
	if ( ! [ 'view', 'choose' ].includes( action ) && ! choice ) {
		return;
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
};

/**
 * URL of a new blank form, tagged with the start screen choice in play so the
 * form is counted for that path (a user who picked AI and then built from
 * scratch counts as AI → scratch).
 *
 * @since x.x.x
 * @param {string} choice Choice to tag, defaults to the one in play.
 * @return {string} URL.
 */
export const getScratchUrl = ( choice = getStartChoice() ) => {
	const url = `${ srfm_admin.site_url }/wp-admin/post-new.php?post_type=sureforms_form`;
	return choice ? `${ url }&srfm_start=${ choice }` : url;
};
