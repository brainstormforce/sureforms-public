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
 * The look a form gets with a chosen style, like
 * Form_Templates::resolve_theme(): the page behind the form, its background,
 * primary color and text colors. With no preset chosen it is the template's
 * own look, where a dark theme starts light (its `default`).
 *
 * @since x.x.x
 * @param {Object} style   { preset?, background?, accent? }.
 * @param {string} theme   The template's own preset id.
 * @param {Array}  presets Presets: { id, page, background, accent, text, onPrimary, default? }.
 * @return {{page: string, background: string, primary: string, text: string, onPrimary: string}} Look.
 */
export const resolveLook = ( style, theme, presets ) => {
	const list = presets || [];
	const chosen = style.preset && list.find( ( p ) => p.id === style.preset );
	const own = list.find( ( p ) => p.id === theme ) ||
		list[ 0 ] || { background: '#FFFFFF', accent: '#2563EB' };
	const preset = chosen || { ...own, ...( own.default || {} ) };
	const hasBackground =
		!! style.background && /^#[0-9a-f]{6}$/i.test( style.background );
	const background = hasBackground ? style.background : preset.background;
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
	return {
		page: preset.page || '#F3F4F6',
		background,
		primary,
		text,
		onPrimary,
	};
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
 * once more and see which of its CSS follows the theme. Its own colors all
 * differ too, so each one in its CSS can only be one thing.
 *
 * @since x.x.x
 * @param {Object} look    Look from resolveLook().
 * @param {Array}  presets Presets.
 * @return {string} Preset id, '' when none differs in every color.
 */
export const getProbePreset = ( look, presets ) => {
	const probe = ( presets || [] ).find( ( preset ) => {
		const other = resolveLook( { preset: preset.id }, preset.id, presets );
		const colors = KEYS.map( ( key ) => String( other[ key ] ).toUpperCase() );
		return (
			new Set( colors ).size === KEYS.length &&
			KEYS.every(
				( key, index ) =>
					colors[ index ] !== String( look[ key ] ).toUpperCase()
			)
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
 * @param {Object} from        Look it was rendered with; its colors all differ.
 * @return {string} Declaration with tokens.
 */
const tokenize = ( declaration, from ) =>
	KEYS.reduce( ( text, key ) => {
		const value = String( from[ key ] || '' );
		if ( ! value ) {
			return text;
		}
		// A color may carry an alpha suffix (#RRGGBBAA, used by Pro).
		const pattern = new RegExp(
			escapeRegExp( value ) + '(?=(?:[0-9a-f]{2})?(?![0-9a-f]))',
			'gi'
		);
		return text.replace( pattern, `__srfm_${ key }__` );
	}, declaration );

/**
 * A declaration split where its property starts: what comes before it (the
 * end of a selector, a comment) and the property with its value.
 *
 * @param {string} declaration CSS declaration.
 * @return {[string, string]} [ before, property and value ].
 */
const splitDeclaration = ( declaration ) => {
	const at = Math.max(
		declaration.lastIndexOf( '{' ) + 1,
		declaration.lastIndexOf( '}' ) + 1,
		declaration.lastIndexOf( '*/' ) + 2
	);
	return [ declaration.slice( 0, at ), declaration.slice( at ) ];
};

/**
 * Put a look's colors in for the tokens.
 *
 * @param {string} text CSS with tokens.
 * @param {Object} look Look.
 * @return {string} CSS.
 */
const fill = ( text, look ) =>
	KEYS.reduce(
		( css, key ) => css.split( `__srfm_${ key }__` ).join( look[ key ] ),
		text
	);

/**
 * Restyle a rendered preview in place. The form's colors are written as
 * plain values into its own <style> block, so they are swapped there for the
 * new ones instead of rendering the whole page (and its scripts) again.
 *
 * Only declarations that follow the theme are touched: the ones that differ
 * from a render of the same form in another preset (`probe`). A value that
 * merely equals a theme color, like a fixed white, is left alone. The tokens
 * are found in the other render, whose colors all differ, so a page whose
 * look repeats a color (a white form with white button text) is still read
 * right; the declaration must come back exactly when filled with `from`.
 *
 * Scripts that read the colors when the page loaded run again afterwards:
 * SureForms' dark-background handling sets a class and a menu color for a
 * dark form, and they have to follow it from light to dark and back.
 *
 * @since x.x.x
 * @param {Document} doc          Preview document.
 * @param {Object}   from         Look the page was rendered with, from resolveLook().
 * @param {Object}   to           Look to show, from resolveLook().
 * @param {Object}   probe        The same form rendered in another preset
 *                                (getProbePreset()).
 * @param {string[]} probe.blocks Its style blocks, in page order.
 * @param {Object}   probe.look   Its look, from resolveLook().
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
		! Array.isArray( probe?.blocks ) ||
		! probe.look ||
		'function' !== typeof normalize
	) {
		return false;
	}

	for ( const [ index, node ] of blocks.entries() ) {
		if ( ! restyleSources.has( node ) ) {
			// Scripts on the page may add to the block after it loads, so the
			// two renders are matched by property, not by position.
			const other = new Map(
				keyDeclarations(
					String( probe.blocks[ index ] ?? '' ).split( ';' )
				)
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
				// The other render's selector carries another form id, so only
				// its property and value are used.
				const tokens =
					splitDeclaration( declaration )[ 0 ] +
					tokenize(
						splitDeclaration( other.get( key ) )[ 1 ],
						probe.look
					);
				// Follows the theme, but not only as the colors we know.
				if (
					! tokens.includes( '__srfm_' ) ||
					fill( tokens, from ).toLowerCase() !==
						declaration.toLowerCase()
				) {
					return false;
				}
				source.push( tokens );
			}
			restyleSources.set( node, source.join( ';' ) );
		}
	}

	for ( const node of blocks ) {
		const css = fill( restyleSources.get( node ), to );
		if ( css !== node.textContent ) {
			node.textContent = css;
		}
	}
	doc.querySelectorAll( '.srfm-form-container' ).forEach( ( container ) =>
		normalize( container )
	);
	return true;
};
