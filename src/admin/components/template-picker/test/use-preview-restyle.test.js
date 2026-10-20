/* eslint-env jest */
import { createElement, createRoot } from '@wordpress/element';
// React's own act(): react-dom/test-utils' is deprecated in React 18.3. React
// comes with @wordpress/element, which re-exports it.
// eslint-disable-next-line import/no-extraneous-dependencies
import { act } from 'react';
import usePreviewRestyle, {
	PROBE_GRACE,
	PROBE_TIMEOUT,
	RESTYLE_DELAY,
	styledPreviewUrl,
} from '../use-preview-restyle';
import { resolveLook } from '../template-colors';

global.IS_REACT_ACT_ENVIRONMENT = true;

const presets = [
	{
		id: 'ocean',
		page: '#E3EBFB',
		background: '#FFFFFF',
		accent: '#2563EB',
		text: '#1E293B',
		onPrimary: '#FFFFFF',
	},
	{
		id: 'midnight',
		page: '#E4E8EF',
		background: '#111827',
		accent: '#38BDF8',
		text: '#E2E8F0',
		onPrimary: '#0F172A',
	},
];

const template = {
	id: 'contact-us',
	theme: 'ocean',
	preview_url: 'http://localhost/?srfm_template_preview=contact-us',
};

const formCss = ( look ) =>
	`.srfm-form-container-1 { --srfm-color-scheme-primary: ${ look.primary }; --srfm-color-scheme-text: ${ look.text }; --srfm-color-scheme-text-on-primary: ${ look.onPrimary }; --srfm-bg-color: ${ look.background }; }`;

const ocean = resolveLook( {}, 'ocean', presets );
const midnightStyle = { preset: 'midnight' };
const midnight = resolveLook( midnightStyle, 'ocean', presets );

// The preview page, rendered in the template's own look.
const renderPage = () => {
	document.head.innerHTML = `<style>${ formCss( ocean ) }</style>`;
	document.body.innerHTML =
		'<div class="srfm-template-preview"><div class="srfm-form-container"></div></div>';
};
const pageCss = () => document.head.querySelector( 'style' ).textContent;

// The probe render: the same form in midnight.
const probeHtml = `<html><head><style>${ formCss(
	resolveLook( { preset: 'midnight' }, 'midnight', presets )
) }</style></head><body></body></html>`;

let root;
let container;
let result;
let fetchCalls;

const Harness = ( props ) => {
	result = usePreviewRestyle( props );
	return null;
};

const render = ( props = {} ) =>
	act( () =>
		root.render(
			createElement( Harness, {
				template,
				style: {},
				look: ocean,
				renderedLook: ocean,
				presets,
				active: true,
				canRestyle: true,
				...props,
			} )
		)
	);

// Changes the chosen style, as the Customize panel does.
const choose = ( style, props = {} ) =>
	render( {
		style,
		look: resolveLook( style, 'ocean', presets ),
		...props,
	} );

// A frame whose page is the jsdom document.
const frame = ( src ) => ( { src, contentDocument: document } );

const load = ( index, src = result.frames[ index ] ) =>
	act( () => {
		result.setFrameNode( index )( frame( src ) );
		result.onFrameLoad( index, { currentTarget: frame( src ) } );
	} );

// A fetch that settles when the test says so.
const deferredFetch = () => {
	fetchCalls = [];
	window.fetch = jest.fn( ( url, options ) => {
		let resolve;
		const promise = new Promise( ( res, rej ) => {
			resolve = res;
			options.signal.addEventListener( 'abort', () =>
				rej( new Error( 'aborted' ) )
			);
		} );
		fetchCalls.push( { url, options, resolve } );
		return promise;
	} );
};

const answerProbe = ( html = probeHtml ) =>
	act( async () => {
		fetchCalls[ 0 ].resolve( { ok: true, text: async () => html } );
	} );

const wait = ( ms ) =>
	act( () => {
		jest.advanceTimersByTime( ms );
	} );

beforeEach( () => {
	jest.useFakeTimers();
	deferredFetch();
	renderPage();
	window.srfm = { normalizeCSSVariablesForDarkBackground: jest.fn() };
	container = document.createElement( 'div' );
	root = createRoot( container );
} );

