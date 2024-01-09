import { registerBlockType, createBlock } from '@wordpress/blocks';
import { getBlockTypes, getAllowedBlocks } from './util';
import { createHigherOrderComponent } from '@wordpress/compose';
import { addFilter } from '@wordpress/hooks';
import { useDeviceType } from '@Controls/getPreviewType';
import { BlockControls } from '@wordpress/block-editor';
import { ToolbarGroup, ToolbarButton } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import parse from 'html-react-parser';
import svgIcons from '@Svg/svgs.json';

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
				const fieldWidth = attributes?.fieldWidth
					? String( attributes.fieldWidth )
					: '100';
				const width = fieldWidth
					? fieldWidth.replace( '.', '-' )
					: '100';
				const slug = name.replace( 'sureforms/', '' );

				return (
					<BlockListBlock
						{ ...props }
						wrapperProps={ wrapperProps }
						className={
							attributes?.fieldWidth &&
							'Mobile' !== useDeviceType()
								? `srfm-block-single srfm-${ slug }-block-wrap srfm-block-width-${ width }`
								: ''
						}
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
	'srfm/with-block-with-wrapper-props',
	blockWidthWrapperProps
);

const withToolbarButton = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { name, setAttributes } = props;

		const allowedBlocks = getAllowedBlocks();

		const oneColIcon = parse( svgIcons.width_full );
		const twoColIcon = parse( svgIcons.with_two_col );
		const threeColIcon = parse( svgIcons.width_three_col );
		const fourColIcon = parse( svgIcons.width_four_col );

		if ( allowedBlocks.includes( name ) ) {
			return (
				<>
					<BlockControls>
						<ToolbarGroup>
							<ToolbarButton
								icon={ oneColIcon }
								label="Full Width"
								onClick={ () => {
									setAttributes( {
										fieldWidth: Number( 100 ),
									} );
								} }
							/>
							<ToolbarButton
								icon={ twoColIcon }
								label="Two Columns"
								onClick={ () => {
									setAttributes( {
										fieldWidth: Number( 50 ),
									} );
								} }
							/>
							<ToolbarButton
								icon={ threeColIcon }
								label="Three Columns"
								onClick={ () => {
									setAttributes( {
										fieldWidth: Number( 33.33 ),
									} );
								} }
							/>
							<ToolbarButton
								icon={ fourColIcon }
								label="Four Columns"
								onClick={ () => {
									setAttributes( {
										fieldWidth: Number( 25 ),
									} );
								} }
							/>
						</ToolbarGroup>
					</BlockControls>
					<BlockEdit { ...props } />
				</>
			);
		}
		return <BlockEdit { ...props } />;
	};
}, 'withToolbarButton' );

wp.hooks.addFilter(
	'editor.BlockEdit',
	'srfm/with-toolbar-button',
	withToolbarButton
);
