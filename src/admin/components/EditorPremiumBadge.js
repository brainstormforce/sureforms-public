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
			>
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
