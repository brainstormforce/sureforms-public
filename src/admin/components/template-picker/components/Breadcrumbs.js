import { __ } from '@wordpress/i18n';
import { useLocation, Link } from 'react-router-dom';
import ICONS from './icons';

const Breadcrumbs = () => {
	function useQuery() {
		return new URLSearchParams( useLocation().search );
	}

	const query = useQuery();

	const page = query.get( 'page' );
	const method = query.get( 'method' );

	return (
		<div className="srfm-header-breadcrumb-container">
			<div
				className="srfm-tp-logo"
				onClick={ () =>
					( window.location.href =
						srfm_admin.admin_url + '?page=sureforms_menu' )
				}
			>
				{ ICONS.logo }
			</div>
			{ page === 'add-new-form' && (
				<Link
					className="srfm-tp-breadcrumb-url"
					to={ {
						location: `${ srfm_admin.site_url }/wp-admin/admin.php`,
						search: `?page=add-new-form`,
					} }
				>
					<span className="srfm-header-breadcrumb">
						{ ICONS.breadcrumb }
						{ __( 'Add New Form', 'sureforms' ) }
					</span>
				</Link>
			) }
			{ method === 'ai' && (
				<Link
					className="srfm-tp-breadcrumb-url"
					to={ {
						location: `${ srfm_admin.site_url }/wp-admin/admin.php`,
						search: `?page=add-new-form&method=ai`,
					} }
				>
					<span className="srfm-header-breadcrumb">
						{ ICONS.breadcrumb }
						{ __( 'Describe your Form', 'sureforms' ) }
					</span>
				</Link>
			) }
		</div>
	);
};

export default Breadcrumbs;
