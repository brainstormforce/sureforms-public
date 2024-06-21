/**
 * Creates sidebar blocks.
 */
import { useSelect } from '@wordpress/data';
import { createBlock, getBlockTypes } from '@wordpress/blocks';
import DraggableBlock from './draggable-block';
import DragAndDropComponent from './move-up-down';

const Blocks = ( {
	defaultAllowedQuickSidebarBlocks,
	updateDefaultAllowedQuickSidebarBlocks,
	saveOptionToDatabase,
	enableRearrange,
	// getDefaultAllowedQuickSidebarBlocks,
} ) => {
	const blocks = getBlockTypes();
	// const { enableRearrange } = props;
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

	// Loop through each object and add id
	srfmBlocks.forEach( ( item, index ) => {
		item.id = `${ index + 1 }`;
	} );

	const sortedY = defaultAllowedQuickSidebarBlocks.map( ( item ) =>
		blocks.find( ( { name } ) => name === item )
	);

	console.log( { enableRearrange } );

	return (
		<>
			{ ! enableRearrange &&
				sortedY.map( ( block, index ) => (
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
							enableRearrange,
						} }
					/>
				) ) }
			{ enableRearrange && (
				<DragAndDropComponent
					initialItems={ sortedY }
					updateDefaultAllowedQuickSidebarBlocks={
						updateDefaultAllowedQuickSidebarBlocks
					}
					saveOptionToDatabase={ saveOptionToDatabase }
				/>
			) }
		</>
	);
};

export default Blocks;
