import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import Header from './Header.js';
import ICONS from './icons.js';
import AiFormBuilder from './AiFormBuilder.js';

const LimitReachedPopup = ( {
	title = __( 'Limit Reached', 'sureforms' ),
	paraOne,
	paraTwo,
	buttonText,
	onclick,
} ) => {
	return (
		<>
			<Header />
			<div className="srfm-popup-overlay" />
			<div className="srfm-limit-reached-popup">
				<div className="srfm-popup-header">
					<span className="srfm-popup-icon">{ ICONS.warning }</span>
					<span className="srfm-popup-title">
						{ title }
					</span>
					<div
						className="srfm-ai-limit-reached-close"
						onClick={
							() => window.location.href = srfm_admin.site_url + '/wp-admin/admin.php?page=add-new-form'
						 }
					>
						{ ICONS.close }
					</div>
				</div>
				<div className="srfm-limit-reached-popup-content">
					<span className="srfm-limit-reached-popup-text">
						{ paraOne }
					</span>
					<span>
						{ paraTwo }
					</span>
				</div>
				<div className="srfm-limit-reached-popup-content">
					<Button
						className="srfm-limit-reached-more-credits-btn"
						onClick={
							onclick
						 }
					>
						{ buttonText ?? __( 'Connect Now', 'sureforms' ) }
					</Button>
				</div>
			</div>
			<AiFormBuilder />
		</>
	);
};

export default LimitReachedPopup;
