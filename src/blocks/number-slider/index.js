/**
 * WordPress dependencies
 */
import { RxSlider as icon } from 'react-icons/rx';

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
