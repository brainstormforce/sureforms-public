/**
 * The Quick Access Sidebar.
 */
import { useLayoutEffect, useState, useEffect } from '@wordpress/element';
import style from '../editor.lazy.scss';
import Blocks from './blocks';
import PopoverModal from './Modal';
import getApiData from '@Controls/getApiData';
import { __ } from '@wordpress/i18n';
import { Popover } from '@wordpress/components';

const Sidebar = () => {
	const [
		defaultAllowedQuickSidebarBlocks,
		setDefaultAllowedQuickSidebarBlocks,
	] = useState( srfm_quick_sidebar_blocks.allowed_blocks );
	const [ isPopoverVisible, setPopoverVisible ] = useState( false );

	// State to track the sorting status
	const [ isSorting, setSorting ] = useState( false );
	const [ hovering, setHovering ] = useState( null );
	// State to track the active tab
	const [ activeTab, setActiveTab ] = useState( 0 );
	const handleMouseOver = ( button ) => setHovering( button );

	const handleMouseOut = () => setHovering( false );
	// Function to handle outside click
	useEffect( () => {
		const handleOutsideClick = ( event ) => {
			// Check if the click is outside the QAB sidebar and popover
			if (
				document.querySelector( '.srfm-ee-quick-access' ) &&
				! document
					.querySelector( '.srfm-ee-quick-access' )
					.contains( event.target ) &&
				! document.querySelector( '.popover' )?.contains( event.target )
			) {
				setPopoverVisible( false );
				setSorting( false );

				setActiveTab( 0 );
			}
		};

		// Add event listener when component mounts
		document.body.addEventListener( 'click', handleOutsideClick );

		// Remove event listener when component unmounts
		return () => {
			document.body.removeEventListener( 'click', handleOutsideClick );
		};
	}, [] ); // Empty array ensures this effect runs only once when the component mounts

	// Function to handle tab click
	const handleTabClick = ( index ) => {
		setActiveTab( index );
	};

	useLayoutEffect( () => {
		style.use();
		return () => {
			style.unuse();
		};
	}, [] );
	// Function to open the popover
	const openPopover = () => {
		setPopoverVisible( true );
		setSorting( false );
	};
	// Function to close the popover
	const closePopover = () => {
		setPopoverVisible( false );
		setSorting( false );
	};
	// Function to enable re-arrange
	const enableRearrange = () => {
		setSorting( true );
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
		<>
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
							enableRearrange={ isSorting }
						/>
					</div>
				</div>
				{ /* The sidebar actions will come here - like the plus sign. */ }
				<div className="srfm-ee-quick-access__sidebar--tabs-container">
					<div className="srfm-ee-quick-access__sidebar--tabs">
						{ /* Map over your tab items */ }
						{ [ 'add', 'sort' ].map( ( tab, index ) => (
							<div
								key={ index }
								className={ `tab ${
									activeTab === index ? 'active' : ''
								}` }
								onClick={ () => handleTabClick( index ) }
							>
								{ 'add' === tab && (
									<div className="srfm-ee-quick-access__sidebar--actions">
										<div className="srfm-ee-quick-access__sidebar--actions--plus">
											<div className="srfm-quick-action-sidebar-wrap">
												<div
													id="plus-icon"
													onClick={ openPopover }
													onMouseOver={ () =>
														handleMouseOver( 'add' )
													}
													onMouseOut={
														handleMouseOut
													}
													onFocus={ () =>
														handleMouseOver( 'add' )
													}
													onBlur={ handleMouseOut }
												>
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
														closePopover={
															closePopover
														}
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
												{ hovering === 'add' && (
													<Popover
														placement="right"
														className="srfm-ee-quick-access__sidebar--blocks--block--icon--name"
													>
														<div className="block-title">
															{ __(
																'Add blocks to Quick Action Bar',
																'sureforms'
															) }
														</div>
													</Popover>
												) }
											</div>
										</div>
									</div>
								) }
								{ 'sort' === tab && (
									<div className="srfm-ee-quick-access__sidebar--actions">
										<div className="srfm-ee-quick-access__sidebar--actions--plus rearrange">
											<div className="srfm-quick-action-sidebar-wrap">
												<div
													id="rearrange-icon"
													onClick={ enableRearrange }
													onMouseOver={ () =>
														handleMouseOver(
															'sort'
														)
													}
													onMouseOut={
														handleMouseOut
													}
													onFocus={ () =>
														handleMouseOver(
															'sort'
														)
													}
													onBlur={ handleMouseOut }
												>
													<svg
														xmlns="http://www.w3.org/2000/svg"
														viewBox="0 0 24 24"
														width="24"
														height="24"
														aria-hidden="true"
														fill="#fff"
														focusable="false"
													>
														<path d="m19 7-3-3-8.5 8.5-1 4 4-1L19 7Zm-7 11.5H5V20h7v-1.5Z"></path>
													</svg>
													{ hovering === 'sort' && (
														<Popover
															placement="right"
															className="srfm-ee-quick-access__sidebar--blocks--block--icon--name"
														>
															<div className="block-title">
																{ __(
																	'Re-arrange block inside Quick Action Bar',
																	'sureforms'
																) }
															</div>
														</Popover>
													) }
												</div>
											</div>
										</div>
									</div>
								) }
							</div>
						) ) }
					</div>
				</div>
			</div>
		</>
	);
};

export default Sidebar;
