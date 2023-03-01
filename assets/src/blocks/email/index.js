/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { verse as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import edit from './edit';
import deprecated from './deprecated';
import metadata from './block.json';

const { name } = metadata;

export { metadata, name };

export const settings = {
	icon,
	edit,
	deprecated,
};
