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
		'srfm/payment',
		'srfm/signature',
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

const checkInvalidCharacter = ( value ) => value.includes( '|' );

/**
 * Updates an array of selected indices after an item has been reordered via drag-and-drop.
 * When an item is moved from one position to another, this function recalculates
 * which indices should remain selected to maintain the same logical selections.
 *
 * @param {Array}  selectedIndices - Array of currently selected item indices.
 * @param {number} movedFromIndex  - Original index of the item that was moved.
 * @param {number} movedToIndex    - New index where the item was moved to.
 * @return {Array} Updated array of selected indices after the reorder.
 *
 * @example
 * // Moving item from index 1 to index 3, with items at indices 0 and 2 selected
 * updateSelectedIndicesAfterReorder([0, 2], 1, 3)
 * // Returns [0, 1] because item at index 2 shifted left to index 1
 */
const updateSelectedIndicesAfterReorder = (
	selectedIndices,
	movedFromIndex,
	movedToIndex
) => {
	return selectedIndices.map( ( currentIndex ) => {
		// The moved item itself now has the new index
		if ( currentIndex === movedFromIndex ) {
			return movedToIndex;
		}

		// Items between source and destination need their indices adjusted
		// Moving forward (left to right): items in between shift left by 1
		if (
			movedFromIndex < movedToIndex &&
			currentIndex > movedFromIndex &&
			currentIndex <= movedToIndex
		) {
			return currentIndex - 1;
		}

		// Moving backward (right to left): items in between shift right by 1
		if (
			movedFromIndex > movedToIndex &&
			currentIndex >= movedToIndex &&
			currentIndex < movedFromIndex
		) {
			return currentIndex + 1;
		}

		// Items outside the moved range remain unchanged
		return currentIndex;
	} );
};

export {
	stripHTML,
	getSpacingPresetCssVar,
	getBlockTypes,
	validationMessage,
	useErrMessage,
	getDefaultMessage,
	decodeHtmlEntities,
	checkInvalidCharacter,
	updateSelectedIndicesAfterReorder,
};
