import { __ } from '@wordpress/i18n';
import { addQueryParam } from '@Utils/Helpers';

export default ( { badgeName = 'Starter'
	, tooltipHeading = '', tooltipContent = '', tooltipPosition = 'bottom',
	utmMedium = '',
} ) => {
	return (
		<div className="srfm-tooltip"
		>
			<span
				className="srfm-premium-badge-text"
				onClick={ ( e ) => {
					e.stopPropagation();
					window.open( addQueryParam( srfm_admin?.sureforms_pricing_page, utmMedium ), '_blank', 'noreferrer' );
				}
				}
			>
				{
					badgeName + ' ' + __( 'Plan', 'sureforms' )
				}
			</span>
			<div className={ `tooltip-wrap ${ tooltipPosition }` }>
				<div className="tooltip-content">
					<div className="tooltip-text">
						<h3>
							{ tooltipHeading }
						</h3>
						<p>{ tooltipContent }</p>
					</div>
					<a target="_blank" href={ addQueryParam( srfm_admin?.sureforms_pricing_page, utmMedium ) } rel="noreferrer">{ __( 'Upgrade', 'sureforms' ) }</a>
				</div>
				<i></i>
			</div>
		</div>
	);
};
