import { __ } from '@wordpress/i18n';
import { addQueryParam } from '@Utils/Helpers';
import blurImage from '@Image/blur.png';

const UpgradePrompt = () => {
	return (
		<div className="srfm-upgrade-prompt">
			<div
				className="srfm-upgrade-prompt__background"
				style={ { backgroundImage: `url(${ blurImage })` } }
				aria-hidden="true"
			>
				<div className="srfm-upgrade-prompt__content">
					<span className="srfm-upgrade-prompt__heading">
						{ __( 'Unlock Advanced Styling', 'sureforms' ) }
					</span>
					<p className="srfm-upgrade-prompt__description">
						{ __(
							"Get full control over your form's look with custom colors, fonts, and layouts.",
							'sureforms'
						) }
					</p>
					<a
						className="srfm-upgrade-prompt__link"
						target="_blank"
						href={ addQueryParam(
							srfm_admin?.pricing_page_url ||
								srfm_admin?.sureforms_pricing_page,
							'form-theme-upgrade-cta'
						) }
						rel="noreferrer"
					>
						{ __( 'Upgrade To Unlock', 'sureforms' ) }
					</a>
				</div>
			</div>
		</div>
	);
};

export default UpgradePrompt;
