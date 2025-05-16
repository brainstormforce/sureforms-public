import { __ } from '@wordpress/i18n';
import { Link, useLocation } from 'react-router-dom';
import { applyFilters } from '@wordpress/hooks';
import { cn } from '@Utils/Helpers';
import { Cpu, Settings, ShieldCheck, TriangleAlert } from 'lucide-react';
import { Accordion } from '@bsf/force-ui';

function useQuery() {
	return new URLSearchParams( useLocation().search );
}

export const navigation = applyFilters( 'srfm.settings.navigation', [
	{
		name: __( 'General', 'sureforms' ),
		slug: 'general-settings',
		icon: <Settings />,
	},
	{
		name: __( 'Form Validation', 'sureforms' ),
		slug: 'validation-settings',
		icon: <ShieldCheck />,
	},
	{
		name: __( 'Spam Protection', 'sureforms' ),
		slug: 'security-settings',
		icon: <TriangleAlert />,
		submenu: [
			{
				name: __( 'reCaptcha', 'sureforms' ),
				slug: 'recaptcha',
			},
			{
				name: __( 'hCaptcha', 'sureforms' ),
				slug: 'hcaptcha',
			},
			{
				name: __( 'Turnstile', 'sureforms' ),
				slug: 'turnstile',
			},
			{
				name: __( 'Honeypot', 'sureforms' ),
				slug: 'honeypot',
			},
		],
	},
	{
		name: __( 'Integrations', 'sureforms' ),
		slug: 'integration-settings',
		icon: <Cpu />,
	},
] );

const NavLink = ( { label, path, icon: Icon, subPage = '' } ) => {
	const activatedTab = useQuery();
	const addActiveClass = false;

	const isActive = () => {
		if ( subPage ) {
			return activatedTab.get( 'subpage' ) === subPage;
		}

		if ( activatedTab.get( 'tab' ) === path ) {
			return true;
		}
		return false;
	};

	return (
		<Link
			to={ {
				location: `${ srfm_admin.site_url }/wp-admin/admin.php`,
				search: `?page=sureforms_form_settings&tab=${ path }${
					subPage ? `&subpage=${ subPage }` : ''
				}`,
			} }
			className={ cn(
				'flex items-center gap-3.5 px-2.5 py-2 rounded-md transition-[colors,box-shadow] text-text-secondary hover:bg-brand-background-50 no-underline hover:no-underline focus:outline-none focus:ring-1 focus:ring-focus',
				( isActive() ||
					applyFilters(
						'srfm.settings.nav.addActiveClass',
						addActiveClass,
						label,
						activatedTab.get( 'tab' )
					) ) &&
					'active bg-brand-background-50 text-text-primary'
			) }
		>
			<span
				className={ cn(
					'contents [&>svg]:size-5 text-icon-secondary [&>svg]:!text-icon-secondary',
					isActive() &&
						'text-icon-interactive [&>svg]:!text-icon-interactive'
				) }
			>
				{ Icon }
			</span>
			<span className="text-base font-normal">{ label }</span>
		</Link>
	);
};

const SubmenuAccordion = ( { label, path, icon: Icon, submenu } ) => {
	const activatedTab = useQuery();

	const isActive = () => {
		return activatedTab.get( 'tab' ) === path;
	};

	return (
		<Accordion iconType="arrow" type="simple" defaultValue="subpage">
			<Accordion.Item value="subpage" className="max-w-[230px] ">
				<Accordion.Trigger
					iconType="arrow"
					className={ cn(
						'p-2 pl-2.5 text-base font-normal hover:bg-brand-background-50 rounded-md no-underline cursor-pointer focus:outline-none focus:shadow-none transition ease-in-out duration-150',
						'flex items-start gap-2 text-left',
						'[&_svg]:text-icon-secondary [&_svg]:size-5',
						'[&_div]:font-normal [&_div]:text-text-secondary',
						isActive() &&
							'[&_div]:text-text-primary [&>div>svg]:!text-icon-interactive'
					) }
					aria-label={ `${ label } submenu` }
				>
					<span className="shrink-0">{ !! Icon && Icon }</span>
					<span className="whitespace-normal break-words text-left">
						{ label }
					</span>
				</Accordion.Trigger>
				<Accordion.Content className="p-2 [&>div]:pb-0">
					<div
						className="border-l border-solid border-r-0 border-t-0 border-b-0 border-border-subtle pl-2 ml-1 space-y-2"
						role="menu"
					>
						{ submenu.map(
							( {
								slug: subpage,
								name: subLabel,
								icon: SubIcon,
							} ) => (
								<NavLink
									key={ subpage }
									path={ path }
									label={ subLabel }
									icon={ SubIcon }
									subPage={ subpage }
								/>
							)
						) }
					</div>
				</Accordion.Content>
			</Accordion.Item>
		</Accordion>
	);
};

const Navigation = () => {
	return (
		<div className="flex-shrink-0 bg-white">
			<div className="px-4 pb-4 pt-2 absolute">
				<nav className="space-y-2">
					{ navigation.map( ( item ) =>
						item.submenu ? (
							<SubmenuAccordion
								key={ item.name }
								label={ item.name }
								path={ item.slug }
								icon={ item.icon }
								submenu={ item.submenu }
							/>
						) : (
							<NavLink
								key={ item.name }
								label={ item.name }
								path={ item.slug }
								icon={ item.icon }
							/>
						)
					) }
				</nav>
			</div>
		</div>
	);
};

export default Navigation;
