import { registerBlockType } from '@wordpress/blocks';

/**
 * Function to register blocks provided by SureForms.
 */
export const registerBlocks = (blocks = []) => {
	return (blocks || []).forEach(registerBlock);
};

/**
 * Function to register an individual block.
 *
 * @param {Object} block The block to be registered.
 *
 */
const registerBlock = (block) => {
	if (!block) {
		return;
	}

	const { metadata, settings } = block;

	registerBlockType(
		{
			...metadata,
			textdomain: 'SureForms', // set our text domain for everything.
		},
		{
			...settings,
		}
	);
};
