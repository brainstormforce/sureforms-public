/**
 * WordPress dependencies
 */
import { MdOutlinePhoneIphone as icon } from 'react-icons/md';

/**
 * Internal dependencies
 */
import edit from './edit';
import save from './save';
import metadata from '@IncBlocks/phone/block.json';

const { name } = metadata;

export { metadata, name };

export const settings = {
	icon,
	edit,
	save,
};
