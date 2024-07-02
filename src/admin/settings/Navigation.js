import { __ } from '@wordpress/i18n';
import { Link, useLocation } from 'react-router-dom';
import parse from 'html-react-parser';
import svgIcons from '@Svg/svgs.json';
import { applyFilters } from '@wordpress/hooks';

import {
	MdSettings,
	MdWarningAmber,
	MdOutlineMail,
	MdOutlineSecurity,
	MdOutlineDashboardCustomize,
} from 'react-icons/md';

function useQuery() {
	return new URLSearchParams( useLocation().search );
}

export const navigation = applyFilters( 'srfm.settings.navigation', [
	{
		name: __( 'General', 'sureforms' ),
		slug: 'general-settings',
		icon: <MdSettings size={ 20 } color="#0f172a" />,
	},
	{
		name: __( 'Validations', 'sureforms' ),
		slug: 'validation-settings',
		icon: <MdWarningAmber size={ 20 } color="#0f172a" />,
	},
	{
		name: __( 'Email', 'sureforms' ),
		slug: 'email-settings',
		icon: <MdOutlineMail size={ 20 } color="#0f172a" />,
	},
	{
		name: __( 'Spam Protection', 'sureforms' ),
		slug: 'security-settings',
		icon: <MdOutlineSecurity size={ 20 } color="#0f172a" />,
	},
	{
		name: __( 'Integrations', 'sureforms' ),
		slug: 'integration-settings',
		icon: <MdOutlineDashboardCustomize size={ 20 } color="#0f172a" />,
	},
] );

const isProActive = srfm_admin.is_pro_active;

const Navigation = () => {
	const activatedTab = useQuery();

	return (
		<div
			className="srfm-settings-sidebar"
			style={ {
				...( isProActive && { width: '450px' } ),
			} }
		>
			<div className="srfm-settings-sidebar-content">
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
				{ ! isProActive && (
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
						<button onClick={
							() => {
								window.open( 'https://sureforms.com/pricing' );
							}
						} className="srfm-button-primary srfm-notice-btn">
							{ __( 'Upgrade to Pro', 'sureforms' ) }
						</button>
					</div>
				) }
			</div>
		</div>
	);
};

export default Navigation;
