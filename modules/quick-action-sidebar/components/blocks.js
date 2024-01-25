/**
 * Creates sidebar blocks.
 */
import { useSelect } from '@wordpress/data';
import { createBlock, getBlockTypes } from '@wordpress/blocks';
import DraggableBlock from './draggable-block';

const Blocks = ( {
	defaultAllowedQuickSidebarBlocks,
	updateDefaultAllowedQuickSidebarBlocks,
	saveOptionToDatabase,
} ) => {
	const blocks = getBlockTypes();
	const {
		blockInsertionPoint,
		getBlockRootClientId,
		getSelectedBlockAllowedBlocks,
		getSelectedBlockClientId,
	} = useSelect( ( select ) => {
		const blockEditor = select( 'core/block-editor' );
		const { index } = blockEditor.getBlockInsertionPoint();
		const clientId = blockEditor.getSelectedBlockClientId();
		const rootClientId = blockEditor.getBlockRootClientId(
			getSelectedBlockClientId
		);
		const allowedBlocks = blockEditor.getAllowedBlocks( clientId );
		return {
			blockInsertionPoint: index,
			getBlockRootClientId: rootClientId,
			getSelectedBlockClientId: clientId,
			getSelectedBlockAllowedBlocks: allowedBlocks || [],
		};
	} );
	const srfmBlocks = blocks.filter( ( block ) => {
		return defaultAllowedQuickSidebarBlocks.includes( block.name );
	} );
	const create = ( name ) => {
		return createBlock( name );
	};

	return (
		<>
			{ srfmBlocks.map( ( block, index ) => (
				<DraggableBlock
					key={ index }
					id={ index }
					{ ...{
						block,
						create,
						blockInsertionPoint,
						getBlockRootClientId,
						getSelectedBlockClientId,
						getSelectedBlockAllowedBlocks,
						defaultAllowedQuickSidebarBlocks,
						updateDefaultAllowedQuickSidebarBlocks,
						saveOptionToDatabase,
					} }
				/>
			) ) }
		</>
	);
};

export default Blocks;
