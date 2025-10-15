import { __ } from '@wordpress/i18n';
import { addQueryParam } from '@Utils/Helpers';
import blurImage from './blur.png';

const UpgradePrompt = () => {
	return (
		<div
			style={ {
				backgroundColor: 'rgba(255, 255, 255, 0.9)',
				overflow: 'hidden',
				borderRadius: '1rem',
			} }
		>
			<div
				style={ {
					width: '100%',
					height: '156px',
					display: 'flex',
					flexDirection: 'column',
					alignItems: 'center',
					justifyContent: 'center',
					backgroundImage: `url(${ blurImage })`,
					backgroundPosition: 'center',
					backgroundSize: 'contain',
					backgroundRepeat: 'no-repeat',
					padding: '26px',
				} }
				aria-hidden="true"
			>
				<div
					style={ {
						position: 'relative',
						zIndex: 20,
						maxWidth: '36rem',
						display: 'flex',
						flexDirection: 'column',
						alignItems: 'center',
						gap: '8px',
						padding: '8px',
					} }
				>
					<h2
						style={ {
							fontWeight: 600,
							fontSize: 12,
							margin: 0,
						} }
					>
						{ __( 'Unlock Advanced Styling', 'sureforms' ) }
					</h2>
					<p
						style={ {
							fontSize: 12,
							color: '#4B5563',
							margin: 0,
							minWidth: '218px',
						} }
					>
						{ __(
							"Get full control over your form's look with custom colors, fonts, and layouts.",
							'sureforms'
						) }
					</p>
					<a
						style={ {
							fontSize: '12px',
							fontWeight: 600,
							color: '#D54407',
							transition: 'color 0.2s',
							textDecoration: 'none',
						} }
						target="_blank"
						href={ addQueryParam(
							srfm_admin?.pricing_page_url ||
								srfm_admin?.sureforms_pricing_page,
							'form-theme-upgrade-cta'
						) } rel="noreferrer"
					>
						{ __( 'Upgrade To Unlock', 'sureforms' ) }
					</a>
				</div>
			</div>
		</div>
	);
};

export default UpgradePrompt;
