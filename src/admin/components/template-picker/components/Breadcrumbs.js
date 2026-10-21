import { __ } from '@wordpress/i18n';
import { useLocation } from 'react-router-dom';
import { Breadcrumb } from '@bsf/force-ui';
import { ChevronRight } from 'lucide-react';
import { getAddNewFormScreen } from '@Utils/form-start';

const Breadcrumbs = () => {
	function useQuery() {
		return new URLSearchParams( useLocation().search );
	}

	const query = useQuery();
	const page = query.get( 'page' );
	const screen = getAddNewFormScreen( query );
	const subPage = {
		templates: __( 'Templates', 'sureforms' ),
		ai: __( 'Generate with AI', 'sureforms' ),
	}[ screen ];

	// Narrow screens keep only the current page, then hide the trail, so the
	// bar never wraps or pushes the close button off screen.
	return (
		<div className="whitespace-nowrap [&_li]:whitespace-nowrap max-[860px]:[&_li:not(:last-child)]:hidden max-[860px]:[&_li:last-child>svg]:hidden max-[620px]:hidden">
			<Breadcrumb>
				<Breadcrumb.List>
					{ page === 'add-new-form' && (
						<Breadcrumb.Item>
							<Breadcrumb.Link
								href={ `${ srfm_admin.site_url }/wp-admin/admin.php?page=sureforms_menu` }
							>
								{ __( 'Dashboard', 'sureforms' ) }
							</Breadcrumb.Link>
						</Breadcrumb.Item>
					) }
					{ page === 'add-new-form' && (
						<Breadcrumb.Item>
							<ChevronRight color="#bdc1c7" className="size-3" />
							{ subPage ? (
								<Breadcrumb.Link
									href={ `${ srfm_admin.site_url }/wp-admin/admin.php?page=add-new-form` }
								>
									{ __( 'Add New Form', 'sureforms' ) }
								</Breadcrumb.Link>
							) : (
								<Breadcrumb.Page>
									{ __( 'Add New Form', 'sureforms' ) }
								</Breadcrumb.Page>
							) }
						</Breadcrumb.Item>
					) }
					{ page === 'add-new-form' && subPage && (
						<Breadcrumb.Item>
							<ChevronRight color="#bdc1c7" className="size-3" />
							<Breadcrumb.Page>{ subPage }</Breadcrumb.Page>
						</Breadcrumb.Item>
					) }
				</Breadcrumb.List>
			</Breadcrumb>
		</div>
	);
};

export default Breadcrumbs;
