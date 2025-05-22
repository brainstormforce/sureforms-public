import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useLocation } from 'react-router-dom';
import { Wand, X } from 'lucide-react';
import { Topbar, Button } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';
import CreditDetailsPopup from './CreditDetailsPopup.js';
import Breadcrumbs from './Breadcrumbs';
import ICONS from './icons';

const Header = () => {
	const [ showRevokePopover, setShowRevokePopover ] = useState( true );
	const formCreationleft = srfm_admin?.srfm_ai_usage_details?.remaining ?? 0;

	function useQuery() {
		return new URLSearchParams( useLocation().search );
	}

	const query = useQuery();

	const page = query.get( 'page' );
	const method = query.get( 'method' );

	// if the methods is ai then hide the the scrollbar from body
	useEffect( () => {
		if ( method === 'template' ) {
			document.body.style.overflow = 'auto';
		} else {
			document.body.style.overflow = 'hidden';
		}
	}, [ method ] );

	const isRegistered =
		srfm_admin?.srfm_ai_usage_details?.type === 'registered';
	const finalFormCreationCountRemaining =
		isRegistered && formCreationleft > 20 ? 20 : formCreationleft;

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
				<Topbar.Right>
					{ page === 'add-new-form' &&
						method === 'ai' &&
						( ! srfm_admin?.is_pro_active ||
							! srfm_admin?.is_pro_license_active ) && (
						<>
							<Topbar.Item>
								<CreditDetailsPopup
									finalFormCreationCountRemaining={
										finalFormCreationCountRemaining
									}
									setShowRevokePopover={
										setShowRevokePopover
									}
									showRevokePopover={ showRevokePopover }
								>
									<Button
										className={ cn(
											'flex items-center gap-0 px-3 py-1 rounded border border-border-primary [&>svg]:size-5',
											showRevokePopover &&
													'bg-background-secondary'
										) }
										onClick={ () =>
											setShowRevokePopover(
												( prev ) => ! prev
											)
										}
										variant="outline"
										size="sm"
										icon={ <Wand /> }
										iconPosition="right"
									>
										<span className="text-sm text-text-primary">
											{ wp.i18n.sprintf(
												// translators: %s: number of AI form generations left
												__(
													'%d AI form generations left',
													'sureforms'
												),
												finalFormCreationCountRemaining
											) }
										</span>
									</Button>
								</CreditDetailsPopup>
							</Topbar.Item>
						</>
					) }
					<Topbar.Item className="p-5">
						<Button
							variant="ghost"
							className="p-1 hover:bg-background-secondary rounded-sm transition-colors [box-shadow:none] focus:[box-shadow:none]"
							onClick={ () => {
								window.location.href =
									'/wp-admin/admin.php?page=sureforms_menu';
							} }
							icon={
								<X className="size-6 text-text-secondary" />
							}
						/>
					</Topbar.Item>
				</Topbar.Right>
			</Topbar>
		</div>
	);
};

export default Header;
