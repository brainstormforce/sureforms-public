import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import { Button, Tooltip, ProgressBar } from '@bsf/force-ui';

const CreditDetailsPopup = ( {
	setShowRevokePopover,
	finalFormCreationCountRemaining,
	children,
	showRevokePopover,
} ) => {
	const revokePopover = useRef( null );

	const formCreationleft = srfm_admin?.srfm_ai_usage_details?.remaining ?? 0;
	const totalFormCount = srfm_admin?.srfm_ai_usage_details?.limit ?? 0;
	const aiFormCreationCount = totalFormCount - formCreationleft;
	const isRegistered =
		srfm_admin?.srfm_ai_usage_details?.type === 'registered';
	const aiFormsConsumed = totalFormCount - finalFormCreationCountRemaining;

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
		<Tooltip
			tooltipPortalId="srfm-add-new-form-container"
			className="border border-solid border-border-subtle shadow-sm max-w-64 p-4"
			open={ showRevokePopover }
			setOpen={ setShowRevokePopover }
			placement="bottom-end"
			variant="light"
			content={
				<div className="space-y-3" ref={ revokePopover }>
					<div className="space-y-1">
						<div className="text-sm text-text-tertiary flex items-center justify-between">
							<span>{ __( 'Usage ', 'sureforms' ) }</span>
							<span>
								{ isRegistered
									? aiFormsConsumed + '/' + 20
									: aiFormCreationCount +
									  '/' +
									  totalFormCount }
							</span>
						</div>
						<ProgressBar
							progress={
								isRegistered
									? ( aiFormsConsumed / 20 ) * 100
									: aiFormCreationCount < totalFormCount
										? ( aiFormCreationCount / totalFormCount ) *
									  100
										: 100
							}
							className="h-1 [&>div]:h-1"
						/>
					</div>
					<div className="text-sm text-text-tertiary">
						<span>
							{ sprintf(
								// translators: %s: Number of AI form generations
								__(
									'Free plan only allows %s AI form generations. Need to create more forms with AI?',
									'sureforms'
								),
								isRegistered ? 20 : totalFormCount
							) }
						</span>
					</div>
					<Button
						className="w-full"
						onClick={ () => {
							window.open(
								srfm_admin?.pricing_page_url,
								'_blank'
							);
						} }
						size="sm"
					>
						{ __( 'Upgrade Plan', 'sureforms' ) }
					</Button>
				</div>
			}
		>
			{ children }
		</Tooltip>
	);
};

export default CreditDetailsPopup;
