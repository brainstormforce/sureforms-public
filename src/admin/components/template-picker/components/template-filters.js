import { getFeatures, isPremiumTemplate } from './template-features.js';

/**
 * Templates shown first while no filter or search is active, in this order.
 * The rest follow in catalog order.
 *
 * @since 2.13.0
 */
export const FEATURED_TEMPLATES = [
	'contact-us',
	'quote-request',
	'instant-quote-calculator',
	'callback-request',
	'appointment-request',
	'knowledge-quiz',
	'customer-feedback',
	'event-ticket-order',
	'newsletter-signup',
	'service-agreement',
	'donation-form',
	'support-request',
];

/**
 * Filters with nothing chosen.
 *
 * @since 2.13.0
 */
export const DEFAULT_FILTERS = {
	category: 'all',
	formType: 'all',
	plan: 'all',
};

// Almost every template sends a notification, so searching "email" would match them all.
const UNSEARCHED_FEATURES = [ 'notification' ];

/**
 * Text a search looks through: name, description, category, field labels and
 * features.
 *
 * @since 2.13.0
 * @param {Object} template       Template.
 * @param {Object} categoryLabels Category labels by id.
 * @return {string} Lowercase text.
 */
export const getSearchText = ( template, categoryLabels = {} ) =>
	[
		template.title,
		template.description,
		categoryLabels[ template.category ],
		...( template.fields || [] ),
		...getFeatures(
			( template.features || [] ).filter(
				( id ) => ! UNSEARCHED_FEATURES.includes( id )
			)
		).map( ( feature ) => feature.label ),
	]
		.filter( Boolean )
		.join( ' ' )
		.toLowerCase();

/**
 * Whether search words all appear in a template's search text.
 *
 * @since 2.13.0
 * @param {string} text   Search text from getSearchText().
 * @param {string} search What was typed.
 * @return {boolean} Matches.
 */
export const matchesSearch = ( text, search ) =>
	search
		.toLowerCase()
		.split( /\s+/ )
		.filter( Boolean )
		.every( ( word ) => text.includes( word ) );

/**
 * Whether a template passes the filters. Every filter combines with the
 * others and with the search.
 *
 * @since 2.13.0
 * @param {Object}  template Template.
 * @param {Object}  filters  { category, formType, plan }.
 * @param {boolean} matched  Whether it matches the search.
 * @param {string}  skip     Filter to leave out, for counting its options.
 * @return {boolean} Shown.
 */
export const passesFilters = ( template, filters, matched, skip = '' ) => {
	if ( ! matched ) {
		return false;
	}
	if (
		'category' !== skip &&
		'all' !== filters.category &&
		template.category !== filters.category
	) {
		return false;
	}
	if (
		'formType' !== skip &&
		'all' !== filters.formType &&
		( template.form_type || 'single' ) !== filters.formType
	) {
		return false;
	}
	if ( 'plan' !== skip && 'all' !== filters.plan ) {
		const premium = isPremiumTemplate( template );
		return 'premium' === filters.plan ? premium : ! premium;
	}
	return true;
};

/**
 * How many templates each option of a filter would show, given the other
 * filters and the search.
 *
 * @since 2.13.0
 * @param {Array}    templates Templates.
 * @param {Object}   filters   { category, formType, plan }.
 * @param {Function} isMatched Whether a template matches the search.
 * @param {string}   filter    'category' or 'formType'.
 * @return {Object<string, number>} Counts by option id, with `all`.
 */
export const countOptions = ( templates, filters, isMatched, filter ) => {
	const key = 'category' === filter ? 'category' : 'form_type';
	const counts = { all: 0 };
	templates.forEach( ( template ) => {
		if ( ! passesFilters( template, filters, isMatched( template ), filter ) ) {
			return;
		}
		const value = template[ key ] || ( 'form_type' === key ? 'single' : '' );
		counts.all++;
		counts[ value ] = ( counts[ value ] || 0 ) + 1;
	} );
	return counts;
};

/**
 * Whether any filter or a search is active.
 *
 * @since 2.13.0
 * @param {Object} filters { category, formType, plan }.
 * @param {string} search  Search text.
 * @return {boolean} Narrowed.
 */
export const isNarrowed = ( filters, search ) =>
	Boolean( search ) ||
	Object.keys( DEFAULT_FILTERS ).some(
		( key ) => filters[ key ] !== DEFAULT_FILTERS[ key ]
	);

/**
 * Templates in the order the grid shows them: the featured ones first while
 * nothing narrows the list, else catalog order.
 *
 * @since 2.13.0
 * @param {Array}   templates Templates in catalog order.
 * @param {boolean} narrowed  A filter or search is active.
 * @return {Array} Templates.
 */
export const sortTemplates = ( templates, narrowed ) => {
	if ( narrowed ) {
		return templates;
	}
	const rank = ( template ) => {
		const index = FEATURED_TEMPLATES.indexOf( template.id );
		return -1 === index ? FEATURED_TEMPLATES.length : index;
	};
	// Array.prototype.sort is stable, so the rest keep catalog order.
	return [ ...templates ].sort( ( a, b ) => rank( a ) - rank( b ) );
};
