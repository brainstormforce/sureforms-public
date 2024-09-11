import { Button } from '@wordpress/components';
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';

const CreditDetailsPopup = ( {
	setShowRevokePopover,
	finalFormCreationCountRemaining,
} ) => {
	const revokePopover = useRef( null );

	const formCreationleft = srfm_admin?.srfm_ai_usage_details?.remaining ?? 0;
	const totalFormCount = srfm_admin?.srfm_ai_usage_details?.limit ?? 0;
	const aiFormCreationCount = totalFormCount - formCreationleft;
	const isRegistered = srfm_admin?.srfm_ai_usage_details?.type === 'registered';
	const aiFormsConsumed = 20 - finalFormCreationCountRemaining;

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
			<div className="srfm-tp-header-credits-popover-stats-ctn">
				<div className="srfm-tp-header-credits-popover-stats">
					<span>{ __( 'Usage ', 'sureforms' ) }</span>
					<span>{ isRegistered
						? aiFormsConsumed + '/' + 20
						: aiFormCreationCount + '/' + totalFormCount }</span>
				</div>
				<div className="srfm-progress-bar bg-slate-200">
					<div
						className="progress"
						style={ {
							width: `${
								// If the user is registered, show the progress bar based on the remaining form creations
								isRegistered
									? aiFormsConsumed / 20 * 100
									: aiFormCreationCount < totalFormCount ? ( aiFormCreationCount / totalFormCount ) * 100 : 100
							}%`,
						} }
					/>
				</div>
			</div>
			<div className="srfm-tp-header-credits-popover-title">
				<span className="srfm-tp-header-credits-popover-description">
					{
						sprintf(
							// translators: %s: Number of AI form generations
							__(
								'Free plan only allows %s AI form generations. Need to create more forms with AI?',
								'sureforms'
							),
							isRegistered ? 20 : totalFormCount
						)
					}
				</span>
			</div>
			<Button
				className="srfm-credits-popover-more-btn"
				onClick={ () => {
					window.open(
						srfm_admin?.pricing_page_url,
						'_blank'
					);
				} }
			>
				{ __( 'Upgrade Plan', 'sureforms' ) }
			</Button>
		</div>
	);
};

export default CreditDetailsPopup;
