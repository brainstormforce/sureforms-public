import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import ICONS from './icons';
import Breadcrumbs from './Breadcrumbs';
import { useLocation } from 'react-router-dom';
import { BsLightningCharge } from 'react-icons/bs';
import CreditDetailsPopup from './CreditDetailsPopup.js';
import { Button } from '@wordpress/components';

const Header = () => {
	const [ showRevokePopover, setShowRevokePopover ] = useState( true );
	const formCreationleft = srfm_admin?.srfm_ai_usage_details?.remaining ?? 0;

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

	return (
		<div
			className="srfm-tp-header-ctn"
		>
			<div
				className="srfm-tp-header"

			>
				<div className="srfm-tp-header-items">
					{ /** Logo & Breadcrumbs */ }
					<div className="srfm-tp-main-title">
						<Breadcrumbs />
					</div>
				</div>

				{ /* if the page is add-new-form and the method is ai then show the credits left in the account only if the user is not pro user
				 */ }
				{ page === 'add-new-form' && method === 'ai' && ( ! srfm_admin?.is_pro_active || ! srfm_admin?.is_pro_license_active )
				 ? (
						<div className="srfm-tp-header-credits-ctn">
							<Button
								style={ {
								// if popover is open, change background color
									background: showRevokePopover
										? '#F3F4F6'
										: 'white',
								} }
								className="srfm-tp-header-credits"
								onClick={ () => {
									setShowRevokePopover( ! showRevokePopover );
								} }
							>
								<span className="srfm-tp-header-credits-left">
									{ wp.i18n.sprintf(
									/* translators: %s: number of AI form generations left */
										__(
											'%d AI form generations left',
											'sureforms'
										),
										formCreationleft
									) }
								</span>
								<div className="srfm-tp-header-bolt-icon">
									<BsLightningCharge />
								</div>
							</Button>
							{ showRevokePopover && (
								<CreditDetailsPopup
									finalFormCreationCountRemaining={ formCreationleft }
									setShowRevokePopover={ setShowRevokePopover }
								/>
							) }
							<div
								className="srfm-tp-header-close"
								onClick={ () => {
									window.location.href =
									'/wp-admin/admin.php?page=sureforms_menu';
								} }
							>
								<div
									className="srfm-tp-header-close-icon"
								>
									{ ICONS.close }
								</div>
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
							<div
								className="srfm-tp-header-close-icon"
							>{ ICONS.close }</div>
						</div>
					) }
			</div>
		</div>
	);
};

export default Header;
