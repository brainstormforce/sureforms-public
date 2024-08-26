/**
 * Creates a popover modal.
 */
import { useState } from '@wordpress/element';
import { Popover, SearchControl, Icon } from '@wordpress/components';
import { useDispatch, dispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { getBlockTypes } from '@wordpress/blocks';

const PopoverModal = ( {
	closePopover,
	defaultAllowedQuickSidebarBlocks,
	saveOptionToDatabase,
} ) => {
	const [ searchTerm, setSearchTerm ] = useState( '' );
	const { createNotice } = useDispatch( 'core/notices' );
	const [ uniqueId, setUniqueId ] = useState( 0 );
	const addedNoticeID = `quick-action-sidebar/add-notices-flow/added-notice/${ uniqueId }`;
	const blocks = getBlockTypes();

	const closePopup = () => {
		// Call the callback function in the parent
		closePopover( false );
	};

	const handleSearchChange = ( newSearchTerm ) =>
		setSearchTerm( newSearchTerm );

	const handleBlockClick = ( selectedBlock ) => {
		// You can handle the selected block here, e.g., add it to the state or perform other actions
		const allowedBlocks = [
			...defaultAllowedQuickSidebarBlocks,
			selectedBlock.name,
		];
		saveOptionToDatabase( allowedBlocks );
		// Increment uniqueId when removing a block
		setUniqueId( ( prevUniqueId ) => prevUniqueId + 1 );

		createNotice(
			'success',
			sprintf(
				/* translators: abbreviation for units */ __(
					'%s Added to Quick Action Bar.',
					'sureforms'
				),
				selectedBlock.title
			),
			{
				type: 'snackbar',
				id: addedNoticeID,
				isDismissible: true,
			}
		);
		// Set a timeout to remove the notice after a specific duration (e.g., 600 milliseconds)
		setTimeout( () => {
			// Remove the notice by ID
			dispatch( 'core/notices' ).removeNotice( addedNoticeID );
		}, 1000 );
		closePopup();
	};

	const filteredBlocks = blocks.filter( ( block ) =>
		block.title.toLowerCase()?.includes( searchTerm.toLowerCase() )
	);

	// Separate arrays for used and unused items
	const usedArray = [];
	const unusedArray = [];

	// Iterate.
	filteredBlocks.forEach( ( item ) => {
		if ( defaultAllowedQuickSidebarBlocks?.includes( item.name ) ) {
			usedArray.push( item );
		} else if ( item.name !== 'srfm/form' ) {
			unusedArray.push( item );
		}
	} );

	const isSrfmBlock = ( item ) =>
		item?.name?.includes( 'srfm/' ) && ! item.parent;

	const isTermMatched = ( item ) =>
		item?.title?.toLowerCase()?.includes( searchTerm.toLowerCase() );

	const addToSidebar = () => {
		return unusedArray.map(
			( item, index ) =>
				// include all srfm blocks and core/paragraph block
				isSrfmBlock( item ) && (
					<div
						key={ index }
						className="srfm-block-wrap"
						onClick={ () => handleBlockClick( item ) }
						style={ { cursor: 'pointer' } }
					>
						<div className="srfm-ee-quick-access__sidebar--blocks--block--icon">
							<Icon
								icon={
									item.icon?.src ? item.icon.src : item.icon
								}
							/>
						</div>
						<div className="block-title">{ item.title }</div>
					</div>
				)
		);
	};

	const alreadyPresentInSidebar = () => {
		return usedArray.map(
			( item, index ) =>
				isSrfmBlock( item ) && (
					<div key={ index } className="srfm-block-wrap">
						<div className="srfm-ee-quick-access__sidebar--blocks--block--icon">
							<Icon
								icon={
									item.icon?.src ? item.icon.src : item.icon
								}
							/>
						</div>
						<div className="block-title">{ item.title }</div>
					</div>
				)
		);
	};

	return (
		<Popover
			onClose={ closePopup }
			placement="right-start"
			className="srfm-quick-action-block-popover"
		>
			<SearchControl
				value={ searchTerm }
				onChange={ handleSearchChange }
				label="Search Blocks"
				className="srfm-quick-action-block-popover-search"
			/>
			<div className="srfm-block-container">
				{ unusedArray.some(
					( item ) => isSrfmBlock( item ) && isTermMatched( item )
				) && (
					<div className="block-editor-inserter__panel-header srfm-quick-action-block-popover-header__add-to-quick-action-bar">
						<h2 className="block-editor-inserter__panel-title">
							{ __( 'Add to Quick Action Bar', 'sureforms' ) }
						</h2>
					</div>
				) }
				{ addToSidebar() }
				{ usedArray.some(
					( item ) => isSrfmBlock( item ) && isTermMatched( item )
				) && (
					<div className="block-editor-inserter__panel-header srfm-quick-action-block-popover-header__already-present-in-quick-action-bar">
						<h2 className="block-editor-inserter__panel-title">
							{ __(
								'Already Present in Quick Action Bar',
								'sureforms'
							) }
						</h2>
					</div>
				) }
				{ alreadyPresentInSidebar() }
				{ ! unusedArray.some(
					( item ) => isSrfmBlock( item ) && isTermMatched( item )
				) &&
					! usedArray.some(
						( item ) =>
							isSrfmBlock( item ) &&
							item.title
								.toLowerCase()
								.includes( searchTerm.toLowerCase() )
					) && (
					<div className="block-editor-inserter__no-results">
						<p>{ __( 'No results found.', 'sureforms' ) }</p>
					</div>
				) }
			</div>
		</Popover>
	);
};

export default PopoverModal;
