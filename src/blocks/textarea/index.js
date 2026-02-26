/**
 * WordPress dependencies
 */
import { BsTextareaResize as icon } from 'react-icons/bs';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import metadata from '@IncBlocks/textarea/block.json';

const { name } = metadata;

export { metadata, name };

export const settings = {
	icon,
	edit,
	save,
};
