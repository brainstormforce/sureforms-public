/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import deprecated from './deprecated';
import metadata from './block.json';
import { MdOutlineToggleOn as icon } from 'react-icons/md';

const { name } = metadata;

export { metadata, name };

export const settings = {
	icon,
	edit,
	save,
	deprecated,
};