afterEach( () => {
	act( () => root.unmount() );
	jest.useRealTimers();
	delete window.srfm;
	delete window.fetch;
	delete window.crossOriginIsolated;
} );

describe( 'usePreviewRestyle', () => {
	it( 'restyles the shown page in place once the other render is in', async () => {
		await render();
		await load( 0 );
		await answerProbe();
		expect( fetchCalls[ 0 ].url ).toBe(
			styledPreviewUrl( template, { preset: 'midnight' } )
		);

		await choose( midnightStyle );

		expect( pageCss() ).toBe( formCss( midnight ) );
		expect(
			window.srfm.normalizeCSSVariablesForDarkBackground
		).toHaveBeenCalled();
		expect( result.frames ).toEqual( [ template.preview_url, '' ] );
		expect( result.isUpdating ).toBe( false );
	} );

	it( 'waits a little for the other render, then renders the page again', async () => {
		await render();
		await load( 0 );

		await choose( midnightStyle );
		// Not dead while the other render is on its way.
		expect( result.isUpdating ).toBe( true );
		await wait( PROBE_GRACE - 1 );
		expect( result.frames[ 1 ] ).toBe( '' );

		await wait( 1 );
		expect( result.frames ).toEqual( [
			template.preview_url,
			styledPreviewUrl( template, midnightStyle ),
		] );
		expect( result.isUpdating ).toBe( true );
	} );

	it( 'restyles in place when the other render arrives in time', async () => {
		await render();
		await load( 0 );
		await choose( midnightStyle );
		await wait( PROBE_GRACE / 2 );

		await answerProbe();

		expect( pageCss() ).toBe( formCss( midnight ) );
		await wait( PROBE_GRACE );
		expect( result.frames ).toEqual( [ template.preview_url, '' ] );
		expect( result.isUpdating ).toBe( false );
	} );

	it( 'gives up on a render that never comes', async () => {
		await render();
		await load( 0 );

		await wait( PROBE_TIMEOUT );
		await act( async () => {} );
		expect( fetchCalls[ 0 ].options.signal.aborted ).toBe( true );

		// From then on a change renders the page again, without the wait.
		await choose( midnightStyle );
		await wait( RESTYLE_DELAY );
		expect( result.frames[ 1 ] ).toBe(
			styledPreviewUrl( template, midnightStyle )
		);
	} );

	it( 'drops the other render when the preview closes', async () => {
		await render();
		const { signal } = fetchCalls[ 0 ].options;
		await act( () => root.unmount() );
		expect( signal.aborted ).toBe( true );
		root = createRoot( container );
	} );

	it.each( [
		[ 'a picture is shown instead', { active: false } ],
		[ 'colors were never up for change', { canRestyle: false } ],
	] )( 'fetches no other render when %s', async ( _, props ) => {
		await render( props );
		await load( 0 );
		expect( window.fetch ).not.toHaveBeenCalled();
	} );

	it( "fetches no other render for a page it can't read", async () => {
		window.crossOriginIsolated = true;
		await render();
		await load( 0 );
		expect( window.fetch ).not.toHaveBeenCalled();

		await choose( midnightStyle );
		await wait( RESTYLE_DELAY );
		expect( result.frames[ 1 ] ).toBe(
			styledPreviewUrl( template, midnightStyle )
		);
	} );

	it( 'keeps the shown page until the new render has loaded', async () => {
		delete window.srfm;
		await render();
		await load( 0 );
		await answerProbe();

		await choose( midnightStyle );
		await wait( RESTYLE_DELAY );

		// Couldn't be restyled in place, so it renders again behind the page.
		expect( pageCss() ).toBe( formCss( ocean ) );
		expect( result.shown ).toBe( 0 );
		expect( result.status ).toBe( 'ready' );
		expect( result.isUpdating ).toBe( true );

		await load( 1 );
		expect( result.shown ).toBe( 1 );
		expect( result.isUpdating ).toBe( false );
		// The page that was shown stays until the next change, to fade out.
		expect( result.frames[ 0 ] ).toBe( template.preview_url );
	} );
} );
