import { registerBlockType, createBlock } from '@wordpress/blocks';
import { getBlockTypes, getAllowedBlocks } from './util';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { useDeviceType } from '@Controls/getPreviewType';

/**
 * Function to register blocks provided by SureForms.
 *
 * @param {blocks} blocks
 */
export const registerBlocks = ( blocks = [] ) => {
	return ( blocks || [] ).forEach( registerBlock );
};

/**
 * Function to register an individual block.
 *
 * @param {Object} block The block to be registered.
 */
const registerBlock = ( block ) => {
	if ( ! block ) {
		return;
	}

	const { metadata, settings } = block;

	const additionalSettings =
		'sureforms/form' !== metadata.name
			? {
				transforms: {
					from: [
						{
							type: 'block',
							blocks: getBlockTypes( metadata.name ),
							transform: ( attributes ) => {
								return createBlock(
									metadata.name,
									attributes
								);
							},
						},
					],
				},
			  }
			: {};

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

// Width feature for all sureforms blocks.
const blockWidthWrapperProps = createHigherOrderComponent(
	( BlockListBlock ) => {
		return ( props ) => {
			const { attributes, name } = props;

			const wrapperProps = {
				...props.wrapperProps,
			};

			const allowedBlocks = getAllowedBlocks();

			if ( allowedBlocks.includes( name ) ) {

				const slug = name.replace('sureforms/', '');

				return (
					<BlockListBlock
						{ ...props }
						wrapperProps={ wrapperProps }  className={ attributes?.fieldWidth && 'Mobile' !== useDeviceType() ? `srfm-block-single srfm-${slug}-block-wrap srfm-block-width-${attributes?.fieldWidth}` : '' }
					/>
				);
			}
			return <BlockListBlock { ...props } />;
		};
	},
	'blockWidthWrapperProps'
);

addFilter(
	'editor.BlockListBlock',
	'uagb/with-block-with-wrapper-props',
	blockWidthWrapperProps
);
