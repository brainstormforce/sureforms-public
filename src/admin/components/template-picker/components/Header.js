import { useState, useEffect } from '@wordpress/element';
import ICONS from './icons';
import Breadcrumbs from './Breadcrumbs';
import { useLocation } from 'react-router-dom';
import { BsLightningCharge } from 'react-icons/bs';
import RevokeConfirmationPopup from './RevokeConfirmationPopup.js';
import CreditDetailsPopup from './CreditDetailsPopup.js';
import { formatNumber, getRemaingCredits } from '@Utils/Helpers';

const Header = () => {
	const [ showRevokePopover, setShowRevokePopover ] = useState( false );
	const [ showRevokeConfirmation, setShowRevokeConfirmation ] =
		useState( false );
	function useQuery() {
		return new URLSearchParams( useLocation().search );
	}

	const query = useQuery();

	const page = query.get( 'page' );
	const method = query.get( 'method' );

	// if the methods is ai then hide the the scrollbar from body
	useEffect( () => {
		if ( method === 'template' ) {
			document.body.style.overflow = 'auto';
		} else {
			document.body.style.overflow = 'hidden';
		}
	}, [ method ] );

	const creditsLeft = getRemaingCredits();

	if ( showRevokeConfirmation ) {
		return (
			<RevokeConfirmationPopup
				setShowRevokeConfirmation={ setShowRevokeConfirmation }
			/>
		);
	}

	return (
		<div className="srfm-tp-header">
			<div className="srfm-tp-header-items">
				{ /** Logo & Breadcrumbs */ }
				<div className="srfm-tp-main-title">
					<Breadcrumbs />
				</div>
			</div>

			{ /* if the user is authorized and the page is add-new-form and the method is ai then show the credits left in the account
			 */ }
			{ srfm_admin.is_authorized &&
			page === 'add-new-form' &&
			method === 'ai' ? (
				<div className="srfm-tp-header-credits-ctn">
					<div
						style={ {
							// if popover is open, change background color
							background: showRevokePopover ? '#F3F4F6' : 'white',
						} }
						className="srfm-tp-header-credits"
						onClick={ () => {
							setShowRevokePopover( ! showRevokePopover );
						} }
					>
						<span className="srfm-tp-header-credits-left">
							{ formatNumber( creditsLeft ) }
						</span>
						<BsLightningCharge />
					</div>
					{ showRevokePopover && (
						<CreditDetailsPopup
							setShowRevokePopover={ setShowRevokePopover }
							setShowRevokeConfirmation={
								setShowRevokeConfirmation
							}
							creditsLeft={ creditsLeft }
						/>
					) }
					<div
						className="srfm-tp-header-close"
						onClick={ () => {
							window.location.href =
								'/wp-admin/admin.php?page=sureforms_menu';
						} }
					>
						<div>{ ICONS.close }</div>
					</div>
				</div>
			) : (
				<div
					className="srfm-tp-header-close"
					onClick={ () => {
						window.location.href =
							'/wp-admin/admin.php?page=sureforms_menu';
					} }
				>
					<div>{ ICONS.close }</div>
				</div>
			) }
		</div>
	);
};

export default Header;
