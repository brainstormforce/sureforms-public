/**
 * WordPress dependencies
 */
import { MdOutlineCheckBox as icon } from 'react-icons/md';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import deprecated from './deprecated';
import metadata from './block.json';

const { name } = metadata;

export { metadata, name };

export const settings = {
	icon,
	edit,
	save,
	deprecated,
};
