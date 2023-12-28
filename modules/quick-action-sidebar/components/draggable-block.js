/**
 * Creates a single draggable block.
 */
import { useState, useRef } from '@wordpress/element';
import { Icon, Draggable, Popover } from '@wordpress/components';
import { dispatch } from '@wordpress/data';

const DraggableBlock = ( props ) => {
	const { block, id, create, blockInsertionPoint, getBlockRootClientId } = props;
	const [ hovering, setHovering ] = useState( false );
	const isDragging = useRef( false );
	const handleMouseOver = () => {
		setHovering( true );
	}

	const handleMouseOut = () => {
		setHovering( false );
	}

	return (
		<>
			<Draggable elementId="draggable-panel"
			__experimentalTransferDataType="wp-blocks"
			transferData={ {
				type: 'inserter',
				blocks: [ create( block.name ) ],
			} }>
				{ ( { onDraggableStart } ) => (
				<div
					className='srfm-ee-quick-access__sidebar--blocks--block'
					key={id}
					onClick = { () => {
						dispatch( 'core/block-editor' ).insertBlocks( create( block.name ), blockInsertionPoint, getBlockRootClientId || '' );
					} }
					draggable
					onDragStart={ ( event ) => {
						isDragging.current = true;
						if( onDraggableStart ) {
							onDraggableStart( event );
						}
					} }
					onDragEnd={ () => {
					}  }
					onMouseOver={ handleMouseOver }
					onMouseOut={ handleMouseOut }
					onFocus={ handleMouseOver }
					onBlur={ handleMouseOut }
				>
						<div className='srfm-ee-quick-access__sidebar--blocks--block--icon'>

								<Icon icon={ block.icon?.src ? block.icon.src : block.icon } />

						</div>
						{ hovering && ( <Popover placement="right" key={id} className='srfm-ee-quick-access__sidebar--blocks--block--icon--name'><div className='block-title'>{ block.title }</div></Popover> )}
				</div>
				)}
			</Draggable>

		</>
	);
};

export default DraggableBlock;
