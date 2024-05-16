import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';

const CreditDetailsPopup = ( {
	setShowRevokePopover,
	setShowRevokeConfirmation,
	creditsLeft,
} ) => {
	const revokePopover = useRef( null );

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

	return (
		<div className="srfm-tp-header-credits-popover" ref={ revokePopover }>
			<span className="srfm-tp-header-credits-popover-title">
				{ typeof creditsLeft === 'number' && ! isNaN( creditsLeft )
					? `${ creditsLeft
						.toString()
						.replace( /\B(?=(\d{3})+(?!\d))/g, ',' ) }
                            `
					: '0' }
				{ __( ' AI Credits in Your Account', 'sureforms' ) }
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
	);
};

export default CreditDetailsPopup;
