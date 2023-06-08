import { __ } from '@wordpress/i18n';
import { Link, useLocation } from 'react-router-dom';

function useQuery() {
	return new URLSearchParams( useLocation().search );
}

const Navigation = () => {
	const activatedTab = useQuery();

	const navigation = [
		{
			name: __( 'General', 'sureforms' ),
			slug: 'general-settings',
		},
	];
	return (
		<nav
			className="flex flex-col max-w-[300px] min-h-full p-[20px] gap-[2px] w-[20%] bg-[#FBFBFC] shadow-md"
			style={ { borderRight: '1px solid #E4E7EB' } }
		>
			{ navigation.map( ( item ) => (
				<Link
					to={ {
						pathname: 'wp-admin/admin.php',
						search: `?page=sureforms_form_settings&tab=${ item.slug }`,
					} }
					key={ item.name }
					className={ `no-underline group p-2 cursor-pointer rounded-md transition-colors duration-300 ease-in-out hover:bg-wpprimary focus:bg-wpprimary focus:text-[#FBFBFC] focus:ring-0 ${
						activatedTab.get( 'tab' ) === item.slug
							? 'bg-wpprimary text-[#FBFBFC]'
							: 'text-[#111827]'
					}` }
				>
					<div className="">
						<span className="truncate text-[16px] group-hover:text-[#FBFBFC] transition-colors duration-300 ease-in-out">
							{ item.name }
						</span>
					</div>
				</Link>
			) ) }
		</nav>
	);
};

export default Navigation;
