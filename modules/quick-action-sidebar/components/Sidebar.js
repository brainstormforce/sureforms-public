/**
 * The Quick Access Sidebar.
 */
import { useLayoutEffect, useState } from '@wordpress/element';
import style from '../editor.lazy.scss';
import Blocks from './blocks';
import PopoverModal from './Modal';

const Sidebar = () => {
	const [
		defaultAllowedQuickSidebarBlocks,
		setDefaultAllowedQuickSidebarBlocks,
	] = useState( quickSidebarBlocks.allowed_blocks );
	console.log( defaultAllowedQuickSidebarBlocks );
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
	function updateDefaultAllowedQuickSidebarBlocks( value ) {
		return {
			...defaultAllowedQuickSidebarBlocks,
			value,
		};
	}
	return (
		<div className="srfm-ee-quick-access">
			<div className="srfm-ee-quick-access__sidebar">
				{ /* The block selection buttons will come here. */ }
				<div className="srfm-ee-quick-access__sidebar--blocks">
					<Blocks
						defaultAllowedQuickSidebarBlocks={
							defaultAllowedQuickSidebarBlocks
						}
						setDefaultAllowedQuickSidebarBlocks={
							setDefaultAllowedQuickSidebarBlocks
						}
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
									setDefaultAllowedQuickSidebarBlocks={
										setDefaultAllowedQuickSidebarBlocks
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
