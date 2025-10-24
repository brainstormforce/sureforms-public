import { __ } from '@wordpress/i18n';
import { Link, useLocation } from 'react-router-dom';
import { applyFilters } from '@wordpress/hooks';
import { cn } from '@Utils/Helpers';
import { Cpu, Settings, ShieldCheck, TriangleAlert } from 'lucide-react';
import { Accordion } from '@bsf/force-ui';
import ottoKitIcon from '@Image/suretriggers-grayscale.svg';

function useQuery() {
	return new URLSearchParams( useLocation().search );
}

export const navigation = applyFilters( 'srfm.settings.navigation', [
	{
		name: __( 'General Settings', 'sureforms' ),
		slug: 'general-settings',
		icon: <Settings />,
		helpText: __(
			'Set up email summaries, admin alerts, and data preferences to manage your forms with ease.',
			'sureforms'
		),
	},
	{
		name: __( 'Form Validation', 'sureforms' ),
		slug: 'validation-settings',
		icon: <ShieldCheck />,
		helpText: __(
			'Customize default error messages shown when users submit invalid or incomplete form entries.',
			'sureforms'
		),
	},
	{
		name: __( 'Spam Protection', 'sureforms' ),
		slug: 'security-settings',
		icon: <TriangleAlert />,
		submenu: [
			{
				name: __( 'reCAPTCHA', 'sureforms' ),
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
		helpText: __(
			'Enable spam protection for your forms using CAPTCHA services or honeypot security.',
			'sureforms'
		),
	},
	{
		name: __( 'OttoKit', 'sureforms' ),
		slug: 'ottokit-settings',
		icon: (
			<img
				src={ ottoKitIcon }
				className="size-4"
				alt={ __( 'OttoKit', 'sureforms' ) }
			/>
		),
		hidePageTitle: true, // Hide the page title for the OttoKit tab.
	},
	{
		name: __( 'Integrations', 'sureforms' ),
		slug: 'integration-settings',
		icon: <Cpu />,
		hidePageTitle: true, // Hide the page title for the Integrations tab.
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
				'flex items-start gap-3.5 px-2.5 py-2 rounded-md transition-[colors,box-shadow] text-text-secondary hover:bg-brand-background-50 no-underline hover:no-underline focus:outline-none focus:ring-1 focus:ring-focus w-full',
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
					'flex-shrink-0 mt-0.5 [&>svg]:size-5 [&>img]:size-5 text-icon-secondary [&>svg]:!text-icon-secondary',
					isActive() &&
						'text-icon-interactive [&>svg]:!text-icon-interactive'
				) }
			>
				{ Icon }
			</span>
			<span className="text-base font-normal break-words">{ label }</span>
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
			<Accordion.Item value="subpage" className="w-full">
				<Accordion.Trigger
					iconType="arrow"
					className={ cn(
						'p-2 pl-2.5 text-base font-normal hover:bg-brand-background-50 rounded-md no-underline cursor-pointer focus:outline-none focus:shadow-none transition ease-in-out duration-150',
						'flex items-center gap-2 text-left w-full',
						'[&_svg]:text-icon-secondary [&_svg]:size-5 [&_svg]:block',
						'[&_div]:font-normal [&_div]:text-text-secondary',
						isActive() &&
							'[&_div]:text-text-primary [&>div>svg]:!text-icon-interactive'
					) }
					aria-label={ `${ label } submenu` }
				>
					<span className="flex-shrink-0 mt-0.5">
						{ !! Icon && Icon }
					</span>
					<span className="break-words text-left pl-1 flex-1">
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
		<div className="flex-shrink-0 bg-background-primary">
			<div className="px-4 pb-4 pt-2 absolute">
				<nav className="space-y-2 w-[215px]">
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
