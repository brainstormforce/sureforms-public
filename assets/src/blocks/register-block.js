import { registerBlockType, createBlock } from '@wordpress/blocks';
import { getBlockTypes } from './util';

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

	const additionalSettings = 'sureforms/form' !== metadata.name ? {
		transforms: {
			from: [{
				type: 'block',
				blocks: getBlockTypes(metadata.name),
				transform: (attributes) => { return createBlock( metadata.name, attributes) },
			}],
		}
	} : {};

	registerBlockType(
		{
			...metadata,
			textdomain: 'sureforms', // set our text domain for everything.
		},
		{
			...settings,
			...additionalSettings,
		}
	);
};
