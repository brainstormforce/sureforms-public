import { cn } from '@Utils/Helpers';

const SidebarNav = ( {
	tabs: NAV_LINKS,
	selectedTab,
	setSelectedTab,
	parentTab,
} ) => {
	const handleTabClick = ( tab ) => () => {
		setSelectedTab( tab );
	};

	return (
		<nav className="shrink-0 w-[16rem] p-2 h-full bg-background-primary overflow-y-auto">
			<ul className="w-full h-full bg-background-primary p-2 space-y-1 m-0">
				{ NAV_LINKS.map( ( link ) => {
					const isActive =
						link.id === selectedTab ||
						( null !== parentTab && link.id === parentTab );
					return (
						link.parent === undefined && (
							<li key={ link.id }>
								<button
									className={ cn(
										'w-full flex items-center justify-start gap-2.5 px-2 py-2 rounded-md text-text-secondary bg-transparent hover:bg-brand-background-50 outline-none focus:outline-none focus:ring-1 focus:ring-focus border-0 transition-[colors,box-shadow] duration-200 cursor-pointer',
										isActive &&
											'active bg-brand-background-50 text-text-primary'
									) }
									onClick={ handleTabClick( link.id ) }
								>
									<span
										className={ cn(
											'contents [&>svg]:size-5 text-icon-secondary [&>svg]:!text-icon-secondary',
											isActive &&
												'text-icon-interactive [&>svg]:!text-icon-interactive'
										) }
									>
										{ link.icon }
									</span>
									<span className="text-base font-normal">
										{ link.label }
									</span>
								</button>
							</li>
						)
					);
				} ) }
			</ul>
		</nav>
	);
};

export default SidebarNav;
