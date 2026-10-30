/* eslint-env jest */
// lucide-react ships ESM that jest does not transform; icons are irrelevant here.
jest.mock( 'lucide-react', () => new Proxy( {}, { get: () => () => null } ) );
jest.mock( '@Utils/Helpers', () => ( { cn: ( ...classes ) => classes.join( ' ' ) } ), {
	virtual: true,
} );
jest.mock( '@Utils/form-templates', () => ( { trackTemplateAction: () => {} } ), {
	virtual: true,
} );

import {
	DEFAULT_FILTERS,
	FEATURED_TEMPLATES,
	countOptions,
	getSearchText,
	isNarrowed,
	matchesSearch,
	passesFilters,
	sortTemplates,
} from '../template-filters';

const templates = [
	{
		id: 'contact-us',
		title: 'Contact Us',
		description: 'Let visitors send you a message.',
		category: 'contact',
		form_type: 'single',
		requires: 'free',
		fields: [ 'Name', 'Email', 'Message' ],
		features: [ 'notification', 'consent' ],
	},
	{
		id: 'quote-request',
		title: 'Quote Request',
		description: 'Collect project details for an estimate.',
		category: 'contact',
		form_type: 'multistep',
		requires: 'free',
		fields: [ 'Name', 'Budget' ],
		features: [ 'notification' ],
	},
	{
		id: 'event-ticket-order',
		title: 'Event Ticket Order',
		description: 'Sell tickets.',
		category: 'payments',
		form_type: 'single',
		requires: 'business',
		fields: [ 'Tickets' ],
		features: [ 'notification', 'payment' ],
	},
	{
		id: 'lead-qualifier',
		title: 'Lead Qualifier',
		description: 'One question at a time.',
		category: 'contact',
		form_type: 'conversational',
		requires: 'pro',
		fields: [ 'Role' ],
		features: [ 'notification', 'autoresponder' ],
	},
];

const labels = { contact: 'Contact & Leads', payments: 'Payments' };
const all = () => true;

describe( 'search', () => {
	const find = ( search ) =>
		templates
			.filter( ( t ) =>
				matchesSearch( getSearchText( t, labels ), search )
			)
			.map( ( t ) => t.id );

	it( 'needs every word, in any field', () => {
		expect( find( 'quote budget' ) ).toEqual( [ 'quote-request' ] );
		expect( find( 'payments' ) ).toEqual( [ 'event-ticket-order' ] );
		expect( find( 'leads' ) ).toEqual( [
			'contact-us',
			'quote-request',
			'lead-qualifier',
		] );
	} );

	it( 'does not match the notification every template sends', () => {
		expect( find( 'notification' ) ).toEqual( [] );
		// Other email features still match.
		expect( find( 'auto-reply email' ) ).toEqual( [ 'lead-qualifier' ] );
	} );

	it( 'matches everything when empty', () => {
		expect( find( '  ' ) ).toHaveLength( templates.length );
	} );
} );

describe( 'filters', () => {
	const shown = ( filters, isMatched = all ) =>
		templates
			.filter( ( t ) =>
				passesFilters( t, { ...DEFAULT_FILTERS, ...filters }, isMatched( t ) )
			)
			.map( ( t ) => t.id );

	it( 'combine with each other and the search', () => {
		expect( shown( { category: 'contact', plan: 'free' } ) ).toEqual( [
			'contact-us',
			'quote-request',
		] );
		expect( shown( { plan: 'premium' } ) ).toEqual( [
			'event-ticket-order',
			'lead-qualifier',
		] );
		expect( shown( { formType: 'conversational' } ) ).toEqual( [
			'lead-qualifier',
		] );
		expect(
			shown( { category: 'contact' }, ( t ) => 'quote-request' === t.id )
		).toEqual( [ 'quote-request' ] );
	} );

	it( 'count each option against the other filters', () => {
		const filters = { ...DEFAULT_FILTERS, plan: 'free', category: 'payments' };
		expect(
			countOptions( templates, filters, all, 'category' )
		).toEqual( { all: 2, contact: 2 } );
		expect(
			countOptions( templates, filters, all, 'formType' )
		).toEqual( { all: 0 } );
		expect(
			countOptions(
				templates,
				DEFAULT_FILTERS,
				( t ) => 'contact' === t.category,
				'formType'
			)
		).toEqual( { all: 3, single: 1, multistep: 1, conversational: 1 } );
	} );

	it( 'treat a template without a form type as single page', () => {
		const [ first ] = templates;
		const { form_type: formType, ...plain } = first;
		expect( formType ).toBe( 'single' );
		expect(
			passesFilters( plain, { ...DEFAULT_FILTERS, formType: 'single' }, true )
		).toBe( true );
	} );

	it( 'know when the list is narrowed', () => {
		expect( isNarrowed( DEFAULT_FILTERS, '' ) ).toBe( false );
		expect( isNarrowed( DEFAULT_FILTERS, 'quote' ) ).toBe( true );
		expect( isNarrowed( { ...DEFAULT_FILTERS, plan: 'free' }, '' ) ).toBe(
			true
		);
	} );
} );

describe( 'sortTemplates', () => {
	it( 'puts featured templates first while nothing narrows the list', () => {
		const ids = sortTemplates( templates, false ).map( ( t ) => t.id );
		expect( ids ).toEqual( [
			'contact-us',
			'quote-request',
			'event-ticket-order',
			'lead-qualifier',
		] );
		expect( FEATURED_TEMPLATES.indexOf( 'event-ticket-order' ) ).toBeGreaterThan(
			FEATURED_TEMPLATES.indexOf( 'quote-request' )
		);
	} );

	it( 'keeps catalog order when narrowed', () => {
		const reversed = [ ...templates ].reverse();
		expect( sortTemplates( reversed, true ) ).toBe( reversed );
		expect( sortTemplates( reversed, false )[ 0 ].id ).toBe( 'contact-us' );
	} );
} );
