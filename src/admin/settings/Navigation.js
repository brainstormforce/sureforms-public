import { __ } from '@wordpress/i18n';
import { Link, useLocation } from 'react-router-dom';
import parse from 'html-react-parser';
import svgIcons from '@Svg/svgs.json';

function useQuery() {
	return new URLSearchParams( useLocation().search );
}

export const navigation = [
	{
		name: __( 'General', 'sureforms' ),
		slug: 'general-settings',
		icon: parse( svgIcons.vertical_settings ),
	},
	{
		name: __( 'Validations', 'sureforms' ),
		slug: 'validation-settings',
		icon: parse( svgIcons.email ),
	},
	{
		name: __( 'Email', 'sureforms' ),
		slug: 'email-settings',
		icon: parse( svgIcons.email ),
	},
	{
		name: __( 'Security', 'sureforms' ),
		slug: 'security-settings',
		icon: parse( svgIcons.lock ),
	},
];

const Navigation = () => {
	const activatedTab = useQuery();

	return (
		<div className="srfm-settings-sidebar">
			<nav>
				{ navigation.map( ( item ) => (
					<Link
						to={ {
							location: `${ srfm_admin.site_url }/wp-admin/admin.php`,
							search: `?page=sureforms_form_settings&tab=${ item.slug }`,
						} }
						key={ item.name }
						className={ `srfm-settings-sidebar-category ${
							activatedTab.get( 'tab' ) === item.slug
								? 'active'
								: ''
						}` }
					>
						{ item.icon }
						<span>{ item.name }</span>
					</Link>
				) ) }
			</nav>
			<div className="srfm-notice-container">
				<div className="srfm-notice-title-container">
					{ parse( svgIcons.message ) }
					<div className="srfm-notice-title">
						{ __( 'Want More?', 'sureforms' ) }
					</div>
				</div>
				<div className="srfm-notice-body">
					{ __(
						'Unlock revenue boosting features when you upgrade to Pro',
						'sureforms'
					) }
				</div>
				<button className="button button-primary srfm-notice-btn">
					{ __( 'Upgrade to Premium', 'sureforms' ) }
				</button>
			</div>
		</div>
	);
};

export default Navigation;
