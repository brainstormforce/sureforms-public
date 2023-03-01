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

	registerBlockType(
		{
			...metadata,
			textdomain: 'sureforms', // set our text domain for everything.
			postTypes: [ 'sureforms_form' ], // Register Blocks for forms only.
		},
		{
			...settings,
			transforms: {
				from: [{
					type: 'block',
					blocks: getBlockTypes(metadata.name),
					transform: (attributes) => { return createBlock( metadata.name, attributes) },
				}],
			},
		}
	);
};
