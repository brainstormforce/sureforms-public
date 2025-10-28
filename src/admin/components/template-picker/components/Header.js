import { useLocation } from 'react-router-dom';
import { Topbar, Button } from '@bsf/force-ui';
import Breadcrumbs from './Breadcrumbs';
import ICONS from './icons';
import { useEffect } from '@wordpress/element';

const Header = () => {
	function useQuery() {
		return new URLSearchParams( useLocation().search );
	}

	const query = useQuery();
	const method = query.get( 'method' );

	// if the methods is ai then hide the the scrollbar from body
	useEffect( () => {
		if ( method === 'template' ) {
			document.body.style.overflow = 'auto';
		} else {
			document.body.style.overflow = 'hidden';
		}
	}, [ method ] );

	return (
		<div>
			<Topbar className="fixed inset-x-0 top-0 z-[1] py-0 px-0 pt-0 pb-0 min-h-0 h-14 gap-4 shadow-sm bg-background-primary/75 backdrop-blur-[5px]">
				<Topbar.Left className="gap-3 pl-5">
					<Topbar.Item>
						<Button
							variant="ghost"
							onClick={ () => {
								window.location.href =
									'/wp-admin/admin.php?page=sureforms_menu';
							} }
							className="focus:[box-shadow:none] p-0 pt-1"
						>
							{ ICONS.logo }
						</Button>
					</Topbar.Item>
					<Topbar.Item>
						<Breadcrumbs />
					</Topbar.Item>
				</Topbar.Left>
			</Topbar>
		</div>
	);
};

export default Header;
