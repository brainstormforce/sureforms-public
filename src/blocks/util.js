import { applyFilters } from '@wordpress/hooks';

const stripHTML = ( text ) => {
	const { DOMParser } = window;
	const parsedLabel = new DOMParser().parseFromString( text, 'text/html' );
	return parsedLabel?.body?.textContent || '';
};

/**
 * Converts a spacing preset into a custom value.
 *
 * @param {string} value Value to convert.
 *
 * @return {string} CSS var string for given spacing preset value.
 */
const getSpacingPresetCssVar = ( value ) => {
	if ( ! value ) {
		return '';
	}

	const slug = value.match( /var:preset\|spacing\|(.+)/ );

	if ( ! slug ) {
		return value;
	}

	return `var(--wp--preset--spacing--${ slug[ 1 ] })`;
};

/**
 * Gets input block types.
 *
 * @param {string} exclude Block to exclude.
 *
 * @return {Array} Block Types.
 */
const getBlockTypes = ( exclude = '' ) => {
	const blocks = applyFilters( 'srfm.AllowedBlocks', [
		'srfm/input',
		'srfm/email',
		'srfm/textarea',
		'srfm/number',
		'srfm/checkbox',
		'srfm/phone',
		'srfm/address',
		'srfm/dropdown',
		'srfm/multi-choice',
		'srfm/url',
	] );

	if ( exclude ) {
		for ( let i = 0; i < blocks.length; i++ ) {
			if ( blocks[ i ] === exclude ) {
				blocks.splice( i, 1 );
			}
		}
	}

	return blocks;
};

/**
 * Modified the string value.
 *
 * @param {string} str - The input string to be modified.
 * @return {string} modified value.
 */
function decodeHtmlEntities( str ) {
	if ( str.includes( '&amp;' ) ) {
		str = str.replace( /&amp;/g, '&' );
	}
	if ( str.includes( '&lt;' ) ) {
		str = str.replace( /&lt;/g, '<' );
	}
	return str;
}

export { stripHTML, getSpacingPresetCssVar, getBlockTypes, decodeHtmlEntities };
