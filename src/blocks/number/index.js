/**
 * WordPress dependencies
 */
import { Bs123 as icon } from 'react-icons/bs';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import metadata from '@IncBlocks/number/block.json';

const { name } = metadata;

export { metadata, name };

export const settings = {
	icon,
	edit,
	save,
};
