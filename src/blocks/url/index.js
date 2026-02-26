/**
 * WordPress dependencies
 */
import { MdOutlineLink as icon } from 'react-icons/md';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from '@IncBlocks/url/block.json';

const { name } = metadata;

export { metadata, name };

export const settings = {
	icon,
	edit,
};
