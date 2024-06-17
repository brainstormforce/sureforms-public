import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import Header from './Header.js';
import ICONS from './icons.js';
import AiFormBuilder from './AiFormBuilder.js';

const LimitReachedPopup = ( { setShowLimitReachedPopup } ) => {
	return (
		<>
			<Header />
			<div className="srfm-popup-overlay" />
			<div className="srfm-limit-reached-popup">
				<div className="srfm-popup-header">
					<span className="srfm-popup-icon">{ ICONS.warning }</span>
					<span className="srfm-popup-title">
						{ __( 'Limit Reached', 'sureforms' ) }
					</span>
					<div
						className="srfm-ai-limit-reached-close"
						onClick={ () => setShowLimitReachedPopup( false ) }
					>
						{ ICONS.close }
					</div>
				</div>
				<div className="srfm-limit-reached-popup-content">
					<span className="srfm-limit-reached-popup-text">
						{ __(
							'You have reached the maximum number of credits usage in your Free Plan.',
							'sureforms'
						) }
					</span>
					<span>
						{ __(
							'Please upgrade your plan in order to create more forms.',
							'sureforms'
						) }
					</span>
				</div>
				<div className="srfm-limit-reached-popup-content">
					<Button
						className="srfm-limit-reached-more-credits-btn"
						onClick={ () => {
							window.open(
								'https://app.zipwp.com/credits-pricing',
								'_blank'
							);
						} }
					>
						{ __( 'Get more credits', 'sureforms' ) }
					</Button>
					<span
						className="srfm-limit-reached-check-usage-btn"
						onClick={ () => {
							window.open(
								'https://app.zipwp.com/dashboard',
								'_blank'
							);
						} }
					>
						{ __( 'Check Your Usage', 'sureforms' ) }
					</span>
				</div>
			</div>
			<AiFormBuilder />
		</>
	);
};

export default LimitReachedPopup;
