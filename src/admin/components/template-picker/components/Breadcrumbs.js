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
	const templateId = query.get( 'template-id' );

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
			{ method === 'template' && (
				<Link
					className="srfm-tp-breadcrumb-url"
					to={ {
						location: `${ srfm_admin.site_url }/wp-admin/admin.php`,
						search: `?page=add-new-form&method=template`,
					} }
				>
					<span className="srfm-header-breadcrumb">
						{ ICONS.breadcrumb }
						{ __( 'Select a Template', 'sureforms' ) }
					</span>
				</Link>
			) }
			{ templateId && (
				<span className="srfm-header-breadcrumb">
					{ ICONS.breadcrumb }
					{ __( 'Template Preview', 'sureforms' ) }
				</span>
			) }
		</div>
	);
};

export default Breadcrumbs;
