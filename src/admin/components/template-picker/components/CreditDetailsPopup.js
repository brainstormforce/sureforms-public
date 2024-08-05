import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';

const CreditDetailsPopup = ( {
	setShowRevokePopover,
} ) => {
	const revokePopover = useRef( null );

	const formCreationleft = srfm_admin?.srfm_ai_usage_details?.remaining ?? 10;
	const totalFormCount = srfm_admin?.srfm_ai_usage_details?.limit ?? 10;
	const aiFormCreationCount = totalFormCount - formCreationleft;

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
					<span>{ aiFormCreationCount + '/' + totalFormCount }</span>

				</div>
				<div className="srfm-progress-bar bg-slate-200">
					<div
						className="progress"
						style={ {
							width: `${
								aiFormCreationCount < totalFormCount
									? ( aiFormCreationCount / totalFormCount ) *
									  100
									: 100
							}%`,
						} }
					/>
				</div>
			</div>
			<div className="srfm-tp-header-credits-popover-title">
				<span className="srfm-tp-header-credits-popover-description">

					{ wp.i18n.sprintf(
						/* translators: %s: number of AI form generations left */
						__(
							'Free plan only allows %d AI form generations. Need to create more forms with AI?',
							'sureforms'
						),
						totalFormCount ?? 10
					) }
				</span>
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
				{ __( 'Upgrade Plan', 'sureforms' ) }
			</Button>
		</div>
	);
};

export default CreditDetailsPopup;
