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

	// count the credits left in the account and show the number like 463.4k

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

	// if the methods is ai then hide the the scrollbar from body
	useEffect( () => {
		if ( method === 'ai' ) {
			document.body.style.overflow = 'hidden';
		} else {
			document.body.style.overflow = 'auto';
		}
	}, [ method ] );

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
								gap: '12px',
								width: '266px',
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
								{ creditsLeft
									.toString()
									.replace(
										/\B(?=(\d{3})+(?!\d))/g,
										','
									) }{ ' ' }
								AI Credits in Your Account
							</span>
							<span
								style={ {
									fontSize: '14px',
									fontWeight: '400',
									lineHeight: '20px',
									color: '#9CA3AF',
								} }
							>
								{ __(
									'Credits are used to generate forms with AI.',
									'sureforms'
								) }
							</span>
							<Button
								style={ {
									backgroundColor: 'transparent',
									color: '#D54407',
									fontSize: '14px',
									fontWeight: '400',
									lineHeight: '20px',
									width: '208px',
									height: '34px',
									border: 'none',
									cursor: 'pointer',
									border: '1px solid #D54407',
									padding: '9px 13px 9px 13px',
									borderRadius: '6px',
									lineHeight: '16px',
								} }
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
								style={ {
									backgroundColor: 'transparent',
									color: '#CD1A1A',
									fontSize: '14px',
									fontWeight: '400',
									lineHeight: '20px',
									height: '20px',
									border: 'none',
									cursor: 'pointer',
								} }
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
			<div
				style={ {
					position: 'fixed',
					top: 0,
					left: 0,
					width: '100%',
					height: '100%',
					backgroundColor: '#0F172AB2',
					zIndex: 999,
				} }
			/>
			<div
				style={ {
					position: 'fixed',
					top: '50%',
					left: '50%',
					transform: 'translate(-50%, -50%)',
					zIndex: '1000',
					display: 'flex',
					flexDirection: 'column',
					alignItems: 'flex-start',
					gap: '16px',
					width: '464px',
					height: '184px',
					padding: '20px',
					background: '#FFFFFF',
					borderRadius: '8px',
				} }
			>
				<div
					style={ {
						display: 'flex',
						alignItems: 'flex-start',
						gap: '12px',
						justifyContent: 'center',
					} }
				>
					<span
						style={ {
							paddingTop: '3px',
						} }
					>
						{ ICONS.warning }
					</span>
					<span
						style={ {
							fontSize: '18px',
							fontWeight: '700',
							lineHeight: '28px',
							color: '#0F172A',
						} }
					>
						{ __( 'Revoke Access', 'sureforms' ) }
					</span>
				</div>
				<span
					style={ {
						fontSize: '14px',
						fontWeight: '400',
						lineHeight: '20px',
						color: '#475569',
					} }
				>
					{ __(
						'Are you sure you want to disconnect? You will need to reconnect to use AI features again.',
						'sureforms'
					) }
				</span>
				<div
					style={ {
						display: 'flex',
						alignItems: 'flex-start',
						gap: '12px',
					} }
				>
					<Button
						style={ {
							backgroundColor: '#DC2626',
							color: '#ffffff',
							fontSize: '14px',
							fontWeight: '600',
							lineHeight: '20px',
							width: '100px',
							height: '34px',
							border: 'none',
							cursor: 'pointer',
							padding: '9px 13px 9px 13px',
							borderRadius: '6px',
							lineHeight: '16px',
						} }
						onClick={ () => {
							// localStorage.removeItem(
							// 	'zipAiAuthorizationStatus'
							// );
							window.location.assign(
								srfm_admin.zip_ai_auth_revoke_url
							);
						} }
					>
						{ __( 'Revoke', 'sureforms' ) }
					</Button>
					<Button
						style={ {
							backgroundColor: 'transparent',
							color: '#0F172A',
							fontSize: '14px',
							fontWeight: '600',
							lineHeight: '20px',
							width: '100px',
							height: '34px',
							border: 'none',
							cursor: 'pointer',
							border: '1px solid #D8DFE9',
							padding: '9px 13px 9px 13px',
							borderRadius: '6px',
							lineHeight: '16px',
						} }
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
