/* eslint-env jest */
/**
 * getStarterPrompts() feeds the starter cards on the "Generate with AI" screen.
 * Default prompts must only ask for fields the free plugin can generate, since
 * the field mapper silently drops pro-only fields (upload, date, rating, ...)
 * when SureForms Pro is not active.
 */
// lucide-react ships ESM that jest does not transform; icons are irrelevant here.
jest.mock( 'lucide-react', () => ( {
	Mail: () => null,
	FileText: () => null,
	CalendarCheck: () => null,
	MessageSquare: () => null,
} ) );

import { addFilter, removeAllFilters } from '@wordpress/hooks';
import {
	getStarterPrompts,
	isFromStarter,
	MAX_PROMPT_LENGTH,
} from '../starter-prompts';

const FILTER = 'srfm.aiFormScreen.starterPrompts';

afterEach( () => {
	removeAllFilters( FILTER );
} );

describe( 'getStarterPrompts', () => {
	it( 'returns the four default starters with unique ids', () => {
		const starters = getStarterPrompts();
		expect( starters.map( ( starter ) => starter.id ) ).toEqual( [
			'contact',
			'quote-request',
			'event-registration',
			'feedback',
		] );
	} );

	it( 'keeps default prompts within the prompt length limit', () => {
		getStarterPrompts().forEach( ( starter ) => {
			expect( starter.prompt.length ).toBeLessThanOrEqual(
				MAX_PROMPT_LENGTH
			);
		} );
	} );

	it( 'does not ask for pro-only fields in default prompts', () => {
		const proOnly =
			/\b(upload|attachment|date picker|time picker|signature|slider|star rating|nps)\b/i;
		getStarterPrompts().forEach( ( starter ) => {
			expect( starter.prompt ).not.toMatch( proOnly );
		} );
	} );

	it( 'drops invalid and duplicate starters from filters', () => {
		addFilter( FILTER, 'test', ( starters ) => [
			...starters,
			{ id: 'contact', title: 'Duplicate', prompt: 'Duplicate' },
			{ id: 'no-prompt', title: 'Missing prompt' },
		] );
		const ids = getStarterPrompts().map( ( starter ) => starter.id );
		expect( ids.filter( ( id ) => id === 'contact' ) ).toHaveLength( 1 );
		expect( ids ).not.toContain( 'no-prompt' );
	} );

	it.each( [
		[ 'a number prompt', { prompt: 123 } ],
		[ 'an object title', { title: { text: 'Bad' } } ],
		[ 'a number id', { id: 7 } ],
		[ 'an array description', { description: [ 'Bad' ] } ],
		[ 'an element icon', { icon: { $$typeof: Symbol.for( 'react.element' ), type: 'svg', props: {} } } ],
		[ 'a string icon', { icon: 'mail' } ],
	] )( 'drops a filtered starter with %s', ( _label, override ) => {
		addFilter( FILTER, 'test', () => [
			{ id: 'bad', title: 'Bad', prompt: 'Bad', ...override },
			{ id: 'good', title: 'Good', prompt: 'Good' },
		] );
		expect( getStarterPrompts().map( ( starter ) => starter.id ) ).toEqual(
			[ 'good' ]
		);
	} );

	it( 'keeps function and forwardRef icon components', () => {
		const FunctionIcon = () => null;
		const ForwardRefIcon = {
			$$typeof: Symbol.for( 'react.forward_ref' ),
			render: () => null,
		};
		addFilter( FILTER, 'test', () => [
			{ id: 'fn', title: 'Fn', prompt: 'Fn', icon: FunctionIcon },
			{ id: 'ref', title: 'Ref', prompt: 'Ref', icon: ForwardRefIcon },
		] );
		expect( getStarterPrompts().map( ( starter ) => starter.id ) ).toEqual(
			[ 'fn', 'ref' ]
		);
	} );

	it( 'caps the number of starters and trims long prompts', () => {
		addFilter( FILTER, 'test', () => [
			{ id: 'a', title: 'A', prompt: 'x'.repeat( 2500 ) },
			{ id: 'b', title: 'B', prompt: 'b' },
			{ id: 'c', title: 'C', prompt: 'c' },
			{ id: 'd', title: 'D', prompt: 'd' },
			{ id: 'e', title: 'E', prompt: 'e' },
		] );
		const starters = getStarterPrompts();
		expect( starters ).toHaveLength( 4 );
		expect( starters[ 0 ].prompt ).toHaveLength( MAX_PROMPT_LENGTH );
	} );

	it( 'returns an empty list when a filter returns a non-array', () => {
		addFilter( FILTER, 'test', () => null );
		expect( getStarterPrompts() ).toEqual( [] );
	} );
} );

describe( 'isFromStarter', () => {
	const contact = () =>
		getStarterPrompts().find( ( starter ) => 'contact' === starter.id )
			.prompt;

	it( 'keeps the untouched and lightly edited prompt', () => {
		expect( isFromStarter( contact(), contact() ) ).toBe( true );
		expect(
			isFromStarter( contact() + ' Add a company name field.', contact() )
		).toBe( true );
		expect(
			isFromStarter( contact().replace( 'subject and ', '' ), contact() )
		).toBe( true );
	} );

	it( 'drops a prompt the user wrote over the example', () => {
		expect(
			isFromStarter(
				'Booking form for my salon with name, email, service and preferred date.',
				contact()
			)
		).toBe( false );
		expect( isFromStarter( '', contact() ) ).toBe( false );
	} );

	it( 'drops a different form that shares the field names', () => {
		expect(
			isFromStarter(
				'Job application form with full name, email address, phone number, a resume upload and a message to the hiring team',
				contact()
			)
		).toBe( false );
	} );

	it( 'keeps a card whose form type is reworded but still there', () => {
		const feedback = getStarterPrompts().find(
			( starter ) => 'feedback' === starter.id
		).prompt;
		expect(
			isFromStarter(
				feedback.replace( 'Customer feedback form', 'Feedback form for clients' ),
				feedback
			)
		).toBe( true );
	} );

	it( 'counts words in languages written without spaces', () => {
		// Without spaces, a whole phrase would count as one word, so changing a
		// single character in two phrases would drop the card.
		const prompt = '联系表单，包含姓名、电子邮件和留言。';
		expect(
			isFromStarter( '联系表单，包括姓名、电子邮件和备注。', prompt )
		).toBe( true );
	} );

	it( 'never counts one default card prompt as another card', () => {
		const starters = getStarterPrompts();
		starters.forEach( ( a ) => {
			starters.forEach( ( b ) => {
				expect( isFromStarter( a.prompt, b.prompt ) ).toBe( a.id === b.id );
			} );
		} );
	} );

	it( 'is false for an empty starter prompt', () => {
		expect( isFromStarter( 'Anything', '' ) ).toBe( false );
	} );
} );
