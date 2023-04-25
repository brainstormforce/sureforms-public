/**
 * WordPress dependencies
 */
import { quote as icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import metadata from './block.json';

export { metadata };
export const settings = {
	icon,
	edit,
	save,
};
