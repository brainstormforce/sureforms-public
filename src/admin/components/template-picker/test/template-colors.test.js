/* eslint-env jest */
import {
	getProbePreset,
	resolveLook,
	restyleDocument,
} from '../template-colors';

// Same values as Form_Templates::get_themes_for_js().
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
		default: {
			background: '#FFFFFF',
			accent: '#0369A1',
			text: '#1E293B',
			onPrimary: '#FFFFFF',
		},
	},
	{
		id: 'mint',
		page: '#DEF2E8',
		background: '#FFFFFF',
		accent: '#047857',
		text: '#1F2F2A',
		onPrimary: '#FFFFFF',
	},
];

// The parts of the form's <style> block that carry the theme's colors.
const formCss = ( look, id = 1 ) => `.srfm-form-container-${ id } {
	--srfm-color-scheme-primary: ${ look.primary };
	--srfm-color-scheme-text-on-primary: ${ look.onPrimary };
	--srfm-color-scheme-text: ${ look.text };
	--srfm-color-input-border: hsl( from ${ look.text } h s l / 0.25 );
	--srfm-btn-color-hover: hsl( from ${ look.primary } h s l / 0.9 );
	--srfm-bg-color: ${ look.background };
	--srfm-page-break-back-btn-bg-hover: ${ look.text }0D;
	--srfm-page-break-back-btn-color-hover: ${ look.text }CC;
	--srfm-bg-overlay-color: #FFFFFF75;
}`;

const renderPreview = ( look ) => {
	document.head.innerHTML = `<style>body { color: #2563EB; }</style><style>${ formCss(
		look
	) }</style>`;
	return document;
};

describe( 'resolveLook', () => {
	it( "keeps a preset's own text colors and page", () => {
		expect( resolveLook( {}, 'mint', presets ) ).toEqual( {
			page: '#DEF2E8',
			background: '#FFFFFF',
			primary: '#047857',
			text: '#1F2F2A',
			onPrimary: '#FFFFFF',
		} );
	} );

	it( 'starts a template on a dark theme light, and the preset stays dark', () => {
		expect( resolveLook( {}, 'midnight', presets ) ).toEqual( {
			page: '#E4E8EF',
			background: '#FFFFFF',
			primary: '#0369A1',
			text: '#1E293B',
			onPrimary: '#FFFFFF',
		} );
		expect(
			resolveLook( { preset: 'midnight' }, 'midnight', presets )
		).toEqual( {
			page: '#E4E8EF',
			background: '#111827',
			primary: '#38BDF8',
			text: '#E2E8F0',
			onPrimary: '#0F172A',
		} );
	} );

	it( 'picks readable text for chosen colors, like the server', () => {
		const look = resolveLook(
			{ background: '#0F172A', accent: '#FACC15' },
			'ocean',
			presets
		);
		expect( look.background ).toBe( '#0F172A' );
		expect( look.page ).toBe( '#E3EBFB' );
		expect( look.text ).toBe( '#E2E8F0' );
		expect( look.onPrimary ).toBe( '#0F172A' );
	} );
} );

describe( 'getProbePreset', () => {
	it( 'picks a preset that differs in every color', () => {
		expect(
			getProbePreset( resolveLook( {}, 'ocean', presets ), presets )
		).toBe( 'midnight' );
		expect(
			getProbePreset( resolveLook( {}, 'midnight', presets ), presets )
		).toBe( 'midnight' );
	} );

	it( 'passes over a preset that repeats a color of its own', () => {
		// A white form with white button text can't tell the two apart.
		const list = [ presets[ 1 ], presets[ 0 ] ];
		expect(
			getProbePreset(
				resolveLook( { preset: 'midnight' }, 'ocean', list ),
				list
			)
		).toBe( '' );
	} );

	it( 'passes over a preset that shares even one color', () => {
		const ocean = presets[ 0 ];
		// Same accent as ocean: listed first, so a preset that differs in
		// some colors only would be picked.
		const sky = { ...presets[ 1 ], id: 'sky', accent: ocean.accent };
		const list = [ ocean, sky, presets[ 1 ] ];
		expect( getProbePreset( resolveLook( {}, 'ocean', list ), list ) ).toBe(
			'midnight'
		);
		expect(
			getProbePreset( resolveLook( {}, 'ocean', [ ocean, sky ] ), [
				ocean,
				sky,
			] )
		).toBe( '' );
	} );
} );

