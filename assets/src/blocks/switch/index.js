/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

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
	icon: (
		<svg
			xmlns="http://www.w3.org/2000/svg"
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			strokeLinecap="round"
			strokeLinejoin="round"
			style={{ fill: 'none' }}
		>
			<rect x="1" y="5" width="22" height="14" rx="7" ry="7"></rect>
			<circle cx="8" cy="12" r="3"></circle>
		</svg>
	),
	edit,
	save,
	deprecated,
};
