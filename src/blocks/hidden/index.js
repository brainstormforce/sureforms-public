/**
 * WordPress dependencies
 */
import { FaRegEyeSlash as icon } from 'react-icons/fa';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';

const { name } = metadata;

export { metadata, name };

export const settings = {
	icon,
	edit,
};
