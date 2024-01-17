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
	// Field Transform option.
	const types = [
		'sureforms/input',
		'sureforms/email',
		'sureforms/textarea',
		'sureforms/number',
		'sureforms/checkbox',
		'sureforms/phone',
		'sureforms/address',
		'sureforms/dropdown',
		'sureforms/multi-choice',
		'sureforms/rating',
		'sureforms/upload',
		'sureforms/url',
		'sureforms/password',
		'sureforms/date-time-picker',
		'sureforms/number-slider',
		'sureforms/hidden',
	];
	if ( exclude ) {
		for ( let i = 0; i < types.length; i++ ) {
			if ( types[ i ] === exclude ) {
				types.splice( i, 1 );
			}
		}
	}
	return types;
};

export { stripHTML, getSpacingPresetCssVar, getBlockTypes };

export const getAllowedBlocks = () => {
	const getBlocks = [
		'sureforms/input',
		'sureforms/email',
		'sureforms/textarea',
		'sureforms/number',
		'sureforms/checkbox',
		'sureforms/phone',
		'sureforms/address',
		'sureforms/dropdown',
		'sureforms/multi-choice',
		'sureforms/rating',
		'sureforms/upload',
		'sureforms/url',
		'sureforms/password',
		'sureforms/date-time-picker',
		'sureforms/number-slider',
		'sureforms/hidden',
	];

	return getBlocks;
};