describe( 'restyleDocument', () => {
	// Ocean's form and button text are both white, like the fixed overlay color.
	const from = resolveLook( {}, 'ocean', presets );
	const probeLook = resolveLook( { preset: 'midnight' }, 'ocean', presets );
	// Rendered as another in-memory form, so with another form id.
	const probe = { blocks: [ formCss( probeLook, 2 ) ], look: probeLook };
	// frontend.js's dark-background handling, on the preview's window.
	const normalize = jest.fn();

	beforeEach( () => {
		normalize.mockClear();
		window.srfm = { normalizeCSSVariablesForDarkBackground: normalize };
	} );

	afterEach( () => {
		delete window.srfm;
		document.body.innerHTML = '';
	} );

	it( "swaps the form's theme colors in place, as often as asked", () => {
		const doc = renderPreview( from );
		const mint = resolveLook( { preset: 'mint' }, 'ocean', presets );
		expect( restyleDocument( doc, from, mint, probe ) ).toBe( true );
		expect( doc.querySelectorAll( 'style' )[ 1 ].textContent ).toBe(
			formCss( mint )
		);

		const custom = resolveLook(
			{ background: '#0F172A', accent: '#FACC15' },
			'ocean',
			presets
		);
		expect( restyleDocument( doc, from, custom, probe ) ).toBe( true );
		expect( doc.querySelectorAll( 'style' )[ 1 ].textContent ).toBe(
			formCss( custom )
		);

		expect( restyleDocument( doc, from, from, probe ) ).toBe( true );
		expect( doc.querySelectorAll( 'style' )[ 1 ].textContent ).toBe(
			formCss( from )
		);
	} );

	it( 'keeps what scripts added to the block after it loaded', () => {
		const doc = renderPreview( from );
		const node = doc.querySelectorAll( 'style' )[ 1 ];
		const added = '\n.srfm-form-container-1 { --srfm-added: #123456; }';
		node.textContent += added;
		const mint = resolveLook( { preset: 'mint' }, 'ocean', presets );
		expect( restyleDocument( doc, from, mint, probe ) ).toBe( true );
		expect( node.textContent ).toBe( formCss( mint ) + added );
	} );

	it( 'runs the dark-background handling again for each form', () => {
		const doc = renderPreview( from );
		document.body.innerHTML =
			'<div class="srfm-form-container"></div><div class="srfm-form-container"></div>';
		const midnight = resolveLook( { preset: 'midnight' }, 'ocean', presets );
		// It reads the new colors, so they are in place by then.
		const seen = [];
		normalize.mockImplementation( () =>
			seen.push( doc.querySelectorAll( 'style' )[ 1 ].textContent )
		);
		expect( restyleDocument( doc, from, midnight, probe ) ).toBe( true );
		const containers = document.querySelectorAll( '.srfm-form-container' );
		expect( normalize ).toHaveBeenCalledTimes( 2 );
		expect( normalize ).toHaveBeenNthCalledWith( 1, containers[ 0 ] );
		expect( normalize ).toHaveBeenNthCalledWith( 2, containers[ 1 ] );
		expect( seen ).toEqual( [ formCss( midnight ), formCss( midnight ) ] );
	} );

	it( "leaves a page it can't keep in sync to be rendered again", () => {
		delete window.srfm;
		const doc = renderPreview( from );
		const mint = resolveLook( { preset: 'mint' }, 'ocean', presets );
		expect( restyleDocument( doc, from, mint, probe ) ).toBe( false );
		// Untouched, as it is rendered again anyway.
		expect( doc.querySelectorAll( 'style' )[ 1 ].textContent ).toBe(
			formCss( from )
		);
	} );

	it( 'swaps a color only where it is the whole color', () => {
		// A 3-digit color is the start of a 6-digit one, and a 6-digit one
		// of an 8-digit one with alpha, which does follow it.
		const css = ( look ) =>
			`.srfm-form-container-1 { --srfm-color-scheme-primary: ${ look.primary }; --a: linear-gradient(${ look.onPrimary } 0%, #FFFFFF 100%); --b: ${ look.primary }1A; }`;
		const short = { ...from, background: '#FAFAFA', onPrimary: '#FFF' };
		const other = {
			...from,
			background: '#111111',
			text: '#EEEEEE',
			primary: '#38BDF8',
			onPrimary: '#000',
		};
		document.head.innerHTML = `<style>${ css( short ) }</style>`;
		const to = { ...short, primary: '#059669', onPrimary: '#0F172A' };
		expect(
			restyleDocument( document, short, to, {
				blocks: [ css( other ) ],
				look: other,
			} )
		).toBe( true );
		expect( document.querySelector( 'style' ).textContent ).toBe(
			`.srfm-form-container-1 { --srfm-color-scheme-primary: #059669; --a: linear-gradient(#0F172A 0%, #FFFFFF 100%); --b: #0596691A; }`
		);
	} );

	it( 'tells a white form from its white button text', () => {
		const doc = renderPreview( from );
		expect( from.background ).toBe( from.onPrimary );
		const charcoal = resolveLook(
			{ background: '#1F2937' },
			'ocean',
			presets
		);
		expect( restyleDocument( doc, from, charcoal, probe ) ).toBe( true );
		expect( doc.querySelectorAll( 'style' )[ 1 ].textContent ).toBe(
			formCss( charcoal )
		);
		expect( charcoal.onPrimary ).toBe( '#FFFFFF' );
	} );

	it( 'leaves other styles alone', () => {
		const doc = renderPreview( from );
		restyleDocument(
			doc,
			from,
			resolveLook( {}, 'mint', presets ),
			probe
		);
		expect( doc.querySelector( 'style' ).textContent ).toBe(
			'body { color: #2563EB; }'
		);
	} );

	it( "says so when the page can't be restyled in place", () => {
		const other = resolveLook( {}, 'mint', presets );

		// No render to compare with.
		expect( restyleDocument( renderPreview( from ), from, other ) ).toBe(
			false
		);
		// A theme color that isn't a plain value we know.
		expect(
			restyleDocument( renderPreview( from ), other, from, probe )
		).toBe( false );
		// The other render has no form CSS.
		expect(
			restyleDocument( renderPreview( from ), from, other, {
				blocks: [ 'a;b' ],
				look: probeLook,
			} )
		).toBe( false );

		document.head.innerHTML = '<style>body { margin: 0; }</style>';
		expect( restyleDocument( document, from, other, probe ) ).toBe(
			false
		);
		expect( restyleDocument( null, from, other, probe ) ).toBe( false );
	} );
} );
