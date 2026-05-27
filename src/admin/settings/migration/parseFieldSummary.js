/**
 * parseFieldSummary — turn dry-run block markup into a human-readable summary.
 *
 * The migrator's dry-run returns each form as a string of Gutenberg block
 * comments (`<!-- wp:srfm/input {…} /-->`). That markup is meaningless to end
 * users, so we summarise it into `{ count, fields: [{ label, type }] }` to
 * render a friendly "N fields will import" list instead.
 *
 * We parse the markup with a regex rather than `@wordpress/blocks` `parse()`
 * because the `srfm/*` block types are NOT registered on the settings screen,
 * so `parse()` would return `core/missing` blocks and lose the field names.
 * The regex is registration-independent and mirrors the server-side emitter.
 *
 * @since x.x.x
 */

import { __ } from '@wordpress/i18n';

/**
 * Friendly field-type label for each srfm/* block name.
 *
 * @return {Object<string,string>} Map of block name → display label.
 */
const typeLabels = () => ( {
	'srfm/input': __( 'Text', 'sureforms' ),
	'srfm/email': __( 'Email', 'sureforms' ),
	'srfm/url': __( 'URL', 'sureforms' ),
	'srfm/phone': __( 'Phone', 'sureforms' ),
	'srfm/number': __( 'Number', 'sureforms' ),
	'srfm/textarea': __( 'Textarea', 'sureforms' ),
	'srfm/dropdown': __( 'Dropdown', 'sureforms' ),
	'srfm/multi-choice': __( 'Multiple choice', 'sureforms' ),
	'srfm/checkbox': __( 'Checkbox', 'sureforms' ),
	'srfm/gdpr': __( 'Consent', 'sureforms' ),
	'srfm/address': __( 'Address', 'sureforms' ),
} );

/**
 * Map a block name to a friendly type label, falling back to the bare field
 * name (e.g. `srfm/foo` → `foo`).
 *
 * @param {string} name Block name.
 * @return {string} Display label.
 */
const prettyType = ( name ) => {
	const labels = typeLabels();
	if ( labels[ name ] ) {
		return labels[ name ];
	}
	return String( name || '' ).replace( /^srfm\//, '' );
};

/**
 * Extract the `label` attribute from a block comment's attribute JSON.
 *
 * The label is a JSON string (so it may carry \uXXXX escapes); we decode it
 * via JSON.parse on the quoted token. Returns '' when no label is present.
 *
 * @param {string} attrText The raw block-comment segment.
 * @return {string} Decoded label, or empty string.
 */
const extractLabel = ( attrText ) => {
	const match = attrText.match( /"label":"((?:[^"\\]|\\.)*)"/ );
	if ( ! match ) {
		return '';
	}
	try {
		return JSON.parse( `"${ match[ 1 ] }"` );
	} catch ( e ) {
		return match[ 1 ];
	}
};

/**
 * Summarise one form's dry-run block markup into a field list.
 *
 * Only field blocks are counted — the submit button (`srfm/inline-button`) is
 * auto-rendered by SureForms, and `srfm/form` is a wrapper, so both are
 * ignored.
 *
 * @param {string} markup Block markup string from the dry-run preview.
 * @return {{count: number, fields: Array<{label: string, type: string}>}} Summary.
 */
export const parseFieldSummary = ( markup ) => {
	if ( typeof markup !== 'string' || '' === markup.trim() ) {
		return { count: 0, fields: [] };
	}

	const fields = [];
	// Match each block opener: `<!-- wp:srfm/<name> {…} /-->` (attrs optional).
	const blockRegex = /<!--\s*wp:(srfm\/[a-z-]+)([\s\S]*?)\/?-->/g;
	let m;
	while ( ( m = blockRegex.exec( markup ) ) !== null ) {
		const name = m[ 1 ];
		if ( 'srfm/inline-button' === name || 'srfm/form' === name ) {
			continue;
		}
		const label = extractLabel( m[ 2 ] || '' ) || prettyType( name );
		fields.push( { label, type: prettyType( name ) } );
	}

	return { count: fields.length, fields };
};

export default parseFieldSummary;
