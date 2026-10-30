/* eslint-env jest */
/**
 * frontend.js's dark-background handling runs on load and again when the
 * template preview restyles a form in place, so it has to follow a form from
 * light to dark and back.
 */
require( '../frontend.js' );

const DARK = 'background-color: rgb(15, 23, 42); --srfm-color-input-text: #E2E8F0';
const LIGHT =
	'background-color: rgb(234, 242, 255); --srfm-color-input-text: #1E293B';

const render = ( look, classes = '' ) => {
	document.body.innerHTML = `<div class="srfm-form-container ${ classes }" style="${ look }"><style>.srfm-form-container-7 { --srfm-color-scheme-primary: #2563EB; }</style><form form-id="7"></form></div>`;
	return document.querySelector( '.srfm-form-container' );
};

const normalize = ( container ) =>
	window.srfm.normalizeCSSVariablesForDarkBackground( container );

const overrides = ( container ) =>
	container.querySelectorAll( 'style[data-srfm-dark-bg-overrides]' );

describe( 'normalizeCSSVariablesForDarkBackground', () => {
	afterEach( () => {
		document.body.innerHTML = '';
	} );

	it( 'gives a dark form a dark menu, after its own styles', () => {
		const container = render( DARK );
		normalize( container );

		expect( container.classList.contains( 'srfm-has-dark-bg' ) ).toBe(
			true
		);
		const [ node ] = overrides( container );
		expect( node.textContent ).toBe(
			'.srfm-form-container-7 { --srfm-expandable-menu-background: #2e2e2e; }'
		);
		expect( node.previousElementSibling.textContent ).toContain(
			'--srfm-color-scheme-primary'
		);
		// The form's own block is left as it was rendered.
		expect( node.previousElementSibling.textContent ).not.toContain(
			'#2e2e2e'
		);
	} );

	it( 'follows the form from dark to light and back', () => {
		const container = render( DARK );
		normalize( container );

		container.setAttribute( 'style', LIGHT );
		normalize( container );
		expect( container.classList.contains( 'srfm-has-dark-bg' ) ).toBe(
			false
		);
		expect( overrides( container ) ).toHaveLength( 0 );

		container.setAttribute( 'style', DARK );
		normalize( container );
		normalize( container );
		expect( container.classList.contains( 'srfm-has-dark-bg' ) ).toBe(
			true
		);
		expect( overrides( container ) ).toHaveLength( 1 );
	} );

	it( 'keeps a dark-background class the site added itself', () => {
		const container = render( LIGHT, 'srfm-has-dark-bg' );
		normalize( container );
		expect( container.classList.contains( 'srfm-has-dark-bg' ) ).toBe(
			true
		);

		container.setAttribute( 'style', DARK );
		normalize( container );
		container.setAttribute( 'style', LIGHT );
		normalize( container );
		expect( container.classList.contains( 'srfm-has-dark-bg' ) ).toBe(
			true
		);
	} );

	it( 'only marks a de-skinned form', () => {
		const container = render( DARK, 'srfm-styling-none' );
		container.querySelector( 'style' ).remove();
		normalize( container );

		expect( container.classList.contains( 'srfm-has-dark-bg' ) ).toBe(
			true
		);
		expect( overrides( container ) ).toHaveLength( 0 );
	} );

	it( 'adds its own style block when the form has none', () => {
		const container = render( DARK );
		container.querySelector( 'style' ).remove();
		normalize( container );

		expect( overrides( container ) ).toHaveLength( 1 );
		expect( container.lastElementChild ).toBe( overrides( container )[ 0 ] );
	} );
} );
