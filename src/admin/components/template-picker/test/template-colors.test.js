/* eslint-env jest */
import { resolveLook, restyleDocument } from '../template-colors';

// Same values as Form_Templates::get_themes_for_js().
const presets = [
	{
		id: 'ocean',
		background: 'linear-gradient(135deg, #EAF2FF 0%, #F7FAFF 100%)',
		accent: '#2563EB',
		text: '#1E293B',
		onPrimary: '#FFFFFF',
	},
	{
		id: 'midnight',
		background: 'linear-gradient(160deg, #0F172A 0%, #1E293B 100%)',
		accent: '#38BDF8',
		text: '#E2E8F0',
		onPrimary: '#0F172A',
	},
	{
		id: 'mint',
		background: 'linear-gradient(135deg, #E6FAF1 0%, #F5FFFB 100%)',
		accent: '#059669',
		text: '#1F2F2A',
		onPrimary: '#FFFFFF',
	},
];

// The parts of the form's <style> block that carry the theme's colors.
const formCss = ( look ) => `.srfm-form-container-1 {
	--srfm-color-scheme-primary: ${ look.primary };
	--srfm-color-scheme-text-on-primary: ${ look.onPrimary };
	--srfm-color-scheme-text: ${ look.text };
	--srfm-color-input-border: hsl( from ${ look.text } h s l / 0.25 );
	--srfm-btn-color-hover: hsl( from ${ look.primary } h s l / 0.9 );
	--srfm-bg-gradient: ${ look.background };
}`;

const renderPreview = ( look ) => {
	document.head.innerHTML = `<style>body { color: #2563EB; }</style><style>${ formCss(
		look
	) }</style>`;
	return document;
};

describe( 'resolveLook', () => {
	it( "keeps a preset's own text colors", () => {
		expect( resolveLook( {}, 'mint', presets ) ).toEqual( {
			background: presets[ 2 ].background,
			primary: '#059669',
			text: '#1F2F2A',
			onPrimary: '#FFFFFF',
		} );
	} );

	it( 'picks readable text for chosen colors, like the server', () => {
		const look = resolveLook(
			{ background: '#0F172A', accent: '#FACC15' },
			'ocean',
			presets
		);
		expect( look.background ).toBe(
			'linear-gradient(135deg, #0F172A 0%, #636875 100%)'
		);
		expect( look.text ).toBe( '#E2E8F0' );
		expect( look.onPrimary ).toBe( '#0F172A' );
	} );
} );

describe( 'restyleDocument', () => {
	const from = resolveLook( {}, 'midnight', presets );

	it( "swaps the form's colors in place, as often as asked", () => {
		const doc = renderPreview( from );
		const ocean = resolveLook( { preset: 'ocean' }, 'midnight', presets );
		expect( restyleDocument( doc, from, ocean ) ).toBe( true );
		expect( doc.querySelectorAll( 'style' )[ 1 ].textContent ).toBe(
			formCss( ocean )
		);

		const custom = resolveLook(
			{ background: '#FFFFFF', accent: '#E4572E' },
			'midnight',
			presets
		);
		expect( restyleDocument( doc, from, custom ) ).toBe( true );
		expect( doc.querySelectorAll( 'style' )[ 1 ].textContent ).toBe(
			formCss( custom )
		);

		expect( restyleDocument( doc, from, from ) ).toBe( true );
		expect( doc.querySelectorAll( 'style' )[ 1 ].textContent ).toBe(
			formCss( from )
		);
	} );

	it( 'leaves other styles alone', () => {
		const doc = renderPreview( from );
		restyleDocument( doc, from, resolveLook( {}, 'mint', presets ) );
		expect( doc.querySelector( 'style' ).textContent ).toBe(
			'body { color: #2563EB; }'
		);
	} );

	it( "says so when the page's colors aren't there", () => {
		const doc = renderPreview( from );
		const other = resolveLook( {}, 'ocean', presets );
		expect( restyleDocument( doc, other, from ) ).toBe( false );

		document.head.innerHTML = '<style>body { margin: 0; }</style>';
		expect( restyleDocument( document, from, other ) ).toBe( false );
		expect( restyleDocument( null, from, other ) ).toBe( false );
	} );
} );
