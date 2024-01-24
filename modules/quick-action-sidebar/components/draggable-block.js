/**
 * Creates a single draggable block.
 */
import { useState, useRef } from '@wordpress/element';
import { Icon, Draggable, Popover } from '@wordpress/components';
import { dispatch, useDispatch, select } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import svgIcons from '@Svg/svgs.json';
import parse from 'html-react-parser';

const DraggableBlock = ( props ) => {
	const {
		block,
		id,
		create,
		blockInsertionPoint,
		getSelectedBlockClientId,
		getSelectedBlockAllowedBlocks,
		getBlockRootClientId,
		defaultAllowedQuickSidebarBlocks,
		updateDefaultAllowedQuickSidebarBlocks,
		saveOptionToDatabase,
	} = props;
	const [ hovering, setHovering ] = useState( false );
	const isDragging = useRef( false );
	const { createNotice } = useDispatch( 'core/notices' );
	const [ uniqueId, setUniqueId ] = useState( 0 );
	const removedNoticeID = `quick-action-sidebar/remove-notices-flow/removed-notice/${ uniqueId }`;
	const remove = parse( svgIcons.remove );
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

	// Removes the specified element from an array.
	const removeElementFromArray = (
		arrayFromWhichElementNeedToRemove,
		elementToRemove
	) =>
		arrayFromWhichElementNeedToRemove.filter(
			( element ) => element !== elementToRemove
		);

	const handleRemoveBlock = ( elementToRemove ) => {
		const updatedArray = removeElementFromArray(
			defaultAllowedQuickSidebarBlocks,
			elementToRemove.name
		);
		updateDefaultAllowedQuickSidebarBlocks( updatedArray );
		saveOptionToDatabase( updatedArray );
		// Increment uniqueId when removing a block
		setUniqueId( ( prevUniqueId ) => prevUniqueId + 1 );
		createNotice(
			'success',
			sprintf(
				/* translators: abbreviation for units */ __(
					'%s Removed from Quick Action Bar.',
					'sureforms'
				),
				elementToRemove.title
			),
			{
				type: 'snackbar',
				id: removedNoticeID,
				isDismissible: true,
			}
		);
		// Set a timeout to remove the notice after a specific duration (e.g., 600 milliseconds)
		setTimeout( () => {
			// Remove the notice by ID
			dispatch( 'core/notices' ).removeNotice( removedNoticeID );
		}, 1000 );
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
					{ remove }
				</div>
				{ block.title && block.title }
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
						onClick={ ( e ) => {
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
