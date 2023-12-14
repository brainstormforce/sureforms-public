import { useSelect } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import DraggableBlock from './draggable-block';


const Blocks = () => {
	const { allBlocks, blockInsertionPoint } = useSelect( ( select ) => {
	  	const blocks = select( 'core/block-editor' ).getInserterItems();
		const { index } = select( 'core/block-editor' ).getBlockInsertionPoint();
		return {
			allBlocks: blocks,
			blockInsertionPoint: index,
		};
	} );

	const create = ( name ) => {
		return createBlock( name );
	}


	return (
		<>
			{allBlocks.map( ( block, index ) => (
				<DraggableBlock
					key={index}
					block={block}
					create={create}
					blockInsertionPoint={blockInsertionPoint}
				/>
		  ) )}
		</>
	  );

  };

  export default Blocks;