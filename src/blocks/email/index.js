/**
 * WordPress dependencies
 */
import { MdOutlineMail as icon } from 'react-icons/md';

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
