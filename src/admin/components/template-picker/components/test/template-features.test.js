/* eslint-env jest */
// lucide-react ships ESM that jest does not transform; icons are irrelevant here.
jest.mock( 'lucide-react', () => new Proxy( {}, { get: () => () => null } ) );
jest.mock( '@Utils/Helpers', () => ( { cn: ( ...classes ) => classes.join( ' ' ) } ), {
	virtual: true,
} );
jest.mock( '@Utils/form-templates', () => ( { trackTemplateAction: () => {} } ), {
	virtual: true,
} );

import { getCardImage, showsPreviewImage } from '../template-features';

describe( 'getCardImage', () => {
	it( 'uses the card picture of a template that can be used', () => {
		expect(
			getCardImage( {
				is_locked: false,
				thumbnail: 'https://example.com/contact-us.webp',
				preview_image: '',
			} )
		).toBe( 'https://example.com/contact-us.webp' );
	} );

	it( 'falls back to the Premium picture', () => {
		expect(
			getCardImage( {
				is_locked: true,
				preview_image: 'https://example.com/quiz.webp',
			} )
		).toBe( 'https://example.com/quiz.webp' );
	} );

	it( 'is empty when the template has no picture', () => {
		expect( getCardImage( { thumbnail: '', preview_image: '' } ) ).toBe( '' );
		expect( getCardImage( undefined ) ).toBe( '' );
	} );
} );

describe( 'showsPreviewImage', () => {
	it( 'keeps the live preview for an unlocked template with a picture', () => {
		expect(
			showsPreviewImage( {
				is_locked: false,
				thumbnail: 'https://example.com/contact-us.webp',
				preview_image: 'https://example.com/contact-us.webp',
			} )
		).toBe( false );
	} );

	it( 'shows the picture for a locked template', () => {
		expect(
			showsPreviewImage( {
				is_locked: true,
				preview_image: 'https://example.com/quiz.webp',
			} )
		).toBe( true );
	} );
} );
