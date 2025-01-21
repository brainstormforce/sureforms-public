import { __ } from '@wordpress/i18n';

export default ( { badgeName = 'Starter'
	, tooltipHeading = '', tooltipContent = '' } ) => {
	return (
		<div className="srfm-tooltip"
		>
			<span
				className="srfm-conversational-placeholder-label"
				onClick={ ( e ) => {
					e.stopPropagation();
					window.open( 'https://sureforms.com/pricing', '_blank' );
				}
				}
			>
				{
					badgeName + ' ' + __( 'Plan', 'sureforms' )
				}
			</span>
			<div className="tooltip-wrap bottom">
				<div className="tooltip-content">
					<div className="tooltip-text">
						<h3>
							{ tooltipHeading }
						</h3>
						{ tooltipContent }
					</div>
					<a target="_blank" href="https://sureforms.com/pricing" rel="noreferrer">{ __( 'Upgrade', 'sureforms' ) }</a>
				</div>
				<i></i>
			</div>
		</div>
	);
};
