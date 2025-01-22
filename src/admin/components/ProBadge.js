import { __ } from '@wordpress/i18n';
import { addQueryParam } from '@Utils/Helpers';

export default ( { badgeName = 'Starter'
	, tooltipHeading = '', tooltipContent = '', tooltipPosition = 'top', source = 'settings_page' } ) => {
	return (
		<div className="srfm-tooltip"
		>
			<span
				className="srfm-pro-badge-label"
				onClick={ ( e ) => {
					e.stopPropagation();
					window.open( addQueryParam( 'https://sureforms.com/pricing', source ) );
				}
				}
			>
				{
					badgeName + ' ' + __( 'Plan', 'sureforms' )
				}
			</span>
			<div className={`tooltip-wrap ${ tooltipPosition }`}>
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