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
		'srfm/gdpr',
		'srfm/phone',
		'srfm/address',
		'srfm/dropdown',
		'srfm/multi-choice',
		'srfm/url',
		'srfm/inline-button',
		'srfm/signature',
	] );

	if ( exclude ) {
		for ( let i = 0; i < blocks.length; i++ ) {
			if ( blocks[ i ] === exclude ) {
				blocks.splice( i, 1 );
			}
		}
	}

	console.log( 'blocks', blocks );
	

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

	return srfm_block_data?.srfm_default_dynamic_block_option?.[ key ] ?? '';
};

/**
 * Generate Required Error Message.
 *
 * @param {string} key     Default error message key
 * @param {string} message Custom error message.
 * @return {Object} currentErrorMsg, setCurrentErrorMsg, currentUniqueMessage, setCurrentUniqueMessage.
 */
const useErrMessage = ( key, message ) => {
	const [ currentMessage, setCurrentMessage ] = useState();

	useEffect( () => {
		setCurrentMessage( validationMessage( key, message ) );
	}, [ key, message ] );

	return { currentMessage, setCurrentMessage };
};

/**
 * Get default message value for resetting the respective option.
 *
 * @param {string} optionName Option name.
 * @return {Object} default message.
 */
function getDefaultMessage( optionName ) {
	return {
		type: 'string',
		default:
			srfm_block_data?.srfm_default_dynamic_block_option?.[ optionName ],
	};
}

/**
 * Modified the string value.
 *
 * @param {string} str - The input string to be modified.
 * @return {string} modified value.
 */
function decodeHtmlEntities( str ) {
	const entities = [
		{ entity: '&amp;', char: '&' },
		{ entity: '&lt;', char: '<' },
	];

	for ( let i = 0; i < entities.length; i++ ) {
		if ( str.includes( entities[ i ].entity ) ) {
			const regex = new RegExp( entities[ i ].entity, 'g' );
			str = str.replace( regex, entities[ i ].char );
		}
	}

	return str;
}

export {
	stripHTML,
	getSpacingPresetCssVar,
	getBlockTypes,
	validationMessage,
	useErrMessage,
	getDefaultMessage,
	decodeHtmlEntities,
};
