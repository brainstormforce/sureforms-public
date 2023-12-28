/**
 * Creates sidebar blocks.
 */
import { useSelect } from '@wordpress/data';
import { createBlock, getBlockTypes } from '@wordpress/blocks';
import DraggableBlock from './draggable-block';
import { getAllowedBlocks } from '../../../assets/src/blocks/util';

const Blocks = () => {
    const blocks = getBlockTypes();
	const allowedBlocks = getAllowedBlocks();
	const { blockInsertionPoint, getBlockRootClientId } = useSelect( ( select ) => {
		const { index } = select( 'core/block-editor' ).getBlockInsertionPoint();
		const getSelectedBlockClientId = select( 'core/block-editor' ).getSelectedBlockClientId();
		const rootClientId = select( 'core/block-editor' ).getBlockRootClientId( getSelectedBlockClientId );
		return {
			blockInsertionPoint: index,
			getBlockRootClientId: rootClientId,
		};
	} );
	const srfmBlocks = blocks.filter( ( block ) => {
		return quickSidebarBlocks.allowed_blocks.includes(block.name);
	} );

	const create = ( name ) => { return createBlock( name ); }

	return (
		<>
			{srfmBlocks.map( ( block, index ) => (
				<DraggableBlock
					key={index}
					id={index}
					{ ...{
						block,
						create,
						blockInsertionPoint,
						getBlockRootClientId,
					}}
				/>
		  ) )}
		</>
	  );

  };

  export default Blocks;
