import { applyFilters } from '@wordpress/hooks';
import { useState, useEffect } from '@wordpress/element';

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
 * Generate Validation Message.
 *
 * @param {string} key     Default error message key
 * @param {string} message Custom error message.
 * @return {string} message.
 */
const validationMessage = ( key, message ) => {
	if ( message ) {
		return message;
	}

	return srfm_block_data?.get_default_dynamic_block_option?.[ key ] ?? '';
};

/**
 * Generate Required Error Message.
 *
 * @param {string} key      Default error message key
 * @param {string} errorMsg Custom error message.
 * @return {Object} currentErrorMsg, setCurrentErrorMsg.
 */
const useErrMessage = ( type, key, message ) => {
	const [ currentErrorMsg, setCurrentErrorMsg ] = useState();
	const [ currentUniqueMessage, setCurrentUniqueMessage ] = useState();

	useEffect( () => {
		if ( type === 'required' ) {
			setCurrentErrorMsg( validationMessage( key, message ) );
		} else if ( type === 'unique' ) {
			setCurrentUniqueMessage( validationMessage( key, message ) );
		}
	}, [ type, key, message ] );

	switch ( type ) {
		case 'required':
			return { currentErrorMsg, setCurrentErrorMsg };
		case 'unique':
			return { currentUniqueMessage, setCurrentUniqueMessage };
		default:
			return '';
	}
};

export {
	stripHTML,
	getSpacingPresetCssVar,
	getBlockTypes,
	validationMessage,
	useErrMessage,
};
