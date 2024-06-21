/**
 * Creates sidebar blocks.
 */
import { useState } from '@wordpress/element';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';
import { Icon } from '@wordpress/components';

function DragAndDropComponent( {
	initialItems,
	updateDefaultAllowedQuickSidebarBlocks,
	saveOptionToDatabase,
} ) {
	const [ items, setItems ] = useState( initialItems );

	const onDragEnd = ( result ) => {
		if ( ! result.destination ) {
			return;
		}
		const reorderedItems = Array.from( items );
		const [ removed ] = reorderedItems.splice( result.source.index, 1 );
		reorderedItems.splice( result.destination.index, 0, removed );
		const namesArray = reorderedItems.map( ( item ) => item.name );
		saveOptionToDatabase( namesArray );
		setItems( reorderedItems );
		updateDefaultAllowedQuickSidebarBlocks( namesArray );
	};

	return (
		<DragDropContext onDragEnd={ onDragEnd }>
			<Droppable droppableId="droppable">
				{ ( provided ) => (
					<div
						className="srfm-ee-quick-access__sidebar--blocks"
						{ ...provided.droppableProps }
						ref={ provided.innerRef }
					>
						{ items.map( ( item, index ) => (
							<Draggable
								key={ item.id }
								draggableId={ item.id }
								index={ index }
							>
								{ (
									provided // eslint-disable-line no-shadow
								) => (
									<div
										className="srfm-ee-quick-access__sidebar--blocks--block"
										ref={ provided.innerRef }
										{ ...provided.draggableProps }
										{ ...provided.dragHandleProps }
									>
										<div
											className={ `srfm-ee-quick-access__sidebar-icon__${
												item?.name?.split( '/' )[ 0 ]
											} srfm-ee-quick-access__sidebar-icon__${
												item?.name?.split( '/' )[ 0 ]
											}--${
												item?.name?.split( '/' )[ 1 ]
											} srfm-ee-quick-access__sidebar--blocks--block--icon` }
										>
											<Icon
												icon={
													item.icon.src
														? item.icon.src
														: item.icon
												}
											/>
										</div>
									</div>
								) }
							</Draggable>
						) ) }
						{ provided.placeholder }
					</div>
				) }
			</Droppable>
		</DragDropContext>
	);
}

export default DragAndDropComponent;
