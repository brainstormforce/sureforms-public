import { useLocation } from 'react-router-dom';
import { X } from 'lucide-react';
import { Topbar, Button } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';
import Breadcrumbs from './Breadcrumbs';
import ICONS from './icons';
import { useEffect } from '@wordpress/element';

/**
 * Top bar of the Add New Form screens.
 *
 * @param {Object}  props                 Props.
 * @param {*}       props.actions         Extra controls before the close button, e.g. the
 *                                        template library's "Start from scratch".
 * @param {boolean} props.hideLogoOnPhone Leave the logo out on phones, to make room
 *                                        for a search in `actions`.
 */
const Header = ( { actions, hideLogoOnPhone = false } ) => {
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
			<Topbar className="fixed inset-x-0 top-0 z-[1] py-0 px-0 pt-0 pb-0 min-h-0 h-14 gap-4 shadow-sm bg-background-primary">
				<Topbar.Left
					className={ cn(
						'gap-3 pl-5 max-[620px]:pl-3 min-w-0 shrink-0',
						hideLogoOnPhone && 'max-[620px]:hidden'
					) }
				>
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
				<Topbar.Right className="min-w-0 flex-1 justify-end gap-2">
					{ actions && (
						<Topbar.Item className="min-w-0 flex-1 justify-end">
							{ actions }
						</Topbar.Item>
					) }
					<Topbar.Item
						className={ cn(
							'p-5 max-[620px]:pe-3',
							actions && 'ps-2'
						) }
					>
						<Button
							variant="ghost"
							className="p-1 hover:bg-background-secondary rounded-sm transition-colors [box-shadow:none] focus:[box-shadow:none]"
							onClick={ () => {
								window.location.href =
									'/wp-admin/admin.php?page=sureforms_menu';
							} }
							icon={
								<X className="size-4 text-text-secondary" />
							}
						/>
					</Topbar.Item>
				</Topbar.Right>
			</Topbar>
		</div>
	);
};

export default Header;
