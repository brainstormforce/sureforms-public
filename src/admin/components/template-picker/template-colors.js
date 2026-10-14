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
 * @param {Array}  presets Presets: { id, background, accent }.
 * @return {{background: string, primary: string, text: string, onPrimary: string}} Look.
 */
export const resolveLook = ( style, theme, presets ) => {
	const list = presets || [];
	const preset = list.find( ( p ) => p.id === ( style.preset || theme ) ) ||
		list.find( ( p ) => p.id === theme ) ||
		list[ 0 ] || { background: '#FFFFFF', accent: '#2563EB' };
	const background =
		style.background && /^#[0-9a-f]{6}$/i.test( style.background )
			? `linear-gradient(135deg, ${ style.background } 0%, ${ mix(
				style.background,
				'#FFFFFF',
				0.35
			  ) } 100%)`
			: preset.background;
	const primary = style.accent || preset.accent;
	return {
		background,
		primary,
		text: isDark( firstHex( background ) ) ? '#E2E8F0' : '#1E293B',
		onPrimary: isDark( firstHex( primary ) ) ? '#FFFFFF' : '#0F172A',
	};
};
