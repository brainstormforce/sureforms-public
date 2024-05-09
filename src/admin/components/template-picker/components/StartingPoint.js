import { __ } from '@wordpress/i18n';

const StartingPoint = ( {
	icon,
	title,
	description,
	isComingSoon,
	isBeta,
	onClick,
} ) => {
	return (
		<>
			{ isComingSoon && (
				<div className="srfm-tp-cs-badge">
					{ __( 'Coming Soon…', 'sureforms' ) }
				</div>
			) }
			{ isBeta && (
				<div className="srfm-tp-cs-badge">
					{ __( 'Beta', 'sureforms' ) }
				</div>
			) }
			<div className="srfm-tp-starting-point-card" onClick={ onClick }>
				{ icon }
				<div className="srfm-tp-sp-text-container">
					<h4 className="srfm-tp-sp-title">{ title }</h4>
					<p className="srfm-tp-sp-description">{ description }</p>
				</div>
			</div>
		</>
	);
};

export default StartingPoint;
