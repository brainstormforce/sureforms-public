import React from 'react';
import Logo from '../../../dashboard/templates/Logo';
import { ScBreadcrumb, ScBreadcrumbs } from '@surecart/components-react';
import ICONS from '../images/icons';

const Header = () => {
	return (
		<div className="srfm-tp-header">
			<div className="srfm-tp-header-items">
				{ /** Logo & Breadcrumbs */ }
				<h2 className="srfm-tp-main-title">
					<ScBreadcrumbs>
						<ScBreadcrumb>
							<Logo display="block" />
						</ScBreadcrumb>
						{ sureforms_admin?.breadcrumbs &&
							sureforms_admin.breadcrumbs.length > 0 &&
							sureforms_admin.breadcrumbs.map(
								( breadcrumb, index ) => (
									<ScBreadcrumb
										key={ index }
										href={ breadcrumb.link }
									>
										{ breadcrumb.title }
									</ScBreadcrumb>
								)
							) }
					</ScBreadcrumbs>
				</h2>
			</div>

			{ /** Close Icon */ }
			<div
				className="srfm-tp-header-close"
				onClick={ () =>
					( window.location.href = sureforms_admin.admin_url )
				}
			>
				<div>{ ICONS.close }</div>
			</div>
		</div>
	);
};

export default Header;
