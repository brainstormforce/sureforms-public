/**
 * Color helpers for the template preview, matching the server's choices so
 * what is shown before creating is what the form gets.
 */

/**
 * First hex color in a CSS value, e.g. the start of a gradient.
 *
 * @since x.x.x
 * @param {string} value CSS color or gradient.
 * @return {string} Hex color, '' when there is none.
 */
export const firstHex = ( value ) => {
	const match = String( value || '' ).match(
		/#[0-9a-f]{6}\b|#[0-9a-f]{3}\b/i
	);
	return match ? match[ 0 ] : '';
};

/**
 * Whether a color is dark enough to need light text on it. Same formula as
 * Form_Templates::is_dark().
 *
 * @since x.x.x
 * @param {string} hex Hex color.
 * @return {boolean} Dark.
 */
export const isDark = ( hex ) => {
	let value = String( hex || '' ).replace( '#', '' );
	if ( 3 === value.length ) {
		value = value
			.split( '' )
			.map( ( c ) => c + c )
			.join( '' );
	}
	if ( 6 !== value.length ) {
		return false;
	}
	const channel = ( index ) => {
		const c = parseInt( value.slice( index, index + 2 ), 16 ) / 255;
		return c <= 0.03928
			? c / 12.92
			: Math.pow( ( c + 0.055 ) / 1.055, 2.4 );
	};
	const luminance =
		( 0.2126 * channel( 0 ) ) +
		( 0.7152 * channel( 2 ) ) +
		( 0.0722 * channel( 4 ) );
	return luminance < 0.179;
};

/**
 * Mix two hex colors.
 *
 * @param {string} hex    Base color.
 * @param {string} with_  Color mixed in.
 * @param {number} amount Share of `with_`, 0 to 1.
 * @return {string} Hex color.
 */
const mix = ( hex, with_, amount ) => {
	const rgb = ( color ) =>
		[ 1, 3, 5 ].map( ( i ) => parseInt( color.slice( i, i + 2 ), 16 ) );
	const [ a, b ] = [ rgb( hex ), rgb( with_ ) ];
	return (
		'#' +
		a
			.map( ( c, i ) =>
				Math.round( c + ( ( b[ i ] - c ) * amount ) )
					.toString( 16 )
					.padStart( 2, '0' )
			)
			.join( '' )
	);
};

/**
 * The look a form gets with a chosen style, like
 * Form_Templates::resolve_theme(): background, primary color and text color.
 *
 * @since x.x.x
 * @param {Object} style   { preset?, background?, accent? }.
 * @param {string} theme   The template's own preset id.
 * @param {Array}  presets Presets: { id, background, accent, text, onPrimary }.
 * @return {{background: string, primary: string, text: string, onPrimary: string}} Look.
 */
export const resolveLook = ( style, theme, presets ) => {
	const list = presets || [];
	const preset = list.find( ( p ) => p.id === ( style.preset || theme ) ) ||
		list.find( ( p ) => p.id === theme ) ||
		list[ 0 ] || { background: '#FFFFFF', accent: '#2563EB' };
	const hasBackground =
		!! style.background && /^#[0-9a-f]{6}$/i.test( style.background );
	const background = hasBackground
		? `linear-gradient(135deg, ${ style.background } 0%, ${ mix(
			style.background,
			'#FFFFFF',
			0.35
		  ).toUpperCase() } 100%)`
		: preset.background;
	const primary = style.accent || preset.accent;
	// A preset keeps its own text colors; a chosen color gets readable ones.
	const text =
		! hasBackground && preset.text
			? preset.text
			: isDark( firstHex( background ) )
				? '#E2E8F0'
				: '#1E293B';
	const onPrimary =
		! style.accent && preset.onPrimary
			? preset.onPrimary
			: isDark( firstHex( primary ) )
				? '#FFFFFF'
				: '#0F172A';
	return { background, primary, text, onPrimary };
};

const escapeRegExp = ( value ) =>
	value.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );

const KEYS = [ 'background', 'primary', 'text', 'onPrimary' ];

// Each style block as first rendered, with its theme colors swapped for tokens.
const restyleSources = new WeakMap();

/**
 * The form's own <style> blocks: the ones that set its color scheme.
 *
 * @since x.x.x
 * @param {Document} doc Preview document.
 * @return {HTMLStyleElement[]} Style blocks.
 */
export const getFormStyleBlocks = ( doc ) =>
	Array.from( doc?.querySelectorAll?.( 'style' ) || [] ).filter(
		( node ) =>
			restyleSources.has( node ) ||
			node.textContent.includes( '--srfm-color-scheme-primary' )
	);

/**
 * A preset that differs from a look in every color, to render the same form
 * once more and see which of its CSS follows the theme.
 *
 * @since x.x.x
 * @param {Object} look    Look from resolveLook().
 * @param {Array}  presets Presets.
 * @return {string} Preset id, '' when none differs in every color.
 */
