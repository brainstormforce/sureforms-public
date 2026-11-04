/* eslint-env jest */
/**
 * On the Instant Form page frontend.js keeps the SureForms branding visible and
 * the form wrapper as tall as the form. Both must react to changes on the page,
 * not run on a timer or on every pointer move.
 */

const observed = [];
global.ResizeObserver = class {
	constructor( callback ) {
		this.callback = callback;
	}
	observe( target ) {
		observed.push( { target, callback: this.callback } );
	}
};

// Mutation observers are notified on a microtask, before any timer can run.
const flush = () => Promise.resolve();

let setIntervalSpy;
let branding;
let form;
let wrapper;
let container;

beforeAll( () => {
	document.body.innerHTML = `
		<div id="srfm-single-page-container">
			<div class="srfm-form-wrapper">
				<div class="srfm-form-container" style="position: absolute;">
					<form class="srfm-form"></form>
				</div>
			</div>
			<a class="srfm-branding" href="#">Crafted with SureForms</a>
		</div>`;
	branding = document.querySelector( '.srfm-branding' );
	form = document.querySelector( 'form.srfm-form' );
	wrapper = document.querySelector( '.srfm-form-wrapper' );
	container = document.querySelector( '.srfm-form-container' );

	setIntervalSpy = jest.spyOn( window, 'setInterval' );
	require( '../frontend.js' );
	window.dispatchEvent( new Event( 'load' ) );
} );

describe( 'Instant Form branding', () => {
	it( 'does not poll', () => {
		expect( setIntervalSpy ).not.toHaveBeenCalled();
	} );

	it( 'restores the branding when something hides it', async () => {
		branding.style.display = 'none';
		branding.style.opacity = '0';
		branding.style.visibility = 'hidden';
		await flush();

		expect( branding.style.display ).toBe( 'block' );
		expect( branding.style.opacity ).toBe( '1' );
		expect( branding.style.visibility ).toBe( 'visible' );
	} );

	it( 'hides the branding once the form is hidden', async () => {
		form.style.display = 'none';
		await flush();

		expect( branding.style.display ).toBe( 'none' );

		form.style.display = '';
		await flush();

		expect( branding.style.display ).toBe( 'block' );
	} );
} );

describe( 'Instant Form wrapper height', () => {
	it( 'does not rewrite the wrapper height on pointer movement', () => {
		wrapper.style.height = '';
		window.dispatchEvent( new Event( 'mousemove' ) );

		expect( wrapper.style.height ).toBe( '' );
	} );

	it( 'follows the form container when it resizes', () => {
		const entry = observed.find( ( o ) => o.target === container );
		expect( entry ).toBeDefined();

		wrapper.style.height = '';
		entry.callback( [] );

		expect( wrapper.style.height ).toBe( `${ container.clientHeight }px` );
	} );
} );
