import { __ } from '@wordpress/i18n';
import { useLocation } from 'react-router-dom';
import { Breadcrumb } from '@bsf/force-ui';
import { ChevronRight } from 'lucide-react';

const Breadcrumbs = () => {
	function useQuery() {
		return new URLSearchParams( useLocation().search );
	}

	const query = useQuery();
	const page = query.get( 'page' );
	const method = query.get( 'method' );

	return (
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
						<Breadcrumb.Link
							href={ `${ srfm_admin.site_url }/wp-admin/admin.php?page=add-new-form` }
						>
							{ method !== 'ai' ? (
								<>
									<Breadcrumb.Page>
										{ __( 'Add New Form', 'sureforms' ) }
									</Breadcrumb.Page>
								</>
							) : (
								<span>
									{ __( 'Add New Form', 'sureforms' ) }
								</span>
							) }
						</Breadcrumb.Link>
					</Breadcrumb.Item>
				) }
				{ method === 'ai' && (
					<Breadcrumb.Item>
						<ChevronRight color="#bdc1c7" className="size-3" />
						<Breadcrumb.Link
							href={ `${ srfm_admin.site_url }/wp-admin/admin.php?page=add-new-form&method=ai` }
						>
							<Breadcrumb.Page>
								{ __( 'Generate with AI', 'sureforms' ) }
							</Breadcrumb.Page>
						</Breadcrumb.Link>
					</Breadcrumb.Item>
				) }
			</Breadcrumb.List>
		</Breadcrumb>
	);
};

export default Breadcrumbs;
