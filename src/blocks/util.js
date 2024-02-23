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
		'sureforms/input',
		'sureforms/email',
		'sureforms/textarea',
		'sureforms/number',
		'sureforms/checkbox',
		'sureforms/phone',
		'sureforms/address',
		'sureforms/dropdown',
		'sureforms/multi-choice',
		'sureforms/url',
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
 * Generate Validation Message.
 *
 * @param {string} defaults message.
 *
 * @return {string} message.
 */
const validationMessage = ( defaultMessage, message ) => {
	const defaultErrorMsg = sfBlockData?.get_default_dynamic_block_option?.[defaultMessage] ? sfBlockData.get_default_dynamic_block_option[defaultMessage] : ''
	const msg = message ? message : defaultErrorMsg;
	return msg;
};

export { stripHTML, getSpacingPresetCssVar, getBlockTypes, validationMessage };
