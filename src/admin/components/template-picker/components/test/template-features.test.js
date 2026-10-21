/* eslint-env jest */
// lucide-react ships ESM that jest does not transform; icons are irrelevant here.
jest.mock( 'lucide-react', () => new Proxy( {}, { get: () => () => null } ) );
jest.mock( '@Utils/Helpers', () => ( { cn: ( ...classes ) => classes.join( ' ' ) } ), {
	virtual: true,
} );
jest.mock( '@Utils/form-templates', () => ( { trackTemplateAction: () => {} } ), {
	virtual: true,
} );

import { showsPreviewImage } from '../template-features';

describe( 'showsPreviewImage', () => {
	it( 'keeps the live preview for an unlocked template with a picture', () => {
		expect(
			showsPreviewImage( {
				is_locked: false,
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
