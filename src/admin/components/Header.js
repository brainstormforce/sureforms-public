import { renderToString, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import Logo from '../dashboard/templates/Logo';
import useWhatsNewRSS from '../../lib/whats-new/useWhatsNewRSS';
import { Topbar, Badge, Button, HamburgerMenu } from '@bsf/force-ui';
import { CircleHelp, ArrowUpRight, Megaphone } from 'lucide-react';
import { addQueryParam, cn } from '@Utils/Helpers';
import UpgradeNotice from './UpgradeNotice';

const {
	site_url: siteURL = '',
	is_pro_active: isProActive = false,
} = srfm_admin;

const NAV_ITEMS = [
	{
		slug: 'sureforms_menu',
		text: __( 'Dashboard', 'sureforms' ),
		link: `${ siteURL }/wp-admin/admin.php?page=sureforms_menu`,
	},
	{
		slug: 'sureforms_form',
		text: __( 'All Forms', 'sureforms' ),
		link: `${ siteURL }/wp-admin/edit.php?post_type=sureforms_form`,
	},
	{
		slug: 'sureforms_entries',
		text: __( 'Entries', 'sureforms' ),
		link: `${ siteURL }/wp-admin/admin.php?page=sureforms_entries`,
	},
	{
		slug: 'sureforms_form_settings',
		text: __( 'Settings', 'sureforms' ),
		link: `${ siteURL }/wp-admin/admin.php?page=sureforms_form_settings&tab=general-settings`,
	},
];

const Header = () => {
	const [ activePage, setActivePage ] = useState( null );
	const [ isLicenseActive, setIsLicenseActive ] = useState(
		srfm_admin?.is_license_active || false
	);
	useEffect( () => {
		const searchParams = new URLSearchParams( window.location.search );
		let currentPage = searchParams.get( 'page' );

		// If 'page' parameter is not present, check for 'post_type' parameter
		if ( ! currentPage ) {
			currentPage = searchParams.get( 'post_type' );
		}

		// Set the active page based on the current URL parameter
		setActivePage(
			NAV_ITEMS.find( ( item ) => item.slug === currentPage )
		);
	}, [] );

	useWhatsNewRSS( {
		uniqueKey: 'sureforms',
		rssFeedURL: 'https://sureforms.com/whats-new/feed/',
		selector: '#srfm_whats_new',
		icon: renderToString( <Megaphone className="size-4" /> ),
		flyout: {
			title: __( "What's New?", 'sureforms' ),
		},
		triggerButton: {
			icon: renderToString(
				<Megaphone className="size-4 text-text-primary" />
			),
		},
	} );

	useEffect( () => {
		window.addEventListener( 'srfm_license_status_updated', ( event ) => {
			if ( event?.detail ) {
				setIsLicenseActive( event.detail.is_license_active );
			}
		} );
	}, [] );

	return (
		<div className="top-8 sticky z-[1]">
			{ ( ! isProActive && ! isLicenseActive ) && <UpgradeNotice /> }
			<Topbar className="py-0 px-8 pt-0 pb-0 min-h-0 h-14 gap-4 shadow-sm bg-background-primary/75 backdrop-blur-[5px]">
				<Topbar.Left className="gap-3">
					<Topbar.Item className="w-auto h-auto lg:hidden">
						<HamburgerMenu>
							<HamburgerMenu.Toggle className="size-6" />
							<HamburgerMenu.Options>
								{ NAV_ITEMS.map( ( item ) => (
									<HamburgerMenu.Option
										key={ item.slug }
										active={
											activePage?.slug === item.slug
										}
										href={ item.link }
										iconPosition="left"
										rel="noopener noreferrer"
										tag="a"
										target="_self"
									>
										{ item.text }
									</HamburgerMenu.Option>
								) ) }
								<HamburgerMenu.Option
									href={ NAV_ITEMS[ 0 ].link }
									iconPosition="left"
									rel="noopener noreferrer"
									tag="a"
									target="_blank"
									className="text-link-primary gap-1"
								>
									{ __( 'Upgrade to Pro', 'sureforms' ) }{ ' ' }
									<ArrowUpRight className="size-4" />
								</HamburgerMenu.Option>
							</HamburgerMenu.Options>
						</HamburgerMenu>
					</Topbar.Item>
					<Topbar.Item>
						<Logo />
					</Topbar.Item>
				</Topbar.Left>
				<Topbar.Middle align="left" className="h-full hidden lg:flex">
					<Topbar.Item>
						<nav className="flex items-center gap-4 h-full">
							{ NAV_ITEMS.map( ( item ) => (
								<a
									className={ cn(
										'h-full text-text-secondary text-sm font-medium no-underline px-1 content-center relative focus:outline-none',
										activePage?.slug === item?.slug &&
											'text-text-primary before:content-[""] before:absolute before:h-px before:bg-border-interactive before:bottom-0 before:inset-x-0'
									) }
									href={ item.link }
									key={ item.slug }
								>
									{ item.text }
								</a>
							) ) }
						</nav>
					</Topbar.Item>
					<Topbar.Item>
						<Button
							icon={ <ArrowUpRight /> }
							iconPosition="right"
							variant="link"
							size="sm"
							className="h-full text-link-primary text-sm font-semibold no-underline hover:no-underline px-1 content-center [box-shadow:none] focus:[box-shadow:none] focus:outline-none"
							onClick={ () =>
								window.open(
									addQueryParam(
										srfm_admin?.pricing_page_url ||
											srfm_admin?.sureforms_pricing_page,
										'dashboard-header-cta'
									),
									'_blank',
									'noreferrer'
								)
							}
						>
							{ __( 'Upgrade to Pro', 'sureforms' ) }
						</Button>
					</Topbar.Item>
				</Topbar.Middle>
				<Topbar.Right>
					<Topbar.Item>
						<Badge
							label={ `V ${ srfm_admin?.plugin_version }` }
							size="xs"
							variant="neutral"
						/>
					</Topbar.Item>
					{ ( isProActive || isLicenseActive ) && (
						<Topbar.Item>
							<Button
								variant="ghost"
								className="p-0 hover:bg-transparent focus:[box-shadow:none] [box-shadow:none]"
								onClick={ () => {
									if ( isLicenseActive ) {
										return;
									}
									window.open(
										`${ siteURL }/wp-admin/admin.php?page=sureforms_form_settings&tab=account-settings`,
										'_self',
										'noopener noreferrer'
									);
								} }
								icon={
									<Badge
										label={
											isLicenseActive
												? __( 'Activated', 'sureforms' )
												: __( 'Activate', 'sureforms' )
										}
										size="xs"
										variant={
											isLicenseActive ? 'green' : 'red'
										}
									/>
								}
							></Button>
						</Topbar.Item>
					) }
					<Topbar.Item className="p-1">
						<CircleHelp className="size-4" />
					</Topbar.Item>
					<Topbar.Item className="gap-2">
						<div id="srfm_whats_new" className="[&_a]:!p-1" />
					</Topbar.Item>
				</Topbar.Right>
			</Topbar>
		</div>
	);
};

export default Header;
