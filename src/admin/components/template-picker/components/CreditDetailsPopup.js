import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';

const CreditDetailsPopup = ( {
	setShowRevokePopover,
	setShowRevokeConfirmation,
	creditsLeft,
} ) => {
	const revokePopover = useRef( null );
	const creditUsed = srfm_admin?.zip_ai_credit_details?.used;
	const totalCredits = srfm_admin?.zip_ai_credit_details?.total;

	useEffect( () => {
		const handleClickOutside = ( event ) => {
			if (
				revokePopover.current &&
				! revokePopover.current.contains( event.target ) &&
				event.target.className !== 'srfm-tp-header-credits' &&
				event.target.className !== 'srfm-tp-header-credits-left' &&
				event.target.tagName !== 'svg' &&
				event.target.tagName !== 'path'
			) {
				setShowRevokePopover( false );
			}
		};

		document.addEventListener( 'mousedown', handleClickOutside );
		return () => {
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [ revokePopover ] );

	return (
		<div className="srfm-tp-header-credits-popover" ref={ revokePopover }>
			<div className="srfm-tp-header-credits-popover-title">
				{ typeof creditsLeft === 'number' && ! isNaN( creditsLeft )
					? `${ creditsLeft
						.toString()
						.replace( /\B(?=(\d{3})+(?!\d))/g, ',' ) }
                            `
					: '0' }
				{ __( ' Credits in Your Account', 'sureforms' ) }
				<span className="srfm-tp-header-credits-popover-description">
					{ __(
						'100 Credits are used for each form generated with AI.',
						'sureforms'
					) }
				</span>
			</div>
			<div className="srfm-tp-header-credits-popover-stats-ctn">
				<div className="srfm-tp-header-credits-popover-stats">
					<span>{ __( 'Credits Usage ', 'sureforms' ) }</span>
					<span>{ creditUsed + '/' + totalCredits }</span>
				</div>
				<div className="srfm-progress-bar bg-slate-200">
					<div
						className="progress"
						style={ {
							width: `${ srfm_admin.zip_ai_credit_details.percentage }%`,
						} }
					/>
				</div>
			</div>
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
				{ __( 'Disconnect Account', 'sureforms' ) }
			</Button>
		</div>
	);
};

export default CreditDetailsPopup;
