import { __ } from '@wordpress/i18n';
import { addQueryParam } from '../../utils/Helpers';

export default ( { tooltipHeading = '', tooltipContent = '', tooltipPosition = 'bottom',
	utmMedium = '',
} ) => {
	return (
		<div className="srfm-tooltip"
		>
			<span
				className="srfm-premium-badge-text"
				onClick={ ( e ) => {
					e.stopPropagation();
					window.open( addQueryParam( srfm_admin?.pricing_page_url || srfm_admin?.sureforms_pricing_page, utmMedium ), '_blank', 'noreferrer' ); // both srfm_admin are in different js file context one is being loaded in Dashboard and other in Form Editor
				}
				}
			>
				<span className="srfm-premium-svg-wrapper">
					<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M9.5 5.5H2.5C1.94772 5.5 1.5 5.94772 1.5 6.5V10C1.5 10.5523 1.94772 11 2.5 11H9.5C10.0523 11 10.5 10.5523 10.5 10V6.5C10.5 5.94772 10.0523 5.5 9.5 5.5Z" stroke="white" strokeLinecap="round" strokeLinejoin="round" />
						<path d="M3.5 5.5V3.5C3.5 2.83696 3.76339 2.20107 4.23223 1.73223C4.70107 1.26339 5.33696 1 6 1C6.66304 1 7.29893 1.26339 7.76777 1.73223C8.23661 2.20107 8.5 2.83696 8.5 3.5V5.5" stroke="white" strokeLinecap="round" strokeLinejoin="round" />
					</svg>
				</span>
				<span className="srfm-premium-text-wrapper"> { __( 'Premium', 'sureforms' ) } </span>
			</span>
			<div className={ `tooltip-wrap ${ tooltipPosition }` }>
				<div className="tooltip-content">
					<div className="tooltip-text">
						<h3>
							{ tooltipHeading }
						</h3>
						<p>{ tooltipContent }</p>
					</div>
					<a target="_blank" href={ addQueryParam( srfm_admin?.pricing_page_url || srfm_admin?.sureforms_pricing_page, utmMedium ) } rel="noreferrer">{ __( 'Upgrade', 'sureforms' ) }</a>{
						// both srfm_admin are in different js file context one is being loaded in Dashboard and other in Form Editor
					}
				</div>
				<i></i>
			</div>
		</div>
	);
};
