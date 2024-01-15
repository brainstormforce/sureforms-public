/**
 * Creates a single draggable block.
 */
import { useState, useRef } from '@wordpress/element';
import { Icon, Draggable, Popover } from '@wordpress/components';
import { dispatch } from '@wordpress/data';

const DraggableBlock = ( props ) => {
	const {
		block,
		id,
		create,
		blockInsertionPoint,
		getSelectedBlockClientId,
		getSelectedBlockAllowedBlocks,
		getBlockRootClientId,
	} = props;
	const [ hovering, setHovering ] = useState( false );
	const isDragging = useRef( false );
	const handleMouseOver = () => {
		setHovering( true );
	};

	const handleMouseOut = () => {
		setHovering( false );
	};

	const handleOnClick = ( e, selectedBlock ) => {
		let clientId = getBlockRootClientId || '';
		let insertionPoint = blockInsertionPoint;
		if (
			getSelectedBlockAllowedBlocks &&
			getSelectedBlockAllowedBlocks.includes( selectedBlock )
		) {
			insertionPoint =
				select( 'core/block-editor' ).getSelectedBlock().innerBlocks
					.length;
			clientId = getSelectedBlockClientId;
		}
		if ( e?.target?.classList?.contains( 'block-title-svg' ) ) {
			isDragging.current = false;
			return;
		}
		dispatch( 'core/block-editor' ).insertBlocks(
			create( selectedBlock.name ),
			insertionPoint,
			clientId
		);
	};

	const hoverPopover = (
		<Popover
			placement="right"
			key={ id }
			className="srfm-ee-quick-access__sidebar--blocks--block--icon--name"
		>
			<div className="block-title">
				<div
					onClick={ () => {
						handleRemoveBlock( block );
					} }
					className="srfm-ee-quick-access__sidebar--blocks--block--name"
				>
					<svg
						className="block-title-svg"
						xmlns="http://www.w3.org/2000/svg"
						viewBox="0 0 24 24"
						width="24"
						height="24"
						aria-hidden="true"
						focusable="false"
					>
						<path d="M13 11.8l6.1-6.3-1-1-6.1 6.2-6.1-6.2-1 1 6.1 6.3-6.5 6.7 1 1 6.5-6.6 6.5 6.6 1-1z"></path>
					</svg>
				</div>
				{ block.title }
			</div>
		</Popover>
	);

	return (
		<>
			<Draggable
				elementId="draggable-panel"
				__experimentalTransferDataType="wp-blocks"
				transferData={ {
					type: 'inserter',
					blocks: [ create( block.name ) ],
				} }
			>
				{ ( { onDraggableStart } ) => (
					<div
						className="srfm-ee-quick-access__sidebar--blocks--block"
						key={ id }
						onClick={ () => {
							handleOnClick( e, block );
						} }
						draggable
						onDragStart={ ( event ) => {
							isDragging.current = true;
							if ( onDraggableStart ) {
								onDraggableStart( event );
							}
						} }
						onDragEnd={ () => {} }
						onMouseOver={ handleMouseOver }
						onMouseOut={ handleMouseOut }
						onFocus={ handleMouseOver }
						onBlur={ handleMouseOut }
					>
						<div className="srfm-ee-quick-access__sidebar--blocks--block--icon">
							<Icon
								icon={
									block.icon?.src
										? block.icon.src
										: block.icon
								}
							/>
						</div>
						{ hovering && hoverPopover }
					</div>
				) }
			</Draggable>
		</>
	);
};

export default DraggableBlock;
