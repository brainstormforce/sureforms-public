import ICONS from './icons';
import Breadcrumbs from './Breadcrumbs';
import aiCreditsIcon from '@Image/ai-credits.svg';
import { useLocation } from 'react-router-dom';
import { BsLightningCharge } from 'react-icons/bs';
import { useState, useRef, useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const Header = () => {
	const [ showRevokePopover, setShowRevokePopover ] = useState( false );
	const revokePopover = useRef( null );
	function useQuery() {
		return new URLSearchParams( useLocation().search );
	}

	const query = useQuery();

	const page = query.get( 'page' );
	const method = query.get( 'method' );

	useEffect( () => {
		const handleClickOutside = ( event ) => {
			if (
				revokePopover.current &&
				! revokePopover.current.contains( event.target )
			) {
				setShowRevokePopover( false );
			}
		};

		document.addEventListener( 'mousedown', handleClickOutside );
		return () => {
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [ revokePopover ] );

	// count the credits left in the account and show the number like 463.4k

	const totalCredits = parseInt( srfm_admin.zip_ai_credit_details?.total );
	const usedCredits = parseInt( srfm_admin.zip_ai_credit_details?.used );

	const creditsLeft = totalCredits - usedCredits;

	// convert the number to k if it is greater than 1000
	const creditsLeftInK =
		creditsLeft > 1000
			? `${ ( creditsLeft / 1000 ).toFixed( 1 ) }k`
			: creditsLeft;

	return (
		<div className="srfm-tp-header">
			<div className="srfm-tp-header-items">
				{ /** Logo & Breadcrumbs */ }
				<div className="srfm-tp-main-title">
					<Breadcrumbs />
				</div>
			</div>

			{ /** Close Icon */ }
			{ srfm_admin.is_authorized &&
			page === 'add-new-form' &&
			method === 'ai' ? (
				<div
					style={ {
						display: 'flex',
						alignItems: 'center',
						justifyContent: 'space-between',
						width: '155px',
					} }
				>
					<div
						style={ {
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'space-between',
							gap: '8px',
							borderRadius: '4px',
							padding: '4px 12px',
							border: '1px solid #E5E7EB',
							cursor: 'pointer',
							// if popover is open, change background color
							background: showRevokePopover ? '#F3F4F6' : 'white',
						} }
						onClick={ () => {
							setShowRevokePopover( ! showRevokePopover );
						} }
					>
						<span
							style={ {
								fontSize: '14px',
								fontWeight: '400',
								lineHeight: '20px',
								color: '#374151',
							} }
						>
							{ creditsLeftInK }+
						</span>
						<BsLightningCharge />
					</div>
					{ showRevokePopover && (
						<div
							style={ {
								position: 'absolute',
								top: '60px',
								right: '73px',
								display: 'flex',
								flexDirection: 'column',
								alignItems: 'center',
								width: 'fit-content',
								height: '186px',
								background: '#FFFFFF',
								borderRadius: '8px',
								padding: '16px',
								border: '1px solid #E5E7EB',
							} }
							ref={ revokePopover }
						>
							<span
								style={ {
									fontSize: '14px',
									fontWeight: '500',
									lineHeight: '20px',
									color: '#030712',
								} }
							>
								{ creditsLeft } AI Credits in Your Account
							</span>
							<Button
								style={ {
									backgroundColor: 'transparent',
									color: '#CD1A1A',
									fontSize: '14px',
									fontWeight: '400',
									lineHeight: '20px',
									width: '208px',
									height: '20px',
									border: 'none',
									cursor: 'pointer',
								} }
								onClick={ () => {
									localStorage.removeItem(
										'zipAiAuthorizationStatus'
									);
									window.location.assign(
										srfm_admin.zip_ai_auth_revoke_url
									);
								} }
							>
								{ __( 'Revoke Authorization', 'sureforms' ) }
							</Button>
						</div>
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
