import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom';

const StartingPoint = ( {
	icon,
	title,
	description,
	isComingSoon,
	isSecondary,
	btnText,
	btnLink,
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
				<div className={ `srfm-tp-sp-container ${ isSecondary ? 'srfm-button-secondary' : 'srfm-button-primary' } srfm-tp-btn` }>
					<Link
						className={ `srfm-buttons srfm-tp-btn` }
						to={ btnLink }
						reloadDocument
					>
						{ btnText }
					</Link>
				</div>
			</div>
		</>
	);
};

export default StartingPoint;
