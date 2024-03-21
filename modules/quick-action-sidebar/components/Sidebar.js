/**
 * The Quick Access Sidebar.
 */
import { useLayoutEffect, useState } from '@wordpress/element';
import style from '../editor.lazy.scss';
import Blocks from './blocks';
import PopoverModal from './Modal';
import getApiData from '@Controls/getApiData';

const Sidebar = () => {
	const [
		defaultAllowedQuickSidebarBlocks,
		setDefaultAllowedQuickSidebarBlocks,
	] = useState( srfm_quick_sidebar_blocks.allowed_blocks );
	const [ isPopoverVisible, setPopoverVisible ] = useState( false );
	useLayoutEffect( () => {
		style.use();
		return () => {
			style.unuse();
		};
	}, [] );
	const openPopover = () => {
		setPopoverVisible( true );
	};

	const closePopover = () => {
		setPopoverVisible( false );
	};
	function updateDefaultAllowedQuickSidebarBlocks( allowedBlocks ) {
		setDefaultAllowedQuickSidebarBlocks( allowedBlocks );
	}
	// Saving the allowed blocks to the database.
	const saveOptionToDatabase = ( allowedBlocks ) => {
		// update allowedBlocks.
		updateDefaultAllowedQuickSidebarBlocks( allowedBlocks );
		// Create an object with the srfm_ajax_nonce and confirmation properties.
		const data = {
			security: srfm_quick_sidebar_blocks.srfm_ajax_nonce,
			defaultAllowedQuickSidebarBlocks: JSON.stringify( allowedBlocks ),
		};
		// Call the getApiData function with the specified parameters.
		getApiData( {
			url: srfm_quick_sidebar_blocks.srfm_ajax_url,
			action: 'srfm_global_update_allowed_block',
			data,
		} );
	};
	return (
		<div className="srfm-ee-quick-access">
			<div className="srfm-ee-quick-access__sidebar">
				{ /* The block selection buttons will come here. */ }
				<div className="srfm-ee-quick-access__sidebar--blocks">
					<Blocks
						defaultAllowedQuickSidebarBlocks={
							defaultAllowedQuickSidebarBlocks
						}
						updateDefaultAllowedQuickSidebarBlocks={
							updateDefaultAllowedQuickSidebarBlocks
						}
						saveOptionToDatabase={ saveOptionToDatabase }
					/>
				</div>
				{ /* The sidebar actions will come here - like the plus sign. */ }
				<div className="srfm-ee-quick-access__sidebar--actions">
					<div className="srfm-ee-quick-access__sidebar--actions--plus">
						<div className="srfm-quick-action-sidebar-wrap">
							<div id="plus-icon" onClick={ openPopover }>
								<svg
									xmlns="http://www.w3.org/2000/svg"
									viewBox="0 0 24 24"
									width="24"
									height="24"
									aria-hidden="true"
									fill="#fff"
									focusable="false"
								>
									<path d="M18 11.2h-5.2V6h-1.6v5.2H6v1.6h5.2V18h1.6v-5.2H18z"></path>
								</svg>
							</div>
							{ isPopoverVisible && (
								<PopoverModal
									closePopover={ closePopover }
									updateDefaultAllowedQuickSidebarBlocks={
										updateDefaultAllowedQuickSidebarBlocks
									}
									defaultAllowedQuickSidebarBlocks={
										defaultAllowedQuickSidebarBlocks
									}
									saveOptionToDatabase={
										saveOptionToDatabase
									}
								/>
							) }
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};

export default Sidebar;