export const getProbePreset = ( look, presets ) => {
	const probe = ( presets || [] ).find( ( preset ) => {
		const other = resolveLook( { preset: preset.id }, preset.id, presets );
		return KEYS.every(
			( key ) =>
				String( other[ key ] ).toUpperCase() !==
				String( look[ key ] ).toUpperCase()
		);
	} );
	return probe ? probe.id : '';
};

/**
 * CSS declarations keyed by property, numbered when one repeats, e.g.
 * "--srfm-bg-gradient#2". The selector and comments before a declaration
 * aren't part of its key; a piece with no property gets an empty key.
 *
 * @param {string[]} declarations Declarations, in order.
 * @return {Array<[string, string]>} [ key, declaration ] pairs, in order.
 */
const keyDeclarations = ( declarations ) => {
	const seen = {};
	return declarations.map( ( declaration ) => {
		const tail = declaration
			.replace( /\/\*[\s\S]*?\*\//g, '' )
			.split( /[{}]/ )
			.pop();
		const colon = tail.indexOf( ':' );
		// Only braces or space: never a color, so it never gets a key.
		if ( -1 === colon ) {
			return [ '', declaration ];
		}
		const name = tail.slice( 0, colon ).trim();
		seen[ name ] = ( seen[ name ] || 0 ) + 1;
		return [ `${ name }#${ seen[ name ] }`, declaration ];
	} );
};

/**
 * Swap a look's colors in one CSS declaration for tokens.
 *
 * @param {string} declaration CSS declaration.
 * @param {Object} from        Look it was rendered with.
 * @return {string} Declaration with tokens.
 */
const tokenize = ( declaration, from ) =>
	// The background goes first: its gradient can hold the other colors.
	KEYS.reduce( ( text, key ) => {
		const value = String( from[ key ] || '' );
		if ( ! value ) {
			return text;
		}
		// A color may carry an alpha suffix (#RRGGBBAA, used by Pro).
		const pattern = new RegExp(
			escapeRegExp( value ) +
				( 'background' === key
					? ''
					: '(?=(?:[0-9a-f]{2})?(?![0-9a-f]))' ),
			'gi'
		);
		return text.replace( pattern, `__srfm_${ key }__` );
	}, declaration );

/**
 * Restyle a rendered preview in place. The form's colors are written as
 * plain values into its own <style> block, so they are swapped there for the
 * new ones instead of rendering the whole page (and its scripts) again.
 *
 * Only declarations that follow the theme are touched: the ones that differ
 * from a render of the same form in another preset (`probe`). A value that
 * merely equals a theme color, like a fixed white, is left alone.
 *
 * Scripts that read the colors when the page loaded run again afterwards:
 * SureForms' dark-background handling sets a class and a menu color for a
 * dark form, and they have to follow it from light to dark and back.
 *
 * @since x.x.x
 * @param {Document} doc   Preview document.
 * @param {Object}   from  Look the page was rendered with, from resolveLook().
 * @param {Object}   to    Look to show, from resolveLook().
 * @param {string[]} probe The form's style blocks rendered in another preset
 *                         (getProbePreset()), in page order.
 * @return {boolean} Whether the page was restyled. False when its theme
 *                   colors can't all be swapped, so it has to be rendered again.
 */
export const restyleDocument = ( doc, from, to, probe ) => {
	const blocks = getFormStyleBlocks( doc );
	// Without it the page would keep a dark form's menu colors on a light one.
	const normalize =
		doc?.defaultView?.srfm?.normalizeCSSVariablesForDarkBackground;
	if (
		! blocks.length ||
		! Array.isArray( probe ) ||
		'function' !== typeof normalize
	) {
		return false;
	}

	for ( const [ index, node ] of blocks.entries() ) {
		if ( ! restyleSources.has( node ) ) {
			// Scripts on the page may add to the block after it loads, so the
			// two renders are matched by property, not by position.
			const other = new Map(
				keyDeclarations( String( probe[ index ] ?? '' ).split( ';' ) )
			);
			if ( ! other.has( '--srfm-color-scheme-primary#1' ) ) {
				return false;
			}
			const source = [];
			for ( const [ key, declaration ] of keyDeclarations(
				node.textContent.split( ';' )
			) ) {
				if (
					! key ||
					! other.has( key ) ||
					declaration === other.get( key )
				) {
					source.push( declaration );
					continue;
				}
				const tokens = tokenize( declaration, from );
				// Follows the theme, but not as a plain color we know.
				if ( ! tokens.includes( '__srfm_' ) ) {
					return false;
				}
				source.push( tokens );
			}
			restyleSources.set( node, source.join( ';' ) );
		}
	}

	for ( const node of blocks ) {
		let css = restyleSources.get( node );
		for ( const key of KEYS ) {
			css = css.split( `__srfm_${ key }__` ).join( to[ key ] );
		}
		if ( css !== node.textContent ) {
			node.textContent = css;
		}
	}
	doc.querySelectorAll( '.srfm-form-container' ).forEach( ( container ) =>
		normalize( container )
	);
	return true;
};
