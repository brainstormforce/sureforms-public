import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import Header from './Header.js';
import ICONS from './icons.js';
import AiFormBuilder from './AiFormBuilder.js';

const LimitReachedPopup = ( { setShowLimitReachedPopup ,
	paraOne ,
	paraTwo ,
	onlClick,
	buttonText
} ) => {
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
						{ paraOne }
					</span>
					<span>
						{ paraTwo}
					</span>
				</div>
				<div className="srfm-limit-reached-popup-content">
					<Button
						className="srfm-limit-reached-more-credits-btn"
						onClick={ 
							onlClick
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
