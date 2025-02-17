import Logo from './templates/Logo';
import { __ } from '@wordpress/i18n';
import { cn } from '@Utils/Helpers';

import {
	Container,
	Topbar,
	Badge,
	Title,
	Button,
	Avatar,
} from '@bsf/force-ui';
import {
	CircleHelp, ArrowUpRight, Megaphone, User, CirclePlay,
} from 'lucide-react';

export default () => {
	const tabItems = [
		{ slug: 'dashboard', text: __( 'Dashboard app', 'sureforms' ), link: 'http://seo-reference.local/wp-admin/admin.php?page=sureforms_menu' },
		{ slug: 'all-forms', text: __( 'All Forms', 'sureforms' ), link: 'http://seo-reference.local/wp-admin/edit.php?post_type=sureforms_form' },
		{ slug: 'entries', text: __( 'Entries', 'sureforms' ), link: 'http://seo-reference.local/wp-admin/admin.php?page=sureforms_entries' },
		{ slug: 'settings', text: __( 'Settings', 'sureforms' ), link: 'http://seo-reference.local/wp-admin/admin.php?page=sureforms_form_settings&tab=general-settings' },
	];

	const nav = <Container.Item>
		<Topbar
			className="w-auto min-h-[unset] h-16 shadow-sm p-0 pl-[12px] pr-[12px] relative z-[1]"
			gap={ 0 }
		>
			<Topbar.Left className="p-5">
				<Topbar.Item>
					<Logo />
				</Topbar.Item>
			</Topbar.Left>
			<Topbar.Middle
				align="left"
				className="h-full"
			>
				<Topbar.Item className="h-full gap-4">
					{
						tabItems.map( ( item ) => (
							<a
								className="content-center no-underline h-full py-0 px-1 m-0 bg-transparent outline-none shadow-none border-0 focus:outline-none text-text-secondary text-sm font-medium cursor-pointer"
								href={ item.link }
								target="_self"
								key={ item.slug }
							>
								{ item.text }
							</a>
						) )
					}
					<button
						className="content-center no-underline h-full inline-flex items-center py-0 px-1 m-0 bg-transparent outline-none shadow-none border-0 focus:outline-none cursor-pointer text-sm font-semibold text-brand-800"
					>
						<span>
							Upgrade Pro Features
						</span>
						<ArrowUpRight
							className="size-5"
							strokeWidth="1.5"
						/>
					</button>
				</Topbar.Item>
			</Topbar.Middle>
			<Topbar.Right
				className="p-5"
				gap="md"
			>
				<Topbar.Item>
					<Badge
						label="Free"
						size="xs"
						variant="neutral"
					/>
				</Topbar.Item>
				<Topbar.Item>
					<CircleHelp
						className="size-4 m-1"
						strokeWidth="1.5"
					/>
				</Topbar.Item>
				<Topbar.Item className="relative after:content-[''] after:inline-block after:size-1.5 after:bg-badge-background-important after:rounded-full after:absolute after:-top-0.5 after:left-5">
					<Megaphone
						className="size-4 m-1"
						strokeWidth="1.5"
					/>
				</Topbar.Item>
				<Topbar.Item>
					<Avatar
						className="bg-brand-background-50 text-icon-primary"
						size="sm"
					>
						<User strokeWidth="1.5" />
					</Avatar>
				</Topbar.Item>
			</Topbar.Right>
		</Topbar>
	</Container.Item>;

	const leftSidebar = <div
		className={ cn( 'h-full border-l border-solid', 'p-[32px]' ) }
	>
		<Container
			className="p-8 max-w-[82rem] mx-auto"
			cols={ 12 }
			containerType="grid"
			gap="2xl"
		>
			<Container.Item
				className="flex flex-col gap-8"
				colSpan={ 8 }
			>
				<Container
					className="bg-background-primary p-6 shadow-sm rounded-xl"
					cols={ 8 }
					containerType="grid"
					gap="2xl"
				>
					<Container.Item
						className="flex flex-col gap-6"
						colSpan={ 5 }
					>
						<div>
							<Title
								className="text-text-primary mb-1"
								size="lg"
								tag="h3"
								title="Welcome to Astra"
							/>
							<p className="text-sm text-text-secondary m-0">
								Astra is fast, fully customizable & beautiful WordPress theme suitable for blog, personal portfolio, business website and WooCommerce storefront. It is very lightweight and offers unparalleled speed.
							</p>
						</div>
						<div className="flex gap-3">
							<Button>
								Start Customizing
							</Button>
							<Button
								icon={ <CirclePlay /> }
								variant="ghost"
							>
								Watch a Quick Guide
							</Button>
						</div>
					</Container.Item>
					<Container.Item colSpan={ 3 }>
						<img
							alt="Astra video"
							className="w-full h-full object-cover rounded"
							src="https://placehold.co/272x154"
						/>
					</Container.Item>
				</Container>
			</Container.Item>
		</Container>
	</div>;

	return <Container
		className="h-full"
		containerType="flex"
		direction="column"
		gap={ 0 }
	>
		{ /* top banner */ }
		{ /* nav */ }
		{ nav }
		<Container.Item>
			<Container
				className="h-full grid-cols-[minmax(864px,63%)_minmax(416px,37%)] w-full mx-auto gap-[32px]"
				containerType="grid"
				gap={ 0 }
			>
				<Container.Item>
					{ /* left sidebar */ }
					{ leftSidebar }
				</Container.Item>
				<Container.Item>
					<div>
						<h1>This is right sidebar</h1>
					</div>
				</Container.Item>
			</Container>

		</Container.Item>
	</Container>;
};
