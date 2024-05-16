import { __ } from '@wordpress/i18n';
import { useState, useRef, useEffect } from '@wordpress/element';
import { Button } from '@wordpress/components';
import ICONS from './icons';
import Breadcrumbs from './Breadcrumbs';
import { useLocation } from 'react-router-dom';
import { BsLightningCharge } from 'react-icons/bs';

const Header = () => {
	const [ showRevokePopover, setShowRevokePopover ] = useState( false );
	const [ showRevokeConfirmation, setShowRevokeConfirmation ] =
		useState( false );
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

	// if the methods is ai then hide the the scrollbar from body
	useEffect( () => {
		if ( method === 'template' ) {
			document.body.style.overflow = 'auto';
		} else {
			document.body.style.overflow = 'hidden';
		}
	}, [ method ] );

	// get the total and used credits
	const totalCredits = parseInt( srfm_admin.zip_ai_credit_details?.total );
	const usedCredits = parseInt( srfm_admin.zip_ai_credit_details?.used );

	const creditsLeft = totalCredits - usedCredits;

	// convert the number to k if it is greater than 1000
	const creditsLeftInK =
		creditsLeft > 1000
			? `${ ( creditsLeft / 1000 ).toFixed( 1 ) }K`
			: creditsLeft;

	if ( showRevokeConfirmation ) {
		return (
			<RevokeConfirmation
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
								{ creditsLeftInK }+
							</span>
							<BsLightningCharge />
						</div>
						{ showRevokePopover && (
							<div
								className="srfm-tp-header-credits-popover"
								ref={ revokePopover }
							>
								<span className="srfm-tp-header-credits-popover-title">
									{ creditsLeft
										.toString()
										.replace(
											/\B(?=(\d{3})+(?!\d))/g,
											','
										) }{ ' ' }
									{ __(
										'AI Credits in Your Account',
										'sureforms'
									) }
								</span>
								<span className="srfm-tp-header-credits-popover-description">
									{ __(
										'Credits are used to generate forms with AI.',
										'sureforms'
									) }
								</span>
								<Button
									className="srfm-credits-popover-more-btn"
									onClick={ () => {
										window.open(
											'https://app.zipwp.com/credits-pricing',
											'_blank'
										);
									} }
								>
									{ __( 'Get More Credits', 'sureforms' ) }
								</Button>
								<Button
									className="srfm-credits-popover-revoke-btn"
									onClick={ () => {
										setShowRevokePopover( false );
										setShowRevokeConfirmation( true );
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

const RevokeConfirmation = ( { setShowRevokeConfirmation } ) => {
	return (
		<>
			<Header />
			<div className="srfm-popup-overlay" />
			<div className="srfm-revoke-confirmation-container">
				<div className="srfm-popup-header">
					<span className="srfm-popup-icon">{ ICONS.warning }</span>
					<span className="srfm-popup-title">
						{ __( 'Revoke Access', 'sureforms' ) }
					</span>
				</div>
				<span className="srfm-revoke-confirmation-description">
					{ __(
						'Are you sure you want to disconnect? You will need to reconnect to use AI features again.',
						'sureforms'
					) }
				</span>
				<div className="srfm-revoke-confirmation-btn-container">
					<Button
						className="srfm-revoke-confirmation-revoke-btn"
						onClick={ () => {
							window.location.assign(
								srfm_admin.zip_ai_auth_revoke_url
							);
						} }
					>
						{ __( 'Revoke', 'sureforms' ) }
					</Button>
					<Button
						className="srfm-revoke-confirmation-cancel-btn"
						onClick={ () => {
							setShowRevokeConfirmation( false );
						} }
					>
						{ __( 'Cancel', 'sureforms' ) }
					</Button>
				</div>
			</div>
		</>
	);
};

export default Header;
