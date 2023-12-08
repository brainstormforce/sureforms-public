import React from 'react';
import ICONS from './icons';
import Breadcrumbs from './Breadcrumbs';

const Header = () => {
	return (
		<div className="srfm-tp-header">
			<div className="srfm-tp-header-items">
				{ /** Logo & Breadcrumbs */ }
				<div className="srfm-tp-main-title">
					<Breadcrumbs />
				</div>
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
