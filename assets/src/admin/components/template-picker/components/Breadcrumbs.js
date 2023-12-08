import { __ } from '@wordpress/i18n';
import { useLocation } from 'react-router-dom';
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
			{ ICONS.logo }
			{ page === 'add-new-form' && (
				<span className="srfm-header-breadcrumb">
					{ ICONS.breadcrumb }
					{ __( 'Add New Form', 'sureforms' ) }
				</span>
			) }
			{ method === 'template' && (
				<span className="srfm-header-breadcrumb">
					{ ICONS.breadcrumb }
					{ __( 'Select a Template', 'sureforms' ) }
				</span>
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
