import { useState } from '@wordpress/element';
import { Icon, Draggable, Popover } from '@wordpress/components';
import { dispatch } from '@wordpress/data';

const
DraggableBlock
= ( props ) => {
	const { block, key, create, blockInsertionPoint } = props;
	const [ hovering, setHovering ] = useState( false );

	const handleMouseOver = () => {
		setHovering( true );
	}

	const handleMouseOut = () => {
		setHovering( false );
	}

	return (
		<>
			<Draggable elementId="draggable-panel" transferData={ {} }>
				{ ( { onDraggableStart } ) => (
				<div
					className='spectra-ee-quick-access__sidebar--blocks--block'
					key={key}
					onClick = { () => {
						dispatch( 'core/block-editor' ).insertBlocks( create( block.name ), blockInsertionPoint );
					} }
					draggable
					onDragStart={ onDraggableStart }
					onDragEnd={ () => {
						dispatch( 'core/block-editor' ).insertBlocks( create( block.name ), blockInsertionPoint );
					}  }
					onMouseOver={ handleMouseOver }
					onMouseOut={ handleMouseOut }
					onFocus={ handleMouseOver }
					onBlur={ handleMouseOut }
				>
						<div className='srfm-ee-quick-access__sidebar--blocks--block--icon'>
							<Icon icon={ block.icon && block.icon.src ? block.icon.src : block.icon } />
						</div>
						{ hovering && ( <Popover placement="right" key={key} className='srfm-ee-quick-access__sidebar--blocks--block--icon--name'><div className='block-title'>{ block.title }</div></Popover> )}
				</div>
				)}
			</Draggable>

		</>
	);
};

export default DraggableBlock;