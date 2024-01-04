import { useState } from '@wordpress/element';
import { Popover, SearchControl, Icon } from '@wordpress/components';
import { getBlockTypes } from '@wordpress/blocks';
import { useSelect, useDispatch } from '@wordpress/data';
import { STORE_NAME as storeName } from '@Store/constants';
import apiFetch from '@wordpress/api-fetch';

const PopoverModal = () => {
	const blocks = getBlockTypes();
	const [isPopoverVisible, setPopoverVisible] = useState( false );
	const [searchTerm, setSearchTerm] = useState( '' );
	const { updateDefaultAllowedQuickSidebarBlocks } = useDispatch( storeName );
	const getDefaultAllowedQuickSidebarBlocks = useSelect( ( select ) => select( storeName ).getDefaultAllowedQuickSidebarBlocks() );
	
	// Example of saving the allowed blocks to the database.
	const saveOptionToDatabase = ( allowedBlocks ) => {
		const formData = new window.FormData();

		formData.append( 'action', 'uag_global_update_allowed_block' );
		formData.append( 'security', uagb_blocks_info.uagb_ajax_nonce );
		formData.append( 'defaultAllowedQuickSidebarBlocks', JSON.stringify( allowedBlocks ) );

		apiFetch( {
			url: uagb_blocks_info.ajax_url,
			method: 'POST',
			body: formData,
		} ).then( ( data ) => {
			if ( data?.success ) {
				updateDefaultAllowedQuickSidebarBlocks( allowedBlocks );
			}
		} );
	};
	const openPopover = () => {
		setPopoverVisible( true );
	};

	const closePopover = () => {
		setPopoverVisible( false );
	};

	const handleSearchChange = ( newSearchTerm ) => {
		setSearchTerm( newSearchTerm );
	};

	const handleBlockClick = ( selectedBlock ) => {
		// You can handle the selected block here, e.g., add it to the state or perform other actions
		const allowedBlocks = [
			...getDefaultAllowedQuickSidebarBlocks,
			selectedBlock.name
		];
		saveOptionToDatabase( allowedBlocks );
		closePopover();
	};

	const filteredBlocks = blocks.filter( ( block ) =>
		block.title.toLowerCase().includes( searchTerm.toLowerCase() )
	);
	
	return (
		<div className='spectra-quick-action-sidebar-wrap'>
			<div id="plus-icon" onClick={openPopover} style={{ cursor: 'pointer' }}>
				+
			</div>
			{isPopoverVisible && (
				<Popover
					onClose={closePopover}
					placement="right-start"
					className="spectra-quick-action-block-popover"
				>
					<SearchControl
						value={searchTerm}
						onChange={handleSearchChange}
						label="Search Blocks"
					/>
					<div className="spectra-block-container">
						{filteredBlocks.map( ( item, index ) => (
							item.name.includes( 'uagb/' ) && !item.parent &&
							<div
								key={index}
								className="spectra-block-wrap"
								onClick={() => handleBlockClick( item )}
								style={{cursor: 'pointer'}}
							>
								<div className='spectra-ee-quick-access__sidebar--blocks--block--icon'>
									<Icon icon={item.icon?.src ? item.icon.src : item.icon} />
								</div>
							</div>
						) )}
					</div>
				</Popover>
			)}
		</div>
	);
};

export default PopoverModal;
